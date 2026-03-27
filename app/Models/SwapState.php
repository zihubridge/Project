<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SwapState extends Model
{
    protected $fillable = ['name'];

    public function swaps()
    {
        return $this->hasMany(Swap::class, 'swap_state_id');
    }
}
