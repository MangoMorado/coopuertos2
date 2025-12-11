<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Formulario PQRS - Coopuertos</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans text-gray-900 antialiased bg-gray-50">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white shadow-lg rounded-lg p-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Formulario PQRS</h1>
                <p class="text-gray-600 mb-6">Peticiones, Quejas, Reclamos y Sugerencias</p>

                @if (session('success'))
                    <div class="mb-6 bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('pqrs.store.public') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    <div class="flex justify-center my-6">
                        <img src="/images/logo.svg" alt="Logo" class="max-h-24 max-w-full object-contain">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                        <select name="tipo" 
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Seleccione...</option>
                            @foreach(['Peticiones', 'Quejas', 'Reclamos', 'Sugerencias', 'Otros'] as $opcion)
                            <option value="{{ $opcion }}" {{ old('tipo') === $opcion ? 'selected' : '' }}>{{ $opcion }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                        <input type="date" name="fecha" value="{{ old('fecha', date('Y-m-d')) }}"  
                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                        <input type="text" name="nombre" value="{{ old('nombre') }}"  placeholder="Ingrese su nombre completo"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div x-data="autocomplete_vehiculo_placa()">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vehículo (Placa)</label>
                        <div class="relative">
                            <input 
                                type="text" 
                                x-model="search"
                                @input.debounce.300ms="buscar()"
                                @focus="mostrarDropdown = true"
                                @click.away="mostrarDropdown = false"
                                @keydown.escape="mostrarDropdown = false"
                                @keydown.arrow-down.prevent="siguiente()"
                                @keydown.arrow-up.prevent="anterior()"
                                @keydown.enter.prevent="seleccionar(selectedIndex)"
                                placeholder="Buscar Vehículo"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <input type="hidden" name="vehiculo_placa" x-model="valorSeleccionado">
                            
                            <div x-show="mostrarDropdown && resultados.length > 0" 
                                 x-transition
                                 class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"
                                 style="display: none;">
                                <template x-for="(resultado, index) in resultados" :key="resultado.id">
                                    <div 
                                        @click="seleccionar(index)"
                                        @mouseenter="selectedIndex = index"
                                        :class="selectedIndex === index ? \'bg-gray-100\' : \'\'"
                                        class="px-4 py-2 cursor-pointer text-gray-900 border-b border-gray-200 last:border-b-0 hover:bg-gray-100">
                                        <div class="font-semibold" x-text="resultado.label"></div>
                                        <div x-show="formatearInfoAdicional(resultado)" class="text-sm text-gray-600 mt-1" x-text="formatearInfoAdicional(resultado)"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <script>
                    function autocomplete_vehiculo_placa() {
                        return {
                            search: '',
                            resultados: [],
                            mostrarDropdown: false,
                            selectedIndex: -1,
                            valorSeleccionado: '',
                            labelField: 'placa',
                            searchColumns: ["placa","marca","modelo","tipo"],
                            displayColumns: ["marca","modelo","tipo"],
                            
                            formatearInfoAdicional(item) {
                                if (!this.displayColumns || this.displayColumns.length === 0) return '';
                                const partes = [];
                                this.displayColumns.forEach(col => {
                                    if (item[col] && item[col] !== null && item[col] !== '') {
                                        partes.push(item[col]);
                                    }
                                });
                                return partes.length > 0 ? partes.join(' - ') : '';
                            },
                            
                            buscar() {
                                if (this.search.length < 1) {
                                    this.resultados = [];
                                    this.mostrarDropdown = false;
                                    return;
                                }
                                
                                let url = `{{ route('api.vehiculos.search') }}?q=${encodeURIComponent(this.search)}`;
                                if (this.searchColumns && this.searchColumns.length > 0) {
                                    const columnsParam = this.searchColumns.map(col => 'columns[]=' + encodeURIComponent(col)).join('&');
                                    url += '&' + columnsParam;
                                }
                                
                                fetch(url)
                                    .then(response => response.json())
                                    .then(data => {
                                        this.resultados = data.map(item => {
                                            let label = '';
                                            if (item.placa) {
                                                label = String(item.placa);
                                            } else if (item.label) {
                                                label = item.label;
                                            } else {
                                                label = JSON.stringify(item);
                                            }
                                            return { ...item, label: label };
                                        });
                                        this.mostrarDropdown = data.length > 0;
                                        this.selectedIndex = -1;
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        this.resultados = [];
                                    });
                            },
                            
                            seleccionar(index) {
                                if (index >= 0 && index < this.resultados.length) {
                                    const resultado = this.resultados[index];
                                    const displayValue = resultado.placa || resultado.label || '';
                                    this.search = displayValue;
                                    this.valorSeleccionado = displayValue;
                                    this.mostrarDropdown = false;
                                }
                            },
                            
                            siguiente() {
                                if (this.selectedIndex < this.resultados.length - 1) {
                                    this.selectedIndex++;
                                }
                            },
                            
                            anterior() {
                                if (this.selectedIndex > 0) {
                                    this.selectedIndex--;
                                }
                            }
                        };
                    }
                    </script>

                    <div x-data="autocomplete_campo_autocomplete_1765475558046()">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Conductor</label>
                        <div class="relative">
                            <input 
                                type="text" 
                                x-model="search"
                                @input.debounce.300ms="buscar()"
                                @focus="mostrarDropdown = true"
                                @click.away="mostrarDropdown = false"
                                @keydown.escape="mostrarDropdown = false"
                                @keydown.arrow-down.prevent="siguiente()"
                                @keydown.arrow-up.prevent="anterior()"
                                @keydown.enter.prevent="seleccionar(selectedIndex)"
                                placeholder="Buscar..."
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <input type="hidden" name="campo_autocomplete_1765475558046" x-model="valorSeleccionado">
                            
                            <div x-show="mostrarDropdown && resultados.length > 0" 
                                 x-transition
                                 class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"
                                 style="display: none;">
                                <template x-for="(resultado, index) in resultados" :key="resultado.id">
                                    <div 
                                        @click="seleccionar(index)"
                                        @mouseenter="selectedIndex = index"
                                        :class="selectedIndex === index ? \'bg-gray-100\' : \'\'"
                                        class="px-4 py-2 cursor-pointer text-gray-900 border-b border-gray-200 last:border-b-0 hover:bg-gray-100">
                                        <div class="font-semibold" x-text="resultado.label"></div>
                                        <div x-show="formatearInfoAdicional(resultado)" class="text-sm text-gray-600 mt-1" x-text="formatearInfoAdicional(resultado)"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <script>
                    function autocomplete_campo_autocomplete_1765475558046() {
                        return {
                            search: '',
                            resultados: [],
                            mostrarDropdown: false,
                            selectedIndex: -1,
                            valorSeleccionado: '',
                            labelField: 'placa',
                            searchColumns: ["placa","marca","modelo","nombres","apellidos","vehiculo_placa"],
                            displayColumns: ["marca","modelo","nombres","apellidos","vehiculo_placa"],
                            
                            formatearInfoAdicional(item) {
                                if (!this.displayColumns || this.displayColumns.length === 0) return '';
                                const partes = [];
                                this.displayColumns.forEach(col => {
                                    if (item[col] && item[col] !== null && item[col] !== '') {
                                        partes.push(item[col]);
                                    }
                                });
                                return partes.length > 0 ? partes.join(' - ') : '';
                            },
                            
                            buscar() {
                                if (this.search.length < 1) {
                                    this.resultados = [];
                                    this.mostrarDropdown = false;
                                    return;
                                }
                                
                                let url = `{{ route('api.conductores.search') }}?q=${encodeURIComponent(this.search)}`;
                                if (this.searchColumns && this.searchColumns.length > 0) {
                                    const columnsParam = this.searchColumns.map(col => 'columns[]=' + encodeURIComponent(col)).join('&');
                                    url += '&' + columnsParam;
                                }
                                
                                fetch(url)
                                    .then(response => response.json())
                                    .then(data => {
                                        this.resultados = data.map(item => {
                                            let label = '';
                                            if (item.placa) {
                                                label = String(item.placa);
                                            } else if (item.label) {
                                                label = item.label;
                                            } else {
                                                label = JSON.stringify(item);
                                            }
                                            return { ...item, label: label };
                                        });
                                        this.mostrarDropdown = data.length > 0;
                                        this.selectedIndex = -1;
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        this.resultados = [];
                                    });
                            },
                            
                            seleccionar(index) {
                                if (index >= 0 && index < this.resultados.length) {
                                    const resultado = this.resultados[index];
                                    const displayValue = resultado.placa || resultado.label || '';
                                    this.search = displayValue;
                                    this.valorSeleccionado = displayValue;
                                    this.mostrarDropdown = false;
                                }
                            },
                            
                            siguiente() {
                                if (this.selectedIndex < this.resultados.length - 1) {
                                    this.selectedIndex++;
                                }
                            },
                            
                            anterior() {
                                if (this.selectedIndex > 0) {
                                    this.selectedIndex--;
                                }
                            }
                        };
                    }
                    </script>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Número de Tiquete</label>
                        <input type="text" name="numero_tiquete" value="{{ old('numero_tiquete') }}"  placeholder="Ingrese el número de tiquete"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico</label>
                        <input type="email" name="correo_electronico" value="{{ old('correo_electronico') }}"  placeholder="correo@ejemplo.com"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Número de Teléfono</label>
                        <input type="tel" name="numero_telefono" value="{{ old('numero_telefono') }}"  placeholder="Ingrese su número de teléfono"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Califica el Servicio</label>
                        <div class="flex items-center space-x-2" x-data="{ rating: {{ old('calificacion', 0) }} }">
                            <input type="hidden" name="calificacion" x-model="rating">
                            <button type="button" 
                                    @click="rating = 1"
                                    class="focus:outline-none">
                                <svg class="w-8 h-8 transition-colors"
                                     :class="rating >= 1 ? 'text-yellow-400 fill-current' : 'text-gray-300'"
                                     fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            </button>
                            <button type="button" 
                                    @click="rating = 2"
                                    class="focus:outline-none">
                                <svg class="w-8 h-8 transition-colors"
                                     :class="rating >= 2 ? 'text-yellow-400 fill-current' : 'text-gray-300'"
                                     fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            </button>
                            <button type="button" 
                                    @click="rating = 3"
                                    class="focus:outline-none">
                                <svg class="w-8 h-8 transition-colors"
                                     :class="rating >= 3 ? 'text-yellow-400 fill-current' : 'text-gray-300'"
                                     fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            </button>
                            <button type="button" 
                                    @click="rating = 4"
                                    class="focus:outline-none">
                                <svg class="w-8 h-8 transition-colors"
                                     :class="rating >= 4 ? 'text-yellow-400 fill-current' : 'text-gray-300'"
                                     fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            </button>
                            <button type="button" 
                                    @click="rating = 5"
                                    class="focus:outline-none">
                                <svg class="w-8 h-8 transition-colors"
                                     :class="rating >= 5 ? 'text-yellow-400 fill-current' : 'text-gray-300'"
                                     fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            </button>
                            <span class="ml-2 text-sm text-gray-600" x-show="rating > 0">
                                <span x-text="rating"></span> / 5
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Comentarios</label>
                        <textarea name="comentarios" rows="4"  placeholder="Escriba sus comentarios aquí..."
                                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('comentarios') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Adjuntos</label>
                        <p class="text-xs text-gray-500 mb-2">Formatos permitidos: Imágenes, Documentos, Videos. Máximo 10MB por archivo.</p>
                        <input type="file" name="adjuntos[]" multiple accept="image/*,.pdf,.doc,.docx,video/*" 
                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md shadow-sm font-medium">
                            Enviar PQRS
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function vehiculoAutocomplete() {
        return {
            search: '',
            resultados: [],
            mostrarDropdown: false,
            selectedIndex: -1,
            valorSeleccionado: '',
            
            buscar() {
                if (this.search.length < 1) {
                    this.resultados = [];
                    this.mostrarDropdown = false;
                    return;
                }
                
                fetch(`{{ route('api.vehiculos.search') }}?q=${encodeURIComponent(this.search)}`)
                    .then(response => response.json())
                    .then(data => {
                        this.resultados = data;
                        this.mostrarDropdown = data.length > 0;
                        this.selectedIndex = -1;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.resultados = [];
                    });
            },
            
            seleccionar(index) {
                if (index >= 0 && index < this.resultados.length) {
                    const resultado = this.resultados[index];
                    this.search = resultado.label || resultado.placa || '';
                    this.valorSeleccionado = resultado.label || resultado.placa || '';
                    this.mostrarDropdown = false;
                }
            },
            
            siguiente() {
                if (this.selectedIndex < this.resultados.length - 1) {
                    this.selectedIndex++;
                }
            },
            
            anterior() {
                if (this.selectedIndex > 0) {
                    this.selectedIndex--;
                }
            }
        };
    }
    </script>
</body>
</html>