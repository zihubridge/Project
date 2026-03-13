<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformWalletsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('platform_wallets')->insert([
            // =========================
            // Stellar – Mainnet Hot Wallet
            // =========================
            [
                'blockchain_id'   => 1, // Stellar
                'label'           => 'stellar_hot_mainnet',
                'public_address'  => env('STELLAR_PUBLIC_ADDRESS'),
                'secret_encrypted' => env('STELLAR_SECRET_KEY')
                    ? encrypt(env('STELLAR_SECRET_KEY'))
                    : null,
                'is_active'       => true,
                'is_testnet'      => false,
                'meta'            => json_encode([
                    'network' => 'stellar',
                    'type'    => 'hot',
                ]),
                'created_at'      => now(),
                'updated_at'      => now(),
            ],

            // =========================
            // Stellar – Testnet Wallet
            // =========================
            [
                'blockchain_id'   => 1, // Stellar
                'label'           => 'stellar_hot_testnet',
                'public_address'  => env('STELLAR_TESTNET_PUBLIC_ADDRESS'),
                'secret_encrypted' => env('STELLAR_TESTNET_SECRET_KEY')
                    ? encrypt(env('STELLAR_TESTNET_SECRET_KEY'))
                    : null,
                'is_active'       => true,
                'is_testnet'      => true,
                'meta'            => json_encode([
                    'network' => 'stellar_testnet',
                    'type'    => 'hot',
                ]),
                'created_at'      => now(),
                'updated_at'      => now(),
            ],

            // =========================
            // XRPL – Mainnet Hot Wallet
            // =========================
            [
                'blockchain_id'   => 2, // XRPL
                'label'           => 'xrpl_hot_mainnet',
                'public_address'  => env('XRPL_PUBLIC_ADDRESS'),
                'secret_encrypted' => env('XRPL_SECRET_KEY')
                    ? encrypt(env('XRPL_SECRET_KEY'))
                    : null,
                'is_active'       => true,
                'is_testnet'      => false,
                'meta'            => json_encode([
                    'network' => 'xrpl',
                    'type'    => 'hot',
                ]),
                'created_at'      => now(),
                'updated_at'      => now(),
            ],

            // =========================
            // XRPL – Testnet Wallet
            // =========================
            [
                'blockchain_id'   => 2, // XRPL
                'label'           => 'xrpl_hot_testnet',
                'public_address'  => env('XRPL_TESTNET_PUBLIC_ADDRESS'),
                'secret_encrypted' => env('XRPL_TESTNET_SECRET_KEY')
                    ? encrypt(env('XRPL_TESTNET_SECRET_KEY'))
                    : null,
                'is_active'       => true,
                'is_testnet'      => true,
                'meta'            => json_encode([
                    'network' => 'xrpl_testnet',
                    'type'    => 'hot',
                ]),
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);
    }
}
