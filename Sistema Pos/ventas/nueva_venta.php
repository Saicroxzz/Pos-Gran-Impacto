<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Venta - Sistema POS</title>
    <style>
        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #e8ecef;
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
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            padding: 0;
            border: none;
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
        
        /* LAYOUT PRINCIPAL */
        .pos-container {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 20px;
            padding: 20px;
            height: calc(100vh - 60px);
            max-width: 1600px;
            margin: 0 auto;
        }
        
        /* PANEL IZQUIERDO */
        .panel-left {
            display: flex;
            flex-direction: column;
            gap: 15px;
            overflow: hidden;
        }
        
        /* BUSCADOR */
        .buscador {
            position: relative;
        }
        
        .buscador input {
            width: 100%;
            padding: 14px 18px;
            font-size: 15px;
            border: 2px solid #cbd5e0;
            border-radius: 8px;
            outline: none;
            transition: all 0.3s ease;
            background: white;
        }
        
        .buscador input:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        #resultados {
            display: none;
            position: absolute;
            width: 100%;
            background: white;
            border: 2px solid #3498db;
            border-radius: 8px;
            margin-top: 8px;
            max-height: 250px;
            overflow-y: auto;
            z-index: 9999;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        #resultados[style*="display: block"] {
            display: block !important;
        }
        
        .resultado-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s ease;
        }
        
        .resultado-item:hover {
            background: #f8f9fa;
        }
        
        .resultado-item:last-child {
            border-bottom: none;
        }
        
        .resultado-item.sin-stock {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .resultado-item.sin-stock:hover {
            background: white;
        }
        
        /* TABLA DE PRODUCTOS */
        .productos-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            flex: 1;
        }
        
        .productos-header {
            background: #f8f9fa;
            padding: 12px 16px;
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .tabla-wrapper {
            flex: 1;
            overflow-y: auto;
        }
        
        .tabla-pos {
            width: 100%;
            border-collapse: collapse;
        }
        
        .tabla-pos thead {
            background: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .tabla-pos th {
            text-align: left;
            padding: 10px 12px;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .tabla-pos td {
            padding: 10px 12px;
            border-bottom: 1px solid #f0f0f0;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .tabla-pos tbody tr:hover {
            background: #f8f9fa;
        }
        
        .tabla-pos input.cantidad {
            width: 60px;
            padding: 6px;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            text-align: center;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s ease;
        }
        
        .tabla-pos input.cantidad:focus {
            border-color: #3498db;
        }
        
        .btn-eliminar {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn-eliminar:hover {
            background: #c0392b;
        }
        
        /* ESTADO VACÍO */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #95a5a6;
        }
        
        .empty-state p {
            font-size: 14px;
            margin-top: 10px;
        }
        
        /* PANEL DERECHO */
        .panel-right {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        /* TOTAL */
        .total-card {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .total-label {
            font-size: 13px;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 8px;
        }
        
        .total-amount {
            font-size: 36px;
            font-weight: 700;
            letter-spacing: -1px;
        }
        
        /* FORMULARIO */
        .form-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            flex: 1;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #2c3e50;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"] {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .form-group input:read-only {
            background: #f8f9fa;
            cursor: not-allowed;
        }
        
        /* MÉTODO DE PAGO */
        .pago-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 8px;
        }
        
        .pago-options label {
            position: relative;
            cursor: pointer;
        }
        
        .pago-options input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .pago-option-card {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            border: 2px solid #cbd5e0;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .pago-options input[type="radio"]:checked + .pago-option-card {
            border-color: #3498db;
            background: #ebf5fb;
            color: #2980b9;
        }
        
        .pago-option-card:hover {
            border-color: #3498db;
        }
        
        #pago-efectivo {
            display: none;
            margin-top: 16px;
            padding: 16px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        /* BOTÓN FINALIZAR */
        .btn-finalizar {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }
        
        .btn-finalizar:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
        }
        
        .btn-finalizar:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            box-shadow: none;
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
        
        .notif-error {
            background: #e74c3c;
            color: white;
        }
        
        .notif-success {
            background: #27ae60;
            color: white;
        }
        
        /* RESPONSIVE */
        @media (max-width: 1200px) {
            .pos-container {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr;
            }
            
            .panel-right {
                max-height: 400px;
            }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header-bar">
    <h1>SISTEMA POS - ALMACÉN EL GRAN IMPACTO</h1>
    <div class="header-buttons">
        <a href="../index.php" class="btn-header">Menú Principal</a>
        <a href="../reportes/diario.php" class="btn-header">Reportes</a>
    </div>
</div>

<!-- CONTENEDOR PRINCIPAL -->
<div class="pos-container">
    
    <!-- PANEL IZQUIERDO: Búsqueda y Productos -->
    <div class="panel-left">
        
        <!-- BUSCADOR -->
        <div class="buscador">
            <input type="text" 
                   id="buscador"
                   placeholder="Escanee código de barras o busque producto..."
                   autocomplete="off">
            <div id="resultados"></div>
        </div>
        
        <!-- TABLA DE PRODUCTOS -->
        <div class="productos-card">
            <div class="productos-header">PRODUCTOS EN VENTA</div>
            <div class="tabla-wrapper">
                <table class="tabla-pos">
                    <thead>
                        <tr>
                            <th>Cant.</th>
                            <th>Producto</th>
                            <th>Precio Unit.</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="detalle-venta">
                        <tr>
                            <td colspan="5" class="empty-state">
                                <p>Agregue productos para iniciar la venta</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    
    <!-- PANEL DERECHO: Total y Pago -->
    <div class="panel-right">
        
        <!-- TOTAL -->
        <div class="total-card">
            <div class="total-label">TOTAL A PAGAR</div>
            <div class="total-amount" id="total">$0</div>
        </div>
        
        <!-- FORMULARIO DE PAGO -->
        <div class="form-card">
            
            <!-- CLIENTE -->
            <div class="form-group">
                <label>Nombre del Cliente</label>
                <input type="text" 
                       id="cliente" 
                       placeholder="Consumidor Final">
            </div>
            
            <!-- MÉTODO DE PAGO -->
            <div class="form-group">
                <label>Método de Pago</label>
                <div class="pago-options">
                    <label>
                        <input type="radio" name="pago" value="EFECTIVO" checked>
                        <div class="pago-option-card">Efectivo</div>
                    </label>
                    <label>
                        <input type="radio" name="pago" value="TRANSFERENCIA">
                        <div class="pago-option-card">Transferencia</div>
                    </label>
                </div>
            </div>
            
            <!-- EFECTIVO -->
            <div id="pago-efectivo" style="display: block;">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Monto Recibido</label>
                        <input type="number" 
                               id="monto_recibido" 
                               min="0" 
                               placeholder="200000"
                               step="1000">
                    </div>
                    
                    <div class="form-group">
                        <label>Cambio</label>
                        <input type="text" 
                               id="cambio" 
                               readonly 
                               placeholder="$0">
                    </div>
                </div>
            </div>
            
            <!-- BOTÓN FINALIZAR -->
            <button class="btn-finalizar">Finalizar Venta</button>
            
        </div>
        
    </div>
    
</div>

<script src="js/venta.js"></script>

</body>
</html>