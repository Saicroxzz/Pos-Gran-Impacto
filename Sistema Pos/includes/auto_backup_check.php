<?php
// Script para verificar y ejecutar copias de seguridad automáticas
// Se debe incluir en un archivo común como config/conexion.php

function verificarAutoBackup() {
    // Definir archivo de registro
    $logDir = dirname(__DIR__) . '/backups/';
    
    // Asegurar que existe el directorio
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $logFile = $logDir . 'last_auto_backup.log';
    $lastRun = 0;
    
    // Leer última ejecución
    if (file_exists($logFile)) {
        $lastRun = (int)file_get_contents($logFile);
    }
    
    // 7 días en segundos = 7 * 24 * 60 * 60 = 604800
    // Usamos 604800
    $interval = 604800;
    
    if (time() - $lastRun > $interval) {
        // Ejecutar backup
        require_once __DIR__ . '/backup_db.php';
        
        // Ejecutar silenciosamente (false = no echo JSON)
        // La función utiliza variables globales $host, $usuario etc. que deben estar disponibles
        $success = ejecutarBackup(false);
        
        if ($success) {
            // Actualizar timestamp solo si hubo éxito
            file_put_contents($logFile, time());
            // Opcional: Loguear éxito en algún lado o dejar notificacion silenciosa
        }
    }
}

// Ejecutar verificación
verificarAutoBackup();
?>
