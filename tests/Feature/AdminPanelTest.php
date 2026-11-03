<?php

namespace Tests\Feature;

use App\Models\AdminLoginOtp;
use App\Models\AdminUser;
use App\Models\BlockchainNetwork;
use App\Models\CryptoAsset;
use App\Models\PlatformFeeConfiguration;
use App\Models\SwapOrder;
use App\Models\SwapProvider;
use App\Models\SwapQuote;
use App\Models\TokenListingRequest;
use App\Services\Admin\AdminAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = AdminUser::create([
            'name' => 'Root Super Admin',
            'email' => 'admin@zihubridge.com',
            'role' => AdminUser::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_request_otp(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@zihubridge.com',
        ]);

        $response->assertRedirect('/admin/login/verify');
        $this->assertDatabaseHas('admin_login_otps', [
            'email' => 'admin@zihubridge.com',
            'consumed_at' => null,
        ]);
    }

    public function test_non_existent_email_returns_uniform_success_to_prevent_enumeration(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'stranger@example.com',
        ]);

        $response->assertRedirect('/admin/login/verify');
        $this->assertDatabaseMissing('admin_login_otps', [
            'email' => 'stranger@example.com',
        ]);
    }

    public function test_admin_can_authenticate_with_valid_otp(): void
    {
        $authService = app(AdminAuthService::class);
        $result = $authService->requestOtp('admin@zihubridge.com', '127.0.0.1', 'PHPUnit');
        $devOtp = $result['dev_otp'];

        $response = $this->post('/admin/login/verify', [
            'email' => 'admin@zihubridge.com',
            'code' => $devOtp,
        ]);

        $response->assertRedirect('/admin');
        $this->assertEquals($this->superAdmin->id, session('admin_user_id'));

        // Protected dashboard accessible
        $dashResponse = $this->get('/admin');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Platform Overview');
    }

    public function test_invalid_otp_is_rejected(): void
    {
        $authService = app(AdminAuthService::class);
        $authService->requestOtp('admin@zihubridge.com', '127.0.0.1', 'PHPUnit');

        $response = $this->post('/admin/login/verify', [
            'email' => 'admin@zihubridge.com',
            'code' => '000000',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertNull(session('admin_user_id'));
    }

    public function test_otp_cannot_be_reused(): void
    {
        $authService = app(AdminAuthService::class);
        $result = $authService->requestOtp('admin@zihubridge.com', '127.0.0.1', 'PHPUnit');
        $devOtp = $result['dev_otp'];

        // First verification succeeds
        $this->post('/admin/login/verify', [
            'email' => 'admin@zihubridge.com',
            'code' => $devOtp,
        ]);

        // Clear session to test reuse
        session()->flush();

        // Second verification must fail
        $secondResponse = $this->post('/admin/login/verify', [
            'email' => 'admin@zihubridge.com',
            'code' => $devOtp,
        ]);

        $secondResponse->assertSessionHasErrors('code');
    }

    public function test_super_admin_can_update_platform_fee_schedule(): void
    {
        $this->withSession(['admin_user_id' => $this->superAdmin->id]);

        $response = $this->post('/admin/platform-fees', [
            'fee_bps' => 100, // 1%
            'fee_recipient' => 'treasury.near',
            'is_enabled' => 1,
            'reason' => 'Official protocol fee activation (1.00%)',
        ]);

        $response->assertRedirect('/admin/platform-fees');
        $this->assertDatabaseHas('platform_fee_configurations', [
            'fee_bps' => 100,
            'fee_recipient' => 'treasury.near',
            'is_enabled' => 1,
            'changed_by_admin_id' => $this->superAdmin->id,
        ]);

        // Active fee resolution test
        $activeConfig = PlatformFeeConfiguration::getActive();
        $this->assertNotNull($activeConfig);
        $this->assertEquals(100, $activeConfig->fee_bps);
        $this->assertEquals('treasury.near', $activeConfig->fee_recipient);
    }

    public function test_admin_can_view_swaps_ledger(): void
    {
        $this->withSession(['admin_user_id' => $this->superAdmin->id]);

        $response = $this->get('/admin/swaps');
        $response->assertStatus(200);
        $response->assertSee('Cross-Chain Swap Ledger');
    }

    public function test_admin_can_view_token_registry(): void
    {
        $this->withSession(['admin_user_id' => $this->superAdmin->id]);

        $response = $this->get('/admin/tokens');
        $response->assertStatus(200);
        $response->assertSee('Cross-Chain Token Catalog');
    }

    public function test_admin_can_view_and_export_revenue(): void
    {
        $this->withSession(['admin_user_id' => $this->superAdmin->id]);

        $response = $this->get('/admin/revenue');
        $response->assertStatus(200);
        $response->assertSee('Platform Revenue Accounting');

        $exportResponse = $this->get('/admin/revenue/export');
        $exportResponse->assertStatus(200);
        $this->assertStringContainsString('text/csv', $exportResponse->headers->get('content-type'));
    }

    public function test_admin_can_create_token_without_initial_price(): void
    {
        $this->withSession(['admin_user_id' => $this->superAdmin->id]);

        $network = BlockchainNetwork::create([
            'slug' => 'stellar',
            'display_name' => 'Stellar',
            'chain_family' => 'stellar',
            'native_symbol' => 'XLM',
            'requires_memo_or_tag' => true,
            'is_enabled' => true,
        ]);

        $response = $this->post('/admin/tokens', [
            'blockchain_network_id' => $network->id,
            'symbol' => 'USDC',
            'display_name' => 'USD Coin',
            'is_native' => 0,
            'contract_or_issuer' => 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
            'decimals' => 7,
            'is_enabled' => 1,
        ]);

        $response->assertRedirect('/admin/tokens');
        $this->assertDatabaseHas('crypto_assets', [
            'blockchain_network_id' => $network->id,
            'symbol' => 'USDC',
            'contract_or_issuer' => 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
            'decimals' => 7,
            'current_price_usd' => null,
            'price_updated_at' => null,
        ]);
    }
}
