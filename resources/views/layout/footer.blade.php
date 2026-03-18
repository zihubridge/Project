<footer class="bg-[#0B0F1A] text-gray-400 pt-14 pb-6 px-6 md:px-16">
    <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-12">

        <!-- Brand -->
        <div>
            <img src="{{ asset('assets/new assets/logo.png') }}" alt="logo" class="w-44 mb-4">

            <p class="text-sm leading-relaxed text-gray-400">
                Fast, secure, and low-fee crypto swaps. Built for simplicity and real usage.
            </p>

            <div class="flex gap-3 mt-5">
                <a href="#"
                    class="w-9 h-9 flex items-center justify-center rounded-full bg-[#1A1F2E] hover:bg-blue-500 hover:text-white transition">
                    <ion-icon name="logo-twitter"></ion-icon>
                </a>
            </div>
        </div>

        <!-- Company -->
        <div>
            <h3 class="text-white font-semibold mb-4">Company</h3>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ route('about') }}" class="hover:text-white">About Us</a></li>
                <li><a href="{{ route('contact') }}" class="hover:text-white">Contact</a></li>
            </ul>
        </div>

        <!-- Legal -->
        <div>
            <h3 class="text-white font-semibold mb-4">Legal</h3>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ route('privacy') }}" class="hover:text-white">Privacy Policy</a></li>
                <li><a href="{{ route('terms') }}" class="hover:text-white">Terms & Conditions</a></li>
            </ul>
        </div>

    </div>

    <!-- Divider -->
    <div
        class="border-t border-gray-800 mt-12 pt-6 flex flex-col md:flex-row justify-between items-center text-sm text-gray-500">
        <p>© 2026 GlideSwap. All rights reserved. A product by
            <a href="https://corehives.com" target="_blank"
                class="text-decoration-none text-white fw-semibold hover-purple">
                CoreHives
            </a>
        </p>
        <p class="mt-2 md:mt-0">Built for the decentralized future</p>
    </div>
</footer>
