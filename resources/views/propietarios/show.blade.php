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
            Propietario: {{ $propietario->nombre_completo }}
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-6">
        <div class="{{ $bgCard }} shadow border {{ $borderCard }} rounded-lg p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Tipo de Identificación:</strong> {{ $propietario->tipo_identificacion }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Número de Identificación:</strong> {{ $propietario->numero_identificacion }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Nombre Completo / Razón Social:</strong> {{ $propietario->nombre_completo }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Tipo de Propietario:</strong> {{ $propietario->tipo_propietario }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Estado:</strong> 
                        <span class="px-2 py-1 text-xs rounded-full
                            {{ $propietario->estado === 'Activo' ? ($isDark ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800') : ($isDark ? 'bg-red-900 text-red-200' : 'bg-red-100 text-red-800') }}">
                            {{ $propietario->estado }}
                        </span>
                    </p>
                </div>
                <div class="space-y-3">
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Dirección de Contacto:</strong> {{ $propietario->direccion_contacto ?? '-' }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Teléfono de Contacto:</strong> {{ $propietario->telefono_contacto ?? '-' }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Correo Electrónico:</strong> {{ $propietario->correo_electronico ?? '-' }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Fecha de Registro:</strong> {{ $propietario->created_at?->format('d/m/Y H:i') ?? 'N/A' }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Última Actualización:</strong> {{ $propietario->updated_at?->format('d/m/Y H:i') ?? 'N/A' }}</p>
                </div>
            </div>

            <div class="flex gap-3 pt-4 border-t {{ $borderCard }}">
                <a href="{{ route('propietarios.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md shadow-sm">Regresar</a>
                <a href="{{ route('propietarios.edit', $propietario) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md shadow-sm">Editar</a>
            </div>
        </div>
    </div>
</x-app-layout>
