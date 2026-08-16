/**
 * BAR MANAGER - HISTORIAL Y ANULACIÓN DE VENTAS SCRIPT
 * Etapa 5: Historial de Ventas y Anulación con Devolución de Stock
 */

document.addEventListener('DOMContentLoaded', function () {

    // ─────────────────────────────────────────────────────────────────────────
    // ELEMENTOS DOM
    // ─────────────────────────────────────────────────────────────────────────
    const detailModal       = document.getElementById('detailModal');
    const btnCloseDetail    = document.getElementById('btnCloseDetail');
    const btnCancelDetail   = document.getElementById('btnCancelDetail');
    const btnAnnulTrigger   = document.getElementById('btnAnnulTrigger');

    const annulConfirmModal = document.getElementById('annulConfirmModal');
    const annulVentaNum     = document.getElementById('annulVentaNum');
    const btnCancelAnnul    = document.getElementById('btnCancelAnnul');
    const btnConfirmAnnul   = document.getElementById('btnConfirmAnnul');
    const annulBtnText      = document.getElementById('annulBtnText');
    const annulErrorMsg     = document.getElementById('annulErrorMsg');

    // Campos del Modal de Detalle
    const detailVentaTitle = document.getElementById('detailVentaTitle');
    const detailVentaDate  = document.getElementById('detailVentaDate');
    const detailCajero     = document.getElementById('detailCajero');
    const detailMesero     = document.getElementById('detailMesero');
    const detailPago       = document.getElementById('detailPago');
    const detailEstado     = document.getElementById('detailEstado');
    const detailItemsBody  = document.getElementById('detailItemsBody');
    const detailSubtotal   = document.getElementById('detailSubtotal');
    const detailDescuento  = document.getElementById('detailDescuento');
    const detailTotalValue = document.getElementById('detailTotalValue');

    let currentVentaId = null;

    // ─────────────────────────────────────────────────────────────────────────
    // 1. EVENT LISTENERS EN BOTONES "VER DETALLE"
    // ─────────────────────────────────────────────────────────────────────────
    document.querySelectorAll('.btn-view-detail').forEach(btn => {
        btn.addEventListener('click', function () {
            const ventaId = parseInt(this.dataset.ventaId);
            if (ventaId > 0) {
                cargarDetalleVenta(ventaId);
            }
        });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // 2. CARGAR DETALLE DE VENTA DESDE EL BACKEND (GET JSON)
    // ─────────────────────────────────────────────────────────────────────────
    function cargarDetalleVenta(ventaId) {
        currentVentaId = ventaId;

        // Resetear UI del modal
        if (detailVentaTitle) detailVentaTitle.textContent = `Detalle de Venta #${ventaId}`;
        if (detailVentaDate)  detailVentaDate.textContent  = 'Cargando...';
        if (detailCajero)     detailCajero.textContent     = '...';
        if (detailMesero)     detailMesero.textContent     = '...';
        if (detailPago)       detailPago.textContent       = '...';
        if (detailEstado)     detailEstado.textContent     = '...';
        if (detailItemsBody)  detailItemsBody.innerHTML    = '<tr><td colspan="4" style="text-align:center; padding:1.5rem; color:var(--text-muted);">⏳ Cargando detalles...</td></tr>';
        if (btnAnnulTrigger)  btnAnnulTrigger.style.display = 'none';

        if (detailModal) detailModal.classList.add('show');

        fetch(`${HISTORIAL_BASE_URL}ventas/detalle/${ventaId}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.venta) {
                    if (detailItemsBody) detailItemsBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#f87171;">Error al cargar detalle.</td></tr>';
                    return;
                }

                const v = data.venta;

                // Formatear metadatos
                if (detailVentaTitle) detailVentaTitle.textContent = `Detalle de Venta #${v.id}`;
                if (detailVentaDate)  detailVentaDate.textContent  = formatearFecha(v.fecha_venta);
                if (detailCajero)     detailCajero.textContent     = v.cajero_nombre || 'Sistema';
                
                const meseroTexto = v.mesero_nombre ? `👨‍🍳 ${v.mesero_nombre}` : '🏪 Venta en barra';
                if (detailMesero)     detailMesero.textContent     = meseroTexto;

                let pagoTexto = v.tipo_pago;
                if (v.cliente_nombre) {
                    pagoTexto += ` (${v.cliente_nombre})`;
                }
                if (detailPago) detailPago.textContent = pagoTexto;

                const estadoUpper = (v.estado || '').toUpperCase();
                const esAnulada = estadoUpper === 'ANULADA' || estadoUpper === 'CANCELADA';

                if (detailEstado) {
                    detailEstado.innerHTML = esAnulada 
                        ? '<span class="badge-status status-anulada">🚫 ANULADA</span>'
                        : '<span class="badge-status status-completada">✅ COMPLETADA</span>';
                }

                // Renderizar tabla de ítems
                let itemsHtml = '';
                if (v.items && v.items.length > 0) {
                    v.items.forEach(item => {
                        const icon = item.tipo === 'promocion' ? '🔥' : '🍺';
                        itemsHtml += `
                        <tr>
                            <td>
                                <strong>${icon} ${escapeHtml(item.nombre)}</strong>
                                ${item.codigo ? `<br><small style="color:var(--text-muted); font-family:monospace;">${escapeHtml(item.codigo)}</small>` : ''}
                            </td>
                            <td style="text-align: center; font-weight: 700;">${item.cantidad}x</td>
                            <td style="text-align: right;">S/ ${parseFloat(item.precio_unitario).toFixed(2)}</td>
                            <td style="text-align: right; font-weight: 700; color: var(--accent);">S/ ${parseFloat(item.subtotal).toFixed(2)}</td>
                        </tr>`;
                    });
                } else {
                    itemsHtml = '<tr><td colspan="4" style="text-align:center; color:var(--text-muted);">Sin ítems registrados.</td></tr>';
                }
                if (detailItemsBody) detailItemsBody.innerHTML = itemsHtml;

                // Totales
                if (detailSubtotal)   detailSubtotal.textContent   = 'S/ ' + parseFloat(v.subtotal).toFixed(2);
                if (detailDescuento)  detailDescuento.textContent  = 'S/ ' + parseFloat(v.descuento || 0).toFixed(2);
                if (detailTotalValue) detailTotalValue.textContent = 'S/ ' + parseFloat(v.total).toFixed(2);

                // Mostrar botón Anular Venta sólo si está COMPLETADA
                if (btnAnnulTrigger) {
                    btnAnnulTrigger.style.display = (estadoUpper === 'COMPLETADA') ? 'inline-flex' : 'none';
                }
            })
            .catch(() => {
                if (detailItemsBody) detailItemsBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#f87171;">Error de conexión al cargar detalle.</td></tr>';
            });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. CERRAR MODAL DE DETALLE
    // ─────────────────────────────────────────────────────────────────────────
    if (btnCloseDetail) {
        btnCloseDetail.addEventListener('click', () => detailModal.classList.remove('show'));
    }
    if (btnCancelDetail) {
        btnCancelDetail.addEventListener('click', () => detailModal.classList.remove('show'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. TRIGGER BOTÓN ANULAR VENTA (ABRIR CONFIRMACIÓN)
    // ─────────────────────────────────────────────────────────────────────────
    if (btnAnnulTrigger) {
        btnAnnulTrigger.addEventListener('click', function () {
            if (!currentVentaId) return;

            if (annulVentaNum) annulVentaNum.textContent = `#${currentVentaId}`;
            hideAnnulError();
            setAnnulBtnLoading(false);

            if (annulConfirmModal) annulConfirmModal.classList.add('show');
        });
    }

    if (btnCancelAnnul) {
        btnCancelAnnul.addEventListener('click', () => annulConfirmModal.classList.remove('show'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. CONFIRMAR ANULACIÓN (POST AL BACKEND)
    // ─────────────────────────────────────────────────────────────────────────
    if (btnConfirmAnnul) {
        btnConfirmAnnul.addEventListener('click', function () {
            if (!currentVentaId) return;

            setAnnulBtnLoading(true);
            hideAnnulError();

            fetch(`${HISTORIAL_BASE_URL}ventas/anular/${currentVentaId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    [HISTORIAL_CSRF_NAME]: HISTORIAL_CSRF_HASH,
                },
            })
            .then(r => r.json())
            .then(data => {
                setAnnulBtnLoading(false);
                if (data.success) {
                    annulConfirmModal.classList.remove('show');
                    detailModal.classList.remove('show');
                    
                    // Recargar la página para actualizar la tabla y los KPIs de resumen
                    window.location.reload();
                } else {
                    showAnnulError(data.mensaje || 'No se pudo anular la venta.');
                }
            })
            .catch(() => {
                setAnnulBtnLoading(false);
                showAnnulError('Error de conexión al procesar la anulación.');
            });
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS Y UTILIDADES
    // ─────────────────────────────────────────────────────────────────────────
    function showAnnulError(msg) {
        if (annulErrorMsg) {
            annulErrorMsg.textContent   = msg;
            annulErrorMsg.style.display = 'block';
        }
    }

    function hideAnnulError() {
        if (annulErrorMsg) {
            annulErrorMsg.textContent   = '';
            annulErrorMsg.style.display = 'none';
        }
    }

    function setAnnulBtnLoading(loading) {
        if (!btnConfirmAnnul) return;
        btnConfirmAnnul.disabled = loading;
        if (annulBtnText) {
            annulBtnText.textContent = loading ? '⏳ Procesando anulación...' : 'Sí, Anular Venta';
        }
    }

    function formatearFecha(fechaStr) {
        if (!fechaStr) return '--/--/----';
        const d = new Date(fechaStr);
        if (isNaN(d.getTime())) return fechaStr;
        return d.toLocaleDateString('es-PE', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit', hour12: true
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
