@php
    $theme = Auth::user()->theme ?? 'light';
    $isDark = $theme === 'dark';
    
    // Colores según el tema
    $bgCard = $isDark ? 'bg-gray-800' : 'bg-white';
    $textTitle = $isDark ? 'text-gray-100' : 'text-gray-800';
    $textSubtitle = $isDark ? 'text-gray-400' : 'text-gray-600';
    $textBody = $isDark ? 'text-gray-300' : 'text-gray-700';
    $borderCard = $isDark ? 'border-gray-700' : 'border-gray-200';
    $bgSuccess = $isDark ? 'bg-green-900 border-green-700' : 'bg-green-100 border-green-300';
    $textSuccess = $isDark ? 'text-green-200' : 'text-green-800';
    $bgError = $isDark ? 'bg-red-900 border-red-700' : 'bg-red-100 border-red-300';
    $textError = $isDark ? 'text-red-200' : 'text-red-800';
@endphp

<x-app-layout>
    <div class="max-w-7xl mx-auto py-4 sm:py-8 px-4 sm:px-6">
        <div class="{{ $bgCard }} rounded-lg shadow-md border {{ $borderCard }} p-6">
            <div class="mb-6">
                <h3 class="text-2xl font-bold {{ $textTitle }} mb-2">Gestión de Módulos por Rol</h3>
                <p class="{{ $textSubtitle }}">Activa o desactiva módulos completos para cada rol. Al activar un módulo, el rol obtiene todos los permisos (ver, crear, editar, eliminar) de ese módulo.</p>
                <p class="{{ $textSubtitle }} text-sm mt-2">
                    <strong>Nota:</strong> El rol <strong>Mango</strong> tiene acceso completo a todos los módulos y no puede ser modificado.
                </p>
            </div>

            <form method="POST" action="{{ route('configuracion.update') }}" id="permisos-form">
                @csrf
                @method('PUT')

                <div class="space-y-8">
                    @foreach($roles->where('name', '!=', 'Mango') as $role)
                        <div class="border-b {{ $borderCard }} pb-6 last:border-b-0 last:pb-0">
                            <h4 class="text-xl font-semibold {{ $textTitle }} mb-4">
                                Rol: <span class="capitalize">{{ $role->name }}</span>
                            </h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($modulos as $modulo => $nombreModulo)
                                    @php
                                        $moduloActivo = $modulosPorRol[$role->name][$modulo] ?? false;
                                        $switchId = "switch_{$role->name}_{$modulo}";
                                    @endphp
                                    
                                    <div class="{{ $isDark ? 'bg-gray-700' : 'bg-gray-50' }} rounded-lg p-4 border {{ $borderCard }} flex items-center justify-between">
                                        <label for="{{ $switchId }}" class="flex-1 cursor-pointer">
                                            <span class="font-medium {{ $textTitle }}">{{ $nombreModulo }}</span>
                                        </label>
                                        
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" 
                                                   name="modulos[{{ $role->name }}][]" 
                                                   value="{{ $modulo }}"
                                                   id="{{ $switchId }}"
                                                   {{ $moduloActivo ? 'checked' : '' }}
                                                   class="sr-only peer">
                                            <div class="w-11 h-6 {{ $isDark ? 'bg-gray-600' : 'bg-gray-300' }} peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 flex justify-end space-x-4">
                    <a href="{{ route('dashboard') }}"
                       class="px-6 py-2 {{ $isDark ? 'bg-gray-500 hover:bg-gray-600' : 'bg-gray-500 hover:bg-gray-600' }} text-white rounded-lg transition">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        Guardar
                    </button>
                </div>
            </form>
        </div>

        <!-- Paneles de Salud de la App -->
        <div class="{{ $bgCard }} rounded-lg shadow-md border {{ $borderCard }} p-6 mt-6">
            <div class="mb-6">
                <h3 class="text-2xl font-bold {{ $textTitle }} mb-2">Paneles de Salud de la App</h3>
                <p class="{{ $textSubtitle }}">Estado actual del sistema y sus componentes principales.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Base de Datos -->
                <div class="{{ $isDark ? 'bg-gray-700' : 'bg-gray-50' }} rounded-lg p-4 border {{ $borderCard }}">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-semibold {{ $textTitle }}">Base de Datos</h4>
                        @if(isset($healthStatus['database']['status']))
                            @if($healthStatus['database']['status'] === 'healthy')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                                    Saludable
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    <span class="w-2 h-2 bg-red-500 rounded-full mr-1"></span>
                                    Error
                                </span>
                            @endif
                        @endif
                    </div>
                    @if(isset($healthStatus['database']['message']))
                        <p class="text-sm {{ $textBody }}">{{ $healthStatus['database']['message'] }}</p>
                    @endif
                    @if(isset($healthStatus['database']['connection']))
                        <p class="text-xs {{ $textSubtitle }} mt-1">Conexión: {{ $healthStatus['database']['connection'] }}</p>
                    @endif
                </div>

                <!-- Colas -->
                <div class="{{ $isDark ? 'bg-gray-700' : 'bg-gray-50' }} rounded-lg p-4 border {{ $borderCard }}">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-semibold {{ $textTitle }}">Colas</h4>
                        @if(isset($healthStatus['queue']['status']))
                            @if($healthStatus['queue']['status'] === 'healthy')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                                    Saludable
                                </span>
                            @elseif($healthStatus['queue']['status'] === 'warning')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    <span class="w-2 h-2 bg-yellow-500 rounded-full mr-1"></span>
                                    Advertencia
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    <span class="w-2 h-2 bg-red-500 rounded-full mr-1"></span>
                                    Error
                                </span>
                            @endif
                        @endif
                    </div>
                    @if(isset($healthStatus['queue']['pending']))
                        <p class="text-sm {{ $textBody }}">Pendientes: <span class="font-semibold">{{ $healthStatus['queue']['pending'] }}</span></p>
                    @endif
                    @if(isset($healthStatus['queue']['failed']))
                        <p class="text-sm {{ $textBody }}">Fallidos: <span class="font-semibold {{ $healthStatus['queue']['failed'] > 0 ? 'text-red-600 dark:text-red-400' : '' }}">{{ $healthStatus['queue']['failed'] }}</span></p>
                    @endif
                    @if(isset($healthStatus['queue']['connection']))
                        <p class="text-xs {{ $textSubtitle }} mt-1">Conexión: {{ $healthStatus['queue']['connection'] }}</p>
                    @endif
                </div>

                <!-- Almacenamiento -->
                <div class="{{ $isDark ? 'bg-gray-700' : 'bg-gray-50' }} rounded-lg p-4 border {{ $borderCard }}">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-semibold {{ $textTitle }}">Almacenamiento</h4>
                        @if(isset($healthStatus['storage']['status']))
                            @if($healthStatus['storage']['status'] === 'healthy')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                                    Saludable
                                </span>
                            @elseif($healthStatus['storage']['status'] === 'warning')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    <span class="w-2 h-2 bg-yellow-500 rounded-full mr-1"></span>
                                    Advertencia
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    <span class="w-2 h-2 bg-red-500 rounded-full mr-1"></span>
                                    Error
                                </span>
                            @endif
                        @endif
                    </div>
                    @if(isset($healthStatus['storage']['used']) && isset($healthStatus['storage']['total']))
                        <p class="text-sm {{ $textBody }}">Usado: <span class="font-semibold">{{ $healthStatus['storage']['used'] }}</span> / {{ $healthStatus['storage']['total'] }}</p>
                        @if(isset($healthStatus['storage']['percentage']))
                            <div class="mt-2">
                                <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ $healthStatus['storage']['percentage'] > 90 ? 'bg-red-500' : ($healthStatus['storage']['percentage'] > 75 ? 'bg-yellow-500' : 'bg-green-500') }}" 
                                         style="width: {{ $healthStatus['storage']['percentage'] }}%"></div>
                                </div>
                                <p class="text-xs {{ $textSubtitle }} mt-1">{{ $healthStatus['storage']['percentage'] }}% utilizado</p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Versiones -->
            @if(isset($healthStatus['versions']))
                <div class="mt-4 pt-4 border-t {{ $borderCard }}">
                    <h4 class="font-semibold {{ $textTitle }} mb-2">Versiones del Sistema</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm {{ $textBody }}">PHP: <span class="font-semibold">{{ $healthStatus['versions']['php'] }}</span></p>
                        </div>
                        <div>
                            <p class="text-sm {{ $textBody }}">Laravel: <span class="font-semibold">{{ $healthStatus['versions']['laravel'] }}</span></p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Extensiones PHP -->
            @if(isset($healthStatus['php_extensions']))
                <div class="mt-4 pt-4 border-t {{ $borderCard }}">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-semibold {{ $textTitle }}">Extensiones PHP</h4>
                        @if($healthStatus['php_extensions']['status'] === 'healthy')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                                Todas instaladas
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-1"></span>
                                Faltan extensiones
                            </span>
                        @endif
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($healthStatus['php_extensions']['extensions'] as $extension => $info)
                            <div class="{{ $isDark ? 'bg-gray-700' : 'bg-gray-50' }} rounded-lg p-3 border {{ $borderCard }} flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium {{ $textTitle }}">{{ $extension }}</span>
                                        @if($info['loaded'])
                                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                            </svg>
                                        @endif
                                    </div>
                                    <p class="text-xs {{ $textSubtitle }} mt-1">{{ $info['description'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
