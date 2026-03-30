<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    protected $guarded = [];

    public function blockchain()
    {
        return $this->belongsTo(Blockchain::class);
    }
}
