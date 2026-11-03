<header class="w-full bg-[#10131A] text-white border-b border-[#1E2433] sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex justify-between items-center">
        <div class="flex items-center gap-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 focus:outline-none">
                <img src="{{ asset('assets/images/logo.png') }}" alt="ZihuBridge" class="h-7 w-auto" />
            </a>

            <nav aria-label="Main Navigation" class="hidden md:flex items-center gap-6 text-[13px] font-medium text-slate-300">
                <a href="{{ route('home') }}"
                    class="py-1 transition-colors {{ request()->routeIs('home', 'swap.index') ? 'text-white font-medium border-b-2 border-white' : 'hover:text-white' }}">
                    Swap
                </a>
                <a href="{{ route('swap.history') }}"
                    class="py-1 transition-colors {{ request()->routeIs('swap.history') ? 'text-white font-medium border-b-2 border-white' : 'hover:text-white' }}">
                    Swap Explorer & History
                </a>
                <a href="{{ route('about') }}"
                    class="py-1 transition-colors {{ request()->routeIs('about') ? 'text-white font-medium border-b-2 border-white' : 'hover:text-white' }}">
                    About
                </a>
                <a href="{{ route('whitepaper') }}" target="_blank" rel="noopener noreferrer"
                    class="py-1 hover:text-white transition-colors inline-flex items-center gap-1">
                    <span>Whitepaper</span>
                    <ion-icon name="arrow-up-right-box-outline" class="text-[11px] opacity-70"></ion-icon>
                </a>
                <a href="{{ route('contact') }}"
                    class="py-1 transition-colors {{ request()->routeIs('contact') ? 'text-white font-medium border-b-2 border-white' : 'hover:text-white' }}">
                    Support
                </a>
            </nav>
        </div>

        <div class="flex items-center gap-3">
            <div class="hidden lg:flex items-center px-2.5 py-1 rounded bg-[#161B26] border border-[#232B3D] text-[11px] font-mono text-slate-300">
                <span>NEAR Intents · Active</span>
            </div>

            <button id="openWalletModalBtn" type="button"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-medium bg-white text-[#10131A] hover:bg-slate-100 transition cursor-pointer">
                <ion-icon name="wallet-outline" class="text-sm"></ion-icon>
                <span id="connectedWalletBadgeText" class="font-mono">Connect Wallet</span>
            </button>

            <button id="mobileMenuBtn" type="button" aria-label="Toggle navigation menu"
                class="md:hidden text-xl text-slate-300 hover:text-white p-1.5 rounded-lg border border-[#1E2433]">
                <ion-icon name="menu-outline"></ion-icon>
            </button>
        </div>
    </div>

    <!-- Mobile Navigation Drawer -->
    <div id="mobileMenuDrawer" class="hidden md:hidden bg-[#10131A] border-t border-[#1E2433] px-4 pt-3 pb-5 space-y-2 text-sm">
        <a href="{{ route('home') }}" class="block py-2 px-2 rounded text-slate-200 hover:bg-[#161B26] hover:text-white">Swap</a>
        <a href="{{ route('swap.history') }}" class="block py-2 px-2 rounded text-slate-200 hover:bg-[#161B26] hover:text-white">Swap Explorer & History</a>
        <a href="{{ route('about') }}" class="block py-2 px-2 rounded text-slate-200 hover:bg-[#161B26] hover:text-white">About</a>
        <a href="{{ route('whitepaper') }}" target="_blank" rel="noopener noreferrer" class="block py-2 px-2 rounded text-slate-200 hover:bg-[#161B26] hover:text-white">Whitepaper</a>
        <a href="{{ route('contact') }}" class="block py-2 px-2 rounded text-slate-200 hover:bg-[#161B26] hover:text-white">Support</a>
    </div>
</header>

<!-- Non-Custodial Wallet Connection Modal -->
<div id="walletConnectModal" role="dialog" aria-modal="true" aria-labelledby="walletModalTitle"
    class="fixed inset-0 bg-[#0B0F17]/70 backdrop-blur-[2px] hidden items-center justify-center z-50 p-4">
    <div class="bg-white border border-slate-200 rounded-xl max-w-md w-full p-6 text-[#0B0F17] shadow-2xl">
        <div class="flex items-start justify-between pb-4 border-b border-slate-200">
            <div>
                <span class="text-[11px] font-mono uppercase tracking-widest text-slate-500">Self-Custody Session</span>
                <h3 id="walletModalTitle" class="text-base font-bold text-[#0B0F17] mt-0.5">Connect Browser Wallet</h3>
            </div>
            <button type="button" id="closeWalletModalBtn" aria-label="Close modal"
                class="text-slate-400 hover:text-[#0B0F17] text-xl leading-none p-1 cursor-pointer">&times;</button>
        </div>

        <p class="text-xs text-slate-600 mt-3 leading-relaxed">
            Connecting a browser wallet auto-fills your origin refund or destination address and enables direct native deposit signing on compatible EVM networks. You can also swap without connecting by entering addresses manually.
        </p>

        <div id="walletConnectedCard" class="hidden mt-4 p-3.5 rounded-lg bg-emerald-50 border border-emerald-200">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <span id="walletConnectedProviderName" class="text-[11px] font-mono uppercase tracking-wider text-emerald-700 font-semibold">Connected</span>
                    <p id="walletConnectedAddressDisplay" class="font-mono text-xs text-[#0B0F17] mt-0.5 break-all"></p>
                </div>
                <button type="button" id="disconnectWalletBtn"
                    class="shrink-0 text-xs font-medium px-2.5 py-1.5 rounded bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 transition cursor-pointer">
                    Disconnect
                </button>
            </div>
        </div>

        <div class="mt-4 divide-y divide-slate-200 border border-slate-200 rounded-lg overflow-hidden">
            <button type="button" data-wallet-connect="evm"
                class="w-full flex items-center justify-between p-3.5 bg-white hover:bg-slate-50 transition cursor-pointer text-left">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded bg-slate-100 border border-slate-200 text-[#0B0F17] flex items-center justify-center text-base">
                        <ion-icon name="cube-outline"></ion-icon>
                    </span>
                    <div>
                        <div class="text-xs font-semibold text-[#0B0F17]">EVM Browser Wallet</div>
                        <div class="text-[11px] text-slate-500">MetaMask, Rabby, Coinbase, Brave (ETH, ARB, BASE, OP, POL, BSC)</div>
                    </div>
                </div>
                <ion-icon name="arrow-forward-outline" class="text-slate-400 text-sm"></ion-icon>
            </button>

            <button type="button" data-wallet-connect="solana"
                class="w-full flex items-center justify-between p-3.5 bg-white hover:bg-slate-50 transition cursor-pointer text-left">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded bg-slate-100 border border-slate-200 text-[#0B0F17] flex items-center justify-center text-base">
                        <ion-icon name="flash-outline"></ion-icon>
                    </span>
                    <div>
                        <div class="text-xs font-semibold text-[#0B0F17]">Solana Wallet</div>
                        <div class="text-[11px] text-slate-500">Phantom, Solflare, Backpack</div>
                    </div>
                </div>
                <ion-icon name="arrow-forward-outline" class="text-slate-400 text-sm"></ion-icon>
            </button>

            <button type="button" data-wallet-connect="stellar"
                class="w-full flex items-center justify-between p-3.5 bg-white hover:bg-slate-50 transition cursor-pointer text-left">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded bg-slate-100 border border-slate-200 text-[#0B0F17] flex items-center justify-center text-base">
                        <ion-icon name="planet-outline"></ion-icon>
                    </span>
                    <div>
                        <div class="text-xs font-semibold text-[#0B0F17]">Freighter (Stellar)</div>
                        <div class="text-[11px] text-slate-500">Non-custodial Stellar browser extension</div>
                    </div>
                </div>
                <ion-icon name="arrow-forward-outline" class="text-slate-400 text-sm"></ion-icon>
            </button>
        </div>

        <p id="walletModalError" class="hidden mt-3 text-xs text-rose-700 bg-rose-50 border border-rose-200 rounded-lg p-2.5"></p>
    </div>
</div>
