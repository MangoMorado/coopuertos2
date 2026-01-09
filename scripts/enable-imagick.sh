#!/bin/bash
set -e

echo "=== Habilitando extensión Imagick ==="

# Buscar imagick.so
IMAGICK_SO=$(find /nix/store -name 'imagick.so' 2>/dev/null | head -1)

if [ -z "$IMAGICK_SO" ]; then
    echo "✗ No se encontró imagick.so"
    exit 1
fi

echo "Encontrado imagick.so en: $IMAGICK_SO"

# Intentar encontrar el directorio de extensiones adicionales
PHP_INI_DIR=$(php --ini 2>/dev/null | grep 'Scan for additional .ini files' | awk '{print $NF}' || echo "")

if [ -n "$PHP_INI_DIR" ] && [ -d "$PHP_INI_DIR" ]; then
    # Crear archivo de configuración en el directorio de extensiones
    echo "extension=$IMAGICK_SO" > "$PHP_INI_DIR/20-imagick.ini"
    echo "✓ Extensión imagick habilitada en $PHP_INI_DIR/20-imagick.ini"
else
    # Si no hay directorio de extensiones, intentar modificar php.ini directamente
    PHP_INI=$(php --ini 2>/dev/null | grep 'Loaded Configuration File' | awk '{print $NF}' || echo "")
    
    if [ -n "$PHP_INI" ] && [ -f "$PHP_INI" ]; then
        if ! grep -q "imagick.so" "$PHP_INI"; then
            echo "extension=$IMAGICK_SO" >> "$PHP_INI"
            echo "✓ Extensión imagick habilitada en $PHP_INI"
        else
            echo "⚠️ imagick ya está configurado en $PHP_INI"
        fi
    else
        echo "✗ No se pudo encontrar php.ini ni directorio de extensiones"
        exit 1
    fi
fi

# Verificación final
echo ""
echo "=== Verificación final ==="
if php -m | grep -qi imagick; then
    echo "✓ Imagick está instalado y habilitado"
    php --ri imagick 2>/dev/null | head -5 || true
else
    echo "✗ Imagick NO está disponible después de la configuración"
    exit 1
fi
