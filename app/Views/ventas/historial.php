<?= $this->extend('layout/app_layout'); ?>

<?= $this->section('content'); ?>

<!-- BARRA DE NAVEGACIÓN SUPERIOR DE VENTAS -->
<div class="ventas-nav-header">
    <a href="<?= site_url('ventas/nueva'); ?>" class="ventas-nav-tab">
        ➕ NUEVA VENTA
    </a>
    <a href="<?= site_url('ventas/historial'); ?>" class="ventas-nav-tab active">
        📋 HISTORIAL DE VENTAS
    </a>
</div>

<div class="historial-container">

    <!-- TARJETAS DE RESUMEN KPI -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon">🛒</div>
            <div class="kpi-info">
                <span class="kpi-label">VENTAS VÁLIDAS</span>
                <span class="kpi-value" id="kpiTotalVentas"><?= $resumen['total_ventas']; ?></span>
            </div>
        </div>

        <div class="kpi-card kpi-highlight">
            <div class="kpi-icon">💰</div>
            <div class="kpi-info">
                <span class="kpi-label">TOTAL VENDIDO</span>
                <span class="kpi-value" id="kpiTotalMonto">S/ <?= number_format($resumen['total_monto'], 2); ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon">💵</div>
            <div class="kpi-info">
                <span class="kpi-label">EFECTIVO</span>
                <span class="kpi-value" id="kpiEfectivo">S/ <?= number_format($resumen['total_efectivo'], 2); ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon">📲</div>
            <div class="kpi-info">
                <span class="kpi-label">YAPE</span>
                <span class="kpi-value" id="kpiYape">S/ <?= number_format($resumen['total_yape'], 2); ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon">📱</div>
            <div class="kpi-info">
                <span class="kpi-label">PLIN</span>
                <span class="kpi-value" id="kpiPlin">S/ <?= number_format($resumen['total_plin'], 2); ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon">📋</div>
            <div class="kpi-info">
                <span class="kpi-label">FIADO</span>
                <span class="kpi-value" id="kpiFiado">S/ <?= number_format($resumen['total_fiado'], 2); ?></span>
            </div>
        </div>
    </div>

    <!-- BARRA DE FILTROS DE BÚSQUEDA -->
    <div class="historial-filter-card">
        <form method="GET" action="<?= site_url('ventas/historial'); ?>" id="filterForm" class="filter-form">
            <div class="filter-group">
                <label for="filterFecha" class="filter-label">FECHA:</label>
                <input type="date" id="filterFecha" name="fecha" value="<?= esc($filtros['fecha']); ?>" class="filter-input">
            </div>

            <div class="filter-group">
                <label for="filterMesero" class="filter-label">MESERO / ATENCIÓN:</label>
                <select id="filterMesero" name="mesero_id" class="filter-select">
                    <option value="">-- Todos --</option>
                    <option value="barra" <?= $filtros['mesero_id'] === 'barra' ? 'selected' : ''; ?>>🏪 Venta en Barra</option>
                    <?php foreach ($meseros as $m): ?>
                        <option value="<?= $m['id']; ?>" <?= $filtros['mesero_id'] == $m['id'] ? 'selected' : ''; ?>>
                            👨‍🍳 <?= esc($m['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="filterPago" class="filter-label">MÉTODO DE PAGO:</label>
                <select id="filterPago" name="tipo_pago" class="filter-select">
                    <option value="">-- Todos --</option>
                    <option value="EFECTIVO" <?= $filtros['tipo_pago'] === 'EFECTIVO' ? 'selected' : ''; ?>>💵 EFECTIVO</option>
                    <option value="YAPE" <?= $filtros['tipo_pago'] === 'YAPE' ? 'selected' : ''; ?>>📲 YAPE</option>
                    <option value="PLIN" <?= $filtros['tipo_pago'] === 'PLIN' ? 'selected' : ''; ?>>📱 PLIN</option>
                    <option value="FIADO" <?= $filtros['tipo_pago'] === 'FIADO' ? 'selected' : ''; ?>>📋 FIADO</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="filterEstado" class="filter-label">ESTADO:</label>
                <select id="filterEstado" name="estado" class="filter-select">
                    <option value="">-- Todos --</option>
                    <option value="COMPLETADA" <?= $filtros['estado'] === 'COMPLETADA' ? 'selected' : ''; ?>>✅ COMPLETADA</option>
                    <option value="ANULADA" <?= $filtros['estado'] === 'ANULADA' ? 'selected' : ''; ?>>🚫 ANULADA</option>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn-filter-submit">🔍 Filtrar</button>
                <a href="<?= site_url('ventas/historial'); ?>" class="btn-filter-reset">🧹 Limpiar</a>
            </div>
        </form>
    </div>

    <!-- TABLA DE VENTAS REALIZADAS -->
    <div class="historial-table-card">
        <div class="table-responsive">
            <table class="historial-table">
                <thead>
                    <tr>
                        <th>N° VENTA</th>
                        <th>FECHA / HORA</th>
                        <th>CAJERO</th>
                        <th>ATENCIÓN</th>
                        <th>MÉTODO PAGO</th>
                        <th>TOTAL</th>
                        <th>ESTADO</th>
                        <th style="text-align: center;">ACCIÓN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ventas)): ?>
                        <tr>
                            <td colspan="8" class="table-empty-row">
                                <div class="empty-table-state">
                                    <span class="empty-icon">📂</span>
                                    <p>No se encontraron ventas para los filtros seleccionados.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ventas as $v): ?>
                            <?php 
                                $esAnulada = in_array(strtoupper($v['estado']), ['ANULADA', 'CANCELADA'], true);
                                $meseroTxt = !empty($v['mesero_nombre']) ? '👨‍🍳 ' . esc($v['mesero_nombre']) : '🏪 Venta en barra';
                            ?>
                            <tr class="<?= $esAnulada ? 'row-anulada' : ''; ?>">
                                <td class="col-id"><strong>#<?= $v['id']; ?></strong></td>
                                <td class="col-date"><?= date('d/m/Y h:i A', strtotime($v['fecha_venta'])); ?></td>
                                <td>👤 <?= esc($v['cajero_nombre'] ?? 'Sistema'); ?></td>
                                <td>
                                    <span class="badge-waiter <?= empty($v['mesero_nombre']) ? 'barra' : ''; ?>">
                                        <?= $meseroTxt; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-payment <?= strtolower($v['tipo_pago']); ?>">
                                        <?= esc($v['tipo_pago']); ?>
                                        <?= !empty($v['cliente_nombre']) ? ' (' . esc($v['cliente_nombre']) . ')' : ''; ?>
                                    </span>
                                </td>
                                <td class="col-total">S/ <?= number_format($v['total'], 2); ?></td>
                                <td>
                                    <?php if ($esAnulada): ?>
                                        <span class="badge-status status-anulada">🚫 ANULADA</span>
                                    <?php else: ?>
                                        <span class="badge-status status-completada">✅ COMPLETADA</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" class="btn-view-detail" data-venta-id="<?= $v['id']; ?>">
                                        👁️ Ver Detalle
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL DE DETALLE DE VENTA -->
<div class="pos-modal-overlay" id="detailModal">
    <div class="pos-modal-box" style="max-width: 580px;">
        <div class="modal-detail-header">
            <div class="modal-detail-title-group">
                <span class="modal-detail-icon">🧾</span>
                <div>
                    <h3 class="pos-modal-title" id="detailVentaTitle">Detalle de Venta #--</h3>
                    <span class="modal-detail-date" id="detailVentaDate">--/--/---- --:--</span>
                </div>
            </div>
            <button type="button" class="btn-close-modal" id="btnCloseDetail">✕</button>
        </div>

        <div class="modal-detail-body">

            <!-- Metadatos de la venta -->
            <div class="detail-meta-grid">
                <div class="meta-item">
                    <span class="meta-label">Cajero:</span>
                    <span class="meta-val" id="detailCajero">--</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Atención:</span>
                    <span class="meta-val" id="detailMesero">--</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Método Pago:</span>
                    <span class="meta-val" id="detailPago">--</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Estado:</span>
                    <span class="meta-val" id="detailEstado">--</span>
                </div>
            </div>

            <!-- Tabla de Ítems -->
            <div class="detail-items-table-wrapper">
                <table class="detail-items-table">
                    <thead>
                        <tr>
                            <th>Producto / Descripción</th>
                            <th style="text-align: center;">Cant.</th>
                            <th style="text-align: right;">P. Unit</th>
                            <th style="text-align: right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody id="detailItemsBody">
                        <!-- Inyectado por JS -->
                    </tbody>
                </table>
            </div>

            <!-- Resumen de Totales -->
            <div class="detail-totals-box">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span id="detailSubtotal">S/ 0.00</span>
                </div>
                <div class="total-row">
                    <span>Descuento:</span>
                    <span id="detailDescuento">S/ 0.00</span>
                </div>
                <div class="total-row total-main">
                    <span>TOTAL:</span>
                    <span id="detailTotalValue">S/ 0.00</span>
                </div>
            </div>

        </div>

        <!-- Acciones del Modal de Detalle -->
        <div class="pos-modal-actions" style="margin-top: 1.25rem;">
            <button type="button" class="pos-modal-btn pos-modal-btn-cancel" id="btnCancelDetail" style="width: auto;">Cerrar</button>
            <button type="button" class="btn-annul-trigger" id="btnAnnulTrigger" style="display: none;">
                🚫 ANULAR VENTA
            </button>
        </div>
    </div>
</div>

<!-- MODAL DE CONFIRMACIÓN DE ANULACIÓN DE VENTA -->
<div class="pos-modal-overlay" id="annulConfirmModal">
    <div class="pos-modal-box" style="max-width: 460px; border-color: rgba(239,68,68,0.4);">
        <div class="pos-modal-icon" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">🚫</div>
        <h3 class="pos-modal-title" style="color: #ef4444;">¿Anular Venta <span id="annulVentaNum">#--</span>?</h3>
        
        <div style="background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); border-radius: 10px; padding: 0.85rem; margin: 1rem 0; text-align: left; font-size: 0.85rem; line-height: 1.4; color: var(--text-main);">
            ⚠️ <strong>Esta acción realizará las siguientes operaciones:</strong>
            <ul style="margin-top: 0.4rem; padding-left: 1.2rem; margin-bottom: 0;">
                <li>Cambiará el estado de la venta a <strong>ANULADA</strong>.</li>
                <li>Restaurará el stock de todos los productos al inventario.</li>
                <li>Registrará los movimientos de stock tipo <strong>AJUSTE</strong>.</li>
                <li>Si era una venta fiada, cancelará la deuda del cliente.</li>
                <li>Excluirá este monto de los totales de ventas válidas.</li>
            </ul>
        </div>

        <div id="annulErrorMsg" style="display: none; padding: 0.65rem; background: rgba(239,68,68,0.2); border: 1px solid #ef4444; border-radius: 8px; color: #f87171; font-size: 0.85rem; margin-bottom: 0.75rem;"></div>

        <div class="pos-modal-actions">
            <button type="button" class="pos-modal-btn pos-modal-btn-cancel" id="btnCancelAnnul">Cancelar</button>
            <button type="button" class="pos-modal-btn pos-modal-btn-confirm" id="btnConfirmAnnul" style="background: #dc2626;">
                <span id="annulBtnText">Sí, Anular Venta</span>
            </button>
        </div>
    </div>
</div>

<?= $this->endSection(); ?>

<?= $this->section('scripts'); ?>
<script>
    const HISTORIAL_BASE_URL = '<?= base_url(); ?>';
    const HISTORIAL_CSRF_NAME  = '<?= csrf_token(); ?>';
    const HISTORIAL_CSRF_HASH  = '<?= csrf_hash(); ?>';
</script>
<script src="<?= base_url('js/ventas_historial.js'); ?>"></script>
<?= $this->endSection(); ?>
