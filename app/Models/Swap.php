<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Swap extends Model
{
    protected $guarded = [];

    public function fromBlockchain()
    {
        return $this->belongsTo(Blockchain::class, 'from_blockchain_id');
    }

    public function toBlockchain()
    {
        return $this->belongsTo(Blockchain::class, 'to_blockchain_id');
    }

    public function fromToken()
    {
        return $this->belongsTo(Token::class, 'from_token_id');
    }

    public function toToken()
    {
        return $this->belongsTo(Token::class, 'to_token_id');
    }

    public function deposit()
    {
        return $this->hasOne(SwapDeposit::class);
    }

    public function exchange()
    {
        return $this->hasOne(SwapExchange::class);
    }

    public function payout()
    {
        return $this->hasOne(SwapPayout::class);
    }

    public function events()
    {
        return $this->hasMany(SwapEvent::class);
    }

    public function state()
    {
        return $this->belongsTo(SwapState::class, 'swap_state_id');
    }
}
