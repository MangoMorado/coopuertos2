@php
    $fotoUrl = \App\Helpers\StorageHelper::getFotoUrl($conductor->foto);
    $fallbackAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($conductor->nombres . ' ' . $conductor->apellidos) . '&background=1e3a8a&color=fff';
    
    // Preparar datos del conductor para las variables
    $vehiculo = $conductor->asignacionActiva && $conductor->asignacionActiva->vehicle ? $conductor->asignacionActiva->vehicle : null;
    
    $datosConductor = [
        'nombres' => $conductor->nombres,
        'apellidos' => $conductor->apellidos,
        'nombre_completo' => $conductor->nombres . ' ' . $conductor->apellidos,
        'cedula' => $conductor->cedula,
        'conductor_tipo' => $conductor->conductor_tipo,
        'rh' => $conductor->rh,
        'numero_interno' => $conductor->numero_interno ?? '',
        'celular' => $conductor->celular ?? '',
        'correo' => $conductor->correo ?? '',
        'fecha_nacimiento' => $conductor->fecha_nacimiento ? $conductor->fecha_nacimiento->format('d/m/Y') : '',
        'nivel_estudios' => $conductor->nivel_estudios ?? '',
        'otra_profesion' => $conductor->otra_profesion ?? '',
        'estado' => ucfirst($conductor->estado),
        'foto' => $fotoUrl ?? $fallbackAvatar,
        'vehiculo' => $conductor->vehiculo ? (string) $conductor->vehiculo : 'Relevo',
        'vehiculo_placa' => $vehiculo ? $vehiculo->placa : 'Sin asignar',
        'vehiculo_marca' => $vehiculo ? $vehiculo->marca : '',
        'vehiculo_modelo' => $vehiculo ? $vehiculo->modelo : '',
        'qr_code' => route('conductor.public', $conductor->uuid),
    ];
@endphp

@if(auth()->check())
    <x-app-layout>
        @include('conductores.partials.carnet-content', ['conductor' => $conductor, 'template' => $template, 'previewImageUrl' => $previewImageUrl ?? null, 'datosConductor' => $datosConductor, 'fotoUrl' => $fotoUrl, 'fallbackAvatar' => $fallbackAvatar, 'vehiculo' => $vehiculo])
    </x-app-layout>
@else
    <x-public-layout>
        @include('conductores.partials.carnet-content', ['conductor' => $conductor, 'template' => $template, 'previewImageUrl' => $previewImageUrl ?? null, 'datosConductor' => $datosConductor, 'fotoUrl' => $fotoUrl, 'fallbackAvatar' => $fallbackAvatar, 'vehiculo' => $vehiculo])
    </x-public-layout>
@endif
