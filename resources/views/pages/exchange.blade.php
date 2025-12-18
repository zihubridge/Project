@extends('layout.master')
@section('content')

    <section class="bg-[#F6F7F9]">
        <div class="max-w-7xl px-5 py-20 mx-auto">
            <div class="flex flex-col md:flex-row md:space-x-6">

                <!-- Left column: 4/12 -->
                <div class="w-full md:w-1/3 flex flex-col">
                    <h2 class="text-2xl font-bold mb-6">How To Swap:</h2>

                    <div class="relative flex flex-col">
                        <!-- Step 1 -->
                        <div class="flex items-center relative z-10">
                            <div class="w-8 h-8 flex items-center justify-center rounded-full bg-[#3FBB7D] text-white">
                                1
                            </div>
                            <div class="ml-4 text-[#3FBB7D] font-medium">Choose currencies</div>
                        </div>
                        <!-- Line to Step 2 -->
                        <div class="relative left-4 w-0.5 h-10 bg-gray-300"></div>

                        <!-- Step 2 -->
                        <div class="flex items-center relative z-10">
                            <div
                                class="w-8 h-8 flex items-center justify-center rounded-full bg-black text-white font-bold">
                                2
                            </div>
                            <div class="ml-4 text-black font-medium">Enter the address</div>
                        </div>
                        <!-- Line to Step 3 -->
                        <div class="relative left-4 w-0.5 h-10 bg-gray-300"></div>

                        <!-- Step 3 -->
                        <div class="flex items-center relative z-10">
                            <div
                                class="w-8 h-8 flex items-center justify-center rounded-full bg-black text-white font-bold">
                                3
                            </div>
                            <div class="ml-4 text-black font-medium">Enter the destination tag</div>
                        </div>
                        <!-- Line to Step 4 -->
                        <div class="relative left-4 w-0.5 h-10 bg-gray-300"></div>

                        <!-- Step 4 -->
                        <div class="flex items-center relative z-10">
                            <div
                                class="w-8 h-8 flex items-center justify-center rounded-full bg-[#C6D5EA] text-white font-bold">
                                4
                            </div>
                            <div class="ml-4 text-[#C6D5EA] font-medium">Create an exchange</div>
                        </div>
                    </div>
                </div>

                <!-- Right column: 8/12 -->
                <div class="w-full md:w-2/3 bg-white rounded-3xl p-6 space-y-4">
                    <h2 class="text-2xl font-bold text-center">Select token</h2>
                    <div class="mb-8 space-y-5 pb-10 border-b-1 border-[#D8D8D8]">

                        <!-- Send -->
                        <div class="flex flex-col sm:flex-row gap-4 w-full">
                            <div class="w-full bg-[#EEF2F9] rounded-xl px-4 sm:px-5 py-3 flex items-center justify-between">

                                <div class="flex flex-col">
                                    <span class="text-gray-500 text-xs sm:text-sm">
                                        You Send
                                    </span>
                                    <span class="text-xs text-gray-400 mt-1">
                                        ≈ $3,423.51
                                    </span>
                                </div>

                                <input type="text" placeholder="0.0"
                                    class="bg-transparent text-black text-lg sm:text-xl font-semibold text-right w-32 focus:outline-none" />
                            </div>

                            <select
                                class="bg-[#EEF2F9] rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option>BTC</option>
                                <option>ETH</option>
                                <option>USDT</option>
                            </select>
                        </div>

                        <!-- Swap Button -->
                        <div class="flex justify-end">
                            <button
                                class="w-12 h-12 flex items-center justify-center rounded-xl bg-[#EEF2F9] hover:bg-gray-200 transition">
                                <img src="{{ asset('assets/new assets/Icon.png') }}" class="w-5" alt="Swap">
                            </button>
                        </div>

                        <!-- Receive -->
                        <div class="flex flex-col sm:flex-row gap-4 w-full">
                            <div class="w-full bg-[#EEF2F9] rounded-xl px-4 sm:px-5 py-3 flex items-center justify-between">

                                <div class="flex flex-col">
                                    <span class="text-gray-500 text-xs sm:text-sm">
                                        You Get
                                    </span>
                                    <span class="text-xs text-gray-400 mt-1">
                                        ≈ $3,423.51
                                    </span>
                                </div>

                                <input type="text" placeholder="0.0"
                                    class="bg-transparent text-black text-lg sm:text-xl font-semibold text-right w-32 focus:outline-none" />
                            </div>

                            <select
                                class="bg-[#EEF2F9] rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option>BTC</option>
                                <option>ETH</option>
                                <option>USDT</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0">
                        <h2 class="text-base sm:text-lg font-bold text-center sm:text-left">
                            Enter The Destination Wallet
                        </h2>

                        <a href="#"
                            class="text-sm sm:text-base text-[#0044C9] hover:text-[#012c82] text-center sm:text-right">
                            Don’t have a wallet?
                        </a>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-4 w-full mb-10">
                        <div class="w-full bg-[#EEF2F9] rounded-xl px-4 sm:px-5 py-3 flex items-center justify-between">
                            <input type="text" placeholder="The Recipients Address"
                                class="bg-transparent text-black text-lg sm:text-xl w-full focus:outline-none" />
                            <div class="flex flex-col">
                                <img src="{{ asset('assets/new assets/qr-icon.png') }}" alt="qr-icon">
                            </div>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-black font-semibold cursor-pointer mb-10">
                        <input type="checkbox" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                        <span>
                            I agree not to provide the destination tag
                        </span>
                    </label>
                    <a href="{{ route('deposit') }}"
                        class="block w-full text-center bg-blue-600 text-white py-2 rounded-full text-lg font-semibold hover:bg-transparent hover:text-blue-600 border border-blue-600 transition mb-5">
                        Create An Exchange
                    </a>
                    <div class="flex justify-center font-semibold text-black mb-10">
                        By clicking Create an exchange, you agree to the Privacy and Terms of services.
                    </div>

                    <div class="relative top-10 flex justify-center mb-6">
                        <span class="px-10 py-1 rounded-2xl bg-white text-black border border-[#D8D8D8] text-center">
                            Additional Information
                        </span>
                    </div>


                    <!-- Refund address -->
                    <div class="flex flex-wrap lg:flex-nowrap items-start gap-6 mb-10 border-t border-[#D8D8D8] pt-20 ">
                        <div class="w-full lg:basis-1/2">
                            <h2 class="text-base sm:text-lg font-bold">
                                Enter The Refund Details
                            </h2>
                            <p class="text-gray-400">
                                We recommend adding your wallet address for a refund.
                            </p>
                        </div>

                        <div class="w-full lg:basis-1/2 bg-[#EEF2F9] rounded-xl px-4 sm:px-5 py-3 flex items-center gap-3">
                            <input type="text" placeholder="the ETH Refund Address"
                                class="w-full bg-transparent text-black text-sm sm:text-lg focus:outline-none" />
                            <img src="{{ asset('assets/new assets/qr-icon.png') }}" alt="qr-icon">
                        </div>  
                    </div>

                    <!-- Email -->
                    <div class="flex flex-wrap lg:flex-nowrap items-start gap-6 mb-10">
                        <div class="w-full lg:basis-1/2">
                            <h2 class="text-base sm:text-lg font-bold">
                                Add Email
                            </h2>
                            <p class="text-gray-400">
                                If you want to get notifications about this exchange.
                            </p>
                        </div>

                        <div class="w-full lg:basis-1/2 bg-[#EEF2F9] rounded-xl px-4 sm:px-5 py-3 flex items-center">
                            <input type="text" placeholder="The E-mail Address"
                                class="w-full bg-transparent text-black text-sm sm:text-lg focus:outline-none" />
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

@endsection