<?php

namespace Tests\Unit;

use App\Models\BlockchainNetwork;
use App\Models\CryptoAsset;
use App\Models\FallbackRoute;
use App\Models\ProviderSupportedAsset;
use App\Models\SwapOrder;
use App\Models\SwapProvider;
use App\Models\SwapQuote;
use App\Services\Providers\Adapters\StellarSdexAdapter;
use App\Services\Providers\Contracts\LocalChainExecutionAdapterInterface;
use App\Services\Providers\Contracts\SwapExecutionProviderInterface;
use App\Services\Providers\DTOs\LocalChainQuoteData;
use App\Services\Providers\DTOs\NormalizedAssetData;
use App\Services\Providers\DTOs\NormalizedQuoteData;
use App\Services\Providers\DTOs\NormalizedSwapIntentData;
use App\Services\Providers\DTOs\NormalizedSwapStatusData;
use App\Services\Providers\DTOs\QuoteRequestData;
use App\Services\Providers\DTOs\SwapInitiationData;
use App\Services\Providers\LocalChainAdapterRegistry;
use App\Services\Providers\SwapProviderRegistry;
use App\Services\Swap\ChainAddressValidator;
use App\Services\Swap\MultiHopRoutingService;
use App\Services\Swap\QuoteService;
use App\Services\Swap\SwapExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class UniversalMultiHopRoutingTest extends TestCase
{
    use RefreshDatabase;

    private SwapProvider $nearProvider;
    private BlockchainNetwork $baseNetwork;
    private BlockchainNetwork $stellarNetwork;
    private CryptoAsset $baseUsdc;
    private CryptoAsset $stellarXlm;
    private CryptoAsset $stellarTkg;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nearProvider = SwapProvider::create([
            'slug' => 'near_intents',
            'display_name' => 'NEAR Intents',
            'driver_class' => 'FakeNearDriver',
            'is_enabled' => true,
            'priority' => 100,
        ]);

        $this->baseNetwork = BlockchainNetwork::create([
            'slug' => 'base',
            'display_name' => 'Base',
            'chain_family' => 'evm',
            'native_symbol' => 'ETH',
            'evm_chain_id' => 8453,
            'requires_memo_or_tag' => false,
            'is_enabled' => true,
        ]);

        $this->stellarNetwork = BlockchainNetwork::create([
            'slug' => 'stellar',
            'display_name' => 'Stellar',
            'chain_family' => 'stellar',
            'native_symbol' => 'XLM',
            'evm_chain_id' => null,
            'requires_memo_or_tag' => true,
            'address_regex' => '/^G[A-Z0-9]{55}$/',
            'is_enabled' => true,
        ]);

        $this->baseUsdc = CryptoAsset::create([
            'blockchain_network_id' => $this->baseNetwork->id,
            'symbol' => 'USDC',
            'display_name' => 'USD Coin',
            'is_native' => false,
            'contract_or_issuer' => '0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913',
            'decimals' => 6,
            'current_price_usd' => 1.00,
            'is_enabled' => true,
        ]);

        $this->stellarXlm = CryptoAsset::create([
            'blockchain_network_id' => $this->stellarNetwork->id,
            'symbol' => 'XLM',
            'display_name' => 'Lumen',
            'is_native' => true,
            'contract_or_issuer' => null,
            'decimals' => 7,
            'current_price_usd' => 0.10,
            'is_enabled' => true,
        ]);

        $this->stellarTkg = CryptoAsset::create([
            'blockchain_network_id' => $this->stellarNetwork->id,
            'symbol' => 'TKG',
            'display_name' => 'TokenGlade',
            'is_native' => false,
            'contract_or_issuer' => 'GAM3PID2IOBTNCBMJXHIAS4EO3GQXAGRX4UB6HTQY2DUOVL3AQRB4UKQ',
            'decimals' => 7,
            'current_price_usd' => 0.05,
            'is_enabled' => true,
        ]);

        // Map Base USDC and Stellar XLM to NEAR Intents provider
        ProviderSupportedAsset::create([
            'swap_provider_id' => $this->nearProvider->id,
            'crypto_asset_id' => $this->baseUsdc->id,
            'provider_asset_id' => 'base:0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913',
            'provider_chain_key' => 'base',
            'is_active' => true,
        ]);

        ProviderSupportedAsset::create([
            'swap_provider_id' => $this->nearProvider->id,
            'crypto_asset_id' => $this->stellarXlm->id,
            'provider_asset_id' => 'nep245:v2_1.omni.hot.tg:1100_111bzQBB5v7AhLyPMDwS8uJgQV24KaAPXtwyVWu2KXbbfQU6NXRCz',
            'provider_chain_key' => 'stellar',
            'is_active' => true,
        ]);
        // Note: stellarTkg is NOT mapped to NEAR Intents! It is "Catalog Only" on the primary provider.
    }

    public function test_multi_hop_fallback_routing_generates_valid_quote_for_unlisted_destination_token(): void
    {
        // 1. Create fallback route: Stellar -> XLM via NEAR Intents and StellarSdexAdapter
        FallbackRoute::create([
            'destination_network_id' => $this->stellarNetwork->id,
            'destination_token_id' => null, // applies to all unrouted tokens
            'intermediate_asset_id' => $this->stellarXlm->id,
            'first_leg_provider_id' => $this->nearProvider->id,
            'second_leg_adapter_key' => 'stellar_sdex',
            'priority' => 10,
            'max_slippage_bps' => 300,
            'is_enabled' => true,
        ]);

        // Mock mock provider service
        $mockNearService = new class implements SwapExecutionProviderInterface {
            public function getProviderSlug(): string { return 'near_intents'; }
            public function getDisplayName(): string { return 'NEAR Intents'; }
            public function fetchSupportedAssets(): array { return []; }
            public function requestQuote(QuoteRequestData $request): NormalizedQuoteData {
                return new NormalizedQuoteData(
                    providerSlug: 'near_intents',
                    amountInHuman: $request->amountInHuman,
                    amountInBaseUnits: $request->amountInBaseUnits,
                    expectedAmountOutHuman: '100.0000000', // 10 USDC -> 100 XLM
                    expectedAmountOutBaseUnits: '1000000000',
                    minAmountOutHuman: '99.0000000',
                    minAmountOutBaseUnits: '990000000',
                    exchangeRate: '10.0',
                    amountInUsd: '10.00',
                    amountOutUsd: '10.00',
                    slippageBps: 100,
                    platformFeeBps: 100,
                    estimatedDurationSeconds: 25,
                    expiresAt: now()->addMinutes(10),
                    feeBreakdown: ['platform_fee' => '0.10 USDC'],
                    providerRawPayload: ['mock' => true]
                );
            }
            public function initiateSwap(SwapInitiationData $request): NormalizedSwapIntentData {
                return new NormalizedSwapIntentData(
                    depositAddress: '0x1111111111111111111111111111111111111111',
                    depositMemo: null,
                    amountInHuman: '10.0',
                    amountInBaseUnits: '10000000',
                    expectedAmountOutHuman: '100.0',
                    minAmountOutHuman: '99.0',
                    expiresAt: now()->addMinutes(10),
                    estimatedDurationSeconds: 25,
                    providerContext: []
                );
            }
            public function submitDepositTransaction(SwapOrder $order, string $txHash): bool { return true; }
            public function fetchSwapStatus(SwapOrder $order): NormalizedSwapStatusData {
                return new NormalizedSwapStatusData(
                    normalizedStatus: \App\Enums\SwapStatus::COMPLETED,
                    providerStatus: 'SUCCESS'
                );
            }
        };

        // Mock Stellar SDEX Adapter
        $mockSdexAdapter = new class implements LocalChainExecutionAdapterInterface {
            public function getAdapterKey(): string { return 'stellar_sdex'; }
            public function getDisplayName(): string { return 'Stellar SDEX'; }
            public function getChainFamily(): string { return 'stellar'; }
            public function supports(BlockchainNetwork $n, CryptoAsset $i, CryptoAsset $d): bool { return true; }
            public function checkAccountRequirements(BlockchainNetwork $n, string $a, CryptoAsset $d): array {
                return ['account_exists' => true, 'prerequisites_met' => true, 'requirements' => [], 'message' => null];
            }
            public function requestSecondLegQuote(CryptoAsset $i, CryptoAsset $d, string $amount, int $slippage, ?string $recipient = null): LocalChainQuoteData {
                // 100 XLM ($10) -> 200 TKG ($0.05 each)
                return new LocalChainQuoteData(
                    adapterKey: 'stellar_sdex',
                    intermediateAmountInHuman: $amount,
                    intermediateAmountInBaseUnits: '1000000000',
                    expectedAmountOutHuman: '200.0000000',
                    expectedAmountOutBaseUnits: '2000000000',
                    minAmountOutHuman: '196.0000000',
                    exchangeRate: '2.0',
                    priceImpactBps: 20,
                    estimatedGasOrFee: '0.00001 XLM',
                    requiredWalletType: 'stellar_freighter',
                    accountRequirements: ['trustline_present' => true],
                    rawPathPayload: ['path' => []]
                );
            }
            public function buildUnsignedTransaction(SwapOrder $order, string $recipient): array {
                return ['success' => true, 'transaction_type' => 'stellar_path_payment_strict_send', 'payload' => [], 'instructions' => '', 'error' => null];
            }
            public function submitSignedTransaction(SwapOrder $order, string $signedTxData): array {
                return ['success' => true, 'tx_hash' => 'stellar_tx_123', 'explorer_url' => 'https://stellar.expert/tx/123', 'status' => 'SUBMITTED', 'error' => null];
            }
            public function fetchTransactionStatus(SwapOrder $order): array {
                return ['confirmed' => true, 'status' => 'COMPLETED', 'actual_amount_out' => '200.0', 'error' => null];
            }
        };

        $providerRegistry = new SwapProviderRegistry();
        $providerRegistry->register($mockNearService);
        $adapterRegistry = new LocalChainAdapterRegistry();
        $adapterRegistry->register($mockSdexAdapter);

        $addressValidator = new ChainAddressValidator();
        $multiHopService = new MultiHopRoutingService($providerRegistry, $adapterRegistry, $addressValidator);
        $quoteService = new QuoteService($providerRegistry, $addressValidator, $multiHopService);

        // 2. Request quote for BASE USDC -> Stellar TKG (which has NO direct NEAR Intents route)
        $stellarRecipient = 'GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5';
        $quote = $quoteService->createQuote(
            sourceAssetId: $this->baseUsdc->id,
            destinationAssetId: $this->stellarTkg->id,
            amountInHuman: '10.0',
            slippageBps: 100,
            recipientAddress: $stellarRecipient,
            refundAddress: '0x1111111111111111111111111111111111111111'
        );

        // 3. Verify quote metadata and routing
        $this->assertInstanceOf(SwapQuote::class, $quote);
        $this->assertTrue($quote->is_multi_hop);
        $this->assertEquals($this->stellarXlm->id, $quote->intermediate_asset_id);
        $this->assertEquals(0, bccomp('100.0', (string) $quote->intermediate_amount, 4));
        $this->assertEquals('stellar_sdex', $quote->second_leg_adapter_key);
        $this->assertEquals(0, bccomp('200.0', (string) $quote->expected_amount_out, 4));
        $this->assertEquals(0, bccomp('196.0', (string) $quote->min_amount_out, 4));
        $this->assertNotNull($quote->routing_metadata_json);
        $this->assertEquals('MULTI_HOP_FALLBACK', $quote->routing_metadata_json['type']);

        // 4. Initiate swap order from multi-hop quote
        $executionService = new SwapExecutionService($providerRegistry, $addressValidator, $adapterRegistry);
        $order = $executionService->initiateFromQuote(
            quoteUuid: $quote->quote_uuid,
            recipientAddress: $stellarRecipient,
            refundAddress: '0x1111111111111111111111111111111111111111',
            connectedWalletAddress: '0x1111111111111111111111111111111111111111'
        );

        $this->assertInstanceOf(SwapOrder::class, $order);
        $this->assertTrue($order->is_multi_hop);
        $this->assertEquals($this->stellarXlm->id, $order->intermediate_asset_id);
        $this->assertEquals('PENDING_FIRST_LEG', $order->second_leg_status);
        $this->assertEquals('0x1111111111111111111111111111111111111111', $order->deposit_address);

        // 5. Check multi-hop execution steps
        $steps = $order->steps()->orderBy('step_number')->get();
        $this->assertCount(3, $steps);
        $this->assertEquals('DEPOSIT', $steps[0]->step_key);
        $this->assertEquals('BRIDGE_TO_INTERMEDIATE', $steps[1]->step_key);
        $this->assertEquals('DEX_EXECUTION', $steps[2]->step_key);

        // 6. Test build unsigned transaction for second leg
        $prep = $executionService->prepareSecondLegTransaction($order);
        $this->assertTrue($prep['success']);
        $this->assertEquals('stellar_path_payment_strict_send', $prep['transaction_type']);
    }

    public function test_unsupported_destination_without_fallback_route_throws_clean_exception(): void
    {
        $mockNearService = new class implements SwapExecutionProviderInterface {
            public function getProviderSlug(): string { return 'near_intents'; }
            public function getDisplayName(): string { return 'NEAR Intents'; }
            public function fetchSupportedAssets(): array { return []; }
            public function requestQuote(QuoteRequestData $request): NormalizedQuoteData {
                throw new RuntimeException('Direct route unavailable.');
            }
            public function initiateSwap(SwapInitiationData $request): NormalizedSwapIntentData {
                throw new RuntimeException('Unsupported');
            }
            public function submitDepositTransaction(SwapOrder $order, string $txHash): bool { return false; }
            public function fetchSwapStatus(SwapOrder $order): NormalizedSwapStatusData {
                throw new RuntimeException('Unsupported');
            }
        };

        $providerRegistry = new SwapProviderRegistry();
        $providerRegistry->register($mockNearService);
        $adapterRegistry = new LocalChainAdapterRegistry();
        $addressValidator = new ChainAddressValidator();
        $multiHopService = new MultiHopRoutingService($providerRegistry, $adapterRegistry, $addressValidator);
        $quoteService = new QuoteService($providerRegistry, $addressValidator, $multiHopService);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No direct or fallback route currently available');

        $quoteService->createQuote(
            sourceAssetId: $this->baseUsdc->id,
            destinationAssetId: $this->stellarTkg->id,
            amountInHuman: '10.0',
            recipientAddress: 'GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5',
            refundAddress: '0x1111111111111111111111111111111111111111'
        );
    }
}
