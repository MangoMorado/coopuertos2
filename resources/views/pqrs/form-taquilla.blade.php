@php
    $theme = Auth::user()->theme ?? 'light';
    $isDark = $theme === 'dark';
    $bgInput = $isDark ? 'bg-gray-700 border-gray-600 text-gray-100' : 'bg-white border-gray-300 text-gray-900';
    $label = $isDark ? 'text-gray-300' : 'text-gray-700';
@endphp

@if ($errors->any())
    <div class="mb-4 {{ $isDark ? 'bg-red-900 border-red-700 text-red-200' : 'bg-red-100 border-red-300 text-red-800' }} border px-4 py-3 rounded">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ isset($pqrTaquilla) ? route('pqrs-taquilla.update', $pqrTaquilla) : route('pqrs.taquilla.store') }}" enctype="multipart/form-data" class="space-y-4">
    @csrf
    @if(isset($pqrTaquilla))
        @method('PUT')
    @endif

    <!-- Fecha y Hora -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block font-semibold {{ $label }}">Fecha <span class="text-red-500">*</span></label>
            <input type="date" name="fecha" value="{{ old('fecha', isset($pqrTaquilla) && $pqrTaquilla->fecha ? $pqrTaquilla->fecha->format('Y-m-d') : date('Y-m-d')) }}" required
                   class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block font-semibold {{ $label }}">Hora <span class="text-red-500">*</span></label>
            <input type="time" name="hora" value="{{ old('hora', isset($pqrTaquilla) && $pqrTaquilla->hora ? \Carbon\Carbon::parse($pqrTaquilla->hora)->format('H:i') : date('H:i')) }}" required
                   class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
    </div>

    <!-- Nombre -->
    <div>
        <label class="block font-semibold {{ $label }}">Nombre <span class="text-red-500">*</span></label>
        <input type="text" name="nombre" value="{{ old('nombre', $pqrTaquilla->nombre ?? '') }}" required
               class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <!-- Sede -->
    <div>
        <label class="block font-semibold {{ $label }}">Sede</label>
        <input type="text" name="sede" value="{{ old('sede', $pqrTaquilla->sede ?? '') }}"
               class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <!-- Correo y Teléfono -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block font-semibold {{ $label }}">Correo</label>
            <input type="email" name="correo" value="{{ old('correo', $pqrTaquilla->correo ?? '') }}"
                   class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block font-semibold {{ $label }}">Teléfono</label>
            <input type="tel" name="telefono" value="{{ old('telefono', $pqrTaquilla->telefono ?? '') }}"
                   class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
    </div>

    <!-- Tipo -->
    <div>
        <label class="block font-semibold {{ $label }}">Tipo <span class="text-red-500">*</span></label>
        <select name="tipo" required
                class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Seleccione...</option>
            @foreach(['Peticiones', 'Quejas', 'Reclamos', 'Sugerencias', 'Otros'] as $tipo)
                <option value="{{ $tipo }}" {{ old('tipo', $pqrTaquilla->tipo ?? '') === $tipo ? 'selected' : '' }}>{{ $tipo }}</option>
            @endforeach
        </select>
    </div>

    <!-- Calificación con Estrellas -->
    <div>
        <label class="block font-semibold {{ $label }} mb-2">Califica el Servicio</label>
        <div class="flex items-center space-x-2" x-data="{ rating: {{ old('calificacion', $pqrTaquilla->calificacion ?? 0) }} }">
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

    <!-- Estado y Usuario Asignado (solo en edición) -->
    @if(isset($pqrTaquilla))
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block font-semibold {{ $label }}">Estado</label>
            <select name="estado"
                    class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach(['Radicada', 'En Trámite', 'En Espera de Información', 'Resuelta', 'Cerrada'] as $estado)
                    <option value="{{ $estado }}" {{ old('estado', $pqrTaquilla->estado ?? 'Radicada') === $estado ? 'selected' : '' }}>{{ $estado }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block font-semibold {{ $label }}">Usuario Asignado</label>
            <select name="usuario_asignado_id"
                    class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Sin asignar</option>
                @foreach(\App\Models\User::orderBy('name')->get() as $user)
                    <option value="{{ $user->id }}" {{ old('usuario_asignado_id', $pqrTaquilla->usuario_asignado_id ?? '') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }} ({{ $user->email }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    @endif

    <!-- Comentario -->
    <div>
        <label class="block font-semibold {{ $label }}">Comentario</label>
        <textarea name="comentario" rows="4"
                  class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('comentario', $pqrTaquilla->comentario ?? '') }}</textarea>
    </div>

    <!-- Adjuntos existentes (solo en edición) -->
    @if(isset($pqrTaquilla) && $pqrTaquilla->adjuntos && count($pqrTaquilla->adjuntos) > 0)
    <div>
        <label class="block font-semibold {{ $label }} mb-2">Adjuntos Actuales</label>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            @foreach($pqrTaquilla->adjuntos as $index => $adjunto)
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
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Nuevos Adjuntos -->
    <div>
        <label class="block font-semibold {{ $label }}">Adjuntos</label>
        <p class="text-xs {{ $isDark ? 'text-gray-400' : 'text-gray-500' }} mb-2">Formatos: Imágenes (jpg, png), Documentos (pdf, doc, docx), Videos (mp4, avi, mov). Máx. 10MB por archivo.</p>
        <input type="file" name="adjuntos[]" multiple accept="image/*,.pdf,.doc,.docx,video/*"
               class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div class="flex justify-end space-x-3 pt-4">
        <a href="{{ route('pqrs.index') }}"
           class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg shadow-md transition">
            Cancelar
        </a>
        <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow-md transition">
            {{ isset($pqrTaquilla) ? 'Actualizar' : 'Crear' }} PQRS
        </button>
    </div>
</form>
