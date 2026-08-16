<?= $this->extend('layout/app_layout'); ?>

<?= $this->section('content'); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">🛒 Registro de Nueva Compra</h1>
        <p class="page-subtitle">Ingresa los productos adquiridos para actualizar el stock</p>
    </div>
    <div class="page-header-actions">
        <a href="<?= site_url('compras'); ?>" class="btn-secondary">
            Volver al Listado
        </a>
    </div>
</div>

<div class="row" style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
    <!-- Formulario Principal -->
    <div style="flex: 2; min-width: 600px;">
        <div class="card" style="padding: 1.5rem;">
            <div style="margin-bottom: 1.5rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label class="form-label">Proveedor *</label>
                    <select id="proveedor_id" class="form-input-custom">
                        <option value="">-- Seleccionar Proveedor --</option>
                        <?php foreach ($proveedores as $prov): ?>
                            <option value="<?= $prov['id']; ?>"><?= esc($prov['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Fecha</label>
                    <input type="text" value="<?= date('d/m/Y'); ?>" class="form-input-custom" readonly style="background: var(--bg-body); cursor: default;">
                </div>
            </div>

            <div style="border-top: 1px solid var(--border-color); padding-top: 1.5rem; margin-bottom: 1rem;">
                <h3 style="font-size: 1.1rem; margin-bottom: 1rem;">Agregar Productos</h3>
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 50px; gap: 0.75rem; align-items: end;">
                    <div>
                        <label class="form-label">Producto</label>
                        <select id="producto_input" class="form-input-custom">
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($productos as $pr): ?>
                                <option value="<?= $pr['id']; ?>" data-nombre="<?= esc($pr['nombre']); ?>" data-costo="<?= $pr['costo']; ?>">
                                    <?= esc($pr['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Cantidad</label>
                        <input type="number" id="cantidad_input" class="form-input-custom" min="1" value="1">
                    </div>
                    <div>
                        <label class="form-label">Costo Total</label>
                        <input type="number" id="costo_total_input" class="form-input-custom" step="0.01" min="0.01">
                    </div>
                    <div>
                        <label class="form-label">Subtotal</label>
                        <input type="text" id="subtotal_item" class="form-input-custom" readonly style="background: var(--bg-body);">
                    </div>
                    <div>
                        <button type="button" id="btn_agregar_item" class="btn-primary" style="width: 100%; height: 46px; justify-content: center; padding: 0;">+</button>
                    </div>
                </div>
            </div>

            <div class="table-responsive" style="margin-top: 1.5rem;">
                <table class="table-custom" id="tabla_detalle">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-center">Costo Unit.</th>
                            <th class="text-center">Subtotal</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Items agregados aquí -->
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 1.5rem;">
                <label class="form-label">Observación (opcional)</label>
                <textarea id="observacion" class="form-input-custom" style="min-height: 80px;"></textarea>
            </div>
        </div>
    </div>

    <!-- Resumen -->
    <div style="flex: 1; min-width: 300px;">
        <div class="card" style="padding: 1.5rem; position: sticky; top: 1rem;">
            <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem; font-family: 'Space Grotesk', sans-serif;">Resumen de Compra</h3>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 1rem; color: var(--text-secondary);">
                <span>Subtotal:</span>
                <span id="resumen_subtotal">S/ 0.00</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem; font-size: 1.5rem; font-weight: 700; color: var(--accent); border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <span>Total:</span>
                <span id="resumen_total">S/ 0.00</span>
            </div>

            <button type="button" id="btn_guardar_compra" class="btn-primary" style="width: 100%; height: 50px; justify-content: center; font-size: 1.1rem;">
                GUARDAR COMPRA
            </button>
        </div>
    </div>
</div>

<!-- MODAL DE AVISO / ERROR -->
<div class="pos-modal-overlay" id="modalAlert">
    <div class="pos-modal-box" style="max-width: 400px;">
        <div id="modalAlertIcon" class="pos-modal-icon" style="background: rgba(255, 215, 0, 0.15); color: var(--accent);">⚠️</div>
        <h3 class="pos-modal-title" id="modalAlertTitle">Aviso</h3>
        <p id="modalAlertMessage" style="margin-top: 1rem; color: var(--text-secondary);"></p>
        <div class="pos-modal-actions" style="display: block;">
            <button type="button" class="pos-modal-btn pos-modal-btn-cancel" id="btnOkAlert" style="width: 100%;">Entendido</button>
        </div>
    </div>
</div>

<!-- MODAL DE CONFIRMACIÓN -->
<div class="pos-modal-overlay" id="modalConfirm">
    <div class="pos-modal-box" style="max-width: 450px;">
        <div class="pos-modal-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success);">✅</div>
        <h3 class="pos-modal-title" id="modalConfirmTitle">¿Confirmar Acción?</h3>
        <p id="modalConfirmMessage" style="margin-top: 1rem; color: var(--text-secondary);"></p>
        <div class="pos-modal-actions" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <button type="button" class="pos-modal-btn pos-modal-btn-cancel" id="btnCancelConfirm">Cancelar</button>
            <button type="button" class="pos-modal-btn pos-modal-btn-confirm" id="btnOkConfirm" style="background: var(--accent); color: #000;">Confirmar</button>
        </div>
    </div>
</div>

<style>
    .btn-secondary { background: var(--bg-body); color: var(--text-primary); border: 1px solid var(--border-color); padding: 0.6rem 1.2rem; border-radius: 8px; text-decoration: none; font-weight: 600; transition: var(--transition); display: inline-block; }
    .btn-secondary:hover { background: var(--bg-card-hover); }
    .form-label { font-weight: 600; font-size: 0.9rem; color: var(--text-secondary); display: block; margin-bottom: 0.5rem; }
    .form-input-custom { width: 100%; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; padding: 0.75rem 1rem; color: var(--text-primary); outline: none; transition: var(--transition); }
    .form-input-custom:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
    .table-custom { width: 100%; border-collapse: collapse; }
    .table-custom th { text-align: left; padding: 0.75rem; background: rgba(0,0,0,0.05); color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; border-bottom: 1px solid var(--border-color); }
    .table-custom td { padding: 0.75rem; border-bottom: 1px solid var(--border-color); }
    .btn-primary { background: var(--accent); color: #000; padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; transition: var(--transition); border: none; cursor: pointer; }
    .btn-primary:hover:not(:disabled) { background: var(--accent-hover); transform: translateY(-2px); box-shadow: var(--accent-glow); }
    .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

    /* Estilos para Select2 */
    .select2-container--default .select2-selection--single {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        height: 46px;
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--text-primary);
        line-height: normal;
        padding-left: 0;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 44px;
        right: 10px;
    }
    .select2-dropdown {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        z-index: 2001;
    }
    .select2-results__option {
        padding: 0.75rem 1rem;
        color: var(--text-primary);
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: var(--accent);
        color: #000;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
        background-color: var(--bg-body);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--text-primary);
        padding: 0.5rem;
    }
    .select2-container--default .select2-selection--single:focus {
        border-color: var(--accent);
        outline: none;
    }
</style>

<?= $this->section('scripts'); ?>
<script>
$(document).ready(function() {
    // Inicializar Select2
    $('#proveedor_id').select2({
        placeholder: '-- Seleccionar Proveedor --',
        width: '100%'
    });
    
    $('#producto_input').select2({
        placeholder: '-- Seleccionar Producto --',
        width: '100%'
    }).on('select2:select', function(e) {
        calcularSubtotalItem();
    });

    let items = [];

    $('#cantidad_input, #costo_total_input').on('input', calcularSubtotalItem);

    function calcularSubtotalItem() {
        const cant = parseFloat($('#cantidad_input').val()) || 0;
        const costoTotal = parseFloat($('#costo_total_input').val()) || 0;
        
        $('#subtotal_item').val(costoTotal.toFixed(2));
    }

    function showAlert(msg, title = 'Aviso') {
        $('#modalAlertTitle').text(title);
        $('#modalAlertMessage').text(msg);
        $('#modalAlert').addClass('show');
    }

    $('#btnOkAlert, #modalAlert').on('click', function(e) {
        if (e.target === this || $(this).attr('id') === 'btnOkAlert') {
            $('#modalAlert').removeClass('show');
        }
    });

    function showConfirm(msg, title, onConfirm) {
        $('#modalConfirmTitle').text(title);
        $('#modalConfirmMessage').text(msg);
        $('#modalConfirm').addClass('show');
        $('#btnOkConfirm').off('click').on('click', function() {
            $('#modalConfirm').removeClass('show');
            onConfirm();
        });
    }

    $('#btnCancelConfirm, #modalConfirm').on('click', function(e) {
        if (e.target === this || $(this).attr('id') === 'btnCancelConfirm') {
            $('#modalConfirm').removeClass('show');
        }
    });

    $('#btn_agregar_item').on('click', function() {
        const prod = $('#producto_input').find(':selected');
        const id = prod.val();
        const nombre = prod.data('nombre');
        const cant = parseInt($('#cantidad_input').val()) || 0;
        const costoTotal = parseFloat($('#costo_total_input').val()) || 0;

        if (!id || cant <= 0 || costoTotal <= 0) {
            showAlert('Por favor ingrese una cantidad mayor a 0 y un costo total mayor a 0.');
            return;
        }

        const costoUnitario = costoTotal / cant;

        const index = items.findIndex(i => i.producto_id === id);
        if (index > -1) {
            items[index].cantidad += cant;
            items[index].subtotal += costoTotal;
            items[index].costo_unitario = items[index].subtotal / items[index].cantidad;
        } else {
            items.push({
                producto_id: id,
                nombre: nombre,
                cantidad: cant,
                costo_unitario: costoUnitario,
                subtotal: costoTotal
            });
        }

        renderTabla();
        $('#producto_input').val('').trigger('change');
        $('#cantidad_input').val(1);
        $('#costo_total_input').val('');
        $('#subtotal_item').val('');
    });

    function renderTabla() {
        const $tbody = $('#tabla_detalle tbody');
        $tbody.empty();
        let total = 0;

        items.forEach((item, idx) => {
            total += item.subtotal;
            $tbody.append(`
                <tr>
                    <td><strong>${item.nombre}</strong></td>
                    <td class="text-center">${item.cantidad}</td>
                    <td class="text-center">S/ ${item.costo_unitario.toFixed(2)}</td>
                    <td class="text-center"><strong>S/ ${item.subtotal.toFixed(2)}</strong></td>
                    <td class="text-center">
                        <button type="button" class="btn_eliminar_item" data-idx="${idx}" style="background:none; border:none; cursor:pointer; font-size:1.2rem;">🗑️</button>
                    </td>
                </tr>
            `);
        });

        if (items.length === 0) {
            $tbody.append('<tr><td colspan="5" class="text-center py-4 text-muted">No hay productos agregados</td></tr>');
        }

        $('#resumen_subtotal').text(`S/ ${total.toFixed(2)}`);
        $('#resumen_total').text(`S/ ${total.toFixed(2)}`);
    }

    $(document).on('click', '.btn_eliminar_item', function() {
        const idx = $(this).data('idx');
        items.splice(idx, 1);
        renderTabla();
    });

    $('#btn_guardar_compra').on('click', function() {
        const provId = $('#proveedor_id').val();
        if (!provId) {
            showAlert('Debe seleccionar un proveedor.');
            return;
        }
        if (items.length === 0) {
            showAlert('Debe agregar al menos un producto.');
            return;
        }

        showConfirm('¿Está seguro de guardar esta compra? El stock se actualizará inmediatamente.', 'Guardar Compra', function() {
            const $btn = $('#btn_guardar_compra');
            if ($btn.prop('disabled')) return; // Prevenir múltiples clicks si ya está deshabilitado
            
            $btn.prop('disabled', true).text('PROCESANDO...').css('opacity', '0.7');

            $.post('<?= site_url('compras/guardar'); ?>', {
                proveedor_id: provId,
                observacion: $('#observacion').val(),
                items: items,
                <?= csrf_token() ?>: '<?= csrf_hash() ?>'
            }, function(res) {
                if (res.success) {
                    window.location.href = res.redirect;
                } else {
                    showAlert(res.message, 'Error al guardar');
                    $btn.prop('disabled', false).text('GUARDAR COMPRA');
                }
            });
        });
    });

    renderTabla();
});
</script>
<?= $this->endSection(); ?>

<?= $this->endSection(); ?>
