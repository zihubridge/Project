<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SwapDeposit extends Model
{
    protected $guarded = [];
    use SoftDeletes;

    public function swap()
    {
        return $this->belongsTo(Swap::class);
    }

    public function expectedToken()
    {
        return $this->belongsTo(Token::class, 'expected_token_id');
    }
}
