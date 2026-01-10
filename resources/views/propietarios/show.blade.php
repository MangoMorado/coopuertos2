<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Propietario: {{ $propietario->nombre_completo }}
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-6">
        <div class="bg-white dark:bg-gray-800 shadow border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <p class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-100">Tipo de Identificación:</strong> {{ $propietario->tipo_identificacion }}</p>
                    <p class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-100">Número de Identificación:</strong> {{ $propietario->numero_identificacion }}</p>
                    <p class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-100">Nombre Completo / Razón Social:</strong> {{ $propietario->nombre_completo }}</p>
                    <p class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-100">Tipo de Propietario:</strong> {{ $propietario->tipo_propietario }}</p>
                    <p class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-100">Estado:</strong> 
                        <span class="px-2 py-1 text-xs rounded-full
                            {{ $propietario->estado === 'Activo' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200' }}">
                            {{ $propietario->estado }}
                        </span>
                    </p>
                </div>
                <div class="space-y-3">
                    <p class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-100">Dirección de Contacto:</strong> {{ $propietario->direccion_contacto ?? '-' }}</p>
                    <p class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-100">Teléfono de Contacto:</strong> {{ $propietario->telefono_contacto ?? '-' }}</p>
                    <p class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-100">Correo Electrónico:</strong> {{ $propietario->correo_electronico ?? '-' }}</p>
                    <p class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-100">Fecha de Registro:</strong> {{ $propietario->created_at?->format('d/m/Y H:i') ?? 'N/A' }}</p>
                    <p class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-100">Última Actualización:</strong> {{ $propietario->updated_at?->format('d/m/Y H:i') ?? 'N/A' }}</p>
                </div>
            </div>

            <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('propietarios.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md shadow-sm transition">Regresar</a>
                <a href="{{ route('propietarios.edit', $propietario) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md shadow-sm transition">Editar</a>
            </div>
        </div>
    </div>
</x-app-layout>
