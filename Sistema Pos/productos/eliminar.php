<?php
// Evitar cualquier output antes del JSON
ob_start();
header('Content-Type: application/json; charset=utf-8');

// Capturar errores de conexión
try {
    include("../config/conexion.php");
    // Limpiar cualquier output que haya generado el include
    ob_clean();
    
    // Verificar si la conexión falló
    if (!isset($conexion) || $conexion->connect_error) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
        exit;
    }
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Leer datos JSON del cuerpo de la petición
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Si no se puede decodificar JSON o está vacío, intentar leer desde $_POST como fallback
if (empty($json) || ($data === null && json_last_error() !== JSON_ERROR_NONE)) {
    $codigo = trim($_POST['codigo'] ?? '');
} else {
    $codigo = trim($data['codigo'] ?? '');
} 

// Validación
if (empty($codigo)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'El código es obligatorio']);
    exit;
}

// Verificar que el producto existe
$stmt = $conexion->prepare("SELECT codigo FROM productos WHERE codigo = ?");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'El producto no existe']);
    $stmt->close();
    exit;
}
$stmt->close();

// Verificar si existe el campo 'activo' en la tabla productos
$checkField = $conexion->query("SHOW COLUMNS FROM productos LIKE 'activo'");
$tieneCampoActivo = $checkField->num_rows > 0;

// Si no existe el campo 'activo', intentar agregarlo
if (!$tieneCampoActivo) {
    try {
        $conexion->query("ALTER TABLE productos ADD COLUMN activo TINYINT(1) DEFAULT 1 AFTER cantidad");
        $conexion->query("UPDATE productos SET activo = 1 WHERE activo IS NULL");
        $tieneCampoActivo = true;
    } catch (Exception $e) {
        // Si no se puede agregar el campo, continuar con eliminación física
    }
}

// Verificar si el producto tiene ventas asociadas
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM detalle_ventas WHERE codigo_producto = ?");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$resultado = $stmt->get_result();
$ventas = $resultado->fetch_assoc();
$stmt->close();

$tieneVentas = $ventas['total'] > 0;

// Si tiene ventas y existe el campo activo, usar eliminación suave
// Si no tiene ventas, eliminar físicamente
if ($tieneVentas && $tieneCampoActivo) {
    // Eliminación suave: marcar como inactivo
    try {
        $stmt = $conexion->prepare("UPDATE productos SET activo = 0 WHERE codigo = ?");
        $stmt->bind_param("s", $codigo);
        
        ob_end_clean();
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => '✓ Producto desactivado exitosamente (tiene ' . $ventas['total'] . ' venta(s) asociada(s), se mantiene en el historial)'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al desactivar el producto: ' . $stmt->error
            ]);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Error al desactivar el producto: ' . $e->getMessage()
        ]);
    }
} else if ($tieneVentas && !$tieneCampoActivo) {
    // Tiene ventas pero no se puede usar eliminación suave
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'No se puede eliminar el producto porque tiene ' . $ventas['total'] . ' venta(s) asociada(s). Ejecuta el script SQL en productos/agregar_campo_activo.sql para habilitar la eliminación suave.'
    ]);
} else {
    // No tiene ventas, eliminar físicamente
    try {
        $stmt = $conexion->prepare("DELETE FROM productos WHERE codigo = ?");
        $stmt->bind_param("s", $codigo);
        
        ob_end_clean();
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => '✓ Producto eliminado exitosamente'
            ]);
        } else {
            // Capturar el error específico
            $error = $stmt->error;
            $errorCode = $stmt->errno;
            
            // Verificar si es un error de restricción de clave foránea
            if ($errorCode == 1451 || strpos($error, 'Cannot delete or update a parent row') !== false) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No se puede eliminar el producto porque tiene registros relacionados. El sistema intentará usar eliminación suave.'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al eliminar el producto: ' . $error
                ]);
            }
        }
        
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        ob_end_clean();
        $errorMsg = $e->getMessage();
        
        if (strpos($errorMsg, 'Cannot delete or update a parent row') !== false) {
            echo json_encode([
                'success' => false,
                'message' => 'No se puede eliminar el producto porque tiene registros relacionados en el sistema.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al eliminar el producto: ' . $errorMsg
            ]);
        }
    }
}

$conexion->close();
?>