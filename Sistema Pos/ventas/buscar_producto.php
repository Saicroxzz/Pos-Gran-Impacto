<?php
require_once "../config/conexion.php";

$q = $_GET['q'] ?? '';

// Si no hay búsqueda, salir
if (trim($q) === '') {
    exit;
}

// Preparar búsqueda por código, nombre o precio (solo productos activos)
$stmt = $conexion->prepare("
    SELECT codigo, nombre, precio, cantidad
    FROM productos
    WHERE (activo = 1 OR activo IS NULL)
      AND (codigo LIKE ? 
       OR nombre LIKE ? 
       OR CAST(precio AS CHAR) LIKE ?)
    ORDER BY 
        CASE 
            WHEN codigo = ? THEN 1
            WHEN codigo LIKE ? THEN 2
            WHEN nombre LIKE ? THEN 3
            ELSE 4
        END,
        nombre ASC
    LIMIT 10
");

$like = "%$q%";
$exact = $q;
$like_start = "$q%";

$stmt->bind_param("ssssss", $like, $like, $like, $exact, $like_start, $like_start);
$stmt->execute();
$result = $stmt->get_result();

// Si no hay resultados
if ($result->num_rows === 0) {
    echo "<div class='resultado-item' style='cursor: default; color: #95a5a6;'>
            ❌ Sin resultados para \"" . htmlspecialchars($q) . "\"
          </div>";
    exit;
}

// Mostrar resultados
while ($row = $result->fetch_assoc()) {
    // Verificar stock
    $stock_class = $row['cantidad'] > 0 ? '' : 'sin-stock';
    $stock_text = $row['cantidad'] > 0 
        ? "Stock: {$row['cantidad']}" 
        : "Sin stock";
    
    echo "
        <div class='resultado-item {$stock_class}'
             data-codigo='" . htmlspecialchars($row['codigo']) . "'
             data-nombre='" . htmlspecialchars($row['nombre']) . "'
             data-precio='{$row['precio']}'>
            <div style='display: flex; justify-content: space-between; align-items: center;'>
                <div>
                    <strong>" . htmlspecialchars($row['nombre']) . "</strong><br>
                    <small style='color: #7f8c8d;'>Código: " . htmlspecialchars($row['codigo']) . "</small>
                </div>
                <div style='text-align: right;'>
                    <div style='font-weight: bold; color: #27ae60; font-size: 16px;'>
                        $" . number_format($row['precio'], 0, ',', '.') . "
                    </div>
                    <small style='color: " . ($row['cantidad'] > 0 ? '#3498db' : '#e74c3c') . ";'>
                        {$stock_text}
                    </small>
                </div>
            </div>
        </div>
    ";
}

$stmt->close();
$conexion->close();
?>