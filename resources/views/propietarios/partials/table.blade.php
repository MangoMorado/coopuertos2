<table class="w-full border-collapse text-sm">
    <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 uppercase text-sm">
        <tr>
            <th class="text-left px-4 py-3">Identificación</th>
            <th class="text-left px-4 py-3">Nombre</th>
            <th class="text-left px-4 py-3">Teléfono</th>
            <th class="text-left px-4 py-3">Correo</th>
            <th class="text-center px-4 py-3">Acciones</th>
        </tr>
    </thead>
    <tbody class="text-sm">
        @forelse($propietarios as $propietario)
            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $propietario->numero_identificacion }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $propietario->nombre_completo }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $propietario->telefono_contacto ?? 'N/A' }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $propietario->correo_electronico ?? 'N/A' }}</td>
                <td class="text-center py-3">
                    <div class="flex justify-center space-x-2">
                        <a href="{{ route('propietarios.show', $propietario) }}"
                           class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded-md text-sm shadow-sm">
                            Ver
                        </a>
                        <a href="{{ route('propietarios.edit', $propietario) }}"
                           class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-md text-sm shadow-sm">
                            Editar
                        </a>
                        <form method="POST" action="{{ route('propietarios.destroy', $propietario) }}" onsubmit="return confirm('¿Eliminar este propietario?')" class="inline">
                            @csrf @method('DELETE')
                            <button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md text-sm shadow-sm">
                                Eliminar
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center py-6 text-gray-500 dark:text-gray-400">No se encontraron propietarios.</td>
            </tr>
        @endforelse
    </tbody>
</table>
