<?= $this->extend('layout/app_layout'); ?>

<?= $this->section('content'); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">🍺 Gestión de Productos</h1>
        <p class="page-subtitle">Administra los productos, precios e inventario del bar</p>
    </div>
    <div class="page-header-actions">
        <?php if (session()->get('rol') === 'ADMIN'): ?>
            <a href="<?= site_url('productos/crear'); ?>" class="btn-primary">
                <span class="btn-icon">+</span> Nuevo Producto
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="toolbar-container" style="margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
    <div class="search-wrapper" style="position: relative; flex: 1; min-width: 300px;">
        <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;">🔎</span>
        <input type="text" id="buscarProducto" placeholder="Buscar producto por nombre, código o categoría..." 
               style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-primary); outline: none; transition: var(--transition);"
               autocomplete="off">
    </div>
    <div id="contadorResultados" class="text-muted" style="font-size: 0.9rem; font-weight: 500;">
        <?php if (!empty($productos)): ?>
            <?= count($productos); ?> productos en total
        <?php endif; ?>
    </div>
</div>

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

<div class="card">
    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Costo</th>
                    <th class="text-center">Stock</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($productos)): ?>
                    <?php foreach ($productos as $p): ?>
                        <tr>
                            <td><code class="text-muted"><?= esc($p['codigo']) ?: '---'; ?></code></td>
                            <td><strong><?= esc($p['nombre']); ?></strong></td>
                            <td><span class="badge-category"><?= esc($p['categoria_nombre']); ?></span></td>
                            <td><span class="text-accent" style="font-weight: 600;">S/ <?= number_format($p['precio_venta'], 2); ?></span></td>
                            <td><span class="text-muted">S/ <?= number_format($p['costo'], 2); ?></span></td>
                            <td class="text-center">
                                <?php if ($p['controla_stock']): ?>
                                    <?php 
                                        $stockBajo = $p['stock_actual'] <= $p['stock_minimo'];
                                    ?>
                                    <span class="stock-badge <?= $stockBajo ? 'stock-low' : 'stock-normal'; ?>" title="Mínimo: <?= $p['stock_minimo']; ?>">
                                        <?= $stockBajo ? '⚠️' : ''; ?> <?= $p['stock_actual']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 0.8rem;">No controla</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge-status <?= $p['estado'] === 'ACTIVO' ? 'status-activo' : 'status-inactivo'; ?>">
                                    <?= esc($p['estado']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <?php if (session()->get('rol') === 'ADMIN'): ?>
                                        <?php if ($p['controla_stock']): ?>
                                            <button type="button" class="btn-icon-only btn-stock btn-trigger-ajuste" 
                                                    title="Ajustar Stock"
                                                    data-id="<?= $p['id']; ?>"
                                                    data-nombre="<?= esc($p['nombre']); ?>"
                                                    data-stock="<?= $p['stock_actual']; ?>">
                                                📦
                                            </button>
                                        <?php endif; ?>

                                        <a href="<?= site_url('productos/editar/' . $p['id']); ?>" class="btn-icon-only btn-edit" title="Editar">
                                            ✏️
                                        </a>
                                        
                                        <form action="<?= site_url('productos/cambiar-estado/' . $p['id']); ?>" method="POST" style="display:inline;" class="form-dinamico-estado">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="accion" class="input-accion" value="cambiar">
                                            
                                            <?php if ($p['estado'] === 'INACTIVO'): ?>
                                                <button type="button" class="btn-icon-only btn-success-light btn-trigger-dinamico" 
                                                        title="Activar"
                                                        data-id="<?= $p['id']; ?>"
                                                        data-nombre="<?= esc($p['nombre']); ?>"
                                                        data-estado="INACTIVO"
                                                        data-relacion="0">
                                                    ✅
                                                </button>
                                            <?php elseif ($p['tiene_ventas']): ?>
                                                <button type="button" class="btn-icon-only btn-delete btn-trigger-dinamico" 
                                                        title="Desactivar"
                                                        data-id="<?= $p['id']; ?>"
                                                        data-nombre="<?= esc($p['nombre']); ?>"
                                                        data-estado="ACTIVO"
                                                        data-relacion="1">
                                                    🚫
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn-icon-only btn-delete btn-trigger-dinamico" 
                                                        title="Eliminar"
                                                        data-id="<?= $p['id']; ?>"
                                                        data-nombre="<?= esc($p['nombre']); ?>"
                                                        data-estado="ACTIVO"
                                                        data-relacion="0">
                                                    🗑️
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 0.8rem;">Solo lectura</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="empty-state">
                                <span class="empty-icon">🍺</span>
                                <p>No existen productos registrados.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL DINÁMICO DE ESTADO / ELIMINACIÓN -->
<div class="pos-modal-overlay" id="modalConfirmarEstado">
    <div class="pos-modal-box" style="max-width: 460px;">
        <div id="modalEstadoIcon" class="pos-modal-icon">⚠️</div>
        <h3 class="pos-modal-title" id="modalEstadoTitle">¿Confirmar acción?</h3>
        
        <div id="modalEstadoContent" style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 10px; padding: 1rem; margin: 1rem 0; text-align: center; color: var(--text-primary);">
            <span id="modalMensajePrincipal"></span> <strong id="modalNombreItem"></strong>?
            <div id="modalWarningSecundario" style="margin-top: 0.5rem; color: var(--danger); font-size: 0.85rem; font-weight: 600; display: none;"></div>
        </div>

        <div class="pos-modal-actions">
            <button type="button" class="pos-modal-btn pos-modal-btn-cancel" id="btnCancelEstado">Cancelar</button>
            <button type="button" class="pos-modal-btn pos-modal-btn-confirm" id="btnConfirmEstado">
                Confirmar
            </button>
        </div>
    </div>
</div>

<!-- MODAL DE AJUSTE DE STOCK -->
<div class="pos-modal-overlay" id="modalAjustarStock">
    <div class="pos-modal-box" style="max-width: 500px;">
        <div class="pos-modal-icon" style="background: rgba(255, 215, 0, 0.15); color: var(--accent);">📦</div>
        <h3 class="pos-modal-title">Ajustar Inventario</h3>
        
        <form action="<?= site_url('productos/ajustar-stock'); ?>" method="POST" id="formAjustarStock">
            <?= csrf_field(); ?>
            <input type="hidden" name="producto_id" id="ajusteProductoId">
            
            <div style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 10px; padding: 1rem; margin: 1rem 0; text-align: left;">
                <div style="margin-bottom: 0.5rem;">
                    <label style="font-size: 0.85rem; color: var(--text-muted);">Producto:</label>
                    <div id="ajusteNombreProducto" style="font-weight: 700; color: var(--text-primary);"></div>
                </div>
                <div style="margin-bottom: 0.5rem;">
                    <label style="font-size: 0.85rem; color: var(--text-muted);">Stock Actual:</label>
                    <div id="ajusteStockActual" style="font-weight: 700; color: var(--accent);"></div>
                </div>
            </div>

            <div style="text-align: left; margin-bottom: 1rem;">
                <label class="form-label">Nuevo Stock</label>
                <input type="number" name="nuevo_stock" id="nuevoStockInput" required class="form-input-custom" placeholder="Ingrese el nuevo stock total">
            </div>

            <div style="text-align: left; margin-bottom: 1rem;">
                <label class="form-label">Motivo</label>
                <select name="motivo" id="motivoAjuste" required class="form-input-custom">
                    <option value="">-- Seleccionar Motivo --</option>
                    <option value="Corrección de stock">Corrección de stock</option>
                    <option value="Merma">Merma</option>
                </select>
            </div>

            <div style="text-align: left; margin-bottom: 1.5rem;">
                <label class="form-label">Observación</label>
                <textarea name="observacion" id="observacionAjuste" required class="form-input-custom" style="min-height: 80px;" placeholder="Detalle el motivo del ajuste..."></textarea>
            </div>

            <div class="pos-modal-actions">
                <button type="button" class="pos-modal-btn pos-modal-btn-cancel" id="btnCancelAjuste">Cancelar</button>
                <button type="submit" class="pos-modal-btn pos-modal-btn-confirm" id="btnConfirmAjuste">
                    Guardar Ajuste
                </button>
            </div>
        </form>
    </div>
</div>


<style>
    .table-custom {
        width: 100%;
        border-collapse: collapse;
    }
    .table-custom th {
        text-align: left;
        padding: 1rem;
        background: rgba(0,0,0,0.05);
        color: var(--text-secondary);
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid var(--border-color);
    }
    .table-custom td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }
    .btn-group {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
    }
    .btn-icon-only {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background: var(--bg-body);
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
        font-size: 0.9rem;
    }
    .btn-edit:hover {
        background: var(--accent-glow);
        border-color: var(--accent);
    }
    .btn-delete:hover {
        background: var(--danger-bg);
        border-color: var(--danger);
    }
    .btn-success-light:hover {
        background: var(--success-bg);
        border-color: var(--success);
    }
    .empty-state {
        padding: 2rem;
        color: var(--text-muted);
    }
    .empty-icon {
        font-size: 2rem;
        display: block;
        margin-bottom: 0.5rem;
    }
    .badge-category {
        padding: 0.25rem 0.6rem;
        background: var(--bg-body);
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 500;
        color: var(--text-secondary);
    }
    .stock-badge {
        padding: 0.35rem 0.75rem;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.9rem;
        display: inline-block;
        min-width: 50px;
    }
    .stock-normal {
        background: var(--success-bg);
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.3);
    }
    .stock-low {
        background: var(--danger-bg);
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.3);
        animation: pulse-danger 2s infinite;
    }
    .btn-stock:hover {
        background: rgba(var(--accent-rgb, 255, 215, 0), 0.15);
        border-color: var(--accent);
    }
    .form-label {
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--text-secondary);
        display: block;
        margin-bottom: 0.5rem;
    }
    .form-input-custom {
        width: 100%;
        background: var(--bg-body);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 0.75rem 1rem;
        color: var(--text-primary);
        font-family: inherit;
        font-size: 1rem;
        transition: var(--transition);
        outline: none;
    }
    .form-input-custom:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px var(--accent-glow);
    }
    @keyframes pulse-danger {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    .text-accent { color: var(--accent); }
    .btn-primary {
        background: var(--accent);
        color: #000;
        padding: 0.6rem 1.2rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: var(--transition);
        border: none;
    }
    .btn-primary:hover {
        background: var(--accent-hover);
        transform: translateY(-2px);
        box-shadow: var(--accent-glow);
    }
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
    #buscarProducto:focus {
        border-color: var(--accent) !important;
        box-shadow: 0 0 0 3px var(--accent-glow);
    }
</style>

<?= $this->section('scripts'); ?>
<script>
$(document).ready(function() {
    const $searchInput = $('#buscarProducto');
    const $tableRows = $('.table-custom tbody tr:not(.no-results-row)');
    const $tbody = $('.table-custom tbody');
    const $contador = $('#contadorResultados');
    const totalRegistros = <?= count($productos ?? []); ?>;

    $searchInput.on('input', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        let visibleCount = 0;

        $('.no-results-row').remove();

        if (searchTerm === "") {
            $tableRows.show();
            visibleCount = totalRegistros;
        } else {
            $tableRows.each(function() {
                const $row = $(this);
                const codigo = $row.find('td:nth-child(1)').text().toLowerCase();
                const nombre = $row.find('td:nth-child(2)').text().toLowerCase();
                const categoria = $row.find('td:nth-child(3)').text().toLowerCase();

                if (nombre.includes(searchTerm) || codigo.includes(searchTerm) || categoria.includes(searchTerm)) {
                    $row.show();
                    visibleCount++;
                } else {
                    $row.hide();
                }
            });
        }

        if (visibleCount === 0 && totalRegistros > 0) {
            $tbody.append(`
                <tr class="no-results-row">
                    <td colspan="8" class="text-center py-4">
                        <div class="empty-state">
                            <span class="empty-icon">🔍</span>
                            <p>No se encontraron productos que coincidan con "${escapeHtml(searchTerm)}"</p>
                        </div>
                    </td>
                </tr>
            `);
        }

        if (searchTerm === "") {
            $contador.text(`${totalRegistros} productos en total`);
        } else {
            $contador.text(`${visibleCount} de ${totalRegistros} productos encontrados`);
        }
    });

    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // --- Lógica del Modal Dinámico (Estado / Eliminación) ---
    const $modal = $('#modalConfirmarEstado');
    const $modalTitle = $('#modalEstadoTitle');
    const $modalIcon = $('#modalEstadoIcon');
    const $modalMsg = $('#modalMensajePrincipal');
    const $modalNombre = $('#modalNombreItem');
    const $modalWarning = $('#modalWarningSecundario');
    const $btnConfirm = $('#btnConfirmEstado');
    let $formActivo = null;

    $('.btn-trigger-dinamico').on('click', function() {
        const $btn = $(this);
        const id = $btn.data('id');
        const nombre = $btn.data('nombre');
        const estado = $btn.data('estado');
        const title = $btn.attr('title');

        $formActivo = $btn.closest('form');
        $modalNombre.text(nombre);
        $modalWarning.hide();
        $formActivo.find('.input-accion').val('cambiar');
        
        // Resetear botón de confirmación
        $btnConfirm.prop('disabled', false).css('opacity', '1');

        if (title === 'Activar') {
            $modalTitle.text('¿Activar Producto?');
            $modalIcon.text('✅').css({ 'background': 'rgba(16, 185, 129, 0.15)', 'color': '#10b981' });
            $modalMsg.text('¿Desea activar nuevamente el producto');
            $btnConfirm.text('Sí, Activar').css('background', 'var(--success)');
        } else if (title === 'Desactivar') {
            $modalTitle.text('¿Desactivar Producto?');
            $modalIcon.text('🚫').css({ 'background': 'rgba(239, 68, 68, 0.15)', 'color': '#ef4444' });
            $modalMsg.text('¿Desea desactivar el producto');
            $modalWarning.text('Este producto tiene ventas asociadas y no puede eliminarse definitivamente.').show().css('color', 'var(--text-muted)');
            $btnConfirm.text('Sí, Desactivar').css('background', '#dc2626');
        } else if (title === 'Eliminar') {
            $modalTitle.text('¿Eliminar Producto?');
            $modalIcon.text('🗑️').css({ 'background': 'rgba(239, 68, 68, 0.15)', 'color': '#ef4444' });
            $modalMsg.text('Este producto se eliminará de manera definitiva. ¿Desea eliminar el producto');
            $modalWarning.text('Esta acción no se puede deshacer.').show().css('color', 'var(--danger)');
            $btnConfirm.text('Eliminar definitivamente').css('background', '#dc2626');
            $formActivo.find('.input-accion').val('eliminar');
        }

        $modal.addClass('show');
    });

    $('#btnCancelEstado, .pos-modal-overlay').on('click', function(e) {
        if (e.target === this || $(this).attr('id') === 'btnCancelEstado') {
            $modal.removeClass('show');
            $formActivo = null;
        }
    });

    $btnConfirm.on('click', function() {
        if ($formActivo) {
            $(this).prop('disabled', true).text('Procesando...').css('opacity', '0.7');
            $formActivo.submit();
        }
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $modal.hasClass('show')) {
            $modal.removeClass('show');
            $formActivo = null;
        }
    });

    // --- Lógica del Modal de Ajuste de Stock ---
    const $modalAjuste = $('#modalAjustarStock');
    const $ajusteProductoId = $('#ajusteProductoId');
    const $ajusteNombreProducto = $('#ajusteNombreProducto');
    const $ajusteStockActual = $('#ajusteStockActual');
    
    $('.btn-trigger-ajuste').on('click', function() {
        const $btn = $(this);
        $ajusteProductoId.val($btn.data('id'));
        $ajusteNombreProducto.text($btn.data('nombre'));
        $ajusteStockActual.text($btn.data('stock'));
        $('#nuevoStockInput').val('');
        $('#motivoAjuste').val('');
        $('#observacionAjuste').val('');
        
        // Resetear botón de confirmación
        $('#btnConfirmAjuste').prop('disabled', false).text('Guardar Ajuste').css('opacity', '1');
        
        $modalAjuste.addClass('show');
    });

    $('#btnCancelAjuste, #modalAjustarStock').on('click', function(e) {
        if (e.target === this || $(this).attr('id') === 'btnCancelAjuste') {
            $modalAjuste.removeClass('show');
        }
    });

    $('#formAjustarStock').on('submit', function() {
        const $btn = $('#btnConfirmAjuste');
        $btn.prop('disabled', true).text('Procesando...').css('opacity', '0.7');
    });

    // --- Lógica del Modal de Eliminación ---
    const $modalEliminar = $('#modalEliminar');
    const $modalNombreEliminar = $('#modalNombreEliminar');
    const $modalEliminarWarning = $('#modalEliminarWarning');
    const $modalDesactivarWarning = $('#modalDesactivarWarning');
    const $modalEliminarTitle = $('#modalEliminarTitle');
    const $modalEliminarIcon = $('#modalEliminarIcon');
    const $btnConfirmEliminar = $('#btnConfirmEliminar');
    let $formEliminarActivo = null;

    $('.btn-trigger-eliminar').on('click', function() {
        const $btn = $(this);
        const nombre = $btn.data('nombre');
        const tieneVentas = $btn.data('ventas') == '1';
        $formEliminarActivo = $btn.closest('form');

        $modalNombreEliminar.text(nombre);
        
        // Resetear botón de confirmación
        $btnConfirmEliminar.prop('disabled', false).css('opacity', '1');
        
        if (tieneVentas) {
            $modalEliminarTitle.text('¿Desactivar Producto?');
            $modalEliminarIcon.text('🚫').css({
                'background': 'rgba(239, 68, 68, 0.15)',
                'color': '#ef4444'
            });
            $modalEliminarWarning.hide();
            $modalDesactivarWarning.show();
            $btnConfirmEliminar.text('Sí, Desactivar').css('background', '#dc2626');
            // Cambiar la acción del formulario a desactivar
            $formEliminarActivo.attr('action', '<?= site_url('productos/cambiar-estado/'); ?>' + $btn.data('id'));
        } else {
            $modalEliminarTitle.text('¿Eliminar Producto?');
            $modalEliminarIcon.text('🗑️').css({
                'background': 'rgba(239, 68, 68, 0.15)',
                'color': '#ef4444'
            });
            $modalEliminarWarning.show();
            $modalDesactivarWarning.hide();
            $btnConfirmEliminar.text('Sí, Eliminar').css('background', '#dc2626');
        }

        $modalEliminar.addClass('show');
    });

    $('#btnCancelEliminar, #modalEliminar').on('click', function(e) {
        if (e.target === this || $(this).attr('id') === 'btnCancelEliminar') {
            $modalEliminar.removeClass('show');
            $formEliminarActivo = null;
        }
    });

    $btnConfirmEliminar.on('click', function() {
        if ($formEliminarActivo) {
            $(this).prop('disabled', true).text('Procesando...').css('opacity', '0.7');
            $formEliminarActivo.submit();
        }
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('.pos-modal-overlay').removeClass('show');
        }
    });
});
</script>
<?= $this->endSection(); ?>

<?= $this->endSection(); ?>
