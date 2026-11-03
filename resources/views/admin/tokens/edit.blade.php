@extends('admin.layout')

@section('title', 'Edit Token: ' . $token->symbol)
@section('header-tag', 'Asset Catalog')
@section('header-title', $token->symbol . ' (' . ($token->blockchainNetwork->display_name ?? 'N/A') . ')')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">

    <div class="flex items-center gap-3 pb-4 border-b border-[#E8E5DF]">
        <a href="{{ route('admin.tokens.index') }}"
            class="px-2.5 py-1.5 rounded-lg border border-[#DDD9D0] bg-[#FAF8F5] text-xs font-heading font-medium text-[#52565E] hover:text-[#121417] transition">
            &larr; Back to Token Registry
        </a>
        <h1 class="text-xl font-heading font-semibold text-[#121417]">
            Edit Token: {{ $token->display_name }} ({{ $token->symbol }})
        </h1>
    </div>

    <form method="POST" action="{{ route('admin.tokens.update', $token->id) }}" class="bg-white border border-[#E8E5DF] rounded-xl p-6 sm:p-8 space-y-6 shadow-xs">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Blockchain Network
                </label>
                <input type="text" value="{{ $token->blockchainNetwork->display_name ?? 'N/A' }}" disabled
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm text-[#8A8F99] cursor-not-allowed" />
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Token Symbol
                </label>
                <input type="text" value="{{ $token->symbol }}" disabled
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm font-mono text-[#8A8F99] cursor-not-allowed" />
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Display Name
                </label>
                <input type="text" name="display_name" value="{{ old('display_name', $token->display_name) }}" required
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm text-[#121417] focus:outline-none focus:border-[#121417]" />
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Decimals
                </label>
                <input type="number" name="decimals" value="{{ old('decimals', $token->decimals) }}" min="0" max="36" required
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm font-mono text-[#121417] focus:outline-none focus:border-[#121417]" />
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Asset Class
                </label>
                <select name="is_native" class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm text-[#121417] focus:outline-none focus:border-[#121417]">
                    <option value="0" {{ old('is_native', $token->is_native ? '1' : '0') == '0' ? 'selected' : '' }}>Contract / Issued Token</option>
                    <option value="1" {{ old('is_native', $token->is_native ? '1' : '0') == '1' ? 'selected' : '' }}>Native Layer Token</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Current USD Price (Optional)
                </label>
                <input type="number" step="0.00000001" name="current_price_usd" value="{{ old('current_price_usd', $token->current_price_usd) }}" placeholder="e.g. 1.00"
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm font-mono text-[#121417] focus:outline-none focus:border-[#121417]" />
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Catalog State
                </label>
                <select name="is_enabled" class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm text-[#121417] focus:outline-none focus:border-[#121417]">
                    <option value="1" {{ old('is_enabled', $token->is_enabled ? '1' : '0') == '1' ? 'selected' : '' }}>Enabled in Selectors</option>
                    <option value="0" {{ old('is_enabled', $token->is_enabled ? '1' : '0') == '0' ? 'selected' : '' }}>Disabled (Hidden)</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                Contract Address / Issuer Account / Asset Identifier
            </label>
            <input type="text" name="contract_or_issuer" value="{{ old('contract_or_issuer', $token->contract_or_issuer) }}" placeholder="0x... or issuer address"
                class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-xs font-mono text-[#121417] focus:outline-none focus:border-[#121417]" />
        </div>

        <div>
            <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                Logo Image URL (Optional)
            </label>
            <input type="url" name="logo_url" value="{{ old('logo_url', $token->logo_url) }}" placeholder="https://..."
                class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-xs font-mono text-[#121417] focus:outline-none focus:border-[#121417]" />
        </div>

        <div class="pt-4 border-t border-[#E8E5DF] flex justify-end gap-3">
            <a href="{{ route('admin.tokens.index') }}"
                class="px-4 py-2 rounded-lg bg-[#FAF8F5] hover:bg-[#EAE7E0] border border-[#DDD9D0] text-xs font-heading font-medium text-[#52565E] transition">
                Cancel
            </a>
            <button type="submit"
                class="px-5 py-2 rounded-lg bg-[#121417] hover:bg-black text-white text-xs font-heading font-semibold transition cursor-pointer">
                Save Token Settings
            </button>
        </div>
    </form>

</div>
@endsection
