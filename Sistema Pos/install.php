<?php
/**
 * Script de Instalación del Sistema POS
 * Ejecutar una sola vez para configurar la base de datos
 */

$mensaje = '';
$tipo_mensaje = '';
$detalles = [];

// Procesar instalación al ejecutar el script
try {
    // 1. Conexión al servidor MySQL (sin base de datos)
    $host = 'localhost';
    $user = 'root';
    $pass = ''; // Por defecto en XAMPP es vacío
    
    // Suprimir advertencias para manejar errores limpiamente
    mysqli_report(MYSQLI_REPORT_OFF);
    $mysqli = @new mysqli($host, $user, $pass);
    
    if ($mysqli->connect_error) {
        throw new Exception("No se puede conectar al servidor MySQL. Verifique que XAMPP esté encendido. Error: " . $mysqli->connect_error);
    }
    
    $detalles[] = "Conexión al servidor establecida.";

    // 2. VERIFICACIÓN DE SEGURIDAD: Comprobar si la base de datos ya existe
    $db_name = 'pos_almacen';
    $check_db = $mysqli->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db_name'");
    
    if ($check_db && $check_db->num_rows > 0) {
        // La base de datos existe, verificar si tiene tablas
        $mysqli->select_db($db_name);
        $check_tables = $mysqli->query("SHOW TABLES");
        
        if ($check_tables && $check_tables->num_rows > 0) {
            // ALERTA: Ya instalada
            $mensaje = "El sistema YA está instalado";
            $tipo_mensaje = "warning";
            $detalles[] = "¡Alerta de Seguridad! Se detectó la base de datos '$db_name' con datos existentes.";
            $detalles[] = "Para evitar borrar su información, el instalador se ha detenido.";
            $detalles[] = "Puede ingresar al sistema normalmente.";
            
            // Cerrar y salir para no ejecutar nada de SQL
            $mysqli->close();
            goto render_html; // Saltar directo al HTML
        }
    }

    // 3. Si no existe o está vacía, proceder con la instalación
    $sql_create_db = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
    
    if (!$mysqli->query($sql_create_db)) {
        throw new Exception("Error al crear la base de datos: " . $mysqli->error);
    }
    
    $detalles[] = "Base de datos '$db_name' creada/verificada.";
    $mysqli->select_db($db_name);

    // 4. Leer y ejecutar el archivo SQL
    $sql_file = __DIR__ . '/pos_almacen.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("Falta el archivo 'pos_almacen.sql' en la carpeta.");
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // Separar queries
    $queries = explode(';', $sql_content);
    $executed = 0;
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            if (!$mysqli->query($query)) {
                // Si falla una query específica, lanzamos error (al ser instalación limpia)
                throw new Exception("Error instalando tabla: " . $mysqli->error);
            }
            $executed++;
        }
    }
    
    $detalles[] = "Base de datos importada correctamente ($executed sentencias).";
    
    $mensaje = "¡Instalación Completada con Éxito!";
    $tipo_mensaje = "success";
    $detalles[] = "El sistema ha sido configurado correctamente.";

    $mysqli->close();

} catch (Exception $e) {
    $mensaje = "Error en la Instalación";
    $tipo_mensaje = "error";
    $detalles[] = $e->getMessage();
}

render_html:
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Sistema POS</title>
    <style>
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        
        .install-card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
            animation: slideUp 0.5s ease-out;
        }
        
        h1 {
            color: #1a1a1a;
            margin-bottom: 10px;
            font-size: 24px;
        }
        
        .icon-container {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        
        .success .icon-container {
            background: #dcfce7;
            color: #166534;
        }
        
        .error .icon-container {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .icon {
            width: 40px;
            height: 40px;
        }
        
        .log-container {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
            font-family: monospace;
            font-size: 13px;
            color: #64748b;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .log-item {
            margin-bottom: 5px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        
        .log-item:last-child {
            border-bottom: none;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            margin-top: 10px;
        }
        
        .btn-primary {
            background: #2563eb;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1d4ed8;
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body class="<?= $tipo_mensaje ?>">

    <div class="install-card">
        <div class="icon-container">
            <?php if ($tipo_mensaje === 'success'): ?>
                <!-- Check Icon -->
                <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            <?php elseif ($tipo_mensaje === 'warning'): ?>
                <!-- Warning Icon -->
                <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="#f59e0b">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            <?php else: ?>
                <!-- X Icon -->
                <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            <?php endif; ?>
        </div>

        <h1><?= $mensaje ?></h1>

        <div class="log-container">
            <?php foreach ($detalles as $detalle): ?>
                <div class="log-item">
                    <?php if (strpos($detalle, 'Error') !== false) echo '❌ '; 
                          elseif (strpos($detalle, 'Alerta') !== false) echo '⚠️ ';
                          else echo '✅ '; ?>
                    <?= htmlspecialchars($detalle) ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($tipo_mensaje === 'success' || $tipo_mensaje === 'warning'): ?>
            <a href="index.php" class="btn btn-primary">Ir al Sistema</a>
        <?php else: ?>
            <button onclick="location.reload()" class="btn btn-primary">Reintentar</button>
        <?php endif; ?>
    </div>

</body>
</html>
