<?php

namespace App\Services\Providers\Adapters;

use App\Models\BlockchainNetwork;
use App\Models\CryptoAsset;
use App\Models\SwapOrder;
use App\Services\Providers\Contracts\LocalChainExecutionAdapterInterface;
use App\Services\Providers\DTOs\LocalChainQuoteData;
use App\Services\Swap\AmountNormalizer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class StellarSdexAdapter implements LocalChainExecutionAdapterInterface
{
    public const ADAPTER_KEY = 'stellar_sdex';
    private string $horizonUrl;

    public function __construct(?string $horizonUrl = null)
    {
        $this->horizonUrl = rtrim($horizonUrl ?: (string) config('services.stellar.horizon_url', 'https://horizon.stellar.org'), '/');
    }

    public function getAdapterKey(): string
    {
        return self::ADAPTER_KEY;
    }

    public function getDisplayName(): string
    {
        return 'Stellar SDEX & Liquidity Pools';
    }

    public function getChainFamily(): string
    {
        return 'stellar';
    }

    public function supports(BlockchainNetwork $network, CryptoAsset $intermediateAsset, CryptoAsset $destAsset): bool
    {
        return strtolower($network->chain_family) === 'stellar'
            && strtolower($intermediateAsset->network?->chain_family ?? '') === 'stellar'
            && strtolower($destAsset->network?->chain_family ?? '') === 'stellar';
    }

    public function checkAccountRequirements(BlockchainNetwork $network, string $recipientAddress, CryptoAsset $destAsset): array
    {
        $recipient = trim($recipientAddress);
        if ($recipient === '' || !\App\Services\Swap\ChainAddressValidator::isValidStellarAddress($recipient)) {
            return [
                'account_exists' => false,
                'prerequisites_met' => false,
                'requirements' => ['valid_stellar_address' => false],
                'message' => 'Invalid Stellar public key format or checksum (must be a valid 56-character G... address).',
            ];
        }

        try {
            $response = Http::timeout(8)->get("{$this->horizonUrl}/accounts/{$recipient}");

            if ($response->status() === 404) {
                return [
                    'account_exists' => false,
                    'prerequisites_met' => false,
                    'requirements' => [
                        'account_activated' => false,
                        'trustline_present' => false,
                    ],
                    'message' => 'Stellar account has not been activated on the ledger yet. It requires at least 1 XLM to activate.',
                ];
            }

            if ($response->failed()) {
                return [
                    'account_exists' => true,
                    'prerequisites_met' => true,
                    'requirements' => ['status_check_failed' => true],
                    'message' => 'Could not verify account trustlines due to network timeout. Ensure your trustline is established.',
                ];
            }

            $data = $response->json();
            $balances = $data['balances'] ?? [];

            // If destination asset is native XLM, no trustline required
            if ($destAsset->is_native || strtoupper($destAsset->symbol) === 'XLM') {
                return [
                    'account_exists' => true,
                    'prerequisites_met' => true,
                    'requirements' => [
                        'account_activated' => true,
                        'trustline_present' => true,
                    ],
                    'message' => null,
                ];
            }

            // Check trustline for issued asset
            $hasTrustline = false;
            foreach ($balances as $b) {
                $bCode = $b['asset_code'] ?? null;
                $bIssuer = $b['asset_issuer'] ?? null;
                if ($bCode && strcasecmp((string) $bCode, (string) $destAsset->symbol) === 0 && (!$destAsset->contract_or_issuer || (string) $bIssuer === (string) $destAsset->contract_or_issuer)) {
                    $hasTrustline = true;
                    break;
                }
            }

            return [
                'account_exists' => true,
                'prerequisites_met' => $hasTrustline,
                'requirements' => [
                    'account_activated' => true,
                    'trustline_present' => $hasTrustline,
                    'trustline_asset' => "{$destAsset->symbol}:{$destAsset->contract_or_issuer}",
                ],
                'message' => $hasTrustline
                    ? null
                    : "Missing {$destAsset->symbol} trustline on recipient wallet",
            ];
        } catch (\Throwable $e) {
            Log::warning("Stellar account check failed: {$e->getMessage()}");
            return [
                'account_exists' => true,
                'prerequisites_met' => true,
                'requirements' => ['status_check_exception' => true],
                'message' => null,
            ];
        }
    }

    public function requestSecondLegQuote(
        CryptoAsset $intermediateAsset,
        CryptoAsset $destAsset,
        string $intermediateAmountHuman,
        int $slippageBps,
        ?string $recipientAddress = null
    ): LocalChainQuoteData {
        $sourceAmount = AmountNormalizer::trimTrailingZeros($intermediateAmountHuman);
        if (bccomp($sourceAmount, '0', 7) <= 0) {
            throw new RuntimeException('Invalid intermediate swap amount for Stellar second leg.');
        }

        // Build Horizon query params
        $queryParams = [
            'source_amount' => number_format((float) $sourceAmount, 7, '.', ''),
        ];

        if ($intermediateAsset->is_native || strtoupper($intermediateAsset->symbol) === 'XLM') {
            $queryParams['source_asset_type'] = 'native';
        } else {
            $len = strlen($intermediateAsset->symbol);
            $queryParams['source_asset_type'] = $len <= 4 ? 'credit_alphanum4' : 'credit_alphanum12';
            $queryParams['source_asset_code'] = $intermediateAsset->symbol;
            $queryParams['source_asset_issuer'] = $intermediateAsset->contract_or_issuer;
        }

        if ($destAsset->is_native || strtoupper($destAsset->symbol) === 'XLM') {
            $queryParams['destination_assets'] = 'native';
        } else {
            $destDescriptor = "{$destAsset->symbol}:{$destAsset->contract_or_issuer}";
            $queryParams['destination_assets'] = $destDescriptor;
        }

        $rawResponse = null;
        try {
            $response = Http::timeout(10)->get("{$this->horizonUrl}/paths/strict-send", $queryParams);
            if ($response->successful()) {
                $rawResponse = $response->json();
            }
        } catch (\Throwable $e) {
            Log::warning("Stellar Horizon path query error: {$e->getMessage()}");
        }

        $records = $rawResponse['_embedded']['records'] ?? [];

        if (!empty($records)) {
            // Find the path offering the highest destination amount
            usort($records, fn ($a, $b) => bccomp((string) ($b['destination_amount'] ?? '0'), (string) ($a['destination_amount'] ?? '0'), 7));
            $bestPath = $records[0];
            $destAmount = AmountNormalizer::trimTrailingZeros((string) $bestPath['destination_amount']);
        } else {
            // If the token is registered with an estimated USD price or in local sandbox test mode:
            // Calculate synthetic cross-rate from catalog prices if available, or throw
            $destAmount = $this->calculateFallbackPriceEstimate($intermediateAsset, $destAsset, $sourceAmount);
            if ($destAmount === null) {
                throw new RuntimeException("No liquidity pool or SDEX orderbook path found for {$intermediateAsset->symbol} -> {$destAsset->symbol} on Stellar.");
            }
            $bestPath = ['source_amount' => $sourceAmount, 'destination_amount' => $destAmount, 'path' => []];
        }

        // Slippage deduction: minAmountOut = destAmount * (1 - slippageBps / 10000)
        $slippageFraction = bcdiv((string) $slippageBps, '10000', 8);
        $retentionFactor = bcsub('1', $slippageFraction, 8);
        $minAmountOut = bcmul($destAmount, $retentionFactor, 7);
        $minAmountOut = AmountNormalizer::trimTrailingZeros($minAmountOut);

        $exchangeRate = AmountNormalizer::calculateExchangeRate($sourceAmount, $destAmount);

        $intermediateBaseUnits = AmountNormalizer::toBaseUnits($sourceAmount, 7);
        // Stellar classic assets strictly use 7 decimals (stroops)
        $destDecimals = 7;
        $destBaseUnits = AmountNormalizer::toBaseUnits($destAmount, $destDecimals);

        $accountCheck = $recipientAddress ? $this->checkAccountRequirements($destAsset->network, $recipientAddress, $destAsset) : [];

        return new LocalChainQuoteData(
            adapterKey: self::ADAPTER_KEY,
            intermediateAmountInHuman: $sourceAmount,
            intermediateAmountInBaseUnits: $intermediateBaseUnits,
            expectedAmountOutHuman: $destAmount,
            expectedAmountOutBaseUnits: $destBaseUnits,
            minAmountOutHuman: $minAmountOut,
            exchangeRate: $exchangeRate,
            priceImpactBps: 25,
            estimatedGasOrFee: '0.00001 XLM',
            requiredWalletType: 'stellar_freighter',
            accountRequirements: $accountCheck,
            rawPathPayload: $bestPath
        );
    }

    public function buildUnsignedTransaction(SwapOrder $order, string $recipientAddress): array
    {
        $quote = $order->quote;
        $routingMeta = $quote?->routing_metadata_json ?? [];
        $secondLegMeta = $routingMeta['second_leg'] ?? [];

        $intermediateAsset = $order->intermediateAsset;
        $destAsset = $order->destinationAsset;

        if (!$intermediateAsset || !$destAsset) {
            return [
                'success' => false,
                'transaction_type' => 'stellar_path_payment_strict_send',
                'payload' => null,
                'instructions' => '',
                'error' => 'Missing intermediate or destination asset metadata.',
            ];
        }

        $sourceAmount = $order->intermediate_amount ?: ($secondLegMeta['intermediate_amount_human'] ?? '0');
        $destMin = $order->min_amount_out ?: ($secondLegMeta['min_amount_out_human'] ?? '0');

        $sendAsset = ($intermediateAsset->is_native || strtoupper($intermediateAsset->symbol) === 'XLM')
            ? ['type' => 'native']
            : ['type' => 'credit', 'code' => $intermediateAsset->symbol, 'issuer' => $intermediateAsset->contract_or_issuer];

        $destAssetPayload = ($destAsset->is_native || strtoupper($destAsset->symbol) === 'XLM')
            ? ['type' => 'native']
            : ['type' => 'credit', 'code' => $destAsset->symbol, 'issuer' => $destAsset->contract_or_issuer];

        $path = $secondLegMeta['raw_path']['path'] ?? [];

        $payload = [
            'operation' => 'pathPaymentStrictSend',
            'sendAsset' => $sendAsset,
            'sendAmount' => number_format((float) $sourceAmount, 7, '.', ''),
            'destination' => trim($recipientAddress),
            'destAsset' => $destAssetPayload,
            'destMin' => number_format((float) $destMin, 7, '.', ''),
            'path' => $path,
            'memo' => $order->recipient_memo,
            'networkPassphrase' => config('services.stellar.network_passphrase', 'Public Global Stellar Network ; September 2015'),
            'suggestedFeeStroops' => '100',
        ];

        return [
            'success' => true,
            'transaction_type' => 'stellar_path_payment_strict_send',
            'payload' => $payload,
            'instructions' => "Authorize the SDEX Path Payment transaction in your Stellar wallet to convert {$sourceAmount} {$intermediateAsset->symbol} into at least {$destMin} {$destAsset->symbol}.",
            'error' => null,
        ];
    }

    public function submitSignedTransaction(SwapOrder $order, string $signedTxData): array
    {
        try {
            $response = Http::asForm()->timeout(15)->post("{$this->horizonUrl}/transactions", [
                'tx' => trim($signedTxData),
            ]);

            if ($response->successful()) {
                $json = $response->json();
                $hash = $json['hash'] ?? null;
                return [
                    'success' => true,
                    'tx_hash' => $hash,
                    'explorer_url' => "https://stellar.expert/explorer/public/tx/{$hash}",
                    'status' => 'SUBMITTED',
                    'error' => null,
                ];
            }

            $errorJson = $response->json();
            $title = $errorJson['title'] ?? 'Transaction submission failed on Horizon';
            $detail = $errorJson['extras']['result_codes']['transaction'] ?? ($errorJson['detail'] ?? 'Horizon transaction rejected');

            return [
                'success' => false,
                'tx_hash' => null,
                'explorer_url' => null,
                'status' => 'FAILED',
                'error' => "{$title}: {$detail}",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'tx_hash' => null,
                'explorer_url' => null,
                'status' => 'FAILED',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function fetchTransactionStatus(SwapOrder $order): array
    {
        if (empty($order->second_leg_tx_hash)) {
            return [
                'confirmed' => false,
                'status' => 'PENDING',
                'actual_amount_out' => null,
                'error' => null,
            ];
        }

        try {
            $response = Http::timeout(8)->get("{$this->horizonUrl}/transactions/{$order->second_leg_tx_hash}");
            if ($response->successful()) {
                $json = $response->json();
                $successful = (bool) ($json['successful'] ?? false);
                return [
                    'confirmed' => $successful,
                    'status' => $successful ? 'COMPLETED' : 'FAILED',
                    'actual_amount_out' => $order->expected_amount_out,
                    'error' => $successful ? null : 'Transaction unconfirmed or failed on Stellar.',
                ];
            }
        } catch (\Throwable) {
            // Keep current status
        }

        return [
            'confirmed' => false,
            'status' => 'PROCESSING',
            'actual_amount_out' => null,
            'error' => null,
        ];
    }

    private function calculateFallbackPriceEstimate(CryptoAsset $intermediate, CryptoAsset $dest, string $amount): ?string
    {
        $intPrice = (float) ($intermediate->current_price_usd ?: 0.12);
        $destPrice = (float) ($dest->current_price_usd ?: 0);

        if ($destPrice > 0 && $intPrice > 0) {
            $totalUsd = bcmul($amount, (string) $intPrice, 8);
            $estimatedOut = bcdiv($totalUsd, (string) $destPrice, 7);
            return AmountNormalizer::trimTrailingZeros($estimatedOut);
        }

        return null;
    }
}

