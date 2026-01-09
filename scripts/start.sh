#!/bin/bash
set -e

echo "=== Iniciando servicios de Coopuertos ==="

# Configurar permisos
chmod -R 775 /app/storage /app/bootstrap/cache
chmod -R 777 /app/public/uploads

# Configurar directorios de almacenamiento
php /app/artisan storage:setup-directories 2>/dev/null || true
chmod -R 777 /app/public/uploads/conductores /app/public/uploads/vehiculos /app/public/uploads/pqrs /app/public/uploads/carnets 2>/dev/null || true

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
    echo "⚠️ Supervisor no disponible. Los workers no se iniciarán automáticamente."
fi

# Iniciar nginx y php-fpm
echo ""
echo "=== Iniciando Nginx y PHP-FPM ==="
node /assets/scripts/prestart.mjs /assets/nginx.template.conf /nginx.conf
php-fpm -y /assets/php-fpm.conf &
nginx -c /nginx.conf &

echo ""
echo "=== Todos los servicios iniciados ==="
echo "   - Supervisor: ${SUPERVISORD_CMD:-No disponible}"
echo "   - PHP-FPM: Ejecutándose"
echo "   - Nginx: Ejecutándose"

# Mantener el script corriendo
wait
