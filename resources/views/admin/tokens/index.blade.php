@extends('admin.layout')

@section('title', 'Token Catalog Registry')
@section('header-tag', 'Asset Catalog')
@section('header-title', 'Token Registry')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#E8E5DF]">
        <div>
            <h1 class="text-2xl font-heading font-semibold text-[#121417] tracking-tight">
                Cross-Chain Token Catalog
            </h1>
            <p class="text-xs text-[#52565E] mt-1">
                Manage token identities, contract addresses, decimals, and inspect NEAR Intents provider route synchronization.
            </p>
        </div>

        <a href="{{ route('admin.tokens.create') }}"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-[#121417] hover:bg-black text-white text-xs font-heading font-medium transition cursor-pointer">
            <ion-icon name="add-outline" class="text-base"></ion-icon>
            <span>Register New Token</span>
        </a>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="{{ route('admin.tokens.index') }}" class="bg-white border border-[#E8E5DF] rounded-xl p-4 shadow-xs">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-[11px] font-heading font-semibold uppercase tracking-wider text-[#6B7280] mb-1">Blockchain</label>
                <select name="network_id" class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-2.5 py-1.5 text-xs text-[#121417] focus:outline-none focus:border-[#121417]">
                    <option value="">All Blockchains</option>
                    @foreach($networks as $net)
                        <option value="{{ $net->id }}" {{ ($filters['network_id'] ?? '') == $net->id ? 'selected' : '' }}>{{ $net->display_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-heading font-semibold uppercase tracking-wider text-[#6B7280] mb-1">Symbol Search</label>
                <input type="text" name="symbol" value="{{ $filters['symbol'] ?? '' }}" placeholder="Search symbol..."
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-2.5 py-1.5 text-xs font-mono text-[#121417] focus:outline-none focus:border-[#121417]" />
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                    class="flex-1 bg-[#121417] hover:bg-black text-white font-heading font-semibold py-1.5 px-3 rounded-lg text-xs transition cursor-pointer">
                    Filter
                </button>
                <a href="{{ route('admin.tokens.index') }}"
                    class="bg-[#FAF8F5] hover:bg-[#EAE7E0] border border-[#DDD9D0] text-[#52565E] font-heading font-medium py-1.5 px-3 rounded-lg text-xs transition">
                    Reset
                </a>
            </div>
        </div>
    </form>

    <!-- Tokens Table -->
    <div class="bg-white border border-[#E8E5DF] rounded-xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F7F5F0] text-[#52565E] font-heading font-medium uppercase text-[11px] tracking-wider border-b border-[#E8E5DF]">
                    <tr>
                        <th class="py-3 px-4 font-semibold">Asset</th>
                        <th class="py-3 px-4 font-semibold">Network</th>
                        <th class="py-3 px-4 font-semibold">Contract / Issuer</th>
                        <th class="py-3 px-4 font-semibold">Decimals</th>
                        <th class="py-3 px-4 font-semibold">Provider Route Sync</th>
                        <th class="py-3 px-4 font-semibold">State</th>
                        <th class="py-3 px-4 text-right font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E8E5DF]">
                    @forelse($tokens as $tok)
                        <tr class="hover:bg-[#FAF8F5] transition">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2.5">
                                    @if($tok->logo_url)
                                        <img src="{{ $tok->logo_url }}" alt="{{ $tok->symbol }}" class="w-6 h-6 rounded-full object-contain bg-slate-100" />
                                    @else
                                        <div class="w-6 h-6 rounded-full bg-[#EAE7E0] text-[#121417] font-mono font-bold text-[10px] flex items-center justify-center">
                                            {{ substr($tok->symbol, 0, 2) }}
                                        </div>
                                    @endif
                                    <div>
                                        <span class="font-heading font-semibold text-[#121417] block">{{ $tok->display_name }}</span>
                                        <span class="font-mono text-[11px] text-[#6B7280]">{{ $tok->symbol }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-sans text-[#121417]">
                                {{ $tok->blockchainNetwork->display_name ?? 'N/A' }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-[#6B7280] max-w-xs truncate" title="{{ $tok->contract_or_issuer ?: 'Native Currency' }}">
                                @if($tok->is_native)
                                    <span class="text-[#0F6B48] font-sans font-medium">Native Layer Currency</span>
                                @else
                                    {{ $tok->contract_or_issuer ?: '-' }}
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[#121417]">
                                {{ $tok->decimals }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-xs">
                                @if($tok->providerSupportedAssets->isNotEmpty())
                                    <span class="inline-flex items-center gap-1 text-[#0F6B48] bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 text-[11px]">
                                        <span>NEAR Intents Synced</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[#8A8F99] bg-[#FAF8F5] px-2 py-0.5 rounded border border-[#E8E5DF] text-[11px]">
                                        <span>Catalog Only</span>
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[11px] font-mono font-semibold
                                    {{ $tok->is_enabled ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $tok->is_enabled ? 'ENABLED' : 'DISABLED' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('admin.tokens.edit', $tok->id) }}"
                                    class="inline-flex items-center text-xs px-2.5 py-1 rounded-md bg-[#121417] text-white hover:bg-black font-heading font-medium transition">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-[#8A8F99]">No tokens registered matching filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tokens->hasPages())
            <div class="p-4 border-t border-[#E8E5DF] bg-[#FAF8F5]">
                {{ $tokens->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
