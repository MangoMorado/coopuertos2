<table class="w-full border-collapse text-sm">
    <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 uppercase text-sm">
        <tr>
            <th class="text-left px-4 py-3">Placa</th>
            <th class="text-left px-4 py-3">Marca</th>
            <th class="text-left px-4 py-3">Modelo</th>
            <th class="text-left px-4 py-3">Tipo</th>
            <th class="text-left px-4 py-3">Propietario</th>
            <th class="text-center px-4 py-3">Acciones</th>
        </tr>
    </thead>
    <tbody class="text-sm">
        @forelse($vehiculos as $vehiculo)
            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $vehiculo->placa }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $vehiculo->marca }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $vehiculo->modelo }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $vehiculo->tipo }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                    @if($vehiculo->propietario_nombre)
                        {{ $vehiculo->propietario_nombre }}
                    @else
                        <span class="text-gray-500 dark:text-gray-400">Sin propietario</span>
                    @endif
                </td>
                <td class="text-center py-3">
                    <div class="flex justify-center space-x-2">
                        <a href="{{ route('vehiculos.show', $vehiculo) }}"
                           class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded-md text-sm shadow-sm">
                            Ver
                        </a>
                        <a href="{{ route('vehiculos.edit', $vehiculo) }}"
                           class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-md text-sm shadow-sm">
                            Editar
                        </a>
                        <form method="POST" action="{{ route('vehiculos.destroy', $vehiculo) }}" onsubmit="return confirm('¿Eliminar este vehículo?')" class="inline">
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
                <td colspan="6" class="text-center py-6 text-gray-500 dark:text-gray-400">No se encontraron vehículos.</td>
            </tr>
        @endforelse
    </tbody>
</table>
