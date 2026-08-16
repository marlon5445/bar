<?= $this->extend('layout/app_layout'); ?>

<?= $this->section('content'); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">🔥 Promociones</h1>
        <p class="page-subtitle">Gestiona ofertas y combos de productos</p>
    </div>
    <div class="page-header-actions">
        <a href="<?= site_url('promociones/nuevo'); ?>" class="btn-primary">
            <span class="btn-icon">+</span> Nueva promoción
        </a>
    </div>
</div>

<div class="toolbar-container" style="margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
    <div class="search-wrapper" style="position: relative; flex: 1; min-width: 300px;">
        <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;">🔎</span>
        <input type="text" id="buscarPromocion" placeholder="Buscar por nombre o descripción..." 
               style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-primary); outline: none; transition: var(--transition);"
               autocomplete="off">
    </div>
    <div id="contadorResultados" class="text-muted" style="font-size: 0.9rem; font-weight: 500;">
        <?php if (!empty($promociones)): ?>
            <?= count($promociones); ?> promociones en total
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
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Precio</th>
                    <th>Productos incluidos</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($promociones)): ?>
                    <?php foreach ($promociones as $p): ?>
                        <tr>
                            <td><strong><?= esc($p['nombre']); ?></strong></td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="<?= esc($p['descripcion']); ?>">
                                    <?= esc($p['descripcion']) ?: '<span class="text-muted">Sin descripción</span>'; ?>
                                </div>
                            </td>
                            <td><span class="text-accent" style="font-weight: 700;">S/ <?= number_format($p['precio'], 2); ?></span></td>
                            <td>
                                <div style="font-size: 0.85rem; line-height: 1.4;">
                                    <?= $p['productos_resumen']; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge-status <?= $p['estado'] === 'ACTIVO' ? 'status-activo' : 'status-inactivo'; ?>">
                                    <?= esc($p['estado']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="<?= site_url('promociones/editar/' . $p['id']); ?>" class="btn-icon-only btn-edit" title="Editar">
                                        ✏️
                                    </a>
                                    
                                    <form action="<?= site_url('promociones/cambiar-estado/' . $p['id']); ?>" method="POST" style="display:inline;" class="form-dinamico-estado">
                                        <?= csrf_field(); ?>
                                        <input type="hidden" name="accion" class="input-accion" value="cambiar">
                                        
                                        <?php if ($p['estado'] === 'INACTIVO'): ?>
                                            <button type="button" class="btn-icon-only btn-success-light btn-trigger-dinamico" 
                                                    title="Activar"
                                                    data-id="<?= $p['id']; ?>"
                                                    data-nombre="<?= esc($p['nombre']); ?>"
                                                    data-estado="INACTIVO">
                                                ✅
                                            </button>
                                        <?php elseif ($p['tiene_ventas']): ?>
                                            <button type="button" class="btn-icon-only btn-delete btn-trigger-dinamico" 
                                                    title="Desactivar"
                                                    data-id="<?= $p['id']; ?>"
                                                    data-nombre="<?= esc($p['nombre']); ?>"
                                                    data-estado="ACTIVO"
                                                    data-ventas="1">
                                                🚫
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn-icon-only btn-delete btn-trigger-dinamico" 
                                                    title="Eliminar"
                                                    data-id="<?= $p['id']; ?>"
                                                    data-nombre="<?= esc($p['nombre']); ?>"
                                                    data-estado="ACTIVO"
                                                    data-ventas="0">
                                                🗑️
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="empty-state">
                                <span class="empty-icon">🔥</span>
                                <p>No existen promociones registradas.</p>
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

<style>
    .table-custom { width: 100%; border-collapse: collapse; }
    .table-custom th { text-align: left; padding: 1rem; background: rgba(0,0,0,0.05); color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-color); }
    .table-custom td { padding: 1rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
    .btn-group { display: flex; gap: 0.5rem; justify-content: center; }
    .btn-icon-only { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-body); cursor: pointer; transition: var(--transition); text-decoration: none; font-size: 0.9rem; }
    .btn-edit:hover { background: var(--accent-glow); border-color: var(--accent); }
    .btn-delete:hover { background: var(--danger-bg); border-color: var(--danger); }
    .btn-success-light:hover { background: var(--success-bg); border-color: var(--success); }
    .empty-state { padding: 2rem; color: var(--text-muted); }
    .empty-icon { font-size: 2rem; display: block; margin-bottom: 0.5rem; }
    .btn-primary { background: var(--accent); color: #000; padding: 0.6rem 1.2rem; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; transition: var(--transition); border: none; }
    .btn-primary:hover { background: var(--accent-hover); transform: translateY(-2px); box-shadow: var(--accent-glow); }
    .pos-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 2000; opacity: 0; visibility: hidden; transition: var(--transition); }
    .pos-modal-overlay.show { opacity: 1; visibility: visible; }
    .pos-modal-box { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: 2rem; width: 90%; box-shadow: var(--shadow); transform: translateY(20px); transition: var(--transition); text-align: center; }
    .pos-modal-overlay.show .pos-modal-box { transform: translateY(0); }
    .pos-modal-icon { width: 60px; height: 60px; background: var(--danger-bg); color: var(--danger); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 1rem; }
    .pos-modal-title { font-family: 'Space Grotesk', sans-serif; font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-primary); }
    .pos-modal-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem; }
    .pos-modal-btn { padding: 0.75rem; border-radius: 10px; border: none; font-weight: 600; cursor: pointer; transition: var(--transition); }
    .pos-modal-btn-cancel { background: var(--bg-body); color: var(--text-primary); border: 1px solid var(--border-color); }
    .pos-modal-btn-confirm { background: var(--danger); color: #fff; }
</style>

<?= $this->section('scripts'); ?>
<script>
$(document).ready(function() {
    const $searchInput = $('#buscarPromocion');
    const $tableRows = $('.table-custom tbody tr:not(.no-results-row)');
    const $tbody = $('.table-custom tbody');
    const $contador = $('#contadorResultados');
    const totalRegistros = <?= count($promociones ?? []); ?>;

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
                const nombre = $row.find('td:nth-child(1)').text().toLowerCase();
                const descripcion = $row.find('td:nth-child(2)').text().toLowerCase();

                if (nombre.includes(searchTerm) || descripcion.includes(searchTerm)) {
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
                    <td colspan="6" class="text-center py-4">
                        <div class="empty-state">
                            <span class="empty-icon">🔍</span>
                            <p>No se encontraron promociones que coincidan con "${escapeHtml(searchTerm)}"</p>
                        </div>
                    </td>
                </tr>
            `);
        }
        $contador.text(searchTerm === "" ? `${totalRegistros} promociones en total` : `${visibleCount} de ${totalRegistros} encontradas`);
    });

    function escapeHtml(text) {
        return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

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
        const nombre = $btn.data('nombre');
        const title = $btn.attr('title');
        const ventas = $btn.data('ventas');

        $formActivo = $btn.closest('form');
        $modalNombre.text(nombre);
        $modalWarning.hide();
        $formActivo.find('.input-accion').val('cambiar');

        if (title === 'Activar') {
            $modalTitle.text('¿Activar Promoción?');
            $modalIcon.text('✅').css({ 'background': 'rgba(16, 185, 129, 0.15)', 'color': '#10b981' });
            $modalMsg.text('¿Desea activar nuevamente la promoción');
            $btnConfirm.text('Sí, Activar').css('background', 'var(--success)');
        } else if (title === 'Desactivar') {
            $modalTitle.text('¿Desactivar Promoción?');
            $modalIcon.text('🚫').css({ 'background': 'rgba(239, 68, 68, 0.15)', 'color': '#ef4444' });
            $modalMsg.text('¿Desea desactivar la promoción');
            $modalWarning.text('Esta promoción tiene ventas asociadas y no puede eliminarse definitivamente.').show().css('color', 'var(--text-muted)');
            $btnConfirm.text('Sí, Desactivar').css('background', '#dc2626');
        } else if (title === 'Eliminar') {
            $modalTitle.text('¿Eliminar Promoción?');
            $modalIcon.text('🗑️').css({ 'background': 'rgba(239, 68, 68, 0.15)', 'color': '#ef4444' });
            $modalMsg.text('Esta promoción se eliminará de manera definitiva. ¿Está seguro de eliminar la promoción');
            $modalWarning.text('Esta acción no se puede deshacer.').show().css('color', 'var(--danger)');
            $btnConfirm.text('Eliminar definitivamente').css('background', '#dc2626');
            $formActivo.find('.input-accion').val('eliminar');
        }
        $modal.addClass('show');
    });

    $('#btnCancelEstado, .pos-modal-overlay').on('click', function(e) {
        if (e.target === this || $(this).attr('id') === 'btnCancelEstado') {
            $modal.removeClass('show');
        }
    });

    $btnConfirm.on('click', function() {
        if ($formActivo) $formActivo.submit();
    });
});
</script>
<?= $this->endSection(); ?>

<?= $this->endSection(); ?>
