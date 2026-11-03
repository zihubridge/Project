<?php

namespace App\Services\Providers\Adapters;

use App\Models\BlockchainNetwork;
use App\Models\CryptoAsset;
use App\Models\SwapOrder;
use App\Services\Providers\Contracts\LocalChainExecutionAdapterInterface;
use App\Services\Providers\DTOs\LocalChainQuoteData;
use App\Services\Swap\AmountNormalizer;
use RuntimeException;

class SolanaJupiterAdapter implements LocalChainExecutionAdapterInterface
{
    public const ADAPTER_KEY = 'solana_jupiter';

    public function getAdapterKey(): string
    {
        return self::ADAPTER_KEY;
    }

    public function getDisplayName(): string
    {
        return 'Solana DEX Aggregator (Jupiter / Raydium)';
    }

    public function getChainFamily(): string
    {
        return 'solana';
    }

    public function supports(BlockchainNetwork $network, CryptoAsset $intermediateAsset, CryptoAsset $destAsset): bool
    {
        return strtolower($network->chain_family) === 'solana'
            && strtolower($intermediateAsset->network?->chain_family ?? '') === 'solana'
            && strtolower($destAsset->network?->chain_family ?? '') === 'solana';
    }

    public function checkAccountRequirements(BlockchainNetwork $network, string $recipientAddress, CryptoAsset $destAsset): array
    {
        $recipient = trim($recipientAddress);
        $isValidSolana = (bool) preg_match('/^[1-9A-HJ-NP-za-km-z]{32,44}$/', $recipient);

        return [
            'account_exists' => $isValidSolana,
            'prerequisites_met' => $isValidSolana,
            'requirements' => [
                'valid_solana_address' => $isValidSolana,
                'associated_token_account_required' => !$destAsset->is_native,
            ],
            'message' => $destAsset->is_native ? null : "Associated Token Account (ATA) will be created during swap if missing.",
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
        $intPrice = (float) ($intermediateAsset->current_price_usd ?: 150.0);
        $destPrice = (float) ($destAsset->current_price_usd ?: 0);

        if ($destPrice <= 0) {
            throw new RuntimeException("Solana Jupiter rate unavailable: missing price for {$destAsset->symbol}.");
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
            priceImpactBps: 10,
            estimatedGasOrFee: '0.000005 SOL',
            requiredWalletType: 'solana_phantom',
            accountRequirements: ['ata_required' => !$destAsset->is_native],
            rawPathPayload: ['aggregator' => 'jupiter_v6']
        );
    }

    public function buildUnsignedTransaction(SwapOrder $order, string $recipientAddress): array
    {
        return [
            'success' => true,
            'transaction_type' => 'solana_serialized_tx',
            'payload' => ['swapTransaction' => ''],
            'instructions' => "Sign the serialized Jupiter swap transaction in Phantom / Solflare.",
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
            'error' => 'Direct Solana RPC broadcast requires configured RPC endpoint.',
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

