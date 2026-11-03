<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\BlockchainNetwork;
use Illuminate\Http\Request;

class AdminBlockchainController extends Controller
{
    public function index()
    {
        $networks = BlockchainNetwork::withCount('cryptoAssets')->orderBy('display_name')->paginate(20);

        return view('admin.blockchains.index', [
            'networks' => $networks,
        ]);
    }

    public function edit(int $id)
    {
        $network = BlockchainNetwork::findOrFail($id);

        return view('admin.blockchains.edit', [
            'network' => $network,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $network = BlockchainNetwork::findOrFail($id);
        $admin = $request->attributes->get('current_admin');

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:100'],
            'chain_family' => ['required', 'string', 'max:32'],
            'native_symbol' => ['nullable', 'string', 'max:32'],
            'evm_chain_id' => ['nullable', 'string', 'max:32'],
            'requires_memo_or_tag' => ['required', 'boolean'],
            'address_regex' => ['nullable', 'string', 'max:191'],
            'explorer_tx_url_pattern' => ['nullable', 'string', 'max:191'],
            'explorer_address_url_pattern' => ['nullable', 'string', 'max:191'],
            'is_enabled' => ['required', 'boolean'],
        ]);

        $old = $network->toArray();
        $network->update($validated);

        AdminAuditLog::log(
            $admin?->id,
            'BLOCKCHAIN_UPDATED',
            'BlockchainNetwork',
            (string) $network->id,
            $old,
            $network->fresh()->toArray()
        );

        return redirect()->route('admin.blockchains.index')->with('success', "Blockchain '{$network->display_name}' updated successfully.");
    }
}
