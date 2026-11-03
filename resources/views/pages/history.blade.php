@extends('layout.master')

@section('content')
<main class="min-h-screen bg-[#060914] text-white py-10 px-4 md:px-8" id="swapHistoryPage">
    <div class="max-w-5xl mx-auto space-y-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-extrabold">Swap Explorer & History</h1>
                <p class="text-sm text-slate-400 mt-1">
                    Search swaps by wallet address or view your recent swaps saved in this browser.
                </p>
            </div>

            <form id="walletHistorySearchForm" class="flex gap-2 w-full md:w-auto">
                <input id="walletHistorySearchInput" type="text"
                    placeholder="Filter by wallet / recipient address..."
                    class="w-full md:w-80 bg-[#0D1326] border border-slate-700 rounded-xl px-3.5 py-2 text-xs md:text-sm text-white font-mono placeholder-slate-500 focus:outline-none focus:border-cyan-500" />
                <button type="submit"
                    class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-xs md:text-sm font-semibold text-white transition cursor-pointer">
                    Search
                </button>
            </form>
        </div>

        <div class="bg-[#0D1326] border border-slate-800 rounded-3xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs md:text-sm">
                    <thead class="bg-[#090E1F] text-slate-400 uppercase text-[11px] border-b border-slate-800">
                        <tr>
                            <th class="py-3.5 px-4">Swap UUID</th>
                            <th class="py-3.5 px-4">From</th>
                            <th class="py-3.5 px-4">To</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4">Created</th>
                            <th class="py-3.5 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="swapHistoryTableBody" class="divide-y divide-slate-800/70">
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">Loading swap history...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
@endsection

