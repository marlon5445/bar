<?= $this->extend('layout/app_layout'); ?>

<?= $this->section('content'); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">🛒 Historial de Compras</h1>
        <p class="page-subtitle">Visualiza y gestiona las compras realizadas a proveedores</p>
    </div>
    <div class="page-header-actions">
        <?php if (session()->get('rol') === 'ADMIN'): ?>
            <a href="<?= site_url('compras/nuevo'); ?>" class="btn-primary">
                <span class="btn-icon">+</span> Nueva Compra
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="toolbar-container" style="margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
    <div class="search-wrapper" style="position: relative; flex: 1; min-width: 300px;">
        <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;">🔎</span>
        <input type="text" id="buscarCompra" placeholder="Buscar por proveedor o usuario..." 
               style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-primary); outline: none; transition: var(--transition);"
               autocomplete="off">
    </div>
    <div id="contadorResultados" class="text-muted" style="font-size: 0.9rem; font-weight: 500;">
        <?php if (!empty($compras)): ?>
            <?= count($compras); ?> compras registradas
        <?php endif; ?>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alerta-success" style="padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
        <span>✅</span>
        <div><?= session()->getFlashdata('success'); ?></div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Proveedor</th>
                    <th>Total</th>
                    <th>Usuario</th>
                    <th>Fecha</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($compras)): ?>
                    <?php foreach ($compras as $c): ?>
                        <tr>
                            <td><strong>#<?= $c['id']; ?></strong></td>
                            <td><?= esc($c['proveedor_nombre']); ?></td>
                            <td><span class="text-accent" style="font-weight: 600;">S/ <?= number_format($c['total'], 2); ?></span></td>
                            <td><span style="font-size: 0.85rem; color: var(--text-secondary);"><?= esc($c['usuario_nombre']); ?></span></td>
                            <td><?= date('d/m/Y H:i', strtotime($c['fecha'])); ?></td>
                            <td class="text-center">
                                <span class="badge-status <?= $c['estado'] === 'COMPLETADA' ? 'status-activo' : 'status-inactivo'; ?>">
                                    <?= esc($c['estado']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="<?= site_url('compras/detalle/' . $c['id']); ?>" class="btn-icon-only btn-edit" title="Ver Detalle">
                                        👁️
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <div class="empty-state">
                                <span class="empty-icon">🛒</span>
                                <p>No existen compras registradas.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .table-custom { width: 100%; border-collapse: collapse; }
    .table-custom th { text-align: left; padding: 1rem; background: rgba(0,0,0,0.05); color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-color); }
    .table-custom td { padding: 1rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
    .btn-group { display: flex; gap: 0.5rem; justify-content: center; }
    .btn-icon-only { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-body); cursor: pointer; transition: var(--transition); font-size: 0.9rem; text-decoration: none; }
    .btn-edit:hover { background: var(--accent-glow); border-color: var(--accent); }
    .text-accent { color: var(--accent); }
    .btn-primary { background: var(--accent); color: #000; padding: 0.6rem 1.2rem; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; transition: var(--transition); border: none; }
    .btn-primary:hover { background: var(--accent-hover); transform: translateY(-2px); box-shadow: var(--accent-glow); }
</style>

<?= $this->section('scripts'); ?>
<script>
$(document).ready(function() {
    const $searchInput = $('#buscarCompra');
    const $tableRows = $('.table-custom tbody tr:not(.no-results-row)');
    const totalRegistros = <?= count($compras ?? []); ?>;

    $searchInput.on('input', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        let visibleCount = 0;
        $('.no-results-row').remove();

        $tableRows.each(function() {
            const $row = $(this);
            const proveedor = $row.find('td:nth-child(2)').text().toLowerCase();
            const usuario = $row.find('td:nth-child(4)').text().toLowerCase();

            if (proveedor.includes(searchTerm) || usuario.includes(searchTerm)) {
                $row.show();
                visibleCount++;
            } else {
                $row.hide();
            }
        });

        if (visibleCount === 0 && totalRegistros > 0) {
            $('.table-custom tbody').append('<tr class="no-results-row"><td colspan="7" class="text-center py-4">No se encontraron resultados</td></tr>');
        }
        $('#contadorResultados').text(searchTerm === "" ? `${totalRegistros} compras registradas` : `${visibleCount} de ${totalRegistros} encontradas`);
    });
});
</script>
<?= $this->endSection(); ?>

<?= $this->endSection(); ?>
