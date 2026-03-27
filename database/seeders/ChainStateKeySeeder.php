<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChainStateKeySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('chain_state_keys')->insert([
            [
                'name' => 'Payments Cursor',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'last Checked Ledger',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Last Checked Block',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Last Seen Tx',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Last Processed Slot',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
