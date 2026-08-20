<?= $this->extend('layout/app_layout'); ?>

<?= $this->section('content'); ?>

<!-- BARRA DE NAVEGACIÓN SUPERIOR DE VENTAS -->
<div class="ventas-nav-header">
    <a href="<?= site_url('ventas/nueva'); ?>" class="ventas-nav-tab">
        ➕ NUEVA VENTA
    </a>
    <a href="<?= site_url('ventas/historial'); ?>" class="ventas-nav-tab">
        📋 HISTORIAL DE VENTAS
    </a>
    <a href="<?= site_url('ventas/apertura'); ?>" class="ventas-nav-tab active">
        📦 APERTURA DE PRODUCTOS
    </a>
</div>

<div class="apertura-container" style="max-width: 1200px; margin: 0 auto; padding: 1.5rem;">
    <style>
        .pos-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }
        .pos-modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        .pos-modal-box {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2rem;
            width: 90%;
            max-width: 400px;
            box-shadow: var(--shadow);
            transform: translateY(20px);
            transition: var(--transition);
            text-align: center;
        }
        .pos-modal-overlay.show .pos-modal-box {
            transform: translateY(0);
        }
        .pos-modal-icon {
            width: 60px;
            height: 60px;
            background: var(--danger-bg);
            color: var(--danger);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
        }
        .pos-modal-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        .pos-modal-msg {
            color: var(--text-secondary);
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }
        .pos-modal-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .pos-modal-btn {
            padding: 0.75rem;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        .pos-modal-btn-cancel {
            background: var(--bg-body);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }
        .pos-modal-btn-cancel:hover {
            background: var(--bg-card-hover);
        }
        .pos-modal-btn-confirm {
            background: var(--danger);
            color: #fff;
        }
        .pos-modal-btn-confirm:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
        }
    </style>
    
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alerta-success" style="padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                <span>✅</span>
                <div><?= session()->getFlashdata('success'); ?></div>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alerta-error" style="padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                <span>⚠️</span>
                <div><?= session()->getFlashdata('error'); ?></div>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            
            <!-- SECCIÓN IZQUIERDA: FORMULARIO DE APERTURA -->
            <div class="card">
                <h2 style="margin-bottom: 1.5rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.75rem;">
                    📦 Nueva Apertura
                </h2>
                
                <form action="<?= site_url('ventas/procesar-apertura'); ?>" method="POST" id="formApertura" class="modern-form">
                    <?= csrf_field(); ?>
                    
                    <div class="form-group">
                        <label for="producto_id" class="form-label">Producto a abrir <span class="text-danger">*</span></label>
                        <select name="producto_id" id="producto_id" class="form-input-custom select2-search" required>
                            <option value="">-- Seleccionar Producto --</option>
                            <?php foreach ($productos as $p): ?>
                                <?php if ($p['controla_stock']): ?>
                                    <option value="<?= $p['id']; ?>" 
                                            data-stock="<?= $p['stock_actual']; ?>"
                                            data-maneja-unidades="<?= $p['maneja_unidades']; ?>"
                                            data-unidades-por-caja="<?= $p['unidades_por_caja']; ?>"
                                            data-stock-unidades="<?= $p['stock_unidades']; ?>">
                                        <?= esc($p['nombre']); ?> (Stock: <?= $p['stock_actual']; ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="infoProducto" style="display: none; background: var(--bg-body); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid var(--border-color);">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <span class="text-muted" style="font-size: 0.8rem; display: block;">Stock Cerrado:</span>
                                <strong id="infoStockActual" style="font-size: 1.1rem; color: var(--accent);">0</strong>
                            </div>
                            <div id="divInfoUnidades" style="display: none;">
                                <span class="text-muted" style="font-size: 0.8rem; display: block;">Stock Unidades:</span>
                                <strong id="infoStockUnidades" style="font-size: 1.1rem; color: var(--success);">0</strong>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="cantidad" class="form-label">Cantidad a abrir <span class="text-danger">*</span></label>
                        <input type="number" name="cantidad" id="cantidad" class="form-input-custom" value="1" min="1" required>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Indique cuántas botellas/cajetillas desea abrir.</p>
                    </div>

                <div id="previewApertura" style="display: none; background: var(--accent-glow); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px dashed var(--accent);">
                    <h4 style="margin-top: 0; color: var(--accent); font-size: 0.9rem;">Resumen de la operación:</h4>
                    <ul id="listaResumen" style="margin: 0.5rem 0 0 1.25rem; font-size: 0.9rem; color: var(--text-primary);">
                    </ul>
                </div>

                <div class="form-actions" style="margin-top: 2rem;">
                    <button type="submit" class="btn-primary-full" id="btnProcesarApertura">
                        Procesar Apertura
                    </button>
                </div>
            </form>
        </div>

        <!-- SECCIÓN DERECHA: ÚLTIMAS APERTURAS / REVERSIÓN -->
        <div class="card">
            <h2 style="margin-bottom: 1.5rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.75rem;">
                🕒 Últimas Aperturas
            </h2>
            
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Cant.</th>
                            <th>Fecha</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($aperturas)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No hay aperturas registradas recientemente.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($aperturas as $a): ?>
                                <tr>
                                    <td>
                                        <strong><?= esc($a['producto_nombre']); ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge-status status-activo"><?= $a['cantidad']; ?></span>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.8rem; color: var(--text-muted);">
                                            <?= date('d/m H:i', strtotime($a['fecha'])); ?>
                                        </span>
                                    </td>
                                    <td class="text-center" style="display: flex; justify-content: center;">
                                        <form action="<?= site_url('ventas/revertir-apertura/' . $a['id']); ?>" method="POST" class="form-revertir">
                                            <?= csrf_field(); ?>
                                            <button type="button" class="btn-icon-only btn-danger btn-trigger-revertir"
                                                    title="Revertir Apertura" 
                                                    data-nombre="<?= esc($a['producto_nombre']); ?>"
                                                    style="background: var(--danger-bg); color: var(--danger); border-radius: 8px; width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border: none; cursor: pointer;">
                                                ↩️
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- MODAL DE CONFIRMACIÓN DE REVERSIÓN -->
<div class="pos-modal-overlay" id="modalRevertir">
    <div class="pos-modal-box">
        <div class="pos-modal-icon" id="modalRevertirIcon">↩️</div>
        <h3 class="pos-modal-title" id="modalRevertirTitle">¿Revertir Apertura?</h3>
        <p class="pos-modal-msg">Se ajustará el inventario del producto: <br><strong id="modalRevertirNombre"></strong></p>
        <p style="font-size: 0.85rem; color: var(--danger); margin-bottom: 1rem;">Esta acción restaurará el stock cerrado y descontará las unidades sueltas generadas.</p>
        
        <div class="pos-modal-actions">
            <button type="button" class="pos-modal-btn pos-modal-btn-cancel" id="btnCancelRevertir">Cancelar</button>
            <button type="button" class="pos-modal-btn pos-modal-btn-confirm" id="btnConfirmRevertir">Sí, Revertir</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectProducto = document.getElementById('producto_id');
    const inputCantidad = document.getElementById('cantidad');
    const infoProducto = document.getElementById('infoProducto');
    const infoStockActual = document.getElementById('infoStockActual');
    const infoStockUnidades = document.getElementById('infoStockUnidades');
    const divInfoUnidades = document.getElementById('divInfoUnidades');
    const previewApertura = document.getElementById('previewApertura');
    const listaResumen = document.getElementById('listaResumen');
    const btnProcesar = document.getElementById('btnProcesarApertura');

    function actualizarPreview() {
        const option = selectProducto.options[selectProducto.selectedIndex];
        if (!option.value) {
            infoProducto.style.display = 'none';
            previewApertura.style.display = 'none';
            return;
        }

        const stockActual = parseInt(option.dataset.stock);
        const manejaUnidades = option.dataset.manejaUnidades == '1';
        const unidadesPorCaja = parseInt(option.dataset.unidadesPorCaja);
        const stockUnidades = parseInt(option.dataset.stockUnidades);
        const cantidadApertura = parseInt(inputCantidad.value) || 0;

        infoProducto.style.display = 'block';
        infoStockActual.textContent = stockActual;
        
        if (manejaUnidades) {
            divInfoUnidades.style.display = 'block';
            infoStockUnidades.textContent = stockUnidades;
        } else {
            divInfoUnidades.style.display = 'none';
        }

        if (cantidadApertura > 0) {
            previewApertura.style.display = 'block';
            listaResumen.innerHTML = '';
            
            const li1 = document.createElement('li');
            li1.textContent = `Se descontarán ${cantidadApertura} unidades de la presentación cerrada.`;
            listaResumen.appendChild(li1);

            if (manejaUnidades) {
                const totalNuevasUnidades = cantidadApertura * unidadesPorCaja;
                const li2 = document.createElement('li');
                li2.textContent = `Se sumarán ${totalNuevasUnidades} unidades sueltas al inventario.`;
                listaResumen.appendChild(li2);
            }

            if (cantidadApertura > stockActual) {
                const liErr = document.createElement('li');
                liErr.style.color = 'var(--error)';
                liErr.style.fontWeight = 'bold';
                liErr.textContent = '⚠️ No hay suficiente stock disponible.';
                listaResumen.appendChild(liErr);
                btnProcesar.disabled = true;
                btnProcesar.classList.add('btn-disabled');
            } else {
                btnProcesar.disabled = false;
                btnProcesar.classList.remove('btn-disabled');
            }
        } else {
            previewApertura.style.display = 'none';
        }
    }

    selectProducto.addEventListener('change', actualizarPreview);
    inputCantidad.addEventListener('input', actualizarPreview);

    // Inicializar Select2 para el buscador de productos
    if ($.fn.select2) {
        $('.select2-search').select2({
            placeholder: "-- Seleccionar Producto --",
            allowClear: true,
            width: '100%'
        }).on('change', function() {
            actualizarPreview();
        });
    }

    // --- Lógica del Modal de Reversión ---
    const $modalRevertir = $('#modalRevertir');
    const $modalRevertirNombre = $('#modalRevertirNombre');
    const $btnConfirmRevertir = $('#btnConfirmRevertir');
    let $formRevertirActivo = null;

    $('.btn-trigger-revertir').on('click', function() {
        const $btn = $(this);
        $formRevertirActivo = $btn.closest('form');
        $modalRevertirNombre.text($btn.data('nombre'));
        
        $btnConfirmRevertir.prop('disabled', false).text('Sí, Revertir').css('opacity', '1');
        $modalRevertir.addClass('show');
    });

    $('#btnCancelRevertir, #modalRevertir').on('click', function(e) {
        if (e.target === this || $(this).attr('id') === 'btnCancelRevertir') {
            $modalRevertir.removeClass('show');
            $formRevertirActivo = null;
        }
    });

    $btnConfirmRevertir.on('click', function() {
        if ($formRevertirActivo) {
            $(this).prop('disabled', true).text('Procesando...').css('opacity', '0.7');
            $formRevertirActivo.submit();
        }
    });
});
</script>

<?= $this->endSection(); ?>
