@extends('layout.master')

@section('content')
<main class="min-h-screen bg-[#FBF9F5] text-[#121417] font-sans antialiased selection:bg-[#121417] selection:text-white py-16 md:py-24 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <header class="mb-14 pb-8 border-b border-[#E8E5DF]">
            <span class="text-xs font-heading font-medium tracking-wide text-[#6B7280] uppercase block mb-3">
                Legal & Privacy
            </span>
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-heading font-semibold tracking-[-0.03em] text-[#121417] mb-3">
                Privacy Policy
            </h1>
            <p class="text-sm font-mono text-[#6B7280]">
                Last updated: {{ date('F Y') }}
            </p>
        </header>

        <div class="space-y-10 text-[15px] md:text-base text-[#52565E] leading-relaxed">
            <section>
                <h2 class="text-lg md:text-xl font-heading font-semibold text-[#121417] mb-2">1. Overview</h2>
                <p>
                    ZihuBridge operates as a non-custodial cross-chain swap interface. We do not require user account registration, password creation, or personal identification documentation. We never request, collect, or store private keys, seed phrases, or custodial access credentials.
                </p>
            </section>

            <section class="pt-8 border-t border-[#E8E5DF]">
                <h2 class="text-lg md:text-xl font-heading font-semibold text-[#121417] mb-2">2. Information Processed</h2>
                <ul class="list-disc list-inside space-y-2 text-[#52565E]">
                    <li>Public blockchain wallet addresses (origin refund address, destination recipient address) supplied by users to generate executable swap intent quotes.</li>
                    <li>Public on-chain transaction hashes, block numbers, and execution status receipts.</li>
                    <li>Ephemeral technical telemetry (IP address for rate-limiting, browser agent headers) strictly to prevent denial-of-service abuse and maintain infrastructure availability.</li>
                </ul>
            </section>

            <section class="pt-8 border-t border-[#E8E5DF]">
                <h2 class="text-lg md:text-xl font-heading font-semibold text-[#121417] mb-2">3. Utilization of Data</h2>
                <ul class="list-disc list-inside space-y-2 text-[#52565E]">
                    <li>To lock and fulfill cross-chain swap intents through integrated solver networks (such as NEAR Intents).</li>
                    <li>To provide real-time transaction tracking, status monitoring, and automated refund routing.</li>
                    <li>To enforce strict rate limits and assist with user support inquiries.</li>
                </ul>
            </section>

            <section class="pt-8 border-t border-[#E8E5DF]">
                <h2 class="text-lg md:text-xl font-heading font-semibold text-[#121417] mb-2">4. Third-Party Routing & Settlement</h2>
                <p>
                    We do not monetize or sell user data. Relevant public blockchain addresses and swap parameters are broadcast strictly to authorized decentralized solver networks and public RPC providers required to validate deposits and finalize delivery on destination chains.
                </p>
            </section>

            <section class="pt-8 border-t border-[#E8E5DF]">
                <h2 class="text-lg md:text-xl font-heading font-semibold text-[#121417] mb-2">5. Cryptographic Self-Custody</h2>
                <p>
                    All swaps occur on a sovereign, non-custodial basis. You retain full control over your cryptographic keys at all times. Deposits are directed to single-purpose intent deposit addresses that automatically refund to your designated origin address if swap conditions cannot be executed within the quoted threshold.
                </p>
            </section>
        </div>
    </div>
</main>
@endsection
