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
            Vehículo: {{ $vehiculo->placa }}
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-6">
        <div class="{{ $bgCard }} shadow border {{ $borderCard }} rounded-lg p-6 space-y-4">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1 space-y-2">
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Tipo:</strong> {{ $vehiculo->tipo }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Marca/Modelo:</strong> {{ $vehiculo->marca }} {{ $vehiculo->modelo }} ({{ $vehiculo->anio_fabricacion }})</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Placa:</strong> {{ $vehiculo->placa }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Chasis VIN:</strong> {{ $vehiculo->chasis_vin ?? '-' }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Cap. Pasajeros:</strong> {{ $vehiculo->capacidad_pasajeros ?? '-' }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Cap. Carga (kg):</strong> {{ $vehiculo->capacidad_carga_kg ?? '-' }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Combustible:</strong> {{ ucfirst($vehiculo->combustible) }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Última Revisión:</strong> {{ $vehiculo->ultima_revision_tecnica ?? '-' }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Estado:</strong> {{ $vehiculo->estado }}</p>
                </div>
                <div class="flex-1 space-y-2">
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Propietario:</strong> {{ $vehiculo->propietario_nombre }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Conductor ID:</strong> {{ $vehiculo->conductor_id ?? '-' }}</p>
                    @if($vehiculo->foto)
                        @php
                            use App\Helpers\StorageHelper;
                            $fotoUrl = StorageHelper::getFotoUrl($vehiculo->foto);
                        @endphp
                        <div class="mt-3">
                            <img src="{{ $fotoUrl }}" alt="Foto vehículo" class="w-48 h-48 object-cover rounded border {{ $isDark ? 'border-gray-700' : 'border-gray-200' }}">
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex gap-3 pt-4">
                <a href="{{ route('vehiculos.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md shadow-sm">Regresar</a>
                <a href="{{ route('vehiculos.edit', $vehiculo) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md shadow-sm">Editar</a>
            </div>
        </div>
    </div>
</x-app-layout>

