<?= $this->extend('layout/app_layout'); ?>
<?= $this->section('content'); ?>

<?php
$historial    = $datos['historial']    ?? [];
$deudaActual  = $datos['deuda_actual'] ?? 0;
$clienteId    = $datos['cliente_id']   ?? 0;
$nombre       = $datos['nombre']       ?? '';
$telefono     = $datos['telefono']     ?? '';
$garantia     = $datos['garantia']     ?? '';

$hayDeuda = $deudaActual > 0;
?>

<!-- Page Header -->
<div class="fdetalle-header" style="margin-bottom: 20px;">
    <div>
        <h1 class="page-title" style="font-size: 26px; font-weight: 800; text-transform: uppercase; margin: 0; color: #1e293b;"><?= esc($nombre); ?></h1>
        <p class="page-subtitle" style="font-size: 18px; color: #64748b; margin: 5px 0; display: flex; align-items: center; gap: 8px;">
            <span>📞</span> <?= !empty($telefono) ? esc($telefono) : 'Sin teléfono'; ?>
        </p>
    </div>
</div>

<!-- Deuda -->
<div style="background: linear-gradient(to right, #ffffff, #f8fafc); padding: 25px; border-radius: 16px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); margin-bottom: 25px; border: 1px solid #e2e8f0;">
    <div style="margin-bottom: 20px;">
        <span style="display: block; font-size: 13px; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">DEUDA ACTUAL</span>
        <span style="display: block; font-size: 38px; font-weight: 900; color: #0f172a;">S/ <?= number_format($deudaActual, 2); ?></span>
    </div>

    <?php if ($hayDeuda): ?>
        <button type="button" class="btn-pagar-fiado" onclick="event.stopPropagation(); abrirModalPago();" style="width: 100%; padding: 18px; background: linear-gradient(135deg, #28a745, #218838); color: #fff; border: none; border-radius: 12px; font-size: 20px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3); transition: transform 0.2s, box-shadow 0.2s; display: flex; align-items: center; justify-content: center; gap: 10px;">
            <span>💰</span> REGISTRAR PAGO
        </button>
    <?php endif; ?>
</div>

<!-- Historial -->
<div class="fdetalle-historial">
    <h2 style="font-size: 20px; font-weight: bold; margin-bottom: 15px;">HISTORIAL</h2>

    <?php if (empty($historial)): ?>
        <div style="padding: 20px; text-align: center; color: #999;">No hay movimientos.</div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($historial as $mov): ?>
                <?php
                    $color = '#333';
                    if ($mov['tipo'] === 'VENTA_FIADA') $color = '#dc3545'; // Rojo
                    elseif ($mov['tipo'] === 'PAGO') $color = '#28a745'; // Verde
                    elseif ($mov['tipo'] === 'VENTA_ANULADA') $color = '#6c757d'; // Gris
                ?>
                <div style="padding: 18px; background: #fff; border-left: 6px solid <?= $color; ?>; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #f1f5f9; border-left-width: 6px;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="flex: 1;">
                            <span style="display: block; font-weight: 800; color: <?= $color; ?>; font-size: 17px; margin-bottom: 2px;"><?= esc($mov['concepto']); ?></span>
                            <span style="display: block; font-size: 14px; color: #475569; font-weight: 600;"><?= esc($mov['referencia']); ?></span>
                            <span style="display: block; font-size: 12px; color: #94a3b8; margin-top: 2px;"><?= esc($mov['fecha_formato']); ?></span>
                            
                            <?php if (!empty($mov['observacion'])): ?>
                                <div style="margin-top: 10px; font-size: 13px; color: #334155; background: #f8fafc; padding: 10px 15px; border-radius: 10px; border-left: 4px solid <?= $mov['es_anulada'] ? '#94a3b8' : '#fbbf24'; ?>; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                                    <strong style="color: <?= $mov['es_anulada'] ? '#64748b' : '#b45309'; ?>; display: block; margin-bottom: 2px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">🔐 Observación / Garantía</strong>
                                    <?= esc($mov['observacion']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 12px;">
                            <span style="display: block; font-size: 20px; font-weight: 900; color: <?= $color; ?>;">S/ <?= number_format($mov['monto_abs'], 2); ?></span>
                            
                            <?php if ($mov['tipo'] === 'VENTA_FIADA' || $mov['tipo'] === 'VENTA_ANULADA'): ?>
                                <button type="button" class="btn-ver-detalle" 
                                        onclick='event.stopPropagation(); abrirModalDetalle(<?= json_encode([
                                            "referencia" => $mov["referencia"],
                                            "total" => $mov["monto_abs"],
                                            "fecha" => $mov["fecha_formato"],
                                            "observacion" => $mov["observacion"],
                                            "productos" => $mov["productos"]
                                        ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                        style="padding: 6px 14px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 12px; font-weight: 700; color: #475569; cursor: pointer; transition: all 0.2s;">
                                    Ver detalle
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ══════════ MODAL DE PAGO ══════════ -->
<div class="modal-overlay" id="modalPago" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <?php if ($hayDeuda): ?>
        <div class="modal-box modal-pago-box" style="background: #fff; padding: 25px; border-radius: 12px; width: 90%; max-width: 450px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); position: relative;">
            <div class="modal-pago-header" style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                <div>
                    <h2 style="margin: 0; font-size: 22px;">💰 Registrar Pago</h2>
                    <span class="modal-pago-cliente-nombre" style="color: #666; font-size: 14px;"><?= esc($nombre); ?></span>
                </div>
                <button type="button" class="modal-close-btn" onclick="cerrarModalPago()" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 5px;">✕</button>
            </div>

            <div class="modal-pago-deuda-bar" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; border: 1px solid #eee;">
                <span class="mpdb-label" style="display: block; font-size: 12px; color: #888; text-transform: uppercase;">Deuda actual</span>
                <span class="mpdb-valor" style="display: block; font-size: 24px; font-weight: bold; color: #333;">S/ <?= number_format($deudaActual, 2); ?></span>
            </div>

            <form id="formPago" class="form-pago" onsubmit="submitPago(event)">
                <input type="hidden" name="cliente_id" value="<?= $clienteId; ?>">

                <!-- Monto -->
                <div class="fpago-group" style="margin-bottom: 20px;">
                    <label class="fpago-label" style="display: block; font-weight: bold; margin-bottom: 8px;">Monto a pagar</label>
                    <div class="monto-input-wrap" style="position: relative; display: flex; align-items: center;">
                        <span class="monto-prefix" style="position: absolute; left: 15px; z-index: 10; font-weight: bold; color: #333; pointer-events: none;">S/</span>
                        <input
                            type="number"
                            id="montoPago"
                            name="monto"
                            class="input-monto"
                            step="0.01"
                            min="0.01"
                            max="<?= $deudaActual; ?>"
                            placeholder=" 0.00"
                            style="width: 100%; padding: 12px 12px 12px 48px; border: 2px solid #007bff; border-radius: 8px; font-size: 24px; font-weight: 900; box-sizing: border-box; height: 62px; line-height: 1.5; text-decoration: none; text-shadow: none; appearance: none; -webkit-appearance: none; -moz-appearance: textfield; background: #fff;"
                            required
                        >
                    </div>
                    <div class="monto-shortcuts" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 10px;">
                        <?php
                            $atajos = [10, 20, 50];
                            foreach ($atajos as $atajo) {
                                if ($atajo < $deudaActual):
                        ?>
                            <button type="button" class="shortcut-btn" onclick="setMonto(<?= $atajo; ?>)" style="padding: 8px; background: #f0f4f8; border: 1px solid #d1d9e6; border-radius: 6px; cursor: pointer; font-size: 13px;">S/ <?= $atajo; ?></button>
                        <?php endif; } ?>
                        <button type="button" class="shortcut-btn shortcut-full" onclick="setMonto(<?= $deudaActual; ?>)" style="grid-column: span 2; padding: 10px; background: #e7f3ff; border: 1px solid #b3d7ff; border-radius: 6px; cursor: pointer; font-weight: bold; color: #0056b3; font-size: 14px;">
                            Pagar todo (S/ <?= number_format($deudaActual, 2); ?>)
                        </button>
                    </div>
                </div>

                <!-- Método de pago -->
                <div class="fpago-group" style="margin-bottom: 20px;">
                    <label class="fpago-label" style="display: block; font-weight: bold; margin-bottom: 8px;">Método de pago</label>
                    <div class="pago-metodos" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
                        <label class="metodo-option" style="cursor: pointer; text-align: center;">
                            <input type="radio" name="tipo_pago" value="EFECTIVO" checked style="display:none;">
                            <span class="metodo-label" style="display: block; padding: 10px 5px; border: 2px solid #eee; border-radius: 8px; font-size: 11px;">💵<br>Efectivo</span>
                        </label>
                        <label class="metodo-option" style="cursor: pointer; text-align: center;">
                            <input type="radio" name="tipo_pago" value="YAPE" style="display:none;">
                            <span class="metodo-label" style="display: block; padding: 10px 5px; border: 2px solid #eee; border-radius: 8px; font-size: 11px;">📱<br>Yape</span>
                        </label>
                        <label class="metodo-option" style="cursor: pointer; text-align: center;">
                            <input type="radio" name="tipo_pago" value="PLIN" style="display:none;">
                            <span class="metodo-label" style="display: block; padding: 10px 5px; border: 2px solid #eee; border-radius: 8px; font-size: 11px;">📱<br>Plin</span>
                        </label>
                        <label class="metodo-option" style="cursor: pointer; text-align: center;">
                            <input type="radio" name="tipo_pago" value="TARJETA" style="display:none;">
                            <span class="metodo-label" style="display: block; padding: 10px 5px; border: 2px solid #eee; border-radius: 8px; font-size: 11px;">💳<br>Tarjeta</span>
                        </label>
                    </div>
                </div>

                <div id="pagoAlerta" class="pago-alerta" style="display:none; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px;"></div>

                <div class="fpago-actions" style="display: flex; gap: 10px;">
                    <button type="button" class="btn-cancelar-pago" onclick="cerrarModalPago()" style="flex: 1; padding: 12px; background: #fff; border: 1px solid #ccc; border-radius: 8px; cursor: pointer;">Cancelar</button>
                    <button type="submit" id="btnConfirmarPago" class="btn-confirmar-pago" style="flex: 2; padding: 12px; background: #28a745; color: #fff; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 16px;">
                        💰 Confirmar Pago
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- ══════════ MODAL DE DETALLE ══════════ -->
<div class="modal-overlay" id="modalDetalle" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div class="modal-box" style="background: #fff; padding: 25px; border-radius: 12px; width: 90%; max-width: 500px; max-height: 85vh; overflow-y: auto; box-shadow: 0 5px 15px rgba(0,0,0,0.3); position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
            <div>
                <h2 id="detalleTitulo" style="margin: 0; font-size: 20px;">Detalle de Venta</h2>
                <span id="detalleFecha" style="color: #666; font-size: 14px;"></span>
            </div>
            <button type="button" onclick="cerrarModalDetalle()" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 5px;">✕</button>
        </div>

        <div id="detalleGarantia" style="display:none; margin-bottom: 20px; background: #fffbeb; padding: 15px; border-radius: 12px; border-left: 5px solid #f59e0b; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <strong style="display: block; font-size: 11px; margin-bottom: 6px; color: #b45309; text-transform: uppercase; letter-spacing: 1px; font-weight: 800;">🔐 Observación / Garantía</strong>
            <span id="detalleGarantiaTexto" style="font-size: 15px; color: #451a03; line-height: 1.5; font-weight: 500;"></span>
        </div>

        <div style="margin-bottom: 20px;">
            <h3 style="font-size: 16px; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Productos</h3>
            <div id="detalleProductosList" style="display: flex; flex-direction: column; gap: 12px;">
                <!-- Se llena con JS -->
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <strong style="font-size: 14px; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Total Venta</strong>
            <span id="detalleTotal" style="font-size: 24px; font-weight: 900; color: #0f172a;"></span>
        </div>

        <button type="button" onclick="cerrarModalDetalle()" style="width: 100%; margin-top: 25px; padding: 15px; background: linear-gradient(135deg, #007bff, #0056b3); color: #fff; border: none; border-radius: 12px; font-weight: 800; font-size: 16px; cursor: pointer; box-shadow: 0 4px 6px rgba(0, 123, 255, 0.2); transition: all 0.2s;">ENTENDIDO / CERRAR</button>
    </div>
</div>

<!-- MODAL DE ALERTA FIADOS (Reemplazo de alert nativo) -->
<div class="pos-modal-overlay" id="fiadoAlertModal">
    <div class="pos-modal-box" style="max-width: 400px; text-align: center;">
        <div class="pos-modal-icon" id="fiadoAlertIcon" style="font-size: 3rem;">⚠️</div>
        <h3 class="pos-modal-title" id="fiadoAlertTitle">Aviso</h3>
        <p class="pos-modal-text" id="fiadoAlertText"></p>
        <div class="pos-modal-actions" style="margin-top: 1.5rem;">
            <button type="button" class="pos-modal-btn pos-modal-btn-confirm" id="btnCloseFiadoAlert" style="width: 100%;">
                ENTENDIDO
            </button>
        </div>
    </div>
</div>

<style>
    .fdetalle-header {
        background: #fff;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        border-bottom: 4px solid #007bff;
    }
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(4px);
        z-index: 9999;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }
    .modal-box {
        background: #fff;
        padding: 30px;
        border-radius: 16px;
        width: 100%;
        max-width: 500px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        position: relative;
        /* Forzamos visibilidad para evitar problemas con animaciones previas */
        opacity: 1 !important;
        transform: none !important;
    }
    #montoPago::-webkit-outer-spin-button,
    #montoPago::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    #montoPago {
        -moz-appearance: textfield;
        background-clip: padding-box !important;
    }
    .btn-ver-detalle {
        padding: 6px 15px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #495057;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-ver-detalle:hover {
        background: #e9ecef;
        border-color: #ced4da;
    }
    .metodo-option input:checked + .metodo-label {
        background: #e7f3ff;
        border-color: #007bff !important;
        color: #007bff;
        font-weight: bold;
    }
</style>

<?= $this->endSection(); ?>

<?= $this->section('scripts'); ?>
<script>
    window.FIADO_CLIENTE_ID = <?= (int)$clienteId; ?>;
    window.FIADO_URL_PAGAR  = '<?= site_url('fiados/pagar'); ?>';
    window.FIADO_URL_GUARDAR_GARANTIA = '<?= site_url('fiados/guardar-garantia'); ?>';
</script>
<script src="<?= base_url('js/fiados.js'); ?>"></script>
<?= $this->endSection(); ?>
