<?= $this->extend('layout/app_layout'); ?>
<?= $this->section('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title">💳 Fiados</h1>
        <p class="page-subtitle">Gestión de crédito y deudas de clientes</p>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="fiados-kpis">
    <a href="<?= site_url('fiados'); ?>" class="fkpi-card fkpi-total <?= empty($filter) ? 'active' : ''; ?>" style="text-decoration: none;">
        <div class="fkpi-icon">💰</div>
        <div class="fkpi-info">
            <span class="fkpi-label">Deuda Total</span>
            <span class="fkpi-value">S/ <?= number_format($total_deuda, 2); ?></span>
        </div>
    </a>
    <a href="<?= site_url('fiados?filter=CON_DEUDA'); ?>" class="fkpi-card fkpi-con-deuda <?= $filter === 'CON_DEUDA' ? 'active' : ''; ?>" style="text-decoration: none;">
        <div class="fkpi-icon">🔴</div>
        <div class="fkpi-info">
            <span class="fkpi-label">Con Deuda</span>
            <span class="fkpi-value"><?= $con_deuda; ?> clientes</span>
        </div>
    </a>
    <a href="<?= site_url('fiados?filter=ALTA'); ?>" class="fkpi-card fkpi-alta <?= $filter === 'ALTA' ? 'active' : ''; ?>" style="text-decoration: none;">
        <div class="fkpi-icon">⚠️</div>
        <div class="fkpi-info">
            <span class="fkpi-label">Deuda Alta</span>
            <span class="fkpi-value"><?= $alta_deuda; ?> clientes</span>
        </div>
    </a>
    <a href="<?= site_url('fiados?filter=SIN_DEUDA'); ?>" class="fkpi-card fkpi-sin-deuda <?= $filter === 'SIN_DEUDA' ? 'active' : ''; ?>" style="text-decoration: none;">
        <div class="fkpi-icon">✅</div>
        <div class="fkpi-info">
            <span class="fkpi-label">Al Día</span>
            <span class="fkpi-value"><?= $sin_deuda; ?> clientes</span>
        </div>
    </a>
</div>

<!-- Search and Filters Bar -->
<div class="fiados-toolbar">
    <form id="formSearchFiados" method="GET" action="<?= site_url('fiados'); ?>" class="fiados-search-form">
        <?php if (!empty($filter)): ?>
            <input type="hidden" name="filter" value="<?= esc($filter); ?>">
        <?php endif; ?>
        <div class="fsearch-wrapper">
            <span class="fsearch-icon">🔍</span>
            <input
                type="text"
                id="searchInput"
                name="search"
                class="fsearch-input"
                placeholder="Buscar por nombre o teléfono..."
                value="<?= esc($search); ?>"
                autocomplete="off"
            >
            <?php if (!empty($search)): ?>
                <a href="<?= site_url('fiados'); ?>" class="fsearch-clear" title="Limpiar búsqueda">✕</a>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn-fiado-search">Buscar</button>
    </form>

    <div class="fiados-counter">
        <?= count($clientes); ?> cliente<?= count($clientes) !== 1 ? 's' : ''; ?> encontrado<?= count($clientes) !== 1 ? 's' : ''; ?>
        <?php if (!empty($search)): ?>
            <span class="search-badge">buscando: <strong><?= esc($search); ?></strong></span>
        <?php endif; ?>
        <?php if (!empty($filter)): ?>
            <span class="search-badge" style="background: var(--accent-glow); border-color: var(--accent);">filtro: 
                <strong>
                    <?php 
                        if ($filter === 'CON_DEUDA') echo 'Con Deuda';
                        elseif ($filter === 'ALTA') echo 'Deuda Alta';
                        elseif ($filter === 'SIN_DEUDA') echo 'Al Día';
                        else echo esc($filter);
                    ?>
                </strong>
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- Client Table -->
<?php if (empty($clientes)): ?>
    <div class="fiados-empty">
        <div class="empty-icon">💳</div>
        <h3>No se encontraron clientes</h3>
        <p><?= !empty($search) ? 'No hay resultados para "' . esc($search) . '".' : 'Aún no existen clientes registrados en el sistema.'; ?></p>
        <?php if (!empty($search)): ?>
            <a href="<?= site_url('fiados'); ?>" class="btn-fiado-secondary">Ver todos los clientes</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="fiados-table-wrap">
        <table class="fiados-table" id="tablaFiados">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Teléfono</th>
                    <th class="text-right">Total Fiado</th>
                    <th class="text-right">Saldo Pendiente</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clientes as $c): ?>
                    <tr class="fiado-row <?= $c['estado_deuda'] === 'ALTA' ? 'row-alta' : ($c['estado_deuda'] === 'SIN_DEUDA' ? 'row-saldo' : ''); ?>">
                        <td>
                            <div class="cliente-info">
                                <div class="cliente-avatar">
                                    <?= mb_strtoupper(mb_substr($c['cliente_nombre'], 0, 1)); ?>
                                </div>
                                <div>
                                    <span class="cliente-nombre"><?= esc($c['cliente_nombre']); ?></span>
                                    <?php if (!empty($c['limite_credito']) && $c['limite_credito'] > 0): ?>
                                        <span class="cliente-limite">límite: S/ <?= number_format($c['limite_credito'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="cliente-telefono"><?= !empty($c['cliente_telefono']) ? esc($c['cliente_telefono']) : '—'; ?></span>
                        </td>
                        <td class="text-right font-mono">
                            S/ <?= number_format($c['total_fiado'], 2); ?>
                        </td>
                        <td class="text-right font-mono">
                            <?php if ($c['saldo_pendiente'] > 0): ?>
                                <span class="saldo-pendiente <?= $c['estado_deuda'] === 'ALTA' ? 'saldo-alta' : 'saldo-regular'; ?>">
                                    S/ <?= number_format($c['saldo_pendiente'], 2); ?>
                                    <?= $c['estado_deuda'] === 'ALTA' ? ' 🔴' : ''; ?>
                                </span>
                            <?php else: ?>
                                <span class="saldo-cero">S/ 0.00</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($c['estado_deuda'] === 'SIN_DEUDA'): ?>
                                <span class="badge-estado estado-ok">Al día</span>
                            <?php elseif ($c['estado_deuda'] === 'ALTA'): ?>
                                <span class="badge-estado estado-alta">Deuda alta</span>
                            <?php else: ?>
                                <span class="badge-estado estado-regular">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a href="<?= site_url('fiados/cliente/' . $c['cliente_id']); ?>" class="btn-ver-cliente" title="Ver historial">
                                Ver detalle →
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?= $this->endSection(); ?>

<?= $this->section('scripts'); ?>
<style>
    .fkpi-card {
        transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
        border: 2px solid transparent;
        cursor: pointer;
    }
    .fkpi-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow);
    }
    .fkpi-card.active {
        border-color: var(--accent);
        background-color: var(--bg-card-hover);
        box-shadow: var(--accent-glow);
    }
</style>
<script src="<?= base_url('js/fiados.js'); ?>"></script>
<?= $this->endSection(); ?>
