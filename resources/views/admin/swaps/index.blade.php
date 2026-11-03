@extends('admin.layout')

@section('title', 'Swap Ledger & Inspection')
@section('header-tag', 'Ledger')
@section('header-title', 'Swap Orders')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">

    <!-- Header & Search Filters -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-4 border-b border-[#E8E5DF]">
        <div>
            <h1 class="text-2xl font-heading font-semibold text-[#121417] tracking-tight">
                Cross-Chain Swap Ledger
            </h1>
            <p class="text-xs text-[#52565E] mt-1">
                Authoritative record of all customer swap intents, solver responses, and on-chain settlement transactions.
            </p>
        </div>
        <div class="text-xs font-mono text-[#6B7280]">
            Total Records: {{ number_format($swaps->total()) }}
        </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="{{ route('admin.swaps.index') }}" class="bg-white border border-[#E8E5DF] rounded-xl p-5 shadow-xs">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-[11px] font-heading font-semibold uppercase tracking-wider text-[#6B7280] mb-1">Swap UUID</label>
                <input type="text" name="uuid" value="{{ $filters['uuid'] ?? '' }}" placeholder="Search UUID..."
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-2.5 py-1.5 text-xs text-[#121417] font-mono focus:outline-none focus:border-[#121417]" />
            </div>

            <div>
                <label class="block text-[11px] font-heading font-semibold uppercase tracking-wider text-[#6B7280] mb-1">Status</label>
                <select name="status" class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-2.5 py-1.5 text-xs text-[#121417] focus:outline-none focus:border-[#121417]">
                    <option value="">All Statuses</option>
                    @foreach(['PENDING_DEPOSIT', 'DEPOSIT_DETECTED', 'PROCESSING', 'COMPLETED', 'REFUNDED', 'FAILED', 'EXPIRED'] as $st)
                        <option value="{{ $st }}" {{ ($filters['status'] ?? '') === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-heading font-semibold uppercase tracking-wider text-[#6B7280] mb-1">Source Chain</label>
                <select name="source_network_id" class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-2.5 py-1.5 text-xs text-[#121417] focus:outline-none focus:border-[#121417]">
                    <option value="">All Chains</option>
                    @foreach($networks as $net)
                        <option value="{{ $net->id }}" {{ ($filters['source_network_id'] ?? '') == $net->id ? 'selected' : '' }}>{{ $net->display_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-heading font-semibold uppercase tracking-wider text-[#6B7280] mb-1">Dest Chain</label>
                <select name="destination_network_id" class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-2.5 py-1.5 text-xs text-[#121417] focus:outline-none focus:border-[#121417]">
                    <option value="">All Chains</option>
                    @foreach($networks as $net)
                        <option value="{{ $net->id }}" {{ ($filters['destination_network_id'] ?? '') == $net->id ? 'selected' : '' }}>{{ $net->display_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-heading font-semibold uppercase tracking-wider text-[#6B7280] mb-1">Wallet Address</label>
                <input type="text" name="wallet" value="{{ $filters['wallet'] ?? '' }}" placeholder="Origin / Recipient..."
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-2.5 py-1.5 text-xs text-[#121417] font-mono focus:outline-none focus:border-[#121417]" />
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                    class="flex-1 bg-[#121417] hover:bg-black text-white font-heading font-semibold py-1.5 px-3 rounded-lg text-xs transition cursor-pointer">
                    Filter
                </button>
                <a href="{{ route('admin.swaps.index') }}"
                    class="bg-[#FAF8F5] hover:bg-[#EAE7E0] border border-[#DDD9D0] text-[#52565E] font-heading font-medium py-1.5 px-3 rounded-lg text-xs transition">
                    Reset
                </a>
            </div>
        </div>
    </form>

    <!-- Table -->
    <div class="bg-white border border-[#E8E5DF] rounded-xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F7F5F0] text-[#52565E] font-heading font-medium uppercase text-[11px] tracking-wider border-b border-[#E8E5DF]">
                    <tr>
                        <th class="py-3 px-4 font-semibold">Swap UUID</th>
                        <th class="py-3 px-4 font-semibold">Source Route</th>
                        <th class="py-3 px-4 font-semibold">Destination Route</th>
                        <th class="py-3 px-4 font-semibold">Platform Fee</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 font-semibold">Timestamp</th>
                        <th class="py-3 px-4 text-right font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E8E5DF]">
                    @forelse($swaps as $swap)
                        <tr class="hover:bg-[#FAF8F5] transition">
                            <td class="py-3.5 px-4 font-mono font-medium text-[#121417]">
                                <a href="{{ route('admin.swaps.show', $swap->swap_uuid) }}" class="hover:underline">
                                    {{ substr($swap->swap_uuid, 0, 13) }}...
                                </a>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-semibold text-[#121417] font-sans">{{ $swap->amount_in }} {{ $swap->sourceAsset->symbol }}</span>
                                <span class="block text-[11px] text-[#6B7280]">{{ $swap->sourceNetwork->display_name }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-semibold text-[#0F6B48] font-sans">
                                    {{ $swap->actual_amount_out ?? $swap->expected_amount_out }} {{ $swap->destinationAsset->symbol }}
                                </span>
                                <span class="block text-[11px] text-[#6B7280]">{{ $swap->destinationNetwork->display_name }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                <span class="text-xs text-[#121417]">{{ ($swap->swapQuote->platform_fee_bps ?? 0) / 100 }}%</span>
                                <span class="block text-[10px] text-[#6B7280]">({{ $swap->swapQuote->platform_fee_bps ?? 0 }} BPS)</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-block px-2.5 py-0.5 rounded text-[11px] font-heading font-medium
                                    {{ $swap->status === 'COMPLETED' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($swap->status === 'FAILED' ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-[#F0ECE1] text-[#2D3139] border border-[#DDD9D0]') }}">
                                    {{ $swap->status }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[#6B7280]">
                                {{ $swap->created_at->format('Y-m-d H:i') }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('admin.swaps.show', $swap->swap_uuid) }}"
                                    class="inline-flex items-center text-xs px-2.5 py-1 rounded-md bg-[#121417] text-white hover:bg-black font-heading font-medium transition">
                                    Inspect
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-[#8A8F99] font-sans">
                                No swaps match the given filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($swaps->hasPages())
            <div class="p-4 border-t border-[#E8E5DF] bg-[#FAF8F5]">
                {{ $swaps->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
