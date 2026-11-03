<header class="w-full py-4 px-6 bg-[#070B19]/90 backdrop-blur-md text-white border-b border-slate-800/80 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            <img src="{{ asset('assets/images/logo.png') }}" alt="ZihuBridge" class="w-40 md:w-44" />
        </a>

        <nav class="hidden md:flex items-center space-x-8 text-sm font-medium text-slate-300">
            <a href="{{ route('home') }}" class="hover:text-cyan-400 transition {{ request()->routeIs('home', 'swap.index') ? 'text-cyan-400 font-semibold' : '' }}">
                Swap
            </a>
            <a href="{{ route('swap.history') }}" class="hover:text-cyan-400 transition {{ request()->routeIs('swap.history') ? 'text-cyan-400 font-semibold' : '' }}">
                Swap Explorer & History
            </a>
            <a href="{{ route('about') }}" class="hover:text-cyan-400 transition {{ request()->routeIs('about') ? 'text-cyan-400 font-semibold' : '' }}">
                About
            </a>
            <a href="{{ route('whitepaper') }}" target="_blank" class="hover:text-cyan-400 transition">
                Whitepaper
            </a>
            <a href="{{ route('contact') }}" class="hover:text-cyan-400 transition {{ request()->routeIs('contact') ? 'text-cyan-400 font-semibold' : '' }}">
                Support
            </a>
        </nav>

        <div class="flex items-center gap-3">
            <button id="openWalletModalBtn" type="button"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs md:text-sm font-semibold bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white shadow-lg shadow-cyan-500/20 transition cursor-pointer">
                <ion-icon name="wallet-outline" class="text-base"></ion-icon>
                <span id="connectedWalletBadgeText">Connect Wallet</span>
            </button>

            <button id="mobileMenuBtn" type="button" class="md:hidden text-2xl text-slate-300 hover:text-white p-1">
                <ion-icon name="menu-outline"></ion-icon>
            </button>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobileMenuDrawer" class="hidden md:hidden mt-4 pt-4 border-t border-slate-800 space-y-3 text-sm">
        <a href="{{ route('home') }}" class="block py-2 text-slate-200 hover:text-cyan-400">Swap</a>
        <a href="{{ route('swap.history') }}" class="block py-2 text-slate-200 hover:text-cyan-400">Swap Explorer & History</a>
        <a href="{{ route('about') }}" class="block py-2 text-slate-200 hover:text-cyan-400">About</a>
        <a href="{{ route('whitepaper') }}" target="_blank" class="block py-2 text-slate-200 hover:text-cyan-400">Whitepaper</a>
        <a href="{{ route('contact') }}" class="block py-2 text-slate-200 hover:text-cyan-400">Support</a>
    </div>
</header>

<!-- Non-Custodial Multi-Chain Wallet Modal -->
<div id="walletConnectModal" class="fixed inset-0 bg-black/75 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-[#0F172A] border border-slate-700/80 rounded-2xl max-w-md w-full p-6 text-white shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-bold">Connect Non-Custodial Wallet</h3>
                <p class="text-xs text-slate-400 mt-0.5">Your keys never leave your wallet. Or swap directly via self-custody deposit address.</p>
            </div>
            <button type="button" id="closeWalletModalBtn" class="text-slate-400 hover:text-white text-2xl cursor-pointer">&times;</button>
        </div>

        <div id="walletConnectedCard" class="hidden mb-4 p-3.5 rounded-xl bg-emerald-950/50 border border-emerald-500/40">
            <div class="flex items-center justify-between">
                <div>
                    <span id="walletConnectedProviderName" class="text-xs uppercase tracking-wider text-emerald-400 font-semibold">Connected</span>
                    <p id="walletConnectedAddressDisplay" class="font-mono text-sm text-white mt-0.5 break-all"></p>
                </div>
                <button type="button" id="disconnectWalletBtn" class="text-xs px-3 py-1.5 rounded-lg bg-rose-500/20 text-rose-300 hover:bg-rose-500/30 transition cursor-pointer">
                    Disconnect
                </button>
            </div>
        </div>

        <div class="space-y-2.5">
            <button type="button" data-wallet-connect="evm"
                class="w-full flex items-center justify-between p-3.5 rounded-xl bg-slate-800/70 hover:bg-slate-800 border border-slate-700/70 transition cursor-pointer">
                <div class="flex items-center gap-3 text-left">
                    <span class="w-9 h-9 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center text-xl">
                        <ion-icon name="cube-outline"></ion-icon>
                    </span>
                    <div>
                        <div class="text-sm font-semibold">EVM Browser Wallet</div>
                        <div class="text-xs text-slate-400">MetaMask, Rabby, Coinbase, Brave (ETH, ARB, BASE, OP, POL, BSC)</div>
                    </div>
                </div>
                <ion-icon name="chevron-forward-outline" class="text-slate-400"></ion-icon>
            </button>

            <button type="button" data-wallet-connect="solana"
                class="w-full flex items-center justify-between p-3.5 rounded-xl bg-slate-800/70 hover:bg-slate-800 border border-slate-700/70 transition cursor-pointer">
                <div class="flex items-center gap-3 text-left">
                    <span class="w-9 h-9 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center text-xl">
                        <ion-icon name="flash-outline"></ion-icon>
                    </span>
                    <div>
                        <div class="text-sm font-semibold">Solana Wallet</div>
                        <div class="text-xs text-slate-400">Phantom, Solflare, Backpack</div>
                    </div>
                </div>
                <ion-icon name="chevron-forward-outline" class="text-slate-400"></ion-icon>
            </button>

            <button type="button" data-wallet-connect="stellar"
                class="w-full flex items-center justify-between p-3.5 rounded-xl bg-slate-800/70 hover:bg-slate-800 border border-slate-700/70 transition cursor-pointer">
                <div class="flex items-center gap-3 text-left">
                    <span class="w-9 h-9 rounded-lg bg-cyan-500/20 text-cyan-400 flex items-center justify-center text-xl">
                        <ion-icon name="planet-outline"></ion-icon>
                    </span>
                    <div>
                        <div class="text-sm font-semibold">Freighter (Stellar)</div>
                        <div class="text-xs text-slate-400">Non-custodial Stellar browser extension</div>
                    </div>
                </div>
                <ion-icon name="chevron-forward-outline" class="text-slate-400"></ion-icon>
            </button>
        </div>

        <p id="walletModalError" class="hidden mt-3 text-xs text-rose-400 bg-rose-950/50 border border-rose-500/30 rounded-lg p-2.5"></p>
    </div>
</div>
