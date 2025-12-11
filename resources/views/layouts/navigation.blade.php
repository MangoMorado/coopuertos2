@php
    $theme = Auth::user()->theme ?? 'light';
    $isDark = $theme === 'dark';
    
    // Colores según el tema
    $bgSidebar = $isDark ? 'bg-gray-800' : 'bg-white';
    $borderColor = $isDark ? 'border-gray-700' : 'border-gray-200';
    $textPrimary = $isDark ? 'text-white' : 'text-gray-900';
    $textSecondary = $isDark ? 'text-gray-400' : 'text-gray-600';
    $textMuted = $isDark ? 'text-gray-300' : 'text-gray-700';
    $hoverBg = $isDark ? 'hover:bg-gray-700' : 'hover:bg-gray-100';
    $activeBg = $isDark ? 'bg-gray-700' : 'bg-gray-100';
    $dropdownBg = $isDark ? 'bg-gray-700' : 'bg-white';
    $dropdownBorder = $isDark ? 'border-gray-600' : 'border-gray-200';
    $avatarBg = $isDark ? 'bg-gray-600' : 'bg-gray-300';
    $avatarText = $isDark ? 'text-white' : 'text-gray-800';
    
    // Logo según el tema
    $logoPath = $isDark ? 'images/logo_white.svg' : 'images/logo.svg';
@endphp

<nav class="h-full min-h-screen {{ $bgSidebar }} border-r {{ $borderColor }} w-64 flex flex-col fixed left-0 top-0 z-10">

    <!-- Logo -->
    <div class="flex items-center justify-center h-16 border-b {{ $borderColor }}">
        <a href="{{ route('dashboard') }}" class="flex items-center">
            <img src="{{ asset($logoPath) }}" alt="Coopuertos" class="h-8 w-auto">
        </a>
    </div>

    <!-- LINKS DEL MENÚ -->
    <div class="flex-1 overflow-y-auto p-4 sidebar-scrollbar">
        <!-- Sección Plataforma -->
        <div class="mb-6">
            <div class="space-y-1">
                <x-sidebar-nav-link 
                    :href="route('dashboard')" 
                    :active="request()->routeIs('dashboard')"
                    :theme="$theme">
                    <x-slot name="icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"></path>
                        </svg>
                    </x-slot>
                    {{ __('Panel de control') }}
                </x-sidebar-nav-link>

                <x-sidebar-nav-link 
                    :href="route('conductores.index')" 
                    :active="request()->routeIs('conductores.*')"
                    :theme="$theme">
                    <x-slot name="icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"></path>
                        </svg>
                    </x-slot>
                    {{ __('Conductores') }}
                </x-sidebar-nav-link>

                <x-sidebar-nav-link 
                    :href="route('vehiculos.index')" 
                    :active="request()->routeIs('vehiculos.*')"
                    :theme="$theme">
                    <x-slot name="icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"></path>
                        </svg>
                    </x-slot>
                    {{ __('Vehículos') }}
                </x-sidebar-nav-link>

                <x-sidebar-nav-link 
                    :href="route('propietarios.index')" 
                    :active="request()->routeIs('propietarios.*')"
                    :theme="$theme">
                    <x-slot name="icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"></path>
                        </svg>
                    </x-slot>
                    {{ __('Propietarios') }}
                </x-sidebar-nav-link>

                <x-sidebar-nav-link 
                    :href="route('pqrs.index')" 
                    :active="request()->routeIs('pqrs.*')"
                    :theme="$theme">
                    <x-slot name="icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"></path>
                        </svg>
                    </x-slot>
                    {{ __('PQRS') }}
                </x-sidebar-nav-link>
            </div>
        </div>
    </div>

    <!-- PERFIL Y LOGOUT -->
    <div class="border-t {{ $borderColor }} p-4">
        <div x-data="{ open: false }" class="relative">
            <!-- Botón de usuario -->
            <button 
                @click="open = !open"
                class="w-full flex items-center gap-3 px-3 py-2 rounded-lg {{ $hoverBg }} transition-colors duration-200 text-left">
                <div class="w-8 h-8 {{ $avatarBg }} rounded-full flex items-center justify-center {{ $avatarText }} font-semibold text-sm">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="{{ $textPrimary }} text-sm font-medium truncate">
                        {{ Auth::user()->name }}
                    </div>
                </div>
                <svg class="w-4 h-4 {{ $textSecondary }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"></path>
                </svg>
            </button>

            <!-- Dropdown del usuario -->
            <div 
                x-show="open"
                @click.away="open = false"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute bottom-full left-0 right-0 mb-2 {{ $dropdownBg }} rounded-lg shadow-lg overflow-hidden border {{ $dropdownBorder }}"
                style="display: none;">
                
                <!-- Información del usuario -->
                <div class="p-4 border-b {{ $dropdownBorder }}">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 {{ $avatarBg }} rounded-full flex items-center justify-center {{ $avatarText }} font-semibold">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="{{ $textPrimary }} font-semibold truncate">
                                {{ Auth::user()->name }}
                            </div>
                            <div class="{{ $textSecondary }} text-xs truncate">
                                {{ Auth::user()->email }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Opciones -->
                <div class="py-2">
                    <a 
                        href="{{ route('profile.edit') }}"
                        class="flex items-center gap-3 px-4 py-2 {{ $textPrimary }} {{ $hoverBg }} transition-colors duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>{{ __('Configuración') }}</span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button 
                            type="submit"
                            class="w-full flex items-center gap-3 px-4 py-2 {{ $textPrimary }} {{ $hoverBg }} transition-colors duration-200 text-left">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"></path>
                            </svg>
                            <span>{{ __('Cerrar sesión') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</nav>
