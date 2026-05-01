<?php
header('Content-Type: application/json');
include("../config/conexion.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$codigo = trim($_POST['codigo'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$precio = floatval($_POST['precio'] ?? 0);
$cantidad = intval($_POST['cantidad'] ?? 0);

// Validaciones
if (empty($codigo)) {
    echo json_encode(['success' => false, 'message' => 'El código es obligatorio']);
    exit;
}

if (empty($nombre)) {
    echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']);
    exit;
}

if ($precio <= 0) {
    echo json_encode(['success' => false, 'message' => 'El precio debe ser mayor a 0']);
    exit;
}

if ($cantidad < 0) {
    echo json_encode(['success' => false, 'message' => 'La cantidad no puede ser negativa']);
    exit;
}

// Verificar si existe el campo 'activo' en la tabla productos
$checkField = $conexion->query("SHOW COLUMNS FROM productos LIKE 'activo'");
$tieneCampoActivo = $checkField->num_rows > 0;

// Verificar si el código ya existe
$stmt = $conexion->prepare("SELECT codigo, activo FROM productos WHERE codigo = ?");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$resultado = $stmt->get_result();
$productoExistente = $resultado->fetch_assoc();
$stmt->close();

if ($productoExistente) {
    // El producto existe, verificar si está activo
    $estaActivo = $tieneCampoActivo ? ($productoExistente['activo'] == 1 || $productoExistente['activo'] === null) : true;
    
    if ($estaActivo) {
        // Producto activo, no se puede crear duplicado
        echo json_encode(['success' => false, 'message' => 'El código ya existe en el sistema']);
        exit;
    } else {
        // Producto inactivo, reactivarlo y actualizar datos
        if ($tieneCampoActivo) {
            $stmt = $conexion->prepare("
                UPDATE productos 
                SET nombre = ?, precio = ?, cantidad = ?, activo = 1
                WHERE codigo = ?
            ");
            $stmt->bind_param("sdis", $nombre, $precio, $cantidad, $codigo);
        } else {
            // Si no tiene campo activo, solo actualizar datos
            $stmt = $conexion->prepare("
                UPDATE productos 
                SET nombre = ?, precio = ?, cantidad = ?
                WHERE codigo = ?
            ");
            $stmt->bind_param("sdis", $nombre, $precio, $cantidad, $codigo);
        }
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => '✓ Producto reactivado exitosamente (el código ya existía pero estaba desactivado)'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al reactivar el producto: ' . $conexion->error
            ]);
        }
        $stmt->close();
        $conexion->close();
        exit;
    }
}

// El producto no existe, crear uno nuevo
if ($tieneCampoActivo) {
    $stmt = $conexion->prepare("
        INSERT INTO productos (codigo, nombre, precio, cantidad, activo)
        VALUES (?, ?, ?, ?, 1)
    ");
} else {
    $stmt = $conexion->prepare("
        INSERT INTO productos (codigo, nombre, precio, cantidad)
        VALUES (?, ?, ?, ?)
    ");
}

$stmt->bind_param("ssdi", $codigo, $nombre, $precio, $cantidad);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => '✓ Producto creado exitosamente'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al crear el producto: ' . $conexion->error
    ]);
}

$stmt->close();
$conexion->close();
?>