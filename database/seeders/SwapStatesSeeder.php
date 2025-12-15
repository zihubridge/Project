<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
                'name' => 'Pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Running',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Completed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Failed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
