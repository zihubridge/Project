<?php

namespace App\Services\Providers\Contracts;

use App\Models\SwapOrder;
use App\Services\Providers\DTOs\NormalizedAssetData;
use App\Services\Providers\DTOs\NormalizedQuoteData;
use App\Services\Providers\DTOs\NormalizedSwapIntentData;
use App\Services\Providers\DTOs\NormalizedSwapStatusData;
use App\Services\Providers\DTOs\QuoteRequestData;
use App\Services\Providers\DTOs\SwapInitiationData;

interface SwapExecutionProviderInterface
{
    public function getProviderSlug(): string;

    public function getDisplayName(): string;

    /**
     * Retrieve the dynamic list of tokens/assets supported by this provider.
     *
     * @return array<int, NormalizedAssetData>
     */
    public function fetchSupportedAssets(): array;

    /**
     * Request a live quote preview without committing a deposit address.
     */
    public function requestQuote(QuoteRequestData $request): NormalizedQuoteData;

    /**
     * Initiate a live swap commitment and allocate deposit instructions.
     */
    public function initiateSwap(SwapInitiationData $request): NormalizedSwapIntentData;

    /**
     * Submit the user's origin-chain deposit transaction hash to accelerate indexing.
     */
    public function submitDepositTransaction(SwapOrder $order, string $txHash): bool;

    /**
     * Poll the provider for the latest execution status of a swap order.
     */
    public function fetchSwapStatus(SwapOrder $order): NormalizedSwapStatusData;
}

