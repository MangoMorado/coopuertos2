<x-app-layout>
    <div class="max-w-7xl mx-auto py-4 sm:py-8 px-4 sm:px-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Conductores</h2>
            <div class="flex flex-wrap gap-2">
            @can('crear conductores')
                <a href="{{ route('conductores.import') }}"
                   class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg shadow-md transition">
                   Importar Conductores
                </a>
                @endcan
            @can('crear conductores')
                <a href="{{ route('conductores.create') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-md transition">
                   + Nuevo Conductor
                </a>
                @endcan
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button @click="open = !open"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow-md transition flex items-center gap-1">
                        Exportar
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 z-50"
                         style="display: none;">
                        <div class="py-1">
                            <a href="{{ route('conductores.exportar', ['formato' => 'excel']) }}"
                               class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                                Exportar a Excel
                            </a>
                            <a href="{{ route('conductores.exportar', ['formato' => 'csv']) }}"
                               class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                                Exportar a CSV
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<div class="mb-4">
    <div class="flex space-x-2">
        <input type="text" id="search-input" placeholder="Buscar por cédula, nombre, apellido, placa, celular, correo..." 
               value="{{ request('search') }}"
               class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded px-3 py-2 flex-1 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400 dark:placeholder-gray-500">
        @if(request('search'))
            <a href="{{ route('conductores.index') }}"
               class="bg-gray-600 dark:bg-gray-700 hover:bg-gray-700 dark:hover:bg-gray-600 text-white px-4 py-2 rounded-lg shadow-md transition">
               Limpiar
            </a>
        @endif
    </div>
</div>
        {{-- Contenedor para skeleton loader durante búsqueda --}}
        <div id="skeleton-container" class="hidden">
            <x-skeleton-table :rows="5" :columns="6" />
        </div>

        {{-- Contenedor para la tabla real --}}
        <div id="table-container" class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                @include('conductores.partials.table', ['conductores' => $conductores])
            </div>
        </div>

        <div id="pagination-container" class="mt-6">
            {{ $conductores->links() }}
        </div>
    </div>

    @push('scripts')
    <script>
        let searchTimeout;
        const searchInput = document.getElementById('search-input');
        const tableContainer = document.getElementById('table-container');
        const paginationContainer = document.getElementById('pagination-container');

        if (searchInput) {
            const skeletonContainer = document.getElementById('skeleton-container');
            
            searchInput.addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                const searchTerm = e.target.value;

                searchTimeout = setTimeout(function() {
                    if (searchTerm.length >= 2 || searchTerm.length === 0) {
                        // Mostrar skeleton loader durante la búsqueda
                        if (skeletonContainer) {
                            skeletonContainer.classList.remove('hidden');
                            tableContainer.classList.add('hidden');
                        }
                        
                        fetch(`{{ route('conductores.index') }}?search=${encodeURIComponent(searchTerm)}&ajax=1`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            // Ocultar skeleton y mostrar resultados
                            if (skeletonContainer) {
                                skeletonContainer.classList.add('hidden');
                            }
                            tableContainer.classList.remove('hidden');
                            
                            tableContainer.className = 'bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden';
                            tableContainer.innerHTML = '<div class="overflow-x-auto">' + data.html + '</div>';
                            paginationContainer.innerHTML = data.pagination;
                            
                            // Actualizar URL sin recargar
                            const url = new URL(window.location);
                            if (searchTerm) {
                                url.searchParams.set('search', searchTerm);
                            } else {
                                url.searchParams.delete('search');
                            }
                            window.history.pushState({}, '', url);
                        })
                        .catch(error => {
                            console.error('Error en la búsqueda:', error);
                            // Ocultar skeleton en caso de error
                            if (skeletonContainer) {
                                skeletonContainer.classList.add('hidden');
                            }
                            tableContainer.classList.remove('hidden');
                            
                            // Mostrar toast de error
                            if (window.toast) {
                                window.toast.error('Error al realizar la búsqueda. Por favor, intenta de nuevo.');
                            }
                        });
                    }
                }, 300); // Esperar 300ms después de que el usuario deje de escribir
            });
        }
    </script>
    @endpush
</x-app-layout>
