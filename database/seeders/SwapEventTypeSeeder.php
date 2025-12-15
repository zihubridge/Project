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
            ['name' => 'Deposit Failed', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Deposit Expired', 'created_at' => now(), 'updated_at' => now()],

            // Swap legs
            ['name' => 'Leg Started', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Leg Completed', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Leg Failed', 'created_at' => now(), 'updated_at' => now()],

            // Provider / bridge
            ['name' => 'Provider Order Created', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Provider Order Completed', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Provider Order Failed', 'created_at' => now(), 'updated_at' => now()],

            // Payout
            ['name' => 'Payout Initiated', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Payout Sent', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Payout Failed', 'created_at' => now(), 'updated_at' => now()],

            // Final
            ['name' => 'Swap Completed', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Swap Failed', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Swap Refunded', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
