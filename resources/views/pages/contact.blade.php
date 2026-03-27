@extends('layout.master')

@section('content')
    <section class="bg-[#0B0F1A] text-gray-300">
        <div class="max-w-7xl mx-auto px-5 py-20">

            <!-- Header -->
            <div class="mb-16 text-center">
                <h1 class="text-4xl font-bold text-white mb-3">Contact Us</h1>
                <p class="text-gray-400 max-w-2xl mx-auto">
                    Have a question, issue, or feedback? Reach out and we’ll get back to you.
                </p>
            </div>

            <!-- Content -->
            <div class="grid md:grid-cols-2 gap-10">

                <!-- Info -->
                <div class="space-y-6">

                    <div class="bg-[#111827] border border-gray-800 rounded-2xl p-6">
                        <h2 class="text-lg font-semibold text-white mb-2">Support</h2>
                        <p class="text-gray-400 text-sm">
                            For general inquiries or help with your transactions.
                        </p>
                        <p class="text-white font-medium mt-3">
                            support@zihubridge.com
                        </p>
                    </div>

                    <div class="bg-[#111827] border border-gray-800 rounded-2xl p-6">
                        <h2 class="text-lg font-semibold text-white mb-2">Response Time</h2>
                        <p class="text-gray-400 text-sm">
                            We usually respond within 24 hours. Critical issues are prioritized.
                        </p>
                    </div>

                    <div class="bg-[#111827] border border-gray-800 rounded-2xl p-6">
                        <h2 class="text-lg font-semibold text-white mb-2">Note</h2>
                        <p class="text-gray-400 text-sm">
                            Always include your transaction ID when contacting support to speed up resolution.
                        </p>
                    </div>

                </div>

                <!-- Form -->
                <div class="bg-[#111827] border border-gray-800 rounded-2xl p-6">

                    <form method="POST" action="#">
                        @csrf

                        <div class="space-y-5">

                            <div>
                                <label class="text-sm text-gray-400">Your Name</label>
                                <input type="text"
                                    class="w-full mt-2 bg-[#0B0F1A] border border-gray-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-blue-500">
                            </div>

                            <div>
                                <label class="text-sm text-gray-400">Email Address</label>
                                <input type="email"
                                    class="w-full mt-2 bg-[#0B0F1A] border border-gray-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-blue-500">
                            </div>

                            <div>
                                <label class="text-sm text-gray-400">Message</label>
                                <textarea rows="4"
                                    class="w-full mt-2 bg-[#0B0F1A] border border-gray-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-blue-500"></textarea>
                            </div>

                            <button type="submit"
                                class="w-full bg-blue-600 hover:bg-blue-700 transition text-white font-semibold py-3 rounded-xl">
                                Send Message
                            </button>

                        </div>
                    </form>
                </div>

            </div>
        </div>
    </section>
@endsection
