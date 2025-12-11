@php
    $isDark = $isDark ?? false;
    $bgHeader = $isDark ? 'bg-gray-700' : 'bg-gray-100';
    $textHeader = $isDark ? 'text-gray-200' : 'text-gray-700';
    $textBody = $isDark ? 'text-gray-300' : 'text-gray-700';
    $borderRow = $isDark ? 'border-gray-700' : 'border-gray-200';
    $hoverRow = $isDark ? 'hover:bg-gray-700' : 'hover:bg-gray-50';
    $textEmpty = $isDark ? 'text-gray-400' : 'text-gray-500';
@endphp

<table class="w-full border-collapse text-sm">
    <thead class="{{ $bgHeader }} {{ $textHeader }} uppercase text-sm">
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
            <tr class="border-t {{ $borderRow }} {{ $hoverRow }} transition">
                <td class="px-4 py-3 {{ $textBody }}">{{ $c->cedula }}</td>
                <td class="px-4 py-3 {{ $textBody }}">{{ $c->nombres }}</td>
                <td class="px-4 py-3 {{ $textBody }}">{{ $c->apellidos }}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 text-xs rounded-full 
                        {{ $c->estado === 'activo' ? ($isDark ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800') : ($isDark ? 'bg-red-900 text-red-200' : 'bg-red-100 text-red-800') }}">
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
                <td colspan="6" class="text-center py-6 {{ $textEmpty }}">No se encontraron conductores.</td>
            </tr>
        @endforelse
    </tbody>
</table>

