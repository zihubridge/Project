<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SwapOrder;
use Illuminate\View\View;

class SwapPageController extends Controller
{
    public function index(): View
    {
        $recentSwaps = SwapOrder::with(['sourceNetwork', 'destinationNetwork', 'sourceAsset', 'destinationAsset'])
            ->where('status', 'COMPLETED')
            ->orderByDesc('completed_at')
            ->limit(6)
            ->get();

        return view('pages.swap', [
            'recentSwaps' => $recentSwaps,
        ]);
    }

    public function show(string $uuid): View
    {
        $order = SwapOrder::with([
            'provider',
            'sourceNetwork',
            'destinationNetwork',
            'sourceAsset',
            'destinationAsset',
            'steps',
            'histories',
        ])->where('swap_uuid', $uuid)->firstOrFail();

        return view('pages.swap-status', [
            'order' => $order,
        ]);
    }

    public function history(): View
    {
        return view('pages.history');
    }
}

