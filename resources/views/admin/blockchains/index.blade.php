@extends('admin.layout')

@section('title', 'Blockchain Network Management')
@section('header-tag', 'Catalog Infrastructure')
@section('header-title', 'Blockchains')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#E8E5DF]">
        <div>
            <h1 class="text-2xl font-heading font-semibold text-[#121417] tracking-tight">
                Blockchain Networks
            </h1>
            <p class="text-xs text-[#52565E] mt-1">
                Configure settlement chains, address validation patterns, memo/tag flags, and explorer templates.
            </p>
        </div>
        <div class="text-xs font-mono text-[#6B7280]">
            Configured Chains: {{ $networks->total() }}
        </div>
    </div>

    <!-- Networks Table -->
    <div class="bg-white border border-[#E8E5DF] rounded-xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F7F5F0] text-[#52565E] font-heading font-medium uppercase text-[11px] tracking-wider border-b border-[#E8E5DF]">
                    <tr>
                        <th class="py-3 px-4 font-semibold">Network Name</th>
                        <th class="py-3 px-4 font-semibold">Slug</th>
                        <th class="py-3 px-4 font-semibold">Family</th>
                        <th class="py-3 px-4 font-semibold">Native Token</th>
                        <th class="py-3 px-4 font-semibold">Memo / Tag</th>
                        <th class="py-3 px-4 font-semibold">Catalog Assets</th>
                        <th class="py-3 px-4 font-semibold">State</th>
                        <th class="py-3 px-4 text-right font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E8E5DF]">
                    @forelse($networks as $net)
                        <tr class="hover:bg-[#FAF8F5] transition">
                            <td class="py-3.5 px-4 font-heading font-semibold text-[#121417]">
                                {{ $net->display_name }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[#6B7280]">
                                {{ $net->slug }}
                            </td>
                            <td class="py-3.5 px-4 font-mono uppercase text-[#121417]">
                                {{ $net->chain_family }}
                            </td>
                            <td class="py-3.5 px-4 font-mono font-medium text-[#121417]">
                                {{ $net->native_symbol ?: '-' }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-xs">
                                @if($net->requires_memo_or_tag)
                                    <span class="text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">Required</span>
                                @else
                                    <span class="text-[#8A8F99]">Optional / None</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[#121417]">
                                {{ $net->crypto_assets_count }} tokens
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[11px] font-mono font-semibold
                                    {{ $net->is_enabled ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $net->is_enabled ? 'ENABLED' : 'DISABLED' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('admin.blockchains.edit', $net->id) }}"
                                    class="inline-flex items-center text-xs px-2.5 py-1 rounded-md bg-[#121417] text-white hover:bg-black font-heading font-medium transition">
                                    Edit Settings
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-[#8A8F99]">No blockchain networks configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($networks->hasPages())
            <div class="p-4 border-t border-[#E8E5DF] bg-[#FAF8F5]">
                {{ $networks->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
