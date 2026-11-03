<?php

namespace App\Services\Providers\DTOs;

readonly class SwapInitiationData
{
    public function __construct(
        public string $originProviderAssetId,
        public string $destinationProviderAssetId,
        public string $originNetworkSlug,
        public string $destinationNetworkSlug,
        public string $amountInBaseUnits,
        public int $originDecimals,
        public int $destinationDecimals,
        public int $slippageBps,
        public string $recipientAddress,
        public ?string $recipientMemo,
        public string $refundAddress,
        public ?string $refundMemo,
        public string $swapType = 'EXACT_INPUT',
    ) {}
}

