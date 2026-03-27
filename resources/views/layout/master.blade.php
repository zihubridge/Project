<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZihuBridge</title>

    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">

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

<body class="font-sans absolute top-0 right-0 left-0 bottom-0">

    @include('layout.header')
    @yield('content')
    @include('layout.footer')
    @stack('scripts')
</body>

</html>
