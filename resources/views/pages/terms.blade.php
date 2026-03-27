@extends('layout.master')

@section('content')
    <section class="bg-[#0B0F1A] text-gray-300">
        <div class="max-w-7xl mx-auto px-5 py-20">

            <!-- Header -->
            <div class="mb-16">
                <h1 class="text-4xl font-bold text-white mb-3">Terms & Conditions</h1>
                <p class="text-gray-400">Last updated: {{ date('F d, Y') }}</p>
            </div>

            <!-- Content -->
            <div class="max-w-3xl space-y-10">

                <div>
                    <h2 class="text-xl font-semibold text-white mb-3">1. Acceptance of Terms</h2>
                    <p class="text-gray-400 leading-relaxed">
                        By accessing or using ZihuBridge, you agree to be bound by these Terms and Conditions.
                        If you do not agree, you must not use the platform.
                    </p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-white mb-3">2. Nature of Service</h2>
                    <p class="text-gray-400 leading-relaxed">
                        ZihuBridge provides a non-custodial cryptocurrency swapping service. We do not hold,
                        store, or control user funds at any point during the transaction process.
                    </p>
                </div>

                <!-- Important -->
                <div class="bg-[#111827] border border-gray-800 rounded-2xl p-6">
                    <h2 class="text-lg font-semibold text-white mb-2">⚠️ Risk Disclaimer</h2>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Cryptocurrency transactions are irreversible and involve significant risk. Prices are volatile,
                        and users may experience losses. By using this platform, you accept full responsibility.
                    </p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-white mb-3">3. User Responsibilities</h2>
                    <ul class="space-y-2 text-gray-400">
                        <li>• Provide accurate wallet addresses</li>
                        <li>• Verify all transaction details before confirming</li>
                        <li>• Do not use the platform for illegal activities</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-white mb-3">4. Transactions</h2>
                    <p class="text-gray-400 leading-relaxed">
                        All transactions are irreversible. ZihuBridge is not responsible for losses due to user errors,
                        incorrect addresses, or network issues.
                    </p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-white mb-3">5. Memo / Destination Tag Requirements</h2>
                    <p class="text-gray-400 leading-relaxed">
                        Certain blockchain networks (including but not limited to XRP and XLM) require a memo,
                        destination tag, or similar identifier to correctly route transactions.
                    </p>

                    <p class="text-gray-400 leading-relaxed mt-2">
                        It is the sole responsibility of the user to include the correct memo or destination tag
                        when sending funds. Failure to provide accurate routing information may result in permanent
                        loss of funds.
                    </p>

                    <p class="text-gray-400 leading-relaxed mt-2">
                        ZihuBridge is not responsible for any loss of funds caused by missing, incorrect, or improperly
                        formatted memo or destination tag information.
                    </p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-white mb-3">6. Fees</h2>
                    <p class="text-gray-400 leading-relaxed">
                        Fees may apply and are shown before confirmation. They may change based on network conditions.
                    </p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-white mb-3">7. Limitation of Liability</h2>
                    <p class="text-gray-400 leading-relaxed">
                        We are not liable for any direct or indirect losses resulting from use of the platform.
                    </p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-white mb-3">8. Service Availability</h2>
                    <p class="text-gray-400 leading-relaxed">
                        Services may be interrupted or modified at any time without notice.
                    </p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-white mb-3">9. Changes to Terms</h2>
                    <p class="text-gray-400 leading-relaxed">
                        Continued use of the platform means you accept updated terms.
                    </p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-white mb-3">10. Contact</h2>
                    <p class="text-gray-400">
                        support@zihubridge.com
                    </p>
                </div>

            </div>
        </div>
    </section>
@endsection
