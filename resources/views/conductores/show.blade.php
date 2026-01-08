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
        'vehiculo' => $conductor->vehiculo ? (string) $conductor->vehiculo : 'Relevo',
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
                        @if($previewImageUrl)
                            <!-- Mostrar imagen generada por backend (igual al PDF) -->
                            <img src="{{ $previewImageUrl }}" 
                                 alt="Vista previa del carnet" 
                                 class="max-w-full h-auto border border-gray-300 rounded-lg"
                                 id="carnet-preview-image">
                        @else
                            <!-- Fallback: Canvas si no se pudo generar la imagen -->
                            <canvas id="carnet-canvas" class="max-w-full h-auto border border-gray-300 rounded-lg"></canvas>
                        @endif
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
                        <a href="{{ route('conductores.carnet.descargar', $conductor->uuid) }}" 
                           class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow-md transition flex items-center space-x-2"
                           id="btn-descargar-carnet">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            <span>Descargar PDF</span>
                        </a>
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
            // Si hay imagen de previsualización generada por backend, no hacer nada más
            // La imagen ya está renderizada con el mismo motor que genera el PDF
            
            // Si no hay imagen de previsualización, usar Canvas como fallback
            @if(!$previewImageUrl)
            const canvas = document.getElementById('carnet-canvas');
            if (canvas) {
                const ctx = canvas.getContext('2d');
                const templateImage = new Image();
                const conductorFoto = new Image();
                
                const templateConfig = @json($template->variables_config);
                const conductorData = @json($datosConductor);
                const templatePath = '{{ asset($template->imagen_plantilla) }}';
                
                // Cargar imagen de plantilla
                templateImage.crossOrigin = 'anonymous';
                templateImage.onload = function() {
                    canvas.width = templateImage.width;
                    canvas.height = templateImage.height;
                    ctx.drawImage(templateImage, 0, 0);
                    
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
                
                function renderVariables() {
                    ctx.drawImage(templateImage, 0, 0);
                    Object.keys(templateConfig).forEach(key => {
                        const config = templateConfig[key];
                        if (config.activo && config.x !== undefined && config.y !== undefined) {
                            const value = conductorData[key];
                            if (value !== null && value !== '') {
                                const fontSize = config.fontSize || 14;
                                const fontFamily = config.fontFamily || 'Arial';
                                const fontStyle = config.fontStyle || 'normal';
                                const color = config.color || '#000000';
                                
                                ctx.font = `${fontStyle} ${fontSize}px ${fontFamily}`;
                                ctx.fillStyle = color;
                                ctx.textBaseline = 'top';
                                
                                if (key === 'foto' && conductorFoto.complete && conductorFoto.naturalWidth > 0) {
                                    const fotoSize = config.size || 100;
                                    ctx.drawImage(conductorFoto, config.x, config.y, fotoSize, fotoSize);
                                } else if (key === 'qr_code') {
                                    const qrSize = config.size || 100;
                                    if (!qrImages[key]) {
                                        fetch('{{ route("conductor.public", $conductor->uuid) }}?qr=1&size=' + qrSize)
                                            .then(response => response.text())
                                            .then(svgText => {
                                                const img = new Image();
                                                const blob = new Blob([svgText], { type: 'image/svg+xml' });
                                                const url = URL.createObjectURL(blob);
                                                img.onload = function() {
                                                    qrImages[key] = img;
                                                    renderVariables();
                                                    URL.revokeObjectURL(url);
                                                };
                                                img.src = url;
                                            });
                                    } else if (qrImages[key].complete && qrImages[key].naturalWidth > 0) {
                                        ctx.drawImage(qrImages[key], config.x, config.y, qrSize, qrSize);
                                    }
                                } else {
                                    const text = String(value);
                                    let textX = config.x;
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
            }
            @endif
            
            // Mostrar indicador de carga al hacer clic en descargar
            const btnDescargar = document.getElementById('btn-descargar-carnet');
            if (btnDescargar) {
                btnDescargar.addEventListener('click', function(e) {
                    const btn = this;
                    const originalHtml = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = `
                        <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>Generando PDF...</span>
                    `;
                    
                    // Restaurar después de 5 segundos por si acaso
                    setTimeout(function() {
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                    }, 5000);
                });
            }
        });
    </script>
    @endif
</x-app-layout>
