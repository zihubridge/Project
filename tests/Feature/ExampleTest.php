<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        Http::fake([
            '*/v0/tokens' => Http::response([
                [
                    'assetId' => 'nep141:wrap.near',
                    'decimals' => 24,
                    'blockchain' => 'near',
                    'symbol' => 'wNEAR',
                    'price' => 3.50,
                ],
                [
                    'assetId' => 'nep141:eth.omft.near',
                    'decimals' => 18,
                    'blockchain' => 'eth',
                    'symbol' => 'ETH',
                    'price' => 2800.00,
                ],
            ], 200),
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('ZihuBridge');
    }
}
