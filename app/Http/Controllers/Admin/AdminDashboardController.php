<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CryptoAsset;
use App\Models\PlatformRevenueRecord;
use App\Models\SwapOrder;
use App\Models\TokenListingRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('range', '30d');
        $customFrom = $request->query('from');
        $customTo = $request->query('to');

        [$startDate, $endDate, $filterLabel] = $this->resolveDateRange($filter, $customFrom, $customTo);

        // Base Query scoped to date range
        $swapsBaseQuery = SwapOrder::query()
            ->when($startDate, fn($q) => $q->where('swap_orders.created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('swap_orders.created_at', '<=', $endDate));

        // 1. KPI Counts
        $totalSwaps = (clone $swapsBaseQuery)->count();
        $successfulSwaps = (clone $swapsBaseQuery)->where('swap_orders.status', 'COMPLETED')->count();
        $failedSwaps = (clone $swapsBaseQuery)->whereIn('swap_orders.status', ['FAILED', 'REFUNDED', 'EXPIRED'])->count();
        $pendingSwaps = (clone $swapsBaseQuery)->whereIn('swap_orders.status', ['PENDING_DEPOSIT', 'DEPOSIT_DETECTED', 'PROCESSING'])->count();

        // 2. Volume & Revenue
        $completedSwapsQuery = (clone $swapsBaseQuery)->where('swap_orders.status', 'COMPLETED');
        
        // Volume USD estimated from quote data joined with orders
        $totalVolumeUsd = (float) (clone $completedSwapsQuery)
            ->join('swap_quotes', 'swap_orders.swap_quote_id', '=', 'swap_quotes.id')
            ->sum(DB::raw('COALESCE(swap_quotes.amount_in_usd, 0)'));

        // Revenue records
        $revQuery = PlatformRevenueRecord::query()
            ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('created_at', '<=', $endDate));

        $feesCollectedUsd = (float) (clone $revQuery)->where('status', PlatformRevenueRecord::STATUS_COLLECTED)->sum('fee_asset_usd');
        $feesPendingUsd = (float) (clone $revQuery)->where('status', PlatformRevenueRecord::STATUS_PENDING)->sum('fee_asset_usd');
        $totalFeesEarnedUsd = $feesCollectedUsd + $feesPendingUsd;

        // 3. Token & Listing Counts
        $listedTokensCount = CryptoAsset::where('is_enabled', true)->count();
        $pendingListingsCount = TokenListingRequest::whereIn('review_status', [
            TokenListingRequest::STATUS_UNDER_REVIEW,
            TokenListingRequest::STATUS_PENDING_PAYMENT,
            TokenListingRequest::STATUS_PAID,
        ])->count();
        $totalListingRevenueUsd = (float) TokenListingRequest::where('payment_status', TokenListingRequest::STATUS_PAID)
            ->sum('listing_fee_usd');

        // 4. Time Series Chart Data (Daily Buckets)
        $dailyBuckets = $this->buildDailyTimeSeries($startDate, $endDate);

        // 5. Chain & Asset Breakdown
        $revenueByChain = (clone $completedSwapsQuery)
            ->join('blockchain_networks', 'swap_orders.source_network_id', '=', 'blockchain_networks.id')
            ->select('blockchain_networks.display_name', DB::raw('count(*) as count'))
            ->groupBy('blockchain_networks.display_name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $topAssets = (clone $completedSwapsQuery)
            ->join('crypto_assets', 'swap_orders.source_asset_id', '=', 'crypto_assets.id')
            ->select('crypto_assets.symbol', DB::raw('count(*) as swap_count'))
            ->groupBy('crypto_assets.symbol')
            ->orderByDesc('swap_count')
            ->limit(5)
            ->get();

        // 6. Recent Swaps (Last 10)
        $recentSwaps = SwapOrder::with(['sourceNetwork', 'destinationNetwork', 'sourceAsset', 'destinationAsset'])
            ->latest('id')
            ->limit(10)
            ->get();

        return view('admin.dashboard', [
            'filter' => $filter,
            'filterLabel' => $filterLabel,
            'startDate' => $startDate?->format('Y-m-d'),
            'endDate' => $endDate?->format('Y-m-d'),
            'metrics' => [
                'total_swaps' => $totalSwaps,
                'successful_swaps' => $successfulSwaps,
                'failed_swaps' => $failedSwaps,
                'pending_swaps' => $pendingSwaps,
                'total_volume_usd' => $totalVolumeUsd,
                'total_fees_earned_usd' => $totalFeesEarnedUsd,
                'fees_collected_usd' => $feesCollectedUsd,
                'fees_pending_usd' => $feesPendingUsd,
                'listed_tokens_count' => $listedTokensCount,
                'pending_listings_count' => $pendingListingsCount,
                'total_listing_revenue_usd' => $totalListingRevenueUsd,
            ],
            'chartData' => [
                'daily' => $dailyBuckets,
                'revenue_by_chain' => $revenueByChain,
                'top_assets' => $topAssets,
            ],
            'recentSwaps' => $recentSwaps,
        ]);
    }

    private function resolveDateRange(string $filter, ?string $from, ?string $to): array
    {
        $now = Carbon::now('UTC');

        return match ($filter) {
            'today' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                'Today',
            ],
            'yesterday' => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
                'Yesterday',
            ],
            '7d' => [
                $now->copy()->subDays(6)->startOfDay(),
                $now->copy()->endOfDay(),
                'Last 7 Days',
            ],
            'this_month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                'This Month',
            ],
            'last_month' => [
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth(),
                'Last Month',
            ],
            'custom' => [
                $from ? Carbon::parse($from)->startOfDay() : null,
                $to ? Carbon::parse($to)->endOfDay() : $now->copy()->endOfDay(),
                'Custom Range',
            ],
            default => [ // '30d'
                $now->copy()->subDays(29)->startOfDay(),
                $now->copy()->endOfDay(),
                'Last 30 Days',
            ],
        };
    }

    private function buildDailyTimeSeries(?Carbon $start, ?Carbon $end): array
    {
        $effectiveStart = $start ? $start->copy() : Carbon::now('UTC')->subDays(29)->startOfDay();
        $effectiveEnd = $end ? $end->copy() : Carbon::now('UTC')->endOfDay();

        // Limit days to max 60 to prevent large payloads
        if ($effectiveStart->diffInDays($effectiveEnd) > 60) {
            $effectiveStart = $effectiveEnd->copy()->subDays(60);
        }

        // Fetch counts grouped by date
        $counts = SwapOrder::select(
            DB::raw('DATE(created_at) as date_key'),
            DB::raw('count(*) as total'),
            DB::raw("sum(case when status = 'COMPLETED' then 1 else 0 end) as completed"),
            DB::raw("sum(case when status in ('FAILED', 'REFUNDED', 'EXPIRED') then 1 else 0 end) as failed")
        )
            ->where('created_at', '>=', $effectiveStart)
            ->where('created_at', '<=', $effectiveEnd)
            ->groupBy('date_key')
            ->pluck('total', 'date_key')
            ->toArray();

        $days = [];
        $cursor = $effectiveStart->copy();
        while ($cursor->lte($effectiveEnd)) {
            $key = $cursor->format('Y-m-d');
            $label = $cursor->format('M d');
            $days[] = [
                'date' => $key,
                'label' => $label,
                'count' => (int) ($counts[$key] ?? 0),
            ];
            $cursor->addDay();
        }

        return $days;
    }
}
