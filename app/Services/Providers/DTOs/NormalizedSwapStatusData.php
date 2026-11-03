<?php

namespace App\Services\Providers\DTOs;

use App\Enums\SwapStatus;

readonly class NormalizedSwapStatusData
{
    public function __construct(
        public SwapStatus $normalizedStatus,
        public string $providerStatus,
        public ?string $sourceTxHash = null,
        public ?string $sourceExplorerUrl = null,
        public ?string $destinationTxHash = null,
        public ?string $destinationExplorerUrl = null,
        public ?string $refundTxHash = null,
        public ?string $actualAmountOutHuman = null,
        public ?string $refundedAmountHuman = null,
        public ?string $failureReason = null,
        public array $rawPayload = [],
    ) {}
}

