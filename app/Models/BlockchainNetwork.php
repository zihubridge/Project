<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlockchainNetwork extends Model
{
    protected $fillable = [
        'slug',
        'display_name',
        'chain_family',
        'native_symbol',
        'evm_chain_id',
        'requires_memo_or_tag',
        'address_regex',
        'explorer_tx_url_pattern',
        'explorer_address_url_pattern',
        'logo_url',
        'is_enabled',
    ];

    protected $casts = [
        'requires_memo_or_tag' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(CryptoAsset::class, 'blockchain_network_id');
    }

    public function cryptoAssets(): HasMany
    {
        return $this->hasMany(CryptoAsset::class, 'blockchain_network_id');
    }

    public function fallbackRoutes(): HasMany
    {
        return $this->hasMany(FallbackRoute::class, 'destination_network_id');
    }

    public function formatTxExplorerUrl(?string $txHash): ?string
    {
        if (!$txHash || !$this->explorer_tx_url_pattern) {
            return null;
        }

        return str_replace('{tx}', rawurlencode($txHash), $this->explorer_tx_url_pattern);
    }

    public function formatAddressExplorerUrl(?string $address): ?string
    {
        if (!$address || !$this->explorer_address_url_pattern) {
            return null;
        }

        return str_replace('{address}', rawurlencode($address), $this->explorer_address_url_pattern);
    }
}

