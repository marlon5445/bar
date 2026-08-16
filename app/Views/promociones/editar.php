<?= $this->extend('layout/app_layout'); ?>

<?= $this->section('content'); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">✏️ Editar Promoción</h1>
        <p class="page-subtitle">Modifica los datos y productos de la promoción</p>
    </div>
    <div class="page-header-actions">
        <a href="<?= site_url('promociones'); ?>" class="btn-secondary-custom">
            Volver al listado
        </a>
    </div>
</div>

<div class="card card-form-center" style="max-width: 850px;">
    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alerta-error" style="padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
            <ul style="margin: 0; padding-left: 1.5rem;">
                <?php foreach (session()->getFlashdata('errors') as $err): ?>
                    <li><?= esc($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alerta-error" style="padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
            <?= session()->getFlashdata('error'); ?>
        </div>
    <?php endif; ?>

    <form action="<?= site_url('promociones/actualizar/' . $promocion['id']); ?>" method="POST" autocomplete="off" class="modern-form" id="formPromocion">
        <?= csrf_field(); ?>

        <div class="form-group">
            <label for="nombre" class="form-label">Nombre de la Promoción <span class="text-danger">*</span></label>
            <input type="text" id="nombre" name="nombre" value="<?= old('nombre', $promocion['nombre']); ?>" required maxlength="100" class="form-input-custom" placeholder="Ej: 3 Jarras S/20">
        </div>

        <div class="form-group">
            <label for="precio" class="form-label">Precio Promocional (S/) <span class="text-danger">*</span></label>
            <input type="number" id="precio" name="precio" step="0.01" value="<?= old('precio', $promocion['precio']); ?>" required class="form-input-custom" placeholder="0.00" min="0.01">
        </div>

        <div class="form-group">
            <label for="descripcion" class="form-label">Descripción <span class="text-muted">(Opcional)</span></label>
            <textarea id="descripcion" name="descripcion" class="form-input-custom textarea-custom" placeholder="Detalles de la promoción..."><?= old('descripcion', $promocion['descripcion']); ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Productos incluidos <span class="text-danger">*</span></label>
            <div id="contenedorProductos" class="productos-dinamicos">
                <div class="producto-fila-header">
                    <span>Producto</span>
                    <span>Cantidad</span>
                    <span></span>
                </div>
                
                <?php foreach ($promocion['detalles'] as $detalle): ?>
                    <div class="producto-fila">
                        <select name="productos[]" required class="form-input-custom select-producto">
                            <option value="<?= $detalle['producto_id']; ?>" selected>
                                <?= esc($detalle['producto_nombre']); ?> 
                                <?= $detalle['producto_estado'] !== 'ACTIVO' ? '(INACTIVO)' : ''; ?>
                                (S/ <?= number_format($detalle['producto_precio'], 2); ?>)
                            </option>
                            <?php foreach ($productos as $p): ?>
                                <?php if ($p['id'] != $detalle['producto_id']): ?>
                                    <option value="<?= $p['id']; ?>" data-precio="<?= $p['precio_venta']; ?>">
                                        <?= esc($p['nombre']); ?> (S/ <?= number_format($p['precio_venta'], 2); ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="cantidades[]" value="<?= $detalle['cantidad']; ?>" min="1" step="1" required class="form-input-custom" placeholder="Cant.">
                        <button type="button" class="btn-remove-fila" title="Eliminar fila">🗑️</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="btnAgregarProducto" class="btn-secondary-custom" style="margin-top: 1rem; width: fit-content;">
                ➕ Agregar producto
            </button>
        </div>

        <div class="form-group" style="margin-top: 1rem;">
            <label for="estado" class="form-label">Estado</label>
            <select id="estado" name="estado" required class="form-input-custom">
                <option value="ACTIVO" <?= old('estado', $promocion['estado']) === 'ACTIVO' ? 'selected' : ''; ?>>ACTIVO</option>
                <option value="INACTIVO" <?= old('estado', $promocion['estado']) === 'INACTIVO' ? 'selected' : ''; ?>>INACTIVO</option>
            </select>
        </div>

        <div class="form-actions-full">
            <button type="submit" class="btn-primary-full">
                Actualizar Promoción
            </button>
            <a href="<?= site_url('promociones'); ?>" class="btn-secondary-full" style="margin-top: 0.75rem; display: block; text-align: center; text-decoration: none;">
                Cancelar
            </a>
        </div>
    </form>
</div>

<style>
    .card-form-center { margin: 0 auto; padding: 2rem; }
    .modern-form { display: flex; flex-direction: column; gap: 1.5rem; }
    .form-group { display: flex; flex-direction: column; gap: 0.5rem; }
    .form-label { font-weight: 600; font-size: 0.9rem; color: var(--text-secondary); }
    .form-input-custom { width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 10px; padding: 0.75rem 1rem; color: var(--text-primary); font-family: inherit; font-size: 1rem; transition: var(--transition); outline: none; }
    .form-input-custom:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
    .textarea-custom { min-height: 80px; resize: vertical; }
    .btn-primary-full { width: 100%; background: var(--accent); color: #000; padding: 0.85rem; border-radius: 10px; border: none; font-weight: 700; font-size: 1rem; cursor: pointer; transition: var(--transition); }
    .btn-secondary-full { width: 100%; background: var(--bg-card); color: var(--text-primary); padding: 0.85rem; border-radius: 10px; border: 1px solid var(--border-color); font-weight: 600; font-size: 1rem; transition: var(--transition); }
    .btn-secondary-custom { background: var(--bg-card); color: var(--text-primary); padding: 0.6rem 1.2rem; border-radius: 8px; text-decoration: none; font-weight: 600; border: 1px solid var(--border-color); transition: var(--transition); font-size: 0.9rem; cursor: pointer; }
    .text-danger { color: var(--danger); }
    
    .productos-dinamicos { display: flex; flex-direction: column; gap: 0.75rem; }
    .producto-fila-header { display: grid; grid-template-columns: 2fr 1fr 40px; gap: 1rem; padding: 0 0.5rem; font-size: 0.85rem; color: var(--text-muted); font-weight: 600; }
    .producto-fila { display: grid; grid-template-columns: 2fr 1fr 40px; gap: 1rem; align-items: center; background: rgba(0,0,0,0.02); padding: 0.5rem; border-radius: 10px; border: 1px solid var(--border-color); }
    .btn-remove-fila { background: var(--danger-bg); color: var(--danger); border: 1px solid var(--danger); width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: var(--transition); }
    .btn-remove-fila:hover { background: var(--danger); color: #fff; }

    @media (max-width: 600px) {
        .producto-fila-header { display: none; }
        .producto-fila { grid-template-columns: 1fr; gap: 0.5rem; }
        .btn-remove-fila { width: 100%; }
    }
</style>

<!-- MODAL DE ERROR -->
<div class="pos-modal-overlay" id="modalError">
    <div class="pos-modal-box" style="max-width: 400px;">
        <div class="pos-modal-icon" style="background: var(--danger-bg); color: var(--danger);">⚠️</div>
        <h3 class="pos-modal-title">Atención</h3>
        <div id="modalErrorMsg" style="margin: 1rem 0; color: var(--text-primary);"></div>
        <div class="pos-modal-actions" style="grid-template-columns: 1fr;">
            <button type="button" class="pos-modal-btn pos-modal-btn-confirm" id="btnCloseError">Entendido</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contenedor = document.getElementById('contenedorProductos');
    const btnAgregar = document.getElementById('btnAgregarProducto');
    const form = document.getElementById('formPromocion');
    const modalError = document.getElementById('modalError');
    const modalErrorMsg = document.getElementById('modalErrorMsg');
    const btnCloseError = document.getElementById('btnCloseError');

    const productosDisponibles = <?= json_encode($productos); ?>;

    function mostrarError(msg) {
        modalErrorMsg.innerText = msg;
        modalError.classList.add('show');
    }

    btnCloseError.addEventListener('click', () => modalError.classList.remove('show'));

    function asignarEventosFila(fila) {
        fila.querySelector('.btn-remove-fila').addEventListener('click', function() {
            fila.remove();
        });

        fila.querySelector('.select-producto').addEventListener('change', function() {
            const selectedId = this.value;
            if (!selectedId) return;

            const selects = document.querySelectorAll('.select-producto');
            let count = 0;
            selects.forEach(s => {
                if (s.value === selectedId) count++;
            });

            if (count > 1) {
                mostrarError('Este producto ya ha sido agregado a la promoción.');
                this.value = "";
            }
        });
    }

    // Asignar eventos a las filas existentes
    document.querySelectorAll('.producto-fila').forEach(asignarEventosFila);

    function crearFila() {
        const fila = document.createElement('div');
        fila.className = 'producto-fila';
        fila.innerHTML = `
            <select name="productos[]" required class="form-input-custom select-producto">
                <option value="">-- Seleccionar --</option>
                ${productosDisponibles.map(p => `<option value="${p.id}" data-precio="${p.precio_venta}">${p.nombre} (S/ ${p.precio_venta})</option>`).join('')}
            </select>
            <input type="number" name="cantidades[]" value="1" min="1" step="1" required class="form-input-custom" placeholder="Cant.">
            <button type="button" class="btn-remove-fila" title="Eliminar fila">🗑️</button>
        `;

        asignarEventosFila(fila);
        contenedor.appendChild(fila);
    }

    btnAgregar.addEventListener('click', crearFila);

    form.addEventListener('submit', function(e) {
        const filas = document.querySelectorAll('.producto-fila');
        if (filas.length === 0) {
            e.preventDefault();
            mostrarError('La promoción debe incluir al menos un producto.');
            return;
        }

        let valid = true;
        filas.forEach(fila => {
            const select = fila.querySelector('select');
            const cant = fila.querySelector('input[type="number"]');
            if (!select.value || !cant.value || cant.value < 1) {
                valid = false;
            }
        });

        if (!valid) {
            e.preventDefault();
            mostrarError('Por favor, complete todos los campos de productos y cantidades.');
        }
    });
});
</script>

<?= $this->endSection(); ?>
