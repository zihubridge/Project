@extends('layout.master')

@section('content')
<main class="min-h-screen bg-[#060914] text-white py-10 px-4 md:px-8">
    <div class="max-w-6xl mx-auto">
        <!-- Hero & Swap Terminal Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- Left Column: Value Proposition & Live Network Capabilities -->
            <div class="lg:col-span-5 space-y-6 lg:pt-4">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-cyan-500/10 border border-cyan-500/30 text-cyan-300 text-xs font-medium">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Non-Custodial Cross-Chain Swaps Powered by Intent Solvers</span>
                </div>

                <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight leading-tight">
                    Swap Any Supported Asset
                    <span class="bg-gradient-to-r from-cyan-400 via-blue-400 to-indigo-400 bg-clip-text text-transparent">
                        Across Blockchains
                    </span>
                </h1>

                <p class="text-slate-400 text-sm md:text-base leading-relaxed">
                    Exchange native and token assets across supported networks in a single non-custodial flow.
                    Connect your wallet or transfer from any self-custody wallet with guaranteed minimum output protection and automatic origin-chain refunds.
                </p>

                <div class="grid grid-cols-3 gap-3 pt-2">
                    <div class="p-4 rounded-2xl bg-[#0D1326] border border-slate-800/80">
                        <div class="text-cyan-400 text-xl font-bold" id="statActiveNetworks">--</div>
                        <div class="text-xs text-slate-400 mt-1">Supported Networks</div>
                    </div>
                    <div class="p-4 rounded-2xl bg-[#0D1326] border border-slate-800/80">
                        <div class="text-cyan-400 text-xl font-bold" id="statActiveAssets">--</div>
                        <div class="text-xs text-slate-400 mt-1">Swappable Assets</div>
                    </div>
                    <div class="p-4 rounded-2xl bg-[#0D1326] border border-slate-800/80">
                        <div class="text-emerald-400 text-xl font-bold">100%</div>
                        <div class="text-xs text-slate-400 mt-1">Non-Custodial</div>
                    </div>
                </div>

                <!-- Quick Track Existing Swap Card -->
                <div class="p-5 rounded-2xl bg-[#0D1326]/90 border border-slate-800/80">
                    <label for="quickTrackSwapInput" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                        Track an Existing Swap by UUID
                    </label>
                    <form id="quickTrackSwapForm" class="flex gap-2">
                        <input id="quickTrackSwapInput" type="text"
                            placeholder="Paste Swap UUID (e.g. a1b2c3d4-...)"
                            class="flex-1 bg-[#070B19] border border-slate-700/80 rounded-xl px-3.5 py-2 text-xs md:text-sm text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500" />
                        <button type="submit"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-cyan-300 transition cursor-pointer">
                            Track
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Column: Interactive Cross-Chain Swap Terminal -->
            <div class="lg:col-span-7">
                <div id="swapTerminalApp" class="bg-[#0D1326] border border-slate-800 rounded-3xl p-5 md:p-7 shadow-2xl relative">

                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="text-lg md:text-xl font-bold text-white">Cross-Chain Swap</h2>
                            <p class="text-xs text-slate-400">Dynamic routes & live solver quotes</p>
                        </div>

                        <!-- Slippage Selector -->
                        <div class="flex items-center gap-1.5 bg-[#070B19] border border-slate-800 rounded-xl p-1 text-xs">
                            <span class="text-slate-400 px-2">Slippage:</span>
                            <button type="button" data-slippage-bps="50" class="slippage-btn px-2.5 py-1 rounded-lg text-slate-300 hover:text-white cursor-pointer">0.5%</button>
                            <button type="button" data-slippage-bps="100" class="slippage-btn px-2.5 py-1 rounded-lg bg-cyan-500/20 text-cyan-300 font-semibold cursor-pointer">1.0%</button>
                            <button type="button" data-slippage-bps="200" class="slippage-btn px-2.5 py-1 rounded-lg text-slate-300 hover:text-white cursor-pointer">2.0%</button>
                        </div>
                    </div>

                    <!-- Source Asset Box -->
                    <div class="p-4 rounded-2xl bg-[#070B19] border border-slate-800/90 space-y-3">
                        <div class="flex items-center justify-between text-xs text-slate-400">
                            <span class="font-medium">You Pay</span>
                            <span id="sourceAssetPriceLabel" class="text-slate-500"></span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                            <div class="sm:col-span-5 flex flex-col gap-1.5">
                                <select id="sourceNetworkSelect"
                                    class="w-full bg-[#11182E] border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-cyan-500">
                                    <option value="">Loading networks...</option>
                                </select>
                                <select id="sourceAssetSelect"
                                    class="w-full bg-[#11182E] border border-slate-700/80 rounded-xl px-3 py-2.5 text-sm font-semibold text-white focus:outline-none focus:border-cyan-500">
                                    <option value="">Select token...</option>
                                </select>
                            </div>

                            <div class="sm:col-span-7">
                                <input id="swapAmountInput" type="text" inputmode="decimal" value="1"
                                    placeholder="0.00"
                                    class="w-full bg-transparent text-right text-2xl md:text-3xl font-bold text-white placeholder-slate-600 focus:outline-none" />
                                <div id="amountInUsdEstimate" class="text-right text-xs text-slate-500 mt-1"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Flip Route Button -->
                    <div class="flex justify-center -my-3 relative z-10">
                        <button id="flipSwapDirectionBtn" type="button" title="Reverse source and destination"
                            class="w-10 h-10 rounded-xl bg-[#151F38] hover:bg-cyan-500/20 border border-slate-700 hover:border-cyan-400 text-cyan-400 flex items-center justify-center text-lg shadow-md transition cursor-pointer">
                            <ion-icon name="swap-vertical-outline"></ion-icon>
                        </button>
                    </div>

                    <!-- Destination Asset Box -->
                    <div class="p-4 rounded-2xl bg-[#070B19] border border-slate-800/90 space-y-3">
                        <div class="flex items-center justify-between text-xs text-slate-400">
                            <span class="font-medium">You Receive (Estimated)</span>
                            <span id="destAssetPriceLabel" class="text-slate-500"></span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                            <div class="sm:col-span-5 flex flex-col gap-1.5">
                                <select id="destNetworkSelect"
                                    class="w-full bg-[#11182E] border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-cyan-500">
                                    <option value="">Loading networks...</option>
                                </select>
                                <select id="destAssetSelect"
                                    class="w-full bg-[#11182E] border border-slate-700/80 rounded-xl px-3 py-2.5 text-sm font-semibold text-white focus:outline-none focus:border-cyan-500">
                                    <option value="">Select token...</option>
                                </select>
                            </div>

                            <div class="sm:col-span-7">
                                <input id="estimatedOutputInput" type="text" readonly placeholder="0.00"
                                    class="w-full bg-transparent text-right text-2xl md:text-3xl font-bold text-emerald-400 placeholder-slate-600 focus:outline-none" />
                                <div id="amountOutUsdEstimate" class="text-right text-xs text-slate-500 mt-1"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Recipient & Refund Wallet Addresses Section -->
                    <div class="mt-4 p-4 rounded-2xl bg-[#070B19] border border-slate-800/90 space-y-3.5">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="recipientAddressInput" class="text-xs font-medium text-slate-300">
                                    Destination Recipient Address (<span id="recipientNetworkLabel" class="text-cyan-400">Destination Chain</span>)
                                </label>
                                <span id="recipientValidationBadge" class="text-xs"></span>
                            </div>
                            <input id="recipientAddressInput" type="text"
                                placeholder="Enter destination wallet address"
                                class="w-full bg-[#11182E] border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs md:text-sm text-white font-mono placeholder-slate-500 focus:outline-none focus:border-cyan-500" />
                        </div>

                        <div id="recipientMemoContainer" class="hidden">
                            <label for="recipientMemoInput" class="block text-xs font-medium text-slate-300 mb-1">
                                Destination Memo / Tag (Optional for self-custody, required for exchanges)
                            </label>
                            <input id="recipientMemoInput" type="text"
                                placeholder="Optional Memo or Destination Tag"
                                class="w-full bg-[#11182E] border border-slate-700/80 rounded-xl px-3.5 py-2 text-xs text-white font-mono placeholder-slate-500 focus:outline-none focus:border-cyan-500" />
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="refundAddressInput" class="text-xs font-medium text-slate-300">
                                    Origin Refund Address (<span id="refundNetworkLabel" class="text-cyan-400">Source Chain</span>)
                                </label>
                                <button type="button" id="useConnectedWalletForRefundBtn"
                                    class="hidden text-xs text-cyan-400 hover:underline cursor-pointer">
                                    Use Connected Wallet
                                </button>
                            </div>
                            <input id="refundAddressInput" type="text"
                                placeholder="Enter origin wallet address for automatic refund protection"
                                class="w-full bg-[#11182E] border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs md:text-sm text-white font-mono placeholder-slate-500 focus:outline-none focus:border-cyan-500" />
                            <p id="refundValidationMessage" class="text-xs text-slate-500 mt-1">
                                Used automatically by the protocol if the swap expires or exceeds slippage.
                            </p>
                        </div>
                    </div>

                    <!-- Quote Breakdown Card -->
                    <div id="quoteDetailsCard" class="hidden mt-4 p-4 rounded-2xl bg-[#091124] border border-cyan-500/30 text-xs space-y-2">
                        <div class="flex items-center justify-between text-slate-300">
                            <span>Execution Provider</span>
                            <span id="quoteProviderBadge" class="px-2.5 py-0.5 rounded-full bg-cyan-500/15 text-cyan-300 font-semibold"></span>
                        </div>
                        <div class="flex items-center justify-between text-slate-300">
                            <span>Exchange Rate</span>
                            <span id="quoteRateText" class="font-mono text-white"></span>
                        </div>
                        <div class="flex items-center justify-between text-slate-300">
                            <span>Minimum Guaranteed Output</span>
                            <span id="quoteMinOutText" class="font-mono text-emerald-400 font-semibold"></span>
                        </div>
                        <div class="flex items-center justify-between text-slate-300">
                            <span>Protocol & Routing Fee</span>
                            <span id="quoteFeeText" class="font-mono text-slate-200"></span>
                        </div>
                        <div class="flex items-center justify-between text-slate-300">
                            <span>Estimated Settlement Time</span>
                            <span id="quoteEtaText" class="text-slate-200"></span>
                        </div>
                    </div>

                    <!-- Error / Status Alert -->
                    <div id="swapErrorBanner" class="hidden mt-4 p-3.5 rounded-xl bg-rose-950/60 border border-rose-500/40 text-xs text-rose-300"></div>

                    <!-- Action Buttons -->
                    <div class="mt-5 flex flex-col sm:flex-row gap-3">
                        <button id="refreshQuoteBtn" type="button"
                            class="sm:w-1/3 py-3.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-sm font-semibold text-slate-200 transition cursor-pointer">
                            Get Live Quote
                        </button>
                        <button id="initiateSwapBtn" type="button" disabled
                            class="sm:w-2/3 py-3.5 px-6 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 disabled:opacity-40 disabled:cursor-not-allowed text-sm font-bold text-white shadow-lg shadow-cyan-500/20 transition cursor-pointer">
                            Review & Initiate Swap
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- How Non-Custodial Swaps Work -->
        <section class="mt-16 pt-10 border-t border-slate-800/80">
            <h2 class="text-2xl font-bold text-center mb-8">How ZihuBridge Works</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-6 rounded-2xl bg-[#0D1326] border border-slate-800/80">
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/15 text-cyan-400 flex items-center justify-center font-bold mb-4">1</div>
                    <h3 class="font-semibold text-lg mb-2">Select Any Supported Pair</h3>
                    <p class="text-sm text-slate-400">
                        Choose your source blockchain, input asset, destination blockchain, and target asset from the live provider catalog.
                    </p>
                </div>
                <div class="p-6 rounded-2xl bg-[#0D1326] border border-slate-800/80">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/15 text-blue-400 flex items-center justify-center font-bold mb-4">2</div>
                    <h3 class="font-semibold text-lg mb-2">Lock Quote & Send Deposit</h3>
                    <p class="text-sm text-slate-400">
                        Sign directly with your connected wallet or send the exact input amount to the unique intent deposit address.
                    </p>
                </div>
                <div class="p-6 rounded-2xl bg-[#0D1326] border border-slate-800/80">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/15 text-emerald-400 flex items-center justify-center font-bold mb-4">3</div>
                    <h3 class="font-semibold text-lg mb-2">Verifiable Settlement or Refund</h3>
                    <p class="text-sm text-slate-400">
                        Solvers settle the destination asset directly to your recipient wallet—or automatically refund your origin address if conditions aren't met.
                    </p>
                </div>
            </div>
        </section>
    </div>
</main>
@endsection

