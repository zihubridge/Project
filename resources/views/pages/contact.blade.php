@extends('layout.master')

@section('content')
    <section class="bg-[#0B0F1A] text-gray-300">
        <div class="max-w-6xl mx-auto py-30 px-10 lg:px-0 xl:px-0">

            <!-- Header -->
            <div class="mb-10 text-center">
                <h1 class="text-4xl font-bold text-white mb-3">Contact Us</h1>
                <p class="text-gray-400 max-w-2xl mx-auto">
                    Have a question, issue, or feedback? Reach out and we'll get back to you.
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

                    {{-- Success Message --}}
                    @if (session('success'))
                        <div
                            class="mb-6 bg-green-900/30 border border-green-700 text-green-400 rounded-xl px-5 py-4 text-sm">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('contact.send') }}">
                        @csrf

                        <div class="space-y-5">

                            <div>
                                <label class="text-sm text-gray-400">Your Name</label>
                                <input type="text" name="name" value="{{ old('name') }}"
                                    class="w-full mt-2 bg-[#0B0F1A] border @error('name') border-red-500 @else border-gray-700 @enderror rounded-xl px-4 py-3 text-white focus:outline-none focus:border-blue-500">
                                @error('name')
                                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="text-sm text-gray-400">Email Address</label>
                                <input type="email" name="email" value="{{ old('email') }}"
                                    class="w-full mt-2 bg-[#0B0F1A] border @error('email') border-red-500 @else border-gray-700 @enderror rounded-xl px-4 py-3 text-white focus:outline-none focus:border-blue-500">
                                @error('email')
                                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="text-sm text-gray-400">Message</label>
                                <textarea rows="4" name="message"
                                    class="w-full mt-2 bg-[#0B0F1A] border @error('message') border-red-500 @else border-gray-700 @enderror rounded-xl px-4 py-3 text-white focus:outline-none focus:border-blue-500">{{ old('message') }}</textarea>
                                @error('message')
                                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                @enderror
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
