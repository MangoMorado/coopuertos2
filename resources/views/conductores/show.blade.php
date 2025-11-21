<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ficha del Conductor
        </h2>
    </x-slot>

    <div class="min-h-screen bg-gray-100 flex items-center justify-center py-10 px-4">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-200">
            
            <!-- Cabecera con gradiente y foto -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-400 p-6 text-white text-center">
                <img src="{{ $conductor->foto ? asset('storage/' . $conductor->foto) : 'https://ui-avatars.com/api/?name=' . urlencode($conductor->nombre) . '&background=1e3a8a&color=fff' }}"
                     alt="Foto del conductor"
                     class="w-28 h-28 mx-auto rounded-full border-4 border-white shadow-md mb-3">
                <h1 class="text-2xl font-semibold">{{ $conductor->nombre }}</h1>
                <p class="text-sm text-blue-100">{{ $conductor->licencia }}</p>
            </div>

            <!-- Datos del conductor -->
            <div class="p-6 space-y-3">

                @foreach([
                    'Documento' => $conductor->documento,
                    'Teléfono' => $conductor->telefono,
                    'Email' => $conductor->email,
                    'Placa Vehículo' => $conductor->placa,
                    'Empresa' => $conductor->empresa,
                    'Vencimiento Licencia' => $conductor->vencimiento_licencia,
                    'Estado' => $conductor->estado,
                ] as $label => $value)
                    @if($value)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 text-sm">{{ $label }}:</span>
                            <span class="font-medium text-gray-800">
                                {{ $label === 'Estado' ? ucfirst($value) : $value }}
                            </span>
                        </div>
                    @endif
                @endforeach

                <!-- QR -->
                <div class="pt-5 text-center">
                    <p class="text-gray-500 text-sm mb-2">Verificación QR</p>
                    <div class="inline-block p-2 bg-gray-50 rounded-lg shadow">
                        {!! QrCode::size(150)->generate(url('/conductor/' . $conductor->uuid)) !!}
                    </div>
                </div>

                <!-- Botón regresar -->
                <div class="pt-5 text-center">
                    <a href="{{ route('conductores.index') }}" class="btn btn-gray inline-block">
                        Regresar
                    </a>
                </div>

                <!-- Footer -->
                <div class="pt-5 text-center">
                    <p class="text-gray-400 text-xs">© {{ date('Y') }} {{ config('app.name', 'Laravel') }} | Ficha del conductor</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
