<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockchainNetwork;
use App\Models\CryptoAsset;
use App\Models\SwapOrder;
use App\Models\SwapProvider;
use Illuminate\Http\Request;

class AdminSwapController extends Controller
{
    public function index(Request $request)
    {
        $query = SwapOrder::with([
            'sourceNetwork',
            'destinationNetwork',
            'sourceAsset',
            'destinationAsset',
            'swapProvider',
            'swapQuote',
        ]);

        if ($request->filled('uuid')) {
            $query->where('swap_uuid', 'like', '%' . trim($request->query('uuid')) . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('provider_id')) {
            $query->where('swap_provider_id', $request->query('provider_id'));
        }

        if ($request->filled('source_network_id')) {
            $query->where('source_network_id', $request->query('source_network_id'));
        }

        if ($request->filled('destination_network_id')) {
            $query->where('destination_network_id', $request->query('destination_network_id'));
        }

        if ($request->filled('wallet')) {
            $wallet = trim($request->query('wallet'));
            $query->where(function ($q) use ($wallet) {
                $q->where('connected_wallet_address', 'like', "%{$wallet}%")
                    ->orWhere('recipient_address', 'like', "%{$wallet}%")
                    ->orWhere('refund_address', 'like', "%{$wallet}%")
                    ->orWhere('deposit_address', 'like', "%{$wallet}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->query('date_from') . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->query('date_to') . ' 23:59:59');
        }

        $swaps = $query->latest('id')->paginate(20)->withQueryString();

        $networks = BlockchainNetwork::where('is_enabled', true)->orderBy('display_name')->get();
        $providers = SwapProvider::where('is_enabled', true)->get();

        return view('admin.swaps.index', [
            'swaps' => $swaps,
            'networks' => $networks,
            'providers' => $providers,
            'filters' => $request->all(),
        ]);
    }

    public function show(string $uuid)
    {
        $order = SwapOrder::with([
            'sourceNetwork',
            'destinationNetwork',
            'sourceAsset',
            'destinationAsset',
            'swapProvider',
            'swapQuote',
            'steps' => fn($q) => $q->orderBy('step_number'),
            'statusHistories' => fn($q) => $q->latest('created_at'),
        ])->where('swap_uuid', $uuid)->firstOrFail();

        return view('admin.swaps.show', [
            'order' => $order,
        ]);
    }
}
