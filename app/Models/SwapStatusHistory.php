<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SwapStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'swap_order_id',
        'previous_status',
        'new_status',
        'provider_status',
        'triggered_by',
        'idempotency_key',
        'message',
        'metadata_json',
        'created_at',
    ];

    protected $casts = [
        'metadata_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(SwapOrder::class, 'swap_order_id');
    }
}

