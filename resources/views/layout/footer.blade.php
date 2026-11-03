<footer class="bg-[#10131A] text-slate-400 border-t border-[#1E2433]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
        <div class="flex flex-col md:flex-row justify-between gap-10">
            <!-- Brand & Description -->
            <div class="max-w-sm">
                <img src="{{ asset('assets/images/logo.png') }}" alt="ZihuBridge" class="h-7 w-auto mb-4">
                <p class="text-xs leading-relaxed text-slate-400">
                    Non-custodial cross-chain asset execution infrastructure. Direct solver routing across supported blockchains with locked minimum output floors and origin-chain refund protection.
                </p>
                <div class="flex items-center gap-2.5 mt-5">
                    <a href="https://x.com/ZihuBridge" target="_blank" rel="noopener noreferrer" aria-label="ZihuBridge on X"
                        class="w-8 h-8 flex items-center justify-center rounded border border-[#1E2433] bg-[#161B26] hover:border-slate-500 hover:text-white transition">
                        <ion-icon name="logo-twitter" class="text-sm"></ion-icon>
                    </a>
                    <a href="https://github.com/zihubridge" target="_blank" rel="noopener noreferrer" aria-label="ZihuBridge on GitHub"
                        class="w-8 h-8 flex items-center justify-center rounded border border-[#1E2433] bg-[#161B26] hover:border-slate-500 hover:text-white transition">
                        <ion-icon name="logo-github" class="text-sm"></ion-icon>
                    </a>
                </div>
            </div>

            <!-- Right-Aligned Navigation Columns -->
            <div class="flex flex-wrap gap-12 md:gap-20 md:justify-end text-xs">
                <div>
                    <h3 class="font-heading text-xs uppercase tracking-wider text-white font-medium mb-4">Product</h3>
                    <ul class="space-y-2.5">
                        <li><a href="{{ route('home') }}" class="hover:text-white transition-colors">Swap Terminal</a></li>
                        <li><a href="{{ route('swap.history') }}" class="hover:text-white transition-colors">Swap Explorer</a></li>
                        <li><a href="{{ route('whitepaper') }}" target="_blank" rel="noopener noreferrer" class="hover:text-white transition-colors">Whitepaper</a></li>
                        <li><a href="{{ route('contact') }}" class="hover:text-white transition-colors">Support &amp; Listing</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-heading text-xs uppercase tracking-wider text-white font-medium mb-4">Company</h3>
                    <ul class="space-y-2.5">
                        <li><a href="{{ route('about') }}" class="hover:text-white transition-colors">About ZihuBridge</a></li>
                        <li><a href="{{ route('privacy') }}" class="hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="{{ route('terms') }}" class="hover:text-white transition-colors">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="border-t border-[#1E2433] mt-12 pt-6 flex flex-col sm:flex-row justify-between items-center text-xs text-slate-500">
            <p>&copy; {{ date('Y') }} ZihuBridge. Sovereign self-custody execution.</p>
            <p class="mt-2 sm:mt-0 text-xs text-slate-400 font-sans">Provider: NEAR Intents Solver Network</p>
        </div>
    </div>
</footer>
