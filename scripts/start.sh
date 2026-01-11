#!/bin/bash
set -e

echo "=== Iniciando servicios de Coopuertos ==="

# Configurar permisos y crear directorios necesarios
mkdir -p /app/storage/logs /app/storage/framework/cache /app/storage/framework/sessions /app/storage/framework/views
mkdir -p /app/bootstrap/cache
mkdir -p /app/public/uploads/carnets
mkdir -p /app/public/storage/carnet_previews /app/public/storage/carnets
mkdir -p /app/storage/app/carnets /app/storage/app/temp /app/storage/app/temp_imports /app/storage/app/public

# Configurar permisos
chmod -R 775 /app/storage /app/bootstrap/cache
chmod -R 777 /app/public/uploads
chmod -R 777 /app/public/storage 2>/dev/null || true

# Configurar directorios de almacenamiento
php /app/artisan storage:setup-directories 2>/dev/null || true

# Crear enlace simbólico de storage
php /app/artisan storage:link 2>/dev/null || true

# Configurar supervisor
echo ""
bash /app/scripts/setup-supervisor-start.sh

# Buscar supervisor en PATH o Nix
SUPERVISORD_CMD=""
if command -v supervisord &> /dev/null; then
    SUPERVISORD_CMD=$(which supervisord)
elif [ -n "$(find /nix/store -name 'supervisord' -type f 2>/dev/null | head -1)" ]; then
    SUPERVISORD_CMD=$(find /nix/store -name 'supervisord' -type f 2>/dev/null | head -1)
    export PATH="$(dirname ${SUPERVISORD_CMD}):${PATH}"
fi

# Iniciar supervisor si está disponible
if [ -n "${SUPERVISORD_CMD}" ] && [ -f "/etc/supervisor/supervisord.conf" ]; then
    echo ""
    echo "=== Iniciando Supervisor ==="
    ${SUPERVISORD_CMD} -c /etc/supervisor/supervisord.conf &
    SUPERVISORD_PID=$!
    echo "Supervisor iniciado (PID: ${SUPERVISORD_PID})"
    
    # Esperar a que supervisor esté listo
    sleep 3
    
    # Recargar y iniciar workers
    if command -v supervisorctl &> /dev/null || [ -n "$(find /nix/store -name 'supervisorctl' -type f 2>/dev/null | head -1)" ]; then
        SUPERVISORCTL_CMD=$(command -v supervisorctl || find /nix/store -name 'supervisorctl' -type f 2>/dev/null | head -1)
        if [ -n "${SUPERVISORCTL_CMD}" ]; then
            export PATH="$(dirname ${SUPERVISORCTL_CMD}):${PATH}"
            echo "Recargando configuración de supervisor..."
            ${SUPERVISORCTL_CMD} reread 2>/dev/null || true
            ${SUPERVISORCTL_CMD} update 2>/dev/null || true
            ${SUPERVISORCTL_CMD} start laravel-worker:* 2>/dev/null || true
            echo "✓ Workers iniciados"
        fi
    fi
else
    echo "⚠️ Supervisor no disponible. Iniciando worker directamente..."
    
    # Iniciar worker directamente en background
    echo "=== Iniciando Worker de Colas ==="
    
    # Asegurar que el directorio de logs existe
    mkdir -p /app/storage/logs
    
    # Iniciar worker en background
    # Usar nohup para asegurar que el proceso continúe si la shell termina
    nohup php /app/artisan queue:work \
        --queue=importaciones,carnets \
        --tries=3 \
        --timeout=600 \
        --max-time=3600 \
        --sleep=3 \
        --max-jobs=1000 \
        > /app/storage/logs/worker.log 2>&1 &
    
    WORKER_PID=$!
    echo "✓ Worker iniciado (PID: ${WORKER_PID})"
    echo "  Logs: /app/storage/logs/worker.log"
    
    # Dar un momento para que el worker inicie
    sleep 2
    
    # Verificar que el proceso sigue ejecutándose
    if ps -p ${WORKER_PID} > /dev/null 2>&1; then
        echo "✓ Worker verificado: proceso activo"
    else
        echo "⚠️  Advertencia: El worker pudo haber fallado al iniciar. Revisa los logs."
    fi
fi

# Iniciar nginx y php-fpm
echo ""
echo "=== Iniciando Nginx y PHP-FPM ==="
node /assets/scripts/prestart.mjs /assets/nginx.template.conf /nginx.conf
php-fpm -y /assets/php-fpm.conf &
nginx -c /nginx.conf &

echo ""
echo "=== Todos los servicios iniciados ==="
if [ -n "${SUPERVISORD_CMD}" ]; then
    echo "   - Supervisor: Ejecutándose (PID: ${SUPERVISORD_PID})"
else
    echo "   - Worker de Colas: Ejecutándose (PID: ${WORKER_PID})"
fi
echo "   - PHP-FPM: Ejecutándose"
echo "   - Nginx: Ejecutándose"

# Mantener el script corriendo
wait
