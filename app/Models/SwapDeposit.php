<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SwapDeposit extends Model
{
    protected $guarded = [];

    public function swap()
    {
        return $this->belongsTo(Swap::class);
    }

    public function expectedToken()
    {
        return $this->belongsTo(Token::class, 'expected_token_id');
    }
}
