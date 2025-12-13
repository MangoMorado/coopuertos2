@php
    $theme = Auth::user()->theme ?? 'light';
    $isDark = $theme === 'dark';
    $textTitle = $isDark ? 'text-gray-100' : 'text-gray-800';
    $bgCard = $isDark ? 'bg-gray-800' : 'bg-white';
    $bgInput = $isDark ? 'bg-gray-700 border-gray-600 text-gray-100' : 'bg-white border-gray-300 text-gray-900';
    $borderCard = $isDark ? 'border-gray-700' : 'border-gray-200';
    $textBody = $isDark ? 'text-gray-300' : 'text-gray-700';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl {{ $textTitle }} leading-tight">
            Personalizar Plantilla de Carnet
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-6" x-data="carnetEditor()">
        @if (session('success'))
            <div class="mb-4 {{ $isDark ? 'bg-green-900 border-green-700 text-green-200' : 'bg-green-100 border-green-300 text-green-800' }} border px-4 py-2 rounded">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('carnets.guardar-plantilla') }}" enctype="multipart/form-data" id="carnet-form">
            @csrf
            
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Panel de Variables (Lateral Izquierdo) -->
                <div class="lg:col-span-1">
                    <div class="{{ $bgCard }} shadow border {{ $borderCard }} rounded-lg p-6 sticky top-4">
                        <h3 class="text-lg font-semibold {{ $textTitle }} mb-4">Variables Disponibles</h3>
                        
                        <div class="space-y-2 max-h-[600px] overflow-y-auto">
                            <template x-for="(label, key) in variables" :key="key">
                                <div class="flex items-center space-x-2 p-2 rounded {{ $isDark ? 'hover:bg-gray-700' : 'hover:bg-gray-50' }} transition">
                                    <input type="checkbox" 
                                           :id="'var_' + key"
                                           x-model="variablesConfig[key].activo"
                                           @change="updateVariableConfig(key)"
                                           class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                                    <label :for="'var_' + key" 
                                           class="flex-1 text-sm {{ $textBody }} cursor-pointer"
                                           x-text="label"></label>
                                    <button type="button"
                                            @click="selectVariable(key)"
                                            :class="selectedVariable === key ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                                            class="px-2 py-1 text-xs rounded transition">
                                        Posicionar
                                    </button>
                                </div>
                            </template>
                        </div>

                        <div class="mt-6 pt-6 border-t {{ $borderCard }}">
                            <h4 class="text-sm font-semibold {{ $textTitle }} mb-3">Configuración de Variable Seleccionada</h4>
                            <template x-if="selectedVariable">
                                <div class="space-y-3">
                                    <template x-if="selectedVariable !== 'foto'">
                                        <div>
                                            <label class="block text-xs {{ $textBody }} mb-1">Tamaño de Fuente</label>
                                            <input type="number" 
                                                   x-model="variablesConfig[selectedVariable].fontSize"
                                                   min="8" 
                                                   max="300" 
                                                   value="14"
                                                   class="w-full {{ $bgInput }} border rounded px-2 py-1 text-sm">
                                        </div>
                                    </template>
                                    <template x-if="selectedVariable === 'foto' || selectedVariable === 'qr_code'">
                                        <div>
                                            <label class="block text-xs {{ $textBody }} mb-1">Tamaño (1:1)</label>
                                            <input type="number" 
                                                   x-model="variablesConfig[selectedVariable].size"
                                                   min="50" 
                                                   max="500" 
                                                   :value="variablesConfig[selectedVariable].size || 100"
                                                   @input="updateSize()"
                                                   class="w-full {{ $bgInput }} border rounded px-2 py-1 text-sm">
                                            <p class="text-xs {{ $textBody }} mt-1">Tamaño en píxeles (ancho = alto)</p>
                                        </div>
                                    </template>
                                    <div>
                                        <label class="block text-xs {{ $textBody }} mb-1">Color</label>
                                        <input type="color" 
                                               x-model="variablesConfig[selectedVariable].color"
                                               value="#000000"
                                               class="w-full h-8 rounded border {{ $borderCard }}">
                                    </div>
                                    <div>
                                        <label class="block text-xs {{ $textBody }} mb-1">Fuente</label>
                                        <select x-model="variablesConfig[selectedVariable].fontFamily"
                                                class="w-full {{ $bgInput }} border rounded px-2 py-1 text-sm">
                                            <option value="Arial">Arial</option>
                                            <option value="Helvetica">Helvetica</option>
                                            <option value="Times New Roman">Times New Roman</option>
                                            <option value="Courier New">Courier New</option>
                                            <option value="Verdana">Verdana</option>
                                            <option value="Century Gothic">Century Gothic</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs {{ $textBody }} mb-1">Estilo de Fuente</label>
                                        <select x-model="variablesConfig[selectedVariable].fontStyle"
                                                class="w-full {{ $bgInput }} border rounded px-2 py-1 text-sm">
                                            <option value="normal">Regular</option>
                                            <option value="bold">Bold</option>
                                            <option value="italic">Italic</option>
                                            <option value="bold italic">Bold Italic</option>
                                        </select>
                                    </div>
                                    <template x-if="selectedVariable !== 'foto' && selectedVariable !== 'qr_code'">
                                        <div class="flex items-center space-x-2">
                                            <input type="checkbox" 
                                                   :id="'centrado_' + selectedVariable"
                                                   x-model="variablesConfig[selectedVariable].centrado"
                                                   @change="drawVariables()"
                                                   class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                                            <label :for="'centrado_' + selectedVariable" 
                                                   class="text-xs {{ $textBody }} cursor-pointer">
                                                Centrar texto
                                            </label>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <p x-show="!selectedVariable" class="text-xs {{ $textBody }} italic">
                                Selecciona una variable y haz clic en "Posicionar" para ubicarla en la plantilla
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Área de Diseño (Centro) -->
                <div class="lg:col-span-2">
                    <div class="{{ $bgCard }} shadow border {{ $borderCard }} rounded-lg p-6">
                        <div class="mb-4">
                            <label class="block text-sm font-medium {{ $textTitle }} mb-2">
                                Subir Imagen de Plantilla
                            </label>
                            <input type="file" 
                                   name="imagen_plantilla"
                                   accept="image/*"
                                   @change="loadImage($event)"
                                   class="w-full {{ $bgInput }} border rounded px-3 py-2 text-sm">
                            <p class="text-xs {{ $textBody }} mt-1">Formatos: JPG, PNG, GIF. Máximo 5MB</p>
                        </div>

                        <div class="relative border-2 {{ $borderCard }} rounded-lg overflow-auto" 
                             style="min-height: 400px; background: repeating-conic-gradient(#f0f0f0 0% 25%, #ffffff 0% 50%) 50% / 20px 20px;">
                            <canvas id="carnet-canvas" 
                                    @click="handleCanvasClick($event)"
                                    class="cursor-crosshair"
                                    style="display: block;"></canvas>
                            
                            <div x-show="!image" class="absolute inset-0 flex items-center justify-center {{ $textBody }}">
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p class="text-sm">Sube una imagen de plantilla para comenzar</p>
                                </div>
                            </div>
                        </div>

                        @if($template && $template->imagen_plantilla)
                            <div class="mt-4">
                                <p class="text-sm {{ $textBody }} mb-2">Plantilla actual:</p>
                                <img src="{{ asset($template->imagen_plantilla) }}" 
                                     alt="Plantilla actual" 
                                     id="current-template"
                                     class="max-w-full h-auto rounded border {{ $borderCard }}"
                                     style="display: none;">
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Panel de Acciones (Lateral Derecho) -->
                <div class="lg:col-span-1">
                    <div class="{{ $bgCard }} shadow border {{ $borderCard }} rounded-lg p-6 sticky top-4">
                        <h3 class="text-lg font-semibold {{ $textTitle }} mb-4">Acciones</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium {{ $textTitle }} mb-2">
                                    Nombre de la Plantilla
                                </label>
                                <input type="text" 
                                       name="nombre"
                                       value="{{ $template->nombre ?? 'Plantilla Principal' }}"
                                       class="w-full {{ $bgInput }} border rounded px-3 py-2">
                            </div>

                            <div class="pt-4 border-t {{ $borderCard }}">
                                <button type="submit" 
                                        class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-md transition">
                                    Guardar Plantilla
                                </button>
                            </div>

                            <div>
                                <a href="{{ route('carnets.index') }}" 
                                   class="block w-full text-center bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg shadow-md transition">
                                    Cancelar
                                </a>
                            </div>

                            <div class="pt-4 border-t {{ $borderCard }}">
                                <h4 class="text-sm font-semibold {{ $textTitle }} mb-2">Instrucciones</h4>
                                <ul class="text-xs {{ $textBody }} space-y-1 list-disc list-inside">
                                    <li>Sube una imagen de plantilla</li>
                                    <li>Activa las variables que deseas mostrar</li>
                                    <li>Haz clic en "Posicionar" y luego en la imagen donde quieres ubicar la variable</li>
                                    <li>Ajusta tamaño, color y fuente de cada variable</li>
                                    <li>Guarda cuando termines</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Input oculto para enviar la configuración JSON -->
            <input type="hidden" name="variables_config" x-bind:value="JSON.stringify(variablesConfig)">
        </form>
    </div>

    <script>
        function carnetEditor() {
            return {
                variables: @json($variables),
                variablesConfig: @json($variablesConfig),
                selectedVariable: null,
                canvas: null,
                ctx: null,
                image: null,
                scale: 1,

                init() {
                    // Inicializar configuración de variables si no existe
                    Object.keys(this.variables).forEach(key => {
                        if (!this.variablesConfig[key]) {
                            if (key === 'foto' || key === 'qr_code') {
                                this.variablesConfig[key] = {
                                    activo: false,
                                    x: undefined,
                                    y: undefined,
                                    size: 100 // Tamaño por defecto para foto y QR
                                };
                            } else {
                                this.variablesConfig[key] = {
                                    activo: false,
                                    x: undefined,
                                    y: undefined,
                                    fontSize: 14,
                                    color: '#000000',
                                    fontFamily: 'Arial',
                                    fontStyle: 'normal',
                                    centrado: false
                                };
                            }
                        } else {
                            // Asegurar que todas las propiedades existan
                            if (this.variablesConfig[key].x === null) this.variablesConfig[key].x = undefined;
                            if (this.variablesConfig[key].y === null) this.variablesConfig[key].y = undefined;
                            
                            if (key === 'foto' || key === 'qr_code') {
                                if (!this.variablesConfig[key].size) this.variablesConfig[key].size = 100;
                            } else {
                                if (!this.variablesConfig[key].fontSize) this.variablesConfig[key].fontSize = 14;
                                if (!this.variablesConfig[key].color) this.variablesConfig[key].color = '#000000';
                                if (!this.variablesConfig[key].fontFamily) this.variablesConfig[key].fontFamily = 'Arial';
                                if (!this.variablesConfig[key].fontStyle) this.variablesConfig[key].fontStyle = 'normal';
                                if (this.variablesConfig[key].centrado === undefined) this.variablesConfig[key].centrado = false;
                            }
                        }
                    });

                    // Inicializar canvas
                    setTimeout(() => {
                        this.canvas = document.getElementById('carnet-canvas');
                        if (this.canvas) {
                            this.ctx = this.canvas.getContext('2d');
                            
                            // Cargar imagen existente si hay plantilla
                            @if($template && $template->imagen_plantilla)
                                this.loadExistingImage('{{ asset($template->imagen_plantilla) }}');
                            @endif
                        }
                    }, 100);
                },

                loadImage(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.loadExistingImage(e.target.result);
                    };
                    reader.readAsDataURL(file);
                },

                loadExistingImage(src) {
                    this.image = new Image();
                    this.image.onload = () => {
                        // Calcular escala para que quepa en el contenedor (máx 800px de ancho)
                        const maxWidth = 800;
                        this.scale = Math.min(1, maxWidth / this.image.width);
                        
                        // Establecer el tamaño interno del canvas al tamaño de la imagen escalada
                        this.canvas.width = this.image.width * this.scale;
                        this.canvas.height = this.image.height * this.scale;
                        
                        // Establecer el tamaño visual del canvas (puede ser diferente)
                        this.canvas.style.width = this.canvas.width + 'px';
                        this.canvas.style.height = this.canvas.height + 'px';
                        
                        this.ctx.drawImage(this.image, 0, 0, this.canvas.width, this.canvas.height);
                        this.drawVariables();
                    };
                    this.image.src = src;
                },

                selectVariable(key) {
                    this.selectedVariable = this.selectedVariable === key ? null : key;
                },

                handleCanvasClick(event) {
                    if (!this.selectedVariable) {
                        alert('Por favor, selecciona una variable primero haciendo clic en "Posicionar"');
                        return;
                    }

                    if (!this.image) {
                        alert('Por favor, sube una imagen de plantilla primero');
                        return;
                    }

                    // Obtener posición del clic relativa al canvas
                    const rect = this.canvas.getBoundingClientRect();
                    const clickX = event.clientX - rect.left;
                    const clickY = event.clientY - rect.top;

                    // Obtener dimensiones del canvas (tamaño interno)
                    const canvasWidth = this.canvas.width;
                    const canvasHeight = this.canvas.height;
                    
                    // Obtener dimensiones visuales del canvas
                    const visualWidth = rect.width;
                    const visualHeight = rect.height;

                    // Calcular factor de escala visual (si el canvas está escalado por CSS)
                    const scaleX = canvasWidth / visualWidth;
                    const scaleY = canvasHeight / visualHeight;

                    // Convertir coordenadas del clic a coordenadas del canvas interno
                    const canvasX = clickX * scaleX;
                    const canvasY = clickY * scaleY;

                    // Convertir coordenadas del canvas escalado a coordenadas de la imagen original
                    // this.scale es el factor de escala aplicado a la imagen al dibujarla en el canvas
                    const imageX = canvasX / this.scale;
                    const imageY = canvasY / this.scale;

                    // Asegurar que las coordenadas estén dentro de los límites de la imagen
                    const finalX = Math.max(0, Math.min(imageX, this.image.width));
                    const finalY = Math.max(0, Math.min(imageY, this.image.height));

                    console.log('Click en:', { clickX, clickY, canvasX, canvasY, imageX: finalX, imageY: finalY, scale: this.scale });

                    this.variablesConfig[this.selectedVariable].x = finalX;
                    this.variablesConfig[this.selectedVariable].y = finalY;
                    this.variablesConfig[this.selectedVariable].activo = true;

                    this.drawVariables();
                    this.selectedVariable = null;
                },

                drawVariables() {
                    if (!this.image || !this.ctx) return;

                    // Redibujar imagen
                    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                    this.ctx.drawImage(this.image, 0, 0, this.canvas.width, this.canvas.height);

                            // Dibujar marcadores de variables
                            Object.keys(this.variablesConfig).forEach(key => {
                                const config = this.variablesConfig[key];
                                if (config.activo && config.x !== undefined && config.y !== undefined) {
                                    const x = config.x * this.scale;
                                    const y = config.y * this.scale;

                                    // Si es foto o QR, dibujar recuadro
                                    if (key === 'foto' || key === 'qr_code') {
                                        const size = (config.size || 100) * this.scale;
                                        // Dibujar recuadro
                                        this.ctx.strokeStyle = '#3B82F6';
                                        this.ctx.lineWidth = 2;
                                        this.ctx.setLineDash([5, 5]);
                                        this.ctx.strokeRect(x, y, size, size);
                                        this.ctx.setLineDash([]);
                                        
                                        // Dibujar texto de preview
                                        this.ctx.fillStyle = '#3B82F6';
                                        this.ctx.font = `12px Arial`;
                                        this.ctx.fillText(key === 'foto' ? 'Foto' : 'QR', x, y - 5);
                                    } else {
                                        // Dibujar círculo marcador para texto
                                        this.ctx.fillStyle = '#3B82F6';
                                        this.ctx.beginPath();
                                        this.ctx.arc(x, y, 5, 0, 2 * Math.PI);
                                        this.ctx.fill();

                                        // Dibujar texto de preview
                                        const fontStyle = config.fontStyle || 'normal';
                                        const fontFamily = config.fontFamily || 'Arial';
                                        const fontSize = config.fontSize || 14;
                                        this.ctx.font = `${fontStyle} ${fontSize * this.scale}px ${fontFamily}`;
                                        this.ctx.fillStyle = config.color || '#000000';
                                        
                                        const text = this.getVariableLabel(key);
                                        let textX = x + 10;
                                        
                                        // Si está centrado, calcular posición centrada
                                        if (config.centrado) {
                                            this.ctx.textAlign = 'center';
                                            textX = this.canvas.width / 2;
                                        } else {
                                            this.ctx.textAlign = 'left';
                                        }
                                        
                                        this.ctx.fillText(text, textX, y);
                                    }
                                }
                            });
                },

                updateVariableConfig(key) {
                    // Si se desactiva, limpiar posición
                    if (!this.variablesConfig[key].activo) {
                        this.variablesConfig[key].x = undefined;
                        this.variablesConfig[key].y = undefined;
                    }
                    this.drawVariables();
                },

                updateSize() {
                    // Asegurar que el tamaño esté definido para foto o QR
                    if (this.selectedVariable === 'foto' || this.selectedVariable === 'qr_code') {
                        if (!this.variablesConfig[this.selectedVariable].size) {
                            this.variablesConfig[this.selectedVariable].size = 100;
                        }
                    }
                    this.drawVariables();
                },

                getVariableLabel(key) {
                    return this.variables[key] || key;
                }
            }
        }
    </script>
</x-app-layout>
