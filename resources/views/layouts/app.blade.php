@php
    $theme = Auth::user()->theme ?? 'light';
    $isDark = $theme === 'dark';
    
    // Colores según el tema
    $bgBody = $isDark ? 'bg-gray-900' : 'bg-gray-100';
    $textBody = $isDark ? 'text-gray-100' : 'text-gray-900';
    $bgHeader = $isDark ? 'bg-gray-800' : 'bg-white';
    $bgFooter = $isDark ? 'bg-gray-800' : 'bg-white';
    $textFooter = $isDark ? 'text-gray-400' : 'text-gray-500';
    $borderFooter = $isDark ? 'border-gray-700' : 'border-gray-200';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased {{ $bgBody }} {{ $textBody }}">

    <!-- Contenedor principal -->
    <div class="min-h-screen">
        <!-- =========================
              SIDEBAR (Navigation)
        ========================== -->
        @include('layouts.navigation')

        <!-- =========================
              CONTENIDO PRINCIPAL
        ========================== -->
        <div class="flex-1 flex flex-col ml-64 min-h-screen">

            <!-- HEADER (opcional según vista) -->
            @isset($header)
                <header class="{{ $bgHeader }} shadow border-b {{ $isDark ? 'border-gray-700' : 'border-gray-200' }}">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- CONTENIDO -->
            <main class="flex-1 py-6 px-4 sm:px-6 lg:px-8">
                <div class="max-w-7xl mx-auto">
                    {{ $slot }}
                </div>
            </main>

            <!-- FOOTER -->
            <footer class="{{ $bgFooter }} border-t {{ $borderFooter }} mt-auto">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 text-center {{ $textFooter }} text-sm">
                    © {{ date('Y') }} {{ config('app.name', 'Coopuertos') }}.
                </div>
            </footer>

        </div>

    </div>

    @stack('scripts')
</body>
</html>
