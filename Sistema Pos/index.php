<?php
include("includes/header.php");
?>

<div class="menu-principal">
    <div class="menu-container">
        <div class="menu-header">
            <h1>SISTEMA POS</h1>
            <p>Almacén El Gran Impacto</p>
        </div>

        <div class="menu-opciones">
            <a href="ventas/nueva_venta.php" class="menu-card" data-index="0" tabindex="0">
                <div class="card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"/>
                        <circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                </div>
                <h2>Registrar Venta</h2>
                <p>Nueva transacción de venta</p>
            </a>

            <a href="productos/dashboard.php" class="menu-card" data-index="1" tabindex="0">
                <div class="card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                        <line x1="12" y1="22.08" x2="12" y2="12"/>
                    </svg>
                </div>
                <h2>Inventario</h2>
                <p>Gestión de productos</p>
            </a>

            <a href="reportes/index.php" class="menu-card" data-index="2" tabindex="0">
                <div class="card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                </div>
                <h2>Reportes</h2>
                <p>Análisis y estadísticas</p>
            </a>

            <div class="menu-card" data-index="3" tabindex="0" id="btn-backup-root">
                <div class="card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                </div>
                <h2>Backups</h2>
                <p>Copia de seguridad</p>
            </div>
        </div>

        <div class="menu-footer">
            <p>Use las flechas del teclado para navegar • Enter para seleccionar</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.menu-card');
    let currentIndex = 0;

    // Función para actualizar el foco
    function updateFocus(index) {
        cards.forEach(card => card.classList.remove('focused'));
        cards[index].classList.add('focused');
        cards[index].focus();
    }

    // Navegación con teclado
    document.addEventListener('keydown', function(e) {
        // Solo actuar si estamos en el menú principal
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
            
            case '3':
                e.preventDefault();
                cards[2].click();
                break;
            
            case '4':
                e.preventDefault();
                cards[3].click();
                break;
        }
    });

    // Lógica para el botón de backup en el root
    document.getElementById('btn-backup-root').addEventListener('click', function() {
        if(confirm('¿Desea generar una copia de seguridad de la base de datos ahora?')) {
            const btn = this;
            btn.style.opacity = '0.5';
            btn.style.pointerEvents = 'none';
            
            fetch('includes/backup_db.php')
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'all';
            })
            .catch(err => {
                alert('Error al generar el backup');
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'all';
            });
        }
    });

    // Actualizar índice al hacer clic
    cards.forEach((card, index) => {
        card.addEventListener('click', function() {
            currentIndex = index;
        });

        card.addEventListener('focus', function() {
            currentIndex = index;
            updateFocus(index);
        });
    });

    // Foco inicial
    updateFocus(0);
});
</script>

<?php include("includes/footer.php"); ?>
