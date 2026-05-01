<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Sistema POS</title>
    <style>
        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f5f7fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: #2c3e50;
            overflow-x: hidden;
        }
        
        /* CONTENEDOR PRINCIPAL */
        .menu-principal {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            background: linear-gradient(135deg, #e8ecef 0%, #f5f7fa 100%);
        }
        
        .menu-container {
            width: 100%;
            max-width: 800px;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        /* HEADER */
        .menu-header {
            text-align: center;
            animation: fadeInDown 0.6s ease-out;
        }
        
        .menu-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }
        
        .menu-header p {
            font-size: 1rem;
            color: #7f8c8d;
            font-weight: 400;
        }
        
        /* GRID DE OPCIONES */
        .menu-opciones {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            width: 100%;
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }
        
        /* CARDS */
        .menu-card {
            background: white;
            border-radius: 10px;
            padding: 30px 20px;
            text-decoration: none;
            color: #2c3e50;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 2px solid transparent;
            position: relative;
            cursor: pointer;
        }
        
        .menu-card:hover,
        .menu-card.focused {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            border-color: #3498db;
        }
        
        .menu-card:active {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
        }
        
        /* Indicador de foco */
        .menu-card.focused::before {
            content: '';
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            border: 2px solid #3498db;
            border-radius: 12px;
            opacity: 0.5;
        }
        
        /* ICONOS SVG */
        .card-icon {
            width: 60px;
            height: 60px;
            margin-bottom: 16px;
            color: #3498db;
            transition: all 0.3s ease;
        }
        
        .card-icon svg {
            width: 100%;
            height: 100%;
            stroke-width: 1.5;
        }
        
        .menu-card:hover .card-icon,
        .menu-card.focused .card-icon {
            transform: scale(1.1);
            color: #2980b9;
        }
        
        /* TEXTOS */
        .menu-card h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 6px;
            color: #2c3e50;
            transition: color 0.3s ease;
        }
        
        .menu-card p {
            font-size: 0.875rem;
            color: #7f8c8d;
            font-weight: 400;
            line-height: 1.4;
        }
        
        .menu-card:hover h2,
        .menu-card.focused h2 {
            color: #3498db;
        }
        
        /* FOOTER */
        .menu-footer {
            text-align: center;
            padding: 10px;
            animation: fadeIn 0.6s ease-out 0.4s both;
        }
        
        .menu-footer .btn-volver {
            display: inline-block;
            padding: 12px 24px;
            background: #2c3e50;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.2);
        }
        
        .menu-footer .btn-volver:hover {
            background: #34495e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.3);
        }
        
        .menu-footer p {
            font-size: 0.85rem;
            color: #95a5a6;
            font-weight: 400;
            margin-top: 12px;
        }
        
        /* ANIMACIONES */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .menu-opciones {
                grid-template-columns: 1fr;
                gap: 15px;
                max-width: 400px;
                margin: 0 auto;
            }
            
            .menu-card {
                padding: 25px 20px;
            }
            
            .card-icon {
                width: 50px;
                height: 50px;
                margin-bottom: 12px;
            }
            
            .menu-header h1 {
                font-size: 1.6rem;
            }
            
            .menu-header p {
                font-size: 0.95rem;
            }
            
            .menu-card h2 {
                font-size: 1.15rem;
            }
            
            .menu-card p {
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 480px) {
            .menu-principal {
                padding: 15px;
            }
            
            .menu-container {
                gap: 20px;
            }
            
            .menu-header h1 {
                font-size: 1.5rem;
            }
            
            .menu-card {
                padding: 20px 15px;
            }
            
            .card-icon {
                width: 45px;
                height: 45px;
                margin-bottom: 10px;
            }
            
            .menu-card h2 {
                font-size: 1.1rem;
            }
        }
        
        /* ACCESIBILIDAD */
        .menu-card:focus {
            outline: none;
        }
        
        .menu-card:focus-visible {
            outline: 3px solid #3498db;
            outline-offset: 4px;
        }
        
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        .menu-card:active .card-icon {
            transform: scale(0.95);
        }
        
        @media (prefers-contrast: high) {
            .menu-card {
                border: 2px solid #2c3e50;
            }
            
            .menu-card:hover,
            .menu-card.focused {
                border-color: #3498db;
                background: #f8f9fa;
            }
        }
    </style>
</head>
<body>

<div class="menu-principal">
    <div class="menu-container">
        <div class="menu-header">
            <h1>CENTRO DE REPORTES</h1>
            <p>Visualiza y analiza las ventas de tu negocio</p>
        </div>

        <div class="menu-opciones">
            <a href="diario.php" class="menu-card" data-index="0" tabindex="0">
                <div class="card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
                <h2>Reporte Diario</h2>
                <p>Ventas del día actual con detalles completos</p>
            </a>

            <a href="mensual.php" class="menu-card" data-index="1" tabindex="0">
                <div class="card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="20" x2="12" y2="10"/>
                        <line x1="18" y1="20" x2="18" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="16"/>
                    </svg>
                </div>
                <h2>Reporte Mensual</h2>
                <p>Análisis completo del mes en curso</p>
            </a>
        </div>

        <div class="menu-footer">
            <a href="../index.php" class="btn-volver">← Menú Principal</a>
            <p>Use las flechas del teclado para navegar • Enter para seleccionar</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.menu-card');
    let currentIndex = 0;

    function updateFocus(index) {
        cards.forEach(card => card.classList.remove('focused'));
        cards[index].classList.add('focused');
        cards[index].focus();
    }

    document.addEventListener('keydown', function(e) {
        if (!document.querySelector('.menu-principal')) return;

        switch(e.key) {
            case 'ArrowRight':
            case 'ArrowDown':
                e.preventDefault();
                currentIndex = (currentIndex + 1) % cards.length;
                updateFocus(currentIndex);
                break;
            
            case 'ArrowLeft':
            case 'ArrowUp':
                e.preventDefault();
                currentIndex = (currentIndex - 1 + cards.length) % cards.length;
                updateFocus(currentIndex);
                break;
            
            case 'Enter':
                e.preventDefault();
                cards[currentIndex].click();
                break;
            
            case '1':
                e.preventDefault();
                cards[0].click();
                break;
            
            case '2':
                e.preventDefault();
                cards[1].click();
                break;
        }
    });

    cards.forEach((card, index) => {
        card.addEventListener('click', function() {
            currentIndex = index;
        });

        card.addEventListener('focus', function() {
            currentIndex = index;
            updateFocus(index);
        });
    });

    updateFocus(0);
});
</script>

</body>
</html>