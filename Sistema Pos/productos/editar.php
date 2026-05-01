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

// Verificar que el producto existe
$stmt = $conexion->prepare("SELECT codigo FROM productos WHERE codigo = ?");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'El producto no existe']);
    exit;
}

// Actualizar producto
$stmt = $conexion->prepare("
    UPDATE productos 
    SET nombre = ?, precio = ?, cantidad = ?
    WHERE codigo = ?
");

$stmt->bind_param("sdis", $nombre, $precio, $cantidad, $codigo);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => '✓ Producto actualizado exitosamente'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar el producto: ' . $conexion->error
    ]);
}

$stmt->close();
$conexion->close();
?>