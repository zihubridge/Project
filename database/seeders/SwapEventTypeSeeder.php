<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SwapEventTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('swap_event_types')->insert([
            // Deposit
            ['name' => 'Deposit Detected', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Deposit Confirmed', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Deposit Late', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Deposit Expired', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Deposit Failed', 'created_at' => now(), 'updated_at' => now()],

            // INTERNAL SWAP LEG
            ['name' => 'Internal Swap Started', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Internal Swap Completed', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Internal Swap Failed', 'created_at' => now(), 'updated_at' => now()],

            // PROVIDER (Exchange)
            ['name' => 'Exchange Order Created', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Exchange Funds Sent', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Exchange Funds Received', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Exchange Order Completed', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Exchange Order Failed', 'created_at' => now(), 'updated_at' => now()],

            // PAYOUT
            ['name' => 'Payout Initiated', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Payout Sent', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Payout Confirmed', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Payout Failed', 'created_at' => now(), 'updated_at' => now()],

            // FINAL
            ['name' => 'Swap Completed', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Swap Failed', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Swap Refunded', 'created_at' => now(), 'updated_at' => now()],

        ]);
    }
}
