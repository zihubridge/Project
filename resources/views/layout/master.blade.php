<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZihuBridge — Non-Custodial Cross-Chain Swap Infrastructure</title>

    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">

    <!-- Editorial Fintech Typography: Manrope (Headings), IBM Plex Sans (Body), IBM Plex Mono (Data) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;1,400&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Load Tailwind & JS using Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <link rel="stylesheet" href="{{ asset('assets/style/newStyle.css') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-DEK0DQGH5K"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-DEK0DQGH5K');
    </script>

</head>

<body class="min-h-screen flex flex-col bg-[#FBF9F5] text-[#121417] antialiased selection:bg-[#121417] selection:text-white">

    @include('layout.header')
    <div class="flex-1">
        @yield('content')
    </div>
    @include('layout.footer')
    @stack('scripts')
</body>

</html>
