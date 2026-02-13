<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InternalSwapStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $states = [
            'creating',
            'confirmed',
            'failed',
        ];

        foreach ($states as $state) {
            DB::table('internal_swap_states')->insert([
                'name' => $state,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
