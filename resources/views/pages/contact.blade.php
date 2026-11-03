@extends('layout.master')

@section('content')
<section class="min-h-screen bg-[#FBF9F5] text-[#121417] font-sans antialiased selection:bg-[#121417] selection:text-white py-16 md:py-24 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">

        <!-- Header -->
        <header class="mb-14 pb-8 border-b border-[#E8E5DF]">
            <span class="text-xs font-heading font-medium tracking-wide text-[#6B7280] uppercase block mb-3">
                Support & Communications
            </span>
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-heading font-semibold tracking-[-0.03em] text-[#121417] mb-3">
                Contact & Support
            </h1>
            <p class="text-base md:text-lg text-[#52565E] max-w-xl font-light">
                Direct channel for transaction assistance, technical integration inquiries, or institutional token listing proposals.
            </p>
        </header>

        <!-- Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">

            <!-- Information Cards (5 cols) -->
            <div class="lg:col-span-5 space-y-5">
                <div class="bg-white border border-[#E8E5DF] rounded-xl p-6">
                    <span class="text-xs font-heading font-semibold uppercase tracking-wider text-[#6B7280] block mb-1">Direct Desk</span>
                    <h2 class="text-base font-heading font-semibold text-[#121417] mb-2">Technical Support</h2>
                    <p class="text-xs md:text-sm text-[#52565E] leading-relaxed mb-4">
                        For questions regarding locked intents, transaction status queries, or route availability.
                    </p>
                    <a href="mailto:support@zihubridge.com" class="text-xs md:text-sm font-mono font-medium text-[#121417] hover:underline">
                        support@zihubridge.com
                    </a>
                </div>

                <div class="bg-white border border-[#E8E5DF] rounded-xl p-6">
                    <span class="text-xs font-heading font-semibold uppercase tracking-wider text-[#6B7280] block mb-1">SLA Standard</span>
                    <h2 class="text-base font-heading font-semibold text-[#121417] mb-2">Response Times</h2>
                    <p class="text-xs md:text-sm text-[#52565E] leading-relaxed">
                        Inquiries are reviewed by the engineering desk within 24 hours. Critical transaction settlement or refund discrepancies are prioritized immediately.
                    </p>
                </div>

                <div class="bg-[#F7F5F0] border border-[#E8E5DF] rounded-xl p-6">
                    <span class="text-xs font-heading font-semibold uppercase tracking-wider text-[#6B7280] block mb-1">Important</span>
                    <h2 class="text-base font-heading font-semibold text-[#121417] mb-2">Expedited Resolution</h2>
                    <p class="text-xs md:text-sm text-[#52565E] leading-relaxed">
                        When inquiring about an in-flight or completed swap, always include your <strong class="font-mono text-[#121417]">Swap Order UUID</strong> or origin transaction hash to expedite verification.
                    </p>
                </div>
            </div>

            <!-- Form Card (7 cols) -->
            <div class="lg:col-span-7 bg-white border border-[#E8E5DF] rounded-xl p-6 sm:p-8">

                {{-- Success Banner --}}
                @if (session('success'))
                    <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg px-4 py-3 text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.send') }}">
                    @csrf

                    <div class="space-y-5">

                        <div>
                            <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                                Your Name
                            </label>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="Alex Mercer"
                                class="w-full bg-[#FAF8F5] border @error('name') border-rose-400 @else border-[#DDD9D0] @enderror rounded-lg px-3.5 py-2.5 text-sm text-[#121417] placeholder-[#8A8F99] focus:outline-none focus:border-[#121417] focus:bg-white transition" />
                            @error('name')
                                <p class="text-rose-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                                Email Address
                            </label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="alex@example.com"
                                class="w-full bg-[#FAF8F5] border @error('email') border-rose-400 @else border-[#DDD9D0] @enderror rounded-lg px-3.5 py-2.5 text-sm text-[#121417] placeholder-[#8A8F99] focus:outline-none focus:border-[#121417] focus:bg-white transition" />
                            @error('email')
                                <p class="text-rose-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                                Message & Transaction Details
                            </label>
                            <textarea rows="5" name="message" placeholder="Provide details regarding your inquiry, including Swap UUID or deposit tx hash if applicable..."
                                class="w-full bg-[#FAF8F5] border @error('message') border-rose-400 @else border-[#DDD9D0] @enderror rounded-lg px-3.5 py-2.5 text-sm text-[#121417] placeholder-[#8A8F99] focus:outline-none focus:border-[#121417] focus:bg-white transition leading-relaxed">{{ old('message') }}</textarea>
                            @error('message')
                                <p class="text-rose-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                class="w-full bg-[#121417] hover:bg-black text-white font-heading font-semibold py-3 rounded-lg text-sm transition cursor-pointer">
                                Send Message
                            </button>
                        </div>

                    </div>
                </form>
            </div>

        </div>
    </div>
</section>
@endsection
