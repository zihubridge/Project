<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SwapStatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('swap_states')->truncate();

        $states = [
            // Creation
            'created',
            'waiting_deposit',
            'deposit_confirmed',

            // Internal execution
            'internal_swap_started',
            'internal_swap_completed',

            // External provider
            'provider_processing',
            'provider_completed',

            // Finalization
            'payout_processing',
            'completed',
            'expired',
            'failed',
            'late_received',
            'refunded',
        ];

        foreach ($states as $state) {
            DB::table('swap_states')->insert([
                'name' => $state,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
