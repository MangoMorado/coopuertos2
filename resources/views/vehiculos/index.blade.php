<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Vehículos
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Vehículos</h2>
            <div class="flex space-x-2">
                <a href="{{ route('vehiculos.create') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-md transition">
                   + Nuevo Vehículo
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 bg-green-100 dark:bg-green-900 border border-green-300 dark:border-green-700 text-green-800 dark:text-green-200 px-4 py-2 rounded">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-4">
            <form method="GET" action="{{ route('vehiculos.index') }}" class="flex space-x-2">
                <input type="text" name="search" placeholder="Buscar por placa, marca, modelo, tipo, propietario o conductor..." value="{{ request('search') }}"
                       class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded px-3 py-2 flex-1 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400 dark:placeholder-gray-500">
                @if(request('search'))
                    <a href="{{ route('vehiculos.index') }}"
                       class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg shadow-md transition">
                       Limpiar
                    </a>
                @endif
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-md transition" type="submit">Buscar</button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
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
        </div>

        <div class="mt-6">
            {{ $vehiculos->links() }}
        </div>
    </div>
</x-app-layout>
