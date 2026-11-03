<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\BlockchainNetwork;
use App\Models\CryptoAsset;
use App\Models\FallbackRoute;
use App\Models\SwapProvider;
use App\Services\Providers\LocalChainAdapterRegistry;
use App\Services\Swap\CatalogSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminRoutingController extends Controller
{
    public function __construct(
        private readonly LocalChainAdapterRegistry $adapterRegistry
    ) {}

    public function index()
    {
        $routes = FallbackRoute::with([
            'destinationNetwork',
            'destinationToken',
            'intermediateAsset.network',
            'firstLegProvider',
        ])
            ->orderBy('priority')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.routing.index', [
            'routes' => $routes,
        ]);
    }

    public function create()
    {
        $networks = BlockchainNetwork::where('is_enabled', true)->orderBy('display_name')->get();
        $assets = CryptoAsset::with('network')->where('is_enabled', true)->orderBy('symbol')->get();
        $providers = SwapProvider::where('is_enabled', true)->orderByDesc('priority')->get();
        $adapters = $this->adapterRegistry->all();

        return view('admin.routing.create', [
            'networks' => $networks,
            'assets' => $assets,
            'providers' => $providers,
            'adapters' => $adapters,
        ]);
    }

    public function store(Request $request)
    {
        $admin = $request->attributes->get('current_admin');

        $validated = $request->validate([
            'destination_network_id' => ['required', 'exists:blockchain_networks,id'],
            'destination_token_id' => ['nullable', 'exists:crypto_assets,id'],
            'intermediate_asset_id' => ['required', 'exists:crypto_assets,id'],
            'first_leg_provider_id' => ['required', 'exists:swap_providers,id'],
            'second_leg_adapter_key' => ['required', 'string', 'max:64'],
            'priority' => ['required', 'integer', 'min:1', 'max:1000'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'max_slippage_bps' => ['required', 'integer', 'min:10', 'max:5000'],
            'min_liquidity_usd' => ['nullable', 'numeric', 'min:0'],
            'required_wallet_type' => ['nullable', 'string', 'max:64'],
            'account_requirements_note' => ['nullable', 'string', 'max:255'],
            'is_enabled' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $route = FallbackRoute::create($validated);

        Cache::forget(CatalogSyncService::CACHE_KEY_NETWORKS);

        AdminAuditLog::log(
            $admin?->id,
            'FALLBACK_ROUTE_CREATED',
            'FallbackRoute',
            (string) $route->id,
            null,
            $route->toArray()
        );

        return redirect()->route('admin.routing.index')->with('success', 'Fallback route created successfully.');
    }

    public function edit(int $id)
    {
        $route = FallbackRoute::with([
            'destinationNetwork',
            'destinationToken',
            'intermediateAsset',
            'firstLegProvider',
        ])->findOrFail($id);

        $networks = BlockchainNetwork::where('is_enabled', true)->orderBy('display_name')->get();
        $assets = CryptoAsset::with('network')->where('is_enabled', true)->orderBy('symbol')->get();
        $providers = SwapProvider::where('is_enabled', true)->orderByDesc('priority')->get();
        $adapters = $this->adapterRegistry->all();

        return view('admin.routing.edit', [
            'route' => $route,
            'networks' => $networks,
            'assets' => $assets,
            'providers' => $providers,
            'adapters' => $adapters,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $route = FallbackRoute::findOrFail($id);
        $admin = $request->attributes->get('current_admin');

        $validated = $request->validate([
            'destination_network_id' => ['required', 'exists:blockchain_networks,id'],
            'destination_token_id' => ['nullable', 'exists:crypto_assets,id'],
            'intermediate_asset_id' => ['required', 'exists:crypto_assets,id'],
            'first_leg_provider_id' => ['required', 'exists:swap_providers,id'],
            'second_leg_adapter_key' => ['required', 'string', 'max:64'],
            'priority' => ['required', 'integer', 'min:1', 'max:1000'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'max_slippage_bps' => ['required', 'integer', 'min:10', 'max:5000'],
            'min_liquidity_usd' => ['nullable', 'numeric', 'min:0'],
            'required_wallet_type' => ['nullable', 'string', 'max:64'],
            'account_requirements_note' => ['nullable', 'string', 'max:255'],
            'is_enabled' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $old = $route->toArray();
        $route->update($validated);

        Cache::forget(CatalogSyncService::CACHE_KEY_NETWORKS);

        AdminAuditLog::log(
            $admin?->id,
            'FALLBACK_ROUTE_UPDATED',
            'FallbackRoute',
            (string) $route->id,
            $old,
            $route->fresh()->toArray()
        );

        return redirect()->route('admin.routing.index')->with('success', 'Fallback route updated successfully.');
    }

    public function toggle(Request $request, int $id)
    {
        $route = FallbackRoute::findOrFail($id);
        $admin = $request->attributes->get('current_admin');

        $old = $route->toArray();
        $route->is_enabled = !$route->is_enabled;
        $route->save();

        Cache::forget(CatalogSyncService::CACHE_KEY_NETWORKS);

        AdminAuditLog::log(
            $admin?->id,
            'FALLBACK_ROUTE_TOGGLED',
            'FallbackRoute',
            (string) $route->id,
            $old,
            $route->fresh()->toArray()
        );

        $state = $route->is_enabled ? 'enabled' : 'disabled';
        return redirect()->route('admin.routing.index')->with('success', "Fallback route #{$route->id} {$state}.");
    }

    public function destroy(Request $request, int $id)
    {
        $route = FallbackRoute::findOrFail($id);
        $admin = $request->attributes->get('current_admin');

        $old = $route->toArray();
        $route->delete();

        Cache::forget(CatalogSyncService::CACHE_KEY_NETWORKS);

        AdminAuditLog::log(
            $admin?->id,
            'FALLBACK_ROUTE_DELETED',
            'FallbackRoute',
            (string) $id,
            $old,
            null
        );

        return redirect()->route('admin.routing.index')->with('success', 'Fallback route removed.');
    }
}

