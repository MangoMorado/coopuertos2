<x-app-layout>
    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- BIENVENIDA --}}
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">
                Hola {{ Auth::user()->name }}
            </h1>

            {{-- TARJETAS DEL DASHBOARD --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

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

            </div>

            {{-- WIDGETS: PRÓXIMOS CUMPLEAÑOS Y DISTRIBUCIÓN DE ESTADOS DE VEHÍCULOS EN LA MISMA FILA --}}
            @if($proximosCumpleanos->count() > 0 || !empty($vehiculosEstadosConPorcentaje))
            <div class="mt-6 sm:mt-8 flex flex-col lg:flex-row gap-4 sm:gap-6">
                {{-- PRÓXIMOS CUMPLEAÑOS (80% en desktop, 100% en móvil) --}}
                @if($proximosCumpleanos->count() > 0)
                <div class="w-full lg:w-[80%] bg-white dark:bg-gray-800 p-4 sm:p-6 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
                        Próximos Cumpleaños
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
                @endif

                {{-- DISTRIBUCIÓN DE ESTADOS DE VEHÍCULOS (20% en desktop, 100% en móvil) - GRÁFICO DE PASTEL --}}
                @if(!empty($vehiculosEstadosConPorcentaje))
                <div class="w-full lg:w-[20%] bg-white dark:bg-gray-800 p-4 lg:p-6 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                    <h2 class="text-base lg:text-lg font-semibold text-gray-800 dark:text-gray-100 mb-3 lg:mb-4">
                        Estados de Vehículos
                    </h2>
                    
                    <div class="relative">
                        <canvas id="vehiculosEstadosChart" class="w-full" style="max-height: 250px;"></canvas>
                    </div>
                    
                    {{-- Leyenda personalizada --}}
                    <div class="mt-4 space-y-2">
                        @php
                            $estadosConfig = [
                                'Activo' => [
                                    'color' => '#10b981', // green-500
                                    'text' => 'text-green-700 dark:text-green-300',
                                ],
                                'En Mantenimiento' => [
                                    'color' => '#eab308', // yellow-500
                                    'text' => 'text-yellow-700 dark:text-yellow-300',
                                ],
                                'Fuera de Servicio' => [
                                    'color' => '#ef4444', // red-500
                                    'text' => 'text-red-700 dark:text-red-300',
                                ],
                            ];
                        @endphp
                        
                        @foreach(['Activo', 'En Mantenimiento', 'Fuera de Servicio'] as $estado)
                            @if(isset($vehiculosEstadosConPorcentaje[$estado]))
                                @php
                                    $data = $vehiculosEstadosConPorcentaje[$estado];
                                    $config = $estadosConfig[$estado] ?? [
                                        'color' => '#6b7280',
                                        'text' => 'text-gray-700 dark:text-gray-300',
                                    ];
                                @endphp
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 rounded-full" style="background-color: {{ $config['color'] }};"></div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $estado }}</span>
                                    </div>
                                    <span class="font-semibold {{ $config['text'] }}">
                                        {{ $data['total'] }} ({{ $data['porcentaje'] }}%)
                                    </span>
                                </div>
                            @else
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                                        <span class="font-medium text-gray-500 dark:text-gray-400">{{ $estado }}</span>
                                    </div>
                                    <span class="font-semibold text-gray-500 dark:text-gray-400">
                                        0 (0%)
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    
                    {{-- Script para inicializar el gráfico --}}
                    @php
                        $labels = [];
                        $data = [];
                        $backgroundColors = [];
                        $borderColors = [];
                        
                        $estadosOrdenados = ['Activo', 'En Mantenimiento', 'Fuera de Servicio'];
                        $coloresChart = [
                            'Activo' => ['bg' => '#10b981', 'border' => '#059669'],
                            'En Mantenimiento' => ['bg' => '#eab308', 'border' => '#ca8a04'],
                            'Fuera de Servicio' => ['bg' => '#ef4444', 'border' => '#dc2626'],
                        ];
                        
                        foreach ($estadosOrdenados as $estado) {
                            $labels[] = $estado;
                            if (isset($vehiculosEstadosConPorcentaje[$estado])) {
                                $data[] = $vehiculosEstadosConPorcentaje[$estado]['total'];
                                $colores = $coloresChart[$estado] ?? ['bg' => '#6b7280', 'border' => '#4b5563'];
                                $backgroundColors[] = $colores['bg'];
                                $borderColors[] = $colores['border'];
                            } else {
                                $data[] = 0;
                                $backgroundColors[] = '#9ca3af';
                                $borderColors[] = '#6b7280';
                            }
                        }
                    @endphp
                    
                    <script>
                        (function() {
                            function initChart() {
                                if (typeof window.Chart === 'undefined') {
                                    setTimeout(initChart, 100);
                                    return;
                                }
                                
                                const ctx = document.getElementById('vehiculosEstadosChart');
                                if (!ctx) return;
                                
                                // Evitar múltiples inicializaciones
                                if (ctx.chart) return;
                                
                                const isDarkMode = document.documentElement.classList.contains('dark');
                                const textColor = isDarkMode ? '#e5e7eb' : '#374151';
                                
                                const chartData = {
                                    labels: @json($labels),
                                    datasets: [{
                                        data: @json($data),
                                        backgroundColor: @json($backgroundColors),
                                        borderColor: @json($borderColors),
                                        borderWidth: 2
                                    }]
                                };
                                
                                ctx.chart = new window.Chart(ctx, {
                                    type: 'pie',
                                    data: chartData,
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: true,
                                        aspectRatio: 1.2,
                                        plugins: {
                                            legend: {
                                                display: false
                                            },
                                            tooltip: {
                                                backgroundColor: isDarkMode ? '#1f2937' : '#ffffff',
                                                titleColor: textColor,
                                                bodyColor: textColor,
                                                borderColor: isDarkMode ? '#374151' : '#e5e7eb',
                                                borderWidth: 1,
                                                padding: 12,
                                                callbacks: {
                                                    label: function(context) {
                                                        const label = context.label || '';
                                                        const value = context.parsed || 0;
                                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                                        return label + ': ' + value + ' (' + percentage + '%)';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                });
                            }
                            
                            if (document.readyState === 'loading') {
                                document.addEventListener('DOMContentLoaded', initChart);
                            } else {
                                initChart();
                            }
                        })();
                    </script>
                </div>
                @endif
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
