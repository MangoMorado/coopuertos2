@php
    $isDark = $isDark ?? false;
    $theme = $theme ?? 'light';
    $bgInput = $isDark ? 'bg-gray-700 border-gray-600 text-gray-100' : 'bg-white border-gray-300 text-gray-900';
    $bgDropdown = $isDark ? 'bg-gray-800 border-gray-600' : 'bg-white border-gray-300';
    $label = $isDark ? 'text-gray-300' : 'text-gray-700';
    $sectionTitle = $isDark ? 'text-gray-100' : 'text-gray-800';
    $sectionSub = $isDark ? 'text-gray-400' : 'text-gray-600';
    $textDropdown = $isDark ? 'text-gray-200' : 'text-gray-900';
    $hoverDropdown = $isDark ? 'hover:bg-gray-700' : 'hover:bg-gray-100';
    
    // Valores iniciales
    $propietarioSeleccionado = old('propietario_nombre', $vehiculo->propietario_nombre ?? '');
    $conductorSeleccionado = old('conductor_id', $vehiculo->conductor_id ?? '');
    $conductorNombre = '';
    if ($conductorSeleccionado && isset($vehiculo)) {
        $vehiculo->load('conductor');
        if ($vehiculo->conductor) {
            $conductorNombre = $vehiculo->conductor->nombres . ' ' . $vehiculo->conductor->apellidos . ' (' . $vehiculo->conductor->cedula . ')';
        }
    }
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block font-semibold {{ $label }}">Tipo de Vehículo</label>
        <select name="tipo" class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            @foreach(['Bus','Camioneta','Taxi'] as $tipo)
                <option value="{{ $tipo }}" {{ old('tipo', $vehiculo->tipo ?? '') === $tipo ? 'selected' : '' }}>{{ $tipo }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block font-semibold {{ $label }}">Marca</label>
        <input type="text" name="marca" value="{{ old('marca', $vehiculo->marca ?? '') }}" class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
    </div>
    <div>
        <label class="block font-semibold {{ $label }}">Modelo</label>
        <input type="text" name="modelo" value="{{ old('modelo', $vehiculo->modelo ?? '') }}" class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
    </div>
    <div>
        <label class="block font-semibold {{ $label }}">Año de Fabricación</label>
        <input type="number" name="anio_fabricacion" value="{{ old('anio_fabricacion', $vehiculo->anio_fabricacion ?? '') }}" min="1900" max="{{ now()->year }}" class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
    </div>
    <div>
        <label class="block font-semibold {{ $label }}">Número de Placa</label>
        <input type="text" name="placa" value="{{ old('placa', $vehiculo->placa ?? '') }}" class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
    </div>
    <div>
        <label class="block font-semibold {{ $label }}">Número de Chasis (VIN)</label>
        <input type="text" name="chasis_vin" value="{{ old('chasis_vin', $vehiculo->chasis_vin ?? '') }}" class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>
    <div>
        <label class="block font-semibold {{ $label }}">Capacidad de Pasajeros</label>
        <input type="number" name="capacidad_pasajeros" value="{{ old('capacidad_pasajeros', $vehiculo->capacidad_pasajeros ?? '') }}" min="0" class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>
    <div>
        <label class="block font-semibold {{ $label }}">Capacidad de Carga (kg)</label>
        <input type="number" name="capacidad_carga_kg" value="{{ old('capacidad_carga_kg', $vehiculo->capacidad_carga_kg ?? '') }}" min="0" class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>
    <div>
        <label class="block font-semibold {{ $label }}">Tipo de Combustible</label>
        <select name="combustible" class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            @foreach(['gasolina','diesel','hibrido','electrico'] as $c)
                <option value="{{ $c }}" {{ old('combustible', $vehiculo->combustible ?? '') === $c ? 'selected' : '' }}>{{ ucfirst($c) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block font-semibold {{ $label }}">Fecha de Última Revisión Técnica</label>
        <input type="date" name="ultima_revision_tecnica" value="{{ old('ultima_revision_tecnica', $vehiculo->ultima_revision_tecnica ?? '') }}" class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>
    <div>
        <label class="block font-semibold {{ $label }}">Estado del Vehículo</label>
        <select name="estado" class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            @foreach(['Activo','En Mantenimiento','Fuera de Servicio'] as $estado)
                <option value="{{ $estado }}" {{ old('estado', $vehiculo->estado ?? 'Activo') === $estado ? 'selected' : '' }}>{{ $estado }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-2">
        <h3 class="font-semibold {{ $sectionTitle }} mt-4">Propietario y Conductor</h3>
        <p class="{{ $sectionSub }} text-sm mb-2">Asigna propietario y (opcional) conductor relacionado.</p>
    </div>
    
    <!-- Autocomplete Propietario -->
    <div class="md:col-span-2" x-data="propietarioAutocomplete('{{ $propietarioSeleccionado }}')">
        <label class="block font-semibold {{ $label }}">Propietario</label>
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
                placeholder="Buscar por nombre, cédula, teléfono..."
                class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required>
            <input type="hidden" name="propietario_nombre" x-model="valorSeleccionado">
            
            <div x-show="mostrarDropdown && resultados.length > 0" 
                 x-transition
                 class="absolute z-50 w-full mt-1 {{ $bgDropdown }} border rounded-md shadow-lg max-h-60 overflow-auto"
                 style="display: none;">
                <template x-for="(resultado, index) in resultados" :key="resultado.id">
                    <div 
                        @click="seleccionar(index)"
                        @mouseenter="selectedIndex = index"
                        :class="selectedIndex === index ? '{{ $hoverDropdown }}' : ''"
                        class="px-4 py-2 cursor-pointer {{ $textDropdown }} border-b {{ $isDark ? 'border-gray-700' : 'border-gray-200' }} last:border-b-0">
                        <div class="font-semibold" x-text="resultado.nombre_completo"></div>
                        <div class="text-sm {{ $isDark ? 'text-gray-400' : 'text-gray-600' }}" x-text="resultado.label"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Autocomplete Conductor -->
    <div class="md:col-span-2" x-data="conductorAutocomplete('{{ $conductorSeleccionado }}', '{{ $conductorNombre }}')">
        <label class="block font-semibold {{ $label }}">Conductor (Opcional)</label>
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
                placeholder="Buscar por nombre, cédula, teléfono..."
                class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <input type="hidden" name="conductor_id" x-model="valorSeleccionado">
            
            <div x-show="mostrarDropdown && resultados.length > 0" 
                 x-transition
                 class="absolute z-50 w-full mt-1 {{ $bgDropdown }} border rounded-md shadow-lg max-h-60 overflow-auto"
                 style="display: none;">
                <template x-for="(resultado, index) in resultados" :key="resultado.id">
                    <div 
                        @click="seleccionar(index)"
                        @mouseenter="selectedIndex = index"
                        :class="selectedIndex === index ? '{{ $hoverDropdown }}' : ''"
                        class="px-4 py-2 cursor-pointer {{ $textDropdown }} border-b {{ $isDark ? 'border-gray-700' : 'border-gray-200' }} last:border-b-0">
                        <div class="font-semibold" x-text="resultado.label"></div>
                        <div class="text-sm {{ $isDark ? 'text-gray-400' : 'text-gray-600' }}" x-text="(resultado.celular ? 'Tel: ' + resultado.celular : '')"></div>
                    </div>
                </template>
            </div>
            
            <button type="button" 
                    @click="limpiar()"
                    x-show="valorSeleccionado"
                    class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>

    <div class="md:col-span-2">
        <label class="block font-semibold {{ $label }}">Foto del Vehículo</label>
        @if(!empty($vehiculo?->foto))
            @php
                $fotoUrl = \Illuminate\Support\Str::startsWith($vehiculo->foto, 'uploads/') ? asset($vehiculo->foto) : asset('storage/' . $vehiculo->foto);
            @endphp
            <div class="mb-2">
                <img src="{{ $fotoUrl }}" alt="Foto vehículo" class="w-40 h-40 object-cover rounded border {{ $isDark ? 'border-gray-700' : 'border-gray-200' }}">
            </div>
        @endif
        <input type="file" name="foto" accept="image/*" class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>
</div>

@push('scripts')
<script>
function propietarioAutocomplete(valorInicial = '') {
    return {
        search: valorInicial,
        resultados: [],
        mostrarDropdown: false,
        selectedIndex: -1,
        valorSeleccionado: valorInicial,
        
        buscar() {
            if (this.search.length < 2) {
                this.resultados = [];
                this.mostrarDropdown = false;
                return;
            }
            
            fetch(`{{ route('api.propietarios.search') }}?q=${encodeURIComponent(this.search)}`)
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
                this.search = resultado.label;
                this.valorSeleccionado = resultado.nombre_completo;
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

function conductorAutocomplete(valorInicial = '', nombreInicial = '') {
    return {
        search: nombreInicial || '',
        resultados: [],
        mostrarDropdown: false,
        selectedIndex: -1,
        valorSeleccionado: valorInicial,
        
        buscar() {
            if (this.search.length < 2) {
                this.resultados = [];
                this.mostrarDropdown = false;
                return;
            }
            
            fetch(`{{ route('api.conductores.search') }}?q=${encodeURIComponent(this.search)}`)
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
                this.search = resultado.label;
                this.valorSeleccionado = resultado.id;
                this.mostrarDropdown = false;
            }
        },
        
        limpiar() {
            this.search = '';
            this.valorSeleccionado = '';
            this.resultados = [];
            this.mostrarDropdown = false;
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
@endpush
