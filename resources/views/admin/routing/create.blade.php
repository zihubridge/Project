@extends('admin.layout')

@section('title', 'Create Fallback Route')
@section('header-tag', 'Routing Configuration')
@section('header-title', 'Configure New Fallback Route')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <div class="flex items-center gap-3 pb-4 border-b border-[#E8E5DF]">
        <a href="{{ route('admin.routing.index') }}"
            class="px-2.5 py-1.5 rounded-lg border border-[#DDD9D0] bg-[#FAF8F5] text-xs font-heading font-medium text-[#52565E] hover:text-[#121417] transition">
            &larr; Back to Fallback Routes
        </a>
        <h1 class="text-xl font-heading font-semibold text-[#121417]">
            New Universal Fallback Route
        </h1>
    </div>

    <form method="POST" action="{{ route('admin.routing.store') }}" class="bg-white border border-[#E8E5DF] rounded-xl p-6 sm:p-8 space-y-6 shadow-xs">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Destination Blockchain Network
                </label>
                <select name="destination_network_id" required class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm text-[#121417] focus:outline-none focus:border-[#121417]">
                    @foreach($networks as $net)
                        <option value="{{ $net->id }}" {{ old('destination_network_id') == $net->id ? 'selected' : '' }}>
                            {{ $net->display_name }} ({{ $net->chain_family }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Target Token (Optional - Leave blank for all unrouted tokens)
                </label>
                <select name="destination_token_id" class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm text-[#121417] focus:outline-none focus:border-[#121417]">
                    <option value="">-- Apply to All Unrouted Tokens on this Chain --</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}" {{ old('destination_token_id') == $asset->id ? 'selected' : '' }}>
                            {{ $asset->symbol }} - {{ $asset->display_name }} ({{ $asset->network->display_name ?? '' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Intermediate Asset (Delivered on Destination Chain)
                </label>
                <select name="intermediate_asset_id" required class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm text-[#121417] focus:outline-none focus:border-[#121417]">
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}" {{ (old('intermediate_asset_id') == $asset->id || (strtoupper($asset->symbol) === 'XLM' && strtolower($asset->network?->slug ?? '') === 'stellar')) ? 'selected' : '' }}>
                            {{ $asset->symbol }} - {{ $asset->display_name }} ({{ $asset->network->display_name ?? '' }})
                        </option>
                    @endforeach
                </select>
                <p class="text-[11px] text-[#6B7280] mt-1">This asset must be directly supported by the first-leg cross-chain bridge provider.</p>
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    First-Leg Provider (Cross-Chain Bridge)
                </label>
                <select name="first_leg_provider_id" required class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm text-[#121417] focus:outline-none focus:border-[#121417]">
                    @foreach($providers as $p)
                        <option value="{{ $p->id }}" {{ old('first_leg_provider_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Second-Leg Execution Adapter (Local DEX)
                </label>
                <select name="second_leg_adapter_key" required class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm text-[#121417] focus:outline-none focus:border-[#121417]">
                    @foreach($adapters as $key => $adapter)
                        <option value="{{ $key }}" {{ old('second_leg_adapter_key') === $key ? 'selected' : '' }}>
                            {{ $adapter->getDisplayName() }} ({{ $key }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Route Priority (1 = Highest)
                </label>
                <input type="number" name="priority" value="{{ old('priority', 10) }}" min="1" max="1000" required
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm font-mono text-[#121417] focus:outline-none focus:border-[#121417]" />
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Max Acceptable Slippage (Basis Points)
                </label>
                <input type="number" name="max_slippage_bps" value="{{ old('max_slippage_bps', 300) }}" min="10" max="5000" required
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm font-mono text-[#121417] focus:outline-none focus:border-[#121417]" />
                <p class="text-[11px] text-[#6B7280] mt-1">300 BPS = 3.00% max slippage floor.</p>
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Route Status
                </label>
                <select name="is_enabled" class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm text-[#121417] focus:outline-none focus:border-[#121417]">
                    <option value="1" {{ old('is_enabled', '1') == '1' ? 'selected' : '' }}>Enabled (Active for swaps)</option>
                    <option value="0" {{ old('is_enabled') == '0' ? 'selected' : '' }}>Disabled</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Minimum Swap Amount (Optional)
                </label>
                <input type="number" step="any" name="min_amount" value="{{ old('min_amount') }}" placeholder="e.g. 5.0"
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm font-mono text-[#121417] focus:outline-none focus:border-[#121417]" />
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Maximum Swap Amount (Optional)
                </label>
                <input type="number" step="any" name="max_amount" value="{{ old('max_amount') }}" placeholder="e.g. 10000.0"
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm font-mono text-[#121417] focus:outline-none focus:border-[#121417]" />
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Required Wallet Type (Optional)
                </label>
                <input type="text" name="required_wallet_type" value="{{ old('required_wallet_type', 'stellar_freighter') }}" placeholder="e.g. stellar_freighter, metamask, phantom"
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm font-mono text-[#121417] focus:outline-none focus:border-[#121417]" />
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Account / Trustline Requirements Note
                </label>
                <input type="text" name="account_requirements_note" value="{{ old('account_requirements_note', 'Requires active account and trustline') }}" placeholder="e.g. Trustline required"
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm text-[#121417] focus:outline-none focus:border-[#121417]" />
            </div>
        </div>

        <div>
            <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                Admin Notes / Route Description
            </label>
            <textarea name="notes" rows="2" placeholder="e.g. Primary fallback for unlisted Stellar tokens via SDEX liquidity pools"
                class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-xs text-[#121417] focus:outline-none focus:border-[#121417]">{{ old('notes') }}</textarea>
        </div>

        <div class="pt-4 border-t border-[#E8E5DF] flex justify-end gap-3">
            <a href="{{ route('admin.routing.index') }}"
                class="px-4 py-2 rounded-lg bg-[#FAF8F5] hover:bg-[#EAE7E0] border border-[#DDD9D0] text-xs font-heading font-medium text-[#52565E] transition">
                Cancel
            </a>
            <button type="submit"
                class="px-5 py-2 rounded-lg bg-[#121417] hover:bg-black text-white text-xs font-heading font-semibold transition cursor-pointer">
                Create Fallback Route
            </button>
        </div>
    </form>

</div>
@endsection

