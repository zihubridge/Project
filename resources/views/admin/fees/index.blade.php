@extends('admin.layout')

@section('title', 'Platform Swap Fees')
@section('header-tag', 'Monetization')
@section('header-title', 'Platform Fees')

@section('content')
<div class="space-y-8 max-w-6xl mx-auto">

    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#E8E5DF]">
        <div>
            <h1 class="text-2xl font-heading font-semibold text-[#121417] tracking-tight">
                Platform Swap Fee Management
            </h1>
            <p class="text-xs text-[#52565E] mt-1">
                Authoritative protocol fee configuration. 100 Basis Points (BPS) represents 1.00% fee deduction.
            </p>
        </div>
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-mono font-medium
                {{ ($currentConfig && $currentConfig->is_enabled) ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-[#F0ECE1] text-[#2D3139] border border-[#DDD9D0]' }}">
                <span>Status:</span>
                <span class="font-bold">{{ ($currentConfig && $currentConfig->is_enabled) ? 'ACTIVE' : 'DISABLED' }}</span>
            </span>
        </div>
    </div>

    <!-- Active Schedule Card & Update Form Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Left: Active Fee Card (5 cols) -->
        <div class="lg:col-span-5 space-y-4">
            <div class="bg-white border border-[#E8E5DF] rounded-xl p-6 shadow-xs">
                <span class="text-xs font-heading font-semibold uppercase tracking-wider text-[#6B7280]">
                    Current Effective Schedule
                </span>

                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-4xl font-heading font-bold text-[#121417] font-mono">
                        {{ $currentConfig ? ($currentConfig->fee_bps / 100) : 0 }}%
                    </span>
                    <span class="text-sm font-mono text-[#6B7280]">
                        ({{ $currentConfig ? $currentConfig->fee_bps : 0 }} BPS)
                    </span>
                </div>

                <div class="mt-6 space-y-3 text-xs border-t border-[#F0ECE1] pt-4 font-mono">
                    <div class="flex justify-between">
                        <span class="text-[#6B7280] font-sans">State:</span>
                        <span class="font-semibold text-[#121417]">
                            {{ ($currentConfig && $currentConfig->is_enabled) ? 'Enabled on Quotes' : 'Fee Bypassed (0%)' }}
                        </span>
                    </div>

                    <div>
                        <span class="text-[#6B7280] font-sans block mb-1">Fee Recipient Address:</span>
                        <div class="p-2.5 rounded bg-[#FAF8F5] border border-[#E8E5DF] text-[#121417] break-all select-all font-mono text-[11px]">
                            {{ ($currentConfig && $currentConfig->fee_recipient) ? $currentConfig->fee_recipient : (config('services.near_intents.partner_fee_recipient') ?: 'Not Set (Treasury Default)') }}
                        </div>
                    </div>

                    <div class="flex justify-between pt-1">
                        <span class="text-[#6B7280] font-sans">Effective Since:</span>
                        <span class="text-[#121417]">
                            {{ $currentConfig && $currentConfig->effective_from ? $currentConfig->effective_from->format('Y-m-d H:i') : 'Immediate' }}
                        </span>
                    </div>

                    @if($currentConfig && $currentConfig->changedByAdmin)
                    <div class="flex justify-between pt-1">
                        <span class="text-[#6B7280] font-sans">Updated By:</span>
                        <span class="text-[#121417] font-sans font-medium">
                            {{ $currentConfig->changedByAdmin->name }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Context Info -->
            <div class="p-4 rounded-xl bg-[#FAF8F5] border border-[#E8E5DF] text-xs text-[#52565E] space-y-2">
                <span class="font-heading font-semibold text-[#121417] block">Protocol Guarantee:</span>
                <p>Every newly generated quote binds to this active database fee. Existing orders retain their historical locked fee rate.</p>
                <p>Provider quotes pass this value directly inside the NEAR Intents <code class="font-mono text-[#121417]">appFees</code> array.</p>
            </div>
        </div>

        <!-- Right: Update Schedule Form (7 cols) -->
        <div class="lg:col-span-7 bg-white border border-[#E8E5DF] rounded-xl p-6 sm:p-8 shadow-xs">
            <h2 class="text-base font-heading font-semibold text-[#121417] mb-1">
                Configure Platform Fee Schedule
            </h2>
            <p class="text-xs text-[#52565E] mb-6">
                Modifications will take effect immediately for all subsequent customer quotes.
            </p>

            <form method="POST" action="{{ route('admin.fees.update') }}" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                            Fee in Basis Points (BPS)
                        </label>
                        <div class="relative">
                            <input type="number" name="fee_bps" min="0" max="1000" step="5"
                                value="{{ old('fee_bps', $currentConfig ? $currentConfig->fee_bps : 100) }}" required
                                class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2.5 text-sm font-mono text-[#121417] focus:outline-none focus:border-[#121417] focus:bg-white transition" />
                        </div>
                        <span class="text-[11px] text-[#6B7280] mt-1 block">100 BPS = 1.00% &bull; 50 BPS = 0.50%</span>
                    </div>

                    <div>
                        <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                            Fee Schedule Status
                        </label>
                        <select name="is_enabled"
                            class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2.5 text-sm font-heading font-medium text-[#121417] focus:outline-none focus:border-[#121417] focus:bg-white transition">
                            <option value="1" {{ old('is_enabled', $currentConfig?->is_enabled ? '1' : '0') == '1' ? 'selected' : '' }}>Enabled (Collect Fees)</option>
                            <option value="0" {{ old('is_enabled', $currentConfig?->is_enabled ? '1' : '0') == '0' ? 'selected' : '' }}>Disabled (0% Promotional)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                        Fee Recipient Wallet Address / Account
                    </label>
                    <input type="text" name="fee_recipient"
                        value="{{ old('fee_recipient', $currentConfig?->fee_recipient ?: config('services.near_intents.partner_fee_recipient')) }}"
                        placeholder="e.g. zihubridge.near or recipient EVM wallet"
                        class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2.5 text-xs font-mono text-[#121417] focus:outline-none focus:border-[#121417] focus:bg-white transition" />
                    <span class="text-[11px] text-[#6B7280] mt-1 block">The wallet destination where NEAR Intents routes partner fee allocations.</span>
                </div>

                <div>
                    <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                        Mandatory Change Rationale (Audit Record)
                    </label>
                    <textarea name="reason" rows="3" required placeholder="Explain reason for schedule update (e.g. Setting official 1% protocol fee launch schedule)..."
                        class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2.5 text-xs text-[#121417] focus:outline-none focus:border-[#121417] focus:bg-white transition">{{ old('reason') }}</textarea>
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full bg-[#121417] hover:bg-black text-white font-heading font-semibold py-3 rounded-lg text-xs md:text-sm transition cursor-pointer">
                        Save & Commit New Fee Schedule
                    </button>
                </div>
            </form>
        </div>

    </div>

    <!-- Fee Change Audit History -->
    <div class="bg-white border border-[#E8E5DF] rounded-xl overflow-hidden shadow-xs">
        <div class="p-5 border-b border-[#E8E5DF]">
            <h2 class="text-base font-heading font-semibold text-[#121417]">
                Historical Fee Configurations & Audit Trail
            </h2>
            <p class="text-xs text-[#52565E]">Immutable record of every fee change, administrator, and documented rationale</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F7F5F0] text-[#52565E] font-heading font-medium uppercase text-[11px] tracking-wider border-b border-[#E8E5DF]">
                    <tr>
                        <th class="py-3 px-4 font-semibold">Config ID</th>
                        <th class="py-3 px-4 font-semibold">Rate (BPS)</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 font-semibold">Recipient</th>
                        <th class="py-3 px-4 font-semibold">Changed By</th>
                        <th class="py-3 px-4 font-semibold">Rationale</th>
                        <th class="py-3 px-4 font-semibold text-right">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E8E5DF]">
                    @forelse($history as $item)
                        <tr class="hover:bg-[#FAF8F5] transition">
                            <td class="py-3.5 px-4 font-mono font-medium text-[#121417]">#{{ $item->id }}</td>
                            <td class="py-3.5 px-4 font-mono font-semibold text-[#121417]">
                                {{ $item->fee_bps / 100 }}% ({{ $item->fee_bps }} BPS)
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[11px] font-mono font-semibold
                                    {{ $item->is_enabled ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $item->is_enabled ? 'ENABLED' : 'DISABLED' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[#6B7280] truncate max-w-[160px]" title="{{ $item->fee_recipient }}">
                                {{ $item->fee_recipient ?: 'Default' }}
                            </td>
                            <td class="py-3.5 px-4 font-sans text-[#121417]">
                                {{ $item->changedByAdmin->name ?? 'System' }}
                            </td>
                            <td class="py-3.5 px-4 text-[#52565E] max-w-xs truncate" title="{{ $item->reason }}">
                                {{ $item->reason }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[#6B7280] text-right">
                                {{ $item->created_at->format('Y-m-d H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-[#8A8F99]">No prior fee modifications recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
