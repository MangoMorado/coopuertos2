<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Listado de Conductores
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto py-8 px-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Conductores</h2>
            <div class="flex space-x-2">
                <a href="{{ route('conductores.create') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-md transition">
                   + Nuevo Conductor
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 bg-green-100 border border-green-300 text-green-800 px-4 py-2 rounded">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="w-full border-collapse text-sm">
                <thead class="bg-gray-100 text-gray-700 uppercase text-sm">
                    <tr>
                        <th class="text-left px-4 py-3">Cédula</th>
                        <th class="text-left px-4 py-3">Nombres</th>
                        <th class="text-left px-4 py-3">Apellidos</th>
                        <th class="text-left px-4 py-3">Estado</th>
                        <th class="text-center px-4 py-3">QR</th>
                        <th class="text-center px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    @forelse($conductores as $c)
                        <tr class="border-t hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-gray-700">{{ $c->cedula }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $c->nombres }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $c->apellidos }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    {{ $c->estado === 'activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ucfirst($c->estado) }}
                                </span>
                            </td>
                            <td class="text-center py-3">
                                {!! QrCode::size(70)->generate(route('conductor.public', $c->uuid)) !!}
                            </td>
                            <td class="text-center py-3">
                                <div class="flex justify-center space-x-2">
                                    <a href="{{ route('conductor.public', $c->uuid) }}"
                                       class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-md text-sm shadow-sm">
                                        Ver
                                    </a>
                                    <a href="{{ route('conductores.edit', $c) }}"
                                       class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-md text-sm shadow-sm">
                                        Editar
                                    </a>
                                    <a href="{{ route('conductores.carnet', $c) }}"
                                       class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-sm shadow-sm">
                                       Generar Carnet
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
                            <td colspan="6" class="text-center py-6 text-gray-500">No hay conductores registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $conductores->links() }}
        </div>
    </div>
</x-app-layout>
