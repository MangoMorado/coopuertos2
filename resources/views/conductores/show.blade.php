@php
    $fotoUrl = null;
    if ($conductor->foto) {
        if (\Illuminate\Support\Str::startsWith($conductor->foto, 'uploads/')) {
            $fotoUrl = asset($conductor->foto);
        } else {
            $fotoUrl = asset('storage/' . $conductor->foto);
        }
    }
    $fallbackAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($conductor->nombres . ' ' . $conductor->apellidos) . '&background=1e3a8a&color=fff';
    
    // Preparar datos del conductor para las variables
    $vehiculo = $conductor->asignacionActiva && $conductor->asignacionActiva->vehicle ? $conductor->asignacionActiva->vehicle : null;
    
    $datosConductor = [
        'nombres' => $conductor->nombres,
        'apellidos' => $conductor->apellidos,
        'nombre_completo' => $conductor->nombres . ' ' . $conductor->apellidos,
        'cedula' => $conductor->cedula,
        'conductor_tipo' => $conductor->conductor_tipo,
        'rh' => $conductor->rh,
        'numero_interno' => $conductor->numero_interno ?? '',
        'celular' => $conductor->celular ?? '',
        'correo' => $conductor->correo ?? '',
        'fecha_nacimiento' => $conductor->fecha_nacimiento ? $conductor->fecha_nacimiento->format('d/m/Y') : '',
        'nivel_estudios' => $conductor->nivel_estudios ?? '',
        'otra_profesion' => $conductor->otra_profesion ?? '',
        'estado' => ucfirst($conductor->estado),
        'foto' => $fotoUrl ?? $fallbackAvatar,
        'vehiculo_placa' => $vehiculo ? $vehiculo->placa : 'Sin asignar',
        'vehiculo_marca' => $vehiculo ? $vehiculo->marca : '',
        'vehiculo_modelo' => $vehiculo ? $vehiculo->modelo : '',
        'qr_code' => route('conductor.public', $conductor->uuid),
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Carnet del Conductor
        </h2>
    </x-slot>

    <div class="min-h-screen bg-gray-100 flex items-center justify-center py-10 px-4">
        <div class="max-w-4xl w-full">
            @if($template && $template->imagen_plantilla && $template->variables_config)
                <!-- Carnet con diseño personalizado -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-200 p-6">
                    <div class="relative inline-block" style="max-width: 100%;">
                        <canvas id="carnet-canvas" class="max-w-full h-auto border border-gray-300 rounded-lg"></canvas>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="mt-6 flex justify-center space-x-4">
                        <a href="{{ route('conductores.index') }}" 
                           class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg shadow-md transition flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                            </svg>
                            <span>Regresar</span>
                        </a>
                        <button onclick="descargarCarnetPDF()" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow-md transition flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            <span>Descargar PDF</span>
                        </button>
                    </div>
                </div>
            @else
                <!-- Vista por defecto si no hay plantilla -->
                <div class="max-w-md w-full bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-200">
                    <div class="bg-gradient-to-r from-blue-600 to-blue-400 p-6 text-white text-center">
                        <img src="{{ $fotoUrl ?? $fallbackAvatar }}"
                             alt="Foto del conductor"
                             class="w-28 h-28 mx-auto rounded-full border-4 border-white shadow-md mb-3">
                        <h1 class="text-2xl font-semibold">{{ $conductor->nombres }} {{ $conductor->apellidos }}</h1>
                    </div>

                    <div class="p-6 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 text-sm">Cédula:</span>
                            <span class="font-medium text-gray-800">{{ $conductor->cedula }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 text-sm">Estado:</span>
                            <span class="font-medium text-gray-800">{{ ucfirst($conductor->estado) }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 text-sm">Vehículo Asignado:</span>
                            <span class="font-medium text-gray-800">
                                @if($vehiculo)
                                    {{ $vehiculo->placa }} - {{ $vehiculo->marca }} {{ $vehiculo->modelo }}
                                @else
                                    <span class="text-gray-400">Sin asignar</span>
                                @endif
                            </span>
                        </div>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="mt-6 flex justify-center space-x-4">
                        <a href="{{ route('conductores.index') }}" 
                           class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg shadow-md transition flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                            </svg>
                            <span>Regresar</span>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if($template && $template->imagen_plantilla && $template->variables_config)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('carnet-canvas');
            const ctx = canvas.getContext('2d');
            const templateImage = new Image();
            const conductorFoto = new Image();
            
            const templateConfig = @json($template->variables_config);
            const conductorData = @json($datosConductor);
            const templatePath = '{{ asset($template->imagen_plantilla) }}';
            
            // Cargar imagen de plantilla
            templateImage.crossOrigin = 'anonymous';
            templateImage.onload = function() {
                // Establecer tamaño del canvas igual a la imagen
                canvas.width = templateImage.width;
                canvas.height = templateImage.height;
                
                // Dibujar imagen de fondo
                ctx.drawImage(templateImage, 0, 0);
                
                // Cargar foto del conductor si existe
                if (conductorData.foto && conductorData.foto !== '') {
                    conductorFoto.crossOrigin = 'anonymous';
                    conductorFoto.onload = function() {
                        renderVariables();
                    };
                    conductorFoto.onerror = function() {
                        renderVariables();
                    };
                    conductorFoto.src = conductorData.foto;
                } else {
                    renderVariables();
                }
            };
            
            templateImage.src = templatePath;
            
            const qrImages = {};
            let qrLoaded = false;
            
            function renderVariables() {
                // Redibujar imagen de fondo
                ctx.drawImage(templateImage, 0, 0);
                
                // Renderizar cada variable configurada
                Object.keys(templateConfig).forEach(key => {
                    const config = templateConfig[key];
                    if (config.activo && config.x !== undefined && config.y !== undefined) {
                        const value = getVariableValue(key, conductorData);
                        if (value !== null && value !== '') {
                            // Configurar estilo de fuente
                            const fontSize = config.fontSize || 14;
                            const fontFamily = config.fontFamily || 'Arial';
                            const fontStyle = config.fontStyle || 'normal';
                            const color = config.color || '#000000';
                            
                            ctx.font = `${fontStyle} ${fontSize}px ${fontFamily}`;
                            ctx.fillStyle = color;
                            ctx.textBaseline = 'top';
                            
                            // Si es la foto, dibujar imagen
                            if (key === 'foto' && conductorFoto.complete && conductorFoto.naturalWidth > 0) {
                                const fotoSize = config.size || 100; // Tamaño configurado en el editor
                                // Dibujar la foto sin recorte circular, manteniendo relación 1:1
                                ctx.drawImage(conductorFoto, config.x, config.y, fotoSize, fotoSize);
                            }
                            // Si es QR, dibujar código QR desde SVG
                            else if (key === 'qr_code') {
                                const qrSize = config.size || 100; // Tamaño configurado en el editor
                                if (!qrImages[key]) {
                                    // Cargar SVG y convertirlo a imagen
                                    fetch('{{ route("conductor.public", $conductor->uuid) }}?qr=1&size=' + qrSize)
                                        .then(response => response.text())
                                        .then(svgText => {
                                            const img = new Image();
                                            const blob = new Blob([svgText], { type: 'image/svg+xml' });
                                            const url = URL.createObjectURL(blob);
                                            img.onload = function() {
                                                qrImages[key] = img;
                                                qrLoaded = true;
                                                renderVariables();
                                                URL.revokeObjectURL(url);
                                            };
                                            img.src = url;
                                        })
                                        .catch(error => {
                                            console.error('Error cargando QR:', error);
                                        });
                                } else if (qrImages[key].complete && qrImages[key].naturalWidth > 0) {
                                    ctx.drawImage(qrImages[key], config.x, config.y, qrSize, qrSize);
                                }
                            }
                            // Texto normal
                            else {
                                const text = String(value);
                                let textX = config.x;
                                
                                // Si está centrado, calcular posición centrada según el ancho de la imagen
                                if (config.centrado) {
                                    ctx.textAlign = 'center';
                                    textX = templateImage.width / 2;
                                } else {
                                    ctx.textAlign = 'left';
                                }
                                
                                ctx.fillText(text, textX, config.y);
                            }
                        }
                    }
                });
            }
            
            function getVariableValue(key, data) {
                return data[key] !== undefined ? data[key] : null;
            }
            
            // Función para descargar el carnet como PDF
            window.descargarCarnetPDF = function() {
                const canvas = document.getElementById('carnet-canvas');
                if (!canvas) {
                    alert('Error: No se pudo encontrar el canvas del carnet');
                    return;
                }
                
                // Convertir canvas a imagen
                const imageData = canvas.toDataURL('image/png', 1.0);
                
                // Cargar jsPDF desde CDN si no está disponible
                if (typeof window.jspdf === 'undefined') {
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
                    script.onload = function() {
                        generarPDF(imageData, canvas);
                    };
                    document.head.appendChild(script);
                } else {
                    generarPDF(imageData, canvas);
                }
            };
            
            function generarPDF(imageData, canvas) {
                const { jsPDF } = window.jspdf;
                
                // Obtener dimensiones reales del canvas (tamaño interno, no escalado visualmente)
                const canvasWidth = canvas.width;
                const canvasHeight = canvas.height;
                
                // Calcular DPI (asumiendo que la imagen se renderiza a 300 DPI)
                const dpi = 300;
                const mmPerInch = 25.4;
                const pixelsPerMM = dpi / mmPerInch;
                
                // Convertir píxeles a milímetros
                const widthMM = canvasWidth / pixelsPerMM;
                const heightMM = canvasHeight / pixelsPerMM;
                
                // Crear PDF con las dimensiones exactas del canvas
                const pdf = new jsPDF({
                    orientation: widthMM > heightMM ? 'landscape' : 'portrait',
                    unit: 'mm',
                    format: [widthMM, heightMM]
                });
                
                // Agregar imagen al PDF ocupando todo el espacio
                pdf.addImage(imageData, 'PNG', 0, 0, widthMM, heightMM, undefined, 'FAST');
                
                // Descargar PDF
                const filename = 'carnet_{{ $conductor->cedula }}_{{ date("YmdHis") }}.pdf';
                pdf.save(filename);
            }
        });
    </script>
    @endif
</x-app-layout>
