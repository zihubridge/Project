<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SwapExecutionStep extends Model
{
    protected $fillable = [
        'swap_order_id',
        'step_number',
        'step_key',
        'label',
        'status',
        'network_slug',
        'tx_hash',
        'explorer_url',
        'completed_at',
        'details_json',
    ];

    protected $casts = [
        'step_number' => 'integer',
        'completed_at' => 'datetime',
        'details_json' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(SwapOrder::class, 'swap_order_id');
    }
}

