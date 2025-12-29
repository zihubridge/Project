<?php

namespace Database\Seeders;

use App\Models\Token;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Token::truncate();

        $tokens = [
            [
                'name' => 'TokenGlade',
                'asset_code' => 'TKG',
                'blockchain_id' => 1,
                'issuer_address' => 'GAM3PID2IOBTNCBMJXHIAS4EO3GQXAGRX4UB6HTQY2DUOVL3AQRB4UKQ',
                'pool_id' => 'cb1922681c9d2380d34577d3c056e435a8436586e776c38a80412120c2442fb5',
                'status' => 1,
            ],
            [
                'name' => 'Xrush',
                'asset_code' => 'XRUSH',
                'blockchain_id' => 2,
                'issuer_address' => 'rD9Cz99tYyPkgF2cK3Bp7KpRQoK1d24kd4',
                'status' => 1,
            ],
            [
                'name' => 'Army',
                'asset_code' => 'ARMY',
                'blockchain_id' => 2,
                'issuer_address' => 'rnsRq5ahgbFeRiAgBVvFTafyAgiS9x9Ztn',
                'status' => 1,
            ],
        ];

        foreach ($tokens as $token) {
            Token::create($token);
        }
    }
}
