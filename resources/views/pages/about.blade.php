@extends('layout.master')

@section('content')
<section class="min-h-screen bg-[#FBF9F5] text-[#121417] font-sans antialiased selection:bg-[#121417] selection:text-white py-16 md:py-24 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        
        <!-- Header -->
        <header class="mb-14 pb-8 border-b border-[#E8E5DF]">
            <span class="text-xs font-heading font-medium tracking-wide text-[#6B7280] uppercase block mb-3">
                Platform Overview
            </span>
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-heading font-semibold tracking-[-0.03em] text-[#121417] mb-4">
                About ZihuBridge
            </h1>
            <p class="text-lg md:text-xl text-[#52565E] leading-relaxed max-w-2xl font-light">
                Non-custodial cross-chain architecture designed to eliminate multi-step bridging workflows through direct solver intent execution.
            </p>
        </header>

        <!-- Main Narrative -->
        <div class="space-y-12">

            <div>
                <h2 class="text-xl md:text-2xl font-heading font-semibold tracking-tight text-[#121417] mb-3">
                    The Architecture
                </h2>
                <p class="text-[15px] md:text-base text-[#52565E] leading-relaxed">
                    ZihuBridge is built to simplify how assets move between sovereign blockchain networks. Instead of requiring users to navigate multiple lock-and-mint bridges, maintain separate gas tokens, or decipher disparate DEX routers, ZihuBridge coordinates cross-chain exchange through a unified, declarative intent flow.
                </p>
            </div>

            <div class="pt-8 border-t border-[#E8E5DF]">
                <h2 class="text-xl md:text-2xl font-heading font-semibold tracking-tight text-[#121417] mb-3">
                    The Problem with Traditional Bridges
                </h2>
                <p class="text-[15px] md:text-base text-[#52565E] leading-relaxed">
                    Blockchain ecosystems remain structurally fragmented. Each layer operates independently with incompatible consensus mechanisms, distinct address formats, and unique token standards. Moving value across these networks often demands multiple transactions, disparate wallets, manual slippage configuration, and technical inputs like destination tags or memos.
                </p>
                <p class="text-[15px] md:text-base text-[#52565E] leading-relaxed mt-3">
                    This friction introduces high gas overhead, execution delays, and a severe risk of permanent capital loss from user input error or smart contract bridge exploits.
                </p>
            </div>

            <div class="pt-8 border-t border-[#E8E5DF]">
                <h2 class="text-xl md:text-2xl font-heading font-semibold tracking-tight text-[#121417] mb-3">
                    The Intent-Based Approach
                </h2>
                <p class="text-[15px] md:text-base text-[#52565E] leading-relaxed">
                    ZihuBridge replaces fragmented multi-step routing with non-custodial intent settlement. Users simply specify their deposit asset, desired destination token, and recipient address.
                </p>
                <p class="text-[15px] md:text-base text-[#52565E] leading-relaxed mt-3">
                    Competitive market solvers take on the routing complexity and cross-chain execution risk, fulfilling the exact required output before settlement is finalized. You never surrender custody of your private keys or trust intermediary custodial pools.
                </p>
            </div>

            <div class="pt-8 border-t border-[#E8E5DF]">
                <h2 class="text-xl md:text-2xl font-heading font-semibold tracking-tight text-[#121417] mb-4">
                    Core Principles
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-white border border-[#E8E5DF] rounded-xl p-5">
                        <h3 class="text-sm font-heading font-semibold text-[#121417] mb-1.5">Guaranteed Minimums</h3>
                        <p class="text-xs text-[#52565E] leading-relaxed">Quotes include a binding minimum output floor. If solver execution does not meet this requirement, the swap is halted and refunded.</p>
                    </div>
                    <div class="bg-white border border-[#E8E5DF] rounded-xl p-5">
                        <h3 class="text-sm font-heading font-semibold text-[#121417] mb-1.5">Self-Custodial Security</h3>
                        <p class="text-xs text-[#52565E] leading-relaxed">Deposits are sent to single-use deterministic intent addresses. ZihuBridge never holds, pools, or manages user private keys.</p>
                    </div>
                    <div class="bg-white border border-[#E8E5DF] rounded-xl p-5">
                        <h3 class="text-sm font-heading font-semibold text-[#121417] mb-1.5">Strict Origin Refunds</h3>
                        <p class="text-xs text-[#52565E] leading-relaxed">Every transaction registers an origin-chain refund address upfront, ensuring funds return to your control if routing fails.</p>
                    </div>
                    <div class="bg-white border border-[#E8E5DF] rounded-xl p-5">
                        <h3 class="text-sm font-heading font-semibold text-[#121417] mb-1.5">Parameter Validation</h3>
                        <p class="text-xs text-[#52565E] leading-relaxed">Dynamic network checks validate checksums, regex patterns, and mandatory destination memo/tag requirements prior to quote lock.</p>
                    </div>
                </div>
            </div>

            <div class="pt-8 border-t border-[#E8E5DF]">
                <h2 class="text-xl md:text-2xl font-heading font-semibold tracking-tight text-[#121417] mb-3">
                    Execution Lifecycle
                </h2>
                <p class="text-[15px] md:text-base text-[#52565E] leading-relaxed">
                    Once an intent quote is locked, a dedicated one-time deposit address is generated with a time-limited quote window. Upon detecting your confirmed deposit, the solver network executes the conversion and dispatches the final settlement directly to your destination wallet.
                </p>
            </div>

            <div class="pt-8 border-t border-[#E8E5DF]">
                <div class="bg-[#F7F5F0] border border-[#E8E5DF] rounded-xl p-6 md:p-8">
                    <span class="text-xs font-heading font-medium tracking-wide text-[#6B7280] uppercase block mb-1">Engineering</span>
                    <h2 class="text-lg md:text-xl font-heading font-semibold text-[#121417] mb-2">Built for Reliability</h2>
                    <p class="text-sm text-[#52565E] leading-relaxed">
                        ZihuBridge is engineered with a strict focus on resilient fintech and decentralized infrastructure. The protocol is committed to predictable routing, minimal attack surface, transparent execution data, and user sovereignty.
                    </p>
                </div>
            </div>

        </div>
    </div>
</section>
@endsection
