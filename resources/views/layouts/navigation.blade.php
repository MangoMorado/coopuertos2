<nav x-data="{ open: false }" class="h-full bg-white border-r shadow-sm w-64 flex flex-col">

    <!-- Logo -->
    <div class="flex items-center justify-center h-16 border-b">
        <a href="{{ route('dashboard') }}">
            <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
        </a>
    </div>

    <!-- LINKS DEL MENÚ -->
    <div class="flex-1 overflow-y-auto p-4 space-y-1">

        <x-nav-link :href="route('dashboard')" 
                    :active="request()->routeIs('dashboard')" 
                    class="block w-full">
            {{ __('Panel de control') }}
        </x-nav-link>

        <x-nav-link :href="route('conductores.index')" 
                    :active="request()->routeIs('conductores.*')" 
                    class="block w-full">
            {{ __('Conductores') }}
        </x-nav-link>

    </div>

    <!-- PERFIL Y LOGOUT -->
    <div class="border-t p-4">
        <div class="text-sm text-gray-700 font-semibold">
            {{ Auth::user()->name }}
        </div>
        <div class="text-xs text-gray-500 mb-3">
            {{ Auth::user()->email }}
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="w-full text-left px-3 py-2 bg-red-50 text-red-600 rounded hover:bg-red-100">
                {{ __('Cerrar sesión') }}
            </button>
        </form>
    </div>

</nav>
