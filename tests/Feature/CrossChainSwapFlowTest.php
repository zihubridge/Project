<?php

namespace Tests\Feature;

use App\Enums\SwapStatus;
use App\Models\BlockchainNetwork;
use App\Models\CryptoAsset;
use App\Models\SwapOrder;
use App\Models\SwapQuote;
use App\Services\Swap\AmountNormalizer;
use App\Services\Swap\CatalogSyncService;
use App\Services\Swap\SwapExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CrossChainSwapFlowTest extends TestCase
{
    use RefreshDatabase;

    private function fakeNearIntentsEndpoints(): void
    {
        Http::fake([
            'https://1click.chaindefuser.com/v0/tokens' => Http::response([
                [
                    'assetId' => 'nep141:eth-0xa0b86991c6218b36c1d19d4a2e9eb0ce3606eb48.omft.near',
                    'decimals' => 6,
                    'blockchain' => 'eth',
                    'symbol' => 'USDC',
                    'price' => 1.0,
                    'priceUpdatedAt' => '2026-11-03T00:00:00.000Z',
                    'contractAddress' => '0xa0b86991c6218b36c1d19d4a2e9eb0ce3606eb48',
                ],
                [
                    'assetId' => 'nep141:sol.omft.near',
                    'decimals' => 9,
                    'blockchain' => 'sol',
                    'symbol' => 'SOL',
                    'price' => 180.0,
                    'priceUpdatedAt' => '2026-11-03T00:00:00.000Z',
                ],
                [
                    'assetId' => 'nep141:stellar.omft.near',
                    'decimals' => 7,
                    'blockchain' => 'stellar',
                    'symbol' => 'XLM',
                    'price' => 0.40,
                    'priceUpdatedAt' => '2026-11-03T00:00:00.000Z',
                ],
                [
                    'assetId' => 'nep141:xrp.omft.near',
                    'decimals' => 6,
                    'blockchain' => 'xrp',
                    'symbol' => 'XRP',
                    'price' => 2.50,
                    'priceUpdatedAt' => '2026-11-03T00:00:00.000Z',
                ],
            ], 200),

            'https://1click.chaindefuser.com/v0/quote' => function ($request) {
                $body = $request->data();
                $isDry = (bool) ($body['dry'] ?? true);

                return Http::response([
                    'timestamp' => '2026-11-03T02:00:00.000Z',
                    'signature' => 'ed25519:mocksignature123',
                    'quoteRequest' => $body,
                    'quote' => [
                        'depositAddress' => $isDry ? null : '0x1111111111111111111111111111111111111111',
                        'depositMemo' => null,
                        'amountIn' => $body['amount'],
                        'amountInFormatted' => '180',
                        'amountInUsd' => '180.00',
                        'minAmountIn' => $body['amount'],
                        'amountOut' => '995000000',
                        'amountOutFormatted' => '0.995',
                        'amountOutUsd' => '179.10',
                        'minAmountOut' => '985050000',
                        'deadline' => now()->addMinutes(15)->toIso8601ZuluString(),
                        'timeEstimate' => 25,
                    ],
                ], 200);
            },

            'https://1click.chaindefuser.com/v0/deposit/submit' => Http::response([
                'status' => 'OK',
            ], 200),

            'https://1click.chaindefuser.com/v0/status*' => Http::response([
                'status' => 'SUCCESS',
                'swapDetails' => [
                    'originChainTxHashes' => [
                        [
                            'hash' => '0xaaaa111122223333444455556666777788889999aaaabbbbccccddddeeeeffff',
                            'explorerUrl' => 'https://etherscan.io/tx/0xaaaa111122223333444455556666777788889999aaaabbbbccccddddeeeeffff',
                        ],
                    ],
                    'destinationChainTxHashes' => [
                        [
                            'hash' => '5SolTxHashExample111111111111111111111111111111111111',
                            'explorerUrl' => 'https://solscan.io/tx/5SolTxHashExample111111111111111111111111111111111111',
                        ],
                    ],
                    'amountIn' => '180000000',
                    'amountInFormatted' => '180',
                    'amountOut' => '996000000',
                    'amountOutFormatted' => '0.996',
                ],
            ], 200),
        ]);
    }

    public function test_catalog_sync_populates_dynamic_networks_and_assets_without_hardcoding(): void
    {
        $this->fakeNearIntentsEndpoints();

        /** @var CatalogSyncService $syncService */
        $syncService = $this->app->make(CatalogSyncService::class);
        $stats = $syncService->syncAllProviders();

        $this->assertSame(1, $stats['providers_synced']);
        $this->assertSame(4, $stats['networks_upserted']);
        $this->assertSame(4, $stats['assets_upserted']);

        $this->assertDatabaseHas('swap_providers', [
            'slug' => 'near_intents',
            'is_enabled' => true,
        ]);

        $networksResp = $this->getJson('/api/v1/networks');
        $networksResp->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('count', 4);

        $ethAssetsResp = $this->getJson('/api/v1/assets?network=eth');
        $ethAssetsResp->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.symbol', 'USDC');
    }

    public function test_complete_cross_chain_quote_initiation_deposit_and_settlement_lifecycle(): void
    {
        $this->fakeNearIntentsEndpoints();

        /** @var CatalogSyncService $syncService */
        $syncService = $this->app->make(CatalogSyncService::class);
        $syncService->syncAllProviders();

        $usdcEth = CryptoAsset::where('symbol', 'USDC')->firstOrFail();
        $sol = CryptoAsset::where('symbol', 'SOL')->firstOrFail();

        // 1. Request Quote
        $quoteResp = $this->postJson('/api/v1/quotes', [
            'source_asset_id' => $usdcEth->id,
            'destination_asset_id' => $sol->id,
            'amount' => '180',
            'slippage_bps' => 100,
        ]);

        $quoteResp->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('data.expected_amount_out', '0.995')
            ->assertJsonPath('data.min_amount_out', '0.98505');

        $quoteUuid = $quoteResp->json('data.quote_uuid');
        $this->assertNotEmpty($quoteUuid);

        // 2. Initiate Swap Order (locks deposit address)
        $swapResp = $this->postJson('/api/v1/swaps', [
            'quote_uuid' => $quoteUuid,
            'recipient_address' => '13QkxhNMrTPxoCkRdYdJ65tFuwXPhL5gLS2Z5Nr6gjRK',
            'refund_address' => '0x71C7656EC7ab88b098defB751B7401B5f6d8976F',
        ]);

        $swapResp->assertCreated()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('data.status', SwapStatus::DEPOSIT_PENDING->value)
            ->assertJsonPath('data.deposit_instructions.deposit_address', '0x1111111111111111111111111111111111111111');

        $swapUuid = $swapResp->json('data.swap_uuid');

        // 3. Idempotency check: initiating the same quote_uuid again returns the existing order
        $duplicateResp = $this->postJson('/api/v1/swaps', [
            'quote_uuid' => $quoteUuid,
            'recipient_address' => '13QkxhNMrTPxoCkRdYdJ65tFuwXPhL5gLS2Z5Nr6gjRK',
            'refund_address' => '0x71C7656EC7ab88b098defB751B7401B5f6d8976F',
        ]);
        $duplicateResp->assertCreated()
            ->assertJsonPath('data.swap_uuid', $swapUuid);
        $this->assertSame(1, SwapOrder::count());

        // 4. Submit user's origin deposit transaction hash
        $txHash = '0xaaaa111122223333444455556666777788889999aaaabbbbccccddddeeeeffff';
        $submitResp = $this->postJson("/api/v1/swaps/{$swapUuid}/deposit-tx", [
            'tx_hash' => $txHash,
        ]);

        // Because the queue runs synchronously in tests, PollSwapStatusJob also runs and sees SUCCESS!
        $submitResp->assertOk()
            ->assertJsonPath('status', 1);

        // 5. Fetch status and verify COMPLETED transition & step completion
        $statusResp = $this->getJson("/api/v1/swaps/{$swapUuid}");
        $statusResp->assertOk()
            ->assertJsonPath('data.status', SwapStatus::COMPLETED->value)
            ->assertJsonPath('data.destination.actual_amount_out', '0.996')
            ->assertJsonPath('data.destination.tx_hash', '5SolTxHashExample111111111111111111111111111111111111');
    }

    public function test_refunded_swap_status_transitions_correctly(): void
    {
        Http::fake([
            'https://1click.chaindefuser.com/v0/tokens' => Http::response([
                [
                    'assetId' => 'nep141:eth-0xa0b86991c6218b36c1d19d4a2e9eb0ce3606eb48.omft.near',
                    'decimals' => 6,
                    'blockchain' => 'eth',
                    'symbol' => 'USDC',
                    'price' => 1.0,
                ],
                [
                    'assetId' => 'nep141:sol.omft.near',
                    'decimals' => 9,
                    'blockchain' => 'sol',
                    'symbol' => 'SOL',
                    'price' => 180.0,
                ],
            ], 200),
            'https://1click.chaindefuser.com/v0/quote' => Http::response([
                'quote' => [
                    'depositAddress' => '0x2222222222222222222222222222222222222222',
                    'amountIn' => '100000000',
                    'amountInFormatted' => '100',
                    'amountOut' => '500000000',
                    'amountOutFormatted' => '0.5',
                    'minAmountOut' => '495000000',
                    'deadline' => now()->addMinutes(15)->toIso8601ZuluString(),
                    'timeEstimate' => 25,
                ],
            ], 200),
            'https://1click.chaindefuser.com/v0/status*' => Http::response([
                'status' => 'REFUNDED',
                'swapDetails' => [
                    'originChainTxHashes' => ['0xrefundtxhash12345'],
                    'refundedAmount' => '100000000',
                    'reason' => 'Slippage exceeded during volatility',
                ],
            ], 200),
        ]);

        /** @var CatalogSyncService $syncService */
        $syncService = $this->app->make(CatalogSyncService::class);
        $syncService->syncAllProviders();

        $usdc = CryptoAsset::where('symbol', 'USDC')->firstOrFail();
        $sol = CryptoAsset::where('symbol', 'SOL')->firstOrFail();

        $quoteUuid = $this->postJson('/api/v1/quotes', [
            'source_asset_id' => $usdc->id,
            'destination_asset_id' => $sol->id,
            'amount' => '100',
        ])->json('data.quote_uuid');

        $swapResp = $this->postJson('/api/v1/swaps', [
            'quote_uuid' => $quoteUuid,
            'recipient_address' => '13QkxhNMrTPxoCkRdYdJ65tFuwXPhL5gLS2Z5Nr6gjRK',
            'refund_address' => '0x71C7656EC7ab88b098defB751B7401B5f6d8976F',
        ]);

        $swapUuid = $swapResp->json('data.swap_uuid');
        $order = SwapOrder::where('swap_uuid', $swapUuid)->firstOrFail();

        /** @var SwapExecutionService $execService */
        $execService = $this->app->make(SwapExecutionService::class);
        $updated = $execService->syncOrderStatus($order, 'TEST');

        $this->assertSame(SwapStatus::REFUNDED->value, $updated->status);
        $this->assertSame('100', AmountNormalizer::trimTrailingZeros((string) $updated->refunded_amount));
    }

    public function test_expired_quote_cannot_be_initiated(): void
    {
        $this->fakeNearIntentsEndpoints();

        /** @var CatalogSyncService $syncService */
        $syncService = $this->app->make(CatalogSyncService::class);
        $syncService->syncAllProviders();

        $usdc = CryptoAsset::where('symbol', 'USDC')->firstOrFail();
        $sol = CryptoAsset::where('symbol', 'SOL')->firstOrFail();

        $quoteUuid = $this->postJson('/api/v1/quotes', [
            'source_asset_id' => $usdc->id,
            'destination_asset_id' => $sol->id,
            'amount' => '50',
        ])->json('data.quote_uuid');

        SwapQuote::where('quote_uuid', $quoteUuid)->update([
            'expires_at' => now()->subMinute(),
        ]);

        $swapResp = $this->postJson('/api/v1/swaps', [
            'quote_uuid' => $quoteUuid,
            'recipient_address' => '13QkxhNMrTPxoCkRdYdJ65tFuwXPhL5gLS2Z5Nr6gjRK',
            'refund_address' => '0x71C7656EC7ab88b098defB751B7401B5f6d8976F',
        ]);

        $swapResp->assertStatus(422)
            ->assertJsonPath('status', 0);
    }

    public function test_invalid_wallet_addresses_are_rejected_at_initiation(): void
    {
        $this->fakeNearIntentsEndpoints();

        /** @var CatalogSyncService $syncService */
        $syncService = $this->app->make(CatalogSyncService::class);
        $syncService->syncAllProviders();

        $usdc = CryptoAsset::where('symbol', 'USDC')->firstOrFail();
        $sol = CryptoAsset::where('symbol', 'SOL')->firstOrFail();

        $quoteUuid = $this->postJson('/api/v1/quotes', [
            'source_asset_id' => $usdc->id,
            'destination_asset_id' => $sol->id,
            'amount' => '50',
        ])->json('data.quote_uuid');

        // Pass an EVM address where a Solana recipient address is required
        $swapResp = $this->postJson('/api/v1/swaps', [
            'quote_uuid' => $quoteUuid,
            'recipient_address' => '0x71C7656EC7ab88b098defB751B7401B5f6d8976F',
            'refund_address' => '0x71C7656EC7ab88b098defB751B7401B5f6d8976F',
        ]);

        $swapResp->assertStatus(422)
            ->assertJsonPath('status', 0);
    }

    public function test_web_swap_tracking_and_history_pages_and_history_api_work_cleanly(): void
    {
        $this->fakeNearIntentsEndpoints();

        /** @var CatalogSyncService $syncService */
        $syncService = $this->app->make(CatalogSyncService::class);
        $syncService->syncAllProviders();

        $usdc = CryptoAsset::where('symbol', 'USDC')->firstOrFail();
        $sol = CryptoAsset::where('symbol', 'SOL')->firstOrFail();

        $quoteUuid = $this->postJson('/api/v1/quotes', [
            'source_asset_id' => $usdc->id,
            'destination_asset_id' => $sol->id,
            'amount' => '180',
        ])->json('data.quote_uuid');

        $recipient = '13QkxhNMrTPxoCkRdYdJ65tFuwXPhL5gLS2Z5Nr6gjRK';
        $refund = '0x71C7656EC7ab88b098defB751B7401B5f6d8976F';

        $swapUuid = $this->postJson('/api/v1/swaps', [
            'quote_uuid' => $quoteUuid,
            'recipient_address' => $recipient,
            'refund_address' => $refund,
        ])->json('data.swap_uuid');

        // Test Web Swap Tracking View
        $this->get("/swap/{$swapUuid}")
            ->assertOk()
            ->assertSee($swapUuid)
            ->assertSee('0x1111111111111111111111111111111111111111');

        // Test Web History View
        $this->get('/history')
            ->assertOk()
            ->assertSee('Swap Explorer & History', false);

        // Test History API filtering by wallet address
        $this->getJson("/api/v1/swaps?address={$recipient}")
            ->assertOk()
            ->assertJsonPath('data.0.swap_uuid', $swapUuid);
    }

    public function test_stellar_origin_uses_memo_deposit_mode_and_preview_address_fallback_works(): void
    {
        $capturedPayloads = [];

        Http::fake([
            'https://1click.chaindefuser.com/v0/tokens' => Http::response([
                [
                    'assetId' => 'nep141:stellar.omft.near',
                    'decimals' => 7,
                    'blockchain' => 'stellar',
                    'symbol' => 'XLM',
                    'price' => 0.40,
                ],
                [
                    'assetId' => 'nep141:eth-0xa0b86991c6218b36c1d19d4a2e9eb0ce3606eb48.omft.near',
                    'decimals' => 6,
                    'blockchain' => 'eth',
                    'symbol' => 'USDC',
                    'price' => 1.0,
                ],
            ], 200),
            'https://1click.chaindefuser.com/v0/quote' => function ($request) use (&$capturedPayloads) {
                $body = $request->data();
                $capturedPayloads[] = $body;

                return Http::response([
                    'quote' => [
                        'depositAddress' => 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
                        'depositMemo' => '10948572',
                        'amountIn' => $body['amount'],
                        'amountInFormatted' => '100',
                        'amountOut' => '39800000',
                        'amountOutFormatted' => '39.8',
                        'minAmountOut' => '39402000',
                        'deadline' => now()->addMinutes(15)->toIso8601ZuluString(),
                        'timeEstimate' => 20,
                    ],
                ], 200);
            },
            'https://1click.chaindefuser.com/v0/status*' => Http::response([
                'status' => 'PENDING_DEPOSIT',
                'swapDetails' => [],
            ], 200),
        ]);

        /** @var CatalogSyncService $syncService */
        $syncService = $this->app->make(CatalogSyncService::class);
        $syncService->syncAllProviders();

        $xlm = CryptoAsset::where('symbol', 'XLM')->firstOrFail();
        $usdc = CryptoAsset::where('symbol', 'USDC')->firstOrFail();

        $quoteResp = $this->postJson('/api/v1/quotes', [
            'source_asset_id' => $xlm->id,
            'destination_asset_id' => $usdc->id,
            'amount' => '100',
        ]);

        $quoteResp->assertOk();
        $this->assertNotEmpty($capturedPayloads);
        $this->assertSame('MEMO', $capturedPayloads[0]['depositMode']);

        $swapResp = $this->postJson('/api/v1/swaps', [
            'quote_uuid' => $quoteResp->json('data.quote_uuid'),
            'recipient_address' => '0x71C7656EC7ab88b098defB751B7401B5f6d8976F',
            'refund_address' => 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
        ]);

        $swapResp->assertCreated()
            ->assertJsonPath('data.deposit_instructions.deposit_address', 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN')
            ->assertJsonPath('data.deposit_instructions.deposit_memo', '10948572');
    }

    public function test_stellar_destination_trustline_validation(): void
    {
        $this->fakeNearIntentsEndpoints();

        /** @var CatalogSyncService $syncService */
        $syncService = $this->app->make(CatalogSyncService::class);
        $syncService->syncAllProviders();

        $stellarNetwork = BlockchainNetwork::where('slug', 'stellar')->firstOrFail();
        $stellarUsdc = CryptoAsset::create([
            'blockchain_network_id' => $stellarNetwork->id,
            'symbol' => 'USDC',
            'display_name' => 'USD Coin',
            'contract_or_issuer' => 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
            'decimals' => 7,
            'is_native' => false,
            'is_enabled' => true,
        ]);

        $wallet = 'GDBY24O7XLV5HN56D4O24F6G6RHXL7EVJVEQFPYYEW4RA5RBPXYOBKA4';

        Http::fake([
            'https://horizon.stellar.org/accounts/*' => Http::sequence()
                ->push([
                    'balances' => [
                        ['asset_type' => 'native', 'balance' => '5.0000000'],
                        ['asset_type' => 'credit_alphanum4', 'asset_code' => 'TKG', 'asset_issuer' => 'GAM3PID2IOBTNCBMJXHIAS4EO3GQXAGRX4UB6HTQY2DUOVL3AQRB4UKQ', 'balance' => '10.0000000'],
                    ],
                ], 200)
                ->push([
                    'balances' => [
                        ['asset_type' => 'native', 'balance' => '5.0000000'],
                        ['asset_type' => 'credit_alphanum4', 'asset_code' => 'USDC', 'asset_issuer' => $stellarUsdc->contract_or_issuer, 'balance' => '100.0000000'],
                    ],
                ], 200),
        ]);

        $respMissingTrustline = $this->postJson('/api/v1/addresses/validate', [
            'network_slug' => 'stellar',
            'address' => $wallet,
            'asset_id' => $stellarUsdc->id,
        ]);

        $respMissingTrustline->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('valid', false)
            ->assertJsonPath('trustline_missing', true);

        $respHasTrustline = $this->postJson('/api/v1/addresses/validate', [
            'network_slug' => 'stellar',
            'address' => $wallet,
            'asset_id' => $stellarUsdc->id,
        ]);

        $respHasTrustline->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('valid', true);
    }
}
