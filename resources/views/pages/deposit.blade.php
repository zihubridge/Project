@extends('layout.master')
@section('content')
    <div id="copyToast"
        class="hidden fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-[#0066FF] text-white text-sm py-2 px-5 rounded-2xl flex items-center gap-2">
        <ion-icon name="checkmark-circle-outline" class="text-lg"></ion-icon>
        <span>Copied!</span>
    </div>

    <section class="bg-[#F6F7F9] py-10">
        <div class="max-w-6xl mx-auto px-4 mb-5">
            <div class="bg-white rounded-2xl p-5 flex flex-col md:flex-row md:justify-between gap-4 md:gap-0">
                <div class="flex flex-wrap justify-center md:justify-start gap-3 items-center">
                    <h2 class="text-lg font-bold text-black">Exchange ID:</h2>
                    <p id="exchangeId" class="text-[#2B2B2B] break-all">{{ $swap->swap_uuid }}</p>
                    <ion-icon name="copy-outline" class="cursor-pointer text-xl" onclick="copyText()"></ion-icon>
                </div>
                <div
                    class="flex items-center justify-center md:justify-start gap-2 cursor-pointer text-[#0044C9] font-semibold">
                    <ion-icon name="chatbox-ellipses-outline" class="text-xl"></ion-icon>
                    <span>Contact support</span>
                </div>
            </div>
        </div>

        <div class="max-w-6xl mx-auto px-4 mb-10">
            <div class="bg-white rounded-2xl p-5 py-10">

                <!-- Heading -->
                <div class="w-full text-center py-5">
                    <h2 class="text-xl font-bold text-black">Awaiting Your Deposit</h2>
                </div>

                <!-- Send Deposit -->
                <div class="flex flex-col md:flex-row gap-5 mb-10">
                    <!-- Label: 5/12 -->
                    <h2 class="text-lg font-bold text-black w-full md:w-4/12">Send Deposit:</h2>

                    <!-- Content: 7/12 -->
                    <div class="flex flex-wrap items-center gap-3 w-full md:w-8/12 px-8">
                        {{-- <img src="{{ asset('assets/new assets/eth.png') }}" alt="eth"> --}}
                        <p class="text-black font-semibold text-xl">{{ $swap->from_token_amount }}
                            {{ $swap->fromToken->asset_code }}
                        </p>

                        <div class="flex items-center gap-3">
                            <span>Networking:</span>
                            <p class="bg-[#0066FF] rounded-md text-white px-2">
                                {{ strtoupper($swap->fromBlockchain->asset_code) }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row gap-5 mb-10">
                    <h2 class="text-lg font-bold text-black w-full md:w-4/12">
                        You Will Receive:
                    </h2>

                    <div class="flex flex-wrap items-center gap-3 w-full md:w-8/12 px-8">
                        <p class="text-green-600 font-semibold text-xl">
                            {{ number_format($swap->to_estimated_token_amount, 6) }}
                            {{ $swap->toToken->asset_code }}
                        </p>

                        <div class="flex items-center gap-3">
                            <span>Network:</span>
                            <p class="bg-green-600 rounded-md text-white px-2">
                                {{ strtoupper($swap->toBlockchain->asset_code ?? '') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Deposit Address & Memo -->
                <div class="flex flex-col md:flex-row gap-4 md:gap-6">

                    <!-- Label -->
                    <div class="w-full md:w-4/12">
                        <h2 class="text-lg font-bold text-black mb-3 md:mb-0">
                            Deposit Details:
                        </h2>
                    </div>

                    <!-- Content -->
                    <div class="w-full md:w-8/12 px-8">

                        <!-- Unified Deposit Card -->
                        <div class="border border-[#E3E3E3] rounded-xl overflow-hidden">

                            <!-- Deposit Address -->
                            <div class="bg-[#F7F8FA] p-5">
                                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">

                                    <div>
                                        <p class="text-xs font-semibold text-gray-500 mb-1">
                                            Deposit Address
                                        </p>
                                        <p class="text-sm sm:text-base break-all tracking-wider text-gray-900 font-medium">
                                            {{ $depositAddress }}
                                        </p>
                                    </div>

                                    <div class="flex gap-2">
                                        <ion-icon name="open-outline"
                                            class="cursor-pointer text-xl bg-[#E1E8F3] text-[#859AB5] p-2 rounded-md open-explorer"
                                            data-url="{{ $url }}">
                                        </ion-icon>

                                        <ion-icon name="copy-outline"
                                            class="cursor-pointer text-xl bg-[#E1E8F3] text-[#859AB5] p-2 rounded-md copy-address"
                                            data-address="{{ $depositAddress }}">
                                        </ion-icon>
                                    </div>

                                </div>
                            </div>

                            <!-- Divider -->
                            <div class="h-px bg-[#E5E7EB]"></div>

                            <!-- Memo / Destination Tag -->
                            <div class="bg-white p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                                <div>
                                    <p class="text-sm font-bold text-red-600">
                                        {{ $swap->fromBlockchain->asset_code === 'XRP' ? 'Destination Tag (REQUIRED)' : 'Memo ID (REQUIRED)' }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        Funds sent without this will NOT be credited.
                                    </p>
                                </div>

                                <div
                                    class="flex items-center gap-3 bg-[#F9FAFB] border border-red-300 rounded-lg px-4 py-2">
                                    <span class="font-mono text-sm font-semibold text-gray-900 select-all">
                                        {{ $routingValue }}
                                    </span>
                                    <ion-icon name="copy-outline" class="cursor-pointer text-lg text-red-500 copy-memo"
                                        data-memo="{{ $routingValue }}">
                                    </ion-icon>
                                </div>

                            </div>

                            <!-- Network Warning -->
                            <div class="bg-[#FEF7EA] text-[#F7931A] border-t border-[#F7931A] p-4 text-sm">
                                Please deposit using the
                                <strong>Main {{ $swap->fromBlockchain->name }} ({{ $swap->fromBlockchain->asset_code }})
                                    network</strong>.
                            </div>

                        </div>

                    </div>
                </div>

            </div>
        </div>

        <div class="max-w-2xl mx-auto mt-10 px-4">
            <div class="flex flex-wrap justify-between gap-4">
                <!-- Step 1 -->
                <div class="flex flex-col items-center flex-1 min-w-[100px] sm:min-w-[120px]">
                    <div class="bg-[#203052] p-3 rounded-full flex justify-center items-center">
                        <img src="{{ asset('assets/new assets/icon4.png') }}" class="w-6 sm:w-7" alt="">
                    </div>
                    <span class="text-black font-medium text-center text-sm sm:text-base mt-1">Pending Deposit</span>
                </div>

                <!-- Step 2 -->
                <div class="flex flex-col items-center flex-1 min-w-[100px] sm:min-w-[120px]">
                    <div class="bg-[#D7E2F0] p-3 rounded-full flex justify-center items-center">
                        <img src="{{ asset('assets/new assets/icon3.png') }}" class="w-6 sm:w-7" alt="">
                    </div>
                    <span class="text-black font-medium text-center text-sm sm:text-base mt-1">Confirming</span>
                </div>

                <!-- Step 3 -->
                <div class="flex flex-col items-center flex-1 min-w-[100px] sm:min-w-[120px]">
                    <div class="bg-[#D7E2F0] p-3 rounded-full flex justify-center items-center">
                        <img src="{{ asset('assets/new assets/icon2.png') }}" class="w-6 sm:w-7" alt="">
                    </div>
                    <span class="text-black font-medium text-center text-sm sm:text-base mt-1">Exchanging</span>
                </div>

                <!-- Step 4 -->
                <div class="flex flex-col items-center flex-1 min-w-[100px] sm:min-w-[120px]">
                    <div class="bg-[#D7E2F0] p-3 rounded-full flex justify-center items-center">
                        <img src="{{ asset('assets/new assets/icon1.png') }}" class="w-6 sm:w-7" alt="">
                    </div>
                    <span class="text-black font-medium text-center text-sm sm:text-base mt-1">Sending</span>
                </div>
            </div>
        </div>

        <div id="customToast"
            class=" hidden bg-white border-l-4 border-[#859AB5] shadow-lg p-4 flex items-start gap-3 mx-auto max-w-2xl my-20">
            <!-- Icon -->
            <div class="flex-shrink-0 text-[#859AB5]">
                <ion-icon name="alert-circle-outline" class="text-xl"></ion-icon>
            </div>

            <!-- Text -->
            <div class="flex-1 text-sm text-[#859AB5]">
                If you sent the tokens and the status did not change immediately, do not worry. Our system needs a few
                minutes to detect the transaction.
            </div>

            <!-- Close button -->
            <button id="closeToast" class="text-gray-500 hover:text-gray-800">
                <ion-icon name="close-outline" class="text-xl"></ion-icon>
            </button>
        </div>

    </section>
@endsection

@push('scripts')
    <script>
        function copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text);
            }

            // Fallback (HTTP, older browsers)
            const textarea = document.createElement("textarea");
            textarea.value = text;
            textarea.style.position = "fixed";
            textarea.style.left = "-9999px";
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();

            try {
                document.execCommand("copy");
                return Promise.resolve();
            } catch (err) {
                return Promise.reject(err);
            } finally {
                document.body.removeChild(textarea);
            }
        }

        function showToast() {
            const toast = document.getElementById("copyToast");
            if (!toast) return;

            toast.classList.remove("hidden");
            setTimeout(() => {
                toast.classList.add("hidden");
            }, 1500);
        }

        function copyText() {
            const text = document.getElementById("exchangeId")?.textContent;
            if (!text) return;

            copyToClipboard(text).then(showToast);
        }

        document.addEventListener('click', function(e) {
            const target = e.target.closest('.copy-memo');
            if (!target) return;

            const memo = target.dataset.memo;
            if (!memo) return;

            copyToClipboard(memo).then(showToast);
        });

        document.addEventListener('click', function(e) {
            const target = e.target.closest('.copy-address');
            if (!target) return;

            const address = target.dataset.address;
            if (!address) return;

            copyToClipboard(address).then(showToast);
        });

        document.addEventListener('click', function(e) {
            const target = e.target.closest('.open-explorer');
            if (!target) return;

            const url = target.dataset.url;
            if (!url) return;

            window.open(url, '_blank', 'noopener,noreferrer');
        });
    </script>
@endpush
