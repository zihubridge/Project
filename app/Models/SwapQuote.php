<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SwapQuote extends Model
{
    protected $fillable = [
        'quote_uuid',
        'swap_provider_id',
        'source_asset_id',
        'destination_asset_id',
        'swap_type',
        'amount_in',
        'amount_in_base_units',
        'expected_amount_out',
        'expected_amount_out_base_units',
        'min_amount_out',
        'min_amount_out_base_units',
        'exchange_rate',
        'amount_in_usd',
        'amount_out_usd',
        'slippage_bps',
        'platform_fee_bps',
        'estimated_duration_seconds',
        'expires_at',
        'fee_breakdown_json',
        'provider_quote_payload_json',
    ];

    protected $casts = [
        'slippage_bps' => 'integer',
        'platform_fee_bps' => 'integer',
        'estimated_duration_seconds' => 'integer',
        'expires_at' => 'datetime',
        'fee_breakdown_json' => 'array',
        'provider_quote_payload_json' => 'array',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(SwapProvider::class, 'swap_provider_id');
    }

    public function sourceAsset(): BelongsTo
    {
        return $this->belongsTo(CryptoAsset::class, 'source_asset_id');
    }

    public function destinationAsset(): BelongsTo
    {
        return $this->belongsTo(CryptoAsset::class, 'destination_asset_id');
    }

    public function order(): HasOne
    {
        return $this->hasOne(SwapOrder::class, 'swap_quote_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}

