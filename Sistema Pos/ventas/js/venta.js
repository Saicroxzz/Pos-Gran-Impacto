// Para producción: comentar o eliminar este alert
// alert("venta.js cargado correctamente");

document.addEventListener("DOMContentLoaded", () => {

    const buscador = document.getElementById("buscador");
    const resultados = document.getElementById("resultados");
    const detalleVenta = document.getElementById("detalle-venta");
    const totalSpan = document.getElementById("total");
    const btnFinalizar = document.querySelector(".btn-finalizar");
    const clienteInput = document.getElementById("cliente");
    const montoRecibidoInput = document.getElementById("monto_recibido");
    const cambioInput = document.getElementById("cambio");
    const pagoEfectivoDiv = document.getElementById("pago-efectivo");

    let productosVenta = {};
    let totalVenta = 0;
    let resultadoSeleccionado = -1;

    // FOCO AUTOMÁTICO AGRESIVO - Para que funcione el escáner
    buscador.focus();
    
    // Capturar TODAS las teclas cuando no estamos en un input específico
    document.addEventListener("keydown", (e) => {
        const inputsEspeciales = [montoRecibidoInput, clienteInput];
        const cantidadInputs = document.querySelectorAll('.cantidad');
        
        // Si estamos en un input especial, no hacer nada
        if (inputsEspeciales.includes(document.activeElement) || 
            Array.from(cantidadInputs).includes(document.activeElement)) {
            return;
        }
        
        // Si NO estamos en el buscador, redirigir el foco
        if (document.activeElement !== buscador) {
            buscador.focus();
        }
    });
    
    // Re-enfocar al hacer clic en cualquier parte
    document.addEventListener("mousedown", (e) => {
        const clickEnInputEspecial = 
            e.target === montoRecibidoInput || 
            e.target === clienteInput || 
            e.target.classList.contains('cantidad') ||
            e.target.classList.contains('btn-eliminar') ||
            e.target.classList.contains('btn-finalizar');
        
        if (!clickEnInputEspecial) {
            setTimeout(() => buscador.focus(), 0);
        }
    });

    /* ===============================
       BUSCADOR CON NAVEGACIÓN POR TECLADO
    =============================== */
    
    // Evento para navegación con flechas y Enter
    buscador.addEventListener("keydown", (e) => {
        const items = Array.from(resultados.querySelectorAll('.resultado-item')).filter(item => 
            !item.classList.contains('sin-stock') && item.dataset.codigo
        );
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (items.length > 0) {
                resultadoSeleccionado++;
                if (resultadoSeleccionado >= items.length) {
                    resultadoSeleccionado = 0;
                }
                actualizarSeleccion(items);
            }
        } 
        else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (items.length > 0) {
                resultadoSeleccionado--;
                if (resultadoSeleccionado < 0) {
                    resultadoSeleccionado = items.length - 1;
                }
                actualizarSeleccion(items);
            }
        } 
        else if (e.key === 'Enter') {
            e.preventDefault();
            if (items.length > 0) {
                if (resultadoSeleccionado >= 0 && resultadoSeleccionado < items.length) {
                    agregarProductoDesdeItem(items[resultadoSeleccionado]);
                } else if (items.length === 1) {
                    // Si solo hay un resultado, agregarlo directamente
                    agregarProductoDesdeItem(items[0]);
                }
            }
        } 
        else if (e.key === 'Escape') {
            e.preventDefault();
            resultados.innerHTML = "";
            resultados.style.display = "none";
            buscador.value = "";
            resultadoSeleccionado = -1;
        }
    });
    
    function actualizarSeleccion(items) {
        items.forEach((item, index) => {
            if (index === resultadoSeleccionado) {
                item.style.background = '#3498db';
                item.style.color = 'white';
                item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } else {
                item.style.background = '';
                item.style.color = '';
            }
        });
    }
    
    function agregarProductoDesdeItem(item) {
        const codigo = item.dataset.codigo;
        const nombre = item.dataset.nombre;
        const precio = item.dataset.precio;
        
        if (!codigo || !nombre || !precio) {
            mostrarNotificacion("Error al obtener datos del producto", "error");
            return;
        }

        if (productosVenta[codigo]) {
            productosVenta[codigo].cantidad++;
        } else {
            productosVenta[codigo] = {
                codigo,
                nombre,
                precio: parseFloat(precio),
                cantidad: 1
            };
        }

        renderTabla();
        buscador.value = "";
        resultados.innerHTML = "";
        resultados.style.display = "none";
        resultadoSeleccionado = -1;
    }
    
    // Evento para búsqueda (solo letras y números)
    buscador.addEventListener("input", () => {
        const texto = buscador.value.trim();
        resultadoSeleccionado = -1;
        
        if (!texto) {
            resultados.innerHTML = "";
            resultados.style.display = "none";
            return;
        }
        
        fetch("buscar_producto.php?q=" + encodeURIComponent(texto))
            .then(res => res.text())
            .then(data => {
                resultados.innerHTML = data;
                if (data && data.trim().length > 0) {
                    resultados.style.display = "block";
                    
                    // Si el escáner pone todo el código de una vez y solo hay 1 resultado, seleccionarlo automáticamente después de 200ms
                    const items = Array.from(resultados.querySelectorAll('.resultado-item')).filter(item => 
                        !item.classList.contains('sin-stock') && item.dataset.codigo
                    );
                    
                    if (items.length === 1 && texto.length >= 8) {
                        setTimeout(() => {
                            if (buscador.value === texto) { // Verificar que no haya cambiado
                                agregarProductoDesdeItem(items[0]);
                            }
                        }, 300);
                    }
                } else {
                    resultados.style.display = "none";
                }
            })
            .catch(error => {
                console.error("Error en búsqueda:", error);
            });
    });

    // Cerrar resultados al hacer clic fuera
    document.addEventListener("click", (e) => {
        if (!buscador.contains(e.target) && !resultados.contains(e.target)) {
            resultados.style.display = "none";
            resultadoSeleccionado = -1;
        }
    });

    /* ===============================
       AGREGAR PRODUCTO (Click en resultado)
    =============================== */
    resultados.addEventListener("click", e => {
        const item = e.target.closest(".resultado-item");
        if (!item) return;
        
        // Evitar agregar productos sin stock
        if (item.classList.contains("sin-stock")) {
            mostrarNotificacion("Producto sin stock disponible", "error");
            return;
        }

        agregarProductoDesdeItem(item);
    });

    function renderTabla() {
        detalleVenta.innerHTML = "";
        totalVenta = 0;

        if (Object.keys(productosVenta).length === 0) {
            detalleVenta.innerHTML = `
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px 20px; color: #95a5a6;">
                        <p style="font-size: 14px; margin-top: 10px;">Agregue productos para iniciar la venta</p>
                    </td>
                </tr>
            `;
            totalSpan.textContent = "$0";
            return;
        }

        Object.values(productosVenta).forEach(p => {
            const subtotal = p.precio * p.cantidad;
            totalVenta += subtotal;

            detalleVenta.innerHTML += `
                <tr>
                    <td><input type="number" min="1" value="${p.cantidad}" data-codigo="${p.codigo}" class="cantidad"></td>
                    <td>${p.nombre}</td>
                    <td>$${formato(p.precio)}</td>
                    <td>$${formato(subtotal)}</td>
                    <td><button class="btn-eliminar" data-codigo="${p.codigo}">×</button></td>
                </tr>
            `;
        });

        totalSpan.textContent = `$${formato(totalVenta)}`;
        calcularCambio();
    }

    detalleVenta.addEventListener("input", e => {
        if (!e.target.classList.contains("cantidad")) return;
        const codigo = e.target.dataset.codigo;
        const nuevaCantidad = parseInt(e.target.value);
        
        if (nuevaCantidad > 0) {
            productosVenta[codigo].cantidad = nuevaCantidad;
        } else {
            delete productosVenta[codigo];
        }
        renderTabla();
    });

    detalleVenta.addEventListener("click", e => {
        if (!e.target.classList.contains("btn-eliminar")) return;
        delete productosVenta[e.target.dataset.codigo];
        renderTabla();
    });

    /* ===============================
       EFECTIVO / TRANSFERENCIA
    =============================== */
    document.querySelectorAll('input[name="pago"]').forEach(r => {
        r.addEventListener("change", () => {
            if (r.value === "EFECTIVO") {
                pagoEfectivoDiv.style.display = "block";
                montoRecibidoInput.focus();
            } else {
                pagoEfectivoDiv.style.display = "none";
                montoRecibidoInput.value = "";
                cambioInput.value = "";
            }
        });
    });

    montoRecibidoInput.addEventListener("input", calcularCambio);

    function calcularCambio() {
        const pago = parseInt(montoRecibidoInput.value) || 0;
        const cambio = pago - totalVenta;

        if (montoRecibidoInput.value === "") {
            cambioInput.value = "";
        } else if (cambio >= 0) {
            cambioInput.value = `$${formato(cambio)}`;
            cambioInput.style.color = "#27ae60";
        } else {
            cambioInput.value = "Monto insuficiente";
            cambioInput.style.color = "#e74c3c";
        }
    }

    /* ===============================
       FINALIZAR
    =============================== */
    btnFinalizar.addEventListener("click", () => {

        if (!Object.keys(productosVenta).length) {
            mostrarNotificacion("No hay productos en la venta", "error");
            return;
        }

        const metodo = document.querySelector('input[name="pago"]:checked').value;
        const cliente = clienteInput.value.trim() || "Consumidor Final";

        let monto_recibido = totalVenta;

        if (metodo === "EFECTIVO") {
            monto_recibido = parseInt(montoRecibidoInput.value) || 0;
            const cambio = monto_recibido - totalVenta;

            if (cambio < 0) {
                mostrarNotificacion("El monto recibido es insuficiente", "error");
                return;
            }
        }

        const data = {
            productos: Object.values(productosVenta),
            total: totalVenta,
            metodo,
            monto_recibido
        };

        btnFinalizar.disabled = true;
        btnFinalizar.textContent = "Procesando...";

        fetch("finalizar_venta.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(resp => {
            if (resp.ok) {
                window.location.href =
                    `factura.php?id=${resp.id_venta}&cliente=${encodeURIComponent(cliente)}`;
            } else {
                mostrarNotificacion("Error al procesar la venta", "error");
                btnFinalizar.disabled = false;
                btnFinalizar.textContent = "Finalizar Venta";
            }
        })
        .catch(err => {
            console.error("Error:", err);
            mostrarNotificacion("Error de conexión", "error");
            btnFinalizar.disabled = false;
            btnFinalizar.textContent = "Finalizar Venta";
        });
    });

    function formato(v) {
        return v.toLocaleString("es-CO");
    }

    function mostrarNotificacion(mensaje, tipo) {
        const notif = document.createElement("div");
        notif.className = `notificacion notif-${tipo}`;
        notif.textContent = mensaje;
        document.body.appendChild(notif);

        setTimeout(() => notif.classList.add("show"), 10);
        setTimeout(() => {
            notif.classList.remove("show");
            setTimeout(() => notif.remove(), 300);
        }, 3000);
    }
});