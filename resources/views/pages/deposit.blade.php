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
                    <h2 id="statusHeading" class="text-xl font-bold text-black">{{ $stepName }}</h2>
                </div>

                <!-- Send Deposit -->
                <div class="flex flex-col md:flex-row gap-5 mb-10">
                    <h2 class="text-lg font-bold text-black w-full md:w-4/12">Send Deposit:</h2>

                    <div class="flex flex-wrap items-center gap-3 w-full md:w-8/12 px-8">
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
                            {{ number_format($swap->to_estimated_token_amount, 3) }}
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
                <div class="flex flex-col md:flex-row gap-5 mb-10">
                    <h2 class="text-lg font-bold text-black w-full md:w-4/12">
                        Estimated Receive Time:
                    </h2>

                    <div class="w-full md:w-8/12 px-8">
                        <p id="estimatedTime" class="text-[#0066FF] font-semibold text-lg">
                            Calculating...
                        </p>
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

        <!-- Progress Steps -->
        <div class="max-w-5xl mx-auto mt-10 px-4">
            <div class="flex justify-center items-start gap-4 sm:gap-6 md:gap-8">
                @php
                    $steps = [
                        ['name' => 'Awaiting Deposit', 'icon' => 'icon4.png'],
                        ['name' => 'Deposit Confirmed', 'icon' => 'icon3.png'],
                        ['name' => 'Swapping to Coin', 'icon' => 'icon3.png'],
                        ['name' => 'Exchanging Coins', 'icon' => 'icon2.png'],
                        ['name' => 'Swapping to Token', 'icon' => 'icon3.png'],
                        ['name' => 'Sending Tokens', 'icon' => 'icon1.png'],
                        ['name' => 'Completed', 'icon' => 'icon3.png'],
                    ];
                @endphp

                @foreach ($steps as $index => $step)
                    <div class="flex flex-col items-center w-16 sm:w-20" data-step="{{ $index + 1 }}">
                        <div
                            class="step-circle p-2.5 sm:p-3 rounded-full transition-colors duration-300
                            {{ $index + 1 <= $currentStep ? 'bg-[#203052]' : 'bg-[#D7E2F0]' }}
                            {{ $index + 1 == $currentStep ? 'step-loading' : '' }}">
                            <img src="{{ asset('assets/new assets/' . $step['icon']) }}" class="w-5 sm:w-6" alt="">
                        </div>
                        <span class="text-black font-medium text-center text-xs mt-2 leading-tight">
                            {{ $step['name'] }}
                        </span>
                    </div>
                @endforeach
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
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
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

        // ============================================
        // SWAP STATUS POLLING
        // ============================================
        const swapUuid = "{{ $swap->swap_uuid }}";
        let pollInterval;
        let previousStep = {{ $currentStep }};

        function updateSwapStatus() {
            axios.get(`/swap/${swapUuid}/status`)
                .then(response => {
                    const data = response.data;

                    // Only update UI if step changed
                    if (data.current_step !== previousStep) {
                        previousStep = data.current_step;

                        // Update heading
                        const heading = document.getElementById('statusHeading');
                        if (heading) {
                            heading.textContent = data.step_name;
                        }

                        // Update step circles
                        document.querySelectorAll('[data-step]').forEach(stepEl => {
                            const stepNum = parseInt(stepEl.dataset.step);
                            const circle = stepEl.querySelector('.step-circle');

                            circle.classList.remove('step-loading');
                            if (stepNum < data.current_step) {
                                circle.classList.remove('bg-[#D7E2F0]');
                                circle.classList.add('bg-[#203052]');
                            } else if (stepNum === data.current_step) {
                                circle.classList.remove('bg-[#D7E2F0]');
                                circle.classList.add('bg-[#203052]');
                                circle.classList.add('step-loading');
                            } else {
                                circle.classList.remove('bg-[#203052]');
                                circle.classList.add('bg-[#D7E2F0]');
                            }
                        });
                    }

                    // Handle completion (swap_state_id === 9)
                    if (data.is_completed) {
                        clearInterval(pollInterval);

                        // Optional: Redirect after 2 seconds
                        // setTimeout(() => {
                        //     window.location.href = '/swap/success';
                        // }, 2000);
                    }

                    // Handle failure
                    if (data.is_failed) {
                        clearInterval(pollInterval);
                        console.error('Swap failed:', data.failure_reason);

                        // Show error
                        alert('Swap failed: ' + (data.failure_reason || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error fetching swap status:', error);
                });
        }

        // Start polling every 10 seconds
        pollInterval = setInterval(updateSwapStatus, 10000);

        // Initial call after 5 seconds
        setTimeout(updateSwapStatus, 5000);

        // Clear interval when page unloads
        window.addEventListener('beforeunload', () => {
            clearInterval(pollInterval);
        });

        function loadEstimatedTime() {
            axios.get('/global/estimated_swap_time')
                .then(res => {
                    const el = document.getElementById('estimatedTime');
                    if (el) {
                        el.textContent = res.data.estimated_time;
                    }
                })
                .catch(err => {
                    console.error('ETA error', err);
                });
        }

        loadEstimatedTime();
    </script>
@endpush
