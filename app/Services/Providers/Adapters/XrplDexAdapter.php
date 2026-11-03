<?php

namespace App\Services\Providers\Adapters;

use App\Models\BlockchainNetwork;
use App\Models\CryptoAsset;
use App\Models\SwapOrder;
use App\Services\Providers\Contracts\LocalChainExecutionAdapterInterface;
use App\Services\Providers\DTOs\LocalChainQuoteData;
use App\Services\Swap\AmountNormalizer;
use RuntimeException;

class XrplDexAdapter implements LocalChainExecutionAdapterInterface
{
    public const ADAPTER_KEY = 'xrpl_dex';

    public function getAdapterKey(): string
    {
        return self::ADAPTER_KEY;
    }

    public function getDisplayName(): string
    {
        return 'XRP Ledger DEX & AMM';
    }

    public function getChainFamily(): string
    {
        return 'ripple';
    }

    public function supports(BlockchainNetwork $network, CryptoAsset $intermediateAsset, CryptoAsset $destAsset): bool
    {
        return in_array(strtolower($network->chain_family), ['ripple', 'xrpl'], true)
            && in_array(strtolower($intermediateAsset->network?->chain_family ?? ''), ['ripple', 'xrpl'], true)
            && in_array(strtolower($destAsset->network?->chain_family ?? ''), ['ripple', 'xrpl'], true);
    }

    public function checkAccountRequirements(BlockchainNetwork $network, string $recipientAddress, CryptoAsset $destAsset): array
    {
        $recipient = trim($recipientAddress);
        $isValidXrpl = (bool) preg_match('/^r[1-9A-HJ-NP-za-km-z]{25,34}$/', $recipient);

        return [
            'account_exists' => $isValidXrpl,
            'prerequisites_met' => $isValidXrpl,
            'requirements' => [
                'valid_xrpl_address' => $isValidXrpl,
                'trustline_required' => !$destAsset->is_native,
            ],
            'message' => $destAsset->is_native ? null : "Requires an established trustline for {$destAsset->symbol} on XRPL.",
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
        $intPrice = (float) ($intermediateAsset->current_price_usd ?: 0.50);
        $destPrice = (float) ($destAsset->current_price_usd ?: 0);

        if ($destPrice <= 0) {
            throw new RuntimeException("XRPL DEX rate unavailable: missing price for {$destAsset->symbol}.");
        }

        $totalUsd = bcmul($sourceAmount, (string) $intPrice, 8);
        $expectedOut = AmountNormalizer::trimTrailingZeros(bcdiv($totalUsd, (string) $destPrice, 6));

        $slippageFraction = bcdiv((string) $slippageBps, '10000', 8);
        $minAmountOut = AmountNormalizer::trimTrailingZeros(bcmul($expectedOut, bcsub('1', $slippageFraction, 8), 6));

        return new LocalChainQuoteData(
            adapterKey: self::ADAPTER_KEY,
            intermediateAmountInHuman: $sourceAmount,
            intermediateAmountInBaseUnits: AmountNormalizer::toBaseUnits($sourceAmount, 6),
            expectedAmountOutHuman: $expectedOut,
            expectedAmountOutBaseUnits: AmountNormalizer::toBaseUnits($expectedOut, $destAsset->decimals),
            minAmountOutHuman: $minAmountOut,
            exchangeRate: AmountNormalizer::calculateExchangeRate($sourceAmount, $expectedOut),
            priceImpactBps: 20,
            estimatedGasOrFee: '0.000012 XRP',
            requiredWalletType: 'xrpl_xumm',
            accountRequirements: ['trustline_required' => !$destAsset->is_native],
            rawPathPayload: ['dex' => 'xrpl_orderbook']
        );
    }

    public function buildUnsignedTransaction(SwapOrder $order, string $recipientAddress): array
    {
        return [
            'success' => true,
            'transaction_type' => 'xrpl_payment',
            'payload' => [
                'TransactionType' => 'Payment',
                'Account' => trim($recipientAddress),
                'Destination' => trim($recipientAddress),
                'Amount' => $order->min_amount_out,
                'SendMax' => $order->intermediate_amount,
            ],
            'instructions' => "Sign the XRPL Path Payment in Xaman / XUMM to execute second leg.",
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
            'error' => 'Direct XRPL node submission requires configured rippled websocket endpoint.',
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

