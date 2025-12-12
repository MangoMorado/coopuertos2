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
            PQRS - Peticiones, Quejas, Reclamos y Sugerencias
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold {{ $textTitle }}">PQRS - Calificación del Servicio</h2>
            <div class="flex space-x-2 flex-wrap gap-2">
                <a href="{{ route('pqrs.form.public') }}" target="_blank"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-md transition flex items-center space-x-2">
                   <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                       <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                   </svg>
                   <span>Formulario Servicio</span>
                </a>
                <a href="{{ route('pqrs.edit-template') }}"
                   class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg shadow-md transition flex items-center space-x-2">
                   <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                       <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                   </svg>
                   <span>Editar Template Servicio</span>
                </a>
                <a href="{{ route('pqrs.qr') }}"
                   class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow-md transition flex items-center space-x-2">
                   <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5ZM6.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM8.625 17.25a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm2.25-3a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM8.625 20.25a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm2.25-3a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm2.25 3a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm2.25-3a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM14.25 17.25a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm2.25-3a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM14.25 20.25a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm2.25-3a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM17.625 17.25a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm2.25-3a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM17.625 20.25a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm2.25-3a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                   </svg>
                   <span>Generar QR</span>
                </a>
                <a href="{{ route('pqrs.create') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-md transition flex items-center space-x-2">
                   <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                   </svg>
                   <span>Nuevo PQRS</span>
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 {{ $bgSuccess }} border px-4 py-2 rounded">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-4">
            <form method="GET" action="{{ route('pqrs.index') }}" class="flex space-x-2">
                <input type="text" name="search" placeholder="Buscar por nombre, correo, tiquete, placa, tipo, estado, usuario..." value="{{ request('search') }}"
                       class="{{ $bgInput }} border rounded px-3 py-2 flex-1 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400">
                @if(request('search'))
                    <a href="{{ route('pqrs.index') }}"
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
                        <th class="text-left px-4 py-3">Fecha</th>
                        <th class="text-left px-4 py-3">Nombre</th>
                        <th class="text-left px-4 py-3">Vehículo</th>
                        <th class="text-left px-4 py-3">Tipo</th>
                        <th class="text-left px-4 py-3">Estado</th>
                        <th class="text-left px-4 py-3">Usuario Asignado</th>
                        <th class="text-left px-4 py-3">Calificación</th>
                        <th class="text-center px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    @forelse($pqrs as $pqr)
                        <tr class="border-t {{ $borderRow }} {{ $hoverRow }} transition">
                            <td class="px-4 py-3 {{ $textBody }}">{{ $pqr->fecha->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 {{ $textBody }}">{{ $pqr->nombre }}</td>
                            <td class="px-4 py-3 {{ $textBody }}">{{ $pqr->vehiculo_placa ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full {{ $isDark ? 'bg-blue-900 text-blue-200' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $pqr->tipo }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full
                                    @if($pqr->estado === 'Radicada')
                                        {{ $isDark ? 'bg-gray-700 text-gray-200' : 'bg-gray-100 text-gray-800' }}
                                    @elseif($pqr->estado === 'En Trámite')
                                        {{ $isDark ? 'bg-blue-900 text-blue-200' : 'bg-blue-100 text-blue-800' }}
                                    @elseif($pqr->estado === 'En Espera de Información')
                                        {{ $isDark ? 'bg-yellow-900 text-yellow-200' : 'bg-yellow-100 text-yellow-800' }}
                                    @elseif($pqr->estado === 'Resuelta')
                                        {{ $isDark ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800' }}
                                    @else
                                        {{ $isDark ? 'bg-red-900 text-red-200' : 'bg-red-100 text-red-800' }}
                                    @endif">
                                    {{ $pqr->estado ?? 'Radicada' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 {{ $textBody }}">
                                @if($pqr->usuarioAsignado)
                                    {{ $pqr->usuarioAsignado->name }}
                                    <span class="text-xs {{ $isDark ? 'text-gray-400' : 'text-gray-500' }}">
                                        ({{ $pqr->usuarioAsignado->email }})
                                    </span>
                                @else
                                    <span class="{{ $isDark ? 'text-gray-500' : 'text-gray-400' }}">Sin asignar</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($pqr->calificacion)
                                    <div class="flex items-center">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg class="w-4 h-4 {{ $i <= $pqr->calificacion ? 'text-yellow-400 fill-current' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                            </svg>
                                        @endfor
                                        <span class="ml-1 text-xs {{ $textBody }}">({{ $pqr->calificacion }})</span>
                                    </div>
                                @else
                                    <span class="{{ $textEmpty }}">-</span>
                                @endif
                            </td>
                            <td class="text-center py-3">
                                <div class="flex justify-center space-x-2">
                                    <a href="{{ route('pqrs.show', $pqr) }}"
                                       class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-md text-sm shadow-sm">
                                        Ver
                                    </a>
                                    <a href="{{ route('pqrs.edit', $pqr) }}"
                                       class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-md text-sm shadow-sm">
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('pqrs.destroy', $pqr) }}" onsubmit="return confirm('¿Eliminar este PQRS?')">
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
                            <td colspan="8" class="text-center py-6 {{ $textEmpty }}">No se encontraron PQRS.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $pqrs->links() }}
        </div>

        <!-- Sección PQRS Taquilla -->
        <div class="mt-12">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold {{ $textTitle }}">PQRS - Taquilla</h2>
                <div class="flex space-x-2 flex-wrap gap-2">
                    <a href="{{ route('pqrs.form.taquilla') }}" target="_blank"
                       class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg shadow-md transition flex items-center space-x-2">
                       <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                           <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                           <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                       </svg>
                       <span>Formulario Taquilla</span>
                    </a>
                    <a href="{{ route('pqrs.edit-template-taquilla') }}"
                       class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg shadow-md transition flex items-center space-x-2">
                       <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                           <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                       </svg>
                       <span>Editar</span>
                    </a>
                    <a href="{{ route('pqrs.taquilla.qr') }}"
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow-md transition flex items-center space-x-2">
                       <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                           <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5ZM6.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM8.625 17.25a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm2.25-3a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM8.625 20.25a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm2.25-3a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm2.25 3a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm2.25-3a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM14.25 17.25a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm2.25-3a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM14.25 20.25a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm2.25-3a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM17.625 17.25a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm2.25-3a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM17.625 20.25a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm2.25-3a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                       </svg>
                       <span>Generar QR Taquilla</span>
                    </a>
                    <a href="{{ route('pqrs-taquilla.create') }}"
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-md transition flex items-center space-x-2">
                       <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                           <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                       </svg>
                       <span>Nuevo PQRS</span>
                    </a>
                </div>
            </div>

            <div class="mb-4">
                <form method="GET" action="{{ route('pqrs.index') }}" class="flex space-x-2">
                    <input type="text" name="search_taquilla" placeholder="Buscar por nombre, correo, teléfono, sede, tipo, estado, usuario..." value="{{ request('search_taquilla') }}"
                           class="{{ $bgInput }} border rounded px-3 py-2 flex-1 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400">
                    @if(request('search_taquilla'))
                        <a href="{{ route('pqrs.index') }}"
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
                            <th class="text-left px-4 py-3">Fecha</th>
                            <th class="text-left px-4 py-3">Hora</th>
                            <th class="text-left px-4 py-3">Nombre</th>
                            <th class="text-left px-4 py-3">Sede</th>
                            <th class="text-left px-4 py-3">Tipo</th>
                            <th class="text-left px-4 py-3">Estado</th>
                            <th class="text-left px-4 py-3">Usuario Asignado</th>
                            <th class="text-left px-4 py-3">Calificación</th>
                            <th class="text-center px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        @forelse($pqrsTaquilla ?? [] as $pqr)
                            <tr class="border-t {{ $borderRow }} {{ $hoverRow }} transition">
                                <td class="px-4 py-3 {{ $textBody }}">{{ $pqr->fecha->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 {{ $textBody }}">{{ \Carbon\Carbon::parse($pqr->hora)->format('H:i') }}</td>
                                <td class="px-4 py-3 {{ $textBody }}">{{ $pqr->nombre }}</td>
                                <td class="px-4 py-3 {{ $textBody }}">{{ $pqr->sede ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $isDark ? 'bg-blue-900 text-blue-200' : 'bg-blue-100 text-blue-800' }}">
                                        {{ $pqr->tipo }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full
                                        @if($pqr->estado === 'Radicada')
                                            {{ $isDark ? 'bg-gray-700 text-gray-200' : 'bg-gray-100 text-gray-800' }}
                                        @elseif($pqr->estado === 'En Trámite')
                                            {{ $isDark ? 'bg-blue-900 text-blue-200' : 'bg-blue-100 text-blue-800' }}
                                        @elseif($pqr->estado === 'En Espera de Información')
                                            {{ $isDark ? 'bg-yellow-900 text-yellow-200' : 'bg-yellow-100 text-yellow-800' }}
                                        @elseif($pqr->estado === 'Resuelta')
                                            {{ $isDark ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800' }}
                                        @else
                                            {{ $isDark ? 'bg-red-900 text-red-200' : 'bg-red-100 text-red-800' }}
                                        @endif">
                                        {{ $pqr->estado ?? 'Radicada' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 {{ $textBody }}">
                                    @if($pqr->usuarioAsignado)
                                        {{ $pqr->usuarioAsignado->name }}
                                        <span class="text-xs {{ $isDark ? 'text-gray-400' : 'text-gray-500' }}">
                                            ({{ $pqr->usuarioAsignado->email }})
                                        </span>
                                    @else
                                        <span class="{{ $isDark ? 'text-gray-500' : 'text-gray-400' }}">Sin asignar</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($pqr->calificacion)
                                        <div class="flex items-center">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg class="w-4 h-4 {{ $i <= $pqr->calificacion ? 'text-yellow-400 fill-current' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                </svg>
                                            @endfor
                                            <span class="ml-1 text-xs {{ $textBody }}">({{ $pqr->calificacion }})</span>
                                        </div>
                                    @else
                                        <span class="{{ $textEmpty }}">-</span>
                                    @endif
                                </td>
                                <td class="text-center py-3">
                                    <div class="flex justify-center space-x-2">
                                        <a href="{{ route('pqrs-taquilla.show', $pqr) }}"
                                           class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-md text-sm shadow-sm">
                                            Ver
                                        </a>
                                        <a href="{{ route('pqrs-taquilla.edit', $pqr) }}"
                                           class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-md text-sm shadow-sm">
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('pqrs-taquilla.destroy', $pqr) }}" onsubmit="return confirm('¿Eliminar este PQRS de Taquilla?')" class="inline">
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
                                <td colspan="9" class="text-center py-6 {{ $textEmpty }}">No se encontraron PQRS de Taquilla.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ ($pqrsTaquilla ?? collect())->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
