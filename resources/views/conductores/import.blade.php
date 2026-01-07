@php
    $theme = Auth::user()->theme ?? 'light';
    $isDark = $theme === 'dark';
    
    // Colores según el tema
    $bgCard = $isDark ? 'bg-gray-800' : 'bg-white';
    $textTitle = $isDark ? 'text-gray-100' : 'text-gray-800';
    $textSubtitle = $isDark ? 'text-gray-400' : 'text-gray-600';
    $textBody = $isDark ? 'text-gray-300' : 'text-gray-700';
    $borderCard = $isDark ? 'border-gray-700' : 'border-gray-200';
    $bgInput = $isDark ? 'bg-gray-700 border-gray-600' : 'bg-white border-gray-300';
    $bgError = $isDark ? 'bg-red-900 border-red-700' : 'bg-red-100 border-red-300';
    $textError = $isDark ? 'text-red-200' : 'text-red-800';
    $bgSuccess = $isDark ? 'bg-green-900 border-green-700' : 'bg-green-100 border-green-300';
    $textSuccess = $isDark ? 'text-green-200' : 'text-green-800';
    $bgWarning = $isDark ? 'bg-yellow-900 border-yellow-700' : 'bg-yellow-100 border-yellow-300';
    $textWarning = $isDark ? 'text-yellow-200' : 'text-yellow-800';
    
    // Obtener session_id desde la sesión o desde el importLog
    $currentSessionId = session('import_session_id') ?? ($importLog->session_id ?? null);
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl {{ $textTitle }} leading-tight">
            {{ __('Importar Conductores') }}
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto py-8 px-6 space-y-6">
        @if (session('success'))
            <div class="{{ $bgSuccess }} border {{ $textSuccess }} px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="{{ $bgError }} border {{ $textError }} px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <!-- Formulario de importación -->
        <div class="{{ $bgCard }} rounded-lg shadow-md border {{ $borderCard }} p-6">
            <div class="mb-6">
                <h3 class="text-2xl font-bold {{ $textTitle }} mb-2">Importar Conductores desde Excel/CSV</h3>
                <p class="{{ $textSubtitle }}">Sube un archivo Excel (.xlsx, .xls) o CSV (.csv) con los datos de los conductores. El procesamiento se realizará en segundo plano.</p>
            </div>

            <form id="import-form" method="POST" action="{{ route('conductores.import.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <!-- Input de archivo -->
                <div>
                    <label for="archivo" class="block text-sm font-medium {{ $textBody }} mb-2">
                        Archivo Excel/CSV <span class="text-red-500">*</span>
                    </label>
                    <input type="file"
                           id="archivo"
                           name="archivo"
                           accept=".xlsx,.xls,.csv"
                           required
                           class="w-full px-4 py-2 {{ $bgInput }} {{ $textBody }} rounded-lg border focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="mt-1 text-sm {{ $textSubtitle }}">Formatos soportados: .xlsx, .xls, .csv (máximo 10MB)</p>
                </div>

                <!-- Secciones ocultas -->
                <!-- Información sobre columnas requeridas -->
                <div class="{{ $isDark ? 'bg-gray-700' : 'bg-gray-50' }} rounded-lg p-4 border {{ $borderCard }} hidden">
                    <h4 class="font-semibold {{ $textTitle }} mb-3">Estructura del archivo requerida:</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm {{ $textBody }}">
                        <div><strong>NOMBRES</strong> → nombres</div>
                        <div><strong>APELLIDOS</strong> → apellidos</div>
                        <div><strong>CEDULA</strong> → cédula</div>
                        <div><strong>CONDUCTOR TIPO</strong> → conductor_tipo (A o B)</div>
                        <div><strong>RH</strong> → rh</div>
                        <div><strong>VEHICULO PLACA</strong> → vehículo</div>
                        <div><strong>NUMERO INTERNO</strong> → numero_interno</div>
                        <div><strong>CELULAR</strong> → celular</div>
                        <div><strong>CORREO</strong> → correo</div>
                        <div><strong>FECHA DE NACIMIENTO</strong> → fecha_nacimiento</div>
                        <div><strong>¿SABE OTRA PROFESIÓN A PARTE DE SER CONDUCTOR?</strong> → otra_profesion</div>
                        <div><strong>CARGUE SU FOTO PARA CARNET</strong> → foto (URL Google Drive)</div>
                        <div><strong>NIVEL DE ESTUDIOS</strong> → nivel_estudios</div>
                    </div>
                </div>

                <!-- Nota sobre fotos de Google Drive -->
                <div class="{{ $bgWarning }} border {{ $textWarning }} px-4 py-3 rounded-lg text-sm hidden">
                    <p class="font-semibold mb-1">⚠️ Importante sobre las fotos:</p>
                    <p>El campo "CARGUE SU FOTO PARA CARNET" debe contener una URL de Google Drive. El sistema descargará automáticamente la imagen y la subirá a la aplicación.</p>
                </div>

                <!-- Botones -->
                <div class="flex justify-end space-x-4 pt-4">
                    <a href="{{ route('conductores.index') }}"
                       class="px-6 py-2 {{ $isDark ? 'bg-gray-700 hover:bg-gray-600' : 'bg-gray-600 hover:bg-gray-700' }} text-white rounded-lg transition">
                        Cancelar
                    </a>
                    <button type="submit" id="btn-importar"
                            class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="btn-text">Importar Archivo</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Panel de Progreso y Logs (visible cuando hay una importación activa) -->
        <div id="panel-progreso" class="{{ $bgCard }} rounded-lg shadow-md border {{ $borderCard }} p-6 {{ $currentSessionId ? '' : 'hidden' }}">
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-2xl font-bold {{ $textTitle }}">Progreso de Importación</h3>
                    <div id="spinner-container" class="hidden">
                        <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Información de tiempo -->
                <div id="tiempo-info" class="mb-4 flex gap-4 text-sm {{ $textSubtitle }}">
                    <div>
                        <span class="font-semibold">⏱ Tiempo transcurrido:</span>
                        <span id="tiempo-transcurrido" class="ml-2 font-mono">0s</span>
                    </div>
                    <div id="tiempo-restante-container" class="hidden">
                        <span class="font-semibold">⏳ Tiempo estimado restante:</span>
                        <span id="tiempo-restante" class="ml-2 font-mono">--</span>
                    </div>
                </div>

                <!-- Barra de progreso principal -->
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium {{ $textBody }}" id="estado-proceso">Esperando inicio...</span>
                        <span class="text-sm font-semibold {{ $textTitle }}" id="porcentaje-progreso">0%</span>
                    </div>
                    <div class="w-full {{ $isDark ? 'bg-gray-700' : 'bg-gray-200' }} rounded-full h-6">
                        <div id="barra-progreso" class="bg-blue-600 h-6 rounded-full transition-all duration-300 flex items-center justify-center text-white text-xs font-semibold" style="width: 0%">
                            <span id="porcentaje-barra">0%</span>
                        </div>
                    </div>
                    <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm {{ $textSubtitle }}">
                        <div>
                            <span class="font-semibold">Procesados:</span>
                            <span id="procesados" class="ml-2 font-mono">0</span>
                            <span id="total-text" class="ml-1">/ 0</span>
                        </div>
                        <div class="text-green-600 dark:text-green-400">
                            <span class="font-semibold">✓ Importados:</span>
                            <span id="importados" class="ml-2 font-mono">0</span>
                        </div>
                        <div class="text-yellow-600 dark:text-yellow-400">
                            <span class="font-semibold">⚠ Duplicados:</span>
                            <span id="duplicados" class="ml-2 font-mono">0</span>
                        </div>
                        <div class="text-red-600 dark:text-red-400">
                            <span class="font-semibold">✗ Errores:</span>
                            <span id="errores-count" class="ml-2 font-mono">0</span>
                        </div>
                    </div>
                </div>

                <!-- Log de importación -->
                <div class="mb-4">
                    <h4 class="text-sm font-semibold {{ $textTitle }} mb-2">Log de Importación:</h4>
                    <div id="log-container" class="{{ $isDark ? 'bg-gray-900' : 'bg-gray-50' }} border {{ $borderCard }} rounded-lg p-4 h-96 overflow-y-auto font-mono text-xs {{ $textBody }}">
                        <div id="log-content">
                            <div class="text-blue-600">Esperando inicio de importación...</div>
                        </div>
                    </div>
                </div>

                <!-- Mensajes de resultado -->
                <div id="resultado-container" class="hidden">
                    <div id="mensaje-exito" class="hidden {{ $bgSuccess }} border {{ $textSuccess }} px-4 py-3 rounded-lg mb-4"></div>
                    <div id="mensaje-error" class="hidden {{ $bgError }} border {{ $textError }} px-4 py-3 rounded-lg mb-4"></div>
                </div>
                
                <div class="mt-4 flex justify-end">
                    <button id="btn-redirigir" onclick="window.location.href='{{ route('conductores.index') }}'" 
                            class="hidden px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md">
                        Ver Conductores
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let sessionId = '{{ $currentSessionId }}';
        let progressInterval = null;
        let tiempoTranscurridoInterval = null;
        let tiempoInicio = null;

        // Si hay un session_id, iniciar polling automáticamente
        if (sessionId) {
            document.getElementById('panel-progreso').classList.remove('hidden');
            iniciarPolling();
            iniciarContadorTiempo();
        }

        document.getElementById('import-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const archivo = document.getElementById('archivo').files[0];
            
            if (!archivo) {
                alert('Por favor seleccione un archivo');
                return;
            }

            // Mostrar panel de progreso y spinner
            document.getElementById('panel-progreso').classList.remove('hidden');
            document.getElementById('spinner-container').classList.remove('hidden');
            document.getElementById('btn-importar').disabled = true;
            document.getElementById('btn-text').textContent = 'Procesando...';
            document.getElementById('btn-spinner').classList.remove('hidden');
            agregarLog('Iniciando carga del archivo...', 'info');
            
            tiempoInicio = Date.now();
            iniciarContadorTiempo();

            // Subir archivo
            fetch('{{ route("conductores.import.store") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async response => {
                if (!response.ok) {
                    const text = await response.text();
                    throw new Error(`Error del servidor (${response.status}): ${text.substring(0, 200)}`);
                }
                
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.includes("application/json")) {
                    return response.json();
                } else {
                    const text = await response.text();
                    throw new Error('El servidor retornó una respuesta inesperada.');
                }
            })
            .then(data => {
                if (data.success) {
                    agregarLog('Archivo cargado correctamente. Procesando en segundo plano...', 'success');
                    sessionId = data.session_id;
                    
                    // Guardar session_id en localStorage para persistencia
                    localStorage.setItem('import_session_id', sessionId);
                    
                    if (data.estado === 'completado') {
                        mostrarResultado(data);
                    } else {
                        iniciarPolling();
                    }
                } else {
                    agregarLog('Error: ' + (data.message || 'Error desconocido'), 'error');
                    mostrarError(data.message || 'Error al iniciar la importación');
                }
            })
            .catch(error => {
                agregarLog('Error de conexión: ' + error.message, 'error');
                mostrarError('Error al iniciar la importación: ' + error.message);
            });
        });

        function iniciarPolling() {
            if (!sessionId) return;
            
            if (progressInterval) {
                clearInterval(progressInterval);
            }
            
            progressInterval = setInterval(() => {
                fetch(`{{ route('conductores.import.progreso', ':sessionId') }}`.replace(':sessionId', sessionId), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(async response => {
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.includes("application/json")) {
                        return response.json();
                    } else {
                        const text = await response.text();
                        throw new Error('Error en la respuesta del servidor');
                    }
                })
                .then(data => {
                    if (data.success) {
                        actualizarProgreso(data);
                        
                        if (data.estado === 'completado' || data.estado === 'error') {
                            clearInterval(progressInterval);
                            document.getElementById('spinner-container').classList.add('hidden');
                            
                            if (data.estado === 'completado') {
                                mostrarResultado(data);
                            } else {
                                mostrarError(data.mensaje || 'Error en la importación');
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error al obtener progreso:', error);
                });
            }, 1000); // Polling cada 1 segundo
        }

        function iniciarContadorTiempo() {
            if (tiempoTranscurridoInterval) {
                clearInterval(tiemempoTranscurridoInterval);
            }
            
            tiempoTranscurridoInterval = setInterval(() => {
                if (!tiempoInicio) return;
                
                const ahora = Date.now();
                const segundos = Math.floor((ahora - tiempoInicio) / 1000);
                const minutos = Math.floor(segundos / 60);
                const horas = Math.floor(minutos / 60);
                
                let tiempoTexto = '';
                if (horas > 0) {
                    tiempoTexto = `${horas}h ${minutos % 60}m ${segundos % 60}s`;
                } else if (minutos > 0) {
                    tiempoTexto = `${minutos}m ${segundos % 60}s`;
                } else {
                    tiempoTexto = `${segundos}s`;
                }
                
                document.getElementById('tiempo-transcurrido').textContent = tiempoTexto;
            }, 1000);
        }

        function actualizarProgreso(data) {
            // Actualizar barra de progreso
            const porcentaje = Math.min(data.progreso || 0, 100);
            document.getElementById('barra-progreso').style.width = porcentaje + '%';
            document.getElementById('porcentaje-progreso').textContent = porcentaje.toFixed(1) + '%';
            document.getElementById('porcentaje-barra').textContent = porcentaje.toFixed(0) + '%';
            
            // Actualizar contadores
            document.getElementById('procesados').textContent = data.procesados || 0;
            if (data.total) {
                document.getElementById('total-text').textContent = `/ ${data.total}`;
            }
            document.getElementById('importados').textContent = data.importados || 0;
            document.getElementById('duplicados').textContent = data.duplicados || 0;
            document.getElementById('errores-count').textContent = data.errores_count || (data.errores && data.errores.length) || 0;
            
            // Actualizar estado
            document.getElementById('estado-proceso').textContent = data.mensaje || 'Procesando...';
            
            // Actualizar tiempo si viene del servidor
            if (data.tiempo_transcurrido) {
                document.getElementById('tiempo-transcurrido').textContent = data.tiempo_transcurrido;
            }
            if (data.tiempo_estimado_restante) {
                document.getElementById('tiempo-restante').textContent = data.tiempo_estimado_restante;
                document.getElementById('tiempo-restante-container').classList.remove('hidden');
            }
            
            // Actualizar logs
            if (data.log && data.log.length > 0) {
                const logContent = document.getElementById('log-content');
                const existingLogs = Array.from(logContent.children).map(el => el.textContent.trim());
                
                data.log.forEach(entry => {
                    const logExists = existingLogs.some(existing => existing.includes(entry.mensaje));
                    if (!logExists) {
                        agregarLog(entry.mensaje, entry.tipo || 'info');
                    }
                });
                
                logContent.scrollTop = logContent.scrollHeight;
            }
        }

        function agregarLog(mensaje, tipo = 'info') {
            const logContent = document.getElementById('log-content');
            const logEntry = document.createElement('div');
            const timestamp = new Date().toLocaleTimeString();
            
            let colorClass = '';
            let icon = '';
            switch(tipo) {
                case 'success':
                    colorClass = '{{ $isDark ? "text-green-400" : "text-green-600" }}';
                    icon = '✓';
                    break;
                case 'error':
                    colorClass = '{{ $isDark ? "text-red-400" : "text-red-600" }}';
                    icon = '✗';
                    break;
                case 'warning':
                    colorClass = '{{ $isDark ? "text-yellow-400" : "text-yellow-600" }}';
                    icon = '⚠';
                    break;
                default:
                    colorClass = '{{ $isDark ? "text-blue-400" : "text-blue-600" }}';
                    icon = 'ℹ';
            }
            
            logEntry.className = `mb-1 ${colorClass}`;
            logEntry.innerHTML = `[${timestamp}] <strong>${icon}</strong> ${mensaje}`;
            
            logContent.appendChild(logEntry);
            
            // Auto-scroll
            const logContainer = document.getElementById('log-container');
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        function mostrarResultado(data) {
            document.getElementById('resultado-container').classList.remove('hidden');
            document.getElementById('spinner-container').classList.add('hidden');
            
            let mensaje = `Importación completada. Importados: ${data.importados || 0}`;
            if (data.duplicados > 0) {
                mensaje += `, Duplicados omitidos: ${data.duplicados}`;
            }
            if (data.errores && data.errores.length > 0) {
                mensaje += `, Errores: ${data.errores.length}`;
            }
            
            document.getElementById('mensaje-exito').classList.remove('hidden');
            document.getElementById('mensaje-exito').textContent = mensaje;
            document.getElementById('btn-redirigir').classList.remove('hidden');
            document.getElementById('btn-importar').disabled = false;
            document.getElementById('btn-text').textContent = '📥 Importar Archivo';
            document.getElementById('btn-spinner').classList.add('hidden');
            
            agregarLog('✅ Importación finalizada correctamente', 'success');
        }

        function mostrarError(mensaje) {
            document.getElementById('resultado-container').classList.remove('hidden');
            document.getElementById('spinner-container').classList.add('hidden');
            document.getElementById('mensaje-error').classList.remove('hidden');
            document.getElementById('mensaje-error').textContent = mensaje;
            document.getElementById('btn-importar').disabled = false;
            document.getElementById('btn-text').textContent = '📥 Importar Archivo';
            document.getElementById('btn-spinner').classList.add('hidden');
            
            agregarLog('✗ Error: ' + mensaje, 'error');
        }

        // Limpiar localStorage cuando la importación se complete
        if (sessionId && progressInterval) {
            window.addEventListener('beforeunload', function() {
                // El polling continuará cuando regrese
            });
        }
    </script>
</x-app-layout>
