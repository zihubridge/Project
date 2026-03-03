<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SwapDeposit extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'expires_at'  => 'datetime',
        'received_at' => 'datetime',
        'refunded_at' => 'datetime',
        'deleted_at'  => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function swap()
    {
        return $this->belongsTo(Swap::class);
    }

    public function expectedToken()
    {
        return $this->belongsTo(Token::class, 'expected_token_id');
    }
}
