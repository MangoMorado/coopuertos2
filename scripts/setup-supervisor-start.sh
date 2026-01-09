#!/bin/bash
set -e

echo "=== Configurando Supervisor para Laravel Queue Worker ==="

# Detectar rutas
PROJECT_PATH="/app"
PHP_BINARY=$(which php)
ARTISAN_PATH="${PROJECT_PATH}/artisan"
WORKER_NAME="laravel-worker"
CONFIG_FILE="/etc/supervisor/conf.d/${WORKER_NAME}.conf"
SUPERVISORD_CONF="/etc/supervisor/supervisord.conf"
CURRENT_USER=$(whoami)

# Crear directorios de configuración si no existen
mkdir -p /etc/supervisor/conf.d
mkdir -p /var/log/supervisor

# Crear archivo de configuración principal de supervisor si no existe
if [ ! -f "${SUPERVISORD_CONF}" ]; then
    cat > "${SUPERVISORD_CONF}" <<EOF
[unix_http_server]
file=/var/run/supervisor.sock
chmod=0700

[supervisord]
nodaemon=true
logfile=/var/log/supervisor/supervisord.log
pidfile=/var/run/supervisord.pid
childlogdir=/var/log/supervisor

[rpcinterface:supervisor]
supervisor.rpcinterface_factory = supervisor.rpcinterface:make_main_rpcinterface

[supervisorctl]
serverurl=unix:///var/run/supervisor.sock

[include]
files = /etc/supervisor/conf.d/*.conf
EOF
    echo "✓ Archivo de configuración principal creado: ${SUPERVISORD_CONF}"
else
    echo "✓ Archivo de configuración principal ya existe: ${SUPERVISORD_CONF}"
fi

# Crear archivo de configuración del worker
cat > "${CONFIG_FILE}" <<EOF
[program:${WORKER_NAME}]
process_name=%(program_name)s_%(process_num)02d
command=${PHP_BINARY} ${ARTISAN_PATH} queue:work --queue=importaciones,carnets --tries=3 --timeout=600 --max-time=3600 --sleep=3 --max-jobs=1000
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=${CURRENT_USER}
numprocs=1
redirect_stderr=true
stdout_logfile=${PROJECT_PATH}/storage/logs/worker.log
stopwaitsecs=3600
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
EOF

echo "✓ Archivo de configuración creado: ${CONFIG_FILE}"

# Crear directorio de logs si no existe
mkdir -p "${PROJECT_PATH}/storage/logs"
touch "${PROJECT_PATH}/storage/logs/worker.log"
chmod 666 "${PROJECT_PATH}/storage/logs/worker.log"

echo "✓ Logs configurados"

# Buscar supervisor en PATH o en Nix store
SUPERVISORD_CMD=""
if command -v supervisord &> /dev/null; then
    SUPERVISORD_CMD=$(which supervisord)
    echo "✓ Supervisor encontrado en PATH: ${SUPERVISORD_CMD}"
else
    echo "Buscando supervisor en Nix store..."
    SUPERVISORD_CMD=$(find /nix/store -name "supervisord" -type f 2>/dev/null | head -1)
    if [ -n "${SUPERVISORD_CMD}" ]; then
        echo "✓ Supervisor encontrado en Nix: ${SUPERVISORD_CMD}"
        # Agregar al PATH temporalmente
        export PATH="$(dirname ${SUPERVISORD_CMD}):${PATH}"
    else
        echo "⚠️ Supervisor no encontrado. El worker puede no iniciarse automáticamente."
    fi
fi

# Buscar supervisorctl
SUPERVISORCTL_CMD=""
if command -v supervisorctl &> /dev/null; then
    SUPERVISORCTL_CMD=$(which supervisorctl)
else
    SUPERVISORCTL_CMD=$(find /nix/store -name "supervisorctl" -type f 2>/dev/null | head -1)
    if [ -n "${SUPERVISORCTL_CMD}" ]; then
        export PATH="$(dirname ${SUPERVISORCTL_CMD}):${PATH}"
    fi
fi

echo ""
echo "=== Resumen de configuración ==="
echo "   Worker: ${WORKER_NAME}"
echo "   Config: ${CONFIG_FILE}"
echo "   PHP: ${PHP_BINARY}"
echo "   Artisan: ${ARTISAN_PATH}"
echo "   Log: ${PROJECT_PATH}/storage/logs/worker.log"
echo ""
echo "=== Configuración completada ==="
