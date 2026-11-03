@extends('layout.master')

@section('content')
<main class="min-h-screen bg-[#060914] text-white py-10 px-4 md:px-8"
    id="swapStatusPage"
    data-swap-uuid="{{ $order->swap_uuid }}">
    <div class="max-w-4xl mx-auto space-y-6">

        <!-- Top Header Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-[#0D1326] border border-slate-800 rounded-2xl p-5">
            <div>
                <div class="flex items-center gap-2 text-xs text-slate-400">
                    <span>Swap Order UUID:</span>
                    <code id="swapUuidText" class="text-cyan-400 font-mono">{{ $order->swap_uuid }}</code>
                    <button type="button" data-copy-target="#swapUuidText" class="text-slate-400 hover:text-white cursor-pointer" title="Copy Swap UUID">
                        <ion-icon name="copy-outline"></ion-icon>
                    </button>
                </div>
                <h1 id="swapStatusHeading" class="text-xl md:text-2xl font-bold mt-1">
                    {{ $order->statusEnum()->label() }}
                </h1>
            </div>

            <div class="flex items-center gap-3">
                <span id="swapStatusPill" class="px-3.5 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
                    {{ $order->status }}
                </span>
                <a href="{{ route('contact') }}" class="text-xs px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition">
                    Need Help?
                </a>
            </div>
        </div>

        <!-- Amounts Summary Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-5 rounded-2xl bg-[#0D1326] border border-slate-800">
                <div class="text-xs text-slate-400 uppercase tracking-wider">You Send</div>
                <div class="text-2xl font-extrabold text-white mt-1">
                    <span id="orderAmountIn">{{ rtrim(rtrim((string) $order->amount_in, '0'), '.') }}</span>
                    <span class="text-cyan-400">{{ $order->sourceAsset->symbol }}</span>
                </div>
                <div class="text-xs text-slate-400 mt-1">
                    Network: <span class="text-slate-200 font-medium">{{ $order->sourceNetwork->display_name }}</span>
                </div>
                <div class="text-xs text-slate-500 mt-2 break-all font-mono">
                    Refund Address: {{ $order->refund_address }}
                </div>
            </div>

            <div class="p-5 rounded-2xl bg-[#0D1326] border border-slate-800">
                <div class="text-xs text-slate-400 uppercase tracking-wider">You Receive</div>
                <div class="text-2xl font-extrabold text-emerald-400 mt-1">
                    <span id="orderAmountOut">
                        {{ rtrim(rtrim((string) ($order->actual_amount_out ?? $order->expected_amount_out), '0'), '.') }}
                    </span>
                    <span>{{ $order->destinationAsset->symbol }}</span>
                </div>
                <div class="text-xs text-slate-400 mt-1">
                    Network: <span class="text-slate-200 font-medium">{{ $order->destinationNetwork->display_name }}</span>
                    <span class="ml-2 text-slate-500">(Min: {{ rtrim(rtrim((string) $order->min_amount_out, '0'), '.') }})</span>
                </div>
                <div class="text-xs text-slate-500 mt-2 break-all font-mono">
                    Recipient: {{ $order->recipient_address }}
                    @if($order->recipient_memo)
                        (Memo/Tag: {{ $order->recipient_memo }})
                    @endif
                </div>
            </div>
        </div>

        <!-- Deposit Instructions & Non-Custodial Wallet Action Card -->
        <div id="depositInstructionsCard" class="{{ $order->status === 'DEPOSIT_PENDING' ? '' : 'hidden' }} bg-[#0D1326] border border-cyan-500/40 rounded-3xl p-6 space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-white">Send Deposit to Lock & Execute Swap</h2>
                    <p class="text-xs text-slate-400">
                        Transfer the exact amount of <strong class="text-white">{{ $order->sourceAsset->symbol }}</strong> on <strong class="text-cyan-400">{{ $order->sourceNetwork->display_name }}</strong> before the timer expires.
                    </p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400">Quote Expires In:</span>
                    <div id="depositCountdownTimer" class="text-base font-mono font-bold text-amber-400">--:--</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                <!-- QR Code Box -->
                <div class="md:col-span-4 flex flex-col items-center justify-center bg-white p-4 rounded-2xl">
                    <img id="depositQrImage"
                        src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($order->deposit_address) }}"
                        alt="Deposit Address QR"
                        class="w-40 h-40 object-contain" />
                    <span class="text-[11px] text-slate-700 font-semibold mt-2">Scan in Wallet</span>
                </div>

                <!-- Address, Memo & Wallet Sign Controls -->
                <div class="md:col-span-8 space-y-4">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">
                            Intent Deposit Address ({{ $order->sourceNetwork->display_name }})
                        </label>
                        <div class="flex items-center gap-2 bg-[#070B19] border border-slate-700 rounded-xl p-3">
                            <code id="depositAddressValue" class="text-xs md:text-sm text-cyan-300 font-mono break-all flex-1">{{ $order->deposit_address }}</code>
                            <button type="button" data-copy-target="#depositAddressValue"
                                class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-white cursor-pointer">
                                Copy
                            </button>
                        </div>
                    </div>

                    @if($order->deposit_memo)
                    <div class="p-3.5 rounded-xl bg-amber-950/60 border border-amber-500/50">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-amber-300">
                                Mandatory Deposit Memo / Destination Tag
                            </span>
                            <button type="button" data-copy-target="#depositMemoValue"
                                class="px-2.5 py-1 rounded bg-amber-500/20 hover:bg-amber-500/30 text-xs text-amber-200 font-semibold cursor-pointer">
                                Copy Memo
                            </button>
                        </div>
                        <code id="depositMemoValue" class="block text-base font-mono font-bold text-white mt-1">{{ $order->deposit_memo }}</code>
                        <p class="text-[11px] text-amber-200/80 mt-1">
                            You MUST include this Memo / Destination Tag when sending your deposit on {{ $order->sourceNetwork->display_name }}.
                        </p>
                    </div>
                    @endif

                    <!-- One-Click Connected Wallet Sign & Send -->
                    <div class="pt-2 flex flex-col sm:flex-row gap-3">
                        <button id="payWithConnectedWalletBtn" type="button"
                            class="flex-1 py-3 px-4 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-xs md:text-sm font-bold text-white shadow-lg shadow-cyan-500/20 transition cursor-pointer flex items-center justify-center gap-2">
                            <ion-icon name="wallet-outline" class="text-base"></ion-icon>
                            <span>Send Deposit with Connected Wallet</span>
                        </button>
                    </div>
                    <p id="walletPaymentFeedback" class="hidden text-xs rounded-lg p-2.5"></p>
                </div>
            </div>

            <!-- Manual Deposit Transaction Hash Submission -->
            <div class="pt-4 border-t border-slate-800">
                <label for="manualTxHashInput" class="block text-xs font-medium text-slate-300 mb-1.5">
                    Sent manually from an external wallet? Paste your Origin Transaction Hash to accelerate indexing:
                </label>
                <form id="submitDepositTxForm" class="flex flex-col sm:flex-row gap-2">
                    <input id="manualTxHashInput" type="text"
                        value="{{ $order->source_tx_hash }}"
                        placeholder="Paste origin transaction hash (0x... or chain txid)"
                        class="flex-1 bg-[#070B19] border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs md:text-sm text-white font-mono placeholder-slate-500 focus:outline-none focus:border-cyan-500" />
                    <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs md:text-sm font-semibold text-cyan-300 transition cursor-pointer">
                        Submit Tx Hash
                    </button>
                </form>
                <p id="submitTxFeedback" class="hidden text-xs mt-2"></p>
            </div>
        </div>

        <!-- Failure / Refund Banner -->
        <div id="failureOrRefundBanner" class="{{ in_array($order->status, ['FAILED', 'REFUNDED', 'EXPIRED']) ? '' : 'hidden' }} p-5 rounded-2xl bg-rose-950/60 border border-rose-500/40 text-sm text-rose-200 space-y-2">
            <div class="font-bold text-base text-rose-300">Swap Execution Notice</div>
            <p id="failureReasonText">{{ $order->failure_reason ?: 'This swap order did not complete.' }}</p>
            <div id="refundDetailsText" class="text-xs text-rose-300 font-mono">
                @if($order->refunded_amount)
                    Refunded Amount: {{ rtrim(rtrim((string) $order->refunded_amount, '0'), '.') }} {{ $order->sourceAsset->symbol }}
                @endif
            </div>
        </div>

        <!-- Execution Lifecycle Steps -->
        <div class="bg-[#0D1326] border border-slate-800 rounded-3xl p-6">
            <h3 class="text-base font-bold text-white mb-4">Execution Lifecycle</h3>
            <div id="executionStepsContainer" class="space-y-3">
                @foreach($order->steps as $step)
                <div class="flex items-center justify-between p-3.5 rounded-xl bg-[#070B19] border border-slate-800/90"
                    data-step-number="{{ $step->step_number }}">
                    <div class="flex items-center gap-3">
                        <span class="step-badge w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                            {{ $step->status === 'COMPLETED' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40' : ($step->status === 'IN_PROGRESS' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/40 animate-pulse' : ($step->status === 'FAILED' ? 'bg-rose-500/20 text-rose-400 border border-rose-500/40' : 'bg-slate-800 text-slate-500')) }}">
                            {{ $step->step_number }}
                        </span>
                        <div>
                            <div class="text-sm font-semibold text-white step-label">{{ $step->label }}</div>
                            <div class="text-xs text-slate-400 step-status-text">{{ $step->status }}</div>
                        </div>
                    </div>
                    <div class="step-tx-link text-xs">
                        @if($step->tx_hash && $step->explorer_url)
                            <a href="{{ $step->explorer_url }}" target="_blank" rel="noopener noreferrer" class="text-cyan-400 hover:underline font-mono">
                                View Tx &nearr;
                            </a>
                        @elseif($step->tx_hash)
                            <span class="font-mono text-slate-400">{{ \Illuminate\Support\Str::limit($step->tx_hash, 16) }}</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</main>
@endsection

