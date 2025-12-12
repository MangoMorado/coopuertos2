<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Formulario PQRS Taquilla - Coopuertos</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans text-gray-900 antialiased bg-gray-50">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white shadow-lg rounded-lg p-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Formulario PQRS Taquilla</h1>
                <p class="text-gray-600 mb-6">Peticiones, Quejas, Reclamos y Sugerencias - Taquilla</p>

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

                <form method="POST" action="{{ route('pqrs.taquilla.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    <div class="flex justify-center my-6">
                        <img src="/images/logo.svg" alt="Logo" class="max-h-24 max-w-full object-contain">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                        <select name="tipo"  class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Seleccione...</option>
                            <option value="Peticiones" {{ old('tipo') === 'Peticiones' ? 'selected' : '' }}>Peticiones</option>
                            <option value="Quejas" {{ old('tipo') === 'Quejas' ? 'selected' : '' }}>Quejas</option>
                            <option value="Reclamos" {{ old('tipo') === 'Reclamos' ? 'selected' : '' }}>Reclamos</option>
                            <option value="Sugerencias" {{ old('tipo') === 'Sugerencias' ? 'selected' : '' }}>Sugerencias</option>
                            <option value="Otros" {{ old('tipo') === 'Otros' ? 'selected' : '' }}>Otros</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                        <input type="date" name="fecha" value="{{ old('fecha') }}"   class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hora</label>
                        <input type="time" name="hora" value="{{ old('hora') }}"   class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                        <input type="text" name="nombre" value="{{ old('nombre') }}"  placeholder="Ingrese su nombre completo" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sede</label>
                        <input type="text" name="sede" value="{{ old('sede') }}"  placeholder="Ingrese la sede" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Correo</label>
                        <input type="email" name="correo" value="{{ old('correo') }}"  placeholder="correo@ejemplo.com" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="tel" name="telefono" value="{{ old('telefono') }}"  placeholder="Ingrese su número de teléfono" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Califica el Servicio</label>
                        <div class="flex items-center space-x-2" x-data="{ rating: {{ old('calificacion') }} }">
                            <input type="hidden" name="calificacion" x-model="rating">
                            <button type="button" @click="rating = 1" class="focus:outline-none">
                                <svg class="w-8 h-8 transition-colors" :class="rating >= 1 ? 'text-yellow-400 fill-current' : 'text-gray-300'" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            </button>
                            <button type="button" @click="rating = 2" class="focus:outline-none">
                                <svg class="w-8 h-8 transition-colors" :class="rating >= 2 ? 'text-yellow-400 fill-current' : 'text-gray-300'" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            </button>
                            <button type="button" @click="rating = 3" class="focus:outline-none">
                                <svg class="w-8 h-8 transition-colors" :class="rating >= 3 ? 'text-yellow-400 fill-current' : 'text-gray-300'" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            </button>
                            <button type="button" @click="rating = 4" class="focus:outline-none">
                                <svg class="w-8 h-8 transition-colors" :class="rating >= 4 ? 'text-yellow-400 fill-current' : 'text-gray-300'" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            </button>
                            <button type="button" @click="rating = 5" class="focus:outline-none">
                                <svg class="w-8 h-8 transition-colors" :class="rating >= 5 ? 'text-yellow-400 fill-current' : 'text-gray-300'" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            </button>
                            <span class="ml-2 text-sm text-gray-600" x-show="rating > 0"><span x-text="rating"></span> / 5</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Comentario</label>
                        <textarea name="comentario" rows="4"  placeholder="Escriba sus comentarios aquí..." class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('comentario') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Adjuntos</label>
                        <p class="text-xs text-gray-500 mb-2">Formatos permitidos: Imágenes (jpg, png), Documentos (pdf, doc, docx), Videos (mp4, avi, mov). Máximo 10MB por archivo.</p>
                        <input type="file" name="adjuntos[]" multiple accept="image/*,.pdf,.doc,.docx,video/*"  class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow-md transition">
                            Enviar PQRS
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>