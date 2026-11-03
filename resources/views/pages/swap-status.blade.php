@extends('layout.master')

@section('content')
<main class="min-h-screen bg-[#FBF9F5] text-[#121417] font-sans antialiased selection:bg-[#121417] selection:text-white py-12 md:py-20 px-4 sm:px-6 lg:px-8"
    id="swapStatusPage"
    data-swap-uuid="{{ $order->swap_uuid }}">
    <div class="max-w-4xl mx-auto space-y-6">

        <!-- Top Header Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white border border-[#E8E5DF] rounded-xl p-6 shadow-xs">
            <div>
                <div class="flex items-center gap-2 text-xs text-[#6B7280]">
                    <span class="font-heading font-medium">Swap Order UUID:</span>
                    <code id="swapUuidText" class="text-[#121417] font-mono font-medium">{{ $order->swap_uuid }}</code>
                    <button type="button" data-copy-target="#swapUuidText" class="text-[#6B7280] hover:text-[#121417] cursor-pointer" title="Copy Swap UUID">
                        <ion-icon name="copy-outline"></ion-icon>
                    </button>
                </div>
                <h1 id="swapStatusHeading" class="text-xl md:text-2xl font-heading font-semibold text-[#121417] mt-1.5">
                    {{ $order->statusEnum()->label() }}
                </h1>
            </div>

            <div class="flex items-center gap-3">
                <span id="swapStatusPill" class="px-3 py-1 rounded text-xs font-heading font-semibold uppercase tracking-wider bg-[#F0ECE1] text-[#2D3139] border border-[#DDD9D0]">
                    {{ $order->status }}
                </span>
                <a href="{{ route('contact') }}" class="text-xs font-heading font-medium px-3.5 py-1.5 rounded-lg bg-[#FAF8F5] hover:bg-[#121417] hover:text-white border border-[#DDD9D0] text-[#52565E] transition">
                    Need Help?
                </a>
            </div>
        </div>

        <!-- Amounts Summary Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-6 rounded-xl bg-white border border-[#E8E5DF] shadow-xs">
                <div class="text-xs font-heading font-semibold uppercase tracking-wider text-[#6B7280]">Deposit Asset</div>
                <div class="text-2xl font-heading font-semibold text-[#121417] mt-1.5">
                    <span id="orderAmountIn">{{ rtrim(rtrim((string) $order->amount_in, '0'), '.') }}</span>
                    <span>{{ $order->sourceAsset->symbol }}</span>
                </div>
                <div class="text-xs text-[#52565E] mt-1">
                    Network: <span class="text-[#121417] font-medium">{{ $order->sourceNetwork->display_name }}</span>
                </div>
                <div class="text-xs text-[#6B7280] mt-3 break-all font-mono bg-[#FAF8F5] p-2.5 rounded border border-[#E8E5DF]">
                    <span class="text-[#8A8F99] block text-[11px] uppercase tracking-wider font-sans mb-0.5">Refund Address:</span>
                    {{ $order->refund_address }}
                </div>
            </div>

            <div class="p-6 rounded-xl bg-white border border-[#E8E5DF] shadow-xs">
                <div class="text-xs font-heading font-semibold uppercase tracking-wider text-[#6B7280]">Receiving Asset</div>
                <div class="text-2xl font-heading font-semibold text-[#0F6B48] mt-1.5">
                    <span id="orderAmountOut">
                        {{ rtrim(rtrim((string) ($order->actual_amount_out ?? $order->expected_amount_out), '0'), '.') }}
                    </span>
                    <span>{{ $order->destinationAsset->symbol }}</span>
                </div>
                <div class="text-xs text-[#52565E] mt-1">
                    Network: <span class="text-[#121417] font-medium">{{ $order->destinationNetwork->display_name }}</span>
                    <span class="ml-2 text-[#8A8F99] font-mono">(Min: {{ rtrim(rtrim((string) $order->min_amount_out, '0'), '.') }})</span>
                </div>
                <div class="text-xs text-[#6B7280] mt-3 break-all font-mono bg-[#FAF8F5] p-2.5 rounded border border-[#E8E5DF]">
                    <div class="flex items-center justify-between mb-0.5">
                        <span class="text-[#8A8F99] block text-[11px] uppercase tracking-wider font-sans">Recipient Address:</span>
                        <button type="button" data-copy-target="#recipientAddressText"
                            class="text-[#52565E] hover:text-[#121417] text-[11px] font-sans inline-flex items-center gap-1 cursor-pointer">
                            <ion-icon name="copy-outline"></ion-icon>
                            <span>Copy</span>
                        </button>
                    </div>
                    <span id="recipientAddressText">{{ $order->recipient_address }}</span>
                    @if($order->recipient_memo)
                        <span class="block text-amber-800 font-semibold mt-1">Memo/Tag: {{ $order->recipient_memo }}</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Deposit Instructions & Non-Custodial Wallet Action Card -->
        <div id="depositInstructionsCard" class="{{ $order->status === 'DEPOSIT_PENDING' ? '' : 'hidden' }} bg-white border border-[#DDD9D0] rounded-xl p-6 sm:p-8 space-y-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#E8E5DF] pb-4">
                <div>
                    <h2 class="text-lg font-heading font-semibold text-[#121417]">Send Deposit to Execute Intent</h2>
                    <p class="text-xs text-[#52565E] mt-0.5">
                        Transfer the exact amount of <strong class="text-[#121417]">{{ $order->sourceAsset->symbol }}</strong> on <strong class="text-[#121417]">{{ $order->sourceNetwork->display_name }}</strong> before quote expiration.
                    </p>
                </div>
                <div class="text-left sm:text-right shrink-0">
                    <span class="text-xs text-[#6B7280] block font-heading font-medium">Quote Window:</span>
                    <div id="depositCountdownTimer" class="text-base font-mono font-bold text-amber-700">--:--</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                <!-- QR Code Box -->
                <div class="md:col-span-4 flex flex-col items-center justify-center bg-[#FAF8F5] p-4 rounded-xl border border-[#E8E5DF]">
                    <img id="depositQrImage"
                        src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($order->deposit_address) }}"
                        alt="Deposit Address QR"
                        class="w-40 h-40 object-contain rounded" />
                    <span class="text-[11px] text-[#6B7280] font-heading font-medium mt-2">Scan in Wallet App</span>
                </div>

                <!-- Address, Memo & Wallet Sign Controls -->
                <div class="md:col-span-8 space-y-4">
                    <div>
                        <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                            Intent Deposit Address ({{ $order->sourceNetwork->display_name }})
                        </label>
                        <div class="flex items-center gap-2 bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg p-3">
                            <code id="depositAddressValue" class="text-xs md:text-sm text-[#121417] font-mono break-all flex-1">{{ $order->deposit_address }}</code>
                            <button type="button" data-copy-target="#depositAddressValue"
                                class="px-3 py-1.5 rounded bg-[#121417] hover:bg-black text-xs font-heading font-medium text-white cursor-pointer transition">
                                Copy
                            </button>
                        </div>
                    </div>

                    @if($order->deposit_memo)
                    <div class="p-4 rounded-lg bg-amber-50 border border-amber-200">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-heading font-semibold uppercase tracking-wider text-amber-900">
                                Mandatory Deposit Memo / Destination Tag
                            </span>
                            <button type="button" data-copy-target="#depositMemoValue"
                                class="px-2.5 py-1 rounded bg-amber-200 hover:bg-amber-300 text-xs text-amber-950 font-heading font-medium cursor-pointer transition">
                                Copy Memo
                            </button>
                        </div>
                        <code id="depositMemoValue" class="block text-base font-mono font-bold text-amber-950 mt-1">{{ $order->deposit_memo }}</code>
                        <p class="text-[11px] text-amber-800 mt-1">
                            You MUST include this Memo / Destination Tag when broadcasting your deposit on {{ $order->sourceNetwork->display_name }}.
                        </p>
                    </div>
                    @endif

                    <!-- One-Click Connected Wallet Sign & Send -->
                    <div class="pt-2 flex flex-col sm:flex-row gap-3">
                        <button id="payWithConnectedWalletBtn" type="button"
                            class="flex-1 py-3 px-4 rounded-lg bg-[#121417] hover:bg-black text-xs md:text-sm font-heading font-semibold text-white transition cursor-pointer flex items-center justify-center gap-2">
                            <ion-icon name="wallet-outline" class="text-base"></ion-icon>
                            <span>Send Deposit with Connected Wallet</span>
                        </button>
                    </div>
                    <p id="walletPaymentFeedback" class="hidden text-xs rounded-lg p-2.5"></p>
                </div>
            </div>

            <!-- Manual Deposit Transaction Hash Submission -->
            <div class="pt-5 border-t border-[#E8E5DF]">
                <label for="manualTxHashInput" class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Sent manually from an external wallet? Paste origin transaction hash:
                </label>
                <form id="submitDepositTxForm" class="flex flex-col sm:flex-row gap-2">
                    <input id="manualTxHashInput" type="text"
                        value="{{ $order->source_tx_hash }}"
                        placeholder="Paste origin transaction hash (0x... or chain txid)"
                        class="flex-1 bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3.5 py-2.5 text-xs md:text-sm text-[#121417] font-mono placeholder-[#8A8F99] focus:outline-none focus:border-[#121417] transition" />
                    <button type="submit"
                        class="px-5 py-2.5 rounded-lg bg-[#EFECE6] hover:bg-[#121417] hover:text-white border border-[#DDD9D0] text-xs md:text-sm font-heading font-semibold text-[#121417] transition cursor-pointer">
                        Submit Tx Hash
                    </button>
                </form>
                <p id="submitTxFeedback" class="hidden text-xs mt-2"></p>
            </div>
        </div>

        <!-- Failure / Refund Banner -->
        <div id="failureOrRefundBanner" class="{{ in_array($order->status, ['FAILED', 'REFUNDED', 'EXPIRED']) ? '' : 'hidden' }} p-5 rounded-xl bg-rose-50 border border-rose-200 text-sm text-rose-800 space-y-2">
            <div class="font-heading font-semibold text-base text-rose-900">Swap Execution Notice</div>
            <p id="failureReasonText">{{ $order->failure_reason ?: 'This swap order did not complete.' }}</p>
            <div id="refundDetailsText" class="text-xs text-rose-900 font-mono">
                @if($order->refunded_amount)
                    Refunded Amount: {{ rtrim(rtrim((string) $order->refunded_amount, '0'), '.') }} {{ $order->sourceAsset->symbol }}
                @endif
            </div>
        </div>

        <!-- Execution Lifecycle Steps -->
        <div class="bg-white border border-[#E8E5DF] rounded-xl p-6 shadow-xs">
            <h3 class="text-base font-heading font-semibold text-[#121417] mb-4">Execution Lifecycle</h3>
            <div id="executionStepsContainer" class="space-y-3">
                @foreach($order->steps as $step)
                <div class="flex items-center justify-between p-3.5 rounded-lg bg-[#FAF8F5] border border-[#E8E5DF]"
                    data-step-number="{{ $step->step_number }}">
                    <div class="flex items-center gap-3">
                        <span class="step-badge w-7 h-7 rounded-md flex items-center justify-center text-xs font-heading font-semibold
                            {{ $step->status === 'COMPLETED' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : ($step->status === 'IN_PROGRESS' ? 'bg-amber-100 text-amber-800 border border-amber-300' : ($step->status === 'FAILED' ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-[#EAE7E0] text-[#6B7280] border border-[#DDD9D0]')) }}">
                            {{ $step->step_number }}
                        </span>
                        <div>
                            <div class="text-sm font-heading font-medium text-[#121417] step-label">{{ $step->label }}</div>
                            <div class="text-xs text-[#6B7280] step-status-text">{{ $step->status }}</div>
                        </div>
                    </div>
                    <div class="step-tx-link text-xs">
                        @if($step->tx_hash && $step->explorer_url)
                            <a href="{{ $step->explorer_url }}" target="_blank" rel="noopener noreferrer" class="text-[#121417] hover:underline font-mono">
                                View Tx &nearr;
                            </a>
                        @elseif($step->tx_hash)
                            <span class="font-mono text-[#6B7280]">{{ \Illuminate\Support\Str::limit($step->tx_hash, 16) }}</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</main>
@endsection
