<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformFeeConfiguration extends Model
{
    protected $table = 'platform_fee_configurations';

    protected $fillable = [
        'fee_bps',
        'fee_recipient',
        'is_enabled',
        'effective_from',
        'changed_by_admin_id',
        'reason',
    ];

    protected $casts = [
        'fee_bps' => 'integer',
        'is_enabled' => 'boolean',
        'effective_from' => 'datetime',
    ];

    public function changedByAdmin()
    {
        return $this->belongsTo(AdminUser::class, 'changed_by_admin_id');
    }

    /**
     * Get the currently active platform fee configuration.
     */
    public static function getActive(): ?self
    {
        return self::where('is_enabled', true)
            ->where(function ($query) {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', now());
            })
            ->latest('id')
            ->first();
    }
}
