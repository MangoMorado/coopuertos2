<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Listado de Conductores
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto py-8 px-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Conductores</h2>
            <div class="flex space-x-2">
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
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 bg-green-100 dark:bg-green-900 border border-green-300 dark:border-green-700 text-green-800 dark:text-green-200 px-4 py-2 rounded">
                {{ session('success') }}
            </div>
        @endif
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
        <div id="table-container" class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            @include('conductores.partials.table', ['conductores' => $conductores])
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
            searchInput.addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                const searchTerm = e.target.value;

                searchTimeout = setTimeout(function() {
                    if (searchTerm.length >= 2 || searchTerm.length === 0) {
                        fetch(`{{ route('conductores.index') }}?search=${encodeURIComponent(searchTerm)}&ajax=1`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            tableContainer.className = 'bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden';
                            tableContainer.innerHTML = data.html;
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
                        });
                    }
                }, 300); // Esperar 300ms después de que el usuario deje de escribir
            });
        }
    </script>
    @endpush
</x-app-layout>
