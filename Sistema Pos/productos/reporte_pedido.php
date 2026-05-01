<?php
// Configurar zona horaria (ajusta según tu ubicación)
// Para Colombia: 'America/Bogota'
// Para México: 'America/Mexico_City'
// Para Argentina: 'America/Argentina/Buenos_Aires'
// Para España: 'Europe/Madrid'
date_default_timezone_set('America/Bogota'); // Cambia esto según tu zona horaria

include("../config/conexion.php");

// Verificar si existe el campo 'activo'
$checkField = $conexion->query("SHOW COLUMNS FROM productos LIKE 'activo'");
$tieneCampoActivo = $checkField->num_rows > 0;

// Obtener productos agotados (cantidad = 0)
$sql_agotados = "SELECT codigo, nombre, precio, cantidad 
                 FROM productos 
                 WHERE cantidad = 0 AND (activo = 1 OR activo IS NULL)
                 ORDER BY nombre ASC";
$productos_agotados = $conexion->query($sql_agotados);

// Obtener productos por agotarse (cantidad > 0 y <= 10)
$sql_por_agotarse = "SELECT codigo, nombre, precio, cantidad 
                     FROM productos 
                     WHERE cantidad > 0 AND cantidad <= 10 AND (activo = 1 OR activo IS NULL)
                     ORDER BY cantidad ASC, nombre ASC";
$productos_por_agotarse = $conexion->query($sql_por_agotarse);

// Contar totales
$total_agotados = $productos_agotados->num_rows;
$total_por_agotarse = $productos_por_agotarse->num_rows;
$total_general = $total_agotados + $total_por_agotarse;

// Fecha actual para el reporte (ahora con la zona horaria correcta)
$fecha_reporte = date('d/m/Y H:i:s');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Pedido - Sistema POS</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #2c3e50;
            padding: 30px 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px 40px;
            text-align: center;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .header-info {
            display: flex;
            justify-content: space-around;
            margin-top: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.2);
            padding: 15px 25px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }

        .info-item strong {
            display: block;
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .info-item span {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .actions {
            padding: 25px 40px;
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-pdf {
            background: #e74c3c;
            color: white;
        }

        .btn-pdf:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }

        .btn-close {
            background: #95a5a6;
            color: white;
        }

        .btn-close:hover {
            background: #7f8c8d;
        }

        .content {
            padding: 40px;
        }

        .section {
            margin-bottom: 50px;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #ecf0f1;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title.agotados {
            color: #e74c3c;
            border-bottom-color: #e74c3c;
        }

        .section-title.por-agotarse {
            color: #f39c12;
            border-bottom-color: #f39c12;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        thead {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
        }

        th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr {
            border-bottom: 1px solid #ecf0f1;
            transition: background 0.2s ease;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        td {
            padding: 18px 15px;
            font-size: 0.95rem;
        }

        .codigo {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #34495e;
        }

        .nombre {
            font-weight: 600;
            color: #2c3e50;
        }

        .precio {
            color: #27ae60;
            font-weight: 600;
        }

        .cantidad {
            font-weight: 700;
            font-size: 1.1rem;
            padding: 8px 16px;
            border-radius: 6px;
            display: inline-block;
            min-width: 60px;
            text-align: center;
        }

        .cantidad.agotado {
            background: #fee;
            color: #e74c3c;
            border: 2px solid #e74c3c;
        }

        .cantidad.por-agotarse {
            background: #fff4e6;
            color: #f39c12;
            border: 2px solid #f39c12;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #95a5a6;
        }

        .empty-state span {
            font-size: 64px;
            display: block;
            margin-bottom: 20px;
        }

        .empty-state p {
            font-size: 1.1rem;
        }

        @media print {
            .actions {
                display: none;
            }

            body {
                background: white;
                padding: 0;
            }

            .container {
                box-shadow: none;
            }
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }

            .header-info {
                flex-direction: column;
            }

            .content {
                padding: 20px;
            }

            table {
                font-size: 0.85rem;
            }

            th, td {
                padding: 12px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container" id="reporte-container">
        <div class="header">
            <h1>Reporte de Pedido</h1>
            <p>Productos que requieren reposición</p>
            <div class="header-info">
                <div class="info-item">
                    <strong><?= $total_general ?></strong>
                    <span>Total Productos</span>
                </div>
                <div class="info-item">
                    <strong><?= $total_agotados ?></strong>
                    <span>Agotados</span>
                </div>
                <div class="info-item">
                    <strong><?= $total_por_agotarse ?></strong>
                    <span>Por Agotarse</span>
                </div>
                <div class="info-item">
                    <strong><?= $fecha_reporte ?></strong>
                    <span>Fecha del Reporte</span>
                </div>
            </div>
        </div>

        <div class="actions">
            <button onclick="generarPDF()" class="btn btn-pdf">
                Generar PDF
            </button>
            <button onclick="window.close()" class="btn btn-close">
                Cerrar
            </button>
        </div>

        <div class="content">
            <!-- Productos Agotados -->
            <div class="section">
                <h2 class="section-title agotados">
                    Productos Agotados (<?= $total_agotados ?>)
                </h2>
                <div class="table-container">
                    <?php if ($total_agotados > 0): ?>
                        <table id="tabla-agotados">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre del Producto</th>
                                    <th>Precio Unitario</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($producto = $productos_agotados->fetch_assoc()): ?>
                                    <tr>
                                        <td class="codigo"><?= htmlspecialchars($producto['codigo']) ?></td>
                                        <td class="nombre"><?= htmlspecialchars($producto['nombre']) ?></td>
                                        <td class="precio">$<?= number_format($producto['precio'], 0, ',', '.') ?></td>
                                        <td>
                                            <span class="cantidad agotado"><?= $producto['cantidad'] ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <span></span>
                            <p>No hay productos agotados</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Productos por Agotarse -->
            <div class="section">
                <h2 class="section-title por-agotarse">
                    Productos por Agotarse (<?= $total_por_agotarse ?>)
                </h2>
                <div class="table-container">
                    <?php if ($total_por_agotarse > 0): ?>
                        <table id="tabla-por-agotarse">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre del Producto</th>
                                    <th>Precio Unitario</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($producto = $productos_por_agotarse->fetch_assoc()): ?>
                                    <tr>
                                        <td class="codigo"><?= htmlspecialchars($producto['codigo']) ?></td>
                                        <td class="nombre"><?= htmlspecialchars($producto['nombre']) ?></td>
                                        <td class="precio">$<?= number_format($producto['precio'], 0, ',', '.') ?></td>
                                        <td>
                                            <span class="cantidad por-agotarse"><?= $producto['cantidad'] ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <span></span>
                            <p>No hay productos por agotarse</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function generarPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');
            
            // Configuración
            const pageWidth = doc.internal.pageSize.getWidth();
            const margin = 15;
            const maxWidth = pageWidth - (margin * 2);
            let yPos = margin;

            // Función para agregar nueva página si es necesario
            function checkNewPage(neededSpace) {
                if (yPos + neededSpace > doc.internal.pageSize.getHeight() - margin) {
                    doc.addPage();
                    yPos = margin;
                    return true;
                }
                return false;
            }

            // Encabezado
            doc.setFontSize(20);
            doc.setTextColor(102, 126, 234);
            doc.setFont(undefined, 'bold');
            doc.text('REPORTE DE PEDIDO', pageWidth / 2, yPos, { align: 'center' });
            yPos += 10;

            doc.setFontSize(12);
            doc.setTextColor(100, 100, 100);
            doc.setFont(undefined, 'normal');
            doc.text('Productos que requieren reposición', pageWidth / 2, yPos, { align: 'center' });
            yPos += 8;

            doc.setFontSize(10);
            doc.text('Fecha: <?= $fecha_reporte ?>', pageWidth / 2, yPos, { align: 'center' });
            yPos += 15;

            // Información resumida
            doc.setFontSize(11);
            doc.setTextColor(60, 60, 60);
            doc.text(`Total Productos: ${<?= $total_general ?>}`, margin, yPos);
            yPos += 6;
            doc.text(`Agotados: ${<?= $total_agotados ?>}`, margin, yPos);
            yPos += 6;
            doc.text(`Por Agotarse: ${<?= $total_por_agotarse ?>}`, margin, yPos);
            yPos += 10;

            // Productos Agotados
            <?php if ($total_agotados > 0): ?>
                checkNewPage(20);
                doc.setFontSize(14);
                doc.setTextColor(231, 76, 60);
                doc.setFont(undefined, 'bold');
                doc.text('PRODUCTOS AGOTADOS', margin, yPos);
                yPos += 8;

                // Preparar datos para la tabla
                const datosAgotados = [
                    <?php 
                    $productos_agotados->data_seek(0);
                    $first = true;
                    while ($producto = $productos_agotados->fetch_assoc()): 
                        if (!$first) echo ',';
                        $first = false;
                    ?>
                    [
                        '<?= addslashes($producto['codigo']) ?>',
                        '<?= addslashes($producto['nombre']) ?>',
                        '$<?= number_format($producto['precio'], 0, ',', '.') ?>',
                        '<?= $producto['cantidad'] ?>'
                    ]
                    <?php endwhile; ?>
                ];

                doc.autoTable({
                    startY: yPos,
                    head: [['Código', 'Nombre', 'Precio', 'Cantidad']],
                    body: datosAgotados,
                    theme: 'striped',
                    headStyles: {
                        fillColor: [231, 76, 60],
                        textColor: [255, 255, 255],
                        fontStyle: 'bold'
                    },
                    styles: {
                        fontSize: 9,
                        cellPadding: 3
                    },
                    columnStyles: {
                        0: { cellWidth: 35 },
                        1: { cellWidth: 80 },
                        2: { cellWidth: 30, halign: 'right' },
                        3: { cellWidth: 25, halign: 'center', textColor: [231, 76, 60], fontStyle: 'bold' }
                    }
                });

                yPos = doc.lastAutoTable.finalY + 10;
            <?php endif; ?>

            // Productos por Agotarse
            <?php if ($total_por_agotarse > 0): ?>
                checkNewPage(20);
                doc.setFontSize(14);
                doc.setTextColor(243, 156, 18);
                doc.setFont(undefined, 'bold');
                doc.text('PRODUCTOS POR AGOTARSE', margin, yPos);
                yPos += 8;

                // Preparar datos para la tabla
                const datosPorAgotarse = [
                    <?php 
                    $productos_por_agotarse->data_seek(0);
                    $first = true;
                    while ($producto = $productos_por_agotarse->fetch_assoc()): 
                        if (!$first) echo ',';
                        $first = false;
                    ?>
                    [
                        '<?= addslashes($producto['codigo']) ?>',
                        '<?= addslashes($producto['nombre']) ?>',
                        '$<?= number_format($producto['precio'], 0, ',', '.') ?>',
                        '<?= $producto['cantidad'] ?>'
                    ]
                    <?php endwhile; ?>
                ];

                doc.autoTable({
                    startY: yPos,
                    head: [['Código', 'Nombre', 'Precio', 'Cantidad']],
                    body: datosPorAgotarse,
                    theme: 'striped',
                    headStyles: {
                        fillColor: [243, 156, 18],
                        textColor: [255, 255, 255],
                        fontStyle: 'bold'
                    },
                    styles: {
                        fontSize: 9,
                        cellPadding: 3
                    },
                    columnStyles: {
                        0: { cellWidth: 35 },
                        1: { cellWidth: 80 },
                        2: { cellWidth: 30, halign: 'right' },
                        3: { cellWidth: 25, halign: 'center', textColor: [243, 156, 18], fontStyle: 'bold' }
                    }
                });
            <?php endif; ?>

            // Pie de página
            const totalPages = doc.internal.pages.length - 1;
            for (let i = 1; i <= totalPages; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.setTextColor(150, 150, 150);
                doc.text(
                    `Página ${i} de ${totalPages}`,
                    pageWidth / 2,
                    doc.internal.pageSize.getHeight() - 10,
                    { align: 'center' }
                );
            }

            // Descargar PDF
            const fecha = new Date().toISOString().split('T')[0];
            doc.save(`Reporte_Pedido_${fecha}.pdf`);
        }
    </script>
</body>
</html>