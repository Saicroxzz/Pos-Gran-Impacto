<?php
include("../config/conexion.php");

// Verificar si existe el campo 'activo' y agregarlo si no existe
$checkField = $conexion->query("SHOW COLUMNS FROM productos LIKE 'activo'");
if ($checkField->num_rows == 0) {
    try {
        $conexion->query("ALTER TABLE productos ADD COLUMN activo TINYINT(1) DEFAULT 1 AFTER cantidad");
        $conexion->query("UPDATE productos SET activo = 1 WHERE activo IS NULL");
    } catch (Exception $e) {
        // Ignorar si no se puede agregar
    }
}

// Obtener estadísticas (solo productos activos)
$total_productos = $conexion->query("SELECT COUNT(*) as total FROM productos WHERE activo = 1 OR activo IS NULL")->fetch_assoc()['total'];
$stock_disponible = $conexion->query("SELECT SUM(cantidad) as total FROM productos WHERE cantidad > 0 AND (activo = 1 OR activo IS NULL)")->fetch_assoc()['total'];
$agotados = $conexion->query("SELECT COUNT(*) as total FROM productos WHERE cantidad = 0 AND (activo = 1 OR activo IS NULL)")->fetch_assoc()['total'];
$por_agotarse = $conexion->query("SELECT COUNT(*) as total FROM productos WHERE cantidad > 0 AND cantidad <= 10 AND (activo = 1 OR activo IS NULL)")->fetch_assoc()['total'];

// Obtener productos con filtro (solo activos)
$filtro = $_GET['filtro'] ?? 'todos';
$busqueda = $_GET['buscar'] ?? '';

$sql = "SELECT * FROM productos WHERE (activo = 1 OR activo IS NULL)";

if ($busqueda) {
    $busqueda_limpia = $conexion->real_escape_string($busqueda);
    $sql .= " AND (nombre LIKE '%$busqueda_limpia%' OR codigo LIKE '%$busqueda_limpia%')";
}

switch ($filtro) {
    case 'disponibles':
        $sql .= " AND cantidad > 10";
        break;
    case 'por_agotarse':
        $sql .= " AND cantidad > 0 AND cantidad <= 10";
        break;
    case 'agotados':
        $sql .= " AND cantidad = 0";
        break;
}

$sql .= " ORDER BY cantidad ASC, nombre ASC";
$productos = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventario</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #e8ecef;
            color: #2c3e50;
            height: 100vh;
            overflow: hidden;
        }
        
        /* HEADER */
        .header-bar {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-bar h1 {
            font-size: 20px;
            font-weight: 600;
        }
        
        .header-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-header {
            padding: 8px 16px;
            background: rgba(255,255,255,0.15);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .btn-header:hover {
            background: rgba(255,255,255,0.25);
        }
        
        /* LAYOUT PRINCIPAL - DOS COLUMNAS */
        .dashboard-layout {
            display: grid;
            grid-template-columns: 75% 25%;
            gap: 20px;
            padding: 20px;
            height: calc(100vh - 60px);
            max-width: 1800px;
            margin: 0 auto;
        }
        
        /* COLUMNA IZQUIERDA */
        .main-column {
            display: flex;
            flex-direction: column;
            gap: 15px;
            overflow: hidden;
        }
        
        /* CONTROLES DE BÚSQUEDA Y FILTROS */
        .controls-section {
            background: white;
            padding: 16px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .search-input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #cbd5e0;
            border-radius: 6px;
            font-size: 16px;
            margin-bottom: 12px;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .filter-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 10px 18px;
            background: #ecf0f1;
            border: 2px solid transparent;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            color: #7f8c8d;
        }
        
        .filter-tab:hover {
            background: #d5dbdb;
        }
        
        .filter-tab.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        /* TABLA DE PRODUCTOS */
        .products-table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            flex: 1;
        }
        
        .table-header {
            background: #f8f9fa;
            padding: 14px 18px;
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
            color: #2c3e50;
            font-size: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .products-count {
            color: #7f8c8d;
            font-size: 14px;
            font-weight: 500;
        }
        
        .table-wrapper {
            flex: 1;
            overflow-y: auto;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .products-table thead {
            background: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .products-table th {
            text-align: left;
            padding: 12px 14px;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .products-table td {
            padding: 14px;
            border-bottom: 1px solid #f0f0f0;
            color: #2c3e50;
            font-size: 15px;
        }
        
        .products-table tbody tr {
            transition: background 0.2s ease;
        }
        
        .products-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        /* INDICADORES DE STOCK SUTILES */
        .stock-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .stock-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        
        .stock-dot.disponible {
            background: #27ae60;
        }
        
        .stock-dot.por-agotarse {
            background: #f39c12;
        }
        
        .stock-dot.agotado {
            background: #e74c3c;
        }
        
        .stock-number {
            font-weight: 600;
        }
        
        .stock-number.disponible {
            color: #2c3e50;
        }
        
        .stock-number.por-agotarse {
            color: #f39c12;
        }
        
        .stock-number.agotado {
            color: #e74c3c;
        }
        
        /* Fila con borde sutil para stock bajo/agotado */
        .products-table tbody tr.row-por-agotarse {
            border-left: 2px solid #f39c12;
        }
        
        .products-table tbody tr.row-agotado {
            border-left: 2px solid #e74c3c;
            opacity: 0.7;
        }
        
        .product-name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .product-code {
            color: #7f8c8d;
            font-size: 13px;
        }
        
        .product-price {
            font-weight: 600;
            color: #2c3e50;
        }
        
        /* BOTONES DE ACCIÓN MINIMALISTAS */
        .action-buttons {
            display: flex;
            gap: 6px;
        }
        
        .btn-action {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-action svg {
            width: 15px;
            height: 15px;
        }
        
        .btn-edit {
            background: #3498db;
            color: white;
        }
        
        .btn-edit:hover {
            background: #2980b9;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c0392b;
        }
        
        /* COLUMNA DERECHA - PANEL DE CONTROL */
        .sidebar-column {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        /* ESTADÍSTICAS VERTICALES */
        .stats-vertical {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .stat-micro-card {
            background: white;
            padding: 18px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid;
            transition: all 0.2s ease;
        }
        
        .stat-micro-card:hover {
            transform: translateX(3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        .stat-micro-card.success {
            border-left-color: #27ae60;
        }
        
        .stat-micro-card.warning {
            border-left-color: #f39c12;
        }
        
        .stat-micro-card.danger {
            border-left-color: #e74c3c;
        }
        
        .stat-micro-label {
            font-size: 12px;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .stat-micro-value {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        /* BOTONES DE ACCIÓN PRINCIPAL */
        .primary-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .btn-primary-action {
            padding: 16px 20px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .btn-new-product {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }
        
        .btn-new-product:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
        }
        
        .btn-report {
            background: #95a5a6;
            color: white;
        }
        
        .btn-report:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }

        .btn-backup {
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.3);
        }

        .btn-backup:hover {
            background: #1a252f;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(44, 62, 80, 0.4);
        }
        
        /* ESTADO VACÍO */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #95a5a6;
        }
        
        .empty-state p {
            font-size: 14px;
            margin-top: 10px;
        }
        
        /* MODALES */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(3px);
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal {
            background: white;
            border-radius: 12px;
            max-width: 550px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalFadeIn 0.3s ease;
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            padding: 20px 24px;
            border-bottom: 2px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            font-size: 20px;
            color: #2c3e50;
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #7f8c8d;
            cursor: pointer;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .modal-close:hover {
            background: #ecf0f1;
            color: #2c3e50;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 13px;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .form-group input:disabled {
            background: #f5f6fa;
            cursor: not-allowed;
        }
        
        .modal-footer {
            padding: 16px 24px;
            border-top: 2px solid #ecf0f1;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-cancel {
            background: #95a5a6;
            color: white;
        }
        
        .btn-cancel:hover {
            background: #7f8c8d;
        }
        
        .btn-submit {
            background: #27ae60;
            color: white;
        }
        
        .btn-submit:hover {
            background: #229954;
        }
        
        /* CONFIRMACIÓN DE ELIMINACIÓN */
        .confirm-modal {
            max-width: 450px;
            text-align: center;
        }
        
        .confirm-icon {
            font-size: 64px;
            margin-bottom: 20px;
            display: block;
        }
        
        .confirm-modal h2 {
            color: #e74c3c;
            margin-bottom: 15px;
        }
        
        .confirm-modal p {
            color: #7f8c8d;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .confirm-product-name {
            font-weight: 700;
            color: #2c3e50;
            font-size: 16px;
            margin: 15px 0;
        }
        
        .btn-delete-confirm {
            background: #e74c3c;
            color: white;
        }
        
        .btn-delete-confirm:hover {
            background: #c0392b;
        }
        
        /* NOTIFICACIONES */
        .notificacion {
            position: fixed;
            top: 70px;
            right: 20px;
            padding: 14px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            z-index: 10000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }
        
        .notificacion.show {
            transform: translateX(0);
        }
        
        .notif-success {
            background: #27ae60;
            color: white;
        }
        
        .notif-error {
            background: #e74c3c;
            color: white;
        }
        
        .notif-info {
            background: #3498db;
            color: white;
        }
        
        /* RESPONSIVE */
        @media (max-width: 1200px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr;
            }
            
            .sidebar-column {
                order: -1;
            }
            
            .stats-vertical {
                flex-direction: row;
            }
            
            .primary-actions {
                flex-direction: row;
            }
        }
        
        @media (max-width: 768px) {
            body {
                overflow: auto;
            }
            
            .dashboard-layout {
                height: auto;
                padding: 15px;
                gap: 15px;
            }
            
            .header-bar {
                padding: 12px 15px;
            }
            
            .header-bar h1 {
                font-size: 16px;
            }
            
            .btn-header {
                font-size: 13px;
                padding: 7px 12px;
            }
            
            .stats-vertical {
                flex-direction: column;
                gap: 10px;
            }
            
            .stat-micro-card {
                padding: 14px;
            }
            
            .stat-micro-value {
                font-size: 28px;
            }
            
            .primary-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .btn-primary-action {
                padding: 14px 16px;
                font-size: 14px;
            }
            
            .controls-section {
                padding: 14px;
            }
            
            .search-input {
                font-size: 16px;
                padding: 12px 14px;
            }
            
            .filter-tabs {
                gap: 6px;
            }
            
            .filter-tab {
                padding: 9px 14px;
                font-size: 14px;
            }
            
            .table-header {
                padding: 12px 14px;
                font-size: 15px;
            }
            
            .products-table {
                font-size: 14px;
            }
            
            .products-table th {
                padding: 10px;
                font-size: 11px;
            }
            
            .products-table td {
                padding: 12px 10px;
                font-size: 14px;
            }
            
            .product-name {
                font-size: 14px;
            }
            
            .product-code {
                font-size: 12px;
            }
            
            .btn-action {
                padding: 8px 10px;
                font-size: 12px;
            }
            
            .btn-action svg {
                width: 14px;
                height: 14px;
            }
            
            /* Ocultar columna de código en móvil para ahorrar espacio */
            .products-table th:nth-child(2),
            .products-table td:nth-child(2) {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .header-bar h1 {
                font-size: 14px;
            }
            
            .dashboard-layout {
                padding: 10px;
                gap: 12px;
            }
            
            .filter-tab {
                padding: 8px 12px;
                font-size: 13px;
                flex: 1;
                text-align: center;
            }
            
            .products-table th,
            .products-table td {
                padding: 8px 6px;
                font-size: 13px;
            }
            
            .btn-action span {
                display: none;
            }
            
            .btn-action {
                padding: 8px;
            }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header-bar">
    <h1>GESTIÓN DE INVENTARIO - SISTEMA POS</h1>
    <div class="header-buttons">
        <a href="../index.php" class="btn-header">Menú Principal</a>
    </div>
</div>

<!-- LAYOUT PRINCIPAL -->
<div class="dashboard-layout">
    
    <!-- COLUMNA IZQUIERDA (75%) -->
    <div class="main-column">
        
        <!-- BÚSQUEDA Y FILTROS -->
        <div class="controls-section">
            <input type="text" 
                   id="buscar-input"
                   class="search-input"
                   placeholder="Buscar por nombre o código..." 
                   value="<?= htmlspecialchars($busqueda) ?>">
            
            <div class="filter-tabs">
                <a href="?filtro=todos<?= $busqueda ? '&buscar='.urlencode($busqueda) : '' ?>" 
                   class="filter-tab <?= $filtro === 'todos' ? 'active' : '' ?>">
                    Todos
                </a>
                <a href="?filtro=disponibles<?= $busqueda ? '&buscar='.urlencode($busqueda) : '' ?>" 
                   class="filter-tab <?= $filtro === 'disponibles' ? 'active' : '' ?>">
                    Disponibles
                </a>
                <a href="?filtro=por_agotarse<?= $busqueda ? '&buscar='.urlencode($busqueda) : '' ?>" 
                   class="filter-tab <?= $filtro === 'por_agotarse' ? 'active' : '' ?>">
                    Por Agotarse
                </a>
                <a href="?filtro=agotados<?= $busqueda ? '&buscar='.urlencode($busqueda) : '' ?>" 
                   class="filter-tab <?= $filtro === 'agotados' ? 'active' : '' ?>">
                    Agotados
                </a>
            </div>
        </div>
        
        <!-- TABLA DE PRODUCTOS -->
        <div class="products-table-container">
            <div class="table-header">
                <span>Productos en Inventario</span>
                <span class="products-count"><?= $productos->num_rows ?> productos</span>
            </div>
            
            <div class="table-wrapper">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Stock</th>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Cantidad</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($productos->num_rows > 0): ?>
                            <?php while ($p = $productos->fetch_assoc()): 
                                $clase_estado = 'disponible';
                                $clase_fila = '';
                                
                                if ($p['cantidad'] == 0) {
                                    $clase_estado = 'agotado';
                                    $clase_fila = 'row-agotado';
                                } elseif ($p['cantidad'] <= 10) {
                                    $clase_estado = 'por-agotarse';
                                    $clase_fila = 'row-por-agotarse';
                                }
                            ?>
                            
                            <tr class="<?= $clase_fila ?>">
                                <td>
                                    <div class="stock-indicator">
                                        <span class="stock-dot <?= $clase_estado ?>"></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="product-code"><?= htmlspecialchars($p['codigo']) ?></span>
                                </td>
                                <td>
                                    <span class="product-name"><?= htmlspecialchars($p['nombre']) ?></span>
                                </td>
                                <td>
                                    <span class="product-price">$<?= number_format($p['precio'], 0, ',', '.') ?></span>
                                </td>
                                <td>
                                    <span class="stock-number <?= $clase_estado ?>"><?= $p['cantidad'] ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="abrirModalEditar('<?= $p['codigo'] ?>', '<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>', <?= $p['precio'] ?>, <?= $p['cantidad'] ?>)" 
                                                class="btn-action btn-edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Editar
                                        </button>
                                        <button onclick="confirmarEliminar('<?= htmlspecialchars($p['codigo'], ENT_QUOTES) ?>', '<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>')" 
                                                class="btn-action btn-delete">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <p>No se encontraron productos</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    
    <!-- COLUMNA DERECHA (25%) - PANEL DE CONTROL -->
    <div class="sidebar-column">
        
        <!-- ESTADÍSTICAS -->
        <div class="stats-vertical">
            <div class="stat-micro-card success">
                <div class="stat-micro-label">Total Productos</div>
                <div class="stat-micro-value"><?= $total_productos ?></div>
            </div>
            
            <div class="stat-micro-card warning">
                <div class="stat-micro-label">Por Agotarse</div>
                <div class="stat-micro-value"><?= $por_agotarse ?></div>
            </div>
            
            <div class="stat-micro-card danger">
                <div class="stat-micro-label">Agotados</div>
                <div class="stat-micro-value"><?= $agotados ?></div>
            </div>
        </div>
        
        <!-- BOTONES DE ACCIÓN -->
        <div class="primary-actions">
            <button onclick="abrirModalCrear()" class="btn-primary-action btn-new-product">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nuevo Producto
            </button>
            
            <a href="reporte_pedido.php" class="btn-primary-action btn-report" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Reporte de Pedido
            </a>

            <button onclick="realizarBackup()" class="btn-primary-action btn-backup">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>
                Respaldo Base Datos
            </button>
        </div>
        
    </div>
    
</div>

<!-- MODAL CREAR PRODUCTO -->
<div id="modalCrear" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2>Nuevo Producto</h2>
            <button class="modal-close" onclick="cerrarModal('modalCrear')">&times;</button>
        </div>
        <form id="formCrear" onsubmit="crearProducto(event)">
            <div class="modal-body">
                <div class="form-group">
                    <label>Código *</label>
                    <input type="text" name="codigo" required placeholder="Ej: 12345678">
                </div>
                
                <div class="form-group">
                    <label>Nombre del Producto *</label>
                    <input type="text" name="nombre" required placeholder="Ej: Producto XYZ">
                </div>
                
                <div class="form-group">
                    <label>Precio (COP) *</label>
                    <input type="number" name="precio" required min="0" step="1" placeholder="Ej: 15000">
                </div>
                
                <div class="form-group">
                    <label>Cantidad Inicial *</label>
                    <input type="number" name="cantidad" required min="0" value="0">
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="cerrarModal('modalCrear')">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-submit">
                    Guardar Producto
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDITAR PRODUCTO -->
<div id="modalEditar" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2>Editar Producto</h2>
            <button class="modal-close" onclick="cerrarModal('modalEditar')">&times;</button>
        </div>
        <form id="formEditar" onsubmit="editarProducto(event)">
            <div class="modal-body">
                <div class="form-group">
                    <label>Código</label>
                    <input type="text" id="edit_codigo" name="codigo" readonly>
                </div>
                
                <div class="form-group">
                    <label>Nombre del Producto *</label>
                    <input type="text" id="edit_nombre" name="nombre" required>
                </div>
                
                <div class="form-group">
                    <label>Precio (COP) *</label>
                    <input type="number" id="edit_precio" name="precio" required min="0" step="1">
                </div>
                
                <div class="form-group">
                    <label>Cantidad en Stock *</label>
                    <input type="number" id="edit_cantidad" name="cantidad" required min="0">
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="cerrarModal('modalEditar')">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-submit">
                    Actualizar Producto
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CONFIRMAR ELIMINACIÓN -->
<div id="modalEliminar" class="modal-overlay">
    <div class="modal confirm-modal">
        <div class="modal-body">
            <span class="confirm-icon"></span>
            <h2>¿Eliminar producto?</h2>
            <p>Esta acción no se puede deshacer. El producto será eliminado permanentemente del inventario.</p>
            <div class="confirm-product-name" id="eliminar_nombre"></div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-cancel" onclick="cerrarModal('modalEliminar')">
                Cancelar
            </button>
            <button type="button" class="btn btn-delete-confirm" onclick="eliminarProducto()">
                Sí, Eliminar
            </button>
        </div>
    </div>
</div>

<script>
// Variables globales
let codigoEliminar = '';

// Búsqueda en tiempo real
const buscarInput = document.getElementById('buscar-input');
let timeoutId;

buscarInput.addEventListener('input', function() {
    clearTimeout(timeoutId);
    
    timeoutId = setTimeout(() => {
        const valor = buscarInput.value;
        const filtroActual = new URLSearchParams(window.location.search).get('filtro') || 'todos';
        
        if (valor.trim() === '') {
            window.location.href = `?filtro=${filtroActual}`;
        } else {
            window.location.href = `?filtro=${filtroActual}&buscar=${encodeURIComponent(valor)}`;
        }
    }, 500);
});

// Funciones de modales
function abrirModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function cerrarModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = 'auto';
    
    // Limpiar formularios
    if (id === 'modalCrear') {
        document.getElementById('formCrear').reset();
    }
}

function abrirModalCrear() {
    abrirModal('modalCrear');
}

function abrirModalEditar(codigo, nombre, precio, cantidad) {
    document.getElementById('edit_codigo').value = codigo;
    document.getElementById('edit_nombre').value = nombre;
    document.getElementById('edit_precio').value = precio;
    document.getElementById('edit_cantidad').value = cantidad;
    abrirModal('modalEditar');
}

function confirmarEliminar(codigo, nombre) {
    codigoEliminar = codigo ? codigo.trim() : '';
    document.getElementById('eliminar_nombre').textContent = nombre;
    abrirModal('modalEliminar');
}

// Crear producto
function crearProducto(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    fetch('crear.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacion(data.message, 'success');
            cerrarModal('modalCrear');
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarNotificacion(data.message || 'Error al crear el producto', 'error');
        }
    })
    .catch(err => {
        mostrarNotificacion('Error de conexión', 'error');
    });
}

// Editar producto
function editarProducto(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    fetch('editar.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacion(data.message, 'success');
            cerrarModal('modalEditar');
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarNotificacion(data.message || 'Error al actualizar el producto', 'error');
        }
    })
    .catch(err => {
        mostrarNotificacion('Error de conexión', 'error');
    });
}

// Eliminar producto
function eliminarProducto() {
    if (!codigoEliminar || codigoEliminar.trim() === '') {
        mostrarNotificacion('Error: No se pudo obtener el código del producto', 'error');
        return;
    }
    
    fetch('eliminar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ codigo: codigoEliminar.trim() })
    })
    .then(res => {
        // Verificar el status de la respuesta
        if (!res.ok) {
            throw new Error('HTTP ' + res.status);
        }
        // Obtener el texto de la respuesta primero
        return res.text().then(text => {
            try {
                // Intentar parsear como JSON
                return JSON.parse(text);
            } catch (e) {
                // Si no es JSON válido, mostrar el texto recibido
                console.error('Respuesta no es JSON válido:', text);
                throw new Error('Respuesta inválida del servidor: ' + text.substring(0, 100));
            }
        });
    })
    .then(data => {
        if (data && data.success) {
            mostrarNotificacion(data.message, 'success');
            cerrarModal('modalEliminar');
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarNotificacion(data.message || 'Error al eliminar el producto', 'error');
        }
    })
    .catch(err => {
        console.error('Error completo:', err);
        mostrarNotificacion('Error de conexión: ' + err.message, 'error');
    });
}

// Notificaciones
function mostrarNotificacion(mensaje, tipo) {
    const notif = document.createElement('div');
    notif.className = `notificacion notif-${tipo}`;
    notif.textContent = mensaje;
    document.body.appendChild(notif);
    
    setTimeout(() => notif.classList.add('show'), 10);
    setTimeout(() => {
        notif.classList.remove('show');
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}

// Realizar Backup
function realizarBackup() {
    mostrarNotificacion('Iniciando copia de seguridad...', 'info');
    
    fetch('../includes/backup_db.php')
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacion(data.message, 'success');
        } else {
            mostrarNotificacion(data.message, 'error');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        mostrarNotificacion('Error al conectar con el servidor', 'error');
    });
}

// Cerrar modal al hacer clic fuera
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            cerrarModal(this.id);
        }
    });
});
</script>

</body>
</html>