<?php
// Configuración de la conexión a la base de datos
$host = "localhost";
$usuario = "root";
$password = "";
$base_datos = "pos_almacen";

// Crear conexión
$conexion = new mysqli($host, $usuario, $password, $base_datos);

// Verificar conexión
if ($conexion->connect_error) {
    // Si estamos en una petición AJAX, devolver JSON en lugar de die()
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
        (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $conexion->connect_error]);
        exit;
    }
    die("Error de conexión: " . $conexion->connect_error);
}

// Establecer zona horaria a Colombia
date_default_timezone_set('America/Bogota');

// Establecer el conjunto de caracteres
$conexion->set_charset("utf8");

// Incluir sistema de backup automático
include_once dirname(__DIR__) . '/includes/auto_backup_check.php';
?>
