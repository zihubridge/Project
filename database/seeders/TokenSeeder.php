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
        // pool id is converted to is the base32 StrKey form of the pool ID ex-encoded pool ID (what Horizon wants) for that pool is:

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
                'name' => 'AQUA',
                'asset_code' => 'AQUA',
                'blockchain_id' => 1,
                'issuer_address' => 'GBNZILSTVQZ4R7IKQDGHYGY2QXL5QOFJYQMXPKWRRM5PAV7Y4M67AQUA',
                'pool_id' => '59fa1dc57433dcfbd2db7319d26cb3da1f28f2d8095a3ec36ad4ef9cadb0013e',
                'status' => 1,
            ],
            [
                'name' => 'Xyield',
                'asset_code' => 'XYIELD',
                'blockchain_id' => 2,
                'issuer_address' => 'rD9Cz99tYyPkgF2cK3Bp7KpRQoK1d24kd4',
                'status' => 1,
            ],
            [
                'name' => 'Army',
                'asset_code' => 'ARMY',
                'blockchain_id' => 2,
                'issuer_address' => 'rGG3wQ4kUzd7Jnmk1n5NWPZjjut62kCBfC',
                'status' => 1,
            ],
        ];

        foreach ($tokens as $token) {
            Token::create($token);
        }
    }
}
