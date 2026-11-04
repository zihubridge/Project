<?php

namespace App\Services\Swap;

use App\Models\BlockchainNetwork;
use App\Models\CryptoAsset;
use App\Models\ProviderSupportedAsset;
use App\Models\SwapProvider;
use App\Services\Providers\Contracts\SwapExecutionProviderInterface;
use App\Services\Providers\SwapProviderRegistry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CatalogSyncService
{
    public const CACHE_KEY_NETWORKS = 'swap_catalog:networks:v1';

    public function __construct(
        private readonly SwapProviderRegistry $registry,
    ) {}

    /**
     * Synchronize the supported networks and assets from the active execution provider(s).
     *
     * @return array{providers_synced: int, networks_upserted: int, assets_upserted: int}
     */
    public function syncAllProviders(): array
    {
        $providersSynced = 0;
        $networksUpserted = 0;
        $assetsUpserted = 0;

        foreach ($this->registry->all() as $providerService) {
            $stats = $this->syncProvider($providerService);
            $providersSynced++;
            $networksUpserted += $stats['networks_upserted'];
            $assetsUpserted += $stats['assets_upserted'];
        }

        Cache::forget(self::CACHE_KEY_NETWORKS);

        return [
            'providers_synced' => $providersSynced,
            'networks_upserted' => $networksUpserted,
            'assets_upserted' => $assetsUpserted,
        ];
    }

    /**
     * @return array{networks_upserted: int, assets_upserted: int}
     */
    public function syncProvider(SwapExecutionProviderInterface $providerService): array
    {
        $assets = $providerService->fetchSupportedAssets();
        $now = Carbon::now();

        return DB::transaction(function () use ($providerService, $assets, $now) {
            $providerModel = SwapProvider::updateOrCreate(
                ['slug' => $providerService->getProviderSlug()],
                [
                    'display_name' => $providerService->getDisplayName(),
                    'driver_class' => $providerService::class,
                    'is_enabled' => true,
                    'last_catalog_synced_at' => $now,
                ]
            );

            $seenProviderAssetIds = [];
            $networkCache = [];
            $assetsCount = 0;

            foreach ($assets as $assetDto) {
                if (!isset($networkCache[$assetDto->networkSlug])) {
                    $meta = ChainMetadataCatalog::resolveNetworkMetadata($assetDto->networkSlug);

                    $network = BlockchainNetwork::updateOrCreate(
                        ['slug' => $assetDto->networkSlug],
                        [
                            'display_name' => $assetDto->networkDisplayName ?: $meta['display_name'],
                            'chain_family' => $assetDto->chainFamily ?: $meta['chain_family'],
                            'native_symbol' => $meta['native_symbol'],
                            'evm_chain_id' => $meta['evm_chain_id'],
                            'requires_memo_or_tag' => $meta['requires_memo_or_tag'],
                            'address_regex' => $meta['address_regex'],
                            'explorer_tx_url_pattern' => $meta['explorer_tx_url_pattern'],
                            'explorer_address_url_pattern' => $meta['explorer_address_url_pattern'],
                            'is_enabled' => true,
                        ]
                    );

                    $networkCache[$assetDto->networkSlug] = $network;
                }

                /** @var BlockchainNetwork $network */
                $network = $networkCache[$assetDto->networkSlug];

                $cryptoAsset = CryptoAsset::updateOrCreate(
                    [
                        'blockchain_network_id' => $network->id,
                        'symbol' => $assetDto->symbol,
                        'contract_or_issuer' => $assetDto->contractOrIssuer,
                    ],
                    [
                        'display_name' => $assetDto->displayName,
                        'is_native' => $assetDto->isNative,
                        'decimals' => $assetDto->decimals,
                        'current_price_usd' => $assetDto->priceUsd,
                        'price_updated_at' => $assetDto->priceUpdatedAt ? Carbon::parse($assetDto->priceUpdatedAt) : $now,
                        'logo_url' => $assetDto->logoUrl,
                        'is_enabled' => true,
                    ]
                );

                ProviderSupportedAsset::updateOrCreate(
                    [
                        'swap_provider_id' => $providerModel->id,
                        'provider_asset_id' => $assetDto->providerAssetId,
                    ],
                    [
                        'crypto_asset_id' => $cryptoAsset->id,
                        'provider_chain_key' => $assetDto->networkSlug,
                        'is_active' => true,
                        'provider_metadata_json' => $assetDto->rawMetadata,
                        'last_seen_at' => $now,
                    ]
                );

                $seenProviderAssetIds[] = $assetDto->providerAssetId;
                $assetsCount++;
            }

            if (!empty($seenProviderAssetIds)) {
                ProviderSupportedAsset::where('swap_provider_id', $providerModel->id)
                    ->whereNotIn('provider_asset_id', $seenProviderAssetIds)
                    ->update(['is_active' => false]);
            }

            return [
                'networks_upserted' => count($networkCache),
                'assets_upserted' => $assetsCount,
            ];
        });
    }

    /**
     * Ensure catalog has been synced at least once; if empty, attempt live sync.
     */
    public function ensureCatalogAvailable(): void
    {
        if (ProviderSupportedAsset::where('is_active', true)->exists()) {
            return;
        }

        try {
            $this->syncAllProviders();
        } catch (\Throwable $e) {
            Log::warning('Automatic catalog synchronization failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get all active blockchain networks that have at least one active provider-supported asset.
     */
    public function getActiveNetworks(): Collection
    {
        $this->ensureCatalogAvailable();

        return BlockchainNetwork::query()
            ->where('is_enabled', true)
            ->whereHas('assets', function ($q) {
                $q->where('is_enabled', true)
                    ->where(function ($sub) {
                        $sub->whereHas('providerMappings', function ($mq) {
                            $mq->where('is_active', true)
                                ->whereHas('provider', fn ($pq) => $pq->where('is_enabled', true));
                        })->orWhereHas('network.fallbackRoutes', function ($fq) {
                            $fq->where('is_enabled', true);
                        });
                    });
            })
            ->withCount(['assets as active_assets_count' => function ($q) {
                $q->where('is_enabled', true)
                    ->where(function ($sub) {
                        $sub->whereHas('providerMappings', fn ($mq) => $mq->where('is_active', true))
                            ->orWhereHas('network.fallbackRoutes', fn ($fq) => $fq->where('is_enabled', true));
                    });
            }])
            ->orderBy('display_name')
            ->get();
    }

    /**
     * Get active provider-supported assets, optionally filtered by network slug and search query.
     */
    public function getActiveAssets(?string $networkSlug = null, ?string $search = null): Collection
    {
        $this->ensureCatalogAvailable();

        $query = CryptoAsset::query()
            ->with(['network', 'providerMappings' => function ($q) {
                $q->where('is_active', true)->with('provider');
            }])
            ->where('is_enabled', true)
            ->whereHas('network', function ($nq) use ($networkSlug) {
                $nq->where('is_enabled', true);
                if ($networkSlug !== null && trim($networkSlug) !== '') {
                    $nq->where('slug', strtolower(trim($networkSlug)));
                }
            })
            ->where(function ($sub) {
                $sub->whereHas('providerMappings', function ($mq) {
                    $mq->where('is_active', true)
                        ->whereHas('provider', fn ($pq) => $pq->where('is_enabled', true));
                })->orWhereHas('network.fallbackRoutes', function ($fq) {
                    $fq->where('is_enabled', true);
                });
            });

        if ($search !== null && trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $query->where(function ($sq) use ($term) {
                $sq->where('symbol', 'like', $term)
                    ->orWhere('display_name', 'like', $term)
                    ->orWhere('contract_or_issuer', 'like', $term);
            });
        }

        return $query
            ->orderByDesc('is_native')
            ->orderBy('symbol')
            ->get();
    }
}

