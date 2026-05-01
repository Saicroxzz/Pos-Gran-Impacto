<?php
require_once dirname(__DIR__) . '/config/conexion.php';

// Función principal para coordinar el backup
function ejecutarBackup($isManual = true) {
    global $host, $usuario, $password, $base_datos;
    
    // Configuración
    $backupDir = dirname(__DIR__) . '/backups/';
    $today = date('Y-m-d');
    $targetDir = $backupDir . $today . '/';
    
    // Crear carpeta del día si no existe
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // Nombre del archivo con timestamp
    $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $filePath = $targetDir . $filename;
    
    $success = generateBackup($host, $usuario, $password, $base_datos, $filePath);
    
    if ($isManual) {
        if ($success) {
            echo json_encode(['success' => true, 'message' => "Copia de seguridad creada con éxito en la carpeta $today", 'file' => $filename]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al generar la copia de seguridad. Verifique la configuración de mysqldump.']);
        }
    }
    
    return $success;
}

/**
 * Función para realizar el backup
 * Intenta usar mysqldump si está disponible, de lo contrario devuelve error
 */
function generateBackup($host, $user, $pass, $db, $outputPath) {
    // Intentar encontrar mysqldump en rutas comunes de XAMPP si no está en el PATH
    $mysqldumpPath = 'mysqldump'; 
    
    // En Windows XAMPP típico
    $possiblePaths = [
        'mysqldump',
        'C:\xampp\mysql\bin\mysqldump.exe',
        'D:\xampp\mysql\bin\mysqldump.exe'
    ];
    
    $success = false;
    
    foreach ($possiblePaths as $path) {
        // En Windows el comando debe estar entre comillas si tiene espacios, y los argumentos también si es necesario
        // Redirigir stderr a null para evitar que warnings rompan el json si se captura salida
        $command = "\"$path\" --user=\"$user\" " . ($pass ? "--password=\"$pass\"" : "") . " --host=\"$host\" \"$db\" > \"$outputPath\" 2>&1";
        
        // Ejecutar comando
        system($command, $returnVar);
        
        // Verificar tamaño del archivo para asegurarse que no está vacío
        if ($returnVar === 0 && file_exists($outputPath) && filesize($outputPath) > 0) {
            $success = true;
            break;
        }
    }
    
    return $success;
}

// Ejecutar solo si el archivo es llamado directamente (AJAX)
// Comparamos rutas normalizadas para compatibilidad Windows
$current_script = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']));
$this_file = str_replace('\\', '/', realpath(__FILE__));

if ($current_script === $this_file) {
    ejecutarBackup(true);
}
?>
