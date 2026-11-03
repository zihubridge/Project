<?php

namespace App\Services\Providers\NearIntents;

use App\Enums\SwapStatus;
use App\Models\SwapOrder;
use App\Services\Providers\Contracts\SwapExecutionProviderInterface;
use App\Services\Providers\DTOs\NormalizedAssetData;
use App\Services\Providers\DTOs\NormalizedQuoteData;
use App\Services\Providers\DTOs\NormalizedSwapIntentData;
use App\Services\Providers\DTOs\NormalizedSwapStatusData;
use App\Services\Providers\DTOs\QuoteRequestData;
use App\Services\Providers\DTOs\SwapInitiationData;
use App\Services\Swap\AmountNormalizer;
use App\Services\Swap\ChainMetadataCatalog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class NearIntentsProvider implements SwapExecutionProviderInterface
{
    public const SLUG = 'near_intents';
    public const DISPLAY_NAME = 'NEAR Intents';

    public function __construct(
        private readonly NearIntentsHttpClient $client,
    ) {}

    public function getProviderSlug(): string
    {
        return self::SLUG;
    }

    public function getDisplayName(): string
    {
        return self::DISPLAY_NAME;
    }

    /**
     * @return array<int, NormalizedAssetData>
     */
    public function fetchSupportedAssets(): array
    {
        $rawTokens = $this->client->getTokens();
        $normalized = [];

        foreach ($rawTokens as $item) {
            if (!is_array($item)) {
                continue;
            }

            $assetId = isset($item['assetId']) ? trim((string) $item['assetId']) : '';
            $chainKey = isset($item['blockchain']) ? strtolower(trim((string) $item['blockchain'])) : '';
            $symbol = isset($item['symbol']) ? strtoupper(trim((string) $item['symbol'])) : '';
            $decimals = isset($item['decimals']) ? (int) $item['decimals'] : null;

            if ($assetId === '' || $chainKey === '' || $symbol === '' || $decimals === null || $decimals < 0) {
                continue;
            }

            $chainMeta = ChainMetadataCatalog::resolveNetworkMetadata($chainKey);
            $contractAddress = isset($item['contractAddress']) && trim((string) $item['contractAddress']) !== ''
                ? trim((string) $item['contractAddress'])
                : null;

            $isNative = ($contractAddress === null && strtoupper((string) $chainMeta['native_symbol']) === $symbol)
                || ($chainKey === 'near' && in_array($symbol, ['NEAR', 'WNEAR'], true));
            $priceUsd = isset($item['price']) && is_numeric($item['price'])
                ? number_format((float) $item['price'], 8, '.', '')
                : null;
            $priceUpdatedAt = isset($item['priceUpdatedAt']) ? (string) $item['priceUpdatedAt'] : null;

            $normalized[] = new NormalizedAssetData(
                providerAssetId: $assetId,
                networkSlug: $chainMeta['slug'],
                networkDisplayName: $chainMeta['display_name'],
                chainFamily: $chainMeta['chain_family'],
                symbol: $symbol,
                displayName: "{$symbol} ({$chainMeta['display_name']})",
                decimals: $decimals,
                isNative: $isNative,
                contractOrIssuer: $contractAddress,
                priceUsd: $priceUsd,
                priceUpdatedAt: $priceUpdatedAt,
                logoUrl: isset($item['icon']) && is_string($item['icon']) ? $item['icon'] : null,
                rawMetadata: $item,
            );
        }

        return $normalized;
    }

    public function requestQuote(QuoteRequestData $request): NormalizedQuoteData
    {
        $ttlMinutes = max(5, (int) config('services.swap.quote_ttl_minutes', 15));
        $deadline = Carbon::now('UTC')->addMinutes($ttlMinutes)->toIso8601ZuluString();

        $originMeta = ChainMetadataCatalog::resolveNetworkMetadata($request->originNetworkSlug);
        $destMeta = ChainMetadataCatalog::resolveNetworkMetadata($request->destinationNetworkSlug);

        [$recipient, $recipientType] = $this->resolvePreviewParty(
            $request->recipientAddress,
            $destMeta,
            'DESTINATION_CHAIN'
        );
        [$refundTo, $refundType] = $this->resolvePreviewParty(
            $request->refundAddress,
            $originMeta,
            'ORIGIN_CHAIN'
        );

        $payload = $this->buildQuotePayload(
            dry: true,
            swapType: $request->swapType,
            slippageBps: $request->slippageBps,
            originAsset: $request->originProviderAssetId,
            destinationAsset: $request->destinationProviderAssetId,
            amountBaseUnits: $request->amountInBaseUnits,
            recipient: $recipient,
            refundTo: $refundTo,
            deadline: $deadline,
            originNetworkSlug: $request->originNetworkSlug,
            recipientType: $recipientType,
            refundType: $refundType,
        );

        $response = $this->postQuoteWithDepositModeFallback($payload);
        $quote = $response['quote'];

        $amountInBase = (string) ($quote['amountIn'] ?? $request->amountInBaseUnits);
        $amountInHuman = isset($quote['amountInFormatted']) && is_numeric($quote['amountInFormatted'])
            ? AmountNormalizer::trimTrailingZeros((string) $quote['amountInFormatted'])
            : AmountNormalizer::toHumanUnits($amountInBase, $request->originDecimals);

        $amountOutBase = (string) ($quote['amountOut'] ?? '0');
        $amountOutHuman = isset($quote['amountOutFormatted']) && is_numeric($quote['amountOutFormatted'])
            ? AmountNormalizer::trimTrailingZeros((string) $quote['amountOutFormatted'])
            : AmountNormalizer::toHumanUnits($amountOutBase, $request->destinationDecimals);

        $minAmountOutBase = (string) ($quote['minAmountOut'] ?? $amountOutBase);
        $minAmountOutHuman = AmountNormalizer::toHumanUnits($minAmountOutBase, $request->destinationDecimals);

        if (bccomp($amountOutHuman, '0', 18) <= 0) {
            throw new RuntimeException('No liquidity available for the requested swap amount.');
        }

        $exchangeRate = AmountNormalizer::calculateExchangeRate($amountInHuman, $amountOutHuman);
        $partnerFeeBps = $this->getConfiguredPartnerFeeBps();
        $protocolFeeBps = empty(config('services.near_intents.api_key')) ? 20 : 0;

        $expiresAt = isset($quote['deadline'])
            ? Carbon::parse((string) $quote['deadline'])
            : Carbon::now('UTC')->addMinutes($ttlMinutes);

        return new NormalizedQuoteData(
            providerSlug: self::SLUG,
            amountInHuman: $amountInHuman,
            amountInBaseUnits: $amountInBase,
            expectedAmountOutHuman: $amountOutHuman,
            expectedAmountOutBaseUnits: $amountOutBase,
            minAmountOutHuman: $minAmountOutHuman,
            minAmountOutBaseUnits: $minAmountOutBase,
            exchangeRate: $exchangeRate,
            slippageBps: $request->slippageBps,
            platformFeeBps: $partnerFeeBps + $protocolFeeBps,
            amountInUsd: isset($quote['amountInUsd']) ? (string) $quote['amountInUsd'] : null,
            amountOutUsd: isset($quote['amountOutUsd']) ? (string) $quote['amountOutUsd'] : null,
            estimatedDurationSeconds: isset($quote['timeEstimate']) ? (int) $quote['timeEstimate'] : 30,
            expiresAt: $expiresAt,
            feeBreakdown: [
                'protocol_fee_bps' => $protocolFeeBps,
                'partner_fee_bps' => $partnerFeeBps,
                'slippage_tolerance_bps' => $request->slippageBps,
            ],
            providerRawPayload: $response,
        );
    }

    public function initiateSwap(SwapInitiationData $request): NormalizedSwapIntentData
    {
        $ttlMinutes = max(5, (int) config('services.swap.quote_ttl_minutes', 15));
        $deadline = Carbon::now('UTC')->addMinutes($ttlMinutes)->toIso8601ZuluString();

        $payload = $this->buildQuotePayload(
            dry: false,
            swapType: $request->swapType,
            slippageBps: $request->slippageBps,
            originAsset: $request->originProviderAssetId,
            destinationAsset: $request->destinationProviderAssetId,
            amountBaseUnits: $request->amountInBaseUnits,
            recipient: $request->recipientAddress,
            refundTo: $request->refundAddress,
            deadline: $deadline,
            originNetworkSlug: $request->originNetworkSlug,
        );

        $response = $this->postQuoteWithDepositModeFallback($payload);
        $quote = $response['quote'];

        $depositAddress = isset($quote['depositAddress']) ? trim((string) $quote['depositAddress']) : '';
        if ($depositAddress === '') {
            throw new RuntimeException('Execution provider did not return a valid deposit address.');
        }

        $depositMemo = isset($quote['depositMemo']) && trim((string) $quote['depositMemo']) !== ''
            ? trim((string) $quote['depositMemo'])
            : null;

        $amountInBase = (string) ($quote['amountIn'] ?? $request->amountInBaseUnits);
        $amountInHuman = isset($quote['amountInFormatted']) && is_numeric($quote['amountInFormatted'])
            ? AmountNormalizer::trimTrailingZeros((string) $quote['amountInFormatted'])
            : AmountNormalizer::toHumanUnits($amountInBase, $request->originDecimals);

        $amountOutBase = (string) ($quote['amountOut'] ?? '0');
        $amountOutHuman = isset($quote['amountOutFormatted']) && is_numeric($quote['amountOutFormatted'])
            ? AmountNormalizer::trimTrailingZeros((string) $quote['amountOutFormatted'])
            : AmountNormalizer::toHumanUnits($amountOutBase, $request->destinationDecimals);

        $minAmountOutBase = (string) ($quote['minAmountOut'] ?? $amountOutBase);
        $minAmountOutHuman = AmountNormalizer::toHumanUnits($minAmountOutBase, $request->destinationDecimals);

        $expiresAt = isset($quote['deadline'])
            ? Carbon::parse((string) $quote['deadline'])
            : Carbon::now('UTC')->addMinutes($ttlMinutes);

        return new NormalizedSwapIntentData(
            depositAddress: $depositAddress,
            depositMemo: $depositMemo,
            amountInHuman: $amountInHuman,
            amountInBaseUnits: $amountInBase,
            expectedAmountOutHuman: $amountOutHuman,
            minAmountOutHuman: $minAmountOutHuman,
            expiresAt: $expiresAt,
            estimatedDurationSeconds: isset($quote['timeEstimate']) ? (int) $quote['timeEstimate'] : 30,
            providerContext: [
                'signature' => $response['signature'] ?? null,
                'timestamp' => $response['timestamp'] ?? null,
                'quote' => $quote,
            ],
        );
    }

    public function submitDepositTransaction(SwapOrder $order, string $txHash): bool
    {
        $payload = [
            'txHash' => trim($txHash),
            'depositAddress' => $order->deposit_address,
        ];

        if ($order->deposit_memo !== null && trim($order->deposit_memo) !== '') {
            $payload['memo'] = trim($order->deposit_memo);
        }

        try {
            $this->client->postDepositSubmit($payload);
            return true;
        } catch (\Throwable $e) {
            Log::warning('NEAR Intents deposit tx submission returned non-200 response', [
                'swap_uuid' => $order->swap_uuid,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function fetchSwapStatus(SwapOrder $order): NormalizedSwapStatusData
    {
        $response = $this->client->getStatus($order->deposit_address, $order->deposit_memo);

        $rawStatus = strtoupper(trim((string) ($response['status'] ?? 'PENDING_DEPOSIT')));
        $swapDetails = isset($response['swapDetails']) && is_array($response['swapDetails'])
            ? $response['swapDetails']
            : [];

        $originHashes = $swapDetails['originChainTxHashes'] ?? [];
        $destHashes = $swapDetails['destinationChainTxHashes'] ?? [];

        [$sourceTxHash, $sourceExplorerUrl] = $this->extractFirstTxHashAndUrl($originHashes);
        [$destTxHash, $destExplorerUrl] = $this->extractFirstTxHashAndUrl($destHashes);

        $actualAmountOut = null;
        if (isset($swapDetails['amountOutFormatted']) && is_numeric($swapDetails['amountOutFormatted'])) {
            $actualAmountOut = AmountNormalizer::trimTrailingZeros((string) $swapDetails['amountOutFormatted']);
        } elseif (isset($swapDetails['amountOut']) && is_numeric($swapDetails['amountOut'])) {
            $decimals = $order->destinationAsset?->decimals ?? 18;
            $actualAmountOut = AmountNormalizer::toHumanUnits((string) $swapDetails['amountOut'], $decimals);
        }

        $refundedAmount = null;
        if (isset($swapDetails['refundedAmountFormatted']) && is_numeric($swapDetails['refundedAmountFormatted'])) {
            $refundedAmount = AmountNormalizer::trimTrailingZeros((string) $swapDetails['refundedAmountFormatted']);
        } elseif (isset($swapDetails['refundedAmount']) && is_numeric($swapDetails['refundedAmount']) && (string) $swapDetails['refundedAmount'] !== '0') {
            $decimals = $order->sourceAsset?->decimals ?? 18;
            $refundedAmount = AmountNormalizer::toHumanUnits((string) $swapDetails['refundedAmount'], $decimals);
        }

        $normalizedStatus = $this->mapProviderStatus($rawStatus, $order);

        $failureReason = null;
        if ($normalizedStatus === SwapStatus::FAILED) {
            $failureReason = $rawStatus === 'INCOMPLETE_DEPOSIT'
                ? 'Deposited amount was below the required minimum amount for this quote.'
                : (string) ($swapDetails['reason'] ?? $response['message'] ?? 'Swap execution failed on provider.');
        } elseif ($normalizedStatus === SwapStatus::REFUNDED) {
            $failureReason = (string) ($swapDetails['reason'] ?? 'Swap could not be fulfilled within slippage or deadline constraints and was refunded to the origin address.');
        }

        return new NormalizedSwapStatusData(
            normalizedStatus: $normalizedStatus,
            providerStatus: $rawStatus,
            sourceTxHash: $sourceTxHash,
            sourceExplorerUrl: $sourceExplorerUrl,
            destinationTxHash: $destTxHash,
            destinationExplorerUrl: $destExplorerUrl,
            refundTxHash: $normalizedStatus === SwapStatus::REFUNDED ? ($destTxHash ?: $sourceTxHash) : null,
            actualAmountOutHuman: $actualAmountOut,
            refundedAmountHuman: $refundedAmount,
            failureReason: $failureReason,
            rawPayload: $response,
        );
    }

    /**
     * When the user has not filled out the recipient or refund address yet, use an
     * INTENTS preview identifier so the provider calculates the live quote without
     * running on-chain trustline or address checks against a placeholder wallet.
     * Once the user enters a valid address for the selected chain, use DESTINATION_CHAIN /
     * ORIGIN_CHAIN so the provider checks their actual wallet (including Stellar trustlines).
     *
     * @param array<string, mixed> $networkMeta
     * @return array{0: string, 1: string}
     */
    private function resolvePreviewParty(?string $candidate, array $networkMeta, string $chainPartyType): array
    {
        $intentsPreviewAccount = 'deadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeef';
        if ($candidate === null || trim($candidate) === '') {
            return [$intentsPreviewAccount, 'INTENTS'];
        }

        $trimmed = trim($candidate);
        $regex = $networkMeta['address_regex'] ?? null;

        if (is_string($regex) && $regex !== '' && @preg_match($regex, $trimmed) !== 1) {
            return [$intentsPreviewAccount, 'INTENTS'];
        }

        return [$trimmed, $chainPartyType];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildQuotePayload(
        bool $dry,
        string $swapType,
        int $slippageBps,
        string $originAsset,
        string $destinationAsset,
        string $amountBaseUnits,
        string $recipient,
        string $refundTo,
        string $deadline,
        string $originNetworkSlug = '',
        string $recipientType = 'DESTINATION_CHAIN',
        string $refundType = 'ORIGIN_CHAIN',
    ): array {
        $depositMode = strtolower(trim($originNetworkSlug)) === 'stellar' ? 'MEMO' : 'SIMPLE';

        $payload = [
            'dry' => $dry,
            'depositMode' => $depositMode,
            'swapType' => $swapType,
            'slippageTolerance' => $slippageBps,
            'originAsset' => $originAsset,
            'depositType' => 'ORIGIN_CHAIN',
            'destinationAsset' => $destinationAsset,
            'amount' => $amountBaseUnits,
            'refundTo' => $refundTo,
            'refundType' => $refundType,
            'recipient' => $recipient,
            'recipientType' => $recipientType,
            'deadline' => $deadline,
            'referral' => (string) config('services.near_intents.referral', 'zihubridge'),
        ];

        $feeBps = $this->getConfiguredPartnerFeeBps();
        $feeRecipient = $this->getEffectiveFeeRecipient();

        if ($feeBps > 0 && is_string($feeRecipient) && trim($feeRecipient) !== '') {
            $payload['appFees'] = [
                [
                    'recipient' => trim($feeRecipient),
                    'fee' => $feeBps,
                ],
            ];
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function postQuoteWithDepositModeFallback(array $payload): array
    {
        try {
            return $this->client->postQuote($payload);
        } catch (RuntimeException $e) {
            if (stripos($e->getMessage(), 'depositMode') !== false) {
                $payload['depositMode'] = ($payload['depositMode'] ?? 'SIMPLE') === 'SIMPLE' ? 'MEMO' : 'SIMPLE';
                return $this->client->postQuote($payload);
            }

            throw $e;
        }
    }

    private function getConfiguredPartnerFeeBps(): int
    {
        try {
            $dbConfig = \App\Models\PlatformFeeConfiguration::getActive();
            if ($dbConfig) {
                return $dbConfig->is_enabled ? (int) $dbConfig->fee_bps : 0;
            }
        } catch (\Throwable) {
            // Fall back to config if table not reachable
        }

        $feeBps = (int) config('services.near_intents.partner_fee_bps', 0);
        $feeRecipient = config('services.near_intents.partner_fee_recipient');

        if ($feeBps > 0 && is_string($feeRecipient) && trim($feeRecipient) !== '') {
            return $feeBps;
        }

        return 0;
    }

    private function getEffectiveFeeRecipient(): ?string
    {
        try {
            $dbConfig = \App\Models\PlatformFeeConfiguration::getActive();
            if ($dbConfig && $dbConfig->is_enabled && !empty($dbConfig->fee_recipient)) {
                return trim($dbConfig->fee_recipient);
            }
        } catch (\Throwable) {
            // Fall back
        }

        $envRecipient = config('services.near_intents.partner_fee_recipient');
        return is_string($envRecipient) && trim($envRecipient) !== '' ? trim($envRecipient) : null;
    }

    private function mapProviderStatus(string $rawStatus, SwapOrder $order): SwapStatus
    {
        return match ($rawStatus) {
            'SUCCESS' => SwapStatus::COMPLETED,
            'REFUNDED' => SwapStatus::REFUNDED,
            'PROCESSING' => SwapStatus::PROCESSING,
            'KNOWN_DEPOSIT_TX' => SwapStatus::DEPOSIT_DETECTED,
            'INCOMPLETE_DEPOSIT', 'FAILED' => SwapStatus::FAILED,
            'PENDING_DEPOSIT' => $this->resolvePendingDepositState($order),
            default => $order->statusEnum(),
        };
    }

    private function resolvePendingDepositState(SwapOrder $order): SwapStatus
    {
        if ($order->source_tx_hash) {
            return SwapStatus::DEPOSIT_SUBMITTED;
        }

        if ($order->deposit_expires_at && $order->deposit_expires_at->isPast()) {
            return SwapStatus::EXPIRED;
        }

        return SwapStatus::DEPOSIT_PENDING;
    }

    /**
     * @param mixed $hashes
     * @return array{0: ?string, 1: ?string}
     */
    private function extractFirstTxHashAndUrl(mixed $hashes): array
    {
        if (!is_array($hashes) || empty($hashes)) {
            return [null, null];
        }

        $first = reset($hashes);
        if (is_string($first) && trim($first) !== '') {
            return [trim($first), null];
        }

        if (is_array($first)) {
            $hash = isset($first['hash']) && is_string($first['hash']) ? trim($first['hash']) : null;
            $url = isset($first['explorerUrl']) && is_string($first['explorerUrl']) ? trim($first['explorerUrl']) : null;
            return [$hash ?: null, $url ?: null];
        }

        return [null, null];
    }
}

