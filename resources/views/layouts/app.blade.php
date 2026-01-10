@php
    // Obtener tema del usuario autenticado o usar 'light' por defecto
    $theme = Auth::check() ? (Auth::user()->theme ?? 'light') : 'light';
    
    // Leer estado del sidebar desde cookie (si está disponible)
    $sidebarCollapsed = isset($_COOKIE['sidebar-collapsed']) 
        ? $_COOKIE['sidebar-collapsed'] === 'true' 
        : null;
    
    // Determinar ancho inicial del contenido basado en cookie
    $initialSidebarCollapsed = $sidebarCollapsed ?? false;
    $initialContentMargin = $initialSidebarCollapsed ? 'ml-16' : 'ml-64';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $theme === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Script inline para inicializar tema y sidebar ANTES del render y evitar flash -->
    <script>
        (function() {
            // Función helper para establecer cookie
            function setCookie(name, value, days) {
                const expires = new Date();
                expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
                document.cookie = name + '=' + value + ';expires=' + expires.toUTCString() + ';path=/;SameSite=Lax';
            }
            
            // Función helper para obtener cookie
            function getCookie(name) {
                const nameEQ = name + '=';
                const ca = document.cookie.split(';');
                for (let i = 0; i < ca.length; i++) {
                    let c = ca[i];
                    while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                    if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
                }
                return null;
            }
            
            // Inicializar tema: priorizar tema del servidor, luego localStorage, por defecto 'light'
            const serverTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            const savedTheme = localStorage.getItem('theme');
            
            // Si el servidor tiene un tema definido, usarlo; si no, usar localStorage; por defecto 'light'
            let currentTheme = serverTheme || savedTheme || 'light';
            
            // Aplicar tema inmediatamente al elemento html
            if (currentTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            
            // Guardar tema actual en window para uso global
            window.__currentTheme = currentTheme;
            
            // Leer estado desde localStorage para sidebar
            let sidebarCollapsed = false;
            const savedState = localStorage.getItem('sidebar-collapsed');
            const isMobile = window.innerWidth < 768;
            
            // Si no es móvil y hay estado guardado, usarlo
            if (!isMobile && savedState !== null) {
                sidebarCollapsed = savedState === 'true';
            }
            
            // Sincronizar con cookie (para disponibilidad en servidor)
            const cookieState = getCookie('sidebar-collapsed');
            if (cookieState !== null && !isMobile) {
                sidebarCollapsed = cookieState === 'true';
                if (savedState !== sidebarCollapsed.toString()) {
                    localStorage.setItem('sidebar-collapsed', sidebarCollapsed.toString());
                }
            } else if (savedState !== null && !isMobile) {
                setCookie('sidebar-collapsed', savedState, 365);
            } else if (!isMobile) {
                setCookie('sidebar-collapsed', 'false', 365);
            }
            
            // Guardar estado inicial en window para que Alpine.js lo use
            window.__sidebarInitialState = {
                collapsed: sidebarCollapsed && !isMobile,
                mobileOpen: false
            };
        })();
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-gray-100 text-gray-900 dark:bg-gray-900 dark:text-gray-100">

    <!-- Contenedor principal -->
    <div class="min-h-screen">
        <!-- =========================
              SIDEBAR (Navigation)
        ========================== -->
        @include('layouts.navigation')

        <!-- =========================
              CONTENIDO PRINCIPAL
        ========================== -->
        <div 
            data-main-content
            x-data="{
                isMobile: window.innerWidth < 768,
                init() {
                    // Usar estado inicial desde el script inline si está disponible
                    if (window.__sidebarInitialState) {
                        if (window.innerWidth >= 768) {
                            $store.sidebar.collapsed = window.__sidebarInitialState.collapsed;
                        }
                    }
                    
                    // Actualizar estado cuando cambia el tamaño de ventana
                    const updateMobile = () => {
                        this.isMobile = window.innerWidth < 768;
                    };
                    window.addEventListener('resize', updateMobile);
                },
                get marginClass() {
                    // En móvil (<768px), el sidebar es overlay y no ocupa espacio
                    if (this.isMobile) {
                        return 'ml-0';
                    }
                    // En desktop (≥768px), ajustar según estado colapsado del sidebar
                    if ($store.sidebar.collapsed) {
                        return 'ml-16'; // 64px cuando sidebar está colapsado (w-16)
                    }
                    return 'ml-64'; // 256px cuando sidebar está expandido (w-64)
                }
            }"
            class="flex-1 flex flex-col min-h-screen transition-all duration-300 ease-in-out {{ $initialContentMargin }}"
            :class="marginClass"
        >

            <!-- HEADER (opcional según vista) -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow border-b border-gray-200 dark:border-gray-700">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- CONTENIDO -->
            <main class="flex-1 py-6 px-4 sm:px-6 lg:px-8" :class="{ 'pt-20': isMobile }">
                <div class="max-w-7xl mx-auto">
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

            <!-- FOOTER -->
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
