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
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                    </x-slot>
                    {{ __('Panel de control') }}
                </x-sidebar-nav-link>

                <x-sidebar-nav-link 
                    :href="route('conductores.index')" 
                    :active="request()->routeIs('conductores.*')"
                    :theme="$theme">
                    <x-slot name="icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </x-slot>
                    {{ __('Conductores') }}
                </x-sidebar-nav-link>

                <x-sidebar-nav-link 
                    :href="route('vehiculos.index')" 
                    :active="request()->routeIs('vehiculos.*')"
                    :theme="$theme">
                    <x-slot name="icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13l2-5h14l2 5m-4 0v5a1 1 0 01-1 1h-8a1 1 0 01-1-1v-5m0 0h10"></path>
                        </svg>
                    </x-slot>
                    {{ __('Vehículos') }}
                </x-sidebar-nav-link>

                <x-sidebar-nav-link 
                    :href="route('propietarios.index')" 
                    :active="request()->routeIs('propietarios.*')"
                    :theme="$theme">
                    <x-slot name="icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </x-slot>
                    {{ __('Propietarios') }}
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
                <svg class="w-4 h-4 {{ $textSecondary }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
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
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>{{ __('Configuración') }}</span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button 
                            type="submit"
                            class="w-full flex items-center gap-3 px-4 py-2 {{ $textPrimary }} {{ $hoverBg }} transition-colors duration-200 text-left">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            <span>{{ __('Cerrar sesión') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</nav>
