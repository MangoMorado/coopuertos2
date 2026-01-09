@php
    $theme = Auth::user()->theme ?? 'light';
    $isDark = $theme === 'dark';
    
    // Colores según el tema
    $bgCard = $isDark ? 'bg-gray-800' : 'bg-white';
    $textTitle = $isDark ? 'text-gray-100' : 'text-gray-800';
    $textSubtitle = $isDark ? 'text-gray-400' : 'text-gray-600';
    $textBody = $isDark ? 'text-gray-300' : 'text-gray-700';
    $textMuted = $isDark ? 'text-gray-500' : 'text-gray-600';
    $borderCard = $isDark ? 'border-gray-700' : 'border-gray-200';
    $bgSection = $isDark ? 'bg-gray-700' : 'bg-gray-50';
    
    use App\Helpers\StorageHelper;
    // Foto del conductor
    $fotoUrl = StorageHelper::getFotoUrl($conductor->foto);
    $fallbackAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($conductor->nombres . ' ' . $conductor->apellidos) . '&background=1e3a8a&color=fff';
    
    // Vehículo activo
    $vehiculo = $conductor->asignacionActiva && $conductor->asignacionActiva->vehicle ? $conductor->asignacionActiva->vehicle : null;
    $asignacion = $conductor->asignacionActiva;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl {{ $textTitle }} leading-tight">
            Información del Conductor
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto py-8 px-6">
        <!-- Botón de regreso -->
        <div class="mb-6">
            <a href="{{ route('conductores.index') }}"
               class="inline-flex items-center {{ $isDark ? 'text-gray-300 hover:text-white' : 'text-gray-600 hover:text-gray-900' }} transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Volver a Conductores
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Columna izquierda: Foto y QR -->
            <div class="lg:col-span-1">
                <!-- Foto del conductor -->
                <div class="{{ $bgCard }} rounded-lg shadow-md border {{ $borderCard }} p-6 mb-6">
                    <div class="text-center">
                        <img src="{{ $fotoUrl ?? $fallbackAvatar }}" 
                             alt="{{ $conductor->nombres }} {{ $conductor->apellidos }}"
                             class="w-48 h-48 rounded-full mx-auto object-cover border-4 {{ $borderCard }} mb-4">
                        <h3 class="text-xl font-bold {{ $textTitle }} mb-2">
                            {{ $conductor->nombres }} {{ $conductor->apellidos }}
                        </h3>
                        <p class="{{ $textSubtitle }} mb-4">Cédula: {{ $conductor->cedula }}</p>
                        
                        <!-- Estado -->
                        <span class="inline-block px-3 py-1 text-sm rounded-full mb-4
                            {{ $conductor->estado === 'activo' 
                                ? ($isDark ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800') 
                                : ($isDark ? 'bg-red-900 text-red-200' : 'bg-red-100 text-red-800') }}">
                            {{ ucfirst($conductor->estado) }}
                        </span>
                    </div>
                </div>

                <!-- Código QR -->
                <div class="{{ $bgCard }} rounded-lg shadow-md border {{ $borderCard }} p-6">
                    <h4 class="text-lg font-semibold {{ $textTitle }} mb-4 text-center">Código QR</h4>
                    <div class="flex justify-center">
                        {!! QrCode::size(150)->generate(route('conductor.public', $conductor->uuid)) !!}
                    </div>
                    <p class="text-xs {{ $textMuted }} text-center mt-4">
                        Escanea para ver información pública
                    </p>
                </div>
            </div>

            <!-- Columna derecha: Información detallada -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Información Personal -->
                <div class="{{ $bgCard }} rounded-lg shadow-md border {{ $borderCard }} p-6">
                    <h3 class="text-xl font-bold {{ $textTitle }} mb-6 pb-3 border-b {{ $borderCard }}">
                        Información Personal
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="{{ $textSubtitle }} text-sm mb-1">Nombres</p>
                            <p class="{{ $textBody }} font-medium">{{ $conductor->nombres }}</p>
                        </div>
                        <div>
                            <p class="{{ $textSubtitle }} text-sm mb-1">Apellidos</p>
                            <p class="{{ $textBody }} font-medium">{{ $conductor->apellidos }}</p>
                        </div>
                        <div>
                            <p class="{{ $textSubtitle }} text-sm mb-1">Cédula</p>
                            <p class="{{ $textBody }} font-medium">{{ $conductor->cedula }}</p>
                        </div>
                        <div>
                            <p class="{{ $textSubtitle }} text-sm mb-1">Fecha de Nacimiento</p>
                            <p class="{{ $textBody }} font-medium">
                                {{ $conductor->fecha_nacimiento ? $conductor->fecha_nacimiento->format('d/m/Y') : 'No registrada' }}
                            </p>
                        </div>
                        <div>
                            <p class="{{ $textSubtitle }} text-sm mb-1">Tipo de Sangre (RH)</p>
                            <p class="{{ $textBody }} font-medium">{{ $conductor->rh ?? 'No registrado' }}</p>
                        </div>
                        <div>
                            <p class="{{ $textSubtitle }} text-sm mb-1">Tipo de Conductor</p>
                            <p class="{{ $textBody }} font-medium">
                                {{ $conductor->conductor_tipo === 'A' ? 'Tipo A (Camionetas)' : 'Tipo B (Busetas)' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Información de Contacto -->
                <div class="{{ $bgCard }} rounded-lg shadow-md border {{ $borderCard }} p-6">
                    <h3 class="text-xl font-bold {{ $textTitle }} mb-6 pb-3 border-b {{ $borderCard }}">
                        Información de Contacto
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="{{ $textSubtitle }} text-sm mb-1">Celular</p>
                            <p class="{{ $textBody }} font-medium">{{ $conductor->celular ?? 'No registrado' }}</p>
                        </div>
                        <div>
                            <p class="{{ $textSubtitle }} text-sm mb-1">Correo Electrónico</p>
                            <p class="{{ $textBody }} font-medium">{{ $conductor->correo ?? 'No registrado' }}</p>
                        </div>
                        <div>
                            <p class="{{ $textSubtitle }} text-sm mb-1">Número Interno</p>
                            <p class="{{ $textBody }} font-medium">{{ $conductor->numero_interno ?? 'No registrado' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Información Académica y Profesional -->
                <div class="{{ $bgCard }} rounded-lg shadow-md border {{ $borderCard }} p-6">
                    <h3 class="text-xl font-bold {{ $textTitle }} mb-6 pb-3 border-b {{ $borderCard }}">
                        Información Académica y Profesional
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="{{ $textSubtitle }} text-sm mb-1">Nivel de Estudios</p>
                            <p class="{{ $textBody }} font-medium">{{ $conductor->nivel_estudios ?? 'No registrado' }}</p>
                        </div>
                        <div>
                            <p class="{{ $textSubtitle }} text-sm mb-1">Otra Profesión</p>
                            <p class="{{ $textBody }} font-medium">{{ $conductor->otra_profesion ?? 'No registrada' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Información del Vehículo -->
                @if($vehiculo)
                <div class="{{ $bgCard }} rounded-lg shadow-md border {{ $borderCard }} p-6">
                    <h3 class="text-xl font-bold {{ $textTitle }} mb-6 pb-3 border-b {{ $borderCard }}">
                        Vehículo Asignado
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="{{ $textSubtitle }} text-sm mb-1">Placa</p>
                            <p class="{{ $textBody }} font-medium">{{ $vehiculo->placa }}</p>
                        </div>
                        <div>
                            <p class="{{ $textSubtitle }} text-sm mb-1">Marca</p>
                            <p class="{{ $textBody }} font-medium">{{ $vehiculo->marca ?? 'No registrada' }}</p>
                        </div>
                        <div>
                            <p class="{{ $textSubtitle }} text-sm mb-1">Modelo</p>
                            <p class="{{ $textBody }} font-medium">{{ $vehiculo->modelo ?? 'No registrado' }}</p>
                        </div>
                        <div>
                            <p class="{{ $textSubtitle }} text-sm mb-1">Año de Fabricación</p>
                            <p class="{{ $textBody }} font-medium">{{ $vehiculo->anio_fabricacion ?? 'No registrado' }}</p>
                        </div>
                        @if($asignacion && $asignacion->fecha_asignacion)
                        <div>
                            <p class="{{ $textSubtitle }} text-sm mb-1">Fecha de Asignación</p>
                            <p class="{{ $textBody }} font-medium">
                                {{ $asignacion->fecha_asignacion ? \Carbon\Carbon::parse($asignacion->fecha_asignacion)->format('d/m/Y') : 'No registrada' }}
                            </p>
                        </div>
                        @endif
                        <div>
                            <p class="{{ $textSubtitle }} text-sm mb-1">Estado del Vehículo</p>
                            <p class="{{ $textBody }} font-medium">{{ ucfirst($vehiculo->estado ?? 'No registrado') }}</p>
                        </div>
                    </div>
                    @if($asignacion && $asignacion->observaciones)
                    <div class="mt-4 pt-4 border-t {{ $borderCard }}">
                        <p class="{{ $textSubtitle }} text-sm mb-1">Observaciones de Asignación</p>
                        <p class="{{ $textBody }}">{{ $asignacion->observaciones }}</p>
                    </div>
                    @endif
                </div>
                @else
                <div class="{{ $bgCard }} rounded-lg shadow-md border {{ $borderCard }} p-6">
                    <h3 class="text-xl font-bold {{ $textTitle }} mb-6 pb-3 border-b {{ $borderCard }}">
                        Vehículo Asignado
                    </h3>
                    <p class="{{ $textMuted }}">No tiene vehículo asignado actualmente.</p>
                </div>
                @endif

                <!-- Historial de Vehículos (si hay asignaciones anteriores) -->
                @if($conductor->asignaciones->count() > 0)
                <div class="{{ $bgCard }} rounded-lg shadow-md border {{ $borderCard }} p-6">
                    <h3 class="text-xl font-bold {{ $textTitle }} mb-6 pb-3 border-b {{ $borderCard }}">
                        Historial de Asignaciones
                    </h3>
                    <div class="space-y-4">
                        @foreach($conductor->asignaciones->sortByDesc('fecha_asignacion') as $asignacion)
                            <div class="{{ $bgSection }} rounded-lg p-4 border {{ $borderCard }}">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <p class="{{ $textBody }} font-medium">
                                            {{ $asignacion->vehicle ? $asignacion->vehicle->placa . ' - ' . ($asignacion->vehicle->marca ?? '') . ' ' . ($asignacion->vehicle->modelo ?? '') : 'Vehículo eliminado' }}
                                        </p>
                                        <p class="{{ $textSubtitle }} text-sm">
                                            Asignado: {{ $asignacion->fecha_asignacion ? \Carbon\Carbon::parse($asignacion->fecha_asignacion)->format('d/m/Y') : 'No registrada' }}
                                            @if($asignacion->fecha_desasignacion)
                                                | Desasignado: {{ $asignacion->fecha_desasignacion ? \Carbon\Carbon::parse($asignacion->fecha_desasignacion)->format('d/m/Y') : 'No registrada' }}
                                            @endif
                                        </p>
                                    </div>
                                    <span class="px-2 py-1 text-xs rounded-full
                                        {{ $asignacion->estado === 'activo' 
                                            ? ($isDark ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800') 
                                            : ($isDark ? 'bg-gray-700 text-gray-300' : 'bg-gray-200 text-gray-700') }}">
                                        {{ ucfirst($asignacion->estado) }}
                                    </span>
                                </div>
                                @if($asignacion->observaciones)
                                <p class="{{ $textMuted }} text-sm mt-2">{{ $asignacion->observaciones }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Información del Sistema -->
                <div class="{{ $bgCard }} rounded-lg shadow-md border {{ $borderCard }} p-6">
                    <h3 class="text-xl font-bold {{ $textTitle }} mb-6 pb-3 border-b {{ $borderCard }}">
                        Información del Sistema
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="{{ $textSubtitle }} text-sm mb-1">UUID</p>
                            <p class="{{ $textBody }} font-mono text-xs break-all">{{ $conductor->uuid }}</p>
                        </div>
                        <div>
                            <p class="{{ $textSubtitle }} text-sm mb-1">Fecha de Registro</p>
                            <p class="{{ $textBody }} font-medium">
                                {{ $conductor->created_at ? $conductor->created_at->format('d/m/Y H:i') : 'No registrada' }}
                            </p>
                        </div>
                        <div>
                            <p class="{{ $textSubtitle }} text-sm mb-1">Última Actualización</p>
                            <p class="{{ $textBody }} font-medium">
                                {{ $conductor->updated_at ? $conductor->updated_at->format('d/m/Y H:i') : 'No registrada' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('conductores.edit', $conductor) }}"
                       class="flex-1 md:flex-none bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded-lg shadow-md transition text-center">
                        Editar Conductor
                    </a>
                    <a href="{{ route('conductor.public', $conductor->uuid) }}" target="_blank"
                       class="flex-1 md:flex-none bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg shadow-md transition text-center">
                        Ver Vista Pública
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

