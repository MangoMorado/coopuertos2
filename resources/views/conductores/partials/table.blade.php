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
            <th class="text-left px-4 py-3">Nombre Completo</th>
            <th class="text-left px-4 py-3">Vehículo Asignado</th>
            <th class="text-center px-4 py-3">Acciones</th>
        </tr>
    </thead>
    <tbody class="text-sm">
        @forelse($conductores as $c)
            @php
                $vehiculo = $c->asignacionActiva && $c->asignacionActiva->vehicle ? $c->asignacionActiva->vehicle : null;
            @endphp
            <tr class="border-t {{ $borderRow }} {{ $hoverRow }} transition">
                <td class="px-4 py-3 {{ $textBody }}">{{ $c->cedula }}</td>
                <td class="px-4 py-3 {{ $textBody }}">{{ $c->nombres }} {{ $c->apellidos }}</td>
                <td class="px-4 py-3 {{ $textBody }}">
                    @if($vehiculo)
                        {{ $vehiculo->placa }} - {{ $vehiculo->marca ?? '' }} {{ $vehiculo->modelo ?? '' }}
                    @else
                        <span class="{{ $textEmpty }}">Sin asignar</span>
                    @endif
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
                <td colspan="4" class="text-center py-6 {{ $textEmpty }}">No se encontraron conductores.</td>
            </tr>
        @endforelse
    </tbody>
</table>

