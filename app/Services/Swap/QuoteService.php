<?php

namespace App\Services\Swap;

use App\Models\CryptoAsset;
use App\Models\ProviderSupportedAsset;
use App\Models\SwapQuote;
use App\Services\Providers\DTOs\QuoteRequestData;
use App\Services\Providers\SwapProviderRegistry;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class QuoteService
{
    public function __construct(
        private readonly SwapProviderRegistry $registry,
        private readonly ChainAddressValidator $addressValidator,
        private readonly MultiHopRoutingService $multiHopService,
    ) {}

    public function createQuote(
        int $sourceAssetId,
        int $destinationAssetId,
        string $amountInHuman,
        int $slippageBps = 100,
        ?string $recipientAddress = null,
        ?string $refundAddress = null,
    ): SwapQuote {
        if ($sourceAssetId === $destinationAssetId) {
            throw new InvalidArgumentException('Source and destination assets must be different.');
        }

        if ($slippageBps < 1 || $slippageBps > 5000) {
            throw new InvalidArgumentException('Slippage tolerance must be between 1 bps (0.01%) and 5000 bps (50%).');
        }

        $sourceAsset = CryptoAsset::with('network')->where('is_enabled', true)->find($sourceAssetId);
        $destAsset = CryptoAsset::with('network')->where('is_enabled', true)->find($destinationAssetId);

        if (!$sourceAsset || !$sourceAsset->network || !$sourceAsset->network->is_enabled) {
            throw new InvalidArgumentException('Selected source asset or network is not currently available.');
        }

        if (!$destAsset || !$destAsset->network || !$destAsset->network->is_enabled) {
            throw new InvalidArgumentException('Selected destination asset or network is not currently available.');
        }

        if ($recipientAddress !== null && trim($recipientAddress) !== '') {
            $check = $this->addressValidator->validate($destAsset->network, $recipientAddress);
            if (!$check['valid']) {
                throw new InvalidArgumentException($check['message']);
            }
        }

        if ($refundAddress !== null && trim($refundAddress) !== '') {
            $check = $this->addressValidator->validate($sourceAsset->network, $refundAddress);
            if (!$check['valid']) {
                throw new InvalidArgumentException($check['message']);
            }
        }

        $amountBaseUnits = AmountNormalizer::toBaseUnits($amountInHuman, $sourceAsset->decimals);

        $providerService = $this->registry->getDefault();

        $sourceMapping = ProviderSupportedAsset::query()
            ->whereHas('provider', fn ($q) => $q->where('slug', $providerService->getProviderSlug())->where('is_enabled', true))
            ->where('crypto_asset_id', $sourceAsset->id)
            ->where('is_active', true)
            ->with('provider')
            ->first();

        $destMapping = ProviderSupportedAsset::query()
            ->whereHas('provider', fn ($q) => $q->where('slug', $providerService->getProviderSlug())->where('is_enabled', true))
            ->where('crypto_asset_id', $destAsset->id)
            ->where('is_active', true)
            ->first();

        // ---------------------------------------------------------------------
        // Priority 1: Direct Provider Route
        // ---------------------------------------------------------------------
        if ($sourceMapping && $destMapping) {
            $quoteRequest = new QuoteRequestData(
                originProviderAssetId: $sourceMapping->provider_asset_id,
                destinationProviderAssetId: $destMapping->provider_asset_id,
                originNetworkSlug: $sourceAsset->network->slug,
                destinationNetworkSlug: $destAsset->network->slug,
                amountInHuman: AmountNormalizer::trimTrailingZeros($amountInHuman),
                amountInBaseUnits: $amountBaseUnits,
                originDecimals: $sourceAsset->decimals,
                destinationDecimals: $destAsset->decimals,
                slippageBps: $slippageBps,
                swapType: 'EXACT_INPUT',
                recipientAddress: $recipientAddress ? trim($recipientAddress) : null,
                refundAddress: $refundAddress ? trim($refundAddress) : null,
            );

            try {
                $normalizedQuote = $providerService->requestQuote($quoteRequest);

                $quote = SwapQuote::create([
                    'quote_uuid' => (string) Str::uuid(),
                    'swap_provider_id' => $sourceMapping->swap_provider_id,
                    'source_asset_id' => $sourceAsset->id,
                    'destination_asset_id' => $destAsset->id,
                    'swap_type' => 'EXACT_INPUT',
                    'is_multi_hop' => false,
                    'amount_in' => $normalizedQuote->amountInHuman,
                    'amount_in_base_units' => $normalizedQuote->amountInBaseUnits,
                    'expected_amount_out' => $normalizedQuote->expectedAmountOutHuman,
                    'expected_amount_out_base_units' => $normalizedQuote->expectedAmountOutBaseUnits,
                    'min_amount_out' => $normalizedQuote->minAmountOutHuman,
                    'min_amount_out_base_units' => $normalizedQuote->minAmountOutBaseUnits,
                    'exchange_rate' => $normalizedQuote->exchangeRate,
                    'amount_in_usd' => $normalizedQuote->amountInUsd,
                    'amount_out_usd' => $normalizedQuote->amountOutUsd,
                    'slippage_bps' => $normalizedQuote->slippageBps,
                    'platform_fee_bps' => $normalizedQuote->platformFeeBps,
                    'estimated_duration_seconds' => $normalizedQuote->estimatedDurationSeconds,
                    'expires_at' => $normalizedQuote->expiresAt,
                    'fee_breakdown_json' => $normalizedQuote->feeBreakdown,
                    'provider_quote_payload_json' => $normalizedQuote->providerRawPayload,
                ]);

                return $quote->load(['provider', 'sourceAsset.network', 'destinationAsset.network', 'intermediateAsset.network']);
            } catch (\Throwable $directException) {
                // If direct route threw an error, continue to Priority 2 fallback
            }
        }

        // ---------------------------------------------------------------------
        // Priority 2: Destination Blockchain Multi-Hop Fallback
        // ---------------------------------------------------------------------
        $fallback = $this->multiHopService->discoverBestFallbackRoute(
            sourceAsset: $sourceAsset,
            destAsset: $destAsset,
            amountInHuman: $amountInHuman,
            slippageBps: $slippageBps,
            recipientAddress: $recipientAddress,
            refundAddress: $refundAddress
        );

        if ($fallback !== null) {
            $quote = SwapQuote::create([
                'quote_uuid' => (string) Str::uuid(),
                'swap_provider_id' => $sourceMapping ? $sourceMapping->swap_provider_id : ($destMapping?->swap_provider_id ?? 1),
                'source_asset_id' => $sourceAsset->id,
                'destination_asset_id' => $destAsset->id,
                'swap_type' => 'EXACT_INPUT',
                'is_multi_hop' => true,
                'intermediate_asset_id' => $fallback->intermediateAsset->id,
                'intermediate_amount' => $fallback->intermediateAmountHuman,
                'intermediate_amount_base_units' => $fallback->intermediateAmountBaseUnits,
                'second_leg_adapter_key' => $fallback->secondLegAdapterKey,
                'amount_in' => $fallback->leg1Quote->amountInHuman,
                'amount_in_base_units' => $fallback->leg1Quote->amountInBaseUnits,
                'expected_amount_out' => $fallback->finalExpectedAmountOutHuman,
                'expected_amount_out_base_units' => $fallback->finalExpectedAmountOutBaseUnits,
                'min_amount_out' => $fallback->finalMinAmountOutHuman,
                'min_amount_out_base_units' => $fallback->finalMinAmountOutBaseUnits,
                'exchange_rate' => $fallback->overallExchangeRate,
                'amount_in_usd' => $fallback->leg1Quote->amountInUsd,
                'amount_out_usd' => null,
                'slippage_bps' => $slippageBps,
                'platform_fee_bps' => $fallback->platformFeeBps,
                'estimated_duration_seconds' => $fallback->totalDurationSeconds,
                'expires_at' => $fallback->leg1Quote->expiresAt,
                'fee_breakdown_json' => $fallback->leg1Quote->feeBreakdown,
                'routing_metadata_json' => $fallback->routingMetadata,
                'provider_quote_payload_json' => [
                    'leg1' => $fallback->leg1Quote->providerRawPayload,
                    'leg2' => $fallback->leg2Quote->rawPathPayload,
                ],
            ]);

            return $quote->load(['provider', 'sourceAsset.network', 'destinationAsset.network', 'intermediateAsset.network']);
        }

        // ---------------------------------------------------------------------
        // Priority 3: No Valid Route
        // ---------------------------------------------------------------------
        throw new RuntimeException("No direct or fallback route currently available to swap {$sourceAsset->symbol} into {$destAsset->symbol}.");
    }
}

