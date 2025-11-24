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
                'issuer_address' => 'Stellar',
                'blockchain_id' => 1,
                'status' => 1,
            ],
            [
                'name' => 'Xrush',
                'asset_code' => 'XRUSH',
                'issuer_address' => 'Ripple',
                'blockchain_id' => 2,
                'status' => 1,
            ],
        ];

        foreach ($tokens as $token) {
            Token::create($token);
        }
    }
}
