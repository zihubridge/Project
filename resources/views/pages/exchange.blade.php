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
                            <div class="ml-4 text-black font-medium">Enter the destination address</div>
                        </div>
                        <!-- Line to Step 3 -->
                        <div class="relative left-4 w-0.5 h-10 bg-gray-300"></div>

                        <!-- Step 3 -->
                        <div class="flex items-center relative z-10">
                            <div
                                class="w-8 h-8 flex items-center justify-center rounded-full bg-[#C6D5EA] text-white font-bold">
                                3
                            </div>
                            <div class="ml-4 text-[#C6D5EA] font-medium">Swap tokens</div>
                        </div>
                    </div>
                </div>

                <!-- Right column: 8/12 -->
                <div class="w-full md:w-2/3 bg-white rounded-3xl p-6 space-y-4">
                    <h2 class="text-2xl font-bold text-center">Swap tokens</h2>
                    <div class="mb-8 space-y-5 pb-10 border-b-1 border-[#D8D8D8]">

                        <!-- Send -->
                        <div class="flex flex-col sm:flex-row gap-4 w-full group">
                            <div
                                class="w-full bg-[#EEF2F9] border border-transparent focus-within:border-blue-500 focus-within:bg-white rounded-2xl px-5 py-4 flex items-center justify-between transition-all">

                                <div class="flex flex-col gap-1">
                                    <span class="text-gray-500 text-xs font-bold uppercase tracking-wider">You Send</span>
                                    <input id="sendAmount" type="text" placeholder="0.0"
                                        class="bg-transparent text-black text-2xl font-bold focus:outline-none w-full" />
                                </div>

                                <div class="flex flex-col items-end gap-2 min-w-[140px]">
                                    <div
                                        class="flex items-center gap-1.5 bg-white/80 px-2 py-1 rounded-full border border-gray-200 shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                        <span class="text-[10px] font-black text-gray-600 uppercase">{{ $fromAsset }}</span>
                                    </div>

                                    <select id="fromToken"
                                        class="bg-white hover:bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm font-bold cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all shadow-sm">
                                        <option selected disabled>Select token</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Receive -->
                        <div class="flex flex-col sm:flex-row gap-4 w-full group">
                            <div
                                class="w-full bg-[#EEF2F9] border border-transparent focus-within:border-blue-500 focus-within:bg-white rounded-2xl px-5 py-4 flex items-center justify-between transition-all">

                                <div class="flex flex-col gap-1">
                                    <span class="text-gray-500 text-xs font-bold uppercase tracking-wider">You Get</span>
                                    <input id="receiveAmount" type="text" placeholder="0.0" readonly
                                        class="bg-transparent text-black text-2xl font-bold focus:outline-none w-full cursor-default" />
                                </div>

                                <div class="flex flex-col items-end gap-2 min-w-[140px]">
                                    <div
                                        class="flex items-center gap-1.5 bg-white/80 px-2 py-1 rounded-full border border-gray-200 shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        <span
                                            class="text-[10px] font-black text-gray-600 uppercase">{{ $toAsset }}</span>
                                    </div>

                                    <div class="relative w-full">
                                        <select id="toToken"
                                            class="w-full bg-white hover:bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm font-bold cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all shadow-sm appearance-none pr-8">
                                            <option selected disabled>Select token</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0">
                        <h2 class="text-base sm:text-lg font-bold text-center sm:text-left">
                            Enter The Destination Wallet
                        </h2>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-4 w-full mb-2">
                        <div class="w-full bg-[#EEF2F9] rounded-xl px-4 sm:px-5 py-3 flex items-center justify-between">
                            <input id="destinationAddress" type="text" placeholder="The Recipients Address"
                                class="bg-transparent text-black text-lg sm:text-xl w-full focus:outline-none" />
                        </div>
                    </div>

                    <p id="destError" class="text-sm text-red-600 hidden"></p>
                    <button id="swapBtn" type="button"
                        class="block w-full text-center bg-blue-600 text-white py-2 rounded-full text-lg font-semibold hover:bg-transparent hover:text-blue-600 border border-blue-600 transition mb-5 disabled:opacity-50 disabled:cursor-not-allowed">
                        Swap Tokens
                    </button>
                    <div class="flex justify-center font-semibold text-black mb-10">
                        By clicking Create an exchange, you agree to the Privacy and Terms of services.
                    </div>
                </div>
            </div>
        </div>

        <form id="realSwapForm" action="{{ route('swap.start') }}" method="POST" style="display: none;">
            @csrf
            <input type="hidden" name="amount" id="post_amount">
            <input type="hidden" name="from_blockchain" id="post_from_blockchain">
            <input type="hidden" name="to_blockchain" id="post_to_blockchain">

            <input type="hidden" name="from_asset_code" id="post_from_asset_code">
            <input type="hidden" name="from_issuer_address" id="post_from_issuer_address">

            <input type="hidden" name="to_asset_code" id="post_to_asset_code">
            <input type="hidden" name="to_issuer_address" id="post_to_issuer_address">

            <input type="hidden" name="destination_address" id="post_destination_address">
        </form>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const fromAsset = @json($fromAsset);
            const toAsset = @json($toAsset);

            const fromTokenSelect = document.getElementById("fromToken");
            const toTokenSelect = document.getElementById("toToken");
            const sendInput = document.getElementById("sendAmount");
            const receiveInput = document.getElementById("receiveAmount");

            const destInput = document.getElementById("destinationAddress");
            const destError = document.getElementById("destError");
            const swapBtn = document.getElementById("swapBtn");

            if (!fromTokenSelect || !toTokenSelect || !sendInput || !receiveInput || !destInput || !destError || !
                swapBtn) {
                console.error("Missing required DOM elements");
                return;
            }

            let destOk = false;
            let estimating = false;
            let estimateReqId = 0;

            function setDestState(ok, message = "") {
                destOk = ok;
                if (!ok && message) {
                    destError.textContent = message;
                    destError.classList.remove("hidden");
                } else {
                    destError.textContent = "";
                    destError.classList.add("hidden");
                }
                swapBtn.disabled = !(destOk && !estimating);
            }

            function setEstimateLoading(on) { 
                estimating = on;

                if (on) {
                    receiveInput.value = "Loading estimated amount...";
                }

                swapBtn.disabled = !(destOk && !estimating);
            }

            setDestState(false, "");
            setEstimateLoading(false);

            loadTokens(fromAsset, "fromToken");
            loadTokens(toAsset, "toToken");

            const debouncedEstimate = debounce(estimateSwap, 400);
            const debouncedDestCheck = debounce(checkDestination, 500);

            sendInput.addEventListener("input", () => {
                debouncedEstimate();
                debouncedDestCheck();
            });

            fromTokenSelect.addEventListener("change", () => {
                debouncedEstimate();
                debouncedDestCheck();
            });

            toTokenSelect.addEventListener("change", () => {
                debouncedEstimate();
                debouncedDestCheck();
            });

            destInput.addEventp;
            destInput.addEventListener("input", debouncedDestCheck);

            swapBtn.addEventListener("click", () => {
                if (swapBtn.disabled) return;

                const fromOption = fromTokenSelect.options[fromTokenSelect.selectedIndex];
                const toOption = toTokenSelect.options[toTokenSelect.selectedIndex];

                // Get the values safely
                document.getElementById('post_amount').value = sendInput.value;

                // Ensure these match the setAttribute names in loadTokens
                document.getElementById('post_from_blockchain').value = fromOption.getAttribute(
                    'data-blockchain-id') || "";
                document.getElementById('post_to_blockchain').value = toOption.getAttribute(
                    'data-blockchain-id') || "";

                document.getElementById('post_from_asset_code').value = fromOption.getAttribute(
                    'data-asset-code') || "";
                document.getElementById('post_from_issuer_address').value = fromOption.getAttribute(
                    'data-issuer') || "";

                document.getElementById('post_to_asset_code').value = toOption.getAttribute(
                    'data-asset-code') || "";
                document.getElementById('post_to_issuer_address').value = toOption.getAttribute(
                    'data-issuer') || "";

                document.getElementById('post_destination_address').value = destInput.value;

                document.getElementById('realSwapForm').submit();
            });

            async function estimateSwap() {
                const amount = sendInput.value.trim();
                const fromOpt = fromTokenSelect.options[fromTokenSelect.selectedIndex];
                const toOpt = toTokenSelect.options[toTokenSelect.selectedIndex];

                // If not ready, clear the output (don’t show loading forever)
                if (!amount || isNaN(Number(amount)) || Number(amount) <= 0 || !fromOpt || !toOpt) {
                    receiveInput.value = "";
                    setEstimateLoading(false);
                    return;
                }

                const payload = {
                    amount: amount,
                    from_blockchain: fromAsset,
                    to_blockchain: toAsset,
                    from_asset_code: fromOpt.dataset.asset,
                    from_issuer_address: fromOpt.dataset.issuer,
                    to_asset_code: toOpt.dataset.asset,
                    to_issuer_address: toOpt.dataset.issuer,
                };

                if (!payload.from_asset_code || !payload.from_issuer_address || !payload.to_asset_code || !
                    payload.to_issuer_address) {
                    receiveInput.value = "";
                    setEstimateLoading(false);
                    return;
                }

                // NEW: show loading instantly
                const myReqId = ++estimateReqId;
                setEstimateLoading(true);

                try {
                    const res = await fetch("/global/token_swapping_amount", {
                        method: "POST",
                        headers: {
                            "Accept": "application/json",
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(payload),
                    });

                    const json = await res.json();

                    if (myReqId !== estimateReqId) return;

                    if (json.status === 1 && json.estimated_amount !== undefined) {
                        receiveInput.value = json.estimated_amount;
                    } else {
                        receiveInput.value = "";
                        console.log("Estimate response:", json);
                    }
                } catch (e) {
                    if (myReqId !== estimateReqId) return;
                    console.error("Estimate failed:", e);
                    receiveInput.value = "";
                } finally {
                    if (myReqId === estimateReqId) setEstimateLoading(false);
                }
            }

            async function checkDestination() {
                const destination = destInput.value.trim();

                // empty => disable button, hide message
                if (!destination) {
                    setDestState(false, "");
                    return;
                }

                const toOpt = toTokenSelect.options[toTokenSelect.selectedIndex];
                if (!toOpt) {
                    setDestState(false, "Select destination token first.");
                    return;
                }

                // amount required by backend validation
                const amount = sendInput.value.trim();
                const safeAmount = (!amount || isNaN(Number(amount)) || Number(amount) <= 0) ? "0.0000001" :
                    amount;

                const payload = {
                    amount: safeAmount,
                    to_blockchain: toAsset,
                    to_asset_code: toOpt.dataset.asset,
                    to_issuer_address: toOpt.dataset.issuer,
                    destination_address: destination,
                };

                try {
                    const res = await fetch("/global/destination_wallet", {
                        method: "POST",
                        headers: {
                            "Accept": "application/json",
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(payload),
                    });

                    const json = await res.json();

                    if (json.status === 1 && json.valid === true && json.needs_trustline === false) {
                        setDestState(true, "");
                    } else {
                        setDestState(false, json.message || "Destination wallet invalid.");
                    }
                } catch (e) {
                    console.error("Destination check failed:", e);
                    setDestState(false, "Could not validate destination wallet.");
                }
            }

        // Load tokens into select and attach asset/issuer to options
        function loadTokens(assetCode, selectId) {

            return fetch("/global/tokens", {
                    method: "POST",
                    headers: {
                        "Accept": "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ asset_code: assetCode }),
                })
                .then(res => res.json())
                .then(json => {
                    const select = document.getElementById(selectId);
                    if (!select || json.status !== "success") return;

                    select.innerHTML = `<option value="" selected disabled>Choose token</option>`;

                    (json.tokens || []).forEach(token => {
                        const opt = document.createElement("option");
                        opt.value = token.id;
                        opt.text = token.asset_code;

                        opt.dataset.asset = token.asset_code;
                        opt.dataset.issuer = token.issuer_address;

                        opt.setAttribute('data-blockchain-id', token.blockchain_id);
                        opt.setAttribute('data-asset-code', token.asset_code);
                        opt.setAttribute('data-issuer', token.issuer_address);

                        select.appendChild(opt);
                    });
                });
        }

        function debounce(fn, delay = 400) {
            let t;
            return (...args) => {
                clearTimeout(t);
                t = setTimeout(() => fn(...args), delay);
            };
        }
    });
    </script>
@endpush
