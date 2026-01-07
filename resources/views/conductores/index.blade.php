@php
    $theme = Auth::user()->theme ?? 'light';
    $isDark = $theme === 'dark';
    
    // Colores según el tema
    $textTitle = $isDark ? 'text-gray-100' : 'text-gray-800';
    $bgTable = $isDark ? 'bg-gray-800' : 'bg-white';
    $bgInput = $isDark ? 'bg-gray-700' : 'bg-white';
    $textInput = $isDark ? 'text-gray-100' : 'text-gray-900';
    $borderInput = $isDark ? 'border-gray-600' : 'border-gray-300';
    $bgClearBtn = $isDark ? 'bg-gray-700 hover:bg-gray-600' : 'bg-gray-300 hover:bg-gray-400';
    $textClearBtn = $isDark ? 'text-gray-100' : 'text-gray-800';
    $bgSuccess = $isDark ? 'bg-green-900 border-green-700' : 'bg-green-100 border-green-300';
    $textSuccess = $isDark ? 'text-green-200' : 'text-green-800';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl {{ $textTitle }} leading-tight">
            Listado de Conductores
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto py-8 px-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold {{ $textTitle }}">Conductores</h2>
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
            <div class="mb-4 {{ $bgSuccess }} border {{ $textSuccess }} px-4 py-2 rounded">
                {{ session('success') }}
            </div>
        @endif
<div class="mb-4">
    <div class="flex space-x-2">
        <input type="text" id="search-input" placeholder="Buscar por cédula, nombre, apellido, placa, celular, correo..." 
               value="{{ request('search') }}"
               class="{{ $bgInput }} {{ $textInput }} {{ $borderInput }} border rounded px-3 py-2 flex-1 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400">
        @if(request('search'))
            <a href="{{ route('conductores.index') }}"
               class="{{ $bgClearBtn }} {{ $textClearBtn }} px-4 py-2 rounded-lg shadow-md transition">
               Limpiar
            </a>
        @endif
    </div>
</div>
        <div id="table-container" class="{{ $bgTable }} shadow-md rounded-lg overflow-hidden">
            @include('conductores.partials.table', ['conductores' => $conductores, 'theme' => $theme, 'isDark' => $isDark])
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
                            const theme = '{{ $theme }}';
                            const isDark = theme === 'dark';
                            const bgTable = isDark ? 'bg-gray-800' : 'bg-white';
                            tableContainer.className = bgTable + ' shadow-md rounded-lg overflow-hidden';
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
