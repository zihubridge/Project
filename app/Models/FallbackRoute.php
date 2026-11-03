<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FallbackRoute extends Model
{
    protected $table = 'fallback_routes';

    protected $fillable = [
        'destination_network_id',
        'destination_token_id',
        'intermediate_asset_id',
        'first_leg_provider_id',
        'second_leg_adapter_key',
        'priority',
        'min_amount',
        'max_amount',
        'max_slippage_bps',
        'min_liquidity_usd',
        'required_wallet_type',
        'account_requirements_note',
        'is_enabled',
        'notes',
    ];

    protected $casts = [
        'priority' => 'integer',
        'min_amount' => 'decimal:18',
        'max_amount' => 'decimal:18',
        'max_slippage_bps' => 'integer',
        'min_liquidity_usd' => 'decimal:2',
        'is_enabled' => 'boolean',
    ];

    public function destinationNetwork(): BelongsTo
    {
        return $this->belongsTo(BlockchainNetwork::class, 'destination_network_id');
    }

    public function destinationToken(): BelongsTo
    {
        return $this->belongsTo(CryptoAsset::class, 'destination_token_id');
    }

    public function intermediateAsset(): BelongsTo
    {
        return $this->belongsTo(CryptoAsset::class, 'intermediate_asset_id');
    }

    public function firstLegProvider(): BelongsTo
    {
        return $this->belongsTo(SwapProvider::class, 'first_leg_provider_id');
    }
}

