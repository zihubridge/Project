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
        DB::table('swap_states')->insert([
            [
                // Initial creation of the swap record
                'name' => 'swap_created',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                // Active state for ScanDepositJob (Waiting for user to pay)
                'name' => 'waiting_for_deposit',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                // ScanDepositJob found the funds!
                'name' => 'deposit_received',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                // ExecuteSwapJob has sent the base asset (XLM/XRP) to ChangeNOW
                'name' => 'sent_to_changenow',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                // Active state for VerifyXrpAndCompleteSwap (Polling the platform wallet)
                'name' => 'waiting_changenow',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                // Verify job confirmed ChangeNOW has paid the platform wallet
                'name' => 'changenow_received',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                // Performing the internal AMM/Trustline swap back to the desired token
                'name' => 'swapping_to_token',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                // Sending the final token to the user's destination wallet
                'name' => 'sending_to_user',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                // Successful
                'name' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                // Any failure caught in any job
                'name' => 'failed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
