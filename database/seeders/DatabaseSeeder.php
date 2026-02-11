<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(BlockchainSeeder::class);
        $this->call(TokenSeeder::class);
        $this->call(ChainStateKeySeeder::class);
        $this->call(SwapStatesSeeder::class);
        $this->call(SwapEventTypeSeeder::class);
        $this->call(DepositStateSeeder::class);
    }
}
