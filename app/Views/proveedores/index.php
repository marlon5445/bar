<?= $this->extend('layout/app_layout'); ?>

<?= $this->section('content'); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">🚚 Gestión de Proveedores</h1>
        <p class="page-subtitle">Administra los datos básicos de tus proveedores</p>
    </div>
    <div class="page-header-actions">
        <?php if (session()->get('rol') === 'ADMIN'): ?>
            <button type="button" class="btn-primary" id="btnNuevoProveedor">
                <span class="btn-icon">+</span> Nuevo Proveedor
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="toolbar-container" style="margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
    <div class="search-wrapper" style="position: relative; flex: 1; min-width: 300px;">
        <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;">🔎</span>
        <input type="text" id="buscarProveedor" placeholder="Buscar proveedor por nombre, RUC o teléfono..." 
               style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-primary); outline: none; transition: var(--transition);"
               autocomplete="off">
    </div>
    <div id="contadorResultados" class="text-muted" style="font-size: 0.9rem; font-weight: 500;">
        <?php if (!empty($proveedores)): ?>
            <?= count($proveedores); ?> proveedores en total
        <?php endif; ?>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alerta-success" style="padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
        <span>✅</span>
        <div><?= session()->getFlashdata('success'); ?></div>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('info')): ?>
    <div class="alerta-info" style="padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; background: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2);">
        <span>ℹ️</span>
        <div><?= session()->getFlashdata('info'); ?></div>
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
                    <th>RUC</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($proveedores)): ?>
                    <?php foreach ($proveedores as $p): ?>
                        <tr>
                            <td><strong><?= esc($p['nombre']); ?></strong></td>
                            <td><code class="text-muted"><?= esc($p['ruc']) ?: '---'; ?></code></td>
                            <td><?= esc($p['telefono']) ?: '---'; ?></td>
                            <td><span style="font-size: 0.85rem;"><?= esc($p['direccion']) ?: '---'; ?></span></td>
                            <td class="text-center">
                                <span class="badge-status <?= $p['estado'] === 'ACTIVO' ? 'status-activo' : 'status-inactivo'; ?>">
                                    <?= esc($p['estado']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <?php if (session()->get('rol') === 'ADMIN'): ?>
                                        <button type="button" class="btn-icon-only btn-edit btn-trigger-editar" 
                                                title="Editar"
                                                data-id="<?= $p['id']; ?>"
                                                data-nombre="<?= esc($p['nombre']); ?>"
                                                data-ruc="<?= esc($p['ruc']); ?>"
                                                data-telefono="<?= esc($p['telefono']); ?>"
                                                data-direccion="<?= esc($p['direccion']); ?>">
                                            ✏️
                                        </button>
                                        
                                        <form action="<?= site_url('proveedores/cambiar-estado/' . $p['id']); ?>" method="POST" style="display:inline;" class="form-dinamico-estado">
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
                                            <?php else: ?>
                                                <?php if ($p['tiene_compras']): ?>
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
                        <td colspan="6" class="text-center py-4">
                            <div class="empty-state">
                                <span class="empty-icon">🚚</span>
                                <p>No existen proveedores registrados.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL FORMULARIO (NUEVO/EDITAR) -->
<div class="pos-modal-overlay" id="modalProveedor">
    <div class="pos-modal-box" style="max-width: 500px; text-align: left;">
        <h3 class="pos-modal-title" id="modalTitle">Nuevo Proveedor</h3>
        
        <form action="<?= site_url('proveedores/guardar'); ?>" method="POST" id="formProveedor">
            <?= csrf_field(); ?>
            <div style="margin-top: 1.5rem;">
                <div style="margin-bottom: 1rem;">
                    <label class="form-label">Nombre del Proveedor *</label>
                    <input type="text" name="nombre" id="inputNombre" required class="form-input-custom" placeholder="Ej. Distribuidora Lima S.A.C.">
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label class="form-label">RUC</label>
                    <input type="text" name="ruc" id="inputRuc" class="form-input-custom" placeholder="Ingrese el RUC">
                </div>

                <div style="margin-bottom: 1rem;">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" id="inputTelefono" class="form-input-custom" placeholder="Ingrese el teléfono">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="direccion" id="inputDireccion" class="form-input-custom" placeholder="Ingrese la dirección fiscal">
                </div>
            </div>

            <div class="pos-modal-actions" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <button type="button" class="pos-modal-btn pos-modal-btn-cancel" id="btnCancelModal">Cancelar</button>
                <button type="submit" class="pos-modal-btn pos-modal-btn-confirm" id="btnGuardar" style="background: var(--accent); color: #000;">
                    Guardar Proveedor
                </button>
            </div>
        </form>
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

        <div class="pos-modal-actions" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
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
    .btn-icon-only { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-body); cursor: pointer; transition: var(--transition); font-size: 0.9rem; }
    .btn-edit:hover { background: var(--accent-glow); border-color: var(--accent); }
    .btn-delete:hover { background: var(--danger-bg); border-color: var(--danger); }
    .btn-success-light:hover { background: var(--success-bg); border-color: var(--success); }
    
    .form-label { font-weight: 600; font-size: 0.9rem; color: var(--text-secondary); display: block; margin-bottom: 0.5rem; }
    .form-input-custom { width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 10px; padding: 0.75rem 1rem; color: var(--text-primary); outline: none; transition: var(--transition); }
    .form-input-custom:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
    
    .btn-primary { background: var(--accent); color: #000; padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; transition: var(--transition); border: none; cursor: pointer; }
    .btn-primary:hover { background: var(--accent-hover); transform: translateY(-2px); box-shadow: var(--accent-glow); }
    .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; transform: none !important; box-shadow: none !important; }
    
    .pos-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 2000; opacity: 0; visibility: hidden; transition: var(--transition); }
    .pos-modal-overlay.show { opacity: 1; visibility: visible; }
    .pos-modal-box { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: 2rem; width: 90%; transform: translateY(20px); transition: var(--transition); }
    .pos-modal-overlay.show .pos-modal-box { transform: translateY(0); }
    
    .pos-modal-icon { width: 60px; height: 60px; background: var(--danger-bg); color: var(--danger); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 1rem; }
    .pos-modal-title { font-size: 1.25rem; font-weight: 700; color: var(--text-primary); }
    .pos-modal-actions { margin-top: 1.5rem; }
    .pos-modal-btn { padding: 0.75rem; border-radius: 10px; border: none; font-weight: 600; cursor: pointer; transition: var(--transition); }
    .pos-modal-btn-cancel { background: var(--bg-body); color: var(--text-primary); border: 1px solid var(--border-color); }
    .pos-modal-btn-confirm { background: var(--danger); color: #fff; }
</style>

<?= $this->section('scripts'); ?>
<script>
$(document).ready(function() {
    // Filtro dinámico
    const $searchInput = $('#buscarProveedor');
    const $tableRows = $('.table-custom tbody tr:not(.no-results-row)');
    const totalRegistros = <?= count($proveedores ?? []); ?>;

    $searchInput.on('input', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        let visibleCount = 0;
        $('.no-results-row').remove();

        $tableRows.each(function() {
            const $row = $(this);
            const nombre = $row.find('td:nth-child(1)').text().toLowerCase();
            const ruc = $row.find('td:nth-child(2)').text().toLowerCase();
            const tel = $row.find('td:nth-child(3)').text().toLowerCase();

            if (nombre.includes(searchTerm) || ruc.includes(searchTerm) || tel.includes(searchTerm)) {
                $row.show();
                visibleCount++;
            } else {
                $row.hide();
            }
        });

        if (visibleCount === 0 && totalRegistros > 0) {
            $('.table-custom tbody').append('<tr class="no-results-row"><td colspan="6" class="text-center py-4">No se encontraron resultados</td></tr>');
        }
        $('#contadorResultados').text(searchTerm === "" ? `${totalRegistros} proveedores en total` : `${visibleCount} de ${totalRegistros} encontrados`);
    });

    // Modal Nuevo/Editar
    const $modal = $('#modalProveedor');
    const $form = $('#formProveedor');
    const $modalTitle = $('#modalTitle');

    $('#btnNuevoProveedor').on('click', function() {
        $modalTitle.text('Nuevo Proveedor');
        $form.attr('action', '<?= site_url('proveedores/guardar'); ?>');
        $form[0].reset();
        $modal.addClass('show');
    });

    $('.btn-trigger-editar').on('click', function() {
        const data = $(this).data();
        $modalTitle.text('Editar Proveedor');
        $form.attr('action', '<?= site_url('proveedores/actualizar/'); ?>' + data.id);
        $('#inputNombre').val(data.nombre);
        $('#inputRuc').val(data.ruc);
        $('#inputTelefono').val(data.telefono);
        $('#inputDireccion').val(data.direccion);
        $modal.addClass('show');
    });

    $('#btnCancelModal, #modalProveedor').on('click', function(e) {
        if (e.target === this || $(this).attr('id') === 'btnCancelModal') {
            $modal.removeClass('show');
        }
    });

    // Prevenir doble click al guardar
    $form.on('submit', function() {
        const $btn = $('#btnGuardar');
        if ($btn.prop('disabled')) return false;
        $btn.prop('disabled', true).text('Procesando...').css('opacity', '0.7');
        return true;
    });

    // --- Lógica del Modal Dinámico (Estado / Eliminación) ---
    const $modalEstado = $('#modalConfirmarEstado');
    const $modalEstadoTitle = $('#modalEstadoTitle');
    const $modalEstadoIcon = $('#modalEstadoIcon');
    const $modalMensajePrincipal = $('#modalMensajePrincipal');
    const $modalNombreItem = $('#modalNombreItem');
    const $modalWarningSecundario = $('#modalWarningSecundario');
    const $btnConfirmEstado = $('#btnConfirmEstado');
    let $formActivo = null;

    $('.btn-trigger-dinamico').on('click', function() {
        const $btn = $(this);
        const id = $btn.data('id');
        const nombre = $btn.data('nombre');
        const title = $btn.attr('title');

        $formActivo = $btn.closest('form');
        $modalNombreItem.text(nombre);
        $modalWarningSecundario.hide();
        
        // Resetear botón de confirmación
        $btnConfirmEstado.prop('disabled', false).css('opacity', '1');

        if (title === 'Activar') {
            $modalEstadoTitle.text('¿Activar Proveedor?');
            $modalEstadoIcon.text('✅').css({ 'background': 'rgba(16, 185, 129, 0.15)', 'color': '#10b981' });
            $modalMensajePrincipal.text('¿Desea activar nuevamente al proveedor');
            $btnConfirmEstado.text('Sí, Activar').css('background', 'var(--success)');
            $formActivo.attr('action', '<?= site_url('proveedores/cambiar-estado/'); ?>' + id);
        } else if (title === 'Desactivar') {
            $modalEstadoTitle.text('¿Desactivar Proveedor?');
            $modalEstadoIcon.text('🚫').css({ 'background': 'rgba(239, 68, 68, 0.15)', 'color': '#ef4444' });
            $modalMensajePrincipal.text('¿Desea desactivar al proveedor');
            $modalWarningSecundario.text('Este proveedor tiene compras asociadas y no puede eliminarse definitivamente.').show().css('color', 'var(--text-muted)');
            $btnConfirmEstado.text('Sí, Desactivar').css('background', '#dc2626');
            $formActivo.attr('action', '<?= site_url('proveedores/cambiar-estado/'); ?>' + id);
        } else if (title === 'Eliminar') {
            $modalEstadoTitle.text('¿Eliminar Proveedor?');
            $modalEstadoIcon.text('🗑️').css({ 'background': 'rgba(239, 68, 68, 0.15)', 'color': '#ef4444' });
            $modalMensajePrincipal.text('Este proveedor se eliminará de manera definitiva. ¿Desea eliminar al proveedor');
            $modalWarningSecundario.text('Esta acción no se puede deshacer.').show().css('color', 'var(--danger)');
            $btnConfirmEstado.text('Eliminar definitivamente').css('background', '#dc2626');
            $formActivo.attr('action', '<?= site_url('proveedores/eliminar/'); ?>' + id);
        }

        $modalEstado.addClass('show');
    });

    $('#btnCancelEstado, #modalConfirmarEstado').on('click', function(e) {
        if (e.target === this || $(this).attr('id') === 'btnCancelEstado') {
            $modalEstado.removeClass('show');
            $formActivo = null;
        }
    });

    $btnConfirmEstado.on('click', function() {
        if ($formActivo) {
            $(this).prop('disabled', true).text('Procesando...').css('opacity', '0.7');
            $formActivo.submit();
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
