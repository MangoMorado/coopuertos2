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
    </div>
</x-app-layout>
