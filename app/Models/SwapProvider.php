<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SwapProvider extends Model
{
    protected $fillable = [
        'slug',
        'display_name',
        'driver_class',
        'is_enabled',
        'priority',
        'config_json',
        'last_catalog_synced_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'priority' => 'integer',
        'config_json' => 'array',
        'last_catalog_synced_at' => 'datetime',
    ];

    public function supportedAssets(): HasMany
    {
        return $this->hasMany(ProviderSupportedAsset::class, 'swap_provider_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(SwapQuote::class, 'swap_provider_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(SwapOrder::class, 'swap_provider_id');
    }
}

