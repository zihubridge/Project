<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdvertisingCampaign extends Model
{
    protected $table = 'advertising_campaigns';

    protected $fillable = [
        'title',
        'advertiser_name',
        'advertiser_email',
        'campaign_type',
        'target_asset_id',
        'banner_image_url',
        'target_url',
        'start_date',
        'end_date',
        'status',
        'budget_usd',
        'impressions_count',
        'clicks_count',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget_usd' => 'decimal:2',
        'impressions_count' => 'integer',
        'clicks_count' => 'integer',
    ];

    public function targetAsset()
    {
        return $this->belongsTo(CryptoAsset::class, 'target_asset_id');
    }
}
