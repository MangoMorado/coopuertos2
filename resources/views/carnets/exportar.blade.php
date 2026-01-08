@php
    $theme = Auth::user()->theme ?? 'light';
    $isDark = $theme === 'dark';
    $textTitle = $isDark ? 'text-gray-100' : 'text-gray-800';
    $bgCard = $isDark ? 'bg-gray-800' : 'bg-white';
    $textBody = $isDark ? 'text-gray-300' : 'text-gray-700';
    $borderCard = $isDark ? 'border-gray-700' : 'border-gray-200';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl {{ $textTitle }} leading-tight">
            Exportación Masiva de Carnets
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-6">
        <!-- Botón de Generación -->
        <div class="mb-6 {{ $bgCard }} shadow border {{ $borderCard }} rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold {{ $textTitle }} mb-2">Generar Carnets</h3>
                    <p class="text-sm {{ $textBody }}">Haz clic en el botón para iniciar la generación masiva de carnets para todos los conductores.</p>
                </div>
                <form action="{{ route('carnets.generar') }}" method="POST" id="form-generar" class="flex-shrink-0">
                    @csrf
                    <button type="submit" 
                            id="btn-generar"
                            class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-6 py-3 rounded-lg shadow-md transition flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                        <span>Generar Carnets</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Barra de Progreso -->
        <div class="mb-6 {{ $bgCard }} shadow border {{ $borderCard }} rounded-lg p-6" id="panel-progreso" style="display: none;">
            <div class="mb-4">
                <h3 class="text-lg font-semibold {{ $textTitle }} mb-4">Progreso de Generación</h3>
                
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm {{ $textBody }}" id="contador-progreso">0 de 0 carnets procesados</span>
                        <span class="text-sm font-semibold {{ $textTitle }}" id="porcentaje-progreso">0%</span>
                    </div>
                    <div class="w-full {{ $isDark ? 'bg-gray-700' : 'bg-gray-200' }} rounded-full h-6">
                        <div id="barra-progreso" class="bg-blue-600 h-6 rounded-full transition-all duration-300 flex items-center justify-center" style="width: 0%">
                            <span class="text-xs font-semibold text-white" id="porcentaje-barra">0%</span>
                        </div>
                    </div>
                </div>

                <div id="estado-proceso" class="mb-4">
                    <div class="flex items-center space-x-2 {{ $isDark ? 'bg-gray-700' : 'bg-gray-100' }} px-4 py-2 rounded">
                        <svg id="icono-estado" class="w-5 h-5 text-blue-500 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span id="mensaje-estado" class="text-sm {{ $textBody }}">Iniciando proceso...</span>
                    </div>
                </div>

                <div id="mensaje-error" class="hidden mb-4 {{ $isDark ? 'bg-red-900 border-red-700 text-red-200' : 'bg-red-100 border-red-300 text-red-800' }} border px-4 py-2 rounded"></div>
                
                <div id="boton-descarga" class="flex justify-center hidden">
                    <a id="link-descarga" href="#" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg shadow-md transition flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        <span>Descargar ZIP</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Log de Eventos -->
        <div class="{{ $bgCard }} shadow border {{ $borderCard }} rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold {{ $textTitle }}">Log de Eventos</h3>
                <button onclick="limpiarLogs()" class="text-sm {{ $isDark ? 'text-gray-400 hover:text-gray-300' : 'text-gray-600 hover:text-gray-800' }} flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                    <span>Limpiar</span>
                </button>
            </div>
            
            <div id="log-container" class="{{ $isDark ? 'bg-gray-900' : 'bg-gray-50' }} rounded-lg p-4 max-h-96 overflow-y-auto">
                <div id="log-messages" class="space-y-2">
                    <div class="text-sm {{ $textBody }} italic">Esperando eventos...</div>
                </div>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="mt-6 flex justify-end">
            <a href="{{ route('carnets.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg shadow-md transition flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                </svg>
                <span>Volver a Carnets</span>
            </a>
        </div>
    </div>

    <script>
        const sessionId = '{{ $sessionId ?? null }}';
        let progressInterval = null;
        let ultimoLogIndex = 0;

        // Iniciar polling al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            if (sessionId) {
                document.getElementById('panel-progreso').style.display = 'block';
                iniciarPolling();
            } else {
                mostrarMensajeInfo();
            }

            // Manejar envío del formulario de generación
            const formGenerar = document.getElementById('form-generar');
            if (formGenerar) {
                formGenerar.addEventListener('submit', function(e) {
                    const btnGenerar = document.getElementById('btn-generar');
                    if (btnGenerar) {
                        btnGenerar.disabled = true;
                        btnGenerar.innerHTML = `
                            <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span>Iniciando...</span>
                        `;
                    }
                });
            }
        });

        function mostrarMensajeInfo() {
            const logMessages = document.getElementById('log-messages');
            if (logMessages) {
                logMessages.innerHTML = `
                    <div class="text-sm {{ $textBody }} space-y-2">
                        <div class="flex items-start space-x-2">
                            <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            <div class="flex-1">
                                <p class="font-semibold {{ $textTitle }}">Generación Manual de Carnets</p>
                                <p class="{{ $textBody }} mt-1">Haz clic en el botón "Generar Carnets" para iniciar la generación masiva de carnets para todos los conductores.</p>
                                <p class="{{ $textBody }} mt-3">El progreso se mostrará aquí una vez que inicies la generación.</p>
                            </div>
                        </div>
                    </div>
                `;
            }
        }

        function iniciarPolling() {
            if (!sessionId) {
                mostrarMensajeInfo();
                return;
            }

            // Mostrar panel de progreso
            const panelProgreso = document.getElementById('panel-progreso');
            if (panelProgreso) {
                panelProgreso.style.display = 'block';
            }

            progressInterval = setInterval(() => {
                fetch(`{{ route('carnets.progreso', ':sessionId') }}`.replace(':sessionId', sessionId))
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            actualizarProgreso(data);
                            actualizarLogs(data.logs || []);
                            
                            if (data.estado === 'completado') {
                                clearInterval(progressInterval);
                                mostrarEstado('completado', data.mensaje || 'Proceso completado exitosamente');
                                mostrarBotonDescarga(data.archivo);
                                // Habilitar botón de generar nuevamente
                                const btnGenerar = document.getElementById('btn-generar');
                                if (btnGenerar) {
                                    btnGenerar.disabled = false;
                                    btnGenerar.innerHTML = `
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                        </svg>
                                        <span>Generar Carnets</span>
                                    `;
                                }
                            } else if (data.estado === 'error') {
                                clearInterval(progressInterval);
                                mostrarEstado('error', 'Error en el proceso');
                                mostrarError(data.error || data.mensaje || 'Error desconocido');
                                // Habilitar botón de generar nuevamente
                                const btnGenerar = document.getElementById('btn-generar');
                                if (btnGenerar) {
                                    btnGenerar.disabled = false;
                                    btnGenerar.innerHTML = `
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                        </svg>
                                        <span>Generar Carnets</span>
                                    `;
                                }
                            } else if (data.estado === 'procesando' || data.estado === 'pendiente') {
                                const mensaje = data.mensaje || 'Procesando carnets...';
                                mostrarEstado('procesando', mensaje);
                            }
                        } else {
                            mostrarError(data.message || 'Error al obtener progreso');
                        }
                    })
                    .catch(error => {
                        console.error('Error al obtener progreso:', error);
                        mostrarError('Error de conexión al obtener progreso');
                    });
            }, 1000); // Polling cada segundo
        }

        function actualizarProgreso(data) {
            const porcentaje = data.progreso || 0;
            const barra = document.getElementById('barra-progreso');
            const porcentajeTexto = document.getElementById('porcentaje-progreso');
            const porcentajeBarra = document.getElementById('porcentaje-barra');
            const contador = document.getElementById('contador-progreso');
            
            if (barra) {
                barra.style.width = porcentaje + '%';
            }
            if (porcentajeTexto) {
                porcentajeTexto.textContent = porcentaje.toFixed(1) + '%';
            }
            if (porcentajeBarra) {
                porcentajeBarra.textContent = porcentaje.toFixed(1) + '%';
            }
            if (contador) {
                const exitosos = data.exitosos || 0;
                const errores = data.errores || 0;
                contador.textContent = `${data.procesados || 0} de ${data.total || 0} carnets procesados (${exitosos} exitosos, ${errores} errores)`;
            }
        }

        function actualizarLogs(logs) {
            if (!logs || logs.length === 0) {
                return;
            }

            const logMessages = document.getElementById('log-messages');
            const container = document.getElementById('log-container');

            // Agregar solo los nuevos logs
            for (let i = ultimoLogIndex; i < logs.length; i++) {
                const log = logs[i];
                const logElement = crearElementoLog(log);
                logMessages.appendChild(logElement);
                ultimoLogIndex++;
            }

            // Auto-scroll al final
            container.scrollTop = container.scrollHeight;
        }

        function crearElementoLog(log) {
            const div = document.createElement('div');
            div.className = 'flex items-start space-x-2 text-sm';

            // Icono según tipo
            let iconoClass = '';
            let textoClass = '';
            
            switch(log.tipo) {
                case 'success':
                    iconoClass = 'text-green-500';
                    textoClass = '{{ $textBody }}';
                    break;
                case 'error':
                    iconoClass = 'text-red-500';
                    textoClass = 'text-red-500';
                    break;
                case 'warning':
                    iconoClass = 'text-yellow-500';
                    textoClass = 'text-yellow-500';
                    break;
                case 'debug':
                    iconoClass = 'text-gray-400';
                    textoClass = '{{ $textBody }} text-xs';
                    break;
                default: // info
                    iconoClass = 'text-blue-500';
                    textoClass = '{{ $textBody }}';
            }

            const icono = obtenerIcono(log.tipo);
            
            div.innerHTML = `
                <div class="${iconoClass} mt-0.5 flex-shrink-0">${icono}</div>
                <div class="flex-1 ${textoClass}">
                    <div class="flex items-center space-x-2">
                        <span class="font-mono text-xs opacity-70">${formatearTimestamp(log.timestamp)}</span>
                        <span class="font-semibold">${log.mensaje}</span>
                    </div>
                    ${log.data && Object.keys(log.data).length > 0 ? `
                        <div class="mt-1 ml-4 text-xs opacity-80">
                            ${Object.entries(log.data).map(([key, value]) => `${key}: ${value}`).join(' | ')}
                        </div>
                    ` : ''}
                </div>
            `;

            return div;
        }

        function obtenerIcono(tipo) {
            const iconos = {
                'success': `<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>`,
                'error': `<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>`,
                'warning': `<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>`,
                'debug': `<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>`,
                'info': `<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>`,
            };

            return iconos[tipo] || iconos['info'];
        }

        function formatearTimestamp(timestamp) {
            if (!timestamp) return '';
            const date = new Date(timestamp);
            return date.toLocaleTimeString('es-ES', { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit' 
            });
        }

        function mostrarEstado(estado, mensaje) {
            const iconoEstado = document.getElementById('icono-estado');
            const mensajeEstado = document.getElementById('mensaje-estado');
            
            if (mensajeEstado) {
                mensajeEstado.textContent = mensaje;
            }

            if (iconoEstado) {
                iconoEstado.classList.remove('animate-spin', 'text-blue-500', 'text-green-500', 'text-red-500');
                
                if (estado === 'completado') {
                    iconoEstado.classList.add('text-green-500');
                    iconoEstado.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>`;
                } else if (estado === 'error') {
                    iconoEstado.classList.add('text-red-500');
                    iconoEstado.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>`;
                } else {
                    iconoEstado.classList.add('text-blue-500', 'animate-spin');
                    iconoEstado.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>`;
                }
            }
        }

        function mostrarError(mensaje) {
            const contenedor = document.getElementById('mensaje-error');
            if (contenedor) {
                contenedor.classList.remove('hidden');
                contenedor.textContent = 'Error: ' + mensaje;
            }
        }

        function mostrarBotonDescarga(archivo) {
            const contenedor = document.getElementById('boton-descarga');
            const link = document.getElementById('link-descarga');
            
            if (contenedor && link) {
                const url = '{{ route("carnets.descargar-zip", ":sessionId") }}'.replace(':sessionId', sessionId);
                link.href = url;
                contenedor.classList.remove('hidden');
            }
        }

        function limpiarLogs() {
            const logMessages = document.getElementById('log-messages');
            if (logMessages) {
                logMessages.innerHTML = '<div class="text-sm {{ $textBody }} italic">Log limpiado. Esperando nuevos eventos...</div>';
                ultimoLogIndex = 0;
            }
        }
    </script>
</x-app-layout>

