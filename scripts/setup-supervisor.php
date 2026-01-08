<?php

/**
 * Script de configuración automática de Supervisor para Laravel Queue Worker
 * 
 * Este script se ejecuta automáticamente durante composer install/post-install-cmd
 * y configura supervisor para ejecutar el worker de colas de Laravel.
 */

// Detectar la ruta del proyecto
$projectPath = getcwd();
if (!$projectPath) {
    $projectPath = dirname(__DIR__);
}

// Detectar el usuario actual
$currentUser = get_current_user();
if (empty($currentUser)) {
    $currentUser = exec('whoami 2>/dev/null') ?: 'www-data';
}

// Detectar la ruta de PHP
$phpBinary = PHP_BINARY;
if (empty($phpBinary)) {
    $phpBinary = exec('which php 2>/dev/null') ?: '/usr/bin/php';
}

// Configuración del worker
$workerName = 'laravel-worker';
$configFile = "/etc/supervisor/conf.d/{$workerName}.conf";
$artisanPath = "{$projectPath}/artisan";
$queueCommand = "{$phpBinary} {$artisanPath} queue:work --queue=importaciones,carnets --tries=3 --timeout=600 --max-time=3600 --sleep=3 --max-jobs=1000";

// Contenido del archivo de configuración
$configContent = <<<INI
[program:{$workerName}]
process_name=%(program_name)s_%(process_num)02d
command={$queueCommand}
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user={$currentUser}
numprocs=1
redirect_stderr=true
stdout_logfile={$projectPath}/storage/logs/worker.log
stopwaitsecs=3600
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5

INI;

// Función para mostrar mensajes
function message(string $text, string $type = 'info'): void
{
    $colors = [
        'info' => "\033[36m",    // Cyan
        'success' => "\033[32m",  // Green
        'warning' => "\033[33m",  // Yellow
        'error' => "\033[31m",    // Red
    ];
    $reset = "\033[0m";
    $color = $colors[$type] ?? $colors['info'];
    
    echo "{$color}{$text}{$reset}\n";
}

// Verificar si supervisor está instalado
$supervisorInstalled = shell_exec('which supervisorctl 2>/dev/null');
if (empty($supervisorInstalled)) {
    message("⚠️  Supervisor no está instalado. El archivo de configuración se creará pero no se habilitará automáticamente.", 'warning');
    message("   Instala supervisor con: sudo apt-get install supervisor (Ubuntu/Debian)", 'info');
    $canEnable = false;
} else {
    $canEnable = true;
}

// Verificar permisos para escribir en /etc/supervisor/conf.d/
$canWrite = is_writable('/etc/supervisor/conf.d/') || 
            (function_exists('shell_exec') && shell_exec('test -w /etc/supervisor/conf.d/ 2>/dev/null && echo 1'));

if (!$canWrite) {
    // Intentar con sudo
    $testSudo = shell_exec('sudo test -w /etc/supervisor/conf.d/ 2>/dev/null && echo 1');
    $canWrite = !empty($testSudo);
    $useSudo = $canWrite;
} else {
    $useSudo = false;
}

// Crear el archivo de configuración
message("📝 Configurando Supervisor para Laravel Queue Worker...", 'info');
message("   Proyecto: {$projectPath}", 'info');
message("   Usuario: {$currentUser}", 'info');
message("   PHP: {$phpBinary}", 'info');

if ($canWrite || $useSudo) {
    // Escribir el archivo temporal primero
    $tempFile = sys_get_temp_dir() . '/' . basename($configFile);
    file_put_contents($tempFile, $configContent);
    
    // Copiar al destino con o sin sudo
    if ($useSudo) {
        $result = shell_exec("sudo cp {$tempFile} {$configFile} 2>&1");
        $chownResult = shell_exec("sudo chown root:root {$configFile} 2>&1");
    } else {
        $result = shell_exec("cp {$tempFile} {$configFile} 2>&1");
    }
    
    unlink($tempFile);
    
    if (file_exists($configFile)) {
        message("✅ Archivo de configuración creado: {$configFile}", 'success');
        
        // Intentar habilitar el servicio
        if ($canEnable) {
            message("🔄 Intentando habilitar el servicio de supervisor...", 'info');
            
            $commands = [
                "sudo supervisorctl reread 2>&1",
                "sudo supervisorctl update 2>&1",
                "sudo supervisorctl start {$workerName}:* 2>&1",
            ];
            
            foreach ($commands as $cmd) {
                $output = shell_exec($cmd);
                if (!empty($output)) {
                    echo "   {$output}";
                }
            }
            
            // Verificar estado
            $status = shell_exec("sudo supervisorctl status {$workerName}:* 2>&1");
            if (!empty($status) && strpos($status, 'RUNNING') !== false) {
                message("✅ Worker de colas iniciado correctamente!", 'success');
            } else {
                message("⚠️  El servicio fue configurado pero no se pudo iniciar automáticamente.", 'warning');
                message("   Ejecuta manualmente:", 'info');
                message("   sudo supervisorctl reread", 'info');
                message("   sudo supervisorctl update", 'info');
                message("   sudo supervisorctl start {$workerName}:*", 'info');
            }
        } else {
            message("⚠️  Supervisor no está instalado. Instala y luego ejecuta:", 'warning');
            message("   sudo supervisorctl reread", 'info');
            message("   sudo supervisorctl update", 'info');
            message("   sudo supervisorctl start {$workerName}:*", 'info');
        }
    } else {
        message("❌ No se pudo crear el archivo de configuración.", 'error');
        message("   Crea manualmente el archivo {$configFile} con el siguiente contenido:", 'info');
        echo "\n{$configContent}\n";
    }
} else {
    message("⚠️  No se tienen permisos para escribir en /etc/supervisor/conf.d/", 'warning');
    message("   Crea manualmente el archivo {$configFile} con el siguiente contenido:", 'info');
    echo "\n{$configContent}\n";
    message("\n   Luego ejecuta:", 'info');
    message("   sudo supervisorctl reread", 'info');
    message("   sudo supervisorctl update", 'info');
    message("   sudo supervisorctl start {$workerName}:*", 'info');
}

message("\n📋 Para verificar el estado del worker:", 'info');
message("   sudo supervisorctl status {$workerName}:*", 'info');
message("   sudo supervisorctl tail -f {$workerName}:*", 'info');
