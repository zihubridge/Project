@extends('admin.layout')

@section('title', 'Swap Inspection: ' . $order->swap_uuid)
@section('header-tag', 'Ledger Inspection')
@section('header-title', 'Order ' . substr($order->swap_uuid, 0, 8) . '...')

@section('content')
<div class="space-y-6 max-w-6xl mx-auto">

    <!-- Back Bar & UUID Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#E8E5DF]">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.swaps.index') }}"
                class="px-2.5 py-1.5 rounded-lg border border-[#DDD9D0] bg-[#FAF8F5] text-xs font-heading font-medium text-[#52565E] hover:text-[#121417] transition">
                &larr; Back to Ledger
            </a>
            <div>
                <h1 class="text-lg md:text-xl font-heading font-semibold text-[#121417]">
                    Swap Order Details
                </h1>
                <div class="text-xs font-mono text-[#6B7280]">
                    UUID: <span class="text-[#121417] select-all font-semibold">{{ $order->swap_uuid }}</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded text-xs font-heading font-semibold uppercase tracking-wider
                {{ $order->status === 'COMPLETED' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($order->status === 'FAILED' ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-[#F0ECE1] text-[#2D3139] border border-[#DDD9D0]') }}">
                {{ $order->status }}
            </span>
            <span class="text-xs font-mono text-[#6B7280] bg-white px-2.5 py-1 rounded border border-[#E8E5DF]">
                Provider: {{ $order->swapProvider->display_name }}
            </span>
        </div>
    </div>

    <!-- Summary Grid: Amounts & Conversion -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Deposit Asset Card -->
        <div class="bg-white border border-[#E8E5DF] rounded-xl p-5 shadow-xs">
            <span class="text-xs font-heading font-semibold uppercase tracking-wider text-[#6B7280]">Deposit Asset</span>
            <div class="text-2xl font-heading font-semibold text-[#121417] mt-1.5 font-mono">
                {{ rtrim(rtrim((string)$order->amount_in, '0'), '.') }} {{ $order->sourceAsset->symbol }}
            </div>
            <div class="text-xs text-[#52565E] mt-1">
                Network: <strong class="text-[#121417]">{{ $order->sourceNetwork->display_name }}</strong>
                ({{ $order->sourceNetwork->chain_family }})
            </div>
            <div class="text-xs font-mono text-[#6B7280] mt-3 p-2.5 rounded bg-[#FAF8F5] border border-[#E8E5DF] break-all">
                <span class="text-[10px] uppercase font-sans text-[#8A8F99] block mb-0.5">Origin Refund Address</span>
                {{ $order->refund_address }}
                @if($order->refund_memo)
                    <span class="block text-amber-800 mt-1">Memo/Tag: {{ $order->refund_memo }}</span>
                @endif
            </div>
        </div>

        <!-- Destination Asset Card -->
        <div class="bg-white border border-[#E8E5DF] rounded-xl p-5 shadow-xs">
            <span class="text-xs font-heading font-semibold uppercase tracking-wider text-[#6B7280]">Destination Delivery</span>
            <div class="text-2xl font-heading font-semibold text-[#0F6B48] mt-1.5 font-mono">
                {{ rtrim(rtrim((string)($order->actual_amount_out ?? $order->expected_amount_out), '0'), '.') }} {{ $order->destinationAsset->symbol }}
            </div>
            <div class="text-xs text-[#52565E] mt-1">
                Network: <strong class="text-[#121417]">{{ $order->destinationNetwork->display_name }}</strong>
                <span class="font-mono text-[#8A8F99] ml-2">(Min Floor: {{ rtrim(rtrim((string)$order->min_amount_out, '0'), '.') }})</span>
            </div>
            <div class="text-xs font-mono text-[#6B7280] mt-3 p-2.5 rounded bg-[#FAF8F5] border border-[#E8E5DF] break-all">
                <span class="text-[10px] uppercase font-sans text-[#8A8F99] block mb-0.5">Destination Recipient Address</span>
                {{ $order->recipient_address }}
                @if($order->recipient_memo)
                    <span class="block text-amber-800 mt-1">Memo/Tag: {{ $order->recipient_memo }}</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Provider & Platform Fee Intelligence -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Exchange Rate & Slippage -->
        <div class="bg-white border border-[#E8E5DF] rounded-xl p-5 shadow-xs">
            <span class="text-xs font-heading font-semibold uppercase tracking-wider text-[#6B7280]">Rate & Slippage</span>
            <div class="text-sm font-mono font-semibold text-[#121417] mt-2">
                1 {{ $order->sourceAsset->symbol }} = {{ rtrim(rtrim((string)($order->swapQuote->exchange_rate ?? 0), '0'), '.') }} {{ $order->destinationAsset->symbol }}
            </div>
            <div class="text-xs text-[#52565E] mt-2 pt-2 border-t border-[#F0ECE1] flex justify-between font-mono">
                <span>Slippage Tolerance:</span>
                <span class="font-semibold text-[#121417]">{{ ($order->slippage_bps / 100) }}% ({{ $order->slippage_bps }} BPS)</span>
            </div>
        </div>

        <!-- Platform Fee Structure -->
        <div class="bg-white border border-[#E8E5DF] rounded-xl p-5 shadow-xs">
            <span class="text-xs font-heading font-semibold uppercase tracking-wider text-[#6B7280]">Platform Fee Rate</span>
            <div class="text-xl font-heading font-semibold text-[#121417] mt-1 font-mono">
                {{ ($order->swapQuote->platform_fee_bps ?? 0) / 100 }}%
                <span class="text-xs font-normal text-[#6B7280]">({{ $order->swapQuote->platform_fee_bps ?? 0 }} BPS)</span>
            </div>
            <div class="text-xs text-[#52565E] mt-2 pt-2 border-t border-[#F0ECE1] flex justify-between font-mono">
                <span>Fee Recipient:</span>
                <span class="text-[#121417] truncate max-w-[140px]" title="{{ config('services.near_intents.partner_fee_recipient') ?: 'Configured Account' }}">
                    {{ config('services.near_intents.partner_fee_recipient') ?: 'Protocol Treasury' }}
                </span>
            </div>
        </div>

        <!-- Timestamps -->
        <div class="bg-white border border-[#E8E5DF] rounded-xl p-5 shadow-xs">
            <span class="text-xs font-heading font-semibold uppercase tracking-wider text-[#6B7280]">Order Lifecycle Times</span>
            <div class="text-xs font-mono text-[#52565E] mt-2 space-y-1">
                <div class="flex justify-between">
                    <span>Initiated:</span>
                    <span class="text-[#121417]">{{ $order->created_at->format('Y-m-d H:i:s') }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Completed:</span>
                    <span class="text-[#121417]">{{ $order->completed_at ? $order->completed_at->format('Y-m-d H:i:s') : 'Pending' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Deposit Instructions & Settlement Transactions -->
    <div class="bg-white border border-[#E8E5DF] rounded-xl p-6 shadow-xs space-y-4">
        <h2 class="text-sm font-heading font-semibold uppercase tracking-wider text-[#121417]">
            On-Chain Intent Execution
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-mono">
            <div class="p-3 rounded-lg bg-[#FAF8F5] border border-[#E8E5DF]">
                <span class="text-[#8A8F99] block text-[10px] uppercase font-sans mb-1">Assigned Deposit Address</span>
                <span class="text-[#121417] break-all select-all">{{ $order->deposit_address }}</span>
                @if($order->deposit_memo)
                    <span class="block text-amber-800 mt-1 font-sans font-medium">Memo: {{ $order->deposit_memo }}</span>
                @endif
            </div>

            <div class="p-3 rounded-lg bg-[#FAF8F5] border border-[#E8E5DF]">
                <span class="text-[#8A8F99] block text-[10px] uppercase font-sans mb-1">Source Deposit Transaction Hash</span>
                @if($order->source_tx_hash)
                    <span class="text-[#121417] break-all select-all">{{ $order->source_tx_hash }}</span>
                    @if($order->source_explorer_url)
                        <a href="{{ $order->source_explorer_url }}" target="_blank" class="block text-[#0F6B48] underline mt-1 font-sans">
                            View on Source Explorer &nearr;
                        </a>
                    @endif
                @else
                    <span class="text-[#8A8F99] font-sans">No deposit transaction submitted yet</span>
                @endif
            </div>

            <div class="p-3 rounded-lg bg-[#FAF8F5] border border-[#E8E5DF]">
                <span class="text-[#8A8F99] block text-[10px] uppercase font-sans mb-1">Destination Settlement Transaction Hash</span>
                @if($order->destination_tx_hash)
                    <span class="text-[#121417] break-all select-all">{{ $order->destination_tx_hash }}</span>
                    @if($order->destination_explorer_url)
                        <a href="{{ $order->destination_explorer_url }}" target="_blank" class="block text-[#0F6B48] underline mt-1 font-sans">
                            View on Destination Explorer &nearr;
                        </a>
                    @endif
                @else
                    <span class="text-[#8A8F99] font-sans">Pending solver payout</span>
                @endif
            </div>

            <div class="p-3 rounded-lg bg-[#FAF8F5] border border-[#E8E5DF]">
                <span class="text-[#8A8F99] block text-[10px] uppercase font-sans mb-1">Refund Transaction Hash</span>
                @if($order->refund_tx_hash)
                    <span class="text-rose-700 break-all select-all">{{ $order->refund_tx_hash }}</span>
                @else
                    <span class="text-[#8A8F99] font-sans">None (Order not refunded)</span>
                @endif
            </div>
        </div>

        @if($order->failure_reason)
            <div class="p-4 rounded-lg bg-rose-50 border border-rose-200 text-xs text-rose-800">
                <span class="font-heading font-semibold block mb-0.5">Failure / Abort Reason</span>
                <span>{{ $order->failure_reason }}</span>
            </div>
        @endif
    </div>

    <!-- Execution Lifecycle Steps -->
    <div class="bg-white border border-[#E8E5DF] rounded-xl p-6 shadow-xs">
        <h2 class="text-sm font-heading font-semibold uppercase tracking-wider text-[#121417] mb-4">
            Order Execution Timeline
        </h2>
        <div class="space-y-3">
            @forelse($order->steps as $step)
                <div class="flex items-center justify-between p-3.5 rounded-lg bg-[#FAF8F5] border border-[#E8E5DF]">
                    <div class="flex items-center gap-3">
                        <span class="w-7 h-7 rounded-md flex items-center justify-center text-xs font-heading font-semibold
                            {{ $step->status === 'COMPLETED' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : ($step->status === 'IN_PROGRESS' ? 'bg-amber-100 text-amber-800 border border-amber-300' : ($step->status === 'FAILED' ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-[#EAE7E0] text-[#6B7280] border border-[#DDD9D0]')) }}">
                            {{ $step->step_number }}
                        </span>
                        <div>
                            <div class="text-sm font-heading font-medium text-[#121417]">{{ $step->label }}</div>
                            <div class="text-xs text-[#6B7280] font-mono">{{ $step->status }}</div>
                        </div>
                    </div>
                    <div class="text-xs font-mono text-[#6B7280]">
                        @if($step->completed_at)
                            {{ $step->completed_at->format('H:i:s') }}
                        @elseif($step->tx_hash)
                            {{ substr($step->tx_hash, 0, 16) }}...
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-xs text-[#8A8F99]">No execution steps registered.</p>
            @endforelse
        </div>
    </div>

</div>
@endsection
