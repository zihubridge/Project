<header class="absolute top-10 left-0 w-full z-50 bg-transparent">
    <nav class="flex max-w-6xl mx-auto items-center justify-between overflow-visible">

        <!-- Logo -->
        <div class="flex items-center">
            <a href="{{ route('home') }}">
                <img src="{{ asset('assets/images/logo.png') }}" alt="LOGO" class="h-[40px]">
            </a>
        </div>

        <!-- Desktop Menu -->
        <div class="hidden md:flex items-center">
            <ul class="flex items-center gap-8 text-gray-200 font-medium">

                <li>
                    <a href="/"
                        class="hover:text-[#2b68e0] hover:underline underline-offset-4 transition-all duration-300 font-light text-sm">
                        Home
                    </a>
                </li>
                <li>
                    <a href="/whitepaper"
                        class="hover:text-[#2b68e0] hover:underline underline-offset-4 transition-all duration-300 font-light text-sm">
                        Whitepaper
                    </a>
                </li>
                <li>
                    <a href="/about"
                        class="hover:text-[#2b68e0] hover:underline underline-offset-4 transition-all duration-300 font-light text-sm">
                        About Us
                    </a>
                </li>
                <li>
                    <a href="contact"
                        class="hover:text-[#2b68e0] hover:underline underline-offset-4 transition-all duration-300 font-light text-sm">
                        Contact
                    </a>
                </li>

                <!-- Customer Benefits -->
                {{-- <li class="relative dropdown-menu">
                    <a href="#"
                        class="flex items-center gap-1 hover:text-[#2b68e0] hover:underline underline-offset-4 transition-all duration-300 font-light text-sm">
                        Customer Benefits
                        <ion-icon class="chevron text-[#B3B3B3] text-lg transition-transform duration-300"
                            name="chevron-down-outline"></ion-icon>
                    </a>
                    <ul class="dropdown absolute left-0 top-full bg-white shadow-xl rounded-md z-[9999]"
                        style="width:180px; opacity:0; visibility:hidden; pointer-events:none; transition: opacity 0.3s ease, visibility 0.3s ease;">
                        <li><a href="#" class="block px-4 py-2 text-black hover:bg-gray-100 text-sm">Option 1</a>
                        </li>
                        <li><a href="#" class="block px-4 py-2 text-black hover:bg-gray-100 text-sm">Option 2</a>
                        </li>
                        <li><a href="#" class="block px-4 py-2 text-black hover:bg-gray-100 text-sm">Option 3</a>
                        </li>
                    </ul>
                </li> --}}

                <!-- Analytics -->
                {{-- <li class="relative dropdown-menu">
                    <a href="#"
                        class="flex items-center gap-1 hover:text-[#2b68e0] hover:underline underline-offset-4 transition-all duration-300 font-light text-sm">
                        Analytics
                        <ion-icon class="chevron text-[#B3B3B3] text-lg transition-transform duration-300"
                            name="chevron-down-outline"></ion-icon>
                    </a>
                    <ul class="dropdown absolute left-0 top-full bg-white shadow-xl rounded-md z-[9999]"
                        style="width:160px; opacity:0; visibility:hidden; pointer-events:none; transition: opacity 0.3s ease, visibility 0.3s ease;">
                        <li><a href="#" class="block px-4 py-2 text-black hover:bg-gray-100 text-sm">Overview</a>
                        </li>
                        <li><a href="#" class="block px-4 py-2 text-black hover:bg-gray-100 text-sm">Reports</a></li>
                        <li><a href="#" class="block px-4 py-2 text-black hover:bg-gray-100 text-sm">Insights</a>
                        </li>
                    </ul>
                </li> --}}

                <!-- Currencies -->
                {{-- <li class="relative dropdown-menu">
                    <a href="#"
                        class="flex items-center gap-1 hover:text-[#2b68e0] hover:underline underline-offset-4 transition-all duration-300 font-light text-sm">
                        Currencies
                        <ion-icon class="chevron text-[#B3B3B3] text-lg transition-transform duration-300"
                            name="chevron-down-outline"></ion-icon>
                    </a>
                    <ul class="dropdown absolute left-0 top-full bg-white shadow-xl rounded-md z-[9999]"
                        style="width:160px; opacity:0; visibility:hidden; pointer-events:none; transition: opacity 0.3s ease, visibility 0.3s ease;">
                        <li><a href="#" class="block px-4 py-2 text-black hover:bg-gray-100 text-sm">USD</a></li>
                        <li><a href="#" class="block px-4 py-2 text-black hover:bg-gray-100 text-sm">EUR</a></li>
                        <li><a href="#" class="block px-4 py-2 text-black hover:bg-gray-100 text-sm">GBP</a></li>
                    </ul>
                </li> --}}

                <!-- Business -->
                {{-- <li class="relative dropdown-menu">
                    <a href="#"
                        class="flex items-center gap-1 hover:text-[#2b68e0] hover:underline underline-offset-4 transition-all duration-300 font-light text-sm">
                        Business
                        <ion-icon class="chevron text-[#B3B3B3] text-lg transition-transform duration-300"
                            name="chevron-down-outline"></ion-icon>
                    </a>
                    <ul class="dropdown absolute left-0 top-full bg-white shadow-xl rounded-md z-[9999]"
                        style="width:160px; opacity:0; visibility:hidden; pointer-events:none; transition: opacity 0.3s ease, visibility 0.3s ease;">
                        <li><a href="#" class="block px-4 py-2 text-black hover:bg-gray-100 text-sm">Startups</a>
                        </li>
                        <li><a href="#" class="block px-4 py-2 text-black hover:bg-gray-100 text-sm">Enterprise</a>
                        </li>
                        <li><a href="#" class="block px-4 py-2 text-black hover:bg-gray-100 text-sm">Partners</a>
                        </li>
                    </ul>
                </li> --}}

                <!-- Language Selector -->
                {{-- <li class="relative dropdown-menu">
                    <button id="langBtn"
                        class="flex items-center gap-2 text-gray-200 hover:text-[#2b68e0] transition-all duration-300 font-light">
                        <img id="activeFlagImg" src="https://flagcdn.com/w20/us.png" alt="EN"
                            class="w-5 h-4 rounded-sm object-cover">
                        <span id="activeLangLabel" class="text-sm">EN</span>
                        <ion-icon class="chevron text-[#B3B3B3] text-lg transition-transform duration-300"
                            name="chevron-down-outline"></ion-icon>
                    </button>
                    <ul class="dropdown absolute right-0 top-full bg-white shadow-xl rounded-md z-[9999]"
                        style="width:160px; opacity:0; visibility:hidden; pointer-events:none; transition: opacity 0.3s ease, visibility 0.3s ease;">
                        <li>
                            <a href="#"
                                class="lang-option flex items-center gap-3 px-4 py-2 text-black hover:bg-gray-100 text-sm"
                                data-flag="https://flagcdn.com/w20/us.png" data-label="EN">
                                <img src="https://flagcdn.com/w20/us.png" class="w-5 h-4 rounded-sm object-cover">
                                English
                            </a>
                        </li>
                        <li>
                            <a href="#"
                                class="lang-option flex items-center gap-3 px-4 py-2 text-black hover:bg-gray-100 text-sm"
                                data-flag="https://flagcdn.com/w20/fr.png" data-label="FR">
                                <img src="https://flagcdn.com/w20/fr.png" class="w-5 h-4 rounded-sm object-cover">
                                French
                            </a>
                        </li>
                        <li>
                            <a href="#"
                                class="lang-option flex items-center gap-3 px-4 py-2 text-black hover:bg-gray-100 text-sm"
                                data-flag="https://flagcdn.com/w20/es.png" data-label="ES">
                                <img src="https://flagcdn.com/w20/es.png" class="w-5 h-4 rounded-sm object-cover">
                                Spanish
                            </a>
                        </li>
                        <li>
                            <a href="#"
                                class="lang-option flex items-center gap-3 px-4 py-2 text-black hover:bg-gray-100 text-sm"
                                data-flag="https://flagcdn.com/w20/de.png" data-label="DE">
                                <img src="https://flagcdn.com/w20/de.png" class="w-5 h-4 rounded-sm object-cover">
                                German
                            </a>
                        </li>
                    </ul>
                </li> --}}

            </ul>
        </div>

        <!-- RIGHT SIDE: Mobile Menu Button -->
        <div class="flex items-center md:hidden">
            <button id="menuBtn" class="text-3xl text-white z-30 relative">
                <ion-icon name="menu-outline"></ion-icon>
            </button>
        </div>

    </nav>

    <!-- MOBILE MENU OVERLAY -->
    <div id="mobileMenu" class="fixed inset-0 bg-black bg-opacity-60 z-40 hidden">
        <div id="mobileMenuInner"
            class="fixed top-0 left-0 w-full h-full bg-[#0a0a0a] transform transition-transform duration-500 ease-in-out -translate-x-full shadow-xl overflow-y-auto">

            <!-- Top Section -->
            <div class="flex justify-between items-center px-5 py-10 border-b border-gray-800">
                <img src="{{ asset('assets/images/logo.png') }}" alt="Logo" class="h-[36px]">
                <button id="closeMenu" class="text-3xl text-white">
                    <ion-icon name="close-outline"></ion-icon>
                </button>
            </div>

            <!-- Mobile Menu Items -->
            <ul class="flex flex-col gap-1 py-6 px-4 text-gray-200">

                <li>
                    <a href="#"
                        class="block px-4 py-3 rounded-lg hover:bg-gray-800 hover:text-[#2b68e0] transition-all duration-200 font-light">
                        How It Works
                    </a>
                </li>

                <li>
                    <a href="/"
                        class="block px-4 py-3 rounded-lg hover:bg-gray-800 hover:text-[#2b68e0] transition-all duration-200 font-light">
                        Home
                    </a>
                </li>
                <li>
                    <a href="/whitepaper"
                        class="block px-4 py-3 rounded-lg hover:bg-gray-800 hover:text-[#2b68e0] transition-all duration-200 font-light">
                        Whitepaper
                    </a>
                </li>
                <li>
                    <a href="/about"
                        class="block px-4 py-3 rounded-lg hover:bg-gray-800 hover:text-[#2b68e0] transition-all duration-200 font-light">
                        About Us
                    </a>
                </li>
                <li>
                    <a href="contact"
                        class="block px-4 py-3 rounded-lg hover:bg-gray-800 hover:text-[#2b68e0] transition-all duration-200 font-light">
                        Contact
                    </a>
                </li>

                <!-- Mobile Accordion: Customer Benefits -->
                {{-- <li class="mobile-accordion">
                    <button
                        class="accordion-trigger w-full flex justify-between items-center px-4 py-3 rounded-lg hover:bg-gray-800 transition-all duration-200 font-light text-left">
                        Customer Benefits
                        <ion-icon class="mobile-chevron text-[#B3B3B3] text-lg transition-transform duration-300"
                            name="chevron-down-outline"></ion-icon>
                    </button>
                    <ul class="accordion-content hidden flex-col gap-1 pl-4 mt-1">
                        <li><a href="#"
                                class="block px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 text-sm transition-all">Option
                                1</a></li>
                        <li><a href="#"
                                class="block px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 text-sm transition-all">Option
                                2</a></li>
                        <li><a href="#"
                                class="block px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 text-sm transition-all">Option
                                3</a></li>
                    </ul>
                </li> --}}

                <!-- Mobile Accordion: Analytics -->
                {{-- <li class="mobile-accordion">
                    <button
                        class="accordion-trigger w-full flex justify-between items-center px-4 py-3 rounded-lg hover:bg-gray-800 transition-all duration-200 font-light text-left">
                        Analytics
                        <ion-icon class="mobile-chevron text-[#B3B3B3] text-lg transition-transform duration-300"
                            name="chevron-down-outline"></ion-icon>
                    </button>
                    <ul class="accordion-content hidden flex-col gap-1 pl-4 mt-1">
                        <li><a href="#"
                                class="block px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 text-sm transition-all">Overview</a>
                        </li>
                        <li><a href="#"
                                class="block px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 text-sm transition-all">Reports</a>
                        </li>
                        <li><a href="#"
                                class="block px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 text-sm transition-all">Insights</a>
                        </li>
                    </ul>
                </li> --}}

                <!-- Mobile Accordion: Currencies -->
                {{-- <li class="mobile-accordion">
                    <button
                        class="accordion-trigger w-full flex justify-between items-center px-4 py-3 rounded-lg hover:bg-gray-800 transition-all duration-200 font-light text-left">
                        Currencies
                        <ion-icon class="mobile-chevron text-[#B3B3B3] text-lg transition-transform duration-300"
                            name="chevron-down-outline"></ion-icon>
                    </button>
                    <ul class="accordion-content hidden flex-col gap-1 pl-4 mt-1">
                        <li><a href="#"
                                class="block px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 text-sm transition-all">USD</a>
                        </li>
                        <li><a href="#"
                                class="block px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 text-sm transition-all">EUR</a>
                        </li>
                        <li><a href="#"
                                class="block px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 text-sm transition-all">GBP</a>
                        </li>
                    </ul>
                </li> --}}

                <!-- Mobile Accordion: Business -->
                {{-- <li class="mobile-accordion">
                    <button
                        class="accordion-trigger w-full flex justify-between items-center px-4 py-3 rounded-lg hover:bg-gray-800 transition-all duration-200 font-light text-left">
                        Business
                        <ion-icon class="mobile-chevron text-[#B3B3B3] text-lg transition-transform duration-300"
                            name="chevron-down-outline"></ion-icon>
                    </button>
                    <ul class="accordion-content hidden flex-col gap-1 pl-4 mt-1">
                        <li><a href="#"
                                class="block px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 text-sm transition-all">Startups</a>
                        </li>
                        <li><a href="#"
                                class="block px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 text-sm transition-all">Enterprise</a>
                        </li>
                        <li><a href="#"
                                class="block px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 text-sm transition-all">Partners</a>
                        </li>
                    </ul>
                </li> --}}

                <!-- Mobile Accordion: Language -->
                {{-- <li class="mobile-accordion">
                    <button
                        class="accordion-trigger w-full flex justify-between items-center px-4 py-3 rounded-lg hover:bg-gray-800 transition-all duration-200 font-light text-left">
                        <span class="flex items-center gap-2">
                            <img id="mobileFlagImg" src="https://flagcdn.com/w20/us.png"
                                class="w-5 h-4 rounded-sm object-cover">
                            <span id="mobileLangLabel">English</span>
                        </span>
                        <ion-icon class="mobile-chevron text-[#B3B3B3] text-lg transition-transform duration-300"
                            name="chevron-down-outline"></ion-icon>
                    </button>
                    <ul class="accordion-content hidden flex-col gap-1 pl-4 mt-1">
                        <li>
                            <a href="#"
                                class="mobile-lang flex items-center gap-3 px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 text-sm transition-all"
                                data-flag="https://flagcdn.com/w20/us.png" data-label="EN" data-name="English">
                                <img src="https://flagcdn.com/w20/us.png" class="w-5 h-4 rounded-sm object-cover">
                                English
                            </a>
                        </li>
                        <li>
                            <a href="#"
                                class="mobile-lang flex items-center gap-3 px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 text-sm transition-all"
                                data-flag="https://flagcdn.com/w20/fr.png" data-label="FR" data-name="French">
                                <img src="https://flagcdn.com/w20/fr.png" class="w-5 h-4 rounded-sm object-cover">
                                French
                            </a>
                        </li>
                        <li>
                            <a href="#"
                                class="mobile-lang flex items-center gap-3 px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 text-sm transition-all"
                                data-flag="https://flagcdn.com/w20/sa.png" data-label="AR" data-name="Arabic">
                                <img src="https://flagcdn.com/w20/sa.png" class="w-5 h-4 rounded-sm object-cover">
                                Arabic
                            </a>
                        </li>
                        <li>
                            <a href="#"
                                class="mobile-lang flex items-center gap-3 px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 text-sm transition-all"
                                data-flag="https://flagcdn.com/w20/es.png" data-label="ES" data-name="Spanish">
                                <img src="https://flagcdn.com/w20/es.png" class="w-5 h-4 rounded-sm object-cover">
                                Spanish
                            </a>
                        </li>
                        <li>
                            <a href="#"
                                class="mobile-lang flex items-center gap-3 px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 text-sm transition-all"
                                data-flag="https://flagcdn.com/w20/de.png" data-label="DE" data-name="German">
                                <img src="https://flagcdn.com/w20/de.png" class="w-5 h-4 rounded-sm object-cover">
                                German
                            </a>
                        </li>
                        <li>
                            <a href="#"
                                class="mobile-lang flex items-center gap-3 px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 text-sm transition-all"
                                data-flag="https://flagcdn.com/w20/pk.png" data-label="UR" data-name="Urdu">
                                <img src="https://flagcdn.com/w20/pk.png" class="w-5 h-4 rounded-sm object-cover"> Urdu
                            </a>
                        </li>
                    </ul>
                </li> --}}

            </ul>
        </div>
    </div>
</header>

<script>
    // ── Mobile Menu ──────────────────────────────────────────
    const menuBtn = document.getElementById('menuBtn');
    const closeMenu = document.getElementById('closeMenu');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuInner = document.getElementById('mobileMenuInner');

    function openMenu() {
        mobileMenu.classList.remove('hidden');
        setTimeout(() => mobileMenuInner.classList.remove('-translate-x-full'), 10);
        document.body.style.overflow = 'hidden';
    }

    function closeMenuFunc() {
        mobileMenuInner.classList.add('-translate-x-full');
        setTimeout(() => mobileMenu.classList.add('hidden'), 500);
        document.body.style.overflow = 'auto';
    }

    menuBtn.addEventListener('click', openMenu);
    closeMenu.addEventListener('click', closeMenuFunc);
    mobileMenu.addEventListener('click', (e) => { if (e.target === mobileMenu) closeMenuFunc(); });

    // ── Desktop Dropdowns (hover) ────────────────────────────
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        const dropdown = menu.querySelector('.dropdown');
        const chevron = menu.querySelector('.chevron');

        menu.addEventListener('mouseenter', () => {
            dropdown.style.opacity = '1';
            dropdown.style.visibility = 'visible';
            dropdown.style.pointerEvents = 'auto';
            chevron.style.transform = 'rotate(180deg)';
        });

        menu.addEventListener('mouseleave', () => {
            dropdown.style.opacity = '0';
            dropdown.style.visibility = 'hidden';
            dropdown.style.pointerEvents = 'none';
            chevron.style.transform = 'rotate(0deg)';
        });
    });

    // ── Desktop Language Selector ────────────────────────────
    document.querySelectorAll('.lang-option').forEach(option => {
        option.addEventListener('click', (e) => {
            e.preventDefault();
            document.getElementById('activeFlagImg').src = option.dataset.flag;
            document.getElementById('activeLangLabel').textContent = option.dataset.label;
        });
    });

    // ── Mobile Accordions ────────────────────────────────────
    document.querySelectorAll('.mobile-accordion').forEach(item => {
        const trigger = item.querySelector('.accordion-trigger');
        const content = item.querySelector('.accordion-content');
        const chevron = item.querySelector('.mobile-chevron');

        trigger.addEventListener('click', () => {
            const isOpen = !content.classList.contains('hidden');

            // Close all other accordions
            document.querySelectorAll('.mobile-accordion').forEach(other => {
                other.querySelector('.accordion-content').classList.add('hidden');
                other.querySelector('.accordion-content').classList.remove('flex');
                other.querySelector('.mobile-chevron').style.transform = 'rotate(0deg)';
            });

            // Toggle current
            if (!isOpen) {
                content.classList.remove('hidden');
                content.classList.add('flex');
                chevron.style.transform = 'rotate(180deg)';
            }
        });
    });

    // ── Mobile Language Selector ─────────────────────────────
    document.querySelectorAll('.mobile-lang').forEach(option => {
        option.addEventListener('click', (e) => {
            e.preventDefault();
            document.getElementById('mobileFlagImg').src = option.dataset.flag;
            document.getElementById('mobileLangLabel').textContent = option.dataset.name;
            // Also sync desktop
            document.getElementById('activeFlagImg').src = option.dataset.flag;
            document.getElementById('activeLangLabel').textContent = option.dataset.label;
        });
    });
</script>