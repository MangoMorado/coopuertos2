#!/bin/bash
# Script de diagnóstico para worker de colas en contenedor (EasyPanel/Docker)

echo "=========================================="
echo "DIAGNÓSTICO DE WORKER DE COLAS"
echo "=========================================="
echo ""

# 1. Verificar si supervisor está instalado
echo "1. Verificando si supervisor está instalado..."
if command -v supervisorctl &> /dev/null; then
    echo "   ✓ supervisorctl encontrado: $(which supervisorctl)"
    SUPERVISOR_AVAILABLE=true
else
    echo "   ✗ supervisorctl NO encontrado"
    SUPERVISOR_AVAILABLE=false
    
    # Buscar en ubicaciones comunes
    echo "   Buscando supervisor en ubicaciones comunes..."
    if [ -f "/usr/bin/supervisorctl" ]; then
        echo "   ✓ Encontrado en /usr/bin/supervisorctl"
        SUPERVISOR_AVAILABLE=true
        export PATH="/usr/bin:$PATH"
    elif [ -f "/usr/local/bin/supervisorctl" ]; then
        echo "   ✓ Encontrado en /usr/local/bin/supervisorctl"
        SUPERVISOR_AVAILABLE=true
        export PATH="/usr/local/bin:$PATH"
    else
        echo "   ✗ Supervisor no está instalado"
    fi
fi
echo ""

# 2. Verificar procesos de queue:work
echo "2. Verificando procesos de queue:work..."
QUEUE_PROCESSES=$(ps aux | grep "queue:work" | grep -v grep | wc -l)
if [ "$QUEUE_PROCESSES" -gt 0 ]; then
    echo "   ✓ Worker encontrado ($QUEUE_PROCESSES proceso(s)):"
    ps aux | grep "queue:work" | grep -v grep
else
    echo "   ✗ NO hay procesos de queue:work ejecutándose"
fi
echo ""

# 3. Verificar jobs en la cola (desde Laravel)
echo "3. Verificando jobs en la cola..."
if [ -f "artisan" ]; then
    echo "   Verificando tabla jobs en base de datos..."
    php artisan tinker --execute="echo 'Jobs pendientes: ' . DB::table('jobs')->count() . PHP_EOL; echo 'Jobs fallidos: ' . DB::table('failed_jobs')->count() . PHP_EOL;"
else
    echo "   ⚠️  No se encontró artisan"
fi
echo ""

# 4. Verificar configuración de colas
echo "4. Verificando configuración de colas..."
if [ -f ".env" ]; then
    QUEUE_CONNECTION=$(grep "^QUEUE_CONNECTION=" .env | cut -d '=' -f2)
    echo "   QUEUE_CONNECTION: ${QUEUE_CONNECTION:-no configurado}"
else
    echo "   ⚠️  No se encontró archivo .env"
fi
echo ""

# 5. Verificar logs del worker
echo "5. Verificando logs del worker..."
if [ -f "storage/logs/worker.log" ]; then
    echo "   ✓ Archivo de log existe: storage/logs/worker.log"
    echo "   Últimas 20 líneas:"
    echo "   ----------------------------------------"
    tail -20 storage/logs/worker.log 2>/dev/null || echo "   (no se puede leer)"
    echo "   ----------------------------------------"
else
    echo "   ✗ No existe storage/logs/worker.log"
fi
echo ""

# 6. Verificar logs de Laravel
echo "6. Verificando logs de Laravel..."
if [ -f "storage/logs/laravel.log" ]; then
    echo "   ✓ Archivo de log existe"
    echo "   Últimas 10 líneas relacionadas con queue:"
    tail -100 storage/logs/laravel.log | grep -i "queue\|job\|worker" | tail -10 || echo "   (sin entradas relacionadas)"
else
    echo "   ⚠️  No existe storage/logs/laravel.log"
fi
echo ""

# 7. Verificar PHP y artisan
echo "7. Verificando PHP y Artisan..."
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -1)
    echo "   ✓ PHP: $PHP_VERSION"
    
    if [ -f "artisan" ]; then
        echo "   ✓ artisan encontrado"
        
        # Probar ejecutar un comando simple
        echo "   Probando comando de Artisan..."
        php artisan --version 2>&1 | head -1
    else
        echo "   ✗ artisan NO encontrado"
    fi
else
    echo "   ✗ PHP NO encontrado"
fi
echo ""

# 8. Verificar permisos y rutas
echo "8. Verificando permisos y rutas..."
PWD=$(pwd)
echo "   Directorio actual: $PWD"
echo "   Usuario actual: $(whoami)"
echo "   Permisos de storage/logs:"
ls -ld storage/logs 2>/dev/null || echo "   (directorio no existe)"
echo ""

# 9. Resumen y recomendaciones
echo "=========================================="
echo "RESUMEN"
echo "=========================================="
if [ "$QUEUE_PROCESSES" -eq 0 ]; then
    echo "❌ PROBLEMA: No hay worker ejecutándose"
    echo ""
    echo "SOLUCIÓN RECOMENDADA:"
    echo "1. Ejecutar el worker manualmente:"
    echo "   nohup php artisan queue:work --queue=importaciones,carnets --tries=3 --timeout=600 > storage/logs/worker.log 2>&1 &"
    echo ""
    echo "2. O verificar cómo EasyPanel ejecuta servicios (puede usar systemd, pm2, o un script de inicio)"
else
    echo "✓ Worker está ejecutándose"
fi
echo ""
echo "Para ver jobs pendientes, ejecuta:"
echo "  php artisan tinker"
echo "  >>> DB::table('jobs')->count();"
echo ""
