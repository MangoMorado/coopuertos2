<x-app-layout>
    <div class="max-w-7xl mx-auto py-4 sm:py-8 px-4 sm:px-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Propietarios</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('propietarios.create') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-md transition">
                   + Nuevo Propietario
                </a>
            </div>
        </div>

        <div class="mb-4">
            <div class="flex space-x-2">
                <input type="text" id="search-input" placeholder="Buscar por número de identificación, nombre, teléfono, correo o dirección..." 
                       value="{{ request('search') }}"
                       class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded px-3 py-2 flex-1 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400 dark:placeholder-gray-500">
                @if(request('search'))
                    <a href="{{ route('propietarios.index') }}"
                       class="bg-gray-600 dark:bg-gray-700 hover:bg-gray-700 dark:hover:bg-gray-600 text-white px-4 py-2 rounded-lg shadow-md transition">
                       Limpiar
                    </a>
                @endif
            </div>
        </div>

        {{-- Contenedor para skeleton loader durante búsqueda --}}
        <div id="skeleton-container" class="hidden">
            <x-skeleton-table :rows="5" :columns="5" />
        </div>

        {{-- Contenedor para la tabla real --}}
        <div id="table-container" class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                @include('propietarios.partials.table', ['propietarios' => $propietarios])
            </div>
        </div>

        <div id="pagination-container" class="mt-6">
            {{ $propietarios->links() }}
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
                        
                        fetch(`{{ route('propietarios.index') }}?search=${encodeURIComponent(searchTerm)}&ajax=1`, {
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
