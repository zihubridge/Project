<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\BlockchainNetwork;
use App\Models\CryptoAsset;
use Illuminate\Http\Request;

class AdminTokenController extends Controller
{
    public function index(Request $request)
    {
        $query = CryptoAsset::with(['blockchainNetwork', 'providerSupportedAssets']);

        if ($request->filled('network_id')) {
            $query->where('blockchain_network_id', $request->query('network_id'));
        }

        if ($request->filled('symbol')) {
            $query->where('symbol', 'like', '%' . trim($request->query('symbol')) . '%');
        }

        if ($request->filled('status')) {
            $query->where('is_enabled', $request->query('status') === '1');
        }

        $tokens = $query->orderBy('symbol')->paginate(25)->withQueryString();
        $networks = BlockchainNetwork::where('is_enabled', true)->orderBy('display_name')->get();

        return view('admin.tokens.index', [
            'tokens' => $tokens,
            'networks' => $networks,
            'filters' => $request->all(),
        ]);
    }

    public function create()
    {
        $networks = BlockchainNetwork::where('is_enabled', true)->orderBy('display_name')->get();

        return view('admin.tokens.create', [
            'networks' => $networks,
        ]);
    }

    public function store(Request $request)
    {
        $admin = $request->attributes->get('current_admin');

        $validated = $request->validate([
            'blockchain_network_id' => ['required', 'exists:blockchain_networks,id'],
            'symbol' => ['required', 'string', 'max:64'],
            'display_name' => ['required', 'string', 'max:128'],
            'is_native' => ['required', 'boolean'],
            'contract_or_issuer' => ['nullable', 'string', 'max:191'],
            'decimals' => ['required', 'integer', 'min:0', 'max:36'],
            'current_price_usd' => ['nullable', 'numeric', 'min:0'],
            'logo_url' => ['nullable', 'url', 'max:191'],
            'is_enabled' => ['required', 'boolean'],
        ]);

        $priceUsd = !empty($validated['current_price_usd']) ? (float) $validated['current_price_usd'] : null;

        $token = CryptoAsset::create([
            'blockchain_network_id' => $validated['blockchain_network_id'],
            'symbol' => strtoupper(trim($validated['symbol'])),
            'display_name' => trim($validated['display_name']),
            'is_native' => (bool) $validated['is_native'],
            'contract_or_issuer' => !empty($validated['contract_or_issuer']) ? trim($validated['contract_or_issuer']) : null,
            'decimals' => $validated['decimals'],
            'current_price_usd' => $priceUsd,
            'price_updated_at' => $priceUsd !== null ? now() : null,
            'logo_url' => !empty($validated['logo_url']) ? trim($validated['logo_url']) : null,
            'is_enabled' => (bool) $validated['is_enabled'],
        ]);

        AdminAuditLog::log(
            $admin?->id,
            'TOKEN_CREATED',
            'CryptoAsset',
            (string) $token->id,
            null,
            $token->toArray()
        );

        return redirect()->route('admin.tokens.index')->with('success', "Token '{$token->symbol}' registered successfully.");
    }

    public function edit(int $id)
    {
        $token = CryptoAsset::with('blockchainNetwork')->findOrFail($id);
        $networks = BlockchainNetwork::where('is_enabled', true)->orderBy('display_name')->get();

        return view('admin.tokens.edit', [
            'token' => $token,
            'networks' => $networks,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $token = CryptoAsset::findOrFail($id);
        $admin = $request->attributes->get('current_admin');

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:128'],
            'is_native' => ['required', 'boolean'],
            'contract_or_issuer' => ['nullable', 'string', 'max:191'],
            'decimals' => ['required', 'integer', 'min:0', 'max:36'],
            'current_price_usd' => ['nullable', 'numeric', 'min:0'],
            'logo_url' => ['nullable', 'url', 'max:191'],
            'is_enabled' => ['required', 'boolean'],
        ]);

        $old = $token->toArray();
        $hasPrice = array_key_exists('current_price_usd', $validated) && $validated['current_price_usd'] !== null && $validated['current_price_usd'] !== '';
        $priceUsd = $hasPrice ? (float) $validated['current_price_usd'] : $token->current_price_usd;

        $token->update([
            'display_name' => trim($validated['display_name']),
            'is_native' => (bool) $validated['is_native'],
            'contract_or_issuer' => !empty($validated['contract_or_issuer']) ? trim($validated['contract_or_issuer']) : null,
            'decimals' => $validated['decimals'],
            'current_price_usd' => $priceUsd,
            'price_updated_at' => $hasPrice ? now() : $token->price_updated_at,
            'logo_url' => !empty($validated['logo_url']) ? trim($validated['logo_url']) : $token->logo_url,
            'is_enabled' => (bool) $validated['is_enabled'],
        ]);

        AdminAuditLog::log(
            $admin?->id,
            'TOKEN_UPDATED',
            'CryptoAsset',
            (string) $token->id,
            $old,
            $token->fresh()->toArray()
        );

        return redirect()->route('admin.tokens.index')->with('success', "Token '{$token->symbol}' updated successfully.");
    }
}
