<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZihuBridge</title>

    {{-- Load Tailwind & JS using Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <link rel="stylesheet" href="{{ asset('assets/style/newStyle.css') }}">

</head>

<body class="font-sans absolute top-0 right-0 left-0 bottom-0">

    @include('layout.header')
    @yield('content')
    @include('layout.footer')

</body>

</html>