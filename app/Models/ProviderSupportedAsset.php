<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderSupportedAsset extends Model
{
    protected $fillable = [
        'swap_provider_id',
        'crypto_asset_id',
        'provider_asset_id',
        'provider_chain_key',
        'is_active',
        'provider_metadata_json',
        'last_seen_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'provider_metadata_json' => 'array',
        'last_seen_at' => 'datetime',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(SwapProvider::class, 'swap_provider_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(CryptoAsset::class, 'crypto_asset_id');
    }
}

