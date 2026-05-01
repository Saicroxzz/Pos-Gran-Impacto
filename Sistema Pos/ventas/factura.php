<?php
include("../config/conexion.php");

// Obtener y validar parámetros
$id_venta = isset($_GET['id']) ? intval($_GET['id']) : 0;
$cliente = isset($_GET['cliente']) ? $_GET['cliente'] : 'Consumidor Final';

// Validar que existe la venta
if ($id_venta <= 0) {
    die("ID de venta inválido");
}

// Obtener datos de la venta usando prepared statement
$stmt = $conexion->prepare("SELECT * FROM ventas WHERE id_venta = ?");
$stmt->bind_param("i", $id_venta);
$stmt->execute();
$ventaQuery = $stmt->get_result();
$venta = $ventaQuery->fetch_assoc();

if (!$venta) {
    die("Venta no encontrada");
}

// Obtener detalle de la venta
$stmt = $conexion->prepare("SELECT * FROM detalle_ventas WHERE id_venta = ?");
$stmt->bind_param("i", $id_venta);
$stmt->execute();
$detalle = $stmt->get_result();

/* ==========================
   CÁLCULO DE CAMBIO (SI APLICA)
   ========================== */
$cambio = 0;
$esEfectivo = (strtoupper($venta['tipo_pago']) === 'EFECTIVO');

if ($esEfectivo) {
    $cambio = max(0, $venta['monto_recibido'] - $venta['total']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura Pedido <?= $id_venta ?></title>

    <style>
        @media print {
            @page {
                margin: 0.5cm;
                size: 80mm auto;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            width: 80mm;
            margin: 0 auto;
            color: #000;
            line-height: 1.3;
            font-size: 14px;
            padding: 2mm 3mm;
        }

        h1 {
            font-size: 20px;
            margin: 0 0 8px 0;
            text-align: center;
            text-transform: uppercase;
            font-weight: bold;
        }

        p {
            margin: 3px 0;
            font-size: 13px;
        }

        hr {
            border: none;
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 5px;
        }

        th {
            border-bottom: 1px solid #000;
            padding: 4px 0;
            text-align: left;
            font-size: 12px;
            font-weight: bold;
        }

        td {
            padding: 5px 0;
            vertical-align: top;
            word-wrap: break-word;
            font-size: 12px;
        }

        .col-cant { width: 10%; text-align: center; }
        .col-prod { width: 42%; }
        .col-unit { width: 24%; text-align: right; }
        .col-total { width: 24%; text-align: right; }

        .total-final {
            font-weight: bold;
            font-size: 18px;
            text-align: right;
            margin-top: 12px;
            padding: 8px 0;
            border-top: 2px solid #000;
        }

        .metodo-pago {
            background: #f0f0f0;
            padding: 8px;
            margin: 10px 0;
            border: 1px dashed #333;
        }

        .metodo-pago p {
            margin: 4px 0;
        }

        .right { text-align: right; }
        .center { text-align: center; }
        .bold { font-weight: bold; }

        .no-print {
            background: #f8f9fa;
            padding: 15px;
            text-align: center;
            border-bottom: 2px solid #333;
            margin-bottom: 10px;
        }

        .btn {
            background: #222;
            color: #fff;
            padding: 10px 25px;
            text-decoration: none;
            font-weight: bold;
            border-radius: 6px;
            display: inline-block;
            margin: 5px;
        }

        .btn:hover {
            background: #444;
        }
    </style>
</head>

<body>

<div class="no-print">
    <a href="nueva_venta.php" class="btn">← NUEVA VENTA</a>
    <a href="#" onclick="window.print(); return false;" class="btn">🖨️ IMPRIMIR</a>
</div>

<h1>ALMACÉN EL GRAN IMPACTO</h1>

<p><strong>N° Pedido:</strong> <?= str_pad($id_venta, 6, '0', STR_PAD_LEFT) ?></p>
<p><strong>Fecha:</strong> <?= date("d/m/Y", strtotime($venta['fecha_venta'])) ?></p>
<p><strong>Hora:</strong> <?= date("h:i A", strtotime($venta['fecha_venta'])) ?></p>
<p><strong>Cliente:</strong> <?= htmlspecialchars($cliente) ?></p>

<hr>

<table>
    <thead>
        <tr>
            <th class="col-cant">Cant</th>
            <th class="col-prod">Producto</th>
            <th class="col-unit">V.Unit</th>
            <th class="col-total">Total</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $detalle->fetch_assoc()) { ?>
            <tr>
                <td class="col-cant"><?= $row['cantidad'] ?></td>
                <td class="col-prod"><?= htmlspecialchars($row['nombre_producto']) ?></td>
                <td class="col-unit">$<?= number_format($row['precio_unitario'], 0, ',', '.') ?></td>
                <td class="col-total">$<?= number_format($row['subtotal'], 0, ',', '.') ?></td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<p class="total-final">
    TOTAL: $<?= number_format($venta['total'], 0, ',', '.') ?>
</p>

<div class="metodo-pago">
    <p class="bold">MÉTODO DE PAGO: <?= strtoupper(htmlspecialchars($venta['tipo_pago'])) ?></p>
    
    <?php if ($esEfectivo) { ?>
        <hr style="margin: 5px 0;">
        <p><strong>Efectivo Recibido:</strong> <span class="right">$<?= number_format($venta['monto_recibido'], 0, ',', '.') ?></span></p>
        <p><strong>Cambio:</strong> <span class="right">$<?= number_format($cambio, 0, ',', '.') ?></span></p>
    <?php } else { ?>
        <p style="margin-top: 5px;">Pago realizado por transferencia</p>
    <?php } ?>
</div>

<hr>

<p class="center" style="font-size: 11px; margin-top: 10px;">
    *** GRACIAS POR SU PREFERENCIA ***<br>
    ¡Vuelva Pronto!
</p>

<p class="center" style="font-size: 10px; color: #666; margin-top: 8px;">
    Sistema POS - <?= date("Y") ?>
</p>

<script>
    // Imprimir automáticamente al cargar
    window.onload = function () {
        // Esperar 500ms para que cargue todo
        setTimeout(function() {
            window.print();
        }, 500);
        
        // Redirigir después de imprimir o cancelar
        window.onafterprint = function () {
            // Opcional: descomentar si quieres redirección automática
            // window.location.href = "nueva_venta.php";
        };
    };
</script>

</body>
</html>