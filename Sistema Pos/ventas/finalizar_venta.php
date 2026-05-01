<?php
require_once "../config/conexion.php";

// Obtener datos JSON del cliente
$data = json_decode(file_get_contents("php://input"), true);

// Validar que los datos existan
if (!$data || !isset($data['productos']) || !isset($data['total']) || !isset($data['metodo'])) {
    echo json_encode([
        "ok" => false,
        "error" => "Datos incompletos"
    ]);
    exit;
}

$productos = $data['productos'];
$total = $data['total'];
$tipoPago = $data['metodo']; // EFECTIVO o TRANSFERENCIA
$montoRecibido = $data['monto_recibido'] ?? $total; // Si es transferencia, monto = total

$conexion->begin_transaction();

try {

    // 1. Insertar venta con monto_recibido
    $stmt = $conexion->prepare("
        INSERT INTO ventas (total, tipo_pago, monto_recibido)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("dsi", $total, $tipoPago, $montoRecibido);
    $stmt->execute();

    $idVenta = $stmt->insert_id;

    // 2. Insertar detalle y descontar inventario
    foreach ($productos as $p) {

        $subtotal = $p['cantidad'] * $p['precio'];

        // Insertar detalle_venta
        $stmt = $conexion->prepare("
            INSERT INTO detalle_ventas
            (id_venta, codigo_producto, nombre_producto, cantidad, precio_unitario, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "issidd",
            $idVenta,
            $p['codigo'],
            $p['nombre'],
            $p['cantidad'],
            $p['precio'],
            $subtotal
        );
        $stmt->execute();

        // Descontar stock del inventario
        $stmt = $conexion->prepare("
            UPDATE productos
            SET cantidad = cantidad - ?
            WHERE codigo = ?
        ");
        $stmt->bind_param("is", $p['cantidad'], $p['codigo']);
        $stmt->execute();
    }

    $conexion->commit();

    echo json_encode([
        "ok" => true,
        "id_venta" => $idVenta
    ]);

} catch (Exception $e) {

    $conexion->rollback(); // CORREGIDO: era $conexionn
    echo json_encode([
        "ok" => false,
        "error" => $e->getMessage()
    ]);
}
?>