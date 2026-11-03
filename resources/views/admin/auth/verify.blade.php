<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Access Code | ZihuBridge Operations</title>

    <!-- Typography: Manrope & IBM Plex Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@300;400;500;600&family=Manrope:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .font-heading { font-family: 'Manrope', -apple-system, sans-serif; }
        .font-sans { font-family: 'IBM Plex Sans', -apple-system, sans-serif; }
        .font-mono { font-family: 'IBM Plex Mono', monospace; }
    </style>
</head>
<body class="bg-[#FBF9F5] text-[#121417] font-sans antialiased min-h-screen flex items-center justify-center p-4 selection:bg-[#121417] selection:text-white">

    <div class="max-w-md w-full bg-white border border-[#E8E5DF] rounded-xl p-8 shadow-xs">
        <!-- Brand Header -->
        <div class="text-center pb-6 border-b border-[#E8E5DF] mb-6">
            <a href="{{ route('home') }}" class="inline-block mb-3">
                <img src="{{ asset('assets/images/logo.png') }}" alt="ZihuBridge" class="h-7 w-auto mx-auto" />
            </a>
            <span class="text-xs font-heading font-medium tracking-wide text-[#6B7280] uppercase block mb-1">
                Step 2 of 2
            </span>
            <h1 class="text-2xl font-heading font-semibold text-[#121417] tracking-tight">
                Enter Verification Code
            </h1>
            <p class="text-xs text-[#52565E] mt-1">
                A 6-digit one-time code was sent to <strong class="text-[#121417] font-mono">{{ $email }}</strong>.
            </p>
        </div>

        @if (session('status'))
            <div class="mb-5 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-xs font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if (!empty($devOtp))
            <div class="mb-5 p-3 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-900">
                <span class="font-heading font-semibold uppercase block mb-1">Development Assistant</span>
                <span>Active OTP: </span><code class="font-mono font-bold text-sm bg-white px-2 py-0.5 rounded border border-amber-300">{{ $devOtp }}</code>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 p-3 rounded-lg bg-rose-50 border border-rose-200 text-xs font-medium text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.verify.post') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}" />

            <div>
                <label for="otpCodeInput" class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5 text-center">
                    6-Digit Security Code
                </label>
                <input id="otpCodeInput" type="text" name="code" maxlength="6" autofocus required
                    placeholder="123456"
                    class="w-full bg-[#FAF8F5] border @error('code') border-rose-400 @else border-[#DDD9D0] @enderror rounded-lg px-3.5 py-3 text-center text-2xl font-mono tracking-[0.35em] text-[#121417] focus:outline-none focus:border-[#121417] focus:bg-white transition" />
            </div>

            <button type="submit"
                class="w-full bg-[#121417] hover:bg-black text-white font-heading font-semibold py-2.5 rounded-lg text-xs md:text-sm transition cursor-pointer">
                Verify & Authenticate
            </button>
        </form>

        <div class="mt-6 pt-5 border-t border-[#E8E5DF] flex items-center justify-between text-xs text-[#6B7280]">
            <a href="{{ route('admin.login') }}" class="hover:text-[#121417] transition flex items-center gap-1 font-heading font-medium">
                &larr; Use Different Email
            </a>
            <form method="POST" action="{{ route('admin.login.send') }}" class="inline">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}" />
                <button type="submit" class="hover:text-[#121417] font-heading font-medium transition cursor-pointer bg-transparent border-none p-0">
                    Resend Code
                </button>
            </form>
        </div>
    </div>

</body>
</html>
