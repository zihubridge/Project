@extends('admin.layout')

@section('title', 'Review Listing Request #' . $listing->id)
@section('header-tag', 'Listing Pipeline')
@section('header-title', $listing->token_name . ' (' . $listing->token_symbol . ')')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">

    <div class="flex items-center justify-between pb-4 border-b border-[#E8E5DF]">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.listings.index') }}"
                class="px-2.5 py-1.5 rounded-lg border border-[#DDD9D0] bg-[#FAF8F5] text-xs font-heading font-medium text-[#52565E] hover:text-[#121417] transition">
                &larr; Back to Pipeline
            </a>
            <h1 class="text-xl font-heading font-semibold text-[#121417]">
                Listing Request #{{ $listing->id }}: {{ $listing->token_name }}
            </h1>
        </div>

        <span class="px-3 py-1 rounded text-xs font-heading font-semibold uppercase tracking-wider
            {{ in_array($listing->review_status, ['PUBLISHED', 'APPROVED']) ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-[#F0ECE1] text-[#2D3139] border border-[#DDD9D0]' }}">
            {{ $listing->review_status }}
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Submission Details (7 cols) -->
        <div class="lg:col-span-7 bg-white border border-[#E8E5DF] rounded-xl p-6 shadow-xs space-y-5">
            <h2 class="text-sm font-heading font-semibold uppercase tracking-wider text-[#121417]">
                Project & Asset Metadata
            </h2>

            <div class="grid grid-cols-2 gap-4 text-xs font-mono">
                <div>
                    <span class="text-[#6B7280] font-sans block mb-0.5">Project Name:</span>
                    <span class="text-[#121417] font-semibold font-sans">{{ $listing->project_name }}</span>
                </div>
                <div>
                    <span class="text-[#6B7280] font-sans block mb-0.5">Contact Email:</span>
                    <a href="mailto:{{ $listing->contact_email }}" class="text-[#121417] hover:underline">{{ $listing->contact_email }}</a>
                </div>
                <div>
                    <span class="text-[#6B7280] font-sans block mb-0.5">Token Name & Symbol:</span>
                    <span class="text-[#121417] font-semibold">{{ $listing->token_name }} ({{ $listing->token_symbol }})</span>
                </div>
                <div>
                    <span class="text-[#6B7280] font-sans block mb-0.5">Target Blockchain:</span>
                    <span class="text-[#121417] font-sans">{{ $listing->blockchainNetwork->display_name ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-[#6B7280] font-sans block mb-0.5">Decimals:</span>
                    <span class="text-[#121417]">{{ $listing->decimals }}</span>
                </div>
                <div>
                    <span class="text-[#6B7280] font-sans block mb-0.5">Website:</span>
                    @if($listing->website_url)
                        <a href="{{ $listing->website_url }}" target="_blank" class="text-[#0F6B48] underline truncate block">{{ $listing->website_url }} &nearr;</a>
                    @else
                        <span class="text-[#8A8F99]">-</span>
                    @endif
                </div>
            </div>

            <div class="text-xs">
                <span class="text-[#6B7280] block mb-1">Contract / Issuer Identifier:</span>
                <div class="p-2.5 rounded bg-[#FAF8F5] border border-[#E8E5DF] font-mono text-[#121417] break-all select-all">
                    {{ $listing->contract_or_issuer ?: 'Native' }}
                </div>
            </div>

            @if($listing->description)
            <div class="text-xs">
                <span class="text-[#6B7280] block mb-1">Project Description:</span>
                <p class="p-3 rounded bg-[#FAF8F5] border border-[#E8E5DF] text-[#52565E] leading-relaxed">
                    {{ $listing->description }}
                </p>
            </div>
            @endif

            @if($listing->publishedCryptoAsset)
            <div class="p-3.5 rounded-lg bg-emerald-50 border border-emerald-200 text-xs text-emerald-900">
                <span class="font-heading font-semibold block mb-0.5">Catalog Status: Live in Public Catalog</span>
                <span>Linked Asset ID: #{{ $listing->publishedCryptoAsset->id }}</span>
            </div>
            @endif
        </div>

        <!-- Review Decision & Payment Console (5 cols) -->
        <div class="lg:col-span-5 bg-white border border-[#E8E5DF] rounded-xl p-6 shadow-xs">
            <h2 class="text-sm font-heading font-semibold uppercase tracking-wider text-[#121417] mb-4">
                Review Decision
            </h2>

            <form method="POST" action="{{ route('admin.listings.updateStatus', $listing->id) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1">
                        Review Status
                    </label>
                    <select name="review_status" class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3 py-2 text-xs font-heading font-medium text-[#121417] focus:outline-none focus:border-[#121417]">
                        @foreach(['DRAFT', 'PENDING_PAYMENT', 'PAID', 'UNDER_REVIEW', 'NEEDS_CHANGES', 'APPROVED', 'REJECTED', 'PUBLISHED', 'SUSPENDED'] as $st)
                            <option value="{{ $st }}" {{ old('review_status', $listing->review_status) === $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1">
                        Listing Fee (USD)
                    </label>
                    <input type="number" step="0.01" name="listing_fee_usd" value="{{ old('listing_fee_usd', $listing->listing_fee_usd) }}" required
                        class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3 py-2 text-xs font-mono text-[#121417] focus:outline-none focus:border-[#121417]" />
                </div>

                <div>
                    <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1">
                        Payment Status
                    </label>
                    <select name="payment_status" class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3 py-2 text-xs text-[#121417] focus:outline-none focus:border-[#121417]">
                        @foreach(['PENDING_PAYMENT', 'PAID', 'REFUNDED'] as $pst)
                            <option value="{{ $pst }}" {{ old('payment_status', $listing->payment_status) === $pst ? 'selected' : '' }}>{{ $pst }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1">
                        Payment Transaction Hash (Optional)
                    </label>
                    <input type="text" name="payment_tx_hash" value="{{ old('payment_tx_hash', $listing->payment_tx_hash) }}" placeholder="0x..."
                        class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3 py-2 text-xs font-mono text-[#121417] focus:outline-none focus:border-[#121417]" />
                </div>

                <div>
                    <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1">
                        Reviewer Internal Notes
                    </label>
                    <textarea name="reviewer_notes" rows="3" placeholder="Contract audited, liquidity checked..."
                        class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3 py-2 text-xs text-[#121417] focus:outline-none focus:border-[#121417]">{{ old('reviewer_notes', $listing->reviewer_notes) }}</textarea>
                </div>

                <div class="pt-2">
                    <label class="flex items-start gap-2 text-xs text-[#52565E] cursor-pointer">
                        <input type="checkbox" name="publish_to_catalog" value="1" class="mt-0.5 rounded border-[#DDD9D0]" />
                        <span>Publish directly into active public token catalog when status is set to <strong>PUBLISHED</strong>.</span>
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full bg-[#121417] hover:bg-black text-white font-heading font-semibold py-2.5 rounded-lg text-xs transition cursor-pointer">
                        Save Review Decision
                    </button>
                </div>
            </form>
        </div>

    </div>

</div>
@endsection
