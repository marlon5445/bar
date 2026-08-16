(function (window, document) {
    'use strict';

    // ──────────────────────────────────────────────────────────────────
    // Live search
    // ──────────────────────────────────────────────────────────────────
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let debounceTimer = null;
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                document.getElementById('formSearchFiados')?.submit();
            }, 600);
        });
    }

    // ──────────────────────────────────────────────────────────────────
    // Funciones Internas de Modal de Pago
    // ──────────────────────────────────────────────────────────────────
    function showModalPago() {
        const modal = document.getElementById('modalPago');
        if (!modal) return;
        
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        const modalBox = modal.querySelector('.modal-box');
        if (modalBox) {
            modalBox.onmousedown = (e) => e.stopPropagation();
            modalBox.onclick = (e) => e.stopPropagation();
        }

        setTimeout(() => {
            const input = document.getElementById('montoPago');
            if (input) {
                input.focus();
            }
        }, 150);
    }

    function hideModalPago() {
        const modal = document.getElementById('modalPago');
        if (!modal) return;
        modal.style.display = 'none';
        document.body.style.overflow = '';

        const form = document.getElementById('formPago');
        if (form) {
            form.reset();
            const efectivo = form.querySelector('input[value="EFECTIVO"]');
            if (efectivo) efectivo.checked = true;
        }
        ocultarAlerta();
    }

    // ──────────────────────────────────────────────────────────────────
    // Funciones Internas de Modal de Detalle
    // ──────────────────────────────────────────────────────────────────
    function showModalDetalle(data) {
        const modal = document.getElementById('modalDetalle');
        if (!modal) return;
        
        document.getElementById('detalleTitulo').textContent = 'Detalle ' + data.referencia;
        document.getElementById('detalleFecha').textContent = data.fecha;
        document.getElementById('detalleTotal').textContent = 'S/ ' + parseFloat(data.total).toFixed(2);

        const garantiaBox = document.getElementById('detalleGarantia');
        const garantiaTexto = document.getElementById('detalleGarantiaTexto');
        if (data.observacion) {
            garantiaTexto.textContent = data.observacion;
            garantiaBox.style.display = 'block';
        } else {
            garantiaBox.style.display = 'none';
        }

        const list = document.getElementById('detalleProductosList');
        list.innerHTML = '';

        if (data.productos && data.productos.length > 0) {
            data.productos.forEach(p => {
                const item = document.createElement('div');
                item.style.display = 'flex';
                item.style.justifyContent = 'space-between';
                item.style.fontSize = '14px';
                item.style.padding = '5px 0';
                item.style.borderBottom = '1px dashed #eee';
                item.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-weight: 700; color: #1e293b;">${escapeHtml(p.nombre)}</span>
                            <span style="font-size: 12px; color: #64748b;">${p.cantidad} x S/ ${p.precio.toFixed(2)}</span>
                        </div>
                        <span style="font-weight: 800; color: #0f172a; font-size: 15px;">S/ ${p.subtotal.toFixed(2)}</span>
                    </div>
                `;
                list.appendChild(item);
            });
        } else {
            list.innerHTML = '<div style="color: #999; font-style: italic;">No hay información de productos.</div>';
        }

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        const modalBox = modal.querySelector('.modal-box');
        if (modalBox) {
            modalBox.onmousedown = (e) => e.stopPropagation();
            modalBox.onclick = (e) => e.stopPropagation();
        }
    }

    function hideModalDetalle() {
        const modal = document.getElementById('modalDetalle');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // Handlers de Cierre
    // ──────────────────────────────────────────────────────────────────
    function setupModalCloseHandlers() {
        const modalPago = document.getElementById('modalPago');
        const modalDetalle = document.getElementById('modalDetalle');

        if (modalPago) {
            modalPago.addEventListener('click', function(e) {
                // SOLO cerrar si el click es EXACTAMENTE en el overlay (fondo)
                if (e.target.id === 'modalPago') {
                    hideModalPago();
                }
            });
        }

        if (modalDetalle) {
            modalDetalle.addEventListener('click', function(e) {
                // SOLO cerrar si el click es EXACTAMENTE en el overlay (fondo)
                if (e.target.id === 'modalDetalle') {
                    hideModalDetalle();
                }
            });
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // Alertas y Utilidades
    // ──────────────────────────────────────────────────────────────────
    function mostrarAlerta(msg, tipo) {
        const el = document.getElementById('pagoAlerta');
        if (!el) return;
        el.textContent = msg;
        el.className   = 'pago-alerta alerta-' + tipo;
        el.style.display = 'block';
    }

    function ocultarAlerta() {
        const el = document.getElementById('pagoAlerta');
        if (el) el.style.display = 'none';
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

    // ──────────────────────────────────────────────────────────────────
    // EXPORTACIÓN AL OBJETO WINDOW (API GLOBAL)
    // ──────────────────────────────────────────────────────────────────
    window.abrirModalPago = function () {
        showModalPago();
    };

    window.cerrarModalPago = function () {
        hideModalPago();
    };

    window.abrirModalDetalle = function (data) {
        showModalDetalle(data);
    };

    window.cerrarModalDetalle = function () {
        hideModalDetalle();
    };

    window.setMonto = function (valor) {
        const input = document.getElementById('montoPago');
        if (input) {
            input.value = parseFloat(valor).toFixed(2);
            input.focus();
            ocultarAlerta();
        }
    };

    window.submitPago = function (event) {
        event.preventDefault();
        const form = document.getElementById('formPago');
        const btnOk = document.getElementById('btnConfirmarPago');
        if (!form || !btnOk) return;

        ocultarAlerta();
        const monto = parseFloat(document.getElementById('montoPago')?.value || 0);
        if (isNaN(monto) || monto <= 0) {
            mostrarAlerta('El monto debe ser mayor a 0.', 'error');
            return;
        }

        const tipoPago = form.querySelector('input[name="tipo_pago"]:checked')?.value;
        if (!tipoPago) {
            mostrarAlerta('Seleccione un método de pago.', 'error');
            return;
        }

        const payload = {
            cliente_id: parseInt(form.querySelector('[name="cliente_id"]')?.value || 0),
            monto: monto,
            tipo_pago: tipoPago,
            observacion: form.querySelector('[name="observacion"]')?.value || '',
        };

        btnOk.disabled = true;
        btnOk.textContent = '⏳ Procesando...';

        fetch(window.FIADO_URL_PAGAR, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta('✅ ' + data.mensaje, 'success');
                setTimeout(() => { window.location.reload(); }, 1200);
            } else {
                mostrarAlerta('❌ ' + (data.mensaje || 'Error al procesar el pago.'), 'error');
                btnOk.disabled = false;
                btnOk.textContent = '💰 Confirmar Pago';
            }
        })
        .catch(err => {
            console.error('[Fiados] Error:', err);
            mostrarAlerta('❌ Error de conexión.', 'error');
            btnOk.disabled = false;
            btnOk.textContent = '💰 Confirmar Pago';
        });
    };

    window.guardarGarantia = function () {
        const input = document.getElementById('inputGarantia');
        const valor = input?.value.trim() || '';
        const clienteId = window.FIADO_CLIENTE_ID;
        if (!clienteId) return;

        const btn = event.target;
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = '⏳';

        fetch(window.FIADO_URL_GUARDAR_GARANTIA, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ cliente_id: clienteId, garantia: valor }),
        })
        .then(res => res.json())
        .then(data => {
            btn.textContent = data.success ? '✅' : '❌';
            setTimeout(() => {
                btn.textContent = originalText;
                btn.disabled = false;
            }, 2000);
            if (!data.success) alert(data.mensaje);
        })
        .catch(() => {
            alert('Error de conexión');
            btn.textContent = originalText;
            btn.disabled = false;
        });
    };

    // ──────────────────────────────────────────────────────────────────
    // Inicialización al cargar el DOM
    // ──────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        setupModalCloseHandlers();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            hideModalPago();
            hideModalDetalle();
        }
    });

})(window, document);
