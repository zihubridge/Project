<?php

namespace App\Services\Providers\DTOs;

class LocalChainQuoteData
{
    public function __construct(
        public readonly string $adapterKey,
        public readonly string $intermediateAmountInHuman,
        public readonly string $intermediateAmountInBaseUnits,
        public readonly string $expectedAmountOutHuman,
        public readonly string $expectedAmountOutBaseUnits,
        public readonly string $minAmountOutHuman,
        public readonly string $exchangeRate,
        public readonly int $priceImpactBps = 0,
        public readonly ?string $estimatedGasOrFee = null,
        public readonly string $requiredWalletType = 'native',
        public readonly array $accountRequirements = [],
        public readonly array $rawPathPayload = [],
    ) {}
}

