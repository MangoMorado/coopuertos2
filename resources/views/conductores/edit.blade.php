<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Editar Conductor
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-6">
        @if ($errors->any())
            <div class="bg-red-100 dark:bg-red-900 border border-red-300 dark:border-red-700 text-red-800 dark:text-red-200 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
            <form method="POST" action="{{ route('conductores.update', $conductor->id) }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                @method('PUT')

                <!-- Nombres y Apellidos -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Nombres</label>
                        <input type="text" name="nombres" value="{{ old('nombres', $conductor->nombres) }}" required
                               class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Apellidos</label>
                        <input type="text" name="apellidos" value="{{ old('apellidos', $conductor->apellidos) }}" required
                               class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Cédula y Tipo de Conductor -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Cédula</label>
                    <input type="text" name="cedula" value="{{ old('cedula', $conductor->cedula) }}" required
                           class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo de Conductor</label>
                    <select name="conductor_tipo" required
                            class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Seleccione</option>
                            <option value="A" {{ old('conductor_tipo', $conductor->conductor_tipo) == 'A' ? 'selected' : '' }}>Tipo A (Camionetas)</option>
                            <option value="B" {{ old('conductor_tipo', $conductor->conductor_tipo) == 'B' ? 'selected' : '' }}>Tipo B (Busetas)</option>
                        </select>
                    </div>
                </div>

                <!-- RH -->
                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">RH</label>
                    <select name="rh" required
                            class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccione</option>
                        @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $grupo)
                            <option value="{{ $grupo }}" {{ old('rh', $conductor->rh) == $grupo ? 'selected' : '' }}>{{ $grupo }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Número Interno y Celular -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Número Interno</label>
                        <input type="text" name="numero_interno" value="{{ old('numero_interno', $conductor->numero_interno) }}"
                               class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Celular</label>
                        <input type="text" name="celular" value="{{ old('celular', $conductor->celular) }}"
                               class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Correo y Fecha de Nacimiento -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Correo</label>
                        <input type="email" name="correo" value="{{ old('correo', $conductor->correo) }}"
                               class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Fecha de Nacimiento</label>
                        <input type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento', $conductor->fecha_nacimiento ? $conductor->fecha_nacimiento->format('Y-m-d') : '') }}"
                               class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Otra Profesión y Nivel de Estudios -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">¿Sabe otra profesión?</label>
                        <input type="text" name="otra_profesion" value="{{ old('otra_profesion', $conductor->otra_profesion) }}"
                               class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Nivel de Estudios</label>
                        <input type="text" name="nivel_estudios" value="{{ old('nivel_estudios', $conductor->nivel_estudios) }}"
                               class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Foto -->
                <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Foto del Conductor</label>
                        @if($conductor->foto)
                            <div class="mb-2">
                                @php
                                    $fotoUrl = \App\Helpers\StorageHelper::getFotoUrl($conductor->foto);
                                @endphp
                                <img src="{{ $fotoUrl }}" alt="Foto Conductor" class="w-32 h-32 object-cover rounded border border-gray-200 dark:border-gray-700">
                            </div>
                        @endif
                        <input type="file" id="foto-input" name="foto" accept="image/*"
                               class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm">
                        
                        <!-- Preview de imagen recortada -->
                        <div id="preview-container" class="hidden mt-3">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Vista previa de nueva imagen:</p>
                            <img id="preview-image" src="" alt="Preview" class="w-32 h-32 object-cover rounded border border-gray-300 dark:border-gray-600">
                        </div>
                    </div>
                </div>

                <!-- Modal de recorte -->
                <div id="cropper-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full mx-4 border border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Recortar Imagen (1:1)</h3>
                        <div id="cropper-container" class="mb-4">
                            <img id="cropper-image" src="" alt="Imagen a recortar" style="max-width: 100%; max-height: 500px;">
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" id="cancel-crop" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded hover:bg-gray-400 dark:hover:bg-gray-500">
                                Cancelar
                            </button>
                            <button type="button" id="crop-btn" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded">
                                Recortar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Estado -->
                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Estado</label>
                    <select name="estado" class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="activo" {{ old('estado', $conductor->estado) == 'activo' ? 'selected' : '' }}>Activo</option>
                        <option value="inactivo" {{ old('estado', $conductor->estado) == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <a href="{{ route('conductores.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md shadow-sm">
                        Cancelar
                    </a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md shadow-sm">
                        Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('js/image-cropper.js') }}"></script>
    @endpush
</x-app-layout>
