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

    public function deposit()
    {
        return $this->hasOne(SwapDeposit::class);
    }
}
