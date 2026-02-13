<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SwapExchangeStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('swap_exchange_states')->truncate();

        $swap_exchange_states = [
            // Provider order lifecycle
            'order_creating',
            'order_created',

            // Funds transfer to provider
            'sending_to_provider',
            'sent_to_provider',

            // Provider side lifecycle
            'waiting_provider',
            'provider_exchanging',
            'provider_completed',

            // Controlled failures
            'provider_retryable_error',
            'provider_failed_permanent',

            // Safety / edge cases
            'refunding',
            'refunded',
        ];

        foreach ($swap_exchange_states as $swap_exchange_state) {
            DB::table('swap_exchange_states')->insert([
                'name' => $swap_exchange_state,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
