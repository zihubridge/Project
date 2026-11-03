<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CryptoAsset extends Model
{
    protected $fillable = [
        'blockchain_network_id',
        'symbol',
        'display_name',
        'is_native',
        'contract_or_issuer',
        'decimals',
        'current_price_usd',
        'price_updated_at',
        'logo_url',
        'is_enabled',
    ];

    protected $casts = [
        'is_native' => 'boolean',
        'decimals' => 'integer',
        'price_updated_at' => 'datetime',
        'is_enabled' => 'boolean',
    ];

    public function network(): BelongsTo
    {
        return $this->belongsTo(BlockchainNetwork::class, 'blockchain_network_id');
    }

    public function blockchainNetwork(): BelongsTo
    {
        return $this->belongsTo(BlockchainNetwork::class, 'blockchain_network_id');
    }

    public function providerMappings(): HasMany
    {
        return $this->hasMany(ProviderSupportedAsset::class, 'crypto_asset_id');
    }

    public function providerSupportedAssets(): HasMany
    {
        return $this->hasMany(ProviderSupportedAsset::class, 'crypto_asset_id');
    }
}

