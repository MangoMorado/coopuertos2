<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- BIENVENIDA --}}
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">
                Hola {{ Auth::user()->name }}
            </h1>

            {{-- TARJETAS DEL DASHBOARD --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                {{-- TARJETA DE CONDUCTORES --}}
                <a href="{{ route('conductores.index') }}" 
                   class="bg-white dark:bg-gray-800 p-6 shadow rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow cursor-pointer block">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-gray-600 dark:text-gray-400 text-sm font-semibold">
                            Conductores registrados
                        </h2>
                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"></path>
                        </svg>
                    </div>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white mb-2">
                        {{ $conductoresCount }}
                    </p>
                    @if(!empty($conductoresPorTipoFormateado))
                        <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                            @foreach($conductoresPorTipoFormateado as $tipo => $total)
                                <span class="text-xs px-2 py-1 rounded-full bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200">
                                    {{ $tipo }}: {{ $total }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </a>

                {{-- TARJETA DE VEHÍCULOS --}}
                <a href="{{ route('vehiculos.index') }}" 
                   class="bg-white dark:bg-gray-800 p-6 shadow rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow cursor-pointer block">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-gray-600 dark:text-gray-400 text-sm font-semibold">
                            Vehículos registrados
                        </h2>
                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"></path>
                        </svg>
                    </div>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white mb-2">
                        {{ $vehiculosCount }}
                    </p>
                    @if(!empty($vehiculosPorTipo))
                        <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                            @foreach($vehiculosPorTipo as $tipo => $total)
                                <span class="text-xs px-2 py-1 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                    {{ $tipo }}: {{ $total }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </a>

                {{-- TARJETA DE PROPIETARIOS --}}
                <a href="{{ route('propietarios.index') }}" 
                   class="bg-white dark:bg-gray-800 p-6 shadow rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow cursor-pointer block">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-gray-600 dark:text-gray-400 text-sm font-semibold">
                            Propietarios registrados
                        </h2>
                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"></path>
                        </svg>
                    </div>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white mb-2">
                        {{ $propietariosCount }}
                    </p>
                    @if(!empty($propietariosPorTipo))
                        <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                            @foreach($propietariosPorTipo as $tipo => $total)
                                <span class="text-xs px-2 py-1 rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                    {{ $tipo }}: {{ $total }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </a>

            </div>

            {{-- WIDGET: DISTRIBUCIÓN DE ESTADOS DE VEHÍCULOS --}}
            @if(!empty($vehiculosEstadosConPorcentaje))
            <div class="mt-8">
                <div class="bg-white dark:bg-gray-800 p-6 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
                        Distribución de Estados de Vehículos
                    </h2>
                    
                    <div class="space-y-4">
                        @php
                            $estadosConfig = [
                                'Activo' => [
                                    'color' => 'bg-green-500',
                                    'text' => 'text-green-700 dark:text-green-300',
                                    'bg' => 'bg-green-100 dark:bg-green-900',
                                ],
                                'En Mantenimiento' => [
                                    'color' => 'bg-yellow-500',
                                    'text' => 'text-yellow-700 dark:text-yellow-300',
                                    'bg' => 'bg-yellow-100 dark:bg-yellow-900',
                                ],
                                'Fuera de Servicio' => [
                                    'color' => 'bg-red-500',
                                    'text' => 'text-red-700 dark:text-red-300',
                                    'bg' => 'bg-red-100 dark:bg-red-900',
                                ],
                            ];
                        @endphp
                        
                        @foreach(['Activo', 'En Mantenimiento', 'Fuera de Servicio'] as $estado)
                            @if(isset($vehiculosEstadosConPorcentaje[$estado]))
                                @php
                                    $data = $vehiculosEstadosConPorcentaje[$estado];
                                    $config = $estadosConfig[$estado] ?? [
                                        'color' => 'bg-gray-500',
                                        'text' => 'text-gray-700 dark:text-gray-300',
                                        'bg' => 'bg-gray-100 dark:bg-gray-700',
                                    ];
                                @endphp
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">
                                            {{ $estado }}
                                        </span>
                                        <span class="font-semibold {{ $config['text'] }}">
                                            {{ $data['total'] }} ({{ $data['porcentaje'] }}%)
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-6 overflow-hidden">
                                        <div 
                                            class="{{ $config['color'] }} h-6 rounded-full transition-all duration-500 flex items-center justify-end pr-2"
                                            style="width: {{ $data['porcentaje'] }}%"
                                        >
                                            @if($data['porcentaje'] > 10)
                                                <span class="text-xs font-semibold text-white">
                                                    {{ $data['porcentaje'] }}%
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">
                                            {{ $estado }}
                                        </span>
                                        <span class="font-semibold text-gray-500 dark:text-gray-400">
                                            0 (0%)
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-6">
                                        <div class="bg-gray-300 dark:bg-gray-600 h-6 rounded-full" style="width: 0%"></div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- WIDGET: PRÓXIMOS CUMPLEAÑOS DE CONDUCTORES --}}
            @if($proximosCumpleanos->count() > 0)
            <div class="mt-8">
                <div class="bg-white dark:bg-gray-800 p-6 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
                        Próximos Cumpleaños de Conductores
                    </h2>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Conductor
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Tipo
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Próximo Cumpleaños
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Días Restantes
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Cumplirá
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($proximosCumpleanos as $cumpleanos)
                                    @php
                                        $dias = $cumpleanos['dias_restantes'];
                                        $esHoy = $dias === 0;
                                        $esProximo = $dias <= 7;
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $cumpleanos['nombres'] }} {{ $cumpleanos['apellidos'] }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                C.C. {{ $cumpleanos['cedula'] }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                @if($cumpleanos['tipo'] === 'Camionetas')
                                                    bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200
                                                @else
                                                    bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200
                                                @endif">
                                                {{ $cumpleanos['tipo'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ $cumpleanos['proximo_cumpleanos']->format('d/m/Y') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if($esHoy)
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                                    ¡Hoy!
                                                </span>
                                            @elseif($esProximo)
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">
                                                    {{ $dias }} día{{ $dias > 1 ? 's' : '' }}
                                                </span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                                    {{ $dias }} días
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ $cumpleanos['edad'] }} años
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
            
            {{-- Ejemplo de Skeleton Loaders para cuando se carguen datos dinámicamente --}}
            {{-- Descomentar cuando se implemente carga dinámica de métricas --}}
            {{-- 
            <div id="dashboard-skeletons" class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                <x-skeleton-card :hide-title="false" />
                <x-skeleton-card :hide-title="false" />
                <x-skeleton-card :hide-title="false" />
            </div>
            --}}
        </div>
    </div>
</x-app-layout>
