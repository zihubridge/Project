@extends('admin.layout')

@section('title', 'Edit Blockchain: ' . $network->display_name)
@section('header-tag', 'Network Settings')
@section('header-title', $network->display_name)

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <div class="flex items-center gap-3 pb-4 border-b border-[#E8E5DF]">
        <a href="{{ route('admin.blockchains.index') }}"
            class="px-2.5 py-1.5 rounded-lg border border-[#DDD9D0] bg-[#FAF8F5] text-xs font-heading font-medium text-[#52565E] hover:text-[#121417] transition">
            &larr; Back to Blockchains
        </a>
        <h1 class="text-xl font-heading font-semibold text-[#121417]">
            Edit Network: {{ $network->display_name }}
        </h1>
    </div>

    <form method="POST" action="{{ route('admin.blockchains.update', $network->id) }}" class="bg-white border border-[#E8E5DF] rounded-xl p-6 sm:p-8 space-y-6 shadow-xs">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Display Name
                </label>
                <input type="text" name="display_name" value="{{ old('display_name', $network->display_name) }}" required
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm text-[#121417] focus:outline-none focus:border-[#121417]" />
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Chain Family
                </label>
                <select name="chain_family" class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm text-[#121417] focus:outline-none focus:border-[#121417]">
                    @foreach(['evm', 'solana', 'stellar', 'ripple', 'bitcoin', 'near', 'ton', 'tron', 'cosmos', 'other'] as $fam)
                        <option value="{{ $fam }}" {{ old('chain_family', $network->chain_family) === $fam ? 'selected' : '' }}>{{ strtoupper($fam) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Native Token Symbol
                </label>
                <input type="text" name="native_symbol" value="{{ old('native_symbol', $network->native_symbol) }}"
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm font-mono text-[#121417] focus:outline-none focus:border-[#121417]" />
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    EVM Chain ID (Dec / Hex)
                </label>
                <input type="text" name="evm_chain_id" value="{{ old('evm_chain_id', $network->evm_chain_id) }}" placeholder="e.g. 1, 8453, 42161"
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm font-mono text-[#121417] focus:outline-none focus:border-[#121417]" />
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Mandatory Memo / Destination Tag
                </label>
                <select name="requires_memo_or_tag" class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm text-[#121417] focus:outline-none focus:border-[#121417]">
                    <option value="1" {{ old('requires_memo_or_tag', $network->requires_memo_or_tag ? '1' : '0') == '1' ? 'selected' : '' }}>Yes (Enforce Memo Validation)</option>
                    <option value="0" {{ old('requires_memo_or_tag', $network->requires_memo_or_tag ? '1' : '0') == '0' ? 'selected' : '' }}>No (Standard Address Only)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Network State
                </label>
                <select name="is_enabled" class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-sm text-[#121417] focus:outline-none focus:border-[#121417]">
                    <option value="1" {{ old('is_enabled', $network->is_enabled ? '1' : '0') == '1' ? 'selected' : '' }}>Enabled in Swap Selectors</option>
                    <option value="0" {{ old('is_enabled', $network->is_enabled ? '1' : '0') == '0' ? 'selected' : '' }}>Disabled (Hidden from Terminal)</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                Address Validation Regular Expression
            </label>
            <input type="text" name="address_regex" value="{{ old('address_regex', $network->address_regex) }}" placeholder="^0x[a-fA-F0-9]{40}$"
                class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-xs font-mono text-[#121417] focus:outline-none focus:border-[#121417]" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Tx Explorer URL Template
                </label>
                <input type="text" name="explorer_tx_url_pattern" value="{{ old('explorer_tx_url_pattern', $network->explorer_tx_url_pattern) }}" placeholder="https://etherscan.io/tx/{txHash}"
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-xs font-mono text-[#121417] focus:outline-none focus:border-[#121417]" />
            </div>

            <div>
                <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Address Explorer URL Template
                </label>
                <input type="text" name="explorer_address_url_pattern" value="{{ old('explorer_address_url_pattern', $network->explorer_address_url_pattern) }}" placeholder="https://etherscan.io/address/{address}"
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-xs font-mono text-[#121417] focus:outline-none focus:border-[#121417]" />
            </div>
        </div>

        <div class="pt-4 border-t border-[#E8E5DF] flex justify-end gap-3">
            <a href="{{ route('admin.blockchains.index') }}"
                class="px-4 py-2 rounded-lg bg-[#FAF8F5] hover:bg-[#EAE7E0] border border-[#DDD9D0] text-xs font-heading font-medium text-[#52565E] transition">
                Cancel
            </a>
            <button type="submit"
                class="px-5 py-2 rounded-lg bg-[#121417] hover:bg-black text-white text-xs font-heading font-semibold transition cursor-pointer">
                Save Blockchain Settings
            </button>
        </div>
    </form>

</div>
@endsection
