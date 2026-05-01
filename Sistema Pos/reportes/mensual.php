<?php
include("../config/conexion.php");
date_default_timezone_set('America/Bogota');
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'spanish');

// Función para obtener mes en español
function mesEspanol($fecha) {
    $meses = array('January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo', 
                   'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio', 
                   'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre', 
                   'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre');
    
    $mes_nombre = $meses[date('F', strtotime($fecha))];
    $anio = date('Y', strtotime($fecha));
    
    return "$mes_nombre de $anio";
}

// Función para obtener día en español
function diaEspanol($fecha) {
    $dias = array('Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mié', 
                  'Thu' => 'Jue', 'Fri' => 'Vie', 'Sat' => 'Sáb', 'Sun' => 'Dom');
    
    $dia_corto = $dias[date('D', strtotime($fecha))];
    $dia_numero = date('d/m/Y', strtotime($fecha));
    
    return "$dia_corto, $dia_numero";
}

// Obtener resumen del mes
$query_resumen = $conexion->query("
    SELECT 
        SUM(total) as total_mes,
        COUNT(id_venta) as num_ventas_mes,
        AVG(total) as promedio_venta,
        SUM(CASE WHEN tipo_pago = 'EFECTIVO' THEN total ELSE 0 END) as total_efectivo,
        SUM(CASE WHEN tipo_pago = 'TRANSFERENCIA' THEN total ELSE 0 END) as total_transferencia
    FROM ventas
    WHERE MONTH(fecha_venta) = MONTH(CURRENT_DATE()) 
      AND YEAR(fecha_venta) = YEAR(CURRENT_DATE())
");
$resumen = $query_resumen->fetch_assoc();

// Obtener ventas agrupadas por día
$query_dias = $conexion->query("
    SELECT 
        DATE(fecha_venta) as fecha, 
        SUM(total) as total_dia,
        COUNT(id_venta) as num_ventas,
        SUM(CASE WHEN tipo_pago = 'EFECTIVO' THEN total ELSE 0 END) as efectivo,
        SUM(CASE WHEN tipo_pago = 'TRANSFERENCIA' THEN total ELSE 0 END) as transferencia
    FROM ventas
    WHERE MONTH(fecha_venta) = MONTH(CURRENT_DATE()) 
      AND YEAR(fecha_venta) = YEAR(CURRENT_DATE())
    GROUP BY DATE(fecha_venta)
    ORDER BY DATE(fecha_venta) DESC
");

// Obtener productos más vendidos del mes
$query_productos = $conexion->query("
    SELECT 
        dv.nombre_producto,
        SUM(dv.cantidad) as total_cantidad,
        SUM(dv.subtotal) as total_vendido
    FROM detalle_ventas dv
    INNER JOIN ventas v ON dv.id_venta = v.id_venta
    WHERE MONTH(v.fecha_venta) = MONTH(CURRENT_DATE()) 
      AND YEAR(v.fecha_venta) = YEAR(CURRENT_DATE())
    GROUP BY dv.nombre_producto
    ORDER BY total_cantidad DESC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Mensual</title>
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
        
        .header .mes {
            font-size: 0.95rem;
            opacity: 0.9;
            text-transform: capitalize;
        }
        
        .resumen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .resumen-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            border-left: 4px solid #3498db;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .resumen-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        .resumen-card h3 {
            font-size: 0.7rem;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .resumen-card .value {
            font-size: 1.6rem;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .resumen-card.total { border-left-color: #e74c3c; }
        .resumen-card.total .value { color: #e74c3c; }
        
        .resumen-card.ventas { border-left-color: #f39c12; }
        .resumen-card.ventas .value { color: #f39c12; }
        
        .resumen-card.promedio { border-left-color: #9b59b6; }
        .resumen-card.promedio .value { color: #9b59b6; }
        
        .resumen-card.efectivo { border-left-color: #27ae60; }
        .resumen-card.efectivo .value { color: #27ae60; }
        
        .resumen-card.transferencia { border-left-color: #3498db; }
        .resumen-card.transferencia .value { color: #3498db; }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        @media (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .section h2 {
            color: #2c3e50;
            font-size: 1.25rem;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #3498db;
            font-weight: 600;
        }
        
        .dia-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .dia-card:hover {
            border-color: #3498db;
            box-shadow: 0 3px 12px rgba(52, 152, 219, 0.15);
        }
        
        .dia-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .dia-fecha {
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .dia-total {
            font-size: 1.125rem;
            font-weight: 700;
            color: #e74c3c;
        }
        
        .dia-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            font-size: 0.875rem;
        }
        
        .dia-info-item {
            display: flex;
            flex-direction: column;
        }
        
        .dia-info-label {
            color: #95a5a6;
            font-size: 0.7rem;
            text-transform: uppercase;
            margin-bottom: 3px;
            font-weight: 600;
        }
        
        .dia-info-value {
            color: #2c3e50;
            font-weight: 600;
        }
        
        .producto-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s ease;
        }
        
        .producto-item:hover {
            background: #f8f9fa;
        }
        
        .producto-item:last-child {
            border-bottom: none;
        }
        
        .producto-nombre {
            flex: 1;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.95rem;
        }
        
        .producto-stats {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .producto-cantidad {
            background: #3498db;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .producto-total {
            color: #27ae60;
            font-weight: 700;
            font-size: 0.95rem;
        }
        
        .toggle-icon {
            margin-left: 10px;
            color: #3498db;
            font-size: 1rem;
            transition: transform 0.3s ease;
        }
        
        .dia-card.expanded .toggle-icon {
            transform: rotate(180deg);
        }
        
        .dia-detalles {
            display: none;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed #e0e0e0;
        }
        
        .dia-detalles.active {
            display: block;
        }
        
        .dia-detalles table {
            width: 100%;
            font-size: 0.8rem;
        }
        
        .dia-detalles th {
            text-align: left;
            padding: 8px 0;
            color: #7f8c8d;
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
        }
        
        .dia-detalles td {
            padding: 8px 0;
            color: #2c3e50;
        }
        
        .btn-factura-small {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            background: #27ae60;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.8rem;
            transition: all 0.2s ease;
        }
        
        .btn-factura-small:hover {
            background: #229954;
            transform: scale(1.1);
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
            padding: 40px 20px;
            color: #95a5a6;
        }
        
        .empty-state p {
            font-size: 0.95rem;
            margin-top: 8px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Reporte Mensual</h1>
        <div class="mes"><?= mesEspanol(date('Y-m-d')) ?></div>
    </div>
    
    <div class="resumen-grid">
        <div class="resumen-card total">
            <h3>Total del Mes</h3>
            <div class="value">$<?= number_format($resumen['total_mes'] ?: 0, 0, ',', '.') ?></div>
        </div>
        
        <div class="resumen-card ventas">
            <h3>Ventas Realizadas</h3>
            <div class="value"><?= $resumen['num_ventas_mes'] ?: 0 ?></div>
        </div>
        
        <div class="resumen-card promedio">
            <h3>Promedio por Venta</h3>
            <div class="value">$<?= number_format($resumen['promedio_venta'] ?: 0, 0, ',', '.') ?></div>
        </div>
        
        <div class="resumen-card efectivo">
            <h3>Efectivo</h3>
            <div class="value">$<?= number_format($resumen['total_efectivo'] ?: 0, 0, ',', '.') ?></div>
        </div>
        
        <div class="resumen-card transferencia">
            <h3>Transferencia</h3>
            <div class="value">$<?= number_format($resumen['total_transferencia'] ?: 0, 0, ',', '.') ?></div>
        </div>
    </div>
    
    <div class="content-grid">
        <div class="section">
            <h2>Ventas por Día</h2>
            
            <?php if($query_dias->num_rows > 0): ?>
                <?php while($dia = $query_dias->fetch_assoc()): ?>
                    <div class="dia-card" onclick="toggleDia('<?= $dia['fecha'] ?>')">
                        <div class="dia-header">
                            <span class="dia-fecha"><?= diaEspanol($dia['fecha']) ?></span>
                            <span class="dia-total">$<?= number_format($dia['total_dia'], 0, ',', '.') ?></span>
                            <span class="toggle-icon">▼</span>
                        </div>
                        
                        <div class="dia-info">
                            <div class="dia-info-item">
                                <span class="dia-info-label">Ventas</span>
                                <span class="dia-info-value"><?= $dia['num_ventas'] ?></span>
                            </div>
                            <div class="dia-info-item">
                                <span class="dia-info-label">Efectivo</span>
                                <span class="dia-info-value">$<?= number_format($dia['efectivo'], 0, ',', '.') ?></span>
                            </div>
                            <div class="dia-info-item">
                                <span class="dia-info-label">Transferencia</span>
                                <span class="dia-info-value">$<?= number_format($dia['transferencia'], 0, ',', '.') ?></span>
                            </div>
                        </div>
                        
                        <div class="dia-detalles" id="dia-<?= $dia['fecha'] ?>">
                            <?php
                            $fecha_buscar = $dia['fecha'];
                            $query_ventas_dia = $conexion->query("
                                SELECT id_venta, total, tipo_pago, DATE_FORMAT(fecha_venta, '%H:%i') as hora
                                FROM ventas
                                WHERE DATE(fecha_venta) = '$fecha_buscar'
                                ORDER BY fecha_venta DESC
                            ");
                            ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Venta</th>
                                        <th>Hora</th>
                                        <th>Método</th>
                                        <th>Total</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($venta = $query_ventas_dia->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?= $venta['id_venta'] ?></td>
                                        <td><?= $venta['hora'] ?></td>
                                        <td><?= $venta['tipo_pago'] ?></td>
                                        <td><strong>$<?= number_format($venta['total'], 0, ',', '.') ?></strong></td>
                                        <td>
                                            <a href="../ventas/factura.php?id=<?= $venta['id_venta'] ?>&cliente=Consumidor Final" 
                                               target="_blank"
                                               class="btn-factura-small"
                                               title="Ver Factura">
                                                🖨️
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                <p>No hay ventas registradas este mes</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>Top Productos Vendidos</h2>
            
            <?php if($query_productos->num_rows > 0): ?>
                <?php while($producto = $query_productos->fetch_assoc()): ?>
                    <div class="producto-item">
                        <div class="producto-nombre"><?= $producto['nombre_producto'] ?></div>
                        <div class="producto-stats">
                            <span class="producto-cantidad"><?= $producto['total_cantidad'] ?> und</span>
                            <span class="producto-total">$<?= number_format($producto['total_vendido'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                <p>Sin productos vendidos</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div style="text-align: center;">
        <a href="index.php" class="btn-volver">← Volver al menú de reportes</a>
    </div>
</div>

<script>
function toggleDia(fecha) {
    const detalleDiv = document.getElementById('dia-' + fecha);
    const diaCard = detalleDiv.closest('.dia-card');
    
    detalleDiv.classList.toggle('active');
    diaCard.classList.toggle('expanded');
}
</script>

</body>
</html>