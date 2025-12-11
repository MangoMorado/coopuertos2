@php
    $isDark = $isDark ?? false;
    $theme = $theme ?? 'light';
    $bgInput = $isDark ? 'bg-gray-700 border-gray-600 text-gray-100' : 'bg-white border-gray-300 text-gray-900';
    $label = $isDark ? 'text-gray-300' : 'text-gray-700';
    $sectionTitle = $isDark ? 'text-gray-100' : 'text-gray-800';
    $sectionSub = $isDark ? 'text-gray-400' : 'text-gray-600';
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block font-semibold {{ $label }}">Tipo de Identificación</label>
        <select name="tipo_identificacion" class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            <option value="">Seleccione</option>
            <option value="Cédula de Ciudadanía" {{ old('tipo_identificacion', $propietario->tipo_identificacion ?? '') === 'Cédula de Ciudadanía' ? 'selected' : '' }}>Cédula de Ciudadanía</option>
            <option value="RUC/NIT" {{ old('tipo_identificacion', $propietario->tipo_identificacion ?? '') === 'RUC/NIT' ? 'selected' : '' }}>RUC/NIT</option>
            <option value="Pasaporte" {{ old('tipo_identificacion', $propietario->tipo_identificacion ?? '') === 'Pasaporte' ? 'selected' : '' }}>Pasaporte</option>
        </select>
    </div>
    <div>
        <label class="block font-semibold {{ $label }}">Número de Identificación</label>
        <input type="text" name="numero_identificacion" value="{{ old('numero_identificacion', $propietario->numero_identificacion ?? '') }}" class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
    </div>
    <div class="md:col-span-2">
        <label class="block font-semibold {{ $label }}">Nombre Completo o Razón Social</label>
        <input type="text" name="nombre_completo" value="{{ old('nombre_completo', $propietario->nombre_completo ?? '') }}" class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
    </div>
    <div>
        <label class="block font-semibold {{ $label }}">Tipo de Propietario</label>
        <select name="tipo_propietario" class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            <option value="">Seleccione</option>
            <option value="Persona Natural" {{ old('tipo_propietario', $propietario->tipo_propietario ?? '') === 'Persona Natural' ? 'selected' : '' }}>Persona Natural</option>
            <option value="Persona Jurídica" {{ old('tipo_propietario', $propietario->tipo_propietario ?? '') === 'Persona Jurídica' ? 'selected' : '' }}>Persona Jurídica</option>
        </select>
    </div>
    <div>
        <label class="block font-semibold {{ $label }}">Estado</label>
        <select name="estado" class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            <option value="Activo" {{ old('estado', $propietario->estado ?? 'Activo') === 'Activo' ? 'selected' : '' }}>Activo</option>
            <option value="Inactivo" {{ old('estado', $propietario->estado ?? 'Activo') === 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
        </select>
    </div>
    <div class="md:col-span-2">
        <label class="block font-semibold {{ $label }}">Dirección de Contacto</label>
        <textarea name="direccion_contacto" rows="3" class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('direccion_contacto', $propietario->direccion_contacto ?? '') }}</textarea>
    </div>
    <div>
        <label class="block font-semibold {{ $label }}">Teléfono de Contacto</label>
        <input type="text" name="telefono_contacto" value="{{ old('telefono_contacto', $propietario->telefono_contacto ?? '') }}" class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>
    <div>
        <label class="block font-semibold {{ $label }}">Correo Electrónico de Contacto</label>
        <input type="email" name="correo_electronico" value="{{ old('correo_electronico', $propietario->correo_electronico ?? '') }}" class="mt-1 block w-full {{ $bgInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>
</div>
