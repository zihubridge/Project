<header class="relative shadow-lg px-4 py-3 bg-black z-50">
    <nav class="flex justify-between items-center max-w-7xl mx-auto">
        <!-- Logo -->
        <div class="flex items-center">
            <a href="{{ route('home') }}">
                <img src="{{ asset('assets/images/updatedLogo.png') }}" alt="LOGO" class=" h-[40px]">
            </a>
        </div>

        <!-- Desktop Menu -->
        <div class="hidden md:flex items-center">
            <ul class="flex items-center gap-8 text-gray-200 font-medium">
                <li class="relative group">
                    <a href="{{ route('home') }}"
                        class="hover:text-[#2b68e0] hover:tracking-wide hover:underline underline-offset-4 transition-all duration-300 font-light">
                        Home
                    </a>
                </li>
                {{-- <li class="relative group">
                    <a href="#"
                        class="hover:text-[#2b68e0] hover:tracking-wide hover:underline underline-offset-4 transition-all duration-300 font-light">
                        Faculty
                    </a>
                </li>
                <li class="relative group">
                    <a href="#"
                        class="hover:text-[#2b68e0] hover:tracking-wide hover:underline underline-offset-4 transition-all duration-300 font-light">
                        Courses
                    </a>
                </li> --}}
                <li class="relative group">
                    <a href="{{ route('about') }}"
                        class="hover:text-[#2b68e0] hover:tracking-wide hover:underline underline-offset-4 transition-all duration-300 font-light">
                        About Us
                    </a>
                </li>
                <li class="relative group">
                    <a href="{{ route('contact') }}"
                        class="hover:text-[#2b68e0] hover:tracking-wide hover:underline underline-offset-4 transition-all duration-300 font-light">
                        Contact
                    </a>
                </li>
            </ul>
        </div>

        <!-- RIGHT SIDE -->
        <div class="flex items-center gap-4">

        <!-- Mobile Menu Icon -->
            <button id="menuBtn" class="md:hidden text-3xl text-white z-30 relative">
                <ion-icon name="menu-outline"></ion-icon>
            </button>
        </div>

    </nav>

    <!-- MOBILE MENU OVERLAY -->
    <div id="mobileMenu" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden">
        <div id="mobileMenuInner"
            class="fixed top-0 left-0 w-full max-w-full h-full bg-black transform transition-transform duration-600 ease-in-out -translate-x-full shadow-xl">

            <!-- Top Section -->
            <div class="flex justify-between items-center p-4">
                <img src="{{ asset('assets/images/logo.png') }}" alt="Logo" class="w-[65px] md:w-[80px] h-auto">
                <button id="closeMenu" class="text-3xl text-white">
                    <ion-icon name="close-outline"></ion-icon>
                </button>
            </div>

            <!-- Menu Items -->
            <ul class="flex flex-col items-center gap-6 py-10 px-6 text-lg font-medium text-gray-800">
                <li><a href="#"
                        class="hover:text-[#2b68e0] hover:tracking-wide hover:underline underline-offset-4 text-white font-light transition-all duration-300">Home</a>
                </li>
                <li><a href="#"
                        class="hover:text-[#2b68e0] hover:tracking-wide hover:underline underline-offset-4 text-white font-light transition-all duration-300">Faculty</a>
                </li>
                <li><a href="#"
                        class="hover:text-[#2b68e0] hover:tracking-wide hover:underline underline-offset-4 text-white font-light transition-all duration-300">Courses</a>
                </li>
                <li><a href="#"
                        class="hover:text-[#2b68e0] hover:tracking-wide hover:underline underline-offset-4 text-white font-light transition-all duration-300">About
                        Us</a></li>
                <li><a href="#"
                        class="hover:text-[#2b68e0] hover:tracking-wide hover:underline underline-offset-4 text-white font-light transition-all duration-300">Contact</a>
                </li>
            </ul>
        </div>
    </div>
</header>

<script>
    const menuBtn = document.getElementById('menuBtn');
    const closeMenu = document.getElementById('closeMenu');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuInner = document.getElementById('mobileMenuInner');

    function openMenu() {
        mobileMenu.classList.remove('hidden');
        setTimeout(() => {
            mobileMenuInner.classList.remove('-translate-x-full');
        }, 10);
        document.body.style.overflow = 'hidden';
    }

    function closeMenuFunc() {
        mobileMenuInner.classList.add('-translate-x-full');
        setTimeout(() => {
            mobileMenu.classList.add('hidden');
        }, 500);
        document.body.style.overflow = 'auto';
    }

    menuBtn.addEventListener('click', openMenu);
    closeMenu.addEventListener('click', closeMenuFunc);
    mobileMenu.addEventListener('click', (e) => {
        if (e.target === mobileMenu) closeMenuFunc();
    });
</script>