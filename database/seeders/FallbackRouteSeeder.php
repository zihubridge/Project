<?php

namespace Database\Seeders;

use App\Models\BlockchainNetwork;
use App\Models\CryptoAsset;
use App\Models\FallbackRoute;
use App\Models\SwapProvider;
use Illuminate\Database\Seeder;

class FallbackRouteSeeder extends Seeder
{
    public function run(): void
    {
        $stellarNetwork = BlockchainNetwork::where('slug', 'stellar')->first();
        if (!$stellarNetwork) {
            return;
        }

        $xlmAsset = CryptoAsset::where('blockchain_network_id', $stellarNetwork->id)
            ->where(function ($q) {
                $q->where('symbol', 'XLM')->orWhere('is_native', true);
            })
            ->first();

        $nearIntentsProvider = SwapProvider::where('slug', 'near_intents')->first()
            ?: SwapProvider::first();

        if ($xlmAsset && $nearIntentsProvider) {
            FallbackRoute::updateOrCreate(
                [
                    'destination_network_id' => $stellarNetwork->id,
                    'destination_token_id' => null, // Applies to all unrouted Stellar tokens
                    'intermediate_asset_id' => $xlmAsset->id,
                    'second_leg_adapter_key' => 'stellar_sdex',
                ],
                [
                    'first_leg_provider_id' => $nearIntentsProvider->id,
                    'priority' => 10,
                    'min_amount' => 1.0,
                    'max_amount' => 50000.0,
                    'max_slippage_bps' => 300,
                    'required_wallet_type' => 'stellar_freighter',
                    'account_requirements_note' => 'Stellar account must be activated and have trustline established',
                    'is_enabled' => true,
                    'notes' => 'Universal Stellar fallback route: swaps into native XLM via NEAR Intents, then trades XLM to destination asset via Stellar SDEX / AMM.',
                ]
            );
        }
    }
}

