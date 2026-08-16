<?= $this->extend('layout/app_layout'); ?>

<?= $this->section('content'); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">👤 Gestión de Usuarios</h1>
        <p class="page-subtitle">Administra los accesos y roles del personal del bar</p>
    </div>
    <div class="page-header-actions">
        <a href="<?= site_url('usuarios/crear'); ?>" class="btn-primary">
            <span class="btn-icon">+</span> Nuevo Usuario
        </a>
    </div>
</div>

<div class="toolbar-container" style="margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
    <div class="search-wrapper" style="position: relative; flex: 1; min-width: 300px;">
        <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;">🔎</span>
        <input type="text" id="buscarUsuario" placeholder="Buscar por nombre, usuario, rol o estado..." 
               style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-primary); outline: none; transition: var(--transition);"
               autocomplete="off">
    </div>
    <div id="contadorResultados" class="text-muted" style="font-size: 0.9rem; font-weight: 500;">
        <?php if (!empty($usuarios)): ?>
            <?= count($usuarios); ?> usuarios en total
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
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Fecha Creación</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($usuarios)): ?>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td><span class="text-muted">#</span><?= $u['id']; ?></td>
                            <td><strong><?= esc($u['nombre']); ?></strong></td>
                            <td><code><?= esc($u['usuario']); ?></code></td>
                            <td>
                                <span class="badge-status <?= $u['rol'] === 'ADMIN' ? 'status-activo' : 'status-cajero'; ?>">
                                    <?= esc($u['rol']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-status <?= $u['estado'] === 'ACTIVO' ? 'status-activo' : 'status-inactivo'; ?>">
                                    <?= esc($u['estado']); ?>
                                </span>
                            </td>
                            <td><span class="text-muted" style="font-size: 0.85rem;"><?= date('d/m/Y H:i', strtotime($u['fecha_creacion'])); ?></span></td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="<?= site_url('usuarios/editar/' . $u['id']); ?>" class="btn-icon-only btn-edit" title="Editar">
                                        ✏️
                                    </a>
                                    
                                    <a href="<?= site_url('usuarios/cambiar-password/' . $u['id']); ?>" class="btn-icon-only btn-success-light" title="Cambiar Contraseña">
                                        🔑
                                    </a>

                                    <?php if ($u['id'] != session()->get('usuario_id')): ?>
                                        <form action="<?= site_url('usuarios/cambiar-estado/' . $u['id']); ?>" method="POST" style="display:inline;" class="form-dinamico-estado">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="accion" class="input-accion" value="cambiar">
                                            
                                            <?php if ($u['estado'] === 'INACTIVO'): ?>
                                                <button type="button" class="btn-icon-only btn-success-light btn-trigger-dinamico" 
                                                        title="Activar"
                                                        data-id="<?= $u['id']; ?>"
                                                        data-nombre="<?= esc($u['nombre']); ?>">
                                                    ✅
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn-icon-only btn-delete btn-trigger-dinamico" 
                                                        title="Desactivar"
                                                        data-id="<?= $u['id']; ?>"
                                                        data-nombre="<?= esc($u['nombre']); ?>">
                                                    🚫
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <div class="empty-state">
                                <span class="empty-icon">👤</span>
                                <p>No existen usuarios registrados.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL DINÁMICO DE ESTADO -->
<div class="pos-modal-overlay" id="modalConfirmarEstado">
    <div class="pos-modal-box" style="max-width: 460px;">
        <div id="modalEstadoIcon" class="pos-modal-icon">⚠️</div>
        <h3 class="pos-modal-title" id="modalEstadoTitle">¿Confirmar acción?</h3>
        
        <div id="modalEstadoContent" style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 10px; padding: 1rem; margin: 1rem 0; text-align: center; color: var(--text-primary);">
            <span id="modalMensajePrincipal"></span> <strong id="modalNombreItem"></strong>?
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
    .page-subtitle {
        color: var(--text-muted);
        font-size: 0.9rem;
        margin-top: 0.25rem;
    }
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
    #buscarUsuario:focus {
        border-color: var(--accent) !important;
        box-shadow: 0 0 0 3px var(--accent-glow);
    }
    .status-cajero {
        background: rgba(59, 130, 246, 0.15);
        color: #3b82f6;
    }
</style>

<?= $this->section('scripts'); ?>
<script>
$(document).ready(function() {
    const $searchInput = $('#buscarUsuario');
    const $tableRows = $('.table-custom tbody tr:not(.no-results-row)');
    const $tbody = $('.table-custom tbody');
    const $contador = $('#contadorResultados');
    const totalRegistros = <?= count($usuarios ?? []); ?>;

    $searchInput.on('input', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        let visibleCount = 0;

        // Eliminar fila de "no resultados" si existe
        $('.no-results-row').remove();

        if (searchTerm === "") {
            $tableRows.show();
            visibleCount = totalRegistros;
        } else {
            $tableRows.each(function() {
                const $row = $(this);
                const nombre = $row.find('td:nth-child(2)').text().toLowerCase();
                const usuario = $row.find('td:nth-child(3)').text().toLowerCase();
                const rol = $row.find('td:nth-child(4)').text().toLowerCase();
                const estado = $row.find('td:nth-child(5)').text().toLowerCase();

                if (nombre.includes(searchTerm) || usuario.includes(searchTerm) || rol.includes(searchTerm) || estado.includes(searchTerm)) {
                    $row.show();
                    visibleCount++;
                } else {
                    $row.hide();
                }
            });
        }

        // Mostrar mensaje si no hay resultados
        if (visibleCount === 0 && totalRegistros > 0) {
            $tbody.append(`
                <tr class="no-results-row">
                    <td colspan="7" class="text-center py-4">
                        <div class="empty-state">
                            <span class="empty-icon">🔍</span>
                            <p>No se encontraron usuarios que coincidan con "${escapeHtml(searchTerm)}"</p>
                        </div>
                    </td>
                </tr>
            `);
        }

        // Actualizar contador
        if (searchTerm === "") {
            $contador.text(`${totalRegistros} usuarios en total`);
        } else {
            $contador.text(`${visibleCount} de ${totalRegistros} usuarios encontrados`);
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

    // --- Lógica del Modal Dinámico ---
    const $modal = $('#modalConfirmarEstado');
    const $modalTitle = $('#modalEstadoTitle');
    const $modalIcon = $('#modalEstadoIcon');
    const $modalMsg = $('#modalMensajePrincipal');
    const $modalNombre = $('#modalNombreItem');
    const $btnConfirm = $('#btnConfirmEstado');
    let $formActivo = null;

    $('.btn-trigger-dinamico').on('click', function() {
        const $btn = $(this);
        const id = $btn.data('id');
        const nombre = $btn.data('nombre');
        const title = $btn.attr('title');

        $formActivo = $btn.closest('form');
        $modalNombre.text(nombre);

        if (title === 'Activar') {
            $modalTitle.text('¿Activar Usuario?');
            $modalIcon.text('✅').css({ 'background': 'rgba(16, 185, 129, 0.15)', 'color': '#10b981' });
            $modalMsg.text('¿Desea activar nuevamente al usuario');
            $btnConfirm.text('Sí, Activar').css('background', 'var(--success)');
        } else if (title === 'Desactivar') {
            $modalTitle.text('¿Desactivar Usuario?');
            $modalIcon.text('🚫').css({ 'background': 'rgba(239, 68, 68, 0.15)', 'color': '#ef4444' });
            $modalMsg.text('¿Desea desactivar al usuario');
            $btnConfirm.text('Sí, Desactivar').css('background', '#dc2626');
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
        if ($formActivo) $formActivo.submit();
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $modal.hasClass('show')) {
            $modal.removeClass('show');
            $formActivo = null;
        }
    });
});
</script>
<?= $this->endSection(); ?>

<?= $this->endSection(); ?>
