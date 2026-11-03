@extends('admin.layout')

@section('title', 'Platform Revenue Accounting')
@section('header-tag', 'Financial Ledger')
@section('header-title', 'Revenue & Realized Fees')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#E8E5DF]">
        <div>
            <h1 class="text-2xl font-heading font-semibold text-[#121417] tracking-tight">
                Platform Revenue Accounting
            </h1>
            <p class="text-xs text-[#52565E] mt-1">
                Realized fee collections, pending allocations, and provider reconciliation ledger.
            </p>
        </div>

        <a href="{{ route('admin.revenue.export') }}"
            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-[#121417] hover:bg-black text-white text-xs font-heading font-medium transition cursor-pointer">
            <ion-icon name="download-outline" class="text-sm"></ion-icon>
            <span>Export Revenue CSV</span>
        </a>
    </div>

    <!-- Revenue KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-[#E8E5DF] rounded-xl p-5 shadow-xs">
            <span class="text-xs font-heading font-medium text-[#6B7280] block">Total Realized Revenue</span>
            <div class="text-2xl font-heading font-semibold text-[#0F6B48] mt-1.5 font-mono">
                ${{ number_format($totals['confirmed_usd'], 2) }}
            </div>
            <div class="text-[11px] text-[#6B7280] mt-2 border-t border-[#F0ECE1] pt-2">
                Confirmed delivered to protocol treasury
            </div>
        </div>

        <div class="bg-white border border-[#E8E5DF] rounded-xl p-5 shadow-xs">
            <span class="text-xs font-heading font-medium text-[#6B7280] block">Pending Settlement</span>
            <div class="text-2xl font-heading font-semibold text-amber-700 mt-1.5 font-mono">
                ${{ number_format($totals['pending_usd'], 2) }}
            </div>
            <div class="text-[11px] text-[#6B7280] mt-2 border-t border-[#F0ECE1] pt-2">
                Swaps in-flight or awaiting solver fee sweep
            </div>
        </div>

        <div class="bg-white border border-[#E8E5DF] rounded-xl p-5 shadow-xs">
            <span class="text-xs font-heading font-medium text-[#6B7280] block">Total Expected Value</span>
            <div class="text-2xl font-heading font-semibold text-[#121417] mt-1.5 font-mono">
                ${{ number_format($totals['total_expected_usd'], 2) }}
            </div>
            <div class="text-[11px] text-[#6B7280] mt-2 border-t border-[#F0ECE1] pt-2">
                Total contracted platform fee share
            </div>
        </div>

        <div class="bg-white border border-[#E8E5DF] rounded-xl p-5 shadow-xs">
            <span class="text-xs font-heading font-medium text-[#6B7280] block">Failed Allocations</span>
            <div class="text-2xl font-heading font-semibold text-rose-700 mt-1.5 font-mono">
                ${{ number_format($totals['failed_usd'], 2) }}
            </div>
            <div class="text-[11px] text-[#6B7280] mt-2 border-t border-[#F0ECE1] pt-2">
                Refunded or dropped solver fees
            </div>
        </div>
    </div>

    <!-- Filters Form -->
    <form method="GET" action="{{ route('admin.revenue.index') }}" class="bg-white border border-[#E8E5DF] rounded-xl p-4 shadow-xs">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-heading font-semibold uppercase tracking-wider text-[#6B7280] mb-1">Status</label>
                <select name="status" class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-2.5 py-1.5 text-xs text-[#121417] focus:outline-none focus:border-[#121417]">
                    <option value="">All Revenue States</option>
                    @foreach(['COLLECTED', 'PENDING', 'FAILED', 'UNAVAILABLE', 'REFUNDED'] as $st)
                        <option value="{{ $st }}" {{ ($filters['status'] ?? '') === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-heading font-semibold uppercase tracking-wider text-[#6B7280] mb-1">Fee Asset</label>
                <input type="text" name="fee_asset" value="{{ $filters['fee_asset'] ?? '' }}" placeholder="e.g. USDT, ETH..."
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-2.5 py-1.5 text-xs font-mono text-[#121417] focus:outline-none focus:border-[#121417]" />
            </div>

            <div>
                <label class="block text-[11px] font-heading font-semibold uppercase tracking-wider text-[#6B7280] mb-1">From Date</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-2.5 py-1.5 text-xs font-mono text-[#121417] focus:outline-none focus:border-[#121417]" />
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                    class="flex-1 bg-[#121417] hover:bg-black text-white font-heading font-semibold py-1.5 px-3 rounded-lg text-xs transition cursor-pointer">
                    Apply Filter
                </button>
                <a href="{{ route('admin.revenue.index') }}"
                    class="bg-[#FAF8F5] hover:bg-[#EAE7E0] border border-[#DDD9D0] text-[#52565E] font-heading font-medium py-1.5 px-3 rounded-lg text-xs transition">
                    Reset
                </a>
            </div>
        </div>
    </form>

    <!-- Ledger Table -->
    <div class="bg-white border border-[#E8E5DF] rounded-xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F7F5F0] text-[#52565E] font-heading font-medium uppercase text-[11px] tracking-wider border-b border-[#E8E5DF]">
                    <tr>
                        <th class="py-3 px-4 font-semibold">Ledger ID</th>
                        <th class="py-3 px-4 font-semibold">Swap UUID</th>
                        <th class="py-3 px-4 font-semibold">Rate (BPS)</th>
                        <th class="py-3 px-4 font-semibold">Fee Amount</th>
                        <th class="py-3 px-4 font-semibold">USD Value</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 font-semibold">Collected Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E8E5DF]">
                    @forelse($records as $rec)
                        <tr class="hover:bg-[#FAF8F5] transition">
                            <td class="py-3.5 px-4 font-mono font-medium text-[#121417]">#{{ $rec->id }}</td>
                            <td class="py-3.5 px-4 font-mono text-[#121417]">
                                <a href="{{ route('admin.swaps.show', $rec->swapOrder->swap_uuid ?? '') }}" class="hover:underline">
                                    {{ substr($rec->swapOrder->swap_uuid ?? '', 0, 13) }}...
                                </a>
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                {{ $rec->fee_bps / 100 }}% ({{ $rec->fee_bps }} BPS)
                            </td>
                            <td class="py-3.5 px-4 font-mono font-semibold text-[#121417]">
                                {{ $rec->confirmed_fee_amount ?? $rec->expected_fee_amount }} {{ $rec->fee_asset_symbol }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[#0F6B48]">
                                ${{ number_format((float)$rec->fee_asset_usd, 2) }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-0.5 rounded text-[11px] font-heading font-medium
                                    {{ $rec->status === 'COLLECTED' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($rec->status === 'FAILED' ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-[#F0ECE1] text-[#2D3139] border border-[#DDD9D0]') }}">
                                    {{ $rec->status }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[#6B7280]">
                                {{ $rec->collected_at ? $rec->collected_at->format('Y-m-d H:i') : 'Pending confirmation' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-[#8A8F99] font-sans">
                                No realized revenue records logged yet. Records are generated upon swap finalization.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="p-4 border-t border-[#E8E5DF] bg-[#FAF8F5]">
                {{ $records->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
