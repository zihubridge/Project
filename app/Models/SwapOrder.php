<?php

namespace App\Models;

use App\Enums\SwapStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SwapOrder extends Model
{
    protected $fillable = [
        'swap_uuid',
        'swap_quote_id',
        'swap_provider_id',
        'source_network_id',
        'destination_network_id',
        'source_asset_id',
        'destination_asset_id',
        'amount_in',
        'amount_in_base_units',
        'expected_amount_out',
        'min_amount_out',
        'actual_amount_out',
        'refunded_amount',
        'slippage_bps',
        'connected_wallet_address',
        'recipient_address',
        'recipient_memo',
        'refund_address',
        'refund_memo',
        'deposit_address',
        'deposit_memo',
        'source_tx_hash',
        'source_explorer_url',
        'destination_tx_hash',
        'destination_explorer_url',
        'refund_tx_hash',
        'status',
        'provider_status',
        'error_code',
        'failure_reason',
        'deposit_expires_at',
        'deposit_submitted_at',
        'deposit_detected_at',
        'completed_at',
        'last_polled_at',
        'provider_context_json',
    ];

    protected $casts = [
        'slippage_bps' => 'integer',
        'deposit_expires_at' => 'datetime',
        'deposit_submitted_at' => 'datetime',
        'deposit_detected_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_polled_at' => 'datetime',
        'provider_context_json' => 'array',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(SwapQuote::class, 'swap_quote_id');
    }

    public function swapQuote(): BelongsTo
    {
        return $this->belongsTo(SwapQuote::class, 'swap_quote_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(SwapProvider::class, 'swap_provider_id');
    }

    public function swapProvider(): BelongsTo
    {
        return $this->belongsTo(SwapProvider::class, 'swap_provider_id');
    }

    public function sourceNetwork(): BelongsTo
    {
        return $this->belongsTo(BlockchainNetwork::class, 'source_network_id');
    }

    public function destinationNetwork(): BelongsTo
    {
        return $this->belongsTo(BlockchainNetwork::class, 'destination_network_id');
    }

    public function sourceAsset(): BelongsTo
    {
        return $this->belongsTo(CryptoAsset::class, 'source_asset_id');
    }

    public function destinationAsset(): BelongsTo
    {
        return $this->belongsTo(CryptoAsset::class, 'destination_asset_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(SwapExecutionStep::class, 'swap_order_id')->orderBy('step_number');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(SwapStatusHistory::class, 'swap_order_id')->orderBy('id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(SwapStatusHistory::class, 'swap_order_id')->orderBy('id');
    }

    public function statusEnum(): SwapStatus
    {
        return SwapStatus::tryFrom((string) $this->status) ?? SwapStatus::DEPOSIT_PENDING;
    }

    public function isTerminal(): bool
    {
        return $this->statusEnum()->isTerminal();
    }
}

