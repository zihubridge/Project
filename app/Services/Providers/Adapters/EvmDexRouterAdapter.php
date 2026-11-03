<?php

namespace App\Services\Providers\Adapters;

use App\Models\BlockchainNetwork;
use App\Models\CryptoAsset;
use App\Models\SwapOrder;
use App\Services\Providers\Contracts\LocalChainExecutionAdapterInterface;
use App\Services\Providers\DTOs\LocalChainQuoteData;
use App\Services\Swap\AmountNormalizer;
use RuntimeException;

class EvmDexRouterAdapter implements LocalChainExecutionAdapterInterface
{
    public const ADAPTER_KEY = 'evm_dex';

    public function getAdapterKey(): string
    {
        return self::ADAPTER_KEY;
    }

    public function getDisplayName(): string
    {
        return 'EVM DEX Router (Ethereum / Base)';
    }

    public function getChainFamily(): string
    {
        return 'evm';
    }

    public function supports(BlockchainNetwork $network, CryptoAsset $intermediateAsset, CryptoAsset $destAsset): bool
    {
        return strtolower($network->chain_family) === 'evm'
            && strtolower($intermediateAsset->network?->chain_family ?? '') === 'evm'
            && strtolower($destAsset->network?->chain_family ?? '') === 'evm';
    }

    public function checkAccountRequirements(BlockchainNetwork $network, string $recipientAddress, CryptoAsset $destAsset): array
    {
        $recipient = trim($recipientAddress);
        $isValidEvm = (bool) preg_match('/^0x[a-fA-F0-9]{40}$/', $recipient);

        return [
            'account_exists' => $isValidEvm,
            'prerequisites_met' => $isValidEvm,
            'requirements' => [
                'valid_evm_address' => $isValidEvm,
                'erc20_approval_required' => !$destAsset->is_native,
            ],
            'message' => null,
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
        $intPrice = (float) ($intermediateAsset->current_price_usd ?: 1.0);
        $destPrice = (float) ($destAsset->current_price_usd ?: 0);

        if ($destPrice <= 0) {
            throw new RuntimeException("EVM DEX rate unavailable: missing price for {$destAsset->symbol}.");
        }

        $totalUsd = bcmul($sourceAmount, (string) $intPrice, 8);
        $expectedOut = AmountNormalizer::trimTrailingZeros(bcdiv($totalUsd, (string) $destPrice, 8));

        $slippageFraction = bcdiv((string) $slippageBps, '10000', 8);
        $minAmountOut = AmountNormalizer::trimTrailingZeros(bcmul($expectedOut, bcsub('1', $slippageFraction, 8), 8));

        return new LocalChainQuoteData(
            adapterKey: self::ADAPTER_KEY,
            intermediateAmountInHuman: $sourceAmount,
            intermediateAmountInBaseUnits: AmountNormalizer::toBaseUnits($sourceAmount, $intermediateAsset->decimals),
            expectedAmountOutHuman: $expectedOut,
            expectedAmountOutBaseUnits: AmountNormalizer::toBaseUnits($expectedOut, $destAsset->decimals),
            minAmountOutHuman: $minAmountOut,
            exchangeRate: AmountNormalizer::calculateExchangeRate($sourceAmount, $expectedOut),
            priceImpactBps: 15,
            estimatedGasOrFee: '0.001 ETH',
            requiredWalletType: 'metamask_evm',
            accountRequirements: ['erc20_approval' => true],
            rawPathPayload: ['router' => 'uniswap_v3']
        );
    }

    public function buildUnsignedTransaction(SwapOrder $order, string $recipientAddress): array
    {
        return [
            'success' => true,
            'transaction_type' => 'evm_swap_call',
            'payload' => [
                'to' => '0x0000000000000000000000000000000000000000',
                'data' => '0x',
                'value' => '0',
            ],
            'instructions' => "Sign the DEX Router swap transaction in MetaMask to execute second leg.",
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
            'error' => 'Direct EVM RPC broadcast requires configured RPC endpoint.',
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

