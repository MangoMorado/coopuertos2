@php
    $theme = Auth::user()->theme ?? 'light';
    $isDark = $theme === 'dark';
    $textTitle = $isDark ? 'text-gray-100' : 'text-gray-800';
    $bgCard = $isDark ? 'bg-gray-800' : 'bg-white';
    $bgInput = $isDark ? 'bg-gray-700 border-gray-600 text-gray-100' : 'bg-white border-gray-300 text-gray-900';
    $bgSuccess = $isDark ? 'bg-green-900 border-green-700 text-green-200' : 'bg-green-100 border-green-300 text-green-800';
    $bgError = $isDark ? 'bg-red-900 border-red-700 text-red-200' : 'bg-red-100 border-red-300 text-red-800';
    $borderCard = $isDark ? 'border-gray-700' : 'border-gray-200';
    $textBody = $isDark ? 'text-gray-300' : 'text-gray-700';
    $bgHover = $isDark ? 'hover:bg-gray-700' : 'hover:bg-gray-50';
    $hoverRow = $isDark ? 'hover:bg-gray-700' : 'hover:bg-gray-50';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl {{ $textTitle }} leading-tight">
            Editor Visual del Formulario PQRS
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-6" x-data="formBuilder()">
        @if (session('success'))
            <div class="mb-4 {{ $bgSuccess }} border px-4 py-2 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 {{ $bgError }} border px-4 py-2 rounded">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Panel de Campos -->
            <div class="lg:col-span-2">
                <div class="{{ $bgCard }} shadow border {{ $borderCard }} rounded-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold {{ $textTitle }}">Campos del Formulario</h3>
                        <a href="{{ route('pqrs.form.public') }}" target="_blank"
                           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-md transition text-sm flex items-center space-x-2">
                           <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                               <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                               <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                           </svg>
                           <span>Vista Previa</span>
                        </a>
                    </div>

                    <form method="POST" action="{{ route('pqrs.update-template') }}" id="form-builder-form">
                        @csrf
                        <div class="space-y-4" id="fields-container">
                            <template x-for="(field, index) in fields" :key="field.id">
                                <div class="border {{ $borderCard }} rounded-lg p-4 {{ $bgHover }} transition"
                                     :class="editingIndex === index ? 'ring-2 ring-blue-500' : ''">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center space-x-2 flex-1">
                                            <button type="button" 
                                                    @click="moveField(index, -1)"
                                                    :disabled="index === 0"
                                                    class="text-gray-400 hover:text-gray-600 disabled:opacity-30">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                </svg>
                                            </button>
                                            <button type="button" 
                                                    @click="moveField(index, 1)"
                                                    :disabled="index === fields.length - 1"
                                                    class="text-gray-400 hover:text-gray-600 disabled:opacity-30">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </button>
                                            <span class="text-sm font-medium {{ $textTitle }}" x-text="field.label || 'Campo sin nombre'"></span>
                                            <span class="text-xs px-2 py-1 rounded {{ $isDark ? 'bg-blue-900 text-blue-200' : 'bg-blue-100 text-blue-800' }}" x-text="field.type"></span>
                                            <span x-show="field.required" class="text-red-500 text-xs">*</span>
                                        </div>
                                        <div class="flex space-x-2">
                                            <button type="button" 
                                                    @click="editField(index)"
                                                    class="text-blue-600 hover:text-blue-800 flex items-center space-x-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                </svg>
                                                <span>Editar</span>
                                            </button>
                                            <button type="button" 
                                                    @click="deleteField(index)"
                                                    class="text-red-600 hover:text-red-800 flex items-center space-x-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                                <span>Eliminar</span>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Inputs ocultos para guardar -->
                                    <input type="hidden" :name="'fields[' + index + '][id]'" :value="field.id">
                                    <input type="hidden" :name="'fields[' + index + '][name]'" :value="field.name">
                                    <input type="hidden" :name="'fields[' + index + '][label]'" :value="field.label">
                                    <input type="hidden" :name="'fields[' + index + '][type]'" :value="field.type">
                                    <input type="hidden" :name="'fields[' + index + '][required]'" :value="field.required ? 1 : 0">
                                    <input type="hidden" :name="'fields[' + index + '][placeholder]'" :value="field.placeholder || ''">
                                    <input type="hidden" :name="'fields[' + index + '][order]'" :value="index">
                                    <input type="hidden" :name="'fields[' + index + '][enabled]'" :value="1">
                                    
                                    <!-- Campos adicionales según el tipo -->
                                    <template x-if="field.type === 'select' && field.options">
                                        <input type="hidden" :name="'fields[' + index + '][options]'" :value="JSON.stringify(field.options)">
                                    </template>
                                    
                                    <template x-if="field.type === 'textarea'">
                                        <input type="hidden" :name="'fields[' + index + '][rows]'" :value="field.rows || 4">
                                    </template>
                                    
                                    <template x-if="field.type === 'file'">
                                        <div>
                                            <template x-if="field.multiple !== undefined">
                                                <input type="hidden" :name="'fields[' + index + '][multiple]'" :value="field.multiple ? 1 : 0">
                                            </template>
                                            <template x-if="field.accept">
                                                <input type="hidden" :name="'fields[' + index + '][accept]'" :value="field.accept">
                                            </template>
                                            <template x-if="field.help_text">
                                                <input type="hidden" :name="'fields[' + index + '][help_text]'" :value="field.help_text">
                                            </template>
                                        </div>
                                    </template>
                                    
                                    <template x-if="field.type === 'rating'">
                                        <input type="hidden" :name="'fields[' + index + '][max_rating]'" :value="field.max_rating || 5">
                                    </template>
                                    
                                    <template x-if="field.type === 'autocomplete'">
                                        <div>
                                            <input type="hidden" :name="'fields[' + index + '][autocomplete_source]'" :value="field.autocomplete_source || 'vehiculos'">
                                            <input type="hidden" :name="'fields[' + index + '][autocomplete_columns]'" :value="JSON.stringify(field.autocomplete_columns || [])">
                                            <input type="hidden" :name="'fields[' + index + '][autocomplete_label_field]'" :value="field.autocomplete_label_field || ''">
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <a href="{{ route('pqrs.index') }}"
                               class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg shadow-md transition">
                               Cancelar
                            </a>
                            <button type="submit"
                                    class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg shadow-md transition flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                <span>Guardar Cambios</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Panel de Edición -->
            <div class="lg:col-span-1">
                <div x-show="editingIndex !== null" 
                     x-transition
                     class="{{ $bgCard }} shadow border {{ $borderCard }} rounded-lg p-6 sticky top-4">
                    <h3 class="text-lg font-semibold {{ $textTitle }} mb-4">Editar Campo</h3>
                    
                    <template x-if="editingIndex !== null && fields[editingIndex]">
                        <div class="space-y-4">
                            <!-- Label -->
                            <div>
                                <label class="block text-sm font-medium {{ $textTitle }} mb-1">Etiqueta del Campo</label>
                                <input type="text" 
                                       x-model="fields[editingIndex].label"
                                       class="w-full {{ $bgInput }} border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <!-- Tipo -->
                            <div>
                                <label class="block text-sm font-medium {{ $textTitle }} mb-1">Tipo de Campo</label>
                                <select x-model="fields[editingIndex].type"
                                        class="w-full {{ $bgInput }} border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="text">Texto</option>
                                    <option value="email">Correo</option>
                                    <option value="tel">Teléfono</option>
                                    <option value="date">Fecha</option>
                                    <option value="textarea">Área de Texto</option>
                                    <option value="select">Lista Desplegable</option>
                                    <option value="rating">Calificación (Estrellas)</option>
                                    <option value="file">Archivo</option>
                                    <option value="autocomplete">Búsqueda Automática</option>
                                    <option value="logo">Logo</option>
                                </select>
                            </div>
                            
                            <!-- Ruta del logo (solo para tipo logo) -->
                            <template x-if="fields[editingIndex].type === 'logo'">
                                <div>
                                    <label class="block text-sm font-medium {{ $textTitle }} mb-1">Ruta del Logo</label>
                                    <input type="text" 
                                           x-model="fields[editingIndex].logo_path"
                                           placeholder="/images/logo.svg"
                                           class="w-full {{ $bgInput }} border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <p class="text-xs {{ $textBody }} mt-1">Ruta relativa desde la carpeta public (ejemplo: /images/logo.svg)</p>
                                </div>
                            </template>

                            <!-- Placeholder -->
                            <div>
                                <label class="block text-sm font-medium {{ $textTitle }} mb-1">Texto de Ayuda (Placeholder)</label>
                                <input type="text" 
                                       x-model="fields[editingIndex].placeholder"
                                       class="w-full {{ $bgInput }} border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <!-- Requerido -->
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       x-model="fields[editingIndex].required"
                                       id="required-field"
                                       class="mr-2">
                                <label for="required-field" class="text-sm {{ $textBody }}">Campo obligatorio</label>
                            </div>

                            <!-- Opciones para select -->
                            <template x-if="fields[editingIndex].type === 'select'">
                                <div>
                                    <label class="block text-sm font-medium {{ $textTitle }} mb-1">Opciones (una por línea)</label>
                                    <textarea x-model="selectOptionsText"
                                              @input="updateSelectOptions()"
                                              rows="6"
                                              class="w-full {{ $bgInput }} border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                              placeholder="Opción 1&#10;Opción 2&#10;Opción 3"></textarea>
                                </div>
                            </template>

                            <!-- Configuración para autocomplete -->
                            <template x-if="fields[editingIndex].type === 'autocomplete'">
                                <div class="space-y-4">
                                    <!-- Selección de tabla -->
                                    <div>
                                        <label class="block text-sm font-medium {{ $textTitle }} mb-1">Tabla a buscar</label>
                                        <select x-model="fields[editingIndex].autocomplete_source"
                                                @change="updateAutocompleteColumns()"
                                                class="w-full {{ $bgInput }} border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="vehiculos">Vehículos</option>
                                            <option value="propietarios">Propietarios</option>
                                            <option value="conductores">Conductores</option>
                                        </select>
                                    </div>

                                    <!-- Selección de columnas -->
                                    <div>
                                        <label class="block text-sm font-medium {{ $textTitle }} mb-1">Columnas para búsqueda (marcar las que desea habilitar)</label>
                                        <div class="space-y-2 max-h-48 overflow-y-auto border {{ $borderCard }} rounded p-3">
                                            <template x-for="(column, key) in availableColumns[fields[editingIndex].autocomplete_source] || []" :key="key">
                                                <div class="flex items-center">
                                                    <input type="checkbox" 
                                                           :id="'column-' + editingIndex + '-' + key"
                                                           :value="column.value"
                                                           x-model="fields[editingIndex].autocomplete_columns"
                                                           class="mr-2">
                                                    <label :for="'column-' + editingIndex + '-' + key" class="text-sm {{ $textBody }}">
                                                        <span x-text="column.label"></span>
                                                        <span class="text-xs text-gray-400" x-text="'(' + column.value + ')'"></span>
                                                    </label>
                                                </div>
                                            </template>
                                        </div>
                                        <p class="text-xs {{ $textBody }} mt-1">La búsqueda buscará en todas las columnas seleccionadas</p>
                                    </div>

                                    <!-- Campo para mostrar en el resultado -->
                                    <div>
                                        <label class="block text-sm font-medium {{ $textTitle }} mb-1">Campo a mostrar en el resultado (label)</label>
                                        <select x-model="fields[editingIndex].autocomplete_label_field"
                                                class="w-full {{ $bgInput }} border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <template x-for="(column, key) in availableColumns[fields[editingIndex].autocomplete_source] || []" :key="key">
                                                <option :value="column.value" x-text="column.label"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                            </template>

                            <div class="pt-4 border-t {{ $borderCard }}">
                                <button type="button" 
                                        @click="editingIndex = null"
                                        class="w-full bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <div x-show="editingIndex === null" class="{{ $bgCard }} shadow border {{ $borderCard }} rounded-lg p-6">
                    <p class="text-sm {{ $textBody }} text-center mb-4">
                        Seleccione un campo para editarlo
                    </p>
                    
                    <div class="relative" x-data="{ showFieldTypes: false }">
                        <button type="button" 
                                @click="showFieldTypes = !showFieldTypes"
                                class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg shadow-md transition flex items-center justify-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            <span>Agregar Campo</span>
                        </button>
                        
                        <div x-show="showFieldTypes" 
                             @click.away="showFieldTypes = false"
                             x-transition
                             class="absolute z-10 w-full mt-2 {{ $bgCard }} border {{ $borderCard }} rounded-lg shadow-lg overflow-hidden"
                             style="display: none;">
                            <div class="py-1 max-h-64 overflow-y-auto">
                                <button type="button"
                                        @click="addField('text'); showFieldTypes = false"
                                        class="w-full text-left px-4 py-2 {{ $textBody }} {{ $hoverRow }} flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                                    </svg>
                                    <span>Texto</span>
                                </button>
                                <button type="button"
                                        @click="addField('email'); showFieldTypes = false"
                                        class="w-full text-left px-4 py-2 {{ $textBody }} {{ $hoverRow }} flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                    </svg>
                                    <span>Correo</span>
                                </button>
                                <button type="button"
                                        @click="addField('tel'); showFieldTypes = false"
                                        class="w-full text-left px-4 py-2 {{ $textBody }} {{ $hoverRow }} flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                    </svg>
                                    <span>Teléfono</span>
                                </button>
                                <button type="button"
                                        @click="addField('date'); showFieldTypes = false"
                                        class="w-full text-left px-4 py-2 {{ $textBody }} {{ $hoverRow }} flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                    </svg>
                                    <span>Fecha</span>
                                </button>
                                <button type="button"
                                        @click="addField('textarea'); showFieldTypes = false"
                                        class="w-full text-left px-4 py-2 {{ $textBody }} {{ $hoverRow }} flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                                    </svg>
                                    <span>Área de Texto</span>
                                </button>
                                <button type="button"
                                        @click="addField('select'); showFieldTypes = false"
                                        class="w-full text-left px-4 py-2 {{ $textBody }} {{ $hoverRow }} flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                    </svg>
                                    <span>Lista Desplegable</span>
                                </button>
                                <button type="button"
                                        @click="addField('rating'); showFieldTypes = false"
                                        class="w-full text-left px-4 py-2 {{ $textBody }} {{ $hoverRow }} flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                    <span>Calificación (Estrellas)</span>
                                </button>
                                <button type="button"
                                        @click="addField('file'); showFieldTypes = false"
                                        class="w-full text-left px-4 py-2 {{ $textBody }} {{ $hoverRow }} flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.122 2.122l7.81-7.81" />
                                    </svg>
                                    <span>Archivo</span>
                                </button>
                                <button type="button"
                                        @click="addField('autocomplete'); showFieldTypes = false"
                                        class="w-full text-left px-4 py-2 {{ $textBody }} {{ $hoverRow }} flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                    </svg>
                                    <span>Búsqueda Automática</span>
                                </button>
                                <button type="button"
                                        @click="addField('logo'); showFieldTypes = false"
                                        class="w-full text-left px-4 py-2 {{ $textBody }} {{ $hoverRow }} flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                    </svg>
                                    <span>Logo</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function formBuilder() {
            return {
                fields: @json($fields),
                editingIndex: null,
                selectOptionsText: '',
                availableColumns: {
                    vehiculos: [
                        { value: 'placa', label: 'Placa' },
                        { value: 'marca', label: 'Marca' },
                        { value: 'modelo', label: 'Modelo' },
                        { value: 'anio_fabricacion', label: 'Año de Fabricación' },
                        { value: 'chasis_vin', label: 'Chasis/VIN' },
                        { value: 'capacidad_pasajeros', label: 'Capacidad de Pasajeros' },
                        { value: 'tipo', label: 'Tipo' },
                    ],
                    propietarios: [
                        { value: 'numero_identificacion', label: 'Número de Identificación' },
                        { value: 'nombre_completo', label: 'Nombre Completo' },
                        { value: 'telefono_contacto', label: 'Teléfono de Contacto' },
                        { value: 'correo_electronico', label: 'Correo Electrónico' },
                        { value: 'direccion_contacto', label: 'Dirección de Contacto' },
                    ],
                    conductores: [
                        { value: 'cedula', label: 'Cédula' },
                        { value: 'nombres', label: 'Nombres' },
                        { value: 'apellidos', label: 'Apellidos' },
                        { value: 'celular', label: 'Celular' },
                        { value: 'correo', label: 'Correo' },
                        { value: 'vehiculo_placa', label: 'Placa del Vehículo' },
                        { value: 'numero_interno', label: 'Número Interno' },
                    ],
                },

                editField(index) {
                    this.editingIndex = index;
                    if (this.fields[index].options) {
                        this.selectOptionsText = this.fields[index].options.join('\n');
                    } else {
                        this.selectOptionsText = '';
                    }
                    // Inicializar columnas de autocomplete si no existen
                    if (this.fields[index].type === 'autocomplete') {
                        if (!this.fields[index].autocomplete_columns) {
                            this.fields[index].autocomplete_columns = [];
                        }
                        if (!this.fields[index].autocomplete_source) {
                            this.fields[index].autocomplete_source = 'vehiculos';
                        }
                        if (!this.fields[index].autocomplete_label_field) {
                            const source = this.fields[index].autocomplete_source;
                            const columns = this.availableColumns[source] || [];
                            if (columns.length > 0) {
                                this.fields[index].autocomplete_label_field = columns[0].value;
                            }
                        }
                    }
                },

                updateAutocompleteColumns() {
                    if (this.editingIndex !== null && this.fields[this.editingIndex].type === 'autocomplete') {
                        const source = this.fields[this.editingIndex].autocomplete_source;
                        // Reinicializar columnas seleccionadas si no existen
                        if (!this.fields[this.editingIndex].autocomplete_columns) {
                            this.fields[this.editingIndex].autocomplete_columns = [];
                        }
                        // Establecer el campo de label por defecto
                        const columns = this.availableColumns[source] || [];
                        if (columns.length > 0 && !this.fields[this.editingIndex].autocomplete_label_field) {
                            this.fields[this.editingIndex].autocomplete_label_field = columns[0].value;
                        }
                    }
                },

                deleteField(index) {
                    if (confirm('¿Está seguro de eliminar este campo?')) {
                        this.fields.splice(index, 1);
                        // Actualizar orden después de eliminar
                        this.fields.forEach((f, i) => {
                            f.order = i;
                        });
                        // Si el campo eliminado estaba siendo editado, cerrar el editor
                        if (this.editingIndex === index) {
                            this.editingIndex = null;
                        } else if (this.editingIndex > index) {
                            this.editingIndex--;
                        }
                    }
                },

                addField(type) {
                    const fieldTypes = {
                        text: {
                            id: 'campo_' + Date.now(),
                            name: 'campo_nuevo_' + Date.now(),
                            label: 'Nuevo Campo de Texto',
                            type: 'text',
                            required: false,
                            placeholder: 'Ingrese texto...',
                            value: '',
                            order: this.fields.length,
                            enabled: true,
                        },
                        email: {
                            id: 'campo_' + Date.now(),
                            name: 'campo_email_' + Date.now(),
                            label: 'Correo Electrónico',
                            type: 'email',
                            required: false,
                            placeholder: 'correo@ejemplo.com',
                            value: '',
                            order: this.fields.length,
                            enabled: true,
                        },
                        tel: {
                            id: 'campo_' + Date.now(),
                            name: 'campo_tel_' + Date.now(),
                            label: 'Teléfono',
                            type: 'tel',
                            required: false,
                            placeholder: 'Ingrese su teléfono',
                            value: '',
                            order: this.fields.length,
                            enabled: true,
                        },
                        date: {
                            id: 'campo_' + Date.now(),
                            name: 'campo_date_' + Date.now(),
                            label: 'Fecha',
                            type: 'date',
                            required: false,
                            placeholder: '',
                            value: '',
                            order: this.fields.length,
                            enabled: true,
                        },
                        textarea: {
                            id: 'campo_' + Date.now(),
                            name: 'campo_textarea_' + Date.now(),
                            label: 'Área de Texto',
                            type: 'textarea',
                            required: false,
                            placeholder: 'Escriba aquí...',
                            value: '',
                            order: this.fields.length,
                            enabled: true,
                            rows: 4,
                        },
                        select: {
                            id: 'campo_' + Date.now(),
                            name: 'campo_select_' + Date.now(),
                            label: 'Lista Desplegable',
                            type: 'select',
                            required: false,
                            placeholder: '',
                            value: '',
                            order: this.fields.length,
                            enabled: true,
                            options: ['Opción 1', 'Opción 2', 'Opción 3'],
                        },
                        rating: {
                            id: 'campo_' + Date.now(),
                            name: 'campo_rating_' + Date.now(),
                            label: 'Calificación',
                            type: 'rating',
                            required: false,
                            placeholder: '',
                            value: 0,
                            order: this.fields.length,
                            enabled: true,
                            max_rating: 5,
                        },
                        file: {
                            id: 'campo_' + Date.now(),
                            name: 'campo_file_' + Date.now(),
                            label: 'Archivo',
                            type: 'file',
                            required: false,
                            placeholder: '',
                            value: '',
                            order: this.fields.length,
                            enabled: true,
                            multiple: true,
                            accept: 'image/*,.pdf,.doc,.docx,video/*',
                            help_text: 'Formatos permitidos: Imágenes, Documentos, Videos. Máximo 10MB por archivo.',
                        },
                        autocomplete: {
                            id: 'campo_' + Date.now(),
                            name: 'campo_autocomplete_' + Date.now(),
                            label: 'Búsqueda Automática',
                            type: 'autocomplete',
                            required: false,
                            placeholder: 'Buscar...',
                            value: '',
                            order: this.fields.length,
                            enabled: true,
                            autocomplete_source: 'vehiculos',
                            autocomplete_columns: ['placa', 'marca', 'modelo'],
                            autocomplete_label_field: 'placa',
                        },
                        logo: {
                            id: 'campo_' + Date.now(),
                            name: 'logo',
                            label: 'Logo',
                            type: 'logo',
                            required: false,
                            placeholder: '',
                            value: '',
                            order: this.fields.length,
                            enabled: true,
                            logo_path: '/images/logo.svg',
                        },
                    };

                    const newField = fieldTypes[type];
                    if (newField) {
                        this.fields.push(newField);
                        // Editar el nuevo campo automáticamente
                        this.editField(this.fields.length - 1);
                    }
                },

                moveField(index, direction) {
                    const newIndex = index + direction;
                    if (newIndex >= 0 && newIndex < this.fields.length) {
                        const field = this.fields.splice(index, 1)[0];
                        this.fields.splice(newIndex, 0, field);
                        // Actualizar orden
                        this.fields.forEach((f, i) => {
                            f.order = i;
                        });
                    }
                },

                updateSelectOptions() {
                    if (this.editingIndex !== null) {
                        const options = this.selectOptionsText
                            .split('\n')
                            .map(line => line.trim())
                            .filter(line => line.length > 0);
                        this.fields[this.editingIndex].options = options;
                    }
                }
            };
        }
    </script>
</x-app-layout>
