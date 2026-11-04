<?php

namespace App\Services\Swap;

use App\Models\CryptoAsset;
use App\Models\FallbackRoute;
use App\Models\PlatformFeeConfiguration;
use App\Models\ProviderSupportedAsset;
use App\Services\Providers\DTOs\CompositeMultiHopQuoteResult;
use App\Services\Providers\DTOs\QuoteRequestData;
use App\Services\Providers\LocalChainAdapterRegistry;
use App\Services\Providers\SwapProviderRegistry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MultiHopRoutingService
{
    public function __construct(
        private readonly SwapProviderRegistry $providerRegistry,
        private readonly LocalChainAdapterRegistry $adapterRegistry,
        private readonly ChainAddressValidator $addressValidator,
    ) {}

    /**
     * Discover and calculate the best multi-hop fallback route for an unsupported destination asset.
     */
    public function discoverBestFallbackRoute(
        CryptoAsset $sourceAsset,
        CryptoAsset $destAsset,
        string $amountInHuman,
        int $slippageBps,
        ?string $recipientAddress = null,
        ?string $refundAddress = null,
    ): ?CompositeMultiHopQuoteResult {
        // Retrieve enabled fallback routes configured for this destination network
        $candidates = FallbackRoute::with(['intermediateAsset.network', 'firstLegProvider'])
            ->where('destination_network_id', $destAsset->blockchain_network_id)
            ->where('is_enabled', true)
            ->where(function ($q) use ($destAsset) {
                $q->whereNull('destination_token_id')
                    ->orWhere('destination_token_id', $destAsset->id);
            })
            ->orderBy('priority')
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        $bestResult = null;

        foreach ($candidates as $routeConfig) {
            $intermediateAsset = $routeConfig->intermediateAsset;
            if (!$intermediateAsset || !$intermediateAsset->is_enabled) {
                continue;
            }

            // Verify min/max limits
            if ($routeConfig->min_amount && bccomp($amountInHuman, (string) $routeConfig->min_amount, 8) < 0) {
                continue;
            }
            if ($routeConfig->max_amount && bccomp($amountInHuman, (string) $routeConfig->max_amount, 8) > 0) {
                continue;
            }

            // Check if second-leg adapter exists
            $adapterKey = $routeConfig->second_leg_adapter_key;
            if (!$this->adapterRegistry->has($adapterKey)) {
                continue;
            }
            $adapter = $this->adapterRegistry->get($adapterKey);

            // Verify adapter supports this pair on destination network
            if (!$adapter->supports($destAsset->network, $intermediateAsset, $destAsset)) {
                continue;
            }

            // Verify first-leg provider supports sourceAsset -> intermediateAsset
            $firstLegProviderModel = $routeConfig->firstLegProvider;
            if (!$firstLegProviderModel || !$firstLegProviderModel->is_enabled) {
                continue;
            }

            try {
                $firstLegService = $this->providerRegistry->get($firstLegProviderModel->slug);
            } catch (\Throwable) {
                continue;
            }

            $sourceMapping = ProviderSupportedAsset::query()
                ->where('swap_provider_id', $firstLegProviderModel->id)
                ->where('crypto_asset_id', $sourceAsset->id)
                ->where('is_active', true)
                ->first();

            $intermediateMapping = ProviderSupportedAsset::query()
                ->where('swap_provider_id', $firstLegProviderModel->id)
                ->where('crypto_asset_id', $intermediateAsset->id)
                ->where('is_active', true)
                ->first();

            if (!$sourceMapping || !$intermediateMapping) {
                continue;
            }

            // Build Leg 1 quote request (Origin -> Intermediate on Destination Chain)
            // Destination address for Leg 1 is the user's destination wallet (non-custodial!)
            $amountBaseUnits = AmountNormalizer::toBaseUnits($amountInHuman, $sourceAsset->decimals);

            $leg1Request = new QuoteRequestData(
                originProviderAssetId: $sourceMapping->provider_asset_id,
                destinationProviderAssetId: $intermediateMapping->provider_asset_id,
                originNetworkSlug: $sourceAsset->network->slug,
                destinationNetworkSlug: $intermediateAsset->network->slug,
                amountInHuman: AmountNormalizer::trimTrailingZeros($amountInHuman),
                amountInBaseUnits: $amountBaseUnits,
                originDecimals: $sourceAsset->decimals,
                destinationDecimals: $intermediateAsset->decimals,
                slippageBps: min($slippageBps, (int) $routeConfig->max_slippage_bps),
                swapType: 'EXACT_INPUT',
                recipientAddress: $recipientAddress ? trim($recipientAddress) : null,
                refundAddress: $refundAddress ? trim($refundAddress) : null,
            );

            try {
                $leg1Quote = $firstLegService->requestQuote($leg1Request);
            } catch (\Throwable $e) {
                Log::debug("Leg 1 quote failed for intermediate asset {$intermediateAsset->symbol}: {$e->getMessage()}");
                continue;
            }

            $intermediateAmountHuman = $leg1Quote->expectedAmountOutHuman;
            if (bccomp($intermediateAmountHuman, '0', 8) <= 0) {
                continue;
            }

            // Request Leg 2 quote (Intermediate -> Final Destination Asset on local DEX)
            try {
                $leg2Quote = $adapter->requestSecondLegQuote(
                    $intermediateAsset,
                    $destAsset,
                    $intermediateAmountHuman,
                    $slippageBps,
                    $recipientAddress
                );
            } catch (\Throwable $e) {
                Log::debug("Leg 2 quote failed for adapter {$adapterKey}: {$e->getMessage()}");
                continue;
            }

            $finalOutHuman = $leg2Quote->expectedAmountOutHuman;
            if (bccomp($finalOutHuman, '0', 8) <= 0) {
                continue;
            }

            // Verify account prerequisites if recipient address is provided
            if ($recipientAddress && isset($leg2Quote->accountRequirements['prerequisites_met']) && !$leg2Quote->accountRequirements['prerequisites_met']) {
                $prereqMsg = $leg2Quote->accountRequirements['message']
                    ?? "Recipient wallet lacks an established trustline for {$destAsset->symbol}.";
                throw new \RuntimeException($prereqMsg);
            }

            // Overall exchange rate from source asset to final destination asset
            $overallRate = AmountNormalizer::calculateExchangeRate($amountInHuman, $finalOutHuman);

            $platformFeeConfig = PlatformFeeConfiguration::getActive();
            $platformFeeBps = ($platformFeeConfig && $platformFeeConfig->is_enabled) ? $platformFeeConfig->fee_bps : 0;

            $routingMetadata = [
                'type' => 'MULTI_HOP_FALLBACK',
                'route_id' => $routeConfig->id,
                'destination_network' => $destAsset->network->slug,
                'path_description' => "{$sourceAsset->symbol} ({$sourceAsset->network->display_name}) -> {$intermediateAsset->symbol} ({$intermediateAsset->network->display_name}) -> {$destAsset->symbol}",
                'leg1' => [
                    'provider' => $firstLegProviderModel->slug,
                    'provider_name' => $firstLegProviderModel->display_name,
                    'input_asset' => $sourceAsset->symbol,
                    'input_amount' => $amountInHuman,
                    'intermediate_asset' => $intermediateAsset->symbol,
                    'intermediate_amount' => $intermediateAmountHuman,
                    'intermediate_amount_base_units' => $leg1Quote->expectedAmountOutBaseUnits,
                    'slippage_bps' => $leg1Request->slippageBps,
                ],
                'leg2' => [
                    'adapter' => $adapter->getAdapterKey(),
                    'adapter_name' => $adapter->getDisplayName(),
                    'chain_family' => $adapter->getChainFamily(),
                    'required_wallet_type' => $leg2Quote->requiredWalletType,
                    'intermediate_asset' => $intermediateAsset->symbol,
                    'intermediate_amount' => $intermediateAmountHuman,
                    'final_asset' => $destAsset->symbol,
                    'expected_final_amount' => $finalOutHuman,
                    'min_final_amount' => $leg2Quote->minAmountOutHuman,
                    'estimated_gas_or_fee' => $leg2Quote->estimatedGasOrFee,
                    'account_requirements' => $leg2Quote->accountRequirements,
                    'raw_path' => $leg2Quote->rawPathPayload,
                ],
                'single_platform_fee_charged_on_leg1' => true,
                'platform_fee_bps' => $platformFeeBps,
            ];

            $result = new CompositeMultiHopQuoteResult(
                leg1Quote: $leg1Quote,
                leg2Quote: $leg2Quote,
                intermediateAsset: $intermediateAsset,
                intermediateAmountHuman: $intermediateAmountHuman,
                intermediateAmountBaseUnits: $leg1Quote->expectedAmountOutBaseUnits,
                finalExpectedAmountOutHuman: $finalOutHuman,
                finalExpectedAmountOutBaseUnits: $leg2Quote->expectedAmountOutBaseUnits,
                finalMinAmountOutHuman: $leg2Quote->minAmountOutHuman,
                finalMinAmountOutBaseUnits: $leg2Quote->minAmountOutHuman,
                overallExchangeRate: $overallRate,
                platformFeeBps: $platformFeeBps,
                totalDurationSeconds: $leg1Quote->estimatedDurationSeconds + 30,
                secondLegAdapterKey: $adapterKey,
                routingMetadata: $routingMetadata,
            );

            // Select candidate with the highest expected final output
            if ($bestResult === null || bccomp($result->finalExpectedAmountOutHuman, $bestResult->finalExpectedAmountOutHuman, 8) > 0) {
                $bestResult = $result;
            }
        }

        return $bestResult;
    }
}

