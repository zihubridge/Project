@extends('layout.master')

@section('content')
<main class="min-h-screen bg-[#FBF9F5] text-[#121417] font-sans antialiased selection:bg-[#121417] selection:text-white py-16 md:py-24 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <header class="mb-14 pb-8 border-b border-[#E8E5DF]">
            <span class="text-xs font-heading font-medium tracking-wide text-[#6B7280] uppercase block mb-3">
                Legal & Governance
            </span>
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-heading font-semibold tracking-[-0.03em] text-[#121417] mb-3">
                Terms of Service
            </h1>
            <p class="text-sm font-mono text-[#6B7280]">
                Last updated: {{ date('F d, Y') }}
            </p>
        </header>

        <div class="space-y-10 text-[15px] md:text-base text-[#52565E] leading-relaxed">
            <section>
                <h2 class="text-lg md:text-xl font-heading font-semibold text-[#121417] mb-2">1. Acceptance of Terms</h2>
                <p>
                    By connecting a wallet, submitting a swap intent, or using ZihuBridge, you agree to be bound by these Terms of Service. If you do not accept these terms in their entirety, you must discontinue platform use immediately.
                </p>
            </section>

            <section class="pt-8 border-t border-[#E8E5DF]">
                <h2 class="text-lg md:text-xl font-heading font-semibold text-[#121417] mb-2">2. Nature of the Protocol</h2>
                <p>
                    ZihuBridge is a non-custodial decentralized interface coordinating cross-chain token swaps via intent solvers. ZihuBridge does not custody, escrow, or take control of digital assets at any stage of execution. Users interact directly with smart contracts and decentralized solver networks.
                </p>
            </section>

            <div class="bg-[#F7F5F0] border border-[#E8E5DF] rounded-xl p-6">
                <h3 class="text-base font-heading font-semibold text-[#121417] mb-2">Risk Notice</h3>
                <p class="text-xs md:text-sm text-[#52565E] leading-relaxed">
                    Cryptocurrency transactions are irreversible once broadcast to a blockchain network. Market volatility, network congestion, and slippage can affect final output amounts. By submitting a swap, you acknowledge that you bear sole responsibility for transaction parameters.
                </p>
            </div>

            <section class="pt-8 border-t border-[#E8E5DF]">
                <h2 class="text-lg md:text-xl font-heading font-semibold text-[#121417] mb-2">3. User Responsibilities</h2>
                <ul class="list-disc list-inside space-y-2 text-[#52565E]">
                    <li>Ensure accurate recipient and origin refund addresses prior to depositing funds.</li>
                    <li>Verify all deposit parameters, including minimum required amounts and network selection.</li>
                    <li>Ensure compliance with applicable laws and sanctions regulations in your jurisdiction.</li>
                </ul>
            </section>

            <section class="pt-8 border-t border-[#E8E5DF]">
                <h2 class="text-lg md:text-xl font-heading font-semibold text-[#121417] mb-2">4. Memo & Destination Tag Requirements</h2>
                <p>
                    Certain networks (including XRP, Stellar, and Cosmos ecosystems) require a numeric memo or destination tag to credit deposits. It is the user's explicit responsibility to supply and attach the required memo. Omission or corruption of memo values may result in irreversible settlement failure.
                </p>
            </section>

            <section class="pt-8 border-t border-[#E8E5DF]">
                <h2 class="text-lg md:text-xl font-heading font-semibold text-[#121417] mb-2">5. Protocol Fees & Slippage Floors</h2>
                <p>
                    Swap quotes display all applicable protocol and solver fees transparently prior to order generation. All quotes incorporate a deterministic minimum output floor (<code class="font-mono text-xs text-[#121417]">min_amount_out</code>). If competitive routing cannot meet this floor, execution will cease and the deposit will be returned to the origin refund address.
                </p>
            </section>

            <section class="pt-8 border-t border-[#E8E5DF]">
                <h2 class="text-lg md:text-xl font-heading font-semibold text-[#121417] mb-2">6. Limitation of Liability</h2>
                <p>
                    In no event shall ZihuBridge or its developers be held liable for losses arising from smart contract failures, third-party RPC downtimes, wallet incompatibility, or user address errors.
                </p>
            </section>

            <section class="pt-8 border-t border-[#E8E5DF]">
                <h2 class="text-lg md:text-xl font-heading font-semibold text-[#121417] mb-2">7. Support Inquiries</h2>
                <p>
                    For questions concerning these terms or order verifications, contact <a href="mailto:support@zihubridge.com" class="font-mono text-[#121417] hover:underline">support@zihubridge.com</a>.
                </p>
            </section>
        </div>
    </div>
</main>
@endsection
