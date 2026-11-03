<?php

namespace App\Services\Providers\DTOs;

class CompositeMultiHopQuoteResult
{
    public function __construct(
        public readonly NormalizedQuoteData $leg1Quote,
        public readonly LocalChainQuoteData $leg2Quote,
        public readonly \App\Models\CryptoAsset $intermediateAsset,
        public readonly string $intermediateAmountHuman,
        public readonly string $intermediateAmountBaseUnits,
        public readonly string $finalExpectedAmountOutHuman,
        public readonly string $finalExpectedAmountOutBaseUnits,
        public readonly string $finalMinAmountOutHuman,
        public readonly string $finalMinAmountOutBaseUnits,
        public readonly string $overallExchangeRate,
        public readonly int $platformFeeBps,
        public readonly int $totalDurationSeconds,
        public readonly string $secondLegAdapterKey,
        public readonly array $routingMetadata,
    ) {}
}

