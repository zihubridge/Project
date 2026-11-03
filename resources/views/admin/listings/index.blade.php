@extends('admin.layout')

@section('title', 'Token Listing Submissions')
@section('header-tag', 'Commercial Pipeline')
@section('header-title', 'Token Listing Requests')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#E8E5DF]">
        <div>
            <h1 class="text-2xl font-heading font-semibold text-[#121417] tracking-tight">
                Paid Token Listing Pipeline
            </h1>
            <p class="text-xs text-[#52565E] mt-1">
                Review submissions from external projects, verify contract security and listing fee payments, and approve publication.
            </p>
        </div>
        <div class="text-xs font-mono text-[#6B7280]">
            Total Submissions: {{ $listings->total() }}
        </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="{{ route('admin.listings.index') }}" class="bg-white border border-[#E8E5DF] rounded-xl p-4 shadow-xs">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-heading font-semibold uppercase tracking-wider text-[#6B7280] mb-1">Review Status</label>
                <select name="status" class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-2.5 py-1.5 text-xs text-[#121417] focus:outline-none focus:border-[#121417]">
                    <option value="">All Review States</option>
                    @foreach(['DRAFT', 'PENDING_PAYMENT', 'PAID', 'UNDER_REVIEW', 'NEEDS_CHANGES', 'APPROVED', 'REJECTED', 'PUBLISHED', 'SUSPENDED'] as $st)
                        <option value="{{ $st }}" {{ ($filters['status'] ?? '') === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-heading font-semibold uppercase tracking-wider text-[#6B7280] mb-1">Payment Status</label>
                <select name="payment_status" class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-2.5 py-1.5 text-xs text-[#121417] focus:outline-none focus:border-[#121417]">
                    <option value="">All Payment States</option>
                    @foreach(['PENDING_PAYMENT', 'PAID', 'REFUNDED'] as $pst)
                        <option value="{{ $pst }}" {{ ($filters['payment_status'] ?? '') === $pst ? 'selected' : '' }}>{{ $pst }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-heading font-semibold uppercase tracking-wider text-[#6B7280] mb-1">Keyword Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Project or token..."
                    class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-2.5 py-1.5 text-xs text-[#121417] focus:outline-none focus:border-[#121417]" />
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                    class="flex-1 bg-[#121417] hover:bg-black text-white font-heading font-semibold py-1.5 px-3 rounded-lg text-xs transition cursor-pointer">
                    Filter
                </button>
                <a href="{{ route('admin.listings.index') }}"
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
                        <th class="py-3 px-4 font-semibold">Request ID</th>
                        <th class="py-3 px-4 font-semibold">Project & Token</th>
                        <th class="py-3 px-4 font-semibold">Network</th>
                        <th class="py-3 px-4 font-semibold">Listing Fee</th>
                        <th class="py-3 px-4 font-semibold">Payment</th>
                        <th class="py-3 px-4 font-semibold">Review Status</th>
                        <th class="py-3 px-4 font-semibold">Submitted</th>
                        <th class="py-3 px-4 text-right font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E8E5DF]">
                    @forelse($listings as $item)
                        <tr class="hover:bg-[#FAF8F5] transition">
                            <td class="py-3.5 px-4 font-mono font-medium text-[#121417]">#{{ $item->id }}</td>
                            <td class="py-3.5 px-4">
                                <span class="font-heading font-semibold text-[#121417] block">{{ $item->token_name }} ({{ $item->token_symbol }})</span>
                                <span class="text-[11px] text-[#6B7280] font-sans">{{ $item->project_name }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-sans text-[#121417]">
                                {{ $item->blockchainNetwork->display_name ?? 'N/A' }}
                            </td>
                            <td class="py-3.5 px-4 font-mono font-semibold text-[#121417]">
                                ${{ number_format($item->listing_fee_usd, 2) }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[11px] font-mono font-semibold
                                    {{ $item->payment_status === 'PAID' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                                    {{ $item->payment_status }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-0.5 rounded text-[11px] font-heading font-medium
                                    {{ in_array($item->review_status, ['PUBLISHED', 'APPROVED']) ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($item->review_status === 'REJECTED' ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-[#F0ECE1] text-[#2D3139] border border-[#DDD9D0]') }}">
                                    {{ $item->review_status }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[#6B7280]">
                                {{ $item->created_at->format('Y-m-d') }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('admin.listings.show', $item->id) }}"
                                    class="inline-flex items-center text-xs px-2.5 py-1 rounded-md bg-[#121417] text-white hover:bg-black font-heading font-medium transition">
                                    Review
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-[#8A8F99]">No token listing requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($listings->hasPages())
            <div class="p-4 border-t border-[#E8E5DF] bg-[#FAF8F5]">
                {{ $listings->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
