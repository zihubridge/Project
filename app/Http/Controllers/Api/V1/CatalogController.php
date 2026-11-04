<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Swap\CatalogSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(
        private readonly CatalogSyncService $catalogService,
    ) {}

    public function networks(): JsonResponse
    {
        $networks = $this->catalogService->getActiveNetworks();

        return response()->json([
            'status' => 1,
            'count' => $networks->count(),
            'data' => $networks->map(fn ($net) => [
                'id' => $net->id,
                'slug' => $net->slug,
                'display_name' => $net->display_name,
                'chain_family' => $net->chain_family,
                'native_symbol' => $net->native_symbol,
                'evm_chain_id' => $net->evm_chain_id,
                'requires_memo_or_tag' => (bool) $net->requires_memo_or_tag,
                'logo_url' => $net->logo_url,
                'active_assets_count' => (int) ($net->active_assets_count ?? 0),
            ])->values(),
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }

    public function assets(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'network' => ['nullable', 'string', 'max:64'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $assets = $this->catalogService->getActiveAssets(
            $validated['network'] ?? null,
            $validated['search'] ?? null,
        );

        return response()->json([
            'status' => 1,
            'count' => $assets->count(),
            'data' => $assets->map(fn ($asset) => [
                'id' => $asset->id,
                'network_id' => $asset->blockchain_network_id,
                'network_slug' => $asset->network?->slug,
                'network_name' => $asset->network?->display_name,
                'chain_family' => $asset->network?->chain_family,
                'requires_memo_or_tag' => (bool) $asset->network?->requires_memo_or_tag,
                'symbol' => $asset->symbol,
                'display_name' => $asset->display_name,
                'is_native' => (bool) $asset->is_native,
                'contract_or_issuer' => $asset->contract_or_issuer,
                'decimals' => (int) $asset->decimals,
                'current_price_usd' => $asset->current_price_usd !== null ? (string) $asset->current_price_usd : null,
                'logo_url' => $asset->logo_url,
                'providers' => $asset->providerMappings
                    ->map(fn ($m) => $m->provider?->slug)
                    ->filter()
                    ->unique()
                    ->values(),
            ])->values(),
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }
}

