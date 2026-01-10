<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- BIENVENIDA --}}
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">
                Bienvenido, {{ Auth::user()->name }}
            </h1>

            {{-- TARJETAS DEL DASHBOARD --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                {{-- TARJETA DE CONDUCTORES --}}
                <div class="bg-white dark:bg-gray-800 p-6 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                    <h2 class="text-gray-600 dark:text-gray-400 text-sm font-semibold mb-2">
                        Conductores registrados
                    </h2>

                    <p class="text-3xl font-bold text-gray-800 dark:text-white">
                        {{ $conductoresCount }}
                    </p>
                </div>

                {{-- Puedes agregar más tarjetas aquí --}}

            </div>
        </div>
    </div>
</x-app-layout>
