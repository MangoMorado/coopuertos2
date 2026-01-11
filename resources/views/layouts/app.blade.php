@php
    $theme = Auth::check() ? (Auth::user()->theme ?? 'light') : 'light';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $theme === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Script inline mínimo para tema -->
    <script>
        (function() {
            const serverTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            const savedTheme = localStorage.getItem('theme');
            const currentTheme = serverTheme || savedTheme || 'light';
            
            if (currentTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            
            window.__currentTheme = currentTheme;
        })();
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-gray-100 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        @include('layouts.navigation')

        <!-- Contenido principal -->
        <div 
            x-data="{
                isMobile: window.innerWidth < 768,
                sidebarWidth: 0,
                init() {
                    this.updateWidth();
                    window.addEventListener('resize', () => {
                        this.isMobile = window.innerWidth < 768;
                        this.updateWidth();
                    });
                    
                    // Escuchar cambios del store de Alpine
                    this.$watch('$store.sidebar.collapsed', () => {
                        if (!this.isMobile) {
                            this.updateWidth();
                        }
                    });
                },
                updateWidth() {
                    if (this.isMobile) {
                        // Móvil: sidebar siempre colapsado (64px)
                        this.sidebarWidth = 64;
                    } else {
                        // Desktop: ancho según estado colapsado
                        this.sidebarWidth = $store.sidebar.collapsed ? 64 : 256;
                    }
                }
            }"
            class="flex-1 flex flex-col min-h-screen transition-all duration-300 ease-in-out"
            :style="{ marginLeft: sidebarWidth + 'px' }"
        >
            <!-- Header (opcional) -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow border-b border-gray-200 dark:border-gray-700">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Contenido -->
            <main class="flex-1 py-4 sm:py-6 px-4 sm:px-6 lg:px-8">
                <div class="max-w-7xl mx-auto w-full">
                    {{ $slot }}
                </div>
            </main>

            {{-- Contenedor de Toasts --}}
            <x-toast-container />

            {{-- Mensajes de sesión ocultos para integración con toasts --}}
            @if(session('success'))
                <div data-session-success="{{ session('success') }}" class="hidden"></div>
            @endif
            @if(session('error'))
                <div data-session-error="{{ session('error') }}" class="hidden"></div>
            @endif
            @if(session('warning'))
                <div data-session-warning="{{ session('warning') }}" class="hidden"></div>
            @endif
            @if(session('info'))
                <div data-session-info="{{ session('info') }}" class="hidden"></div>
            @endif

            <!-- Footer -->
            <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-auto">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 text-center text-gray-500 dark:text-gray-400 text-sm">
                    © {{ date('Y') }} {{ config('app.name', 'Coopuertos') }}.
                </div>
            </footer>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
