<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block font-semibold text-gray-700 dark:text-gray-300">Tipo de Identificación</label>
        <select name="tipo_identificacion" class="mt-1 block w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            <option value="">Seleccione</option>
            <option value="Cédula de Ciudadanía" {{ old('tipo_identificacion', $propietario->tipo_identificacion ?? '') === 'Cédula de Ciudadanía' ? 'selected' : '' }}>Cédula de Ciudadanía</option>
            <option value="RUC/NIT" {{ old('tipo_identificacion', $propietario->tipo_identificacion ?? '') === 'RUC/NIT' ? 'selected' : '' }}>RUC/NIT</option>
            <option value="Pasaporte" {{ old('tipo_identificacion', $propietario->tipo_identificacion ?? '') === 'Pasaporte' ? 'selected' : '' }}>Pasaporte</option>
        </select>
    </div>
    <div>
        <label class="block font-semibold text-gray-700 dark:text-gray-300">Número de Identificación</label>
        <input type="text" name="numero_identificacion" value="{{ old('numero_identificacion', $propietario->numero_identificacion ?? '') }}" class="mt-1 block w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
    </div>
    <div class="md:col-span-2">
        <label class="block font-semibold text-gray-700 dark:text-gray-300">Nombre Completo o Razón Social</label>
        <input type="text" name="nombre_completo" value="{{ old('nombre_completo', $propietario->nombre_completo ?? '') }}" class="mt-1 block w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
    </div>
    <div>
        <label class="block font-semibold text-gray-700 dark:text-gray-300">Tipo de Propietario</label>
        <select name="tipo_propietario" class="mt-1 block w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            <option value="">Seleccione</option>
            <option value="Persona Natural" {{ old('tipo_propietario', $propietario->tipo_propietario ?? '') === 'Persona Natural' ? 'selected' : '' }}>Persona Natural</option>
            <option value="Persona Jurídica" {{ old('tipo_propietario', $propietario->tipo_propietario ?? '') === 'Persona Jurídica' ? 'selected' : '' }}>Persona Jurídica</option>
        </select>
    </div>
    <div>
        <label class="block font-semibold text-gray-700 dark:text-gray-300">Estado</label>
        <select name="estado" class="mt-1 block w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            <option value="Activo" {{ old('estado', $propietario->estado ?? 'Activo') === 'Activo' ? 'selected' : '' }}>Activo</option>
            <option value="Inactivo" {{ old('estado', $propietario->estado ?? 'Activo') === 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
        </select>
    </div>
    <div class="md:col-span-2">
        <label class="block font-semibold text-gray-700 dark:text-gray-300">Dirección de Contacto</label>
        <textarea name="direccion_contacto" rows="3" class="mt-1 block w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('direccion_contacto', $propietario->direccion_contacto ?? '') }}</textarea>
    </div>
    <div>
        <label class="block font-semibold text-gray-700 dark:text-gray-300">Teléfono de Contacto</label>
        <input type="text" name="telefono_contacto" value="{{ old('telefono_contacto', $propietario->telefono_contacto ?? '') }}" class="mt-1 block w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>
    <div>
        <label class="block font-semibold text-gray-700 dark:text-gray-300">Correo Electrónico de Contacto</label>
        <input type="email" name="correo_electronico" value="{{ old('correo_electronico', $propietario->correo_electronico ?? '') }}" class="mt-1 block w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>
</div>
