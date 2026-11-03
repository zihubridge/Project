@extends('admin.layout')

@section('title', 'Platform Operations Dashboard')
@section('header-tag', 'Intelligence & Metrics')
@section('header-title', 'Platform Overview')

@section('content')
<div class="space-y-8 max-w-7xl mx-auto">

    <!-- Top Action Bar & Date Range Filter -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-6 border-b border-[#E8E5DF]">
        <div>
            <h1 class="text-2xl font-heading font-semibold text-[#121417] tracking-tight">
                Operations & Financial Ledger
            </h1>
            <p class="text-xs text-[#52565E] mt-1">
                Real-time metrics, execution performance, fee realization, and token listing queue.
            </p>
        </div>

        <!-- Filter Pill Strip -->
        <div class="flex flex-wrap items-center gap-1.5 bg-white border border-[#E8E5DF] rounded-lg p-1 text-xs font-heading font-medium">
            @foreach([
                'today' => 'Today',
                'yesterday' => 'Yesterday',
                '7d' => '7D',
                '30d' => '30D',
                'this_month' => 'This Month',
                'last_month' => 'Last Month',
            ] as $key => $label)
                <a href="{{ route('admin.dashboard', ['range' => $key]) }}"
                    class="px-3 py-1.5 rounded-md transition {{ $filter === $key ? 'bg-[#121417] text-white' : 'text-[#52565E] hover:text-[#121417] hover:bg-[#FAF8F5]' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Metric Cards: Section 1 - Swaps & Execution -->
    <div>
        <span class="text-xs font-heading font-semibold uppercase tracking-wider text-[#6B7280] block mb-3">
            Execution Performance ({{ $filterLabel }})
        </span>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border border-[#E8E5DF] rounded-xl p-5 shadow-xs">
                <span class="text-xs font-heading font-medium text-[#6B7280] block">Total Swaps</span>
                <div class="text-2xl font-heading font-semibold text-[#121417] mt-1.5 font-mono">
                    {{ number_format($metrics['total_swaps']) }}
                </div>
                <div class="text-[11px] text-[#6B7280] mt-2 flex items-center justify-between border-t border-[#F0ECE1] pt-2">
                    <span>Initiated Orders</span>
                    <span class="font-mono text-[#121417]">100%</span>
                </div>
            </div>

            <div class="bg-white border border-[#E8E5DF] rounded-xl p-5 shadow-xs">
                <span class="text-xs font-heading font-medium text-[#6B7280] block">Successful Swaps</span>
                <div class="text-2xl font-heading font-semibold text-[#0F6B48] mt-1.5 font-mono">
                    {{ number_format($metrics['successful_swaps']) }}
                </div>
                <div class="text-[11px] text-[#6B7280] mt-2 flex items-center justify-between border-t border-[#F0ECE1] pt-2">
                    <span>Success Rate</span>
                    <span class="font-mono text-[#0F6B48] font-semibold">
                        {{ $metrics['total_swaps'] > 0 ? round(($metrics['successful_swaps'] / $metrics['total_swaps']) * 100, 1) : 0 }}%
                    </span>
                </div>
            </div>

            <div class="bg-white border border-[#E8E5DF] rounded-xl p-5 shadow-xs">
                <span class="text-xs font-heading font-medium text-[#6B7280] block">Pending / In-Flight</span>
                <div class="text-2xl font-heading font-semibold text-amber-700 mt-1.5 font-mono">
                    {{ number_format($metrics['pending_swaps']) }}
                </div>
                <div class="text-[11px] text-[#6B7280] mt-2 flex items-center justify-between border-t border-[#F0ECE1] pt-2">
                    <span>Awaiting Deposit / Solver</span>
                    <span class="font-mono text-amber-700">{{ $metrics['pending_swaps'] }}</span>
                </div>
            </div>

            <div class="bg-white border border-[#E8E5DF] rounded-xl p-5 shadow-xs">
                <span class="text-xs font-heading font-medium text-[#6B7280] block">Failed / Refunded</span>
                <div class="text-2xl font-heading font-semibold text-rose-700 mt-1.5 font-mono">
                    {{ number_format($metrics['failed_swaps']) }}
                </div>
                <div class="text-[11px] text-[#6B7280] mt-2 flex items-center justify-between border-t border-[#F0ECE1] pt-2">
                    <span>Protected by Origin Refund</span>
                    <span class="font-mono text-rose-700">{{ $metrics['failed_swaps'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Metric Cards: Section 2 - Revenue & Financials -->
    <div>
        <span class="text-xs font-heading font-semibold uppercase tracking-wider text-[#6B7280] block mb-3">
            Revenue & Capital Flow ({{ $filterLabel }})
        </span>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border border-[#E8E5DF] rounded-xl p-5 shadow-xs">
                <span class="text-xs font-heading font-medium text-[#6B7280] block">Total Swap Volume</span>
                <div class="text-2xl font-heading font-semibold text-[#121417] mt-1.5 font-mono">
                    ${{ number_format($metrics['total_volume_usd'], 2) }}
                </div>
                <div class="text-[11px] text-[#6B7280] mt-2 border-t border-[#F0ECE1] pt-2">
                    Settled volume across supported solvers
                </div>
            </div>

            <div class="bg-white border border-[#E8E5DF] rounded-xl p-5 shadow-xs">
                <span class="text-xs font-heading font-medium text-[#6B7280] block">Total Platform Fees</span>
                <div class="text-2xl font-heading font-semibold text-[#0F6B48] mt-1.5 font-mono">
                    ${{ number_format($metrics['total_fees_earned_usd'], 2) }}
                </div>
                <div class="text-[11px] text-[#6B7280] mt-2 border-t border-[#F0ECE1] pt-2 flex justify-between">
                    <span>1.0% Protocol Baseline</span>
                    <span class="font-mono text-[#121417] font-medium">100 BPS</span>
                </div>
            </div>

            <div class="bg-white border border-[#E8E5DF] rounded-xl p-5 shadow-xs">
                <span class="text-xs font-heading font-medium text-[#6B7280] block">Fees Confirmed Collected</span>
                <div class="text-2xl font-heading font-semibold text-[#0F6B48] mt-1.5 font-mono">
                    ${{ number_format($metrics['fees_collected_usd'], 2) }}
                </div>
                <div class="text-[11px] text-[#6B7280] mt-2 border-t border-[#F0ECE1] pt-2 flex justify-between">
                    <span>Pending Settlement:</span>
                    <span class="font-mono text-amber-700">${{ number_format($metrics['fees_pending_usd'], 2) }}</span>
                </div>
            </div>

            <div class="bg-white border border-[#E8E5DF] rounded-xl p-5 shadow-xs">
                <span class="text-xs font-heading font-medium text-[#6B7280] block">Token Listing Revenue</span>
                <div class="text-2xl font-heading font-semibold text-[#121417] mt-1.5 font-mono">
                    ${{ number_format($metrics['total_listing_revenue_usd'], 2) }}
                </div>
                <div class="text-[11px] text-[#6B7280] mt-2 border-t border-[#F0ECE1] pt-2 flex justify-between">
                    <span>Pending Reviews:</span>
                    <span class="font-mono font-medium text-[#121417]">{{ $metrics['pending_listings_count'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Activity Timeline Chart (8 cols) -->
        <div class="lg:col-span-8 bg-white border border-[#E8E5DF] rounded-xl p-6 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-heading font-semibold text-[#121417]">Execution Volume & Cadence</h2>
                        <p class="text-xs text-[#52565E]">Daily swap count over selected window</p>
                    </div>
                    <span class="text-xs font-mono px-2.5 py-1 rounded bg-[#FAF8F5] border border-[#E8E5DF] text-[#121417]">
                        {{ count($chartData['daily']) }} Data Points
                    </span>
                </div>

                <!-- SVG Trend Chart -->
                <div class="w-full h-48 pt-4">
                    @php
                        $maxCount = max(1, ...array_column($chartData['daily'], 'count'));
                        $points = $chartData['daily'];
                        $totalPoints = count($points);
                    @endphp

                    <div class="w-full h-36 flex items-end gap-1 border-b border-[#E8E5DF]">
                        @foreach($points as $idx => $p)
                            @php
                                $heightPct = $maxCount > 0 ? max(4, round(($p['count'] / $maxCount) * 100)) : 4;
                            @endphp
                            <div class="flex-1 flex flex-col items-center group relative h-full justify-end">
                                <div class="w-full rounded-t transition-all {{ $p['count'] > 0 ? 'bg-[#121417] group-hover:bg-[#0F6B48]' : 'bg-[#F0ECE1]' }}"
                                     style="height: {{ $heightPct }}%;"></div>
                                <!-- Tooltip -->
                                <div class="opacity-0 group-hover:opacity-100 transition absolute bottom-full mb-1 z-10 pointer-events-none bg-[#121417] text-white text-[10px] font-mono py-1 px-2 rounded whitespace-nowrap">
                                    {{ $p['label'] }}: {{ $p['count'] }} swaps
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- X-Axis Labels -->
                    <div class="flex justify-between items-center text-[10px] text-[#6B7280] font-mono pt-2">
                        <span>{{ $points[0]['label'] ?? '' }}</span>
                        <span>Midpoint</span>
                        <span>{{ end($points)['label'] ?? '' }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-[#F0ECE1] flex items-center justify-between text-xs text-[#52565E]">
                <span>Aggregated live from authoritative swap order records</span>
                <a href="{{ route('admin.swaps.index') }}" class="font-heading font-semibold text-[#121417] hover:underline">
                    View Full Swap Ledger &rarr;
                </a>
            </div>
        </div>

        <!-- Breakdown Panels (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Chains Breakdown -->
            <div class="bg-white border border-[#E8E5DF] rounded-xl p-5 shadow-xs">
                <h3 class="text-sm font-heading font-semibold text-[#121417] mb-3">Top Source Chains</h3>
                <div class="space-y-3">
                    @forelse($chartData['revenue_by_chain'] as $chain)
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium text-[#121417]">{{ $chain->display_name }}</span>
                            <span class="font-mono text-[#6B7280] bg-[#FAF8F5] px-2 py-0.5 rounded border border-[#E8E5DF]">
                                {{ $chain->count }} swaps
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-[#8A8F99]">No swap activity in this window.</p>
                    @endforelse
                </div>
            </div>

            <!-- Top Swapped Assets -->
            <div class="bg-white border border-[#E8E5DF] rounded-xl p-5 shadow-xs">
                <h3 class="text-sm font-heading font-semibold text-[#121417] mb-3">Frequently Swapped Assets</h3>
                <div class="space-y-3">
                    @forelse($chartData['top_assets'] as $asset)
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-[#121417] font-mono">{{ $asset->symbol }}</span>
                            <span class="font-mono text-[#0F6B48] bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                {{ $asset->swap_count }} orders
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-[#8A8F99]">No completed assets in this window.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

    <!-- Recent Swaps Ledger Table -->
    <div class="bg-white border border-[#E8E5DF] rounded-xl overflow-hidden shadow-xs">
        <div class="p-5 border-b border-[#E8E5DF] flex items-center justify-between">
            <div>
                <h2 class="text-base font-heading font-semibold text-[#121417]">Recent Swap Orders</h2>
                <p class="text-xs text-[#52565E]">Most recent cross-chain orders initiated on ZihuBridge</p>
            </div>
            <a href="{{ route('admin.swaps.index') }}"
                class="text-xs font-heading font-semibold px-3 py-1.5 rounded-lg bg-[#FAF8F5] hover:bg-[#121417] hover:text-white border border-[#DDD9D0] text-[#121417] transition">
                View All Swaps
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F7F5F0] text-[#52565E] font-heading font-medium uppercase text-[11px] tracking-wider border-b border-[#E8E5DF]">
                    <tr>
                        <th class="py-3 px-4 font-semibold">Swap UUID</th>
                        <th class="py-3 px-4 font-semibold">Source Route</th>
                        <th class="py-3 px-4 font-semibold">Destination Route</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 font-semibold">Timestamp</th>
                        <th class="py-3 px-4 text-right font-semibold">Inspect</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E8E5DF]">
                    @forelse($recentSwaps as $swap)
                        <tr class="hover:bg-[#FAF8F5] transition">
                            <td class="py-3.5 px-4 font-mono font-medium text-[#121417]">
                                {{ substr($swap->swap_uuid, 0, 13) }}...
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-semibold text-[#121417]">{{ $swap->amount_in }} {{ $swap->sourceAsset->symbol }}</span>
                                <span class="block text-[11px] text-[#6B7280]">{{ $swap->sourceNetwork->display_name }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-semibold text-[#0F6B48]">
                                    {{ $swap->actual_amount_out ?? $swap->expected_amount_out }} {{ $swap->destinationAsset->symbol }}
                                </span>
                                <span class="block text-[11px] text-[#6B7280]">{{ $swap->destinationNetwork->display_name }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-block px-2.5 py-0.5 rounded text-[11px] font-heading font-medium
                                    {{ $swap->status === 'COMPLETED' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($swap->status === 'FAILED' ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-[#F0ECE1] text-[#2D3139] border border-[#DDD9D0]') }}">
                                    {{ $swap->status }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[#6B7280]">
                                {{ $swap->created_at->format('M d, H:i') }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('admin.swaps.show', $swap->swap_uuid) }}"
                                    class="inline-flex items-center text-xs px-2.5 py-1 rounded-md bg-[#121417] text-white hover:bg-black font-heading font-medium transition">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-[#8A8F99] font-sans">
                                No swap records in database yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
