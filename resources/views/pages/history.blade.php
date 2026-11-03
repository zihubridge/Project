@extends('layout.master')

@section('content')
<main class="min-h-screen bg-[#FBF9F5] text-[#121417] font-sans antialiased selection:bg-[#121417] selection:text-white py-16 md:py-24 px-4 sm:px-6 lg:px-8" id="swapHistoryPage">
    <div class="max-w-6xl mx-auto space-y-8">
        
        <!-- Header & Filter Form -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-6 border-b border-[#E8E5DF]">
            <div>
                <span class="text-xs font-heading font-medium tracking-wide text-[#6B7280] uppercase block mb-2">
                    Ledger & Activity
                </span>
                <h1 class="text-2xl sm:text-3xl md:text-4xl font-heading font-semibold tracking-[-0.03em] text-[#121417]">
                    Swap Explorer & History
                </h1>
                <p class="text-sm text-[#52565E] mt-1.5 font-light">
                    Query public settlement records across supported chains or review orders initiated in this browser session.
                </p>
            </div>

            <form id="walletHistorySearchForm" class="flex gap-2 w-full md:w-auto shrink-0">
                <input id="walletHistorySearchInput" type="text"
                    placeholder="Filter by wallet or recipient address..."
                    class="w-full md:w-80 bg-white border border-[#DDD9D0] rounded-lg px-3.5 py-2 text-xs md:text-sm text-[#121417] font-mono placeholder-[#8A8F99] focus:outline-none focus:border-[#121417] transition" />
                <button type="submit"
                    class="px-4 py-2 rounded-lg bg-[#121417] hover:bg-black text-xs md:text-sm font-heading font-medium text-white transition cursor-pointer shrink-0">
                    Search
                </button>
            </form>
        </div>

        <!-- Table Container -->
        <div class="bg-white border border-[#E8E5DF] rounded-xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs md:text-sm">
                    <thead class="bg-[#F7F5F0] text-[#52565E] font-heading font-medium uppercase text-[11px] tracking-wider border-b border-[#E8E5DF]">
                        <tr>
                            <th class="py-3.5 px-4 font-semibold">Swap UUID</th>
                            <th class="py-3.5 px-4 font-semibold">Deposit Asset</th>
                            <th class="py-3.5 px-4 font-semibold">Receiving Asset</th>
                            <th class="py-3.5 px-4 font-semibold">Status</th>
                            <th class="py-3.5 px-4 font-semibold">Timestamp</th>
                            <th class="py-3.5 px-4 text-right font-semibold">Order</th>
                        </tr>
                    </thead>
                    <tbody id="swapHistoryTableBody" class="divide-y divide-[#E8E5DF]">
                        <tr>
                            <td colspan="6" class="py-12 text-center text-[#8A8F99] font-sans">
                                Loading swap history...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footnote / Transparency info -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center text-xs text-[#8A8F99] pt-2">
            <p>Recent swaps are locally indexed in your browser storage. Enter any destination address to verify on-chain records.</p>
            <p class="mt-1 sm:mt-0 font-mono text-[11px]">NEAR Intents Solver Protocol</p>
        </div>

    </div>
</main>
@endsection
