<?php

namespace App\Services\Providers\Contracts;

use App\Models\BlockchainNetwork;
use App\Models\CryptoAsset;
use App\Models\SwapOrder;
use App\Services\Providers\DTOs\LocalChainQuoteData;

interface LocalChainExecutionAdapterInterface
{
    /**
     * Unique machine identifier for this adapter (e.g. 'stellar_sdex', 'xrpl_dex', 'evm_dex').
     */
    public function getAdapterKey(): string;

    /**
     * Human-readable name.
     */
    public function getDisplayName(): string;

    /**
     * Blockchain family (e.g. 'stellar', 'ripple', 'evm', 'solana', 'near').
     */
    public function getChainFamily(): string;

    /**
     * Check if this adapter can route between the intermediate asset and destination asset on this network.
     */
    public function supports(BlockchainNetwork $network, CryptoAsset $intermediateAsset, CryptoAsset $destAsset): bool;

    /**
     * Inspect on-chain state for the recipient wallet (e.g. trustline, account activation, token accounts).
     *
     * @return array{
     *     account_exists: bool,
     *     prerequisites_met: bool,
     *     requirements: array<string, mixed>,
     *     message: ?string
     * }
     */
    public function checkAccountRequirements(BlockchainNetwork $network, string $recipientAddress, CryptoAsset $destAsset): array;

    /**
     * Request a second-leg DEX quote for swapping the intermediate asset into the destination asset.
     */
    public function requestSecondLegQuote(
        CryptoAsset $intermediateAsset,
        CryptoAsset $destAsset,
        string $intermediateAmountHuman,
        int $slippageBps,
        ?string $recipientAddress = null
    ): LocalChainQuoteData;

    /**
     * Build an unsigned transaction envelope or payload for user wallet signature.
     *
     * @return array{
     *     success: bool,
     *     transaction_type: string,
     *     payload: mixed,
     *     instructions: string,
     *     error: ?string
     * }
     */
    public function buildUnsignedTransaction(SwapOrder $order, string $recipientAddress): array;

    /**
     * Submit a user-signed transaction to the destination blockchain network.
     *
     * @return array{
     *     success: bool,
     *     tx_hash: ?string,
     *     explorer_url: ?string,
     *     status: string,
     *     error: ?string
     * }
     */
    public function submitSignedTransaction(SwapOrder $order, string $signedTxData): array;

    /**
     * Fetch the on-chain confirmation status of a submitted second-leg transaction.
     *
     * @return array{
     *     confirmed: bool,
     *     status: string,
     *     actual_amount_out: ?string,
     *     error: ?string
     * }
     */
    public function fetchTransactionStatus(SwapOrder $order): array;
}

