@extends('admin.layout')

@section('title', 'Fallback Routing Engine')
@section('header-tag', 'Universal Liquidity Engine')
@section('header-title', 'Multi-Hop Fallback Routes')

@section('content')
<div class="space-y-6">

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-[#E8E5DF]">
        <div>
            <h1 class="text-xl font-heading font-semibold text-[#121417]">Universal Fallback Routing Engine</h1>
            <p class="text-xs text-[#52565E] mt-0.5">
                Configure blockchain-agnostic intermediate assets and local DEX adapters for unlisted destination tokens.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.routing.create') }}"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-[#121417] hover:bg-black text-white text-xs font-heading font-semibold transition cursor-pointer shadow-xs">
                <span>+ New Fallback Route</span>
            </a>
        </div>
    </div>

    <!-- Fallback Routing Table -->
    <div class="bg-white border border-[#E8E5DF] rounded-xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F7F5F0] text-[#52565E] font-heading font-medium uppercase text-[11px] tracking-wider border-b border-[#E8E5DF]">
                    <tr>
                        <th class="py-3 px-4 font-semibold">Priority</th>
                        <th class="py-3 px-4 font-semibold">Destination Chain</th>
                        <th class="py-3 px-4 font-semibold">Target Token</th>
                        <th class="py-3 px-4 font-semibold">Intermediate Asset</th>
                        <th class="py-3 px-4 font-semibold">First Leg (Bridge)</th>
                        <th class="py-3 px-4 font-semibold">Second Leg (DEX Adapter)</th>
                        <th class="py-3 px-4 font-semibold">Max Slippage</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E8E5DF]">
                    @forelse($routes as $r)
                        <tr class="hover:bg-[#FAF8F5] transition">
                            <td class="py-3.5 px-4 font-mono font-semibold text-[#121417]">
                                #{{ $r->priority }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-heading font-semibold text-[#121417]">{{ $r->destinationNetwork->display_name ?? 'N/A' }}</span>
                                <span class="block font-mono text-[11px] text-[#6B7280]">{{ $r->destinationNetwork->slug ?? '' }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($r->destinationToken)
                                    <span class="font-mono font-semibold text-[#121417]">{{ $r->destinationToken->symbol }}</span>
                                    <span class="block text-[11px] text-[#6B7280]">{{ $r->destinationToken->display_name }}</span>
                                @else
                                    <span class="inline-flex items-center text-[#52565E] bg-[#FAF8F5] px-2 py-0.5 rounded border border-[#E8E5DF] font-sans text-[11px]">
                                        All Unrouted Tokens
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-mono font-semibold text-[#121417]">{{ $r->intermediateAsset->symbol ?? 'N/A' }}</span>
                                    <span class="text-[11px] text-[#6B7280]">({{ $r->intermediateAsset->network->display_name ?? '' }})</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-sans text-[#121417]">
                                <span class="font-medium">{{ $r->firstLegProvider->display_name ?? 'NEAR Intents' }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-mono text-xs text-[#121417] font-medium block">{{ $r->second_leg_adapter_key }}</span>
                                @if($r->required_wallet_type)
                                    <span class="font-sans text-[10px] text-[#6B7280] block">Wallet: {{ $r->required_wallet_type }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[#121417]">
                                {{ number_format($r->max_slippage_bps / 100, 2) }}%
                            </td>
                            <td class="py-3.5 px-4">
                                @if($r->is_enabled)
                                    <span class="inline-flex items-center text-[#0F6B48] bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 text-[11px] font-sans font-medium">
                                        ACTIVE
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-[#6B7280] bg-[#FAF8F5] px-2 py-0.5 rounded border border-[#E8E5DF] text-[11px] font-sans font-medium">
                                        DISABLED
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.routing.toggle', $r->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs font-heading font-medium text-[#52565E] hover:text-[#121417] underline underline-offset-2 cursor-pointer">
                                            {{ $r->is_enabled ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                    <span class="text-[#E8E5DF]">|</span>
                                    <a href="{{ route('admin.routing.edit', $r->id) }}"
                                        class="text-xs font-heading font-medium text-[#121417] hover:underline underline-offset-2">
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-[#8A8F99]">
                                No multi-hop fallback routes configured yet. Click "+ New Fallback Route" above to create one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($routes->hasPages())
            <div class="p-4 border-t border-[#E8E5DF] bg-[#FAF8F5]">
                {{ $routes->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

