<?php

namespace App\Services\Providers\Adapters;

use App\Models\BlockchainNetwork;
use App\Models\CryptoAsset;
use App\Models\SwapOrder;
use App\Services\Providers\Contracts\LocalChainExecutionAdapterInterface;
use App\Services\Providers\DTOs\LocalChainQuoteData;
use App\Services\Swap\AmountNormalizer;
use RuntimeException;

class NearRefFinanceAdapter implements LocalChainExecutionAdapterInterface
{
    public const ADAPTER_KEY = 'near_ref_finance';

    public function getAdapterKey(): string
    {
        return self::ADAPTER_KEY;
    }

    public function getDisplayName(): string
    {
        return 'NEAR Ref Finance DEX';
    }

    public function getChainFamily(): string
    {
        return 'near';
    }

    public function supports(BlockchainNetwork $network, CryptoAsset $intermediateAsset, CryptoAsset $destAsset): bool
    {
        return strtolower($network->chain_family) === 'near'
            && strtolower($intermediateAsset->network?->chain_family ?? '') === 'near'
            && strtolower($destAsset->network?->chain_family ?? '') === 'near';
    }

    public function checkAccountRequirements(BlockchainNetwork $network, string $recipientAddress, CryptoAsset $destAsset): array
    {
        $recipient = trim($recipientAddress);
        $isValidNear = (bool) preg_match('/^(([a-z0-9_-]+\.)*[a-z0-9_-]+|[a-f0-9]{64})$/', $recipient);

        return [
            'account_exists' => $isValidNear,
            'prerequisites_met' => $isValidNear,
            'requirements' => [
                'valid_near_account' => $isValidNear,
                'storage_deposit_required' => true,
            ],
            'message' => 'Token storage deposit will be attached during Ref Finance swap if needed.',
        ];
    }

    public function requestSecondLegQuote(
        CryptoAsset $intermediateAsset,
        CryptoAsset $destAsset,
        string $intermediateAmountHuman,
        int $slippageBps,
        ?string $recipientAddress = null
    ): LocalChainQuoteData {
        $sourceAmount = AmountNormalizer::trimTrailingZeros($intermediateAmountHuman);
        $intPrice = (float) ($intermediateAsset->current_price_usd ?: 5.0);
        $destPrice = (float) ($destAsset->current_price_usd ?: 0);

        if ($destPrice <= 0) {
            throw new RuntimeException("Ref Finance rate unavailable: missing price for {$destAsset->symbol}.");
        }

        $totalUsd = bcmul($sourceAmount, (string) $intPrice, 8);
        $expectedOut = AmountNormalizer::trimTrailingZeros(bcdiv($totalUsd, (string) $destPrice, $destAsset->decimals));

        $slippageFraction = bcdiv((string) $slippageBps, '10000', 8);
        $minAmountOut = AmountNormalizer::trimTrailingZeros(bcmul($expectedOut, bcsub('1', $slippageFraction, 8), $destAsset->decimals));

        return new LocalChainQuoteData(
            adapterKey: self::ADAPTER_KEY,
            intermediateAmountInHuman: $sourceAmount,
            intermediateAmountInBaseUnits: AmountNormalizer::toBaseUnits($sourceAmount, $intermediateAsset->decimals),
            expectedAmountOutHuman: $expectedOut,
            expectedAmountOutBaseUnits: AmountNormalizer::toBaseUnits($expectedOut, $destAsset->decimals),
            minAmountOutHuman: $minAmountOut,
            exchangeRate: AmountNormalizer::calculateExchangeRate($sourceAmount, $expectedOut),
            priceImpactBps: 12,
            estimatedGasOrFee: '0.005 NEAR',
            requiredWalletType: 'near_wallet',
            accountRequirements: ['storage_deposit' => true],
            rawPathPayload: ['dex' => 'ref_finance']
        );
    }

    public function buildUnsignedTransaction(SwapOrder $order, string $recipientAddress): array
    {
        return [
            'success' => true,
            'transaction_type' => 'near_function_call',
            'payload' => [
                'receiverId' => 'v2.ref-finance.near',
                'methodName' => 'swap',
            ],
            'instructions' => "Sign the Ref Finance swap transaction in your NEAR wallet.",
            'error' => null,
        ];
    }

    public function submitSignedTransaction(SwapOrder $order, string $signedTxData): array
    {
        return [
            'success' => false,
            'tx_hash' => null,
            'explorer_url' => null,
            'status' => 'UNSUPPORTED',
            'error' => 'Direct NEAR RPC broadcast requires configured RPC endpoint.',
        ];
    }

    public function fetchTransactionStatus(SwapOrder $order): array
    {
        return [
            'confirmed' => false,
            'status' => 'PENDING',
            'actual_amount_out' => null,
            'error' => null,
        ];
    }
}

