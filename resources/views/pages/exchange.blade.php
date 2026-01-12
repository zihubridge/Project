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

                                <input id="sendAmount" type="text" placeholder="0.0"
                                    class="bg-transparent text-black text-lg sm:text-xl font-semibold text-right w-32 focus:outline-none" />
                            </div>

                            <select id="fromToken"
                                class="bg-[#EEF2F9] rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option selected disabled>Select token</option>
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

                                <input id="receiveAmount" type="text" placeholder="0.0" readonly
                                    class="bg-transparent text-black text-lg sm:text-xl font-semibold text-right w-32 focus:outline-none" />
                            </div>

                            <select id="toToken"
                                class="bg-[#EEF2F9] rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option selected disabled>Select token</option>
                            </select>
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

            // Hard fail fast (so you notice)
            if (!fromTokenSelect || !toTokenSelect || !sendInput || !receiveInput || !destInput || !destError || !
                swapBtn) {
                console.error("Missing required DOM elements", {
                    fromTokenSelect,
                    toTokenSelect,
                    sendInput,
                    receiveInput,
                    destInput,
                    destError,
                    swapBtn
                });
                return;
            }

            // ---------- state helpers ----------
            let destOk = false;

            function setDestState(ok, message = "") {
                destOk = ok;

                if (!ok && message) {
                    destError.textContent = message;
                    destError.classList.remove("hidden");
                } else {
                    destError.textContent = "";
                    destError.classList.add("hidden");
                }

                swapBtn.disabled = !destOk;
            }

            // initial
            setDestState(false, "");

            // ---------- load tokens ----------
            loadTokens(fromAsset, "fromToken");
            loadTokens(toAsset, "toToken");

            // Estimate when user types OR changes tokens
            const debouncedEstimate = debounce(estimateSwap, 400);
            sendInput.addEventListener("input", debouncedEstimate);
            fromTokenSelect.addEventListener("change", debouncedEstimate);
            toTokenSelect.addEventListener("change", () => {
                debouncedEstimate();
                debouncedDestCheck();
            });

            const debouncedDestCheck = debounce(checkDestination, 500);
            destInput.addEventListener("input", debouncedDestCheck);

            // Also re-check if user changes amount (optional)
            sendInput.addEventListener("input", debouncedDestCheck);

            swapBtn.addEventListener("click", () => {
                if (!destOk) return;
                window.location.href = "{{ route('deposit') }}";
            });

            async function estimateSwap() {
                const amount = sendInput.value.trim();

                // guards
                if (!amount || isNaN(Number(amount)) || Number(amount) <= 0) {
                    receiveInput.value = "";
                    return;
                }

                const fromOpt = fromTokenSelect.options[fromTokenSelect.selectedIndex];
                const toOpt = toTokenSelect.options[toTokenSelect.selectedIndex];

                if (!fromOpt || !toOpt) return;

                const payload = {
                    amount: amount,
                    from_blockchain: fromAsset,
                    to_blockchain: toAsset,
                    from_asset_code: fromOpt.dataset.asset,
                    from_issuer_address: fromOpt.dataset.issuer,
                    to_asset_code: toOpt.dataset.asset,
                    to_issuer_address: toOpt.dataset.issuer,
                };

                // must have token meta
                if (!payload.from_asset_code || !payload.from_issuer_address || !payload.to_asset_code || !
                    payload.to_issuer_address) {
                    receiveInput.value = "";
                    return;
                }

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
                    
                    if (json.status === 1 && json.estimated_amount !== undefined) {
                        receiveInput.value = json.estimated_amount;
                    } else {
                        receiveInput.value = "";
                        console.log("Estimate response:", json);
                    }
                } catch (e) {
                    console.error("Estimate failed:", e);
                    receiveInput.value = "";
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

            fetch("/global/tokens", {
                    method: "POST",
                    headers: {
                        "Accept": "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        asset_code: assetCode
                    }),
                })
                .then(res => res.json())
                .then(json => {
                    if (json.status !== "success") return;

                    const select = document.getElementById(selectId);
                    if (!select) return;

                    select.innerHTML = `<option value="" selected disabled>Select token</option>`;

                    (json.tokens || []).forEach(token => {
                        const opt = document.createElement("option");
                        opt.value = token.id;
                        opt.textContent = token.symbol ?? token.name;

                        // IMPORTANT: used by estimate payload
                        opt.dataset.asset = token.asset_code;
                        opt.dataset.issuer = token.issuer_address;

                        select.appendChild(opt);
                    });
                })
                .catch(err => console.error("Token fetch failed ❌", err));
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
