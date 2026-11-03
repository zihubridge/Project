@extends('layout.master')

@section('content')
<div class="min-h-screen bg-[#060914] text-gray-200 px-6 py-16">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-4xl font-bold text-white mb-4">Privacy Policy</h1>
        <p class="text-gray-400 mb-10">Last updated: {{ date('F Y') }}</p>

        <section class="mb-8">
            <h2 class="text-xl font-semibold text-white mb-2">1. Overview</h2>
            <p class="text-gray-400 leading-relaxed">
                ZihuBridge ("we", "our", "us") values your privacy. ZihuBridge operates as a non-custodial cross-chain swap interface. We do not require user accounts, and we never request, collect, or store private keys or seed phrases.
            </p>
        </section>

        <section class="mb-8">
            <h2 class="text-xl font-semibold text-white mb-2">2. Information We Process</h2>
            <ul class="list-disc list-inside text-gray-400 space-y-2">
                <li>Public wallet addresses (origin refund address, destination recipient address) submitted to generate swap quotes and routing instructions.</li>
                <li>Public on-chain transaction hashes and swap execution statuses.</li>
                <li>Standard technical telemetry (IP address for rate-limiting, browser type) to prevent API abuse.</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="text-xl font-semibold text-white mb-2">3. How We Use Information</h2>
            <ul class="list-disc list-inside text-gray-400 space-y-2">
                <li>To request quotes and lock cross-chain swap intents with integrated execution providers (such as NEAR Intents).</li>
                <li>To display real-time swap progress, settlement confirmations, and refund statuses.</li>
                <li>To protect platform security, enforce rate limits, and respond to support inquiries.</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="text-xl font-semibold text-white mb-2">4. Data Sharing</h2>
            <p class="text-gray-400 leading-relaxed">
                We do not sell personal data. Public blockchain addresses and swap parameters are shared strictly with the active cross-chain execution provider and public blockchain RPC nodes necessary to route and settle your swap.
            </p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-white mb-2">5. Security</h2>
            <p class="text-gray-400 leading-relaxed">
                We implement strict input validation, HTTPS transport encryption, rate limiting, and non-custodial wallet signing so that you remain in control of your digital assets at all times.
            </p>
        </section>
    </div>
</div>
@endsection
