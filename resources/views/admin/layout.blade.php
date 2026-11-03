<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Console') | ZihuBridge Operations</title>

    <!-- Typography: Manrope & IBM Plex Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@300;400;500;600&family=Manrope:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .font-heading { font-family: 'Manrope', -apple-system, sans-serif; }
        .font-sans { font-family: 'IBM Plex Sans', -apple-system, sans-serif; }
        .font-mono { font-family: 'IBM Plex Mono', monospace; }
    </style>
</head>
<body class="bg-[#FBF9F5] text-[#121417] font-sans antialiased min-h-screen flex flex-col selection:bg-[#121417] selection:text-white">

    <div class="flex-1 flex flex-col md:flex-row">
        <!-- Sidebar Navigation -->
        <aside class="w-full md:w-64 bg-[#10131A] text-slate-300 border-r border-[#1E2433] flex flex-col shrink-0">
            <!-- Brand -->
            <div class="h-16 flex items-center justify-between px-5 border-b border-[#1E2433]">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="ZihuBridge" class="h-6 w-auto" />
                    <span class="text-xs font-heading font-semibold text-white tracking-wider uppercase">Ops Console</span>
                </a>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-3 py-6 space-y-1 text-xs font-heading font-medium">
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('admin.dashboard') ? 'bg-white text-[#10131A] font-semibold' : 'text-slate-300 hover:text-white hover:bg-[#161B26]' }}">
                    <ion-icon name="grid-outline" class="text-base"></ion-icon>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('admin.swaps.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('admin.swaps.*') ? 'bg-white text-[#10131A] font-semibold' : 'text-slate-300 hover:text-white hover:bg-[#161B26]' }}">
                    <ion-icon name="swap-horizontal-outline" class="text-base"></ion-icon>
                    <span>Swap Management</span>
                </a>

                <a href="{{ route('admin.revenue.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('admin.revenue.*') ? 'bg-white text-[#10131A] font-semibold' : 'text-slate-300 hover:text-white hover:bg-[#161B26]' }}">
                    <ion-icon name="cash-outline" class="text-base"></ion-icon>
                    <span>Revenue Accounting</span>
                </a>

                <a href="{{ route('admin.fees.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('admin.fees.*') ? 'bg-white text-[#10131A] font-semibold' : 'text-slate-300 hover:text-white hover:bg-[#161B26]' }}">
                    <ion-icon name="options-outline" class="text-base"></ion-icon>
                    <span>Platform Fees</span>
                </a>

                <div class="pt-4 pb-1 px-3 text-[11px] font-mono uppercase tracking-wider text-slate-500">
                    Asset Catalog
                </div>

                <a href="{{ route('admin.blockchains.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('admin.blockchains.*') ? 'bg-white text-[#10131A] font-semibold' : 'text-slate-300 hover:text-white hover:bg-[#161B26]' }}">
                    <ion-icon name="git-network-outline" class="text-base"></ion-icon>
                    <span>Blockchains</span>
                </a>

                <a href="{{ route('admin.tokens.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('admin.tokens.*') ? 'bg-white text-[#10131A] font-semibold' : 'text-slate-300 hover:text-white hover:bg-[#161B26]' }}">
                    <ion-icon name="cube-outline" class="text-base"></ion-icon>
                    <span>Token Registry</span>
                </a>

                <a href="{{ route('admin.listings.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('admin.listings.*') ? 'bg-white text-[#10131A] font-semibold' : 'text-slate-300 hover:text-white hover:bg-[#161B26]' }}">
                    <ion-icon name="list-outline" class="text-base"></ion-icon>
                    <span>Listing Requests</span>
                </a>

                <div class="pt-4 pb-1 px-3 text-[11px] font-mono uppercase tracking-wider text-slate-500">
                    Governance
                </div>

                <a href="{{ route('admin.settings.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('admin.settings.*') ? 'bg-white text-[#10131A] font-semibold' : 'text-slate-300 hover:text-white hover:bg-[#161B26]' }}">
                    <ion-icon name="shield-checkmark-outline" class="text-base"></ion-icon>
                    <span>Admins & Audit</span>
                </a>
            </nav>

            <!-- Bottom Live Portal Link -->
            <div class="p-4 border-t border-[#1E2433]">
                <a href="{{ route('home') }}" target="_blank"
                    class="flex items-center justify-between text-xs text-slate-400 hover:text-white p-2 rounded-lg bg-[#161B26] transition">
                    <span>Public Swap Terminal</span>
                    <ion-icon name="open-outline"></ion-icon>
                </a>
            </div>
        </aside>

        <!-- Main Body Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Topbar -->
            <header class="h-16 bg-white border-b border-[#E8E5DF] flex items-center justify-between px-6 shrink-0">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-heading font-semibold uppercase tracking-wider text-[#6B7280]">
                        @yield('header-tag', 'Control Center')
                    </span>
                    <span class="text-slate-300">/</span>
                    <h1 class="text-sm font-heading font-semibold text-[#121417]">@yield('header-title', 'Overview')</h1>
                </div>

                <div class="flex items-center gap-4">
                    @if(isset($currentAdmin))
                    <div class="flex items-center gap-2.5">
                        <div class="text-right">
                            <span class="block text-xs font-heading font-semibold text-[#121417] leading-none">{{ $currentAdmin->name }}</span>
                            <span class="text-[11px] font-mono text-[#6B7280]">{{ $currentAdmin->role }}</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.logout') }}" class="inline">
                        @csrf
                        <button type="submit"
                            class="text-xs font-heading font-medium px-3 py-1.5 rounded-md bg-[#FAF8F5] hover:bg-[#121417] hover:text-white border border-[#DDD9D0] text-[#52565E] transition cursor-pointer">
                            Log Out
                        </button>
                    </form>
                    @endif
                </div>
            </header>

            <!-- Notifications / Alerts -->
            <div class="px-6 pt-4">
                @if (session('success'))
                    <div class="p-3.5 rounded-lg bg-emerald-50 border border-emerald-200 text-xs font-heading font-medium text-emerald-800 mb-2">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="p-3.5 rounded-lg bg-rose-50 border border-rose-200 text-xs font-heading font-medium text-rose-800 mb-2">
                        {{ session('error') }}
                    </div>
                @endif
            </div>

            <!-- Page Content -->
            <main class="flex-1 p-6 md:p-8">
                @yield('content')
            </main>
        </div>
    </div>

</body>
</html>
