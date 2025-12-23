@extends('layout.master')
@section('content')
    <!-- Hero Section -->
    <div class="h-[50rem] w-full bg-cover bg-center relative flex items-center justify-center px-4"
        style="background-image: url('{{ asset('assets/images/bgbg.png') }}');">

        <!-- COIN 1 -->
        <div
            class="hidden lg:flex absolute top-26 left-10 lg:left-20 xl:left-60 2xl:left-90 h-[65px] w-[65px] rounded-full bg-[rgba(254,231,21,0.15)] animate-pulseGlow items-center justify-center">
            <img src="{{ asset('assets/new assets/coin1.png') }}" class="w-10" alt="">
        </div>

        <!-- COIN 2 -->
        <div
            class="hidden lg:flex absolute top-66 left-0 lg:left-5 xl:left-40 2xl:left-70 h-[65px] w-[65px] rounded-full bg-[rgba(90,40,176,0.15)] animate-heartBeat items-center justify-center">
            <img src="{{ asset('assets/new assets/coin4.png') }}" class="w-10" alt="">
        </div>

        <!-- COIN 3 -->
        <div
            class="hidden lg:flex absolute top-26 right-10 lg:right-20 xl:right-60 2xl:right-90 h-[65px] w-[65px] rounded-full bg-[rgba(51,135,90,0.15)] animate-heartBeat items-center justify-center">
            <img src="{{ asset('assets/new assets/coin2.png') }}" class="w-10" alt="">
        </div>

        <!-- COIN 4 -->
        <div
            class="hidden lg:flex absolute top-66 right-0 lg:right-5 xl:right-40 2xl:right-70 h-[65px] w-[65px] rounded-full bg-[rgba(204,49,61,0.15)] animate-pulseGlow items-center justify-center">
            <img src="{{ asset('assets/new assets/coin3.png') }}" class="w-10" alt="">
        </div>



        <!-- ======= MAIN CONTENT ======= -->
        <div class="text-center text-white max-w-[55rem] w-full flex flex-col items-center">

            <h1 class="text-3xl md:text-5xl font-bold drop-shadow-xl leading-normal">
                Move Value Across Chains
            </h1>

            <p class="text-base md:text-lg font-light mt-6 opacity-90 leading-relaxed w-[90%] md:w-[75%]">
                ZihuBridge connects Stellar and Ripple into one seamless swap experience, abstracting complexity while
                preserving transparency and security
            </p>

            <!-- ===== CARD UI ===== -->
            <div class="bg-white rounded-3xl shadow-xl mt-10 p-6 w-full max-w-[35rem] flex flex-col gap-5">

                <h2 class="text-xl font-bold text-black">Select Blockchain</h2>

                <select id="fromBlockchain"
                    class="bg-[#EEF2F9] rounded-xl px-5 py-3 w-full text-black shadow-sm">
                    <option selected disabled>From Blockchain</option>
                </select>

                <div class="w-12 h-12 flex items-center justify-center rounded bg-[#EEF2F9] mx-auto">
                    <img src="{{ asset('assets/new assets/Icon.png') }}" class="w-6" alt="">
                </div>

                <select id="toBlockchain"
                    class="bg-[#EEF2F9] rounded-xl px-5 py-3 w-full text-black shadow-sm">
                    <option selected disabled>To Blockchain</option>
                </select>

                <a href="{{ route('exchange') }}"
                    class="w-full bg-blue-600 text-white py-2 rounded-full text-lg font-semibold hover:bg-transparent hover:text-blue-600 border border-blue-600 transition">
                    Swap Now
                </a>
            </div>
        </div>
    </div>
    <!-- Hero Section -->

    <!-- Toncoin Section -->
    <section class="max-w-7xl mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 items-center">

            <!-- Left column: Image -->
            <div class="flex justify-center md:justify-start">
                <img src="{{ asset('assets/new assets/planet.png') }}" alt="Sample"
                    class="w-xl h-auto rounded-lg object-cover">
            </div>

            <!-- Right column: Content -->
            <div class="p-5">
                <h2 class="text-3xl font-bold mb-4 text-center md:text-left">Infrastructure for Cross-Chain Builders</h2>
                <p class="text-gray-600 mb-4 text-center md:text-left">
                    ZihuBridge provides developers and projects with a reliable way to move liquidity between Stellar and
                    XRPL ecosystems.
                    Whether you’re launching a token, enabling cross-chain access, or simplifying user onboarding —
                    ZihuBridge handles the hard parts.
                </p>
                {{-- <div class="flex justify-center md:justify-start">
                    <a href="#"
                        class=" bg-blue-600 text-white py-2 px-4 rounded-full text-lg font-semibold hover:bg-transparent hover:text-blue-600 border border-blue-600 transition">
                        Explore ZihuBridge
                    </a>
                </div> --}}
            </div>
        </div>
    </section>
    <!-- Toncoin Section -->

    <!-- CryptoCap Section -->
    <section class="max-w-7xl mx-auto px-4 py-12">
        <!-- Heading -->
        <div class="text-center mb-10">
            <h2 class="text-3xl font-bold">ZihuBridge Core Features</h2>
            <p class="text-gray-600 mt-2">
                Built for secure, transparent, and reliable cross-chain execution
            </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-stretch">

            <div class="relative rounded-3xl p-5 border border-[#EAEAEA] flex flex-col h-full bg-white z-1">
                <span class="px-3 py-1 text-md font-semibold bg-[#EDF4FF] text-[#0066FF] rounded-2md w-fit">
                    Privacy
                </span>

                <div class="mt-10 bg-[#EDF4FF] p-4 rounded-full w-fit h-fit">
                    <img src="{{ asset('assets/new assets/Frame2.png') }}" class="w-10 h-10 object-cover rounded-full">
                </div>

                <div class="mt-10">
                    <h2 class="text-md font-bold">No Account Required</h2>
                    <p class="text-gray-500 mt-2">ZihuBridge does not require user accounts or stored balances.</p>
                </div>
            </div>

            <div class="relative rounded-3xl p-5 border border-[#EAEAEA] flex flex-col h-full bg-white z-1">
                <span class="px-3 py-1 text-md font-semibold bg-[#EDF4FF] text-[#0066FF] rounded-2md w-fit">
                    Wide choice
                </span>

                <div class="mt-10 bg-[#EDF4FF] p-4 rounded-full w-fit h-fit">
                    <img src="{{ asset('assets/new assets/Frame3.png') }}" class="w-10 h-10 object-cover rounded-full">
                </div>

                <div class="mt-10">
                    <h2 class="text-md font-bold">Focused Asset Support</h2>
                    <p class="text-gray-500 mt-2">ZihuBridge supports carefully selected assets across Stellar and Ripple.
                    </p>
                </div>
            </div>

            <div class="relative rounded-3xl p-5 border border-[#EAEAEA] flex flex-col h-full bg-white z-1">
                <span class="px-3 py-1 text-md font-semibold bg-[#EDF4FF] text-[#0066FF] rounded-2md w-fit">
                    24/7 support
                </span>

                <div class="mt-10 bg-[#EDF4FF] p-4 rounded-full w-fit h-fit">
                    <img src="{{ asset('assets/new assets/Frame4.png') }}" class="w-10 h-10 object-cover rounded-full">
                </div>

                <div class="mt-10">
                    <h2 class="text-md font-bold">Execution You Can Track</h2>
                    <p class="text-gray-500 mt-2">Every swap follows a clear execution path with visible status updates.</p>
                </div>
            </div>

            <div class="relative rounded-3xl p-5 border border-[#EAEAEA] flex flex-col h-full bg-white z-1">
                <span class="px-3 py-1 text-md font-semibold bg-[#EDF4FF] text-[#0066FF] rounded-2md w-fit">
                    Safety
                </span>

                <div class="mt-10 bg-[#EDF4FF] p-4 rounded-full w-fit h-fit">
                    <img src="{{ asset('assets/new assets/Frame1.png') }}" class="w-10 h-10 object-cover rounded-full">
                </div>

                <div class="mt-10">
                    <h2 class="text-md font-bold">ZihuBridge does not hold user balances</h2>
                    <p class="text-gray-500 mt-2">Funds move only for execution and are returned automatically if a swap
                        cannot be completed.
                    </p>
                </div>
            </div>

        </div>

    </section>
    <!-- CryptoCap Section -->

    <!-- Exchange Table -->
    <div class="pt-50 pb-30 mt-[-9rem]"
        style="background-image: url('{{ asset('assets/new assets/bg-gradient.png') }}'); background-repeat: no-repeat; background-size: cover; ">

        {{-- <div class="max-w-7xl mx-auto px-4 py-12 bg-[linear-gradient(135deg,#F2EEFE_0%,#F5FAFF_19%,#F6FCFF_63%,#E0ECFD_100%)] rounded-4xl flex flex-col items-center">
            <!-- Heading -->
            <h2 class="text-center text-3xl font-bold mt-10">Top Pairs on SimpleSwap</h2>
            <p class="text-center text-gray-600 mt-2 max-w-2xl">
                Explore sensational features to prepare your best investment in cryptocurrency
            </p>

            <!-- Two Columns -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-15 mt-14 w-full p-7">

                <!-- Column 1 -->
                <div>
                    <h3 class="text-xl font-semibold mb-4">Crypto-to-crypto</h3>
                    <div class="grid grid-cols-1 gap-10 mt-5 w-full">

                        <!-- ROW TEMPLATE - COPY FOR ALL ROWS -->
                        <div class="bg-white p-3 flex flex-wrap items-center justify-between gap-3 rounded-xl">
                            <div class="text-sm">1</div>

                            <div class="flex items-center gap-2 min-w-[80px]">
                                <img src="{{ asset('assets/new assets/eth.png') }}" alt="">
                                <span class="font-semibold text-sm">ETH</span>
                            </div>

                            <div class="bg-[#7ABCE7] text-white py-1 px-3 rounded-full text-sm">ETH</div>

                            <div class="text-2xl font-light text-[#B3B3B3] shrink-0">⟶</div>

                            <div class="flex items-center gap-2 min-w-[80px] font-semibold">
                                <img src="{{ asset('assets/new assets/solana.png') }}" alt="">
                                <span>SOL</span>
                            </div>

                            <div class="bg-[#9B6CEA] text-white py-1 px-3 rounded-full text-sm">SOL</div>

                            <div class="shrink-0">
                                <ion-icon class="text-[#B3B3B3] text-xl" name="chevron-forward-outline"></ion-icon>
                            </div>
                        </div>
                        <!-- END TEMPLATE -->

                        <!-- Row 2 -->
                        <div class="bg-white p-3 flex flex-wrap items-center justify-between gap-3 rounded-xl">
                            <div class="text-sm">2</div>

                            <div class="flex items-center gap-2 min-w-[80px]">
                                <img src="{{ asset('assets/new assets/solana.png') }}" alt="">
                                <span class="font-semibold text-sm">SOL</span>
                            </div>

                            <div class="bg-[#9B6CEA] text-white py-1 px-3 rounded-full text-sm">SOL</div>

                            <div class="text-2xl font-light text-[#B3B3B3] shrink-0">⟶</div>

                            <div class="flex items-center gap-2 min-w-[80px] font-semibold">
                                <img src="{{ asset('assets/new assets/eth.png') }}" alt="">
                                <span>ETH</span>
                            </div>

                            <div class="bg-[#7ABCE7] text-white py-1 px-3 rounded-full text-sm">ETH</div>

                            <div class="shrink-0">
                                <ion-icon class="text-[#B3B3B3] text-xl" name="chevron-forward-outline"></ion-icon>
                            </div>
                        </div>

                        <!-- Row 3 -->
                        <div class="bg-white p-3 flex flex-wrap items-center justify-between gap-3 rounded-xl">
                            <div class="text-sm">3</div>

                            <div class="flex items-center gap-2 min-w-[80px]">
                                <img src="{{ asset('assets/new assets/eth.png') }}" alt="">
                                <span class="font-semibold text-sm">ETH</span>
                            </div>

                            <div class="bg-[#7ABCE7] text-white py-1 px-3 rounded-full text-sm">ETH</div>

                            <div class="text-2xl font-light text-[#B3B3B3] shrink-0">⟶</div>

                            <div class="flex items-center gap-2 min-w-[80px] font-semibold">
                                <img src="{{ asset('assets/new assets/bitcoin.png') }}" alt="">
                                <span>BTC</span>
                            </div>

                            <div class="bg-[#F0AC3B] text-white py-1 px-3 rounded-full text-sm">BTC</div>

                            <div class="shrink-0">
                                <ion-icon class="text-[#B3B3B3] text-xl" name="chevron-forward-outline"></ion-icon>
                            </div>
                        </div>

                        <!-- Row 4 -->
                        <div class="bg-white p-3 flex flex-wrap items-center justify-between gap-3 rounded-xl">
                            <div class="text-sm">4</div>

                            <div class="flex items-center gap-2 min-w-[80px]">
                                <img src="{{ asset('assets/new assets/bitcoin.png') }}" alt="">
                                <span class="font-semibold text-sm">BTC</span>
                            </div>

                            <div class="bg-[#F0AC3B] text-white py-1 px-3 rounded-full text-sm">BTC</div>

                            <div class="text-2xl font-light text-[#B3B3B3] shrink-0">⟶</div>

                            <div class="flex items-center gap-2 min-w-[80px] font-semibold">
                                <img src="{{ asset('assets/new assets/solana.png') }}" alt="">
                                <span>SOL</span>
                            </div>

                            <div class="bg-[#9B6CEA] text-white py-1 px-3 rounded-full text-sm">SOL</div>

                            <div class="shrink-0">
                                <ion-icon class="text-[#B3B3B3] text-xl" name="chevron-forward-outline"></ion-icon>
                            </div>
                        </div>

                        <!-- Row 5 -->
                        <div class="bg-white p-3 flex flex-wrap items-center justify-between gap-3 rounded-xl">
                            <div class="text-sm">5</div>

                            <div class="flex items-center gap-2 min-w-[80px]">
                                <img src="{{ asset('assets/new assets/eth.png') }}" alt="">
                                <span class="font-semibold text-sm">ETH</span>
                            </div>

                            <div class="bg-[#7ABCE7] text-white py-1 px-3 rounded-full text-sm">ETH</div>

                            <div class="text-2xl font-light text-[#B3B3B3] shrink-0">⟶</div>

                            <div class="flex items-center gap-2 min-w-[80px] font-semibold">
                                <img src="{{ asset('assets/new assets/bitcoin.png') }}" alt="">
                                <span>BTC</span>
                            </div>

                            <div class="bg-[#F0AC3B] text-white py-1 px-3 rounded-full text-sm">BTC</div>

                            <div class="shrink-0">
                                <ion-icon class="text-[#B3B3B3] text-xl" name="chevron-forward-outline"></ion-icon>
                            </div>
                        </div>

                        <!-- Row 6 -->
                        <div class="bg-white p-3 flex flex-wrap items-center justify-between gap-3 rounded-xl">
                            <div class="text-sm">6</div>

                            <div class="flex items-center gap-2 min-w-[80px]">
                                <img src="{{ asset('assets/new assets/bitcoin.png') }}" alt="">
                                <span class="font-semibold text-sm">BTC</span>
                            </div>

                            <div class="bg-[#F0AC3B] text-white py-1 px-3 rounded-full text-sm">BTC</div>

                            <div class="text-2xl font-light text-[#B3B3B3] shrink-0">⟶</div>

                            <div class="flex items-center gap-2 min-w-[80px] font-semibold">
                                <img src="{{ asset('assets/new assets/tpay.png') }}" alt="">
                                <span>TPAY</span>
                            </div>

                            <div class="bg-[#2C58A7] text-white py-1 px-3 rounded-full text-sm">TPAY</div>

                            <div class="shrink-0">
                                <ion-icon class="text-[#B3B3B3] text-xl" name="chevron-forward-outline"></ion-icon>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Column 2 -->
                <div>
                    <h3 class="text-xl font-semibold mb-4">Fiat-to-crypto</h3>

                    <div class="grid grid-cols-1 gap-10 mt-5 w-full">

                        <!-- ROW TEMPLATE - COPY FOR ALL ROWS -->
                        <div class="bg-white p-3 flex flex-wrap items-center justify-between gap-3 rounded-xl">
                            <div class="text-sm">1</div>

                            <div class="flex items-center gap-2 min-w-[80px]">
                                <img src="{{ asset('assets/new assets/eth.png') }}" alt="">
                                <span class="font-semibold text-sm">ETH</span>
                            </div>

                            <div class="bg-[#7ABCE7] text-white py-1 px-3 rounded-full text-sm">ETH</div>

                            <div class="text-2xl font-light text-[#B3B3B3] shrink-0">⟶</div>

                            <div class="flex items-center gap-2 min-w-[80px] font-semibold">
                                <img src="{{ asset('assets/new assets/solana.png') }}" alt="">
                                <span>SOL</span>
                            </div>

                            <div class="bg-[#9B6CEA] text-white py-1 px-3 rounded-full text-sm">SOL</div>

                            <div class="shrink-0">
                                <ion-icon class="text-[#B3B3B3] text-xl" name="chevron-forward-outline"></ion-icon>
                            </div>
                        </div>
                        <!-- END TEMPLATE -->

                        <!-- Row 2 -->
                        <div class="bg-white p-3 flex flex-wrap items-center justify-between gap-3 rounded-xl">
                            <div class="text-sm">2</div>

                            <div class="flex items-center gap-2 min-w-[80px]">
                                <img src="{{ asset('assets/new assets/solana.png') }}" alt="">
                                <span class="font-semibold text-sm">SOL</span>
                            </div>

                            <div class="bg-[#9B6CEA] text-white py-1 px-3 rounded-full text-sm">SOL</div>

                            <div class="text-2xl font-light text-[#B3B3B3] shrink-0">⟶</div>

                            <div class="flex items-center gap-2 min-w-[80px] font-semibold">
                                <img src="{{ asset('assets/new assets/eth.png') }}" alt="">
                                <span>ETH</span>
                            </div>

                            <div class="bg-[#7ABCE7] text-white py-1 px-3 rounded-full text-sm">ETH</div>

                            <div class="shrink-0">
                                <ion-icon class="text-[#B3B3B3] text-xl" name="chevron-forward-outline"></ion-icon>
                            </div>
                        </div>

                        <!-- Row 3 -->
                        <div class="bg-white p-3 flex flex-wrap items-center justify-between gap-3 rounded-xl">
                            <div class="text-sm">3</div>

                            <div class="flex items-center gap-2 min-w-[80px]">
                                <img src="{{ asset('assets/new assets/eth.png') }}" alt="">
                                <span class="font-semibold text-sm">ETH</span>
                            </div>

                            <div class="bg-[#7ABCE7] text-white py-1 px-3 rounded-full text-sm">ETH</div>

                            <div class="text-2xl font-light text-[#B3B3B3] shrink-0">⟶</div>

                            <div class="flex items-center gap-2 min-w-[80px] font-semibold">
                                <img src="{{ asset('assets/new assets/bitcoin.png') }}" alt="">
                                <span>BTC</span>
                            </div>

                            <div class="bg-[#F0AC3B] text-white py-1 px-3 rounded-full text-sm">BTC</div>

                            <div class="shrink-0">
                                <ion-icon class="text-[#B3B3B3] text-xl" name="chevron-forward-outline"></ion-icon>
                            </div>
                        </div>

                        <!-- Row 4 -->
                        <div class="bg-white p-3 flex flex-wrap items-center justify-between gap-3 rounded-xl">
                            <div class="text-sm">4</div>

                            <div class="flex items-center gap-2 min-w-[80px]">
                                <img src="{{ asset('assets/new assets/bitcoin.png') }}" alt="">
                                <span class="font-semibold text-sm">BTC</span>
                            </div>

                            <div class="bg-[#F0AC3B] text-white py-1 px-3 rounded-full text-sm">BTC</div>

                            <div class="text-2xl font-light text-[#B3B3B3] shrink-0">⟶</div>

                            <div class="flex items-center gap-2 min-w-[80px] font-semibold">
                                <img src="{{ asset('assets/new assets/solana.png') }}" alt="">
                                <span>SOL</span>
                            </div>

                            <div class="bg-[#9B6CEA] text-white py-1 px-3 rounded-full text-sm">SOL</div>

                            <div class="shrink-0">
                                <ion-icon class="text-[#B3B3B3] text-xl" name="chevron-forward-outline"></ion-icon>
                            </div>
                        </div>

                        <!-- Row 5 -->
                        <div class="bg-white p-3 flex flex-wrap items-center justify-between gap-3 rounded-xl">
                            <div class="text-sm">5</div>

                            <div class="flex items-center gap-2 min-w-[80px]">
                                <img src="{{ asset('assets/new assets/eth.png') }}" alt="">
                                <span class="font-semibold text-sm">ETH</span>
                            </div>

                            <div class="bg-[#7ABCE7] text-white py-1 px-3 rounded-full text-sm">ETH</div>

                            <div class="text-2xl font-light text-[#B3B3B3] shrink-0">⟶</div>

                            <div class="flex items-center gap-2 min-w-[80px] font-semibold">
                                <img src="{{ asset('assets/new assets/bitcoin.png') }}" alt="">
                                <span>BTC</span>
                            </div>

                            <div class="bg-[#F0AC3B] text-white py-1 px-3 rounded-full text-sm">BTC</div>

                            <div class="shrink-0">
                                <ion-icon class="text-[#B3B3B3] text-xl" name="chevron-forward-outline"></ion-icon>
                            </div>
                        </div>

                        <!-- Row 6 -->
                        <div class="bg-white p-3 flex flex-wrap items-center justify-between gap-3 rounded-xl">
                            <div class="text-sm">6</div>

                            <div class="flex items-center gap-2 min-w-[80px]">
                                <img src="{{ asset('assets/new assets/bitcoin.png') }}" alt="">
                                <span class="font-semibold text-sm">BTC</span>
                            </div>

                            <div class="bg-[#F0AC3B] text-white py-1 px-3 rounded-full text-sm">BTC</div>

                            <div class="text-2xl font-light text-[#B3B3B3] shrink-0">⟶</div>

                            <div class="flex items-center gap-2 min-w-[80px] font-semibold">
                                <img src="{{ asset('assets/new assets/tpay.png') }}" alt="">
                                <span>TPAY</span>
                            </div>

                            <div class="bg-[#2C58A7] text-white py-1 px-3 rounded-full text-sm">TPAY</div>

                            <div class="shrink-0">
                                <ion-icon class="text-[#B3B3B3] text-xl" name="chevron-forward-outline"></ion-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}

        <!-- Get Started Section -->
        <div class="max-w-7xl mx-auto px-4 mt-40">
            <!-- Heading -->
            <div class="text-center mb-10">
                <h2 class="text-3xl font-bold text-white">How ZihuBridge Works</h2>
                <p class="text-white mt-2">
                    A streamlined cross-chain swap flow without wallet connections or accounts
                </p>
                {{-- <button
                    class="mt-10 bg-blue-600 text-white py-2 px-5 rounded-full text-lg font-semibold hover:bg-transparent hover:text-blue-600 border border-blue-600 transition">
                    Get Started
                </button> --}}
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

                <div
                    class="rounded-2xl px-5 py-10 border border-[#EAEAEA] bg-white flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-4">
                    <div class="bg-[#EDF4FF] p-4 rounded-full shrink-0">
                        <img src="{{ asset('assets/new assets/Frame5.png') }}" class="w-10 h-10">
                    </div>
                    <div>
                        <h2 class="text-md font-bold">Select Assets & Destination</h2>
                        <p class="text-gray-500 mt-2">
                            Choose the source blockchain, asset, and destination wallet address.
                        </p>
                    </div>
                </div>

                <div
                    class="rounded-2xl px-5 py-10 border border-[#EAEAEA] bg-white flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-4">
                    <div class="bg-[#EDF4FF] p-4 rounded-full shrink-0">
                        <img src="{{ asset('assets/new assets/Frame6.png') }}" class="w-10 h-10">
                    </div>
                    <div>
                        <h2 class="text-md font-bold">Send Funds to Execute</h2>
                        <p class="text-gray-500 mt-2">
                            ZihuBridge provides a temporary execution address for the swap.
                        </p>
                    </div>
                </div>

                <div
                    class="rounded-2xl px-5 py-10 border border-[#EAEAEA] bg-white flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-4">
                    <div class="bg-[#EDF4FF] p-4 rounded-full shrink-0">
                        <img src="{{ asset('assets/new assets/Frame4.png') }}" class="w-10 h-10">
                    </div>
                    <div>
                        <h2 class="text-md font-bold">Receive on Destination Chain</h2>
                        <p class="text-gray-500 mt-2">
                            Assets are routed through verified liquidity and cross-chain paths.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Get Started Section -->
    </div>
    <!-- Exchange Table -->
@endsection
