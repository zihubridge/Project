<?php

namespace App\Services\Providers\DTOs;

use Carbon\CarbonInterface;

readonly class NormalizedQuoteData
{
    public function __construct(
        public string $providerSlug,
        public string $amountInHuman,
        public string $amountInBaseUnits,
        public string $expectedAmountOutHuman,
        public string $expectedAmountOutBaseUnits,
        public string $minAmountOutHuman,
        public string $minAmountOutBaseUnits,
        public string $exchangeRate,
        public int $slippageBps,
        public int $platformFeeBps,
        public ?string $amountInUsd,
        public ?string $amountOutUsd,
        public ?int $estimatedDurationSeconds,
        public CarbonInterface $expiresAt,
        public array $feeBreakdown = [],
        public array $providerRawPayload = [],
    ) {}
}

