@php
    $isDark = $isDark ?? false;
    $theme = $theme ?? 'light';
    $bgInput = $isDark ? 'bg-gray-700 border-gray-600 text-gray-100' : 'bg-white border-gray-300 text-gray-900';
    $label = $isDark ? 'text-gray-300' : 'text-gray-700';
@endphp

@if ($errors->any())
    <div class="mb-4 bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ isset($pqr) ? route('pqrs.update', $pqr) : route('pqrs.store') }}" enctype="multipart/form-data" class="space-y-4">
    @csrf
    @if(isset($pqr))
        @method('PUT')
    @endif

    <!-- Fecha -->
    <div>
        <label class="block font-semibold {{ $label }}">Fecha</label>
        <input type="date" name="fecha" value="{{ old('fecha', isset($pqr) && $pqr->fecha ? $pqr->fecha->format('Y-m-d') : date('Y-m-d')) }}" 
               class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <!-- Nombre -->
    <div>
        <label class="block font-semibold {{ $label }}">Nombre <span class="text-red-500">*</span></label>
        <input type="text" name="nombre" value="{{ old('nombre', $pqr->nombre ?? '') }}" required
               class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <!-- Vehículo - Autocomplete -->
    <div x-data="vehiculoAutocomplete('{{ old('vehiculo_placa', $pqr->vehiculo_placa ?? '') }}')">
        <label class="block font-semibold {{ $label }}">Vehículo (Placa)</label>
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
                placeholder="Buscar por placa..."
                class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <input type="hidden" name="vehiculo_placa" x-model="valorSeleccionado">
            
            <div x-show="mostrarDropdown && resultados.length > 0" 
                 x-transition
                 class="absolute z-50 w-full mt-1 {{ $isDark ? 'bg-gray-800 border-gray-600' : 'bg-white border-gray-300' }} border rounded-md shadow-lg max-h-60 overflow-auto"
                 style="display: none;">
                <template x-for="(resultado, index) in resultados" :key="resultado.id">
                    <div 
                        @click="seleccionar(index)"
                        @mouseenter="selectedIndex = index"
                        :class="selectedIndex === index ? ($isDark ? 'bg-gray-700' : 'bg-gray-100') : ''"
                        class="px-4 py-2 cursor-pointer {{ $isDark ? 'text-gray-200' : 'text-gray-900' }} border-b {{ $isDark ? 'border-gray-700' : 'border-gray-200' }} last:border-b-0">
                        <div class="font-semibold" x-text="resultado.label"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Número de Tiquete -->
    <div>
        <label class="block font-semibold {{ $label }}">Número de Tiquete</label>
        <input type="text" name="numero_tiquete" value="{{ old('numero_tiquete', $pqr->numero_tiquete ?? '') }}"
               class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <!-- Correo y Teléfono -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block font-semibold {{ $label }}">Correo Electrónico</label>
            <input type="email" name="correo_electronico" value="{{ old('correo_electronico', $pqr->correo_electronico ?? '') }}"
                   class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block font-semibold {{ $label }}">Número de Teléfono</label>
            <input type="text" name="numero_telefono" value="{{ old('numero_telefono', $pqr->numero_telefono ?? '') }}"
                   class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
    </div>

    <!-- Calificación con Estrellas -->
    <div>
        <label class="block font-semibold {{ $label }} mb-2">Califica el Servicio</label>
        <div class="flex items-center space-x-2" x-data="{ rating: {{ old('calificacion', $pqr->calificacion ?? 0) }} }">
            <input type="hidden" name="calificacion" x-model="rating">
            @for($i = 1; $i <= 5; $i++)
                <button type="button" 
                        @click="rating = {{ $i }}"
                        class="focus:outline-none">
                    <svg class="w-8 h-8 transition-colors"
                         :class="rating >= {{ $i }} ? 'text-yellow-400 fill-current' : 'text-gray-300'"
                         fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                    </svg>
                </button>
            @endfor
            <span class="ml-2 text-sm {{ $isDark ? 'text-gray-400' : 'text-gray-600' }}" x-show="rating > 0">
                <span x-text="rating"></span> / 5
            </span>
        </div>
    </div>

    <!-- Tipo -->
    <div>
        <label class="block font-semibold {{ $label }}">Tipo <span class="text-red-500">*</span></label>
        <select name="tipo" required
                class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Seleccione...</option>
            @foreach(['Peticiones', 'Quejas', 'Reclamos', 'Sugerencias', 'Otros'] as $tipo)
                <option value="{{ $tipo }}" {{ old('tipo', $pqr->tipo ?? '') === $tipo ? 'selected' : '' }}>{{ $tipo }}</option>
            @endforeach
        </select>
    </div>

    <!-- Estado y Usuario Asignado -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block font-semibold {{ $label }}">Estado</label>
            <select name="estado"
                    class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach(['Radicada', 'En Trámite', 'En Espera de Información', 'Resuelta', 'Cerrada'] as $estado)
                    <option value="{{ $estado }}" {{ old('estado', $pqr->estado ?? 'Radicada') === $estado ? 'selected' : '' }}>{{ $estado }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block font-semibold {{ $label }}">Usuario Asignado</label>
            <select name="usuario_asignado_id"
                    class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Sin asignar</option>
                @foreach(\App\Models\User::orderBy('name')->get() as $user)
                    <option value="{{ $user->id }}" {{ old('usuario_asignado_id', $pqr->usuario_asignado_id ?? '') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }} ({{ $user->email }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Comentarios -->
    <div>
        <label class="block font-semibold {{ $label }}">Comentarios</label>
        <textarea name="comentarios" rows="4"
                  class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('comentarios', $pqr->comentarios ?? '') }}</textarea>
    </div>

    <!-- Adjuntos existentes -->
    @if(isset($pqr) && $pqr->adjuntos && count($pqr->adjuntos) > 0)
    <div>
        <label class="block font-semibold {{ $label }} mb-2">Adjuntos Actuales</label>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            @foreach($pqr->adjuntos as $index => $adjunto)
                @php
                    $ext = strtolower(pathinfo($adjunto, PATHINFO_EXTENSION));
                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
                    $url = asset($adjunto);
                @endphp
                <div class="relative">
                    @if($isImage)
                        <img src="{{ $url }}" alt="Adjunto {{ $index + 1 }}" class="w-full h-24 object-cover rounded border {{ $isDark ? 'border-gray-700' : 'border-gray-200' }}">
                    @else
                        <div class="p-3 border {{ $isDark ? 'border-gray-700' : 'border-gray-200' }} rounded text-center {{ $isDark ? 'text-gray-300' : 'text-gray-700' }}">
                            <svg class="w-8 h-8 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="text-xs">Doc</span>
                        </div>
                    @endif
                    <a href="{{ route('pqrs.adjunto.delete', ['pqr' => $pqr, 'index' => $index]) }}" 
                       onclick="return confirm('¿Eliminar este adjunto?')"
                       class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600">×</a>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Nuevos Adjuntos -->
    <div>
        <label class="block font-semibold {{ $label }}">Nuevos Adjuntos</label>
        <p class="text-xs {{ $isDark ? 'text-gray-400' : 'text-gray-500' }} mb-2">Formatos: Imágenes (jpg, png), Documentos (pdf, doc, docx), Videos (mp4, avi, mov). Máx. 10MB por archivo.</p>
        <input type="file" name="adjuntos[]" multiple accept="image/*,.pdf,.doc,.docx,video/*"
               class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div class="flex justify-end space-x-3 pt-4">
        <a href="{{ route('pqrs.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md shadow-sm">
            Cancelar
        </a>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md shadow-sm">
            {{ isset($pqr) ? 'Actualizar' : 'Guardar' }}
        </button>
    </div>
</form>

@push('scripts')
<script>
function vehiculoAutocomplete(valorInicial = '') {
    return {
        search: valorInicial,
        resultados: [],
        mostrarDropdown: false,
        selectedIndex: -1,
        valorSeleccionado: valorInicial,
        
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
                this.search = resultado.placa;
                this.valorSeleccionado = resultado.placa;
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
@endpush
