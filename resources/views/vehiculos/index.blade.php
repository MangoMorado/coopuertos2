@php
    $theme = Auth::user()->theme ?? 'light';
    $isDark = $theme === 'dark';
    $textTitle = $isDark ? 'text-gray-100' : 'text-gray-800';
    $bgCard = $isDark ? 'bg-gray-800' : 'bg-white';
    $bgInput = $isDark ? 'bg-gray-700 border-gray-600 text-gray-100' : 'bg-white border-gray-300 text-gray-900';
    $bgSuccess = $isDark ? 'bg-green-900 border-green-700 text-green-200' : 'bg-green-100 border-green-300 text-green-800';
    $bgHeader = $isDark ? 'bg-gray-700 text-gray-200' : 'bg-gray-100 text-gray-700';
    $textBody = $isDark ? 'text-gray-300' : 'text-gray-700';
    $borderRow = $isDark ? 'border-gray-700' : 'border-gray-200';
    $hoverRow = $isDark ? 'hover:bg-gray-700' : 'hover:bg-gray-50';
    $textEmpty = $isDark ? 'text-gray-400' : 'text-gray-500';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl {{ $textTitle }} leading-tight">
            Vehículos
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold {{ $textTitle }}">Vehículos</h2>
            <div class="flex space-x-2">
                <a href="{{ route('vehiculos.create') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-md transition">
                   + Nuevo Vehículo
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 {{ $bgSuccess }} border px-4 py-2 rounded">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-4">
            <form method="GET" action="{{ route('vehiculos.index') }}" class="flex space-x-2">
                <input type="text" name="search" placeholder="Buscar por placa, marca, modelo, tipo, propietario o conductor..." value="{{ request('search') }}"
                       class="{{ $bgInput }} border rounded px-3 py-2 flex-1 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400">
                @if(request('search'))
                    <a href="{{ route('vehiculos.index') }}"
                       class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg shadow-md transition">
                       Limpiar
                    </a>
                @endif
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-md transition" type="submit">Buscar</button>
            </form>
        </div>

        <div class="{{ $bgCard }} shadow-md rounded-lg overflow-hidden">
            <table class="w-full border-collapse text-sm">
                <thead class="{{ $bgHeader }} uppercase text-sm">
                    <tr>
                        <th class="text-left px-4 py-3">Placa</th>
                        <th class="text-left px-4 py-3">Tipo</th>
                        <th class="text-left px-4 py-3">Marca/Modelo</th>
                        <th class="text-left px-4 py-3">Año</th>
                        <th class="text-left px-4 py-3">Estado</th>
                        <th class="text-center px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    @forelse($vehicles as $v)
                        <tr class="border-t {{ $borderRow }} {{ $hoverRow }} transition">
                            <td class="px-4 py-3 {{ $textBody }}">{{ $v->placa }}</td>
                            <td class="px-4 py-3 {{ $textBody }}">{{ $v->tipo }}</td>
                            <td class="px-4 py-3 {{ $textBody }}">{{ $v->marca }} {{ $v->modelo }}</td>
                            <td class="px-4 py-3 {{ $textBody }}">{{ $v->anio_fabricacion }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full
                                    @if($v->estado === 'Activo')
                                        {{ $isDark ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800' }}
                                    @elseif($v->estado === 'En Mantenimiento')
                                        {{ $isDark ? 'bg-yellow-900 text-yellow-200' : 'bg-yellow-100 text-yellow-800' }}
                                    @else
                                        {{ $isDark ? 'bg-red-900 text-red-200' : 'bg-red-100 text-red-800' }}
                                    @endif">
                                    {{ $v->estado }}
                                </span>
                            </td>
                            <td class="text-center py-3">
                                <div class="flex justify-center space-x-2">
                                    <a href="{{ route('vehiculos.show', $v) }}"
                                       class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-md text-sm shadow-sm">
                                        Ver
                                    </a>
                                    <a href="{{ route('vehiculos.edit', $v) }}"
                                       class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-md text-sm shadow-sm">
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('vehiculos.destroy', $v) }}" onsubmit="return confirm('¿Eliminar este vehículo?')">
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
                            <td colspan="6" class="text-center py-6 {{ $textEmpty }}">No se encontraron vehículos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $vehicles->links() }}
        </div>
    </div>
</x-app-layout>

