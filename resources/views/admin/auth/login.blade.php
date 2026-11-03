<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Sign In | ZihuBridge</title>

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
                Security Perimeter
            </span>
            <h1 class="text-2xl font-heading font-semibold text-[#121417] tracking-tight">
                Operations Console
            </h1>
            <p class="text-xs text-[#52565E] mt-1">
                Passwordless one-time verification for authorized administrators.
            </p>
        </div>

        @if (session('status'))
            <div class="mb-5 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-xs font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 p-3 rounded-lg bg-rose-50 border border-rose-200 text-xs font-medium text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.send') }}" class="space-y-4">
            @csrf

            <div>
                <label for="adminEmailInput" class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                    Authorized Administrator Email
                </label>
                <input id="adminEmailInput" type="email" name="email" value="{{ old('email') }}" required autofocus
                    placeholder="admin@zihubridge.com"
                    class="w-full bg-[#FAF8F5] border @error('email') border-rose-400 @else border-[#DDD9D0] @enderror rounded-lg px-3.5 py-2.5 text-sm text-[#121417] placeholder-[#8A8F99] focus:outline-none focus:border-[#121417] focus:bg-white transition" />
            </div>

            <button type="submit"
                class="w-full bg-[#121417] hover:bg-black text-white font-heading font-semibold py-2.5 rounded-lg text-xs md:text-sm transition cursor-pointer">
                Send One-Time Verification Code
            </button>
        </form>

        <div class="mt-6 pt-5 border-t border-[#E8E5DF] text-center text-xs text-[#8A8F99]">
            <p>Single-use OTP valid for 5 minutes. No passwords stored or permitted.</p>
        </div>
    </div>

</body>
</html>
