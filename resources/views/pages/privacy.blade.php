@extends('layout.master')

@section('content')
<section class="bg-[#0B0F1A] text-gray-300">
    <div class="max-w-7xl mx-auto px-5 py-20">

        <!-- Header -->
        <div class="mb-16">
            <h1 class="text-4xl font-bold text-white mb-3">Privacy Policy</h1>
            <p class="text-gray-400">Last updated: {{ date('F d, Y') }}</p>
        </div>

        <!-- Content  -->
        <div class="max-w-3xl space-y-12">

            <div>
                <h2 class="text-xl font-semibold text-white mb-3">1. Introduction</h2>
                <p class="text-gray-400 leading-relaxed">
                    ZihuBridge ("we", "our", "us") values your privacy...
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold text-white mb-3">2. Information We Collect</h2>
                <ul class="space-y-2 text-gray-400">
                    <li>• Wallet addresses and transaction details</li>
                    <li>• Usage data (pages visited, actions taken)</li>
                    <li>• Technical data (IP address, browser, device)</li>
                </ul>
            </div>

            <div>
                <h2 class="text-xl font-semibold text-white mb-3">3. How We Use Your Information</h2>
                <ul class="space-y-2 text-gray-400">
                    <li>• To process swaps and transactions</li>
                    <li>• To improve platform performance</li>
                    <li>• To detect fraud</li>
                </ul>
            </div>

            <!-- Important Notice -->
            <div class="bg-[#111827] border border-gray-800 rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-white mb-2">⚠️ Important Notice</h2>
                <p class="text-gray-400 text-sm leading-relaxed">
                    ZihuBridge is a non-custodial platform. We do not store or control your funds.
                    All blockchain transactions are irreversible and publicly visible.
                    Users are fully responsible for verifying transaction details before execution.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold text-white mb-3">4. Data Sharing</h2>
                <p class="text-gray-400 leading-relaxed">
                    We do not sell your data...
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold text-white mb-3">5. Security</h2>
                <p class="text-gray-400 leading-relaxed">
                    We implement industry-standard protections...
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold text-white mb-3">6. Contact</h2>
                <p class="text-gray-400">
                    support@zihubridge.com
                </p>
            </div>

        </div>
    </div>
</section>
@endsection