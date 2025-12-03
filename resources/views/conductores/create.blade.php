<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Registrar Nuevo Conductor
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-6">
        @if ($errors->any())
            <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white p-6 rounded-lg shadow-md">
            <form method="POST" action="{{ route('conductores.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <!-- Nombres y Apellidos -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Nombres</label>
                        <input type="text" name="nombres" value="{{ old('nombres') }}" required
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Apellidos</label>
                        <input type="text" name="apellidos" value="{{ old('apellidos') }}" required
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Cédula y Tipo de Conductor -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Cédula</label>
                        <input type="text" name="cedula" value="{{ old('cedula') }}" required
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Tipo de Conductor</label>
                        <select name="conductor_tipo" required
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Seleccione</option>
                            <option value="A" {{ old('conductor_tipo') == 'A' ? 'selected' : '' }}>Tipo A (Camionetas)</option>
                            <option value="B" {{ old('conductor_tipo') == 'B' ? 'selected' : '' }}>Tipo B (Busetas)</option>
                        </select>
                    </div>
                </div>

                <!-- RH y Vehículo Placa -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">RH</label>
                        <select name="rh" required
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Seleccione</option>
                            @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $grupo)
                                <option value="{{ $grupo }}" {{ old('rh') == $grupo ? 'selected' : '' }}>{{ $grupo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Vehículo Placa</label>
                        <input type="text" name="vehiculo_placa" value="{{ old('vehiculo_placa') }}"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Número Interno y Celular -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Número Interno</label>
                        <input type="text" name="numero_interno" value="{{ old('numero_interno') }}"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Celular</label>
                        <input type="text" name="celular" value="{{ old('celular') }}"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Correo y Fecha de Nacimiento -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<div class="relative flex flex-col">
    <label class="block font-semibold text-gray-700 mb-1 flex items-center">
        Correo
        <div class="ml-2 relative group">
            <div
                class="w-5 h-5 bg-blue-500 text-white text-xs font-bold rounded-full flex items-center justify-center cursor-pointer">
                i
            </div>
            <div
                class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-44 bg-gray-800 text-white text-xs rounded py-1 px-2 text-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg pointer-events-none">
                Sino tiene correo, dejar en blanco
            </div>
        </div>
    </label>
    <input type="email" name="correo" value="{{ old('correo') }}"
           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
</div>



                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Fecha de Nacimiento</label>
                        <input type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Otra Profesión y Nivel de Estudios -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">¿Sabe otra profesión?</label>
                        <input type="text" name="otra_profesion" value="{{ old('otra_profesion') }}"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Nivel de Estudios</label>
                        <input type="text" name="nivel_estudios" value="{{ old('nivel_estudios') }}"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Licencia, Vencimiento y Foto -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">


                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Foto del Conductor</label>
                        <input type="file" name="foto" accept="image/*"
                               class="w-full border-gray-300 rounded-lg shadow-sm">
                    </div>
                </div>

                <!-- Estado -->
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Estado</label>
                    <select name="estado" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="activo" {{ old('estado') == 'activo' ? 'selected' : '' }}>Activo</option>
                        <option value="inactivo" {{ old('estado') == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <a href="{{ route('conductores.index') }}" class="btn btn-gray">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-blue">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
