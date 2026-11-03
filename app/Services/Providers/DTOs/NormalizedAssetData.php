<?php

namespace App\Services\Providers\DTOs;

readonly class NormalizedAssetData
{
    public function __construct(
        public string $providerAssetId,
        public string $networkSlug,
        public string $networkDisplayName,
        public string $chainFamily,
        public string $symbol,
        public string $displayName,
        public int $decimals,
        public bool $isNative,
        public ?string $contractOrIssuer = null,
        public ?string $priceUsd = null,
        public ?string $priceUpdatedAt = null,
        public ?string $logoUrl = null,
        public array $rawMetadata = [],
    ) {}
}

