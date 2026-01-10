<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Vehículo: {{ $vehiculo->placa }}
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-6">
        <div class="bg-white dark:bg-gray-800 shadow border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-4">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1 space-y-2">
                    <p class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-100">Tipo:</strong> {{ $vehiculo->tipo }}</p>
                    <p class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-100">Marca/Modelo:</strong> {{ $vehiculo->marca }} {{ $vehiculo->modelo }} ({{ $vehiculo->anio_fabricacion }})</p>
                    <p class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-100">Placa:</strong> {{ $vehiculo->placa }}</p>
                    <p class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-100">Chasis VIN:</strong> {{ $vehiculo->chasis_vin ?? '-' }}</p>
                    <p class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-100">Cap. Pasajeros:</strong> {{ $vehiculo->capacidad_pasajeros ?? '-' }}</p>
                    <p class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-100">Cap. Carga (kg):</strong> {{ $vehiculo->capacidad_carga_kg ?? '-' }}</p>
                    <p class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-100">Combustible:</strong> {{ ucfirst($vehiculo->combustible) }}</p>
                    <p class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-100">Última Revisión:</strong> {{ $vehiculo->ultima_revision_tecnica ?? '-' }}</p>
                    <p class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-100">Estado:</strong> {{ $vehiculo->estado }}</p>
                </div>
                <div class="flex-1 space-y-2">
                    <p class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-100">Propietario:</strong> 
                        {{ $vehiculo->propietario_nombre ?? '-' }}
                    </p>
                    <p class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-100">Conductor:</strong> 
                        @if($vehiculo->conductor)
                            <a href="{{ route('conductores.info', $vehiculo->conductor) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                {{ $vehiculo->conductor->nombres }} {{ $vehiculo->conductor->apellidos }}
                            </a>
                        @else
                            <span class="text-gray-500 dark:text-gray-400">Sin asignar</span>
                        @endif
                    </p>
                    @if($vehiculo->foto)
                        @php
                            use App\Helpers\StorageHelper;
                            $fotoUrl = StorageHelper::getFotoUrl($vehiculo->foto);
                        @endphp
                        <div class="mt-3">
                            <img src="{{ $fotoUrl }}" alt="Foto vehículo" class="w-48 h-48 object-cover rounded border border-gray-200 dark:border-gray-700">
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('vehiculos.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md shadow-sm transition">Regresar</a>
                <a href="{{ route('vehiculos.edit', $vehiculo) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md shadow-sm transition">Editar</a>
            </div>
        </div>
    </div>
</x-app-layout>
