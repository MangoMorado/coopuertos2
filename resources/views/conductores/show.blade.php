<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ficha del Conductor
        </h2>
    </x-slot>

    <div class="min-h-screen bg-gray-100 flex items-center justify-center py-10 px-4">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-200">
            
            <!-- Cabecera con gradiente y foto -->
            @php
                $fotoUrl = null;
                if ($conductor->foto) {
                    if (\Illuminate\Support\Str::startsWith($conductor->foto, 'uploads/')) {
                        $fotoUrl = asset($conductor->foto);
                    } else {
                        $fotoUrl = asset('storage/' . $conductor->foto);
                    }
                }
                $fallbackAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($conductor->nombres . ' ' . $conductor->apellidos) . '&background=1e3a8a&color=fff';
            @endphp
            <div class="bg-gradient-to-r from-blue-600 to-blue-400 p-6 text-white text-center">
                <img src="{{ $fotoUrl ?? $fallbackAvatar }}"
                     alt="Foto del conductor"
                     class="w-28 h-28 mx-auto rounded-full border-4 border-white shadow-md mb-3">
                <h1 class="text-2xl font-semibold">{{ $conductor->nombres }} {{ $conductor->apellidos }}</h1>
            </div>

            <!-- Datos adicionales: Cédula, Estado y Placa -->
            <div class="p-6 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-500 text-sm">Cédula:</span>
                    <span class="font-medium text-gray-800">{{ $conductor->cedula }}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-gray-500 text-sm">Estado:</span>
                    <span class="font-medium text-gray-800">{{ ucfirst($conductor->estado) }}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-gray-500 text-sm">Placa Vehículo:</span>
                    <span class="font-medium text-gray-800">{{ $conductor->vehiculo_placa ?? '-' }}</span>
                </div>

                <!-- Botón regresar -->
                <div class="pt-5 text-center">
                    <a href="{{ route('conductores.index') }}" class="btn btn-gray inline-block">
                        Regresar
                    </a>
                </div>

                <!-- Footer -->
                <div class="pt-5 text-center">
                    <p class="text-gray-400 text-xs">© {{ date('Y') }} | Coopuertos | Ficha del conductor</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
