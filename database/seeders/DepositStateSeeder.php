<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepositStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        DB::table('deposit_states')->truncate();

        DB::table('deposit_states')->insert([
            [
                'id' => 1,
                'key' => 'waiting',
                'label' => 'Waiting for Deposit',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'key' => 'detected',
                'label' => 'Deposit Detected',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'key' => 'confirmed',
                'label' => 'Deposit Confirmed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'key' => 'expired',
                'label' => 'Deposit Expired',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'key' => 'failed',
                'label' => 'Deposit Failed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'key' => 'refunded',
                'label' => 'Deposit Refunded',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'key' => 'late_received',
                'label' => 'Late Received',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
