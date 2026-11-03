<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenListingRequest extends Model
{
    protected $table = 'token_listing_requests';

    protected $fillable = [
        'project_name',
        'contact_email',
        'contact_telegram_or_x',
        'token_name',
        'token_symbol',
        'blockchain_network_id',
        'contract_or_issuer',
        'decimals',
        'logo_url',
        'website_url',
        'description',
        'listing_fee_usd',
        'payment_status',
        'payment_tx_hash',
        'review_status',
        'reviewer_notes',
        'reviewed_by_admin_id',
        'published_crypto_asset_id',
        'approved_at',
        'published_at',
    ];

    protected $casts = [
        'decimals' => 'integer',
        'listing_fee_usd' => 'decimal:2',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_PENDING_PAYMENT = 'PENDING_PAYMENT';
    public const STATUS_PAID = 'PAID';
    public const STATUS_UNDER_REVIEW = 'UNDER_REVIEW';
    public const STATUS_NEEDS_CHANGES = 'NEEDS_CHANGES';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_PUBLISHED = 'PUBLISHED';
    public const STATUS_SUSPENDED = 'SUSPENDED';

    public function blockchainNetwork()
    {
        return $this->belongsTo(BlockchainNetwork::class, 'blockchain_network_id');
    }

    public function reviewedByAdmin()
    {
        return $this->belongsTo(AdminUser::class, 'reviewed_by_admin_id');
    }

    public function publishedCryptoAsset()
    {
        return $this->belongsTo(CryptoAsset::class, 'published_crypto_asset_id');
    }
}
