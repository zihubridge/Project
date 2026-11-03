<?php

namespace App\Services\Providers\DTOs;

use Carbon\CarbonInterface;

readonly class NormalizedSwapIntentData
{
    public function __construct(
        public string $depositAddress,
        public ?string $depositMemo,
        public string $amountInHuman,
        public string $amountInBaseUnits,
        public string $expectedAmountOutHuman,
        public string $minAmountOutHuman,
        public CarbonInterface $expiresAt,
        public ?int $estimatedDurationSeconds = null,
        public array $providerContext = [],
    ) {}
}

