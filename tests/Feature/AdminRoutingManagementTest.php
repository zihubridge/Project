<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BlockchainNetwork;
use App\Models\CryptoAsset;
use App\Models\FallbackRoute;
use App\Models\SwapProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoutingManagementTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;
    private BlockchainNetwork $network;
    private CryptoAsset $asset;
    private SwapProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminUser::create([
            'name' => 'Super Administrator',
            'email' => 'admin@zihubridge.test',
            'role' => 'SUPER_ADMIN',
            'is_active' => true,
        ]);

        $this->network = BlockchainNetwork::create([
            'slug' => 'stellar',
            'display_name' => 'Stellar',
            'chain_family' => 'stellar',
            'native_symbol' => 'XLM',
            'requires_memo_or_tag' => true,
            'is_enabled' => true,
        ]);

        $this->asset = CryptoAsset::create([
            'blockchain_network_id' => $this->network->id,
            'symbol' => 'XLM',
            'display_name' => 'Lumen',
            'is_native' => true,
            'decimals' => 7,
            'is_enabled' => true,
        ]);

        $this->provider = SwapProvider::create([
            'slug' => 'near_intents',
            'display_name' => 'NEAR Intents',
            'driver_class' => 'FakeDriver',
            'is_enabled' => true,
        ]);
    }

    public function test_guest_cannot_access_fallback_routing(): void
    {
        $response = $this->get('/admin/routing');
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_view_fallback_routes_index(): void
    {
        FallbackRoute::create([
            'destination_network_id' => $this->network->id,
            'intermediate_asset_id' => $this->asset->id,
            'first_leg_provider_id' => $this->provider->id,
            'second_leg_adapter_key' => 'stellar_sdex',
            'priority' => 10,
            'max_slippage_bps' => 300,
            'is_enabled' => true,
        ]);

        $response = $this->withSession(['admin_user_id' => $this->admin->id])
            ->get('/admin/routing');

        $response->assertOk();
        $response->assertSee('Universal Fallback Routing Engine');
        $response->assertSee('stellar_sdex');
        $response->assertSee('XLM');
    }

    public function test_admin_can_create_fallback_route(): void
    {
        $response = $this->withSession(['admin_user_id' => $this->admin->id])
            ->post('/admin/routing', [
                'destination_network_id' => $this->network->id,
                'intermediate_asset_id' => $this->asset->id,
                'first_leg_provider_id' => $this->provider->id,
                'second_leg_adapter_key' => 'stellar_sdex',
                'priority' => 5,
                'max_slippage_bps' => 250,
                'is_enabled' => 1,
                'notes' => 'Test universal route for Stellar',
            ]);

        $response->assertRedirect('/admin/routing');
        $this->assertDatabaseHas('fallback_routes', [
            'destination_network_id' => $this->network->id,
            'intermediate_asset_id' => $this->asset->id,
            'priority' => 5,
            'max_slippage_bps' => 250,
            'is_enabled' => 1,
        ]);
    }

    public function test_admin_can_toggle_and_delete_fallback_route(): void
    {
        $route = FallbackRoute::create([
            'destination_network_id' => $this->network->id,
            'intermediate_asset_id' => $this->asset->id,
            'first_leg_provider_id' => $this->provider->id,
            'second_leg_adapter_key' => 'stellar_sdex',
            'priority' => 10,
            'max_slippage_bps' => 300,
            'is_enabled' => true,
        ]);

        // Toggle to disabled
        $toggleResponse = $this->withSession(['admin_user_id' => $this->admin->id])
            ->post("/admin/routing/{$route->id}/toggle");

        $toggleResponse->assertRedirect('/admin/routing');
        $this->assertDatabaseHas('fallback_routes', [
            'id' => $route->id,
            'is_enabled' => false,
        ]);

        // Delete route
        $deleteResponse = $this->withSession(['admin_user_id' => $this->admin->id])
            ->delete("/admin/routing/{$route->id}");

        $deleteResponse->assertRedirect('/admin/routing');
        $this->assertDatabaseMissing('fallback_routes', [
            'id' => $route->id,
        ]);
    }
}

