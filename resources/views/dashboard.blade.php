@php
    $theme = Auth::user()->theme ?? 'light';
    $isDark = $theme === 'dark';
    
    // Colores según el tema
    $bgCard = $isDark ? 'bg-gray-800' : 'bg-white';
    $textTitle = $isDark ? 'text-gray-100' : 'text-gray-800';
    $textSubtitle = $isDark ? 'text-gray-400' : 'text-gray-600';
    $textNumber = $isDark ? 'text-white' : 'text-gray-800';
    $borderCard = $isDark ? 'border-gray-700' : 'border-gray-200';
@endphp

<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- BIENVENIDA --}}
            <h1 class="text-2xl font-bold {{ $textTitle }} mb-6">
                Bienvenido, {{ Auth::user()->name }}
            </h1>

            {{-- TARJETAS DEL DASHBOARD --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                {{-- TARJETA DE CONDUCTORES --}}
                <div class="{{ $bgCard }} p-6 shadow rounded-lg border {{ $borderCard }}">
                    <h2 class="{{ $textSubtitle }} text-sm font-semibold mb-2">
                        Conductores registrados
                    </h2>

                    <p class="text-3xl font-bold {{ $textNumber }}">
                        {{ $conductoresCount }}
                    </p>
                </div>

                {{-- Puedes agregar más tarjetas aquí --}}

            </div>
        </div>
    </div>
</x-app-layout>
