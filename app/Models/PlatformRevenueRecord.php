<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformRevenueRecord extends Model
{
    protected $table = 'platform_revenue_records';

    protected $fillable = [
        'swap_order_id',
        'swap_provider_id',
        'fee_bps',
        'expected_fee_amount',
        'confirmed_fee_amount',
        'fee_asset_symbol',
        'fee_asset_usd',
        'fee_recipient',
        'status',
        'fee_tx_hash',
        'collected_at',
    ];

    protected $casts = [
        'fee_bps' => 'integer',
        'expected_fee_amount' => 'decimal:18',
        'confirmed_fee_amount' => 'decimal:18',
        'fee_asset_usd' => 'decimal:8',
        'collected_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_COLLECTED = 'COLLECTED';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_UNAVAILABLE = 'UNAVAILABLE';
    public const STATUS_REFUNDED = 'REFUNDED';

    public function swapOrder()
    {
        return $this->belongsTo(SwapOrder::class, 'swap_order_id');
    }

    public function swapProvider()
    {
        return $this->belongsTo(SwapProvider::class, 'swap_provider_id');
    }
}
