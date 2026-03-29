<footer class="bg-black text-gray-400 pt-14 pb-6 px-6 md:px-16">

    <div class="max-w-6xl mx-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-8">

        <!-- Brand -->
        <div class="sm:col-span-2 lg:col-span-2">
            <img src="{{ asset('assets/images/logo.png') }}" alt="logo" class="w-44 mb-4">

            <div class="flex gap-3 mt-5 flex-wrap">
                <a href="https://x.com/ZihuBridge" target="_blank"
                    class="w-9 h-9 flex items-center justify-center rounded-full bg-[#1A1F2E] hover:bg-blue-500 hover:text-white transition">
                    <ion-icon name="logo-facebook"></ion-icon>
                </a>
                <a href="#"
                    class="w-9 h-9 flex items-center justify-center rounded-full bg-[#1A1F2E] hover:bg-blue-500 hover:text-white transition">
                    <ion-icon name="logo-twitter"></ion-icon>
                </a>
                <a href="#"
                    class="w-9 h-9 flex items-center justify-center rounded-full bg-[#1A1F2E] hover:bg-blue-500 hover:text-white transition">
                    <ion-icon name="logo-instagram"></ion-icon>
                </a>
                <a href="#"
                    class="w-9 h-9 flex items-center justify-center rounded-full bg-[#1A1F2E] hover:bg-blue-500 hover:text-white transition">
                    <ion-icon name="logo-linkedin"></ion-icon>
                </a>
                <a href="#"
                    class="w-9 h-9 flex items-center justify-center rounded-full bg-[#1A1F2E] hover:bg-blue-500 hover:text-white transition">
                    <ion-icon name="logo-youtube"></ion-icon>
                </a>
            </div>
        </div>

        <!-- Company -->
        <div>
            <h3 class="text-white font-semibold mb-4">Company</h3>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ route('about') }}" class="hover:text-white">About Us</a></li>
                <li><a href="#" class="hover:text-white">Blog</a></li>
                <li><a href="#" class="hover:text-white">Careers</a></li>
                <li><a href="#" class="hover:text-white">Student</a></li>
                <li><a href="#" class="hover:text-white">Security</a></li>
                <li><a href="#" class="hover:text-white">Trust and Safety</a></li>
                <li><a href="#" class="hover:text-white">Newsroom</a></li>
                <li><a href="{{ route('contact') }}" class="hover:text-white">Videos</a></li>
            </ul>
        </div>

        <!-- Learn -->
        <div>
            <h3 class="text-white font-semibold mb-4">Learn</h3>
            <ul class="space-y-2 text-sm">
                <li><a href="#" class="hover:text-white">What’s Trending</a></li>
                <li><a href="#" class="hover:text-white">Product News</a></li>
                <li><a href="#" class="hover:text-white">Events</a></li>
                <li><a href="#" class="hover:text-white">University</a></li>
                <li><a href="#" class="hover:text-white">Research</a></li>
                <li><a href="#" class="hover:text-white">Market Updates</a></li>
            </ul>
        </div>

        <!-- Products -->
        <div>
            <h3 class="text-white font-semibold mb-4">Products</h3>
            <ul class="space-y-2 text-sm">
                <li><a href="#" class="hover:text-white">Stock & Fund</a></li>
                <li><a href="#" class="hover:text-white">Cash Card</a></li>
                <li><a href="#" class="hover:text-white">Crypto</a></li>
                <li><a href="#" class="hover:text-white">Options</a></li>
                <li><a href="#" class="hover:text-white">Gold</a></li>
                <li><a href="#" class="hover:text-white">Learn Snacks</a></li>
            </ul>
        </div>

        <!-- Support -->
        <div>
            <h3 class="text-white font-semibold mb-4">Support</h3>
            <ul class="space-y-2 text-sm">
                <li><a href="#" class="hover:text-white">Support Center</a></li>
                <li><a href="{{ route('contact') }}" class="hover:text-white">Contact Us</a></li>
                <li><a href="#" class="hover:text-white">System Status</a></li>
                <li><a href="#" class="hover:text-white">Area of Availability</a></li>
                <li><a href="{{ route('whitepaper') }}" class="hover:text-white">Whitepaper</a></li>
                <li><a href="{{ route('privacy') }}" class="hover:text-white">Privacy Policy</a></li>
                <li><a href="{{ route('terms') }}" class="hover:text-white">Terms & Conditions</a></li>
            </ul>
        </div>

        <!-- Resources -->
        <div>
            <h3 class="text-white font-semibold mb-4">Resources</h3>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ route('privacy') }}" class="hover:text-white">Prices</a></li>
                <li><a href="{{ route('privacy') }}" class="hover:text-white">Site Widgets</a></li>
                <li><a href="{{ route('privacy') }}" class="hover:text-white">Tax</a></li>
                <li><a href="{{ route('privacy') }}" class="hover:text-white">Support</a></li>
            </ul>
        </div>

    </div>

    <!-- Divider -->
    <div
        class="border-t border-gray-800 mt-12 pt-6 flex flex-col md:flex-row justify-between items-center text-sm text-gray-500">
        <p>© 2026 ZihuBridge. All rights reserved. A product by
            <a href="https://corehives.com" target="_blank" class="text-white font-semibold hover:text-purple-400">
                CoreHives
            </a>
        </p>
        <p class="mt-2 md:mt-0">Built for the decentralized future</p>
    </div>

</footer>