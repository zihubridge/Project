@extends('layout.master')
@section('content')

    <!-- Hero Section -->
    <section
        class="relative min-h-screen w-full overflow-hidden bg-cover bg-center px-4 pt-32 pb-12 sm:px-6 sm:pt-36 sm:pb-16 lg:px-8 lg:pt-40  "
        style="background-image: url('{{ asset('assets/images/hero-bg.png') }}');">

        <!-- COIN 1 -->
        <div
            class="animate-float absolute left-[6%] top-[18%] hidden h-7 w-7 items-center justify-center rounded-full bg-[rgba(254,231,21,0.15)] md:flex lg:left-[10%] lg:top-[15%] lg:h-9 lg:w-9 xl:left-[14%] xl:top-[20%]">
            <img src="{{ asset('assets/images/coin1.png') }}" class="w-4 lg:w-6" alt="">
        </div>

        <!-- COIN 2 -->
        <div
            class="animate-float absolute right-[8%] top-[30%] hidden h-10 w-10 items-center justify-center rounded-full bg-[rgba(90,40,176,0.15)] md:flex lg:right-[46%] lg:top-[34%] lg:h-[55px] lg:w-[55px] xl:right-[48%] xl:top-[33%]">
            <img src="{{ asset('assets/images/coin4.png') }}" class="w-5 lg:w-8" alt="">
        </div>

        <!-- COIN 3 -->
        <div
            class="animate-float absolute right-[14%] top-[20%] hidden h-8 w-8 items-center justify-center rounded-full bg-[rgba(51,135,90,0.15)] md:flex lg:left-[49%] lg:right-auto lg:top-[20%] lg:h-[55px] lg:w-[55px] xl:left-[50%]">
            <img src="{{ asset('assets/images/coin2.png') }}" class="w-4 lg:w-8" alt="">
        </div>

        <!-- COIN 4 -->
        <div
            class="animate-float absolute bottom-[20%] left-[5%] hidden h-8 w-8 items-center justify-center rounded-full bg-[rgba(204,49,61,0.15)] md:flex lg:bottom-auto lg:left-[40%] lg:top-[40%] lg:h-[55px] lg:w-[55px] xl:left-[8%] xl:top-[26%]">
            <img src="{{ asset('assets/images/coin3.png') }}" class="w-4 lg:w-8" alt="">
        </div>
        <div class="relative z-10 mx-auto flex max-w-6xl flex-col gap-12 md:flex-row  md:gap-12 lg:gap-20 xl:gap-28">

            <div class="w-full lg:w-1/2">
                <div class="flex justify-center">
                    <div>
                        <!-- Heading -->
                        <p
                            class="max-w-xl pb-6 text-3xl font-semibold leading-tight text-white sm:text-4xl md:pb-8 md:text-[2.75rem] lg:text-5xl">
                            Limitless <span class="text-[#FFDA58]">Web3.0</span>
                            Crypto Exchange by <span class="text-[#FFDA58] font-bold">ZIHU</span> <span
                                class="font-bold">Bridge</span>
                        </p>

                        <!-- List -->
                        <ul
                            class="list-disc space-y-3 pb-8 pl-5 text-base leading-relaxed font-medium text-white sm:text-lg md:pb-10">
                            <li>Best prices across top-10 exchanges</li>
                            <li>Lower fees on every trade</li>
                            <li>No custodial funds required</li>
                            <li>Fast setup & execution</li>
                        </ul>

                        <a href="{{ route('contact') }}" class="w-full block">
                            <button
                                class="px-6 py-3 font-semibold text-white rounded-lg bg-gradient-to-r from-[#365FB5] to-[#9777DB] hover:from-[#9777DB] hover:to-[#365FB5] transform hover:scale-105 transition-all duration-500 ease-in-out shadow-lg hover:shadow-2xl w-full">
                                Get in touch
                            </button>
                        </a>

                        <div class="flex items-center justify-between px-5 py-5">
                            <div>
                                <p class="text-3xl font-semibold text-white">2 Min</p>
                                <p class="sm:text-lg md:text-xl lg:text-2xl text-[#ffffffb9]">Avg Exchange Time</p>
                            </div>
                            <div class="w-px h-12 bg-white/30 mx-5"></div>
                            <div>
                                <p class="text-3xl font-semibold text-white">5 M</p>
                                <p class="sm:text-lg md:text-xl lg:text-2xl text-[#ffffffb9]">Satisfied Clients</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-1/2">
                <!-- Gradient Header -->
                <div
                    class="rounded-t-2xl bg-gradient-to-br from-[#2AD5F1] via-[#365FB5] to-[#9777DB] px-4 py-4 text-center text-lg font-medium text-white sm:text-xl lg:text-2xl">
                    Swap Tokens Across <span class="text-[#FFDA58]">Stellar</span> & <span class="text-[#FFDA58]">XRPL</span>
                </div>

                <!-- Gradient Border Wrapper -->
                <div
                    style="background: linear-gradient(to bottom right, #365FB5, #9777DB, #2AD5F1); padding: 0 3px 3px 3px; border-radius: 0 0 16px 16px;">
                    <div class="w-full bg-[#1f1132] p-5 sm:p-6 lg:p-8" style="border-radius: 0 0 14px 14px;">

                        <!-- From Currency -->
                        <div class="relative currency-select mb-5">
                            <button
                                class="currency-btn w-full flex items-center justify-between gap-2 px-4 py-4 bg-[#eef2f908] border border-white/10 rounded-xl text-white hover:border-white/30 transition-all duration-200">
                                <span class="flex items-center gap-2">
                                    <img class="currency-flag w-6 h-6 rounded-full object-cover"
                                        src="{{ asset('assets/images/btc.png') }}" alt="BTC">
                                    <span class="currency-name text-sm font-medium">BTC</span>
                                </span>
                                <ion-icon name="chevron-down-outline"
                                    class="currency-chevron text-white/50 text-lg transition-transform duration-300"></ion-icon>
                            </button>

                            <!-- Dropdown -->
                            <ul
                                class="currency-dropdown hidden absolute left-0 top-full mt-2 w-full bg-[#1a0f2e] border border-white/10 rounded-lg z-50 overflow-hidden">
                                <li>
                                    <a href="#"
                                        class="currency-option flex items-center gap-3 px-4 py-3 hover:bg-white/10 transition-all"
                                        data-name="BTC" data-img="{{ asset('assets/images/btc.png') }}">
                                        <img src="{{ asset('assets/images/btc.png') }}"
                                            class="w-6 h-6 rounded-full object-cover">
                                        <div>
                                            <p class="text-white text-sm font-medium">Bitcoin</p>
                                            <p class="text-white/40 text-xs">BTC</p>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="#"
                                        class="currency-option flex items-center gap-3 px-4 py-3 hover:bg-white/10 transition-all"
                                        data-name="XRP" data-img="{{ asset('assets/images/ripple.png') }}">
                                        <img src="{{ asset('assets/images/ripple.png') }}"
                                            class="w-6 h-6 rounded-full object-cover">
                                        <div>
                                            <p class="text-white text-sm font-medium">Ripple</p>
                                            <p class="text-white/40 text-xs">XRP</p>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="#"
                                        class="currency-option flex items-center gap-3 px-4 py-3 hover:bg-white/10 transition-all"
                                        data-name="XLM" data-img="{{ asset('assets/images/stellar.png') }}">
                                        <img src="{{ asset('assets/images/stellar.png') }}"
                                            class="w-6 h-6 rounded-full object-cover">
                                        <div>
                                            <p class="text-white text-sm font-medium">Stellar</p>
                                            <p class="text-white/40 text-xs">XLM</p>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <!-- Swap Icon -->
                        <div class="my-5 flex items-center gap-3">
                            <!-- <div class="flex-1 h-px bg-white/10"></div> -->
                            <button
                                class="w-9 h-9 flex items-center justify-center mx-auto rounded-sm bg-white/10 hover:bg-white/20 transition-all duration-200 text-white">
                                <ion-icon name="swap-vertical-outline" class="text-xl"></ion-icon>
                            </button>
                            <!-- <div class="flex-1 h-px bg-white/10"></div> -->
                        </div>

                        <!-- To Currency -->
                        <div class="relative currency-select mb-5">
                            <button
                                class="currency-btn w-full flex items-center justify-between gap-2 px-4 py-4 bg-[#eef2f908] border border-white/10 rounded-xl text-white hover:border-white/30 transition-all duration-200">
                                <span class="flex items-center gap-2">
                                    <img class="currency-flag w-6 h-6 rounded-full object-cover"
                                        src="{{ asset('assets/images/ripple.png') }}" alt="XRP">
                                    <span class="currency-name text-sm font-medium">XRP</span>
                                </span>
                                <ion-icon name="chevron-down-outline"
                                    class="currency-chevron text-white/50 text-lg transition-transform duration-300"></ion-icon>
                            </button>

                            <!-- Dropdown -->
                            <ul
                                class="currency-dropdown hidden absolute left-0 top-full mt-2 w-full bg-[#1a0f2e] border border-white/10 rounded-lg z-50 overflow-hidden">
                                <li>
                                    <a href="#"
                                        class="currency-option flex items-center gap-3 px-4 py-3 hover:bg-white/10 transition-all"
                                        data-name="BTC" data-img="{{ asset('assets/images/btc.png') }}">
                                        <img src="{{ asset('assets/images/btc.png') }}"
                                            class="w-6 h-6 rounded-full object-cover">
                                        <div>
                                            <p class="text-white text-sm font-medium">Bitcoin</p>
                                            <p class="text-white/40 text-xs">BTC</p>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="#"
                                        class="currency-option flex items-center gap-3 px-4 py-3 hover:bg-white/10 transition-all"
                                        data-name="XRP" data-img="{{ asset('assets/images/ripple.png') }}">
                                        <img src="{{ asset('assets/images/ripple.png') }}"
                                            class="w-6 h-6 rounded-full object-cover">
                                        <div>
                                            <p class="text-white text-sm font-medium">Ripple</p>
                                            <p class="text-white/40 text-xs">XRP</p>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="#"
                                        class="currency-option flex items-center gap-3 px-4 py-3 hover:bg-white/10 transition-all"
                                        data-name="XLM" data-img="{{ asset('assets/images/stellar.png') }}">
                                        <img src="{{ asset('assets/images/stellar.png') }}"
                                            class="w-6 h-6 rounded-full object-cover">
                                        <div>
                                            <p class="text-white text-sm font-medium">Stellar</p>
                                            <p class="text-white/40 text-xs">XLM</p>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <button
                            class="w-full px-6 py-3 font-semibold text-white rounded-lg bg-gradient-to-r from-[#365FB5] to-[#9777DB] hover:from-[#9777DB] hover:to-[#365FB5] transition-all duration-300 my-5">
                            Exchange
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Hero Section -->

    <!-- Crypto Exchange -->
    <section class="max-w-6xl mx-auto px-4 py-20">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <!-- Left column: Image -->
            <div class="">
                <p class=" lg:text-lg xl:text-xl 2xl:text-3xl font-bold">Best Rate Crypto Exchange by ZIHU Bridge</p>
                <div class="flex justify-center md:justify-start">
                    <img src="{{ asset('assets/images/planet.png') }}" alt="Sample"
                        class="w-sm h-auto rounded-lg object-cover">
                </div>
            </div>

            <!-- Right column: Content -->
            <div class="w-full">
                <div class="p-6 border border-[#E1E1E1] rounded-xl bg-[#FDFDFD]">

                    <!-- Header -->
                    <div class="flex items-center text-sm md:text-base">
                        <div class="w-1/2 font-semibold text-left">Popular pair</div>
                        <div class="w-1/2 font-semibold text-left">Rate</div>
                        <!-- <div class="text-right">24h</div> -->
                    </div>

                    <!-- Row -->
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between mt-5 gap-3">

                        <!-- Pill -->
                        <div
                            class="flex items-center gap-2 rounded-full border border-[#E1E1E1] py-2 px-4 w-full md:w-[160px]">

                            <div class="flex -space-x-3">
                                <img src="{{ asset('assets/images/solana.png') }}" class="w-6 h-6" alt="">
                                <img src="{{ asset('assets/images/eth.png') }}" class="w-6 h-6" alt="">
                            </div>

                            <div class="font-bold whitespace-nowrap">
                                SOL <ion-icon name="swap-horizontal-outline"></ion-icon> ETH
                            </div>
                        </div>

                        <!-- Rate -->
                        <div class="text-sm md:text-base hidden md:block font-semibold">
                            1 SOL - 1.00022 ETH
                        </div>

                        <!-- Change -->
                        <div class="text-sm md:text-base md:text-right font-semibold text-[#529540]">
                            +0% / 24h
                        </div>
                    </div>

                    <!-- Repeat rows -->

                    <div class="flex flex-col md:flex-row md:items-center md:justify-between mt-5 gap-3">
                        <div
                            class="flex items-center gap-2 rounded-full border border-[#E1E1E1] py-2 px-4 w-full md:w-[160px]">
                            <div class="flex -space-x-3">
                                <img src="{{ asset('assets/images/bitcoin.png') }}" class="w-6 h-6" alt="">
                                <img src="{{ asset('assets/images/solana.png') }}" class="w-6 h-6" alt="">
                            </div>
                            <div class="font-bold whitespace-nowrap">
                                BTC <ion-icon name="swap-horizontal-outline"></ion-icon> SOL
                            </div>
                        </div>

                        <div class="hidden md:block font-semibold">1 SOL - 1.00022 ETH</div>
                        <div class="md:text-right font-semibold text-[#529540]">+6.05% / 24h</div>
                    </div>

                    <div class="flex flex-col md:flex-row md:items-center md:justify-between mt-5 gap-3">
                        <div
                            class="flex items-center gap-2 rounded-full border border-[#E1E1E1] py-2 px-4 w-full md:w-[160px]">
                            <div class="flex -space-x-3">
                                <img src="{{ asset('assets/images/eth.png') }}" class="w-6 h-6" alt="">
                                <img src="{{ asset('assets/images/bitcoin.png') }}" class="w-6 h-6" alt="">
                            </div>
                            <div class="font-bold whitespace-nowrap">
                                ETH <ion-icon name="swap-horizontal-outline"></ion-icon> BTC
                            </div>
                        </div>

                        <div class="hidden md:block font-semibold">1 SOL - 1.00022 ETH</div>
                        <div class="md:text-right font-semibold text-[#AB1C1C]">+0.76% / 24h</div>
                    </div>

                    <div class="flex flex-col md:flex-row md:items-center md:justify-between mt-5 gap-3">
                        <div
                            class="flex items-center gap-2 rounded-full border border-[#E1E1E1] py-2 px-4 w-full md:w-[160px]">
                            <div class="flex -space-x-3">
                                <img src="{{ asset('assets/images/bitcoin.png') }}" class="w-6 h-6" alt="">
                                <img src="{{ asset('assets/images/ripple.png') }}" class="w-6 h-6" alt="">
                            </div>
                            <div class="font-bold whitespace-nowrap">
                                BTC <ion-icon name="swap-horizontal-outline"></ion-icon> RIP
                            </div>
                        </div>

                        <div class="hidden md:block font-semibold">1 SOL - 1.00022 ETH</div>
                        <div class="md:text-right font-semibold text-[#AB1C1C]">-1.54% / 24h</div>
                    </div>

                    <div class="flex flex-col md:flex-row md:items-center md:justify-between mt-5 gap-3">
                        <div
                            class="flex items-center gap-2 rounded-full border border-[#E1E1E1] py-2 px-4 w-full md:w-[160px]">
                            <div class="flex -space-x-3">
                                <img src="{{ asset('assets/images/stellar.png') }}" class="w-6 h-6" alt="">
                                <img src="{{ asset('assets/images/ripple.png') }}" class="w-6 h-6" alt="">
                            </div>
                            <div class="font-bold whitespace-nowrap">
                                STE <ion-icon name="swap-horizontal-outline"></ion-icon> RIP
                            </div>
                        </div>

                        <div class="hidden md:block font-semibold">1 SOL - 1.00022 ETH</div>
                        <div class="md:text-right font-semibold text-[#AB1C1C]">-1.54% / 24h</div>
                    </div>

                </div>
            </div>
        </div>
    </section>
    <!-- Crypto Exchange -->

    <!-- Features -->
    <section class="py-20 bg-cover bg-center" style="background-image: url('{{ asset('assets/images/feature-bg.png') }}');">

        <div class="max-w-6xl mx-auto px-6">

            <!-- Heading -->
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
                    CryptoCap Amazing Features
                </h2>
                <p class="text-gray-300 leading-relaxed">
                    Explore sensational features to prepare your best investment in cryptocurrency
                </p>
            </div>

            <!-- Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-20">

                <!-- Card 1 -->
                <div class="p-6 rounded-xl bg-[#140A20] border border-[#2F1C4A] text-white">
                    <div class="bg-[#2F1C4A] px-4 py-2 rounded-lg w-max mb-4 text-xs font-medium mb-5">
                        Privacy
                    </div>
                    <div>
                        <img src="{{ asset('assets/images/icon1.png') }}" class="h-15 w-15 mb-5" alt="Privacy">
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Sign-up is not required</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        ZihuBridge provides cryptocurrency exchange without registration.
                    </p>
                </div>

                <!-- Card 2 -->
                <div class="p-6 rounded-xl bg-[#140A20] border border-[#2F1C4A] text-white">
                    <div class="bg-[#2F1C4A] px-4 py-2 rounded-lg w-max mb-4 text-xs font-medium mb-5">
                        Wide choice
                    </div>
                    <div>
                        <img src="{{ asset('assets/images/icon2.png') }}" class="h-15 w-15 mb-5" alt="Privacy">
                    </div>
                    <h3 class="text-lg font-semibold mb-2">1500 cryptocurrencies</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Hundreds of crypto and fiat currencies are available for exchange.
                    </p>
                </div>

                <!-- Card 3 -->
                <div class="p-6 rounded-xl bg-[#140A20] border border-[#2F1C4A] text-white">
                    <div class="bg-[#2F1C4A] px-4 py-2 rounded-lg w-max mb-4 text-xs font-medium mb-5">
                        24/7 support
                    </div>
                    <div>
                        <img src="{{ asset('assets/images/icon3.png') }}" class="h-15 w-15 mb-5" alt="Privacy">
                    </div>
                    <h3 class="text-lg font-semibold mb-2">You won’t be left alone</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Our support team is easy to reach and ready to answer your questions.
                    </p>
                </div>

                <!-- Card 4 -->
                <div class="p-6 rounded-xl bg-[#140A20] border border-[#2F1C4A] text-white">
                    <div class="bg-[#2F1C4A] px-4 py-2 rounded-lg w-max mb-4 text-xs font-medium mb-5">
                        Safety
                    </div>
                    <div>
                        <img src="{{ asset('assets/images/icon4.png') }}" class="h-15 w-15 mb-5" alt="Privacy">
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Non-custodial</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Crypto is sent directly to your wallet, we don’t store it on our service.
                    </p>
                </div>
            </div>

            <div class="bg-[#140A20] border border-[#2F1C4A] rounded-xl p-4">

                <div class="text-center max-w-2xl mx-auto my-5">
                    <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
                        Top Pairs on ZihuBridge
                    </h2>
                    <p class="text-gray-300 leading-relaxed">
                        Explore sensational features to prepare your best investment in cryptocurrency
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-15 w-full py-10 px-5">

                    <!-- Column 1 -->
                    <div>
                        <h3 class="text-xl font-semibold mb-4 text-white">
                            Stellar to Ripple
                        </h3>

                        <div id="stellarToRipplePairs" class="grid grid-cols-1 gap-5 mt-5 w-full"></div>
                    </div>

                    <!-- Column 2 -->
                    <div>
                        <h3 class="text-xl font-semibold mb-4 text-white">
                            Ripple to Stellar
                        </h3>

                        <div id="rippleToStellarPairs" class="grid grid-cols-1 gap-5 mt-5 w-full"></div>
                    </div>
                </div>
            </div>

        </div>
    </section>
    <!-- Features -->

    <!-- How to get started -->
    <section class="max-w-6xl mx-auto px-4 py-20">

        <div class="text-center max-w-2xl mx-auto mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-black mb-4">
                How To Get Started
            </h2>
            <p class="text-gray-700 leading-relaxed mb-5">
                Explore sensational features to prepare your best investment in cryptocurrency
            </p>

            <button
                class="px-6 py-3 font-semibold text-white rounded-lg bg-gradient-to-r from-[#365FB5] to-[#9777DB] hover:from-[#9777DB]hover:to-[#365FB5] transform hover:scale-105 transition-all duration-500 ease-in-out shadow-lg hover:shadow-2xl w-1/2">
                Get Started
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

            <div
                class="rounded-2xl px-5 py-10 border border-[#EAEAEA] bg-white flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-4">
                <div class="bg-[#EDF4FF] p-4 rounded-full shrink-0">
                    <img src="{{ asset('assets/images/icon7.png') }}" class="w-10 h-10">
                </div>
                <div>
                    <h2 class="text-md font-bold">Create Your Account</h2>
                    <p class="text-gray-500 mt-2">
                        Your account and personal identity are guaranteed safe.
                    </p>
                </div>
            </div>

            <div
                class="rounded-2xl px-5 py-10 border border-[#EAEAEA] bg-white flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-4">
                <div class="bg-[#EDF4FF] p-4 rounded-full shrink-0">
                    <img src="{{ asset('assets/images/icon6.png') }}" class="w-10 h-8">
                </div>
                <div>
                    <h2 class="text-md font-bold">Connect Bank Account</h2>
                    <p class="text-gray-500 mt-2">
                        Connect the bank account to start transactions.
                    </p>
                </div>
            </div>

            <div
                class="rounded-2xl px-5 py-10 border border-[#EAEAEA] bg-white flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-4">
                <div class="bg-[#EDF4FF] p-4 rounded-full shrink-0">
                    <img src="{{ asset('assets/images/icon5.png') }}" class="w-10 h-10">
                </div>
                <div>
                    <h2 class="text-md font-bold">Start Build Portfolio</h2>
                    <p class="text-gray-500 mt-2">
                        Buy and sell popular currencies and keep track of them.
                    </p>
                </div>
            </div>
        </div>

    </section>
    <!-- How to get started -->

@endsection
@push('scripts')
    <script>
        // Currency Dropdowns
        document.querySelectorAll('.currency-select').forEach(select => {
            const btn = select.querySelector('.currency-btn');
            const dropdown = select.querySelector('.currency-dropdown');
            const chevron = select.querySelector('.currency-chevron');

            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const isHidden = dropdown.classList.contains('hidden');

                // Close all first
                document.querySelectorAll('.currency-select').forEach(other => {
                    other.querySelector('.currency-dropdown').classList.add('hidden');
                    other.querySelector('.currency-chevron').style.transform = 'rotate(0deg)';
                });

                // Open if was closed
                if (isHidden) {
                    dropdown.classList.remove('hidden');
                    chevron.style.transform = 'rotate(180deg)';
                }
            });

            // Select option
            select.querySelectorAll('.currency-option').forEach(option => {
                option.addEventListener('click', (e) => {
                    e.preventDefault();
                    select.querySelector('.currency-flag').src = option.dataset.img;
                    select.querySelector('.currency-name').textContent = option.dataset.name;
                    dropdown.classList.add('hidden');
                    chevron.style.transform = 'rotate(0deg)';
                });
            });
        });

        // Close on outside click
        document.addEventListener('click', () => {
            document.querySelectorAll('.currency-select').forEach(select => {
                select.querySelector('.currency-dropdown').classList.add('hidden');
                select.querySelector('.currency-chevron').style.transform = 'rotate(0deg)';
            });
        });
    </script>
@endpush