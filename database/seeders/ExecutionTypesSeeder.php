<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExecutionTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('execution_types')->insert([
            [
                'name' => 'Stellar Swap',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Changenow Swap',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Xrpl Swap',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
