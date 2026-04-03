@extends('layout.master')

@section('content')
<section class="bg-[#0B0F1A] text-gray-300">
    <div class="max-w-6xl mx-auto py-30 px-10 lg:px-0 xl:px-0">
        
        <!-- Header -->
        <div class="mb-10">
            <h1 class="text-4xl font-bold text-white mb-3">About</h1>
        </div>

        <!-- Content -->
        <div class="space-y-10">

            <div>
                <h2 class="text-xl font-semibold text-white mb-3">About ZihuBridge</h2>
                <p class="text-gray-400 leading-relaxed">
                    ZihuBridge is a cross-chain swap platform built to simplify how assets move between
                    blockchain networks. It removes the need for multiple platforms, complex steps,
                    and manual processes by providing a single, structured flow for token exchange.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold text-white mb-3">The Problem</h2>
                <p class="text-gray-400 leading-relaxed">
                    Blockchain ecosystems are still fragmented. Each network operates independently with its own
                    tools, tokens, and requirements. Moving value across these networks often involves several
                    steps, different wallets, and technical details such as memos or destination tags.
                </p>
                <p class="text-gray-400 leading-relaxed mt-2">
                    This complexity increases cost, delays execution, and creates a high risk of user error.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold text-white mb-3">The Approach</h2>
                <p class="text-gray-400 leading-relaxed">
                    ZihuBridge replaces fragmented workflows with a single coordinated process.
                    Users define the source asset, destination asset, and wallet address, then complete one deposit.
                </p>
                <p class="text-gray-400 leading-relaxed mt-2">
                    The platform manages deposit tracking, conversion, liquidity routing, and final settlement
                    without requiring users to interact with multiple services.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold text-white mb-3">What Makes It Different</h2>
                <ul class="space-y-2 text-gray-400">
                    <li>• One structured process instead of multiple manual steps</li>
                    <li>• Clear pricing before execution with no hidden stages</li>
                    <li>• Validation of addresses and required parameters to reduce errors</li>
                    <li>• Refund handling when payout conditions are not met</li>
                </ul>
            </div>

            <div>
                <h2 class="text-xl font-semibold text-white mb-3">Execution Flow</h2>
                <p class="text-gray-400 leading-relaxed">
                    Once a swap is initiated, a deposit address is generated. After confirmation,
                    the system converts the asset through integrated liquidity sources and delivers
                    the final asset directly to the destination wallet.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold text-white mb-3">Built by CoreHives</h2>
                <p class="text-gray-400 leading-relaxed">
                    ZihuBridge is developed by CoreHives, a team experienced in building fintech
                    and blockchain systems. The focus is on reliability, clear workflows, and
                    long-term scalability.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold text-white mb-3">Direction</h2>
                <p class="text-gray-400 leading-relaxed">
                    The platform will expand to support additional networks, improve routing efficiency,
                    and introduce new features around liquidity and analytics. The long-term goal is to
                    make asset movement between blockchains straightforward and dependable.
                </p>
            </div>

        </div>
    </div>
</section>
@endsection