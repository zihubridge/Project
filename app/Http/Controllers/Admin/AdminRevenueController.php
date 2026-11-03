<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformRevenueRecord;
use App\Models\SwapOrder;
use App\Models\SwapProvider;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminRevenueController extends Controller
{
    public function index(Request $request)
    {
        $query = PlatformRevenueRecord::with(['swapOrder', 'swapProvider']);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('fee_asset')) {
            $query->where('fee_asset_symbol', strtoupper(trim($request->query('fee_asset'))));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->query('date_from') . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->query('date_to') . ' 23:59:59');
        }

        $records = $query->latest('id')->paginate(20)->withQueryString();

        // Totals
        $totalConfirmedUsd = (float) PlatformRevenueRecord::where('status', PlatformRevenueRecord::STATUS_COLLECTED)->sum('fee_asset_usd');
        $totalPendingUsd = (float) PlatformRevenueRecord::where('status', PlatformRevenueRecord::STATUS_PENDING)->sum('fee_asset_usd');
        $totalFailedUsd = (float) PlatformRevenueRecord::where('status', PlatformRevenueRecord::STATUS_FAILED)->sum('fee_asset_usd');

        return view('admin.revenue.index', [
            'records' => $records,
            'filters' => $request->all(),
            'totals' => [
                'confirmed_usd' => $totalConfirmedUsd,
                'pending_usd' => $totalPendingUsd,
                'failed_usd' => $totalFailedUsd,
                'total_expected_usd' => $totalConfirmedUsd + $totalPendingUsd,
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $fileName = 'zihubridge_revenue_ledger_' . date('Ymd_His') . '.csv';

        $records = PlatformRevenueRecord::with(['swapOrder', 'swapProvider'])
            ->latest('id')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Record ID',
                'Swap UUID',
                'Provider',
                'Fee Rate BPS',
                'Expected Fee Amount',
                'Confirmed Fee Amount',
                'Fee Asset',
                'Fee USD Equivalent',
                'Fee Recipient',
                'Status',
                'Fee Transaction Hash',
                'Collected Timestamp',
                'Created At',
            ]);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->id,
                    $r->swapOrder?->swap_uuid ?? 'N/A',
                    $r->swapProvider?->display_name ?? 'NEAR Intents',
                    $r->fee_bps,
                    $r->expected_fee_amount,
                    $r->confirmed_fee_amount,
                    $r->fee_asset_symbol,
                    $r->fee_asset_usd,
                    $r->fee_recipient,
                    $r->status,
                    $r->fee_tx_hash,
                    $r->collected_at?->toIso8601String(),
                    $r->created_at->toIso8601String(),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
