<?php
include("../config/conexion.php");
date_default_timezone_set('America/Bogota');
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'spanish');

// Función para obtener fecha en español
function fechaEspanol($fecha) {
    $dias = array('Sunday' => 'Domingo', 'Monday' => 'Lunes', 'Tuesday' => 'Martes', 
                  'Wednesday' => 'Miércoles', 'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado');
    $meses = array('January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo', 
                   'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio', 
                   'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre', 
                   'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre');
    
    $dia_nombre = $dias[date('l', strtotime($fecha))];
    $dia_numero = date('d', strtotime($fecha));
    $mes_nombre = $meses[date('F', strtotime($fecha))];
    $anio = date('Y', strtotime($fecha));
    
    return "$dia_nombre, $dia_numero de $mes_nombre de $anio";
}

// Obtener totales por tipo de pago
$query_totales = $conexion->query("
    SELECT tipo_pago, SUM(total) as total
    FROM ventas
    WHERE DATE(fecha_venta) = CURDATE()
    GROUP BY tipo_pago
");

$totales = ['EFECTIVO' => 0, 'TRANSFERENCIA' => 0];
while($row = $query_totales->fetch_assoc()){
    $totales[$row['tipo_pago']] = $row['total'];
}
$total_general = $totales['EFECTIVO'] + $totales['TRANSFERENCIA'];

// Obtener cantidad de ventas del día
$query_num_ventas = $conexion->query("
    SELECT COUNT(id_venta) as num_ventas
    FROM ventas
    WHERE DATE(fecha_venta) = CURDATE()
");
$num_ventas = $query_num_ventas->fetch_assoc()['num_ventas'];

// Obtener todas las ventas del día con sus detalles
$query_ventas = $conexion->query("
    SELECT v.id_venta, v.total, v.tipo_pago, v.fecha_venta, v.monto_recibido
    FROM ventas v
    WHERE DATE(v.fecha_venta) = CURDATE()
    ORDER BY v.fecha_venta DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Diario</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f5f7fa;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 1.75rem;
            margin-bottom: 6px;
            font-weight: 600;
        }
        
        .header .fecha {
            font-size: 0.95rem;
            opacity: 0.9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            border-left: 4px solid #3498db;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        .stat-card h3 {
            font-size: 0.75rem;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .stat-card .value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .stat-card.efectivo { border-left-color: #27ae60; }
        .stat-card.efectivo .value { color: #27ae60; }
        
        .stat-card.transferencia { border-left-color: #3498db; }
        .stat-card.transferencia .value { color: #3498db; }
        
        .stat-card.total { border-left-color: #e74c3c; }
        .stat-card.total .value { color: #e74c3c; }
        
        .stat-card.num-ventas { border-left-color: #f39c12; }
        .stat-card.num-ventas .value { color: #f39c12; }
        
        .ventas-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        
        .ventas-section h2 {
            color: #2c3e50;
            font-size: 1.25rem;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #3498db;
            font-weight: 600;
        }
        
        .venta-item {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
        }
        
        .venta-item:hover {
            border-color: #3498db;
            box-shadow: 0 3px 12px rgba(52, 152, 219, 0.15);
        }
        
        .venta-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .venta-id {
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .venta-hora {
            color: #7f8c8d;
            font-size: 0.875rem;
        }
        
        .venta-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 0.7rem;
            color: #95a5a6;
            text-transform: uppercase;
            margin-bottom: 4px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        .info-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge.efectivo {
            background: #d5f4e6;
            color: #27ae60;
        }
        
        .badge.transferencia {
            background: #d6eaf8;
            color: #3498db;
        }
        
        .detalles-venta {
            display: none;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #e0e0e0;
        }
        
        .detalles-venta.active {
            display: block;
        }
        
        .productos-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .productos-table th {
            background: #f8f9fa;
            padding: 10px;
            text-align: left;
            font-size: 0.75rem;
            color: #7f8c8d;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        .productos-table td {
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            color: #2c3e50;
            font-size: 0.875rem;
        }
        
        .productos-table tr:last-child td {
            border-bottom: none;
        }
        
        .toggle-icon {
            float: right;
            color: #3498db;
            font-size: 1.125rem;
            transition: transform 0.3s ease;
        }
        
        .venta-item.expanded .toggle-icon {
            transform: rotate(180deg);
        }
        
        .btn-factura {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            background: #27ae60;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(39, 174, 96, 0.2);
        }
        
        .btn-factura:hover {
            background: #229954;
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(39, 174, 96, 0.3);
        }
        
        .btn-volver {
            display: inline-block;
            padding: 12px 24px;
            background: #2c3e50;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.2);
            transition: all 0.3s ease;
        }
        
        .btn-volver:hover {
            background: #34495e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #95a5a6;
        }
        
        .empty-state p {
            font-size: 1rem;
            margin-top: 10px;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .venta-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Reporte del Día</h1>
        <div class="fecha"><?= fechaEspanol(date('Y-m-d')) ?></div>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card efectivo">
            <h3>Efectivo</h3>
            <div class="value">$<?= number_format($totales['EFECTIVO'], 0, ',', '.') ?></div>
        </div>
        
        <div class="stat-card transferencia">
            <h3>Transferencia</h3>
            <div class="value">$<?= number_format($totales['TRANSFERENCIA'], 0, ',', '.') ?></div>
        </div>
        
        <div class="stat-card total">
            <h3>Total General</h3>
            <div class="value">$<?= number_format($total_general, 0, ',', '.') ?></div>
        </div>
        
        <div class="stat-card num-ventas">
            <h3>Número de Ventas</h3>
            <div class="value"><?= $num_ventas ?></div>
        </div>
    </div>
    
    <div class="ventas-section">
        <h2>Detalle de Ventas del Día</h2>
        
        <?php if($query_ventas->num_rows > 0): ?>
            <?php while($venta = $query_ventas->fetch_assoc()): ?>
                <div class="venta-item" onclick="toggleDetalle(<?= $venta['id_venta'] ?>)">
                    <div class="venta-header">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <span class="venta-id">Venta #<?= $venta['id_venta'] ?></span>
                            <a href="../ventas/factura.php?id=<?= $venta['id_venta'] ?>&cliente=Consumidor Final" 
                               target="_blank"
                               class="btn-factura"
                               title="Ver/Imprimir Factura">
                                Factura
                            </a>
                        </div>
                        <span class="venta-hora"><?= date('h:i A', strtotime($venta['fecha_venta'])) ?></span>
                        <span class="toggle-icon">▼</span>
                    </div>
                    
                    <div class="venta-info">
                        <div class="info-item">
                            <span class="info-label">Total</span>
                            <span class="info-value">$<?= number_format($venta['total'], 0, ',', '.') ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Método de Pago</span>
                            <span class="badge <?= strtolower($venta['tipo_pago']) ?>"><?= $venta['tipo_pago'] ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Monto Recibido</span>
                            <span class="info-value">$<?= number_format($venta['monto_recibido'], 0, ',', '.') ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Cambio</span>
                            <span class="info-value">$<?= number_format($venta['monto_recibido'] - $venta['total'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                    
                    <div class="detalles-venta" id="detalle-<?= $venta['id_venta'] ?>">
                        <?php
                        $query_productos = $conexion->query("
                            SELECT nombre_producto, cantidad, precio_unitario, subtotal
                            FROM detalle_ventas
                            WHERE id_venta = ".$venta['id_venta']."
                        ");
                        ?>
                        
                        <table class="productos-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unit.</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($producto = $query_productos->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $producto['nombre_producto'] ?></td>
                                    <td><?= $producto['cantidad'] ?></td>
                                    <td>$<?= number_format($producto['precio_unitario'], 0, ',', '.') ?></td>
                                    <td><strong>$<?= number_format($producto['subtotal'], 0, ',', '.') ?></strong></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <p>No hay ventas registradas el día de hoy</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div style="text-align: center;">
        <a href="index.php" class="btn-volver">← Volver al menú de reportes</a>
    </div>
</div>

<script>
function toggleDetalle(idVenta) {
    const detalleDiv = document.getElementById('detalle-' + idVenta);
    const ventaItem = detalleDiv.closest('.venta-item');
    
    detalleDiv.classList.toggle('active');
    ventaItem.classList.toggle('expanded');
}
</script>

</body>
</html>