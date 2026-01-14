<table class="w-full border-collapse text-sm">
    <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 uppercase text-sm">
        <tr>
            <th class="text-left px-4 py-3">Cédula</th>
            <th class="text-left px-4 py-3">Nombre Completo</th>
            <th class="text-left px-4 py-3">Vehiculo</th>
            <th class="text-center px-4 py-3">Estado</th>
            <th class="text-center px-4 py-3">Acciones</th>
        </tr>
    </thead>
    <tbody class="text-sm">
        @forelse($conductores as $c)
            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $c->cedula }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $c->nombres }} {{ $c->apellidos }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                    @if($c->vehiculo)
                        {{ $c->vehiculo }}
                    @else
                        <span class="text-gray-500 dark:text-gray-400">Sin asignar</span>
                    @endif
                </td>
                <td class="text-center py-3">
                    <span class="inline-block px-3 py-1 text-xs rounded-full
                        {{ $c->estado === 'activo' 
                            ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' 
                            : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200' }}">
                        {{ ucfirst($c->estado) }}
                    </span>
                </td>
                <td class="text-center py-3">
                    <div class="flex justify-center space-x-2">
                        <a href="{{ route('conductores.info', $c) }}"
                           class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded-md text-sm shadow-sm">
                            Info
                        </a>
                        <a href="{{ route('conductor.public', $c->uuid) }}"
                           class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-md text-sm shadow-sm">
                            Carnet
                        </a>
                        <a href="{{ route('conductores.edit', $c) }}"
                           class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-md text-sm shadow-sm">
                            Editar
                        </a>
                        <form method="POST" action="{{ route('conductores.destroy', $c) }}" onsubmit="return confirm('¿Eliminar este conductor?')">
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
                <td colspan="5" class="text-center py-6 text-gray-500 dark:text-gray-400">No se encontraron conductores.</td>
            </tr>
        @endforelse
    </tbody>
</table>

