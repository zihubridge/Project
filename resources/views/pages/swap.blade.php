@extends('layout.master')

@section('content')
<main class="min-h-screen bg-[#FBF9F5] text-[#121417] antialiased">

    <!-- =======================================================================
         HERO & SWAP EXECUTION TERMINAL SECTION
         Asymmetrical editorial composition: high-readability narrative + precision terminal
         ======================================================================= -->
    <section class="border-b border-[#E8E5DF] pt-12 pb-16 sm:pt-20 sm:pb-24 lg:pt-24 lg:pb-28">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-14 items-start">

                <!-- Left Column: Editorial Value Proposition & Specifications -->
                <div class="lg:col-span-5 flex flex-col justify-between">
                    
                    <div>
                        <!-- Calm Editorial Status Line -->
                        <div class="inline-flex items-center gap-2 text-xs text-[#52565E] pb-3 border-b border-[#E8E5DF] mb-6 sm:mb-8">
                            <span class="font-medium text-[#121417]">NEAR Intents solver network</span>
                            <span class="text-[#8A8F99]">/</span>
                            <span>Non-custodial routing</span>
                        </div>

                        <!-- Main Headline: Expressive, Editorial, Deliberate Font Weight -->
                        <h1 class="text-4xl sm:text-5xl lg:text-[54px] font-normal text-[#121417] leading-[1.1] tracking-[-0.035em]">
                            Cross-chain liquidity,
                            <span class="font-semibold block text-[#121417] mt-1">directly settled</span>
                        </h1>

                        <!-- Readable Narrative Body Text -->
                        <p class="text-base sm:text-lg text-[#52565E] leading-relaxed mt-6 max-w-lg font-normal">
                            ZihuBridge coordinates non-custodial swaps across independent blockchains. Competitive intent solvers fulfill orders directly to your wallet with guaranteed minimum output and automated origin-chain refunds.
                        </p>

                        <!-- Primary Direct Action -->
                        <div class="flex flex-wrap items-center gap-4 mt-8">
                            <a href="#swapTerminalApp"
                                class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-[#121417] text-white text-sm font-medium hover:bg-black transition shadow-sm cursor-pointer">
                                <span>Open Swap Terminal</span>
                                <ion-icon name="arrow-down-outline" class="text-xs"></ion-icon>
                            </a>
                            <a href="{{ route('swap.history') }}"
                                class="inline-flex items-center gap-1.5 text-sm font-medium text-[#121417] hover:underline underline-offset-4 cursor-pointer">
                                <span>Swap Explorer &amp; History &rarr;</span>
                            </a>
                        </div>
                    </div>

                    <!-- Editorial Specifications Ledger (Unboxed Typographic Rhythm) -->
                    <div class="mt-12 pt-8 border-t border-[#E8E5DF] grid grid-cols-2 sm:grid-cols-3 gap-y-6 gap-x-8">
                        <div>
                            <div class="text-xs text-[#8A8F99] font-normal">Execution Model</div>
                            <div class="text-sm font-medium text-[#121417] mt-1">Intent Solvers (RFQ)</div>
                        </div>
                        <div>
                            <div class="text-xs text-[#8A8F99] font-normal">Custody Risk</div>
                            <div class="text-sm font-medium text-[#121417] mt-1">0% (Self-Custody)</div>
                        </div>
                        <div>
                            <div class="text-xs text-[#8A8F99] font-normal">Slippage Guarantee</div>
                            <div class="text-sm font-medium text-[#121417] mt-1">Locked Floor (<code class="text-xs font-mono">min_out</code>)</div>
                        </div>
                        <div>
                            <div class="text-xs text-[#8A8F99] font-normal">Live Catalog</div>
                            <div class="text-sm font-medium text-[#121417] mt-1"><span id="statActiveNetworks">--</span> Chains · <span id="statActiveAssets">--</span> Assets</div>
                        </div>
                        <div>
                            <div class="text-xs text-[#8A8F99] font-normal">Platform Fee</div>
                            <div class="text-sm font-medium text-[#121417] mt-1">1.0% Planned</div>
                        </div>
                        <div>
                            <div class="text-xs text-[#8A8F99] font-normal">Refund Policy</div>
                            <div class="text-sm font-medium text-[#121417] mt-1">Origin-Chain Automatic</div>
                        </div>
                    </div>

                    <!-- Minimal Clean Order UUID Lookup -->
                    <div class="mt-8 pt-6 border-t border-[#E8E5DF]">
                        <form id="quickTrackSwapForm" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <label for="quickTrackSwapInput" class="text-xs text-[#52565E] whitespace-nowrap">
                                Track an active order:
                            </label>
                            <div class="flex-1 flex gap-2">
                                <input id="quickTrackSwapInput" type="text"
                                    placeholder="Paste 36-char order UUID..."
                                    class="flex-1 bg-transparent border-b border-[#C8C4BC] focus:border-[#121417] py-1.5 px-1 text-xs sm:text-sm font-mono text-[#121417] placeholder-[#A09D95] focus:outline-none transition" />
                                <button type="submit"
                                    class="px-3 py-1.5 text-xs font-medium text-[#121417] hover:bg-[#121417] hover:text-white rounded border border-[#C8C4BC] transition cursor-pointer">
                                    Lookup &rarr;
                                </button>
                            </div>
                        </form>
                    </div>

                </div>

                <!-- Right Column: Focused, Dark Financial Swap Terminal -->
                <div class="lg:col-span-7">
                    <div id="swapTerminalApp" class="bg-[#10131A] text-white border border-[#1E2433] rounded-2xl p-6 sm:p-8 shadow-2xl relative">

                        <!-- Terminal Title & Tactical Slippage Switch -->
                        <div class="flex flex-wrap items-center justify-between gap-4 pb-5 mb-5 border-b border-[#1E2433]">
                            <div>
                                <div>
                                    <h2 class="text-base sm:text-lg font-medium tracking-tight text-white font-heading">Cross-Chain Execution Terminal</h2>
                                </div>
                                <p class="text-xs text-[#8A8F99] mt-0.5 font-normal">NEAR Intents solver routing with locked minimum output floor</p>
                            </div>

                            <!-- Slippage Segmented Control -->
                            <div class="flex items-center gap-1 bg-[#161B26] border border-[#232B3D] rounded-lg p-1 text-xs">
                                <span class="text-[#8A8F99] px-2 font-mono text-xs">Slippage</span>
                                <button type="button" data-slippage-bps="50"
                                    class="slippage-btn px-2.5 py-1 rounded text-[#8A8F99] hover:text-white transition font-mono text-xs cursor-pointer">0.5%</button>
                                <button type="button" data-slippage-bps="100"
                                    class="slippage-btn px-2.5 py-1 rounded bg-white text-[#10131A] font-semibold font-mono text-xs cursor-pointer">1.0%</button>
                                <button type="button" data-slippage-bps="200"
                                    class="slippage-btn px-2.5 py-1 rounded text-[#8A8F99] hover:text-white transition font-mono text-xs cursor-pointer">2.0%</button>
                            </div>
                        </div>

                        <!-- Source Asset Input Block -->
                        <div class="bg-[#151922] border border-[#222938] rounded-xl p-4 sm:p-5 space-y-3">
                            <div class="flex items-center justify-between text-xs text-[#8A8F99]">
                                <span class="font-medium text-[#C0C4CC]">You Pay</span>
                                <span id="sourceAssetPriceLabel" class="font-mono text-xs text-[#8A8F99]"></span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                                <div class="sm:col-span-6 flex flex-col gap-2">
                                    <label for="sourceNetworkSelect" class="sr-only">Source Network</label>
                                    <select id="sourceNetworkSelect"
                                        class="w-full bg-[#1C2230] border border-[#2A3348] rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-slate-400 font-medium">
                                        <option value="">Loading networks...</option>
                                    </select>
                                    <label for="sourceAssetSelect" class="sr-only">Source Asset</label>
                                    <select id="sourceAssetSelect"
                                        class="w-full bg-[#1C2230] border border-[#2A3348] rounded-lg px-3 py-2.5 text-sm font-semibold text-white focus:outline-none focus:border-slate-400">
                                        <option value="">Select token...</option>
                                    </select>
                                </div>

                                <div class="sm:col-span-6 text-right">
                                    <label for="swapAmountInput" class="sr-only">Input Amount</label>
                                    <input id="swapAmountInput" type="text" inputmode="decimal" value="1"
                                        placeholder="0.00"
                                        class="w-full bg-transparent text-right text-2xl sm:text-3xl font-mono text-white placeholder-slate-600 focus:outline-none" />
                                    <div id="amountInUsdEstimate" class="text-right text-xs font-mono text-[#8A8F99] mt-1"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Minimal Route Flip Action -->
                        <div class="flex justify-center -my-3 relative z-10">
                            <button id="flipSwapDirectionBtn" type="button" aria-label="Reverse swap direction"
                                class="w-8 h-8 rounded-full bg-[#1C2230] hover:bg-[#252D3F] border border-[#2E374D] text-slate-300 hover:text-white flex items-center justify-center text-sm shadow-md transition cursor-pointer">
                                <ion-icon name="swap-vertical-outline"></ion-icon>
                            </button>
                        </div>

                        <!-- Destination Asset Input Block -->
                        <div class="bg-[#151922] border border-[#222938] rounded-xl p-4 sm:p-5 space-y-3">
                            <div class="flex items-center justify-between text-xs text-[#8A8F99]">
                                <span class="font-medium text-[#C0C4CC]">You Receive (Estimated)</span>
                                <span id="destAssetPriceLabel" class="font-mono text-xs text-[#8A8F99]"></span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                                <div class="sm:col-span-6 flex flex-col gap-2">
                                    <label for="destNetworkSelect" class="sr-only">Destination Network</label>
                                    <select id="destNetworkSelect"
                                        class="w-full bg-[#1C2230] border border-[#2A3348] rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-slate-400 font-medium">
                                        <option value="">Loading networks...</option>
                                    </select>
                                    <label for="destAssetSelect" class="sr-only">Destination Asset</label>
                                    <select id="destAssetSelect"
                                        class="w-full bg-[#1C2230] border border-[#2A3348] rounded-lg px-3 py-2.5 text-sm font-semibold text-white focus:outline-none focus:border-slate-400">
                                        <option value="">Select token...</option>
                                    </select>
                                </div>

                                <div class="sm:col-span-6 text-right">
                                    <label for="estimatedOutputInput" class="sr-only">Estimated Output Amount</label>
                                    <input id="estimatedOutputInput" type="text" readonly placeholder="0.00"
                                        class="w-full bg-transparent text-right text-2xl sm:text-3xl font-mono text-emerald-400 placeholder-slate-600 focus:outline-none" />
                                    <div id="amountOutUsdEstimate" class="text-right text-xs font-mono text-[#8A8F99] mt-1"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Address Specifications (Integrated cleanly without box-nesting) -->
                        <div class="mt-5 pt-5 border-t border-[#1E2433] space-y-4 text-xs">
                            <!-- Destination Address -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label for="recipientAddressInput" class="text-slate-300 font-medium">
                                        Destination Recipient (<span id="recipientNetworkLabel" class="text-slate-100 font-mono">Destination Chain</span>)
                                    </label>
                                    <span id="recipientValidationBadge" class="font-mono text-xs"></span>
                                </div>
                                <input id="recipientAddressInput" type="text"
                                    placeholder="Enter destination wallet address"
                                    class="w-full bg-[#151922] border border-[#222938] rounded-lg px-3 py-2.5 text-xs sm:text-sm text-white font-mono placeholder-slate-500 focus:outline-none focus:border-slate-400" />
                            </div>

                            <!-- Optional Memo / Destination Tag -->
                            <div id="recipientMemoContainer" class="hidden">
                                <label for="recipientMemoInput" class="block text-slate-300 font-medium mb-1.5">
                                    Destination Memo / Tag (Mandatory for exchange deposits)
                                </label>
                                <input id="recipientMemoInput" type="text"
                                    placeholder="Required memo or numeric tag"
                                    class="w-full bg-[#151922] border border-[#222938] rounded-lg px-3 py-2 text-xs text-white font-mono placeholder-slate-500 focus:outline-none focus:border-slate-400" />
                            </div>

                            <!-- Origin Refund Address -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label for="refundAddressInput" class="text-slate-300 font-medium">
                                        Origin Refund Address (<span id="refundNetworkLabel" class="text-slate-100 font-mono">Source Chain</span>)
                                    </label>
                                    <button type="button" id="useConnectedWalletForRefundBtn"
                                        class="hidden text-slate-300 hover:text-white underline cursor-pointer font-mono">
                                        Use Connected Wallet
                                    </button>
                                </div>
                                <input id="refundAddressInput" type="text"
                                    placeholder="Source address for automatic refund protection"
                                    class="w-full bg-[#151922] border border-[#222938] rounded-lg px-3 py-2.5 text-xs sm:text-sm text-white font-mono placeholder-slate-500 focus:outline-none focus:border-slate-400" />
                                <p id="refundValidationMessage" class="text-[11px] text-[#8A8F99] mt-1 font-mono">
                                    Origin refund target if solver order exceeds slippage or expiration window.
                                </p>
                            </div>
                        </div>

                        <!-- Live Quote Line-Item Breakdown (Financial Receipt Style) -->
                        <div id="quoteDetailsCard" class="hidden mt-5 p-4 rounded-xl bg-[#151922] border border-[#222938] text-xs space-y-2.5 font-mono">
                            <div class="flex items-center justify-between text-[#A0A5B0]">
                                <span>Execution Provider</span>
                                <span id="quoteProviderBadge" class="text-white font-medium"></span>
                            </div>
                            <div class="flex items-center justify-between text-[#A0A5B0]">
                                <span>Exchange Rate</span>
                                <span id="quoteRateText" class="text-white"></span>
                            </div>
                            <div class="flex items-center justify-between text-[#A0A5B0]">
                                <span>Guaranteed Output Floor</span>
                                <span id="quoteMinOutText" class="text-emerald-400 font-medium"></span>
                            </div>
                            <div class="flex items-center justify-between text-[#A0A5B0]">
                                <span>Platform Fee (Planned)</span>
                                <span id="quoteFeeText" class="text-white"></span>
                            </div>
                            <div class="flex items-center justify-between text-[#A0A5B0]">
                                <span>Estimated Settlement Time</span>
                                <span id="quoteEtaText" class="text-white"></span>
                            </div>
                        </div>

                        <!-- Error Banner Alert -->
                        <div id="swapErrorBanner" class="hidden mt-4 p-3 rounded-lg bg-rose-950/80 border border-rose-800 text-xs text-rose-200 font-mono"></div>

                        <!-- Action Execution Buttons -->
                        <div class="mt-6 flex flex-col sm:flex-row gap-3">
                            <button id="refreshQuoteBtn" type="button"
                                class="sm:w-1/3 py-3 px-4 rounded-lg bg-[#1C2230] hover:bg-[#252D3F] text-xs font-medium text-slate-200 transition border border-[#2E374D] cursor-pointer">
                                Update Quote
                            </button>
                            <button id="initiateSwapBtn" type="button" disabled
                                class="sm:w-2/3 py-3 px-6 rounded-lg bg-white hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed text-xs sm:text-sm font-semibold text-[#10131A] transition shadow cursor-pointer">
                                Review &amp; Initiate Swap
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- =======================================================================
         SETTLEMENT COVERAGE & EXECUTION OVERVIEW
         Compact overview with representative network strip & expandable directory
         ======================================================================= -->
    <section id="networks" class="py-12 sm:py-16 border-b border-[#E8E5DF]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Top Narrative & Provider Summary -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
                <div class="lg:col-span-7">
                    <span class="text-xs font-heading font-medium tracking-wide text-[#6B7280]">01 / Settlement Coverage</span>
                    <h2 class="text-2xl sm:text-3xl font-normal text-[#121417] font-heading mt-1.5 tracking-tight">
                        Multi-Chain Coverage &amp; Provider Routing
                    </h2>
                    <p class="text-sm sm:text-base text-[#52565E] mt-3 leading-relaxed font-normal">
                        ZihuBridge routes cross-chain swaps through <strong>NEAR Intents</strong> as its initial execution provider. Network coverage and asset pairs are loaded dynamically from live provider catalogs. Available routes and execution liquidity depend on market maker participation at the time of order placement.
                    </p>
                </div>

                <div class="lg:col-span-5 flex flex-col justify-between pt-1">
                    <div class="p-4 rounded-xl bg-[#F7F5F0] border border-[#E8E5DF] text-xs text-[#52565E] space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[#8A8F99]">Execution Provider</span>
                            <span class="font-medium text-[#121417]">NEAR Intents</span>
                        </div>
                        <div class="flex items-center justify-between border-t border-[#E8E5DF] pt-2">
                            <span class="text-[#8A8F99]">Catalog Coverage</span>
                            <span class="font-medium text-[#121417]">
                                <span id="coverageNetworksCount">--</span> Chains &bull; <span id="coverageAssetsCount">--</span> Indexed Assets
                            </span>
                        </div>
                        <div class="flex items-center justify-between border-t border-[#E8E5DF] pt-2">
                            <span class="text-[#8A8F99]">Liquidity Model</span>
                            <span class="text-[#52565E]">Dynamic solver routing (subject to quotes)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Representative Networks Horizontal Strip -->
            <div class="mt-8 pt-6 border-t border-[#E8E5DF]">
                <div class="flex items-center justify-between mb-3 text-xs text-[#8A8F99]">
                    <span>Representative Networks</span>
                    <span class="hidden sm:inline">Select any network to set in terminal</span>
                </div>
                
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
                    <button type="button" data-network-slug="ethereum" class="representative-net-btn p-3 rounded-lg bg-[#F7F5F0] hover:bg-[#EAE7E0] border border-[#E8E5DF] text-left transition cursor-pointer group">
                        <div class="font-medium text-xs sm:text-sm text-[#121417] group-hover:text-black">Ethereum</div>
                        <div class="text-[11px] text-[#6B7280] mt-0.5">EVM · Layer 1</div>
                    </button>
                    <button type="button" data-network-slug="arbitrum" class="representative-net-btn p-3 rounded-lg bg-[#F7F5F0] hover:bg-[#EAE7E0] border border-[#E8E5DF] text-left transition cursor-pointer group">
                        <div class="font-medium text-xs sm:text-sm text-[#121417] group-hover:text-black">Arbitrum</div>
                        <div class="text-[11px] text-[#6B7280] mt-0.5">EVM · Rollup</div>
                    </button>
                    <button type="button" data-network-slug="base" class="representative-net-btn p-3 rounded-lg bg-[#F7F5F0] hover:bg-[#EAE7E0] border border-[#E8E5DF] text-left transition cursor-pointer group">
                        <div class="font-medium text-xs sm:text-sm text-[#121417] group-hover:text-black">Base</div>
                        <div class="text-[11px] text-[#6B7280] mt-0.5">EVM · OP Stack</div>
                    </button>
                    <button type="button" data-network-slug="solana" class="representative-net-btn p-3 rounded-lg bg-[#F7F5F0] hover:bg-[#EAE7E0] border border-[#E8E5DF] text-left transition cursor-pointer group">
                        <div class="font-medium text-xs sm:text-sm text-[#121417] group-hover:text-black">Solana</div>
                        <div class="text-[11px] text-[#6B7280] mt-0.5">SVM · Non-EVM</div>
                    </button>
                    <button type="button" data-network-slug="stellar" class="representative-net-btn p-3 rounded-lg bg-[#F7F5F0] hover:bg-[#EAE7E0] border border-[#E8E5DF] text-left transition cursor-pointer group">
                        <div class="font-medium text-xs sm:text-sm text-[#121417] group-hover:text-black">Stellar</div>
                        <div class="text-[11px] text-[#6B7280] mt-0.5">Stellar Core</div>
                    </button>
                    <button type="button" data-network-slug="near" class="representative-net-btn p-3 rounded-lg bg-[#F7F5F0] hover:bg-[#EAE7E0] border border-[#E8E5DF] text-left transition cursor-pointer group">
                        <div class="font-medium text-xs sm:text-sm text-[#121417] group-hover:text-black">NEAR</div>
                        <div class="text-[11px] text-[#6B7280] mt-0.5">Settlement Hub</div>
                    </button>
                </div>
            </div>

            <!-- Action & Expandable Full Directory Toggle -->
            <div class="mt-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <button id="toggleNetworkDirectoryBtn" type="button"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs sm:text-sm font-medium text-[#121417] bg-[#EAE7E0] hover:bg-[#121417] hover:text-white transition cursor-pointer border border-[#DDD9D0]">
                    <span id="toggleNetworkDirectoryText">Explore Full Network Directory</span>
                    <ion-icon name="chevron-down-outline" id="networkDirectoryChevron" class="text-xs transition-transform duration-200"></ion-icon>
                </button>
                <p class="text-xs text-[#8A8F99]">
                    Route availability is resolved dynamically via solver liquidity. Not all pairs are guaranteed active liquidity.
                </p>
            </div>

            <!-- Expandable Full Network Directory Panel (Uses Dynamic Provider Data) -->
            <div id="fullNetworkDirectoryPanel" class="hidden mt-8 pt-8 border-t border-[#E8E5DF]">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                    <div>
                        <h3 class="text-base font-medium text-[#121417]">Complete Supported Network Directory</h3>
                        <p class="text-xs text-[#52565E] mt-0.5">Live catalog loaded dynamically from the NEAR Intents solver provider.</p>
                    </div>
                    <span class="text-xs font-sans text-[#6B7280]">Select any network to set in terminal</span>
                </div>
                
                <div id="dynamicNetworksGrid">
                    <!-- Populated dynamically via JS from /api/v1/networks -->
                    <div class="py-6 text-center text-xs text-[#8A8F99]">
                        Loading active network directory from execution provider...
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- =======================================================================
         TOKEN DISCOVERY & CURATED ASSET DIRECTORY
         Spacious, authentic asset index + project listing application prospectus
         ======================================================================= -->
    <section id="token-discovery" class="py-16 sm:py-24 border-b border-[#E8E5DF] bg-[#F7F5F0]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10 pb-6 border-b border-[#E8E5DF]">
                <div class="max-w-2xl">
                    <span class="text-xs font-heading font-medium tracking-wide text-[#6B7280]">02 / Platform Registry</span>
                    <h2 class="text-2xl sm:text-3xl font-normal text-[#121417] font-heading mt-2 tracking-tight">
                        Curated Asset Directory
                    </h2>
                    <p class="text-sm sm:text-base text-[#52565E] mt-2 font-normal leading-relaxed">
                        Verified assets indexed across live solver pools. Directory registration confirms smart contract provenance and chain compatibility; live swaps depend on active market maker quotes.
                    </p>
                </div>

                <div class="text-xs text-[#6B7280] max-w-xs">
                    Listing in the directory does not imply universal swap availability without active market maker liquidity.
                </div>
            </div>

            <!-- Spacious Asset Directory List (Replaces cramped table) -->
            <div id="tokenDiscoveryTableBody" class="border-t border-[#E8E5DF]">
                <!-- Static Initial High-Readability Rows (Enhanced via JS dynamically) -->
                <div class="py-5 px-3 sm:px-4 border-b border-[#E8E5DF] flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-[#F4F1EA]/50 transition group">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-[#EAE7E0] border border-[#DDD9D0] flex items-center justify-center font-semibold text-sm text-[#121417]">
                            ETH
                        </div>
                        <div>
                            <div class="text-base font-semibold text-[#121417] group-hover:text-black">
                                Ethereum
                                <span class="text-xs font-mono font-normal text-[#6B7280] ml-1.5">ETH</span>
                            </div>
                            <div class="text-xs text-[#52565E] mt-0.5">Ethereum (EVM) · Native Asset</div>
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap items-center justify-between md:justify-end gap-6 text-sm">
                        <div class="text-left md:text-right">
                            <div class="text-xs text-[#8A8F99]">Reference Price</div>
                            <div class="font-mono text-sm font-medium text-[#121417]">Market Oracle Rate</div>
                        </div>

                        <div class="text-left md:text-right">
                            <div class="text-xs text-[#8A8F99]">Route Availability</div>
                            <div class="inline-flex items-center text-xs text-emerald-800 font-medium">
                                Active Solver Route
                            </div>
                        </div>

                        <a href="#swapTerminalApp" class="px-4 py-2 text-xs font-medium text-[#121417] bg-[#EAE7E0] hover:bg-[#121417] hover:text-white rounded border border-[#DDD9D0] transition cursor-pointer">
                            Select in Swap &rarr;
                        </a>
                    </div>
                </div>

                <div class="py-5 px-3 sm:px-4 border-b border-[#E8E5DF] flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-[#F4F1EA]/50 transition group">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-[#EAE7E0] border border-[#DDD9D0] flex items-center justify-center font-semibold text-sm text-[#121417]">
                            USDC
                        </div>
                        <div>
                            <div class="text-base font-semibold text-[#121417] group-hover:text-black">
                                USD Coin
                                <span class="text-xs font-mono font-normal text-[#6B7280] ml-1.5">USDC</span>
                            </div>
                            <div class="text-xs text-[#52565E] mt-0.5">Arbitrum, Base, Ethereum, Solana · Stablecoin</div>
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap items-center justify-between md:justify-end gap-6 text-sm">
                        <div class="text-left md:text-right">
                            <div class="text-xs text-[#8A8F99]">Reference Price</div>
                            <div class="font-mono text-sm font-medium text-[#121417]">$1.0000</div>
                        </div>

                        <div class="text-left md:text-right">
                            <div class="text-xs text-[#8A8F99]">Route Availability</div>
                            <div class="inline-flex items-center text-xs text-emerald-800 font-medium">
                                Active Solver Route
                            </div>
                        </div>

                        <a href="#swapTerminalApp" class="px-4 py-2 text-xs font-medium text-[#121417] bg-[#EAE7E0] hover:bg-[#121417] hover:text-white rounded border border-[#DDD9D0] transition cursor-pointer">
                            Select in Swap &rarr;
                        </a>
                    </div>
                </div>

                <div class="py-5 px-3 sm:px-4 border-b border-[#E8E5DF] flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-[#F4F1EA]/50 transition group">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-[#EAE7E0] border border-[#DDD9D0] flex items-center justify-center font-semibold text-sm text-[#121417]">
                            SOL
                        </div>
                        <div>
                            <div class="text-base font-semibold text-[#121417] group-hover:text-black">
                                Solana
                                <span class="text-xs font-mono font-normal text-[#6B7280] ml-1.5">SOL</span>
                            </div>
                            <div class="text-xs text-[#52565E] mt-0.5">Solana (SVM) · Native Asset</div>
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap items-center justify-between md:justify-end gap-6 text-sm">
                        <div class="text-left md:text-right">
                            <div class="text-xs text-[#8A8F99]">Reference Price</div>
                            <div class="font-mono text-sm font-medium text-[#121417]">Market Oracle Rate</div>
                        </div>

                        <div class="text-left md:text-right">
                            <div class="text-xs text-[#8A8F99]">Route Availability</div>
                            <div class="inline-flex items-center text-xs text-emerald-800 font-medium">
                                Active Solver Route
                            </div>
                        </div>

                        <a href="#swapTerminalApp" class="px-4 py-2 text-xs font-medium text-[#121417] bg-[#EAE7E0] hover:bg-[#121417] hover:text-white rounded border border-[#DDD9D0] transition cursor-pointer">
                            Select in Swap &rarr;
                        </a>
                    </div>
                </div>

                <div class="py-5 px-3 sm:px-4 border-b border-[#E8E5DF] flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-[#F4F1EA]/50 transition group">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-[#EAE7E0] border border-[#DDD9D0] flex items-center justify-center font-semibold text-sm text-[#121417]">
                            NEAR
                        </div>
                        <div>
                            <div class="text-base font-semibold text-[#121417] group-hover:text-black">
                                NEAR Protocol
                                <span class="text-xs font-mono font-normal text-[#6B7280] ml-1.5">WNEAR / NEAR</span>
                            </div>
                            <div class="text-xs text-[#52565E] mt-0.5">NEAR Sharded PoS · Native Settlement Asset</div>
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap items-center justify-between md:justify-end gap-6 text-sm">
                        <div class="text-left md:text-right">
                            <div class="text-xs text-[#8A8F99]">Reference Price</div>
                            <div class="font-mono text-sm font-medium text-[#121417]">Market Oracle Rate</div>
                        </div>

                        <div class="text-left md:text-right">
                            <div class="text-xs text-[#8A8F99]">Route Availability</div>
                            <div class="inline-flex items-center text-xs text-emerald-800 font-medium">
                                Active Solver Route
                            </div>
                        </div>

                        <a href="#swapTerminalApp" class="px-4 py-2 text-xs font-medium text-[#121417] bg-[#EAE7E0] hover:bg-[#121417] hover:text-white rounded border border-[#DDD9D0] transition cursor-pointer">
                            Select in Swap &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <!-- Project Listing Application Prospectus (Editorial Feature Block) -->
            <div class="mt-14 pt-10 border-t border-[#E8E5DF] grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <div class="lg:col-span-5">
                    <span class="text-xs font-heading font-medium tracking-wide text-[#6B7280]">Token Issuers &amp; Protocols</span>
                    <h3 class="text-xl font-normal text-[#121417] font-heading mt-1">Listing on ZihuBridge</h3>
                    <p class="text-sm text-[#52565E] mt-3 leading-relaxed">
                        We are opening applications for token teams seeking verified cross-chain visibility. Projects gain multi-chain exposure without the smart contract risk of locked bridge pools.
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('contact') }}"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-[#121417] text-white text-xs sm:text-sm font-medium hover:bg-black transition cursor-pointer">
                            <span>Submit Token for Listing</span>
                            <ion-icon name="arrow-forward-outline" class="text-xs"></ion-icon>
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-3 gap-6 text-xs text-[#52565E]">
                    <div>
                        <div class="font-medium text-[#121417] text-sm mb-1.5">01 / Provenance</div>
                        <p class="leading-relaxed">Verification of smart contract source code, deployment provenance, and underlying chain compatibility.</p>
                    </div>
                    <div>
                        <div class="font-medium text-[#121417] text-sm mb-1.5">02 / Oracle Feeds</div>
                        <p class="leading-relaxed">Integration with reference price feeds to ensure reliable USD conversion metrics in the directory.</p>
                    </div>
                    <div>
                        <div class="font-medium text-[#121417] text-sm mb-1.5">03 / Solver Routing</div>
                        <p class="leading-relaxed">Coordination with independent market makers on NEAR Intents to establish active quote liquidity.</p>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- =======================================================================
         TRUST, SETTLEMENT ARCHITECTURE & TRANSPARENCY
         Editorial treatise explaining non-custodial mechanics (No repeated cards)
         ======================================================================= -->
    <section class="py-16 sm:py-24 border-b border-[#E8E5DF]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-start">
                
                <!-- Left Title Column -->
                <div class="lg:col-span-5">
                    <span class="text-xs font-heading font-medium tracking-wide text-[#6B7280]">03 / Architecture &amp; Custody</span>
                    <h2 class="text-2xl sm:text-3xl font-normal text-[#121417] font-heading mt-2 tracking-tight">
                        How ZihuBridge Eliminates Bridge Custody Risk
                    </h2>
                    <p class="text-sm sm:text-base text-[#52565E] mt-4 leading-relaxed font-normal">
                        Traditional cross-chain bridges pool hundreds of millions into monolithic smart contracts—creating vulnerable honeypots. ZihuBridge discards locked bridge contracts in favor of competitive intent solvers.
                    </p>

                    <div class="mt-8 p-5 rounded-xl bg-[#F7F5F0] border border-[#E8E5DF] text-xs text-[#52565E] space-y-2">
                        <div class="font-medium text-[#121417] text-sm">Transparent Economic Terms:</div>
                        <p class="leading-relaxed">
                            Planned 1.0% protocol platform fee on completed swaps. Network gas fees are native to respective blockchains. Swaps are fulfilled by independent market makers in the NEAR Intents network.
                        </p>
                    </div>
                </div>

                <!-- Right Narrative Column (Unboxed, Line-Divided Tenets) -->
                <div class="lg:col-span-7 space-y-8">
                    
                    <div class="pb-8 border-b border-[#E8E5DF]">
                        <div class="text-xs font-heading font-medium tracking-wide text-[#6B7280] mb-1">01 / Non-Custodial Model</div>
                        <h3 class="text-lg font-medium text-[#121417] font-heading">Zero Custody Risk</h3>
                        <p class="text-sm text-[#52565E] mt-2 leading-relaxed">
                            ZihuBridge never controls your private keys, maintains omnibus custodian wallets, or holds speculative reserves. Funds transfer from your own wallet directly to a deterministic, single-use intent deposit address.
                        </p>
                    </div>

                    <div class="pb-8 border-b border-[#E8E5DF]">
                        <div class="text-xs font-heading font-medium tracking-wide text-[#6B7280] mb-1">02 / Execution Layer</div>
                        <h3 class="text-lg font-medium text-[#121417] font-heading">NEAR Intents Solver Network</h3>
                        <p class="text-sm text-[#52565E] mt-2 leading-relaxed">
                            When you request a quote, independent market makers compete off-chain to deliver the best exchange rate. Solvers take on the cross-chain transit risk and deliver native assets directly into your recipient wallet.
                        </p>
                    </div>

                    <div class="pb-8 border-b border-[#E8E5DF]">
                        <div class="text-xs font-heading font-medium tracking-wide text-[#6B7280] mb-1">03 / Slippage Enforcement</div>
                        <h3 class="text-lg font-medium text-[#121417] font-heading">Guaranteed Minimum Output Floor</h3>
                        <p class="text-sm text-[#52565E] mt-2 leading-relaxed">
                            Every quote locks in a mathematical minimum output threshold (<code class="font-mono text-xs text-[#121417]">min_amount_out</code>). If market conditions deteriorate during execution, solvers are cryptographically barred from finalizing below your signed threshold.
                        </p>
                    </div>

                    <div>
                        <div class="text-xs font-heading font-medium tracking-wide text-[#6B7280] mb-1">04 / Failed Order Protection</div>
                        <h3 class="text-lg font-medium text-[#121417] font-heading">Automated Origin-Chain Refunds</h3>
                        <p class="text-sm text-[#52565E] mt-2 leading-relaxed">
                            If a solver fails to settle within the designated quote window, the protocol returns your original deposit directly back to your designated source-chain refund address. Your assets are never trapped in an unresponsive bridge.
                        </p>
                    </div>

                </div>

            </div>

        </div>
    </section>

</main>
@endsection
