<?php

namespace Database\Seeders;

use App\Models\Blockchain;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BlockchainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Blockchain::truncate();

        $blockchains = [
            ['name' => 'Stellar', 'asset_code' => 'XLM'],
            ['name' => 'Ripple', 'asset_code' => 'XRP'],
        ];

        foreach ($blockchains as $blockchains) {
            Blockchain::create($blockchains);
        }
    }
}
