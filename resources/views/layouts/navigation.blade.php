<div 
    x-data="{ 
        isMobile: window.innerWidth < 768,
        init() {
            // Cargar estado desde localStorage (solo desktop)
            if (!this.isMobile) {
                const saved = localStorage.getItem('sidebar-collapsed');
                if (saved !== null) {
                    $store.sidebar.collapsed = saved === 'true';
                }
            }
            
            // Detectar cambios de tamaño
            window.addEventListener('resize', () => {
                this.isMobile = window.innerWidth < 768;
            });
        },
        toggleCollapse() {
            if (!this.isMobile) {
                $store.sidebar.collapsed = !$store.sidebar.collapsed;
                localStorage.setItem('sidebar-collapsed', $store.sidebar.collapsed.toString());
            }
        }
    }"
    class="relative"
>
    <!-- Sidebar -->
    <nav 
        class="fixed left-0 top-0 z-40 h-screen bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col transition-all duration-300 ease-in-out translate-x-0"
        :class="{
            'w-16': isMobile || (!isMobile && $store.sidebar.collapsed),
            'w-64': !isMobile && !$store.sidebar.collapsed
        }"
    >
        <!-- Header con logo/botón -->
        <div class="flex items-center justify-between h-16 border-b border-gray-200 dark:border-gray-700"
             :class="isMobile || (!isMobile && $store.sidebar.collapsed) ? 'px-2' : 'px-3'">
            <div class="flex items-center justify-center flex-1">
                <!-- Logo cuando está expandido (desktop) o en móvil -->
                <a 
                    href="{{ route('dashboard') }}" 
                    class="flex items-center justify-center"
                    x-show="isMobile || !$store.sidebar.collapsed"
                >
                    @if(file_exists(public_path('images/logo.svg')))
                        <img 
                            src="{{ asset('images/logo.svg') }}" 
                            alt="Coopuertos" 
                            class="w-auto h-8 dark:hidden"
                            loading="lazy"
                        >
                        <img 
                            src="{{ asset('images/logo_white.svg') }}" 
                            alt="Coopuertos" 
                            class="w-auto h-8 hidden dark:block"
                            loading="lazy"
                        >
                    @else
                        <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded"></div>
                    @endif
                </a>
                
                <!-- Logo pequeño + botón expandir cuando está colapsado (desktop) -->
                <button
                    @click="toggleCollapse()"
                    x-show="!isMobile && $store.sidebar.collapsed"
                    class="flex items-center justify-center w-full h-full hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
                    title="Expandir menú"
                    aria-label="Expandir menú"
                    style="display: none;"
                >
                    @if(file_exists(public_path('images/logo.svg')))
                        <img 
                            src="{{ asset('images/logo.svg') }}" 
                            alt="Coopuertos" 
                            class="w-6 h-6 dark:hidden"
                            loading="lazy"
                        >
                        <img 
                            src="{{ asset('images/logo_white.svg') }}" 
                            alt="Coopuertos" 
                            class="w-6 h-6 hidden dark:block"
                            loading="lazy"
                        >
                    @else
                        <div class="w-6 h-6 bg-gray-300 dark:bg-gray-600 rounded"></div>
                    @endif
                </button>
            </div>
            
            <!-- Botón colapsar (solo desktop expandido) -->
            <button
                x-show="!isMobile && !$store.sidebar.collapsed"
                @click="toggleCollapse()"
                class="w-8 h-8 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white flex items-center justify-center flex-shrink-0"
                title="Colapsar menú"
                aria-label="Colapsar menú"
                style="display: none;"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 4.5l7.5 7.5-7.5 7.5m-6-15l7.5 7.5-7.5 7.5"></path>
                </svg>
            </button>
            
        </div>

        <!-- Menú de navegación -->
        <div 
            class="flex-1 overflow-y-auto sidebar-scrollbar"
            :class="{
                'p-2': isMobile || (!isMobile && $store.sidebar.collapsed),
                'p-4': !isMobile && !$store.sidebar.collapsed
            }"
        >
            <div class="space-y-1">
                {{-- Dashboard --}}
                @can('ver dashboard')
                <x-sidebar-nav-link 
                    :href="route('dashboard')" 
                    :active="request()->routeIs('dashboard')"
                    :title="__('Panel de control')">
                    <x-slot name="icon">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"></path>
                        </svg>
                    </x-slot>
                    {{ __('Panel de control') }}
                </x-sidebar-nav-link>
                @endcan

                {{-- Conductores --}}
                @can('ver conductores')
                <x-sidebar-nav-link 
                    :href="route('conductores.index')" 
                    :active="request()->routeIs('conductores.*')"
                    :title="__('Conductores')">
                    <x-slot name="icon">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"></path>
                        </svg>
                    </x-slot>
                    {{ __('Conductores') }}
                </x-sidebar-nav-link>
                @endcan

                {{-- Vehículos --}}
                @can('ver vehiculos')
                <x-sidebar-nav-link 
                    :href="route('vehiculos.index')" 
                    :active="request()->routeIs('vehiculos.*')"
                    :title="__('Vehículos')">
                    <x-slot name="icon">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"></path>
                        </svg>
                    </x-slot>
                    {{ __('Vehículos') }}
                </x-sidebar-nav-link>
                @endcan

                {{-- Propietarios --}}
                @can('ver propietarios')
                <x-sidebar-nav-link 
                    :href="route('propietarios.index')" 
                    :active="request()->routeIs('propietarios.*')"
                    :title="__('Propietarios')">
                    <x-slot name="icon">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"></path>
                        </svg>
                    </x-slot>
                    {{ __('Propietarios') }}
                </x-sidebar-nav-link>
                @endcan

                {{-- Carnets --}}
                @can('ver carnets')
                <x-sidebar-nav-link 
                    :href="route('carnets.index')" 
                    :active="request()->routeIs('carnets.*')"
                    :title="__('Carnets')">
                    <x-slot name="icon">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"></path>
                        </svg>
                    </x-slot>
                    {{ __('Carnets') }}
                </x-sidebar-nav-link>
                @endcan

                {{-- Usuarios --}}
                @can('ver usuarios')
                <x-sidebar-nav-link 
                    :href="route('usuarios.index')" 
                    :active="request()->routeIs('usuarios.*')"
                    :title="__('Usuarios')">
                    <x-slot name="icon">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"></path>
                        </svg>
                    </x-slot>
                    {{ __('Usuarios') }}
                </x-sidebar-nav-link>
                @endcan

                {{-- API (Documentación) - Solo Mango --}}
                @if(auth()->user()->hasRole('Mango'))
                <x-sidebar-nav-link 
                    :href="url('/api/documentation')" 
                    :active="false"
                    :title="__('API')">
                    <x-slot name="icon">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"></path>
                        </svg>
                    </x-slot>
                    {{ __('API') }}
                </x-sidebar-nav-link>
                @endif

                {{-- Configuración (solo Mango) --}}
                @can('gestionar configuracion')
                <x-sidebar-nav-link 
                    :href="route('configuracion.index')" 
                    :active="request()->routeIs('configuracion.*')"
                    :title="__('Configuración')">
                    <x-slot name="icon">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </x-slot>
                    {{ __('Configuración') }}
                </x-sidebar-nav-link>
                @endcan
            </div>
        </div>

        <!-- Perfil y logout -->
        <div 
            class="border-t border-gray-200 dark:border-gray-700"
            :class="{
                'p-2': isMobile || (!isMobile && $store.sidebar.collapsed),
                'p-4': !isMobile && !$store.sidebar.collapsed
            }"
        >
            <div x-data="{ open: false }" class="relative">
                <!-- Botón usuario expandido (desktop) o siempre visible (móvil siempre colapsado) -->
                <button 
                    @click="open = !open"
                    x-show="isMobile || !$store.sidebar.collapsed"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-left"
                    :class="{
                        'justify-center': isMobile || (!isMobile && $store.sidebar.collapsed)
                    }"
                >
                    <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center text-gray-800 dark:text-white font-semibold text-sm flex-shrink-0">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div 
                        class="flex-1 min-w-0"
                        x-show="!isMobile && !$store.sidebar.collapsed"
                        style="display: none;"
                    >
                        <div class="text-gray-900 dark:text-white text-sm font-medium truncate">
                            {{ Auth::user()->name }}
                        </div>
                    </div>
                    <svg 
                        class="w-4 h-4 text-gray-600 dark:text-gray-400 flex-shrink-0"
                        x-show="!isMobile && !$store.sidebar.collapsed"
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24" 
                        stroke-width="1.5"
                        style="display: none;"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"></path>
                    </svg>
                </button>
                
                <!-- Botón usuario colapsado (solo desktop) -->
                <button 
                    @click="open = !open"
                    x-show="!isMobile && $store.sidebar.collapsed"
                    class="w-full flex items-center justify-center px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    style="display: none;"
                >
                    <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center text-gray-800 dark:text-white font-semibold text-sm">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                </button>

                <!-- Dropdown usuario -->
                <div 
                    x-show="open"
                    @click.away="open = false"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute bottom-full mb-2 bg-white dark:bg-gray-700 rounded-lg shadow-lg overflow-hidden border border-gray-200 dark:border-gray-600 z-50"
                    :class="(isMobile || (!isMobile && $store.sidebar.collapsed)) ? 'left-full ml-2' : 'left-0 right-0'"
                    style="display: none;"
                >
                    <div class="p-4 border-b border-gray-200 dark:border-gray-600">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center text-gray-800 dark:text-white font-semibold">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-gray-900 dark:text-white font-semibold truncate">
                                    {{ Auth::user()->name }}
                                </div>
                                <div class="text-gray-600 dark:text-gray-400 text-xs truncate">
                                    {{ Auth::user()->email }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="py-2">
                        <a 
                            href="{{ route('profile.edit') }}"
                            class="flex items-center gap-3 px-4 py-2 text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
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
                                class="w-full flex items-center gap-3 px-4 py-2 text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors text-left">
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
</div>
