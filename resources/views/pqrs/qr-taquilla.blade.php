@php
    $theme = Auth::user()->theme ?? 'light';
    $isDark = $theme === 'dark';
    $bgCard = $isDark ? 'bg-gray-800' : 'bg-white';
    $textTitle = $isDark ? 'text-gray-100' : 'text-gray-800';
    $textMuted = $isDark ? 'text-gray-400' : 'text-gray-600';
    $borderCard = $isDark ? 'border-gray-700' : 'border-gray-200';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl {{ $textTitle }} leading-tight">
            Generar QR - Formulario PQRS Taquilla
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto py-8 px-6">
        <div class="{{ $bgCard }} shadow border {{ $borderCard }} rounded-lg p-6">
            <div class="text-center mb-6">
                <h3 class="text-xl font-bold {{ $textTitle }} mb-2">Código QR del Formulario</h3>
                <p class="{{ $textMuted }}">Escanea este código para acceder al formulario de PQRS Taquilla</p>
            </div>

            <div class="flex justify-center mb-6">
                <div class="p-4 bg-white rounded-lg shadow-lg">
                    {!! QrCode::size(300)->generate($url) !!}
                </div>
            </div>

            <div class="text-center space-y-4">
                <div>
                    <label class="block text-sm font-medium {{ $textTitle }} mb-2">URL del Formulario:</label>
                    <div class="flex items-center space-x-2">
                        <input type="text" value="{{ $url }}" readonly
                               class="flex-1 {{ $isDark ? 'bg-gray-700 text-gray-100' : 'bg-gray-50 text-gray-900' }} border {{ $borderCard }} rounded-md px-3 py-2 text-sm">
                        <button onclick="copyToClipboard('{{ $url }}')" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">
                            Copiar
                        </button>
                    </div>
                </div>

                <div class="flex justify-center space-x-4 pt-4">
                    <a href="{{ route('pqrs.index') }}"
                       class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg shadow-md transition">
                        Volver
                    </a>
                    <button onclick="window.print()"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow-md transition">
                        Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('URL copiada al portapapeles');
        });
    }
    </script>
</x-app-layout>
