<?= $this->extend('layout/app_layout'); ?>

<?= $this->section('content'); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">🛒 Detalle de Compra #<?= $compra['id']; ?></h1>
        <p class="page-subtitle">Información detallada de la adquisición realizada</p>
    </div>
    <div class="page-header-actions">
        <a href="<?= site_url('compras'); ?>" class="btn-secondary">
            Volver al Listado
        </a>
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

<div class="row" style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
    <div style="flex: 2; min-width: 600px;">
        <!-- Card Información General -->
        <div class="card" style="padding: 1.5rem; margin-bottom: 1.5rem;">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
                <div>
                    <label class="form-label" style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Proveedor</label>
                    <div style="font-weight: 700; color: var(--text-primary); font-size: 1.1rem;"><?= esc($compra['proveedor_nombre']); ?></div>
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Fecha de Compra</label>
                    <div style="font-weight: 600; color: var(--text-primary);"><?= date('d/m/Y H:i', strtotime($compra['fecha'])); ?></div>
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Registrado por</label>
                    <div style="font-weight: 600; color: var(--text-primary);"><?= esc($compra['usuario_nombre']); ?></div>
                </div>
            </div>
            
            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div>
                    <label class="form-label" style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Estado</label>
                    <div>
                        <span class="badge-status <?= $compra['estado'] === 'COMPLETADA' ? 'status-activo' : 'status-inactivo'; ?>" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                            <?= esc($compra['estado']); ?>
                        </span>
                    </div>
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Observación</label>
                    <div style="font-style: italic; color: var(--text-secondary);"><?= esc($compra['observacion']) ?: 'Sin observaciones'; ?></div>
                </div>
            </div>
        </div>

        <!-- Card Detalle de Productos -->
        <div class="card" style="padding: 1.5rem;">
            <h3 style="font-size: 1.1rem; margin-bottom: 1rem; font-family: 'Space Grotesk', sans-serif;">Productos Adquiridos</h3>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-center">Costo Unitario</th>
                            <th class="text-center">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalles as $det): ?>
                            <tr>
                                <td><strong><?= esc($det['producto_nombre']); ?></strong></td>
                                <td class="text-center"><?= $det['cantidad']; ?></td>
                                <td class="text-center">S/ <?= number_format($det['costo_unitario'], 2); ?></td>
                                <td class="text-center"><span style="font-weight: 600;">S/ <?= number_format($det['subtotal'], 2); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div style="flex: 1; min-width: 300px;">
        <!-- Card Totales -->
        <div class="card" style="padding: 1.5rem; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.1rem; margin-bottom: 1.5rem; font-family: 'Space Grotesk', sans-serif;">Resumen Económico</h3>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; color: var(--text-secondary);">
                <span>Subtotal:</span>
                <span>S/ <?= number_format($compra['subtotal'], 2); ?></span>
            </div>
            
            <div style="display: flex; justify-content: space-between; font-size: 1.5rem; font-weight: 700; color: var(--accent); border-top: 1px solid var(--border-color); padding-top: 1rem; margin-top: 1rem;">
                <span>Total:</span>
                <span>S/ <?= number_format($compra['total'], 2); ?></span>
            </div>
        </div>

        <!-- Acciones Extra -->
        <?php if ($compra['estado'] === 'COMPLETADA' && session()->get('rol') === 'ADMIN'): ?>
            <div class="card" style="padding: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2); background: rgba(239, 68, 68, 0.02);">
                <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--danger);">Zona de Peligro</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">
                    Al anular la compra se restará el stock ingresado y se creará un movimiento de ajuste. Esta acción no se puede deshacer.
                </p>
                <form action="<?= site_url('compras/anular/' . $compra['id']); ?>" method="POST" id="formAnularCompra">
                    <?= csrf_field(); ?>
                    <button type="button" id="btnAnularCompra" class="btn-danger-outline" style="width: 100%;">
                        🚫 ANULAR COMPRA
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Confirmación para Anulación -->
<div class="pos-modal-overlay" id="modalConfirmarAnular">
    <div class="pos-modal-box" style="max-width: 450px;">
        <div class="pos-modal-icon">⚠️</div>
        <h3 class="pos-modal-title">¿Anular esta compra?</h3>
        <p style="margin-top: 1rem; color: var(--text-secondary);">
            Se procederá a revertir el stock de todos los productos incluidos en esta compra.
        </p>
        
        <div class="pos-modal-actions">
            <button type="button" class="pos-modal-btn pos-modal-btn-cancel" id="btnCancelAnular">Cancelar</button>
            <button type="button" class="pos-modal-btn pos-modal-btn-confirm" id="btnConfirmAnular" style="background: var(--danger);">Confirmar Anulación</button>
        </div>
    </div>
</div>

<style>
    .btn-secondary { background: var(--bg-body); color: var(--text-primary); border: 1px solid var(--border-color); padding: 0.6rem 1.2rem; border-radius: 8px; text-decoration: none; font-weight: 600; transition: var(--transition); display: inline-block; }
    .btn-secondary:hover { background: var(--bg-card-hover); }
    .btn-danger-outline { background: transparent; color: var(--danger); border: 1px solid var(--danger); padding: 0.75rem 1rem; border-radius: 10px; font-weight: 700; cursor: pointer; transition: var(--transition); }
    .btn-danger-outline:hover { background: var(--danger); color: #fff; }
    .table-custom { width: 100%; border-collapse: collapse; }
    .table-custom th { text-align: left; padding: 1rem; background: rgba(0,0,0,0.05); color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase; border-bottom: 1px solid var(--border-color); }
    .table-custom td { padding: 1rem; border-bottom: 1px solid var(--border-color); }
    .text-accent { color: var(--accent); }
    
    .pos-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 2000; opacity: 0; visibility: hidden; transition: var(--transition); }
    .pos-modal-overlay.show { opacity: 1; visibility: visible; }
    .pos-modal-box { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: 2rem; width: 90%; transform: translateY(20px); transition: var(--transition); text-align: center; }
    .pos-modal-overlay.show .pos-modal-box { transform: translateY(0); }
    .pos-modal-icon { width: 60px; height: 60px; background: var(--danger-bg); color: var(--danger); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 1rem; }
    .pos-modal-title { font-size: 1.25rem; font-weight: 700; color: var(--text-primary); }
    .pos-modal-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem; }
    .pos-modal-btn { padding: 0.75rem; border-radius: 10px; border: none; font-weight: 600; cursor: pointer; transition: var(--transition); }
    .pos-modal-btn-cancel { background: var(--bg-body); color: var(--text-primary); border: 1px solid var(--border-color); }
</style>

<?= $this->section('scripts'); ?>
<script>
$(document).ready(function() {
    $('#btnAnularCompra').on('click', function() {
        $('#modalConfirmarAnular').addClass('show');
    });

    $('#btnCancelAnular, #modalConfirmarAnular').on('click', function(e) {
        if (e.target === this || $(this).attr('id') === 'btnCancelAnular') {
            $('#modalConfirmarAnular').removeClass('show');
        }
    });

    $('#btnConfirmAnular').on('click', function() {
        $(this).prop('disabled', true).text('Procesando...').css('opacity', '0.7');
        $('#formAnularCompra').submit();
    });
});
</script>
<?= $this->endSection(); ?>

<?= $this->endSection(); ?>
