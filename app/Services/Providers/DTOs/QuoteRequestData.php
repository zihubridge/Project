<?php

namespace App\Services\Providers\DTOs;

readonly class QuoteRequestData
{
    public function __construct(
        public string $originProviderAssetId,
        public string $destinationProviderAssetId,
        public string $originNetworkSlug,
        public string $destinationNetworkSlug,
        public string $amountInHuman,
        public string $amountInBaseUnits,
        public int $originDecimals,
        public int $destinationDecimals,
        public int $slippageBps = 100,
        public string $swapType = 'EXACT_INPUT',
        public ?string $recipientAddress = null,
        public ?string $refundAddress = null,
    ) {}
}

