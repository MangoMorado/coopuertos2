@php
    $theme = Auth::user()->theme ?? 'light';
    $isDark = $theme === 'dark';
    $bgCard = $isDark ? 'bg-gray-800' : 'bg-white';
    $textTitle = $isDark ? 'text-gray-100' : 'text-gray-800';
    $borderCard = $isDark ? 'border-gray-700' : 'border-gray-200';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl {{ $textTitle }} leading-tight">
            Nuevo Vehículo
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-6">
        <div class="{{ $bgCard }} shadow border {{ $borderCard }} rounded-lg p-6">
            <form method="POST" action="{{ route('vehiculos.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                @include('vehiculos.form', ['vehiculo' => null, 'theme' => $theme, 'isDark' => $isDark])

                <div class="flex justify-end space-x-3 pt-4">
                    <a href="{{ route('vehiculos.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md shadow-sm">
                        Cancelar
                    </a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md shadow-sm">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

