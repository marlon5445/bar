<?= $this->extend('layout/app_layout'); ?>

<?= $this->section('content'); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">➕ Nuevo Producto</h1>
        <p class="page-subtitle">Registra un nuevo producto en el catálogo</p>
    </div>
    <div class="page-header-actions">
        <a href="<?= site_url('productos'); ?>" class="btn-secondary-custom">
            Volver al listado
        </a>
    </div>
</div>

<div class="card card-form-center">
    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alerta-error" style="padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <span>⚠️</span>
                <strong>Por favor corrige los siguientes errores:</strong>
            </div>
            <ul style="margin: 0; padding-left: 1.5rem;">
                <?php foreach (session()->getFlashdata('errors') as $err): ?>
                    <li><?= esc($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= site_url('productos/guardar'); ?>" method="POST" autocomplete="off" class="modern-form">
        <?= csrf_field(); ?>

        <div class="form-group">
            <label for="nombre" class="form-label">Nombre del Producto <span class="text-danger">*</span></label>
            <input type="text" id="nombre" name="nombre" value="<?= old('nombre'); ?>" required maxlength="100" class="form-input-custom" placeholder="Ej: Pilsen Callao 630ml">
        </div>

        <div class="form-group">
            <label for="codigo" class="form-label">Código (SKU/Barras)</label>
            <input type="text" id="codigo" name="codigo" value="<?= old('codigo'); ?>" maxlength="50" class="form-input-custom" placeholder="Ej: PILS-001">
        </div>

        <div class="form-group">
            <label for="categoria_id" class="form-label">Categoría <span class="text-danger">*</span></label>
            <select id="categoria_id" name="categoria_id" required class="form-input-custom">
                <option value="">-- Seleccionar Categoría --</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id']; ?>" <?= old('categoria_id') == $cat['id'] ? 'selected' : ''; ?>>
                        <?= esc($cat['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="precio_venta" class="form-label">Precio de Venta (S/) <span class="text-danger">*</span></label>
                <input type="number" id="precio_venta" name="precio_venta" step="0.01" value="<?= old('precio_venta'); ?>" required class="form-input-custom" placeholder="0.00">
            </div>

            <div class="form-group">
                <label for="costo" class="form-label">Costo (S/) <span class="text-danger">*</span></label>
                <input type="number" id="costo" name="costo" step="0.01" value="<?= old('costo'); ?>" required class="form-input-custom" placeholder="0.00">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">¿Controla inventario? <span class="text-danger">*</span></label>
            <div class="radio-group-custom">
                <label class="radio-item">
                    <input type="radio" name="controla_stock" value="1" <?= old('controla_stock', '1') == '1' ? 'checked' : ''; ?>>
                    <span class="radio-label">Sí</span>
                </label>
                <label class="radio-item">
                    <input type="radio" name="controla_stock" value="0" <?= old('controla_stock') == '0' ? 'checked' : ''; ?>>
                    <span class="radio-label">No</span>
                </label>
            </div>
        </div>

        <div id="seccionStock" class="form-grid-2">
            <div class="form-group">
                <label for="stock_actual" class="form-label">Stock Actual</label>
                <input type="number" id="stock_actual" name="stock_actual" value="<?= old('stock_actual', '0'); ?>" class="form-input-custom" placeholder="0">
            </div>

            <div class="form-group">
                <label for="stock_minimo" class="form-label">Stock Mínimo (Alerta)</label>
                <input type="number" id="stock_minimo" name="stock_minimo" value="<?= old('stock_minimo', '0'); ?>" class="form-input-custom" placeholder="0">
            </div>
        </div>

        <div class="form-group">
            <label for="descripcion" class="form-label">Descripción <span class="text-muted">(Opcional)</span></label>
            <textarea id="descripcion" name="descripcion" class="form-input-custom textarea-custom" placeholder="Información adicional del producto..."><?= old('descripcion'); ?></textarea>
        </div>

        <div class="form-group">
            <label for="estado" class="form-label">Estado Inicial</label>
            <select id="estado" name="estado" required class="form-input-custom">
                <option value="ACTIVO" <?= old('estado') === 'ACTIVO' ? 'selected' : ''; ?>>ACTIVO</option>
                <option value="INACTIVO" <?= old('estado') === 'INACTIVO' ? 'selected' : ''; ?>>INACTIVO</option>
            </select>
        </div>

        <div class="form-actions-full">
            <button type="submit" class="btn-primary-full">
                Guardar Producto
            </button>
            <a href="<?= site_url('productos'); ?>" class="btn-secondary-full" style="margin-top: 0.75rem; display: block; text-align: center; text-decoration: none;">
                Cancelar
            </a>
        </div>
    </form>
</div>

<style>
    .card-form-center {
        max-width: 700px;
        margin: 0 auto;
        padding: 2rem;
    }
    .modern-form {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .form-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    .form-label {
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--text-secondary);
    }
    .form-input-custom {
        width: 100%;
        background: var(--bg-body);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 0.75rem 1rem;
        color: var(--text-primary);
        font-family: inherit;
        font-size: 1rem;
        transition: var(--transition);
        outline: none;
    }
    .form-input-custom:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px var(--accent-glow);
    }
    .textarea-custom {
        min-height: 100px;
        resize: vertical;
    }
    .form-actions-full {
        margin-top: 1rem;
    }
    .btn-primary-full {
        width: 100%;
        background: var(--accent);
        color: #000;
        padding: 0.85rem;
        border-radius: 10px;
        border: none;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: var(--transition);
    }
    .btn-primary-full:hover {
        background: var(--accent-hover);
        transform: translateY(-2px);
        box-shadow: var(--accent-glow);
    }
    .btn-secondary-full {
        width: 100%;
        background: var(--bg-card);
        color: var(--text-primary);
        padding: 0.85rem;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        font-weight: 600;
        font-size: 1rem;
        transition: var(--transition);
    }
    .btn-secondary-full:hover {
        background: var(--bg-card-hover);
    }
    .btn-secondary-custom {
        background: var(--bg-card);
        color: var(--text-primary);
        padding: 0.6rem 1.2rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        border: 1px solid var(--border-color);
        transition: var(--transition);
        font-size: 0.9rem;
    }
    .btn-secondary-custom:hover {
        background: var(--bg-card-hover);
        border-color: var(--text-muted);
    }
    .text-danger { color: var(--danger); }
    
    /* Radio Group Custom */
    .radio-group-custom {
        display: flex;
        gap: 2rem;
        padding: 0.5rem 0;
    }
    .radio-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
    }
    .radio-item input[type="radio"] {
        width: 18px;
        height: 18px;
        accent-color: var(--accent);
        cursor: pointer;
    }
    .radio-label {
        font-size: 1rem;
        color: var(--text-primary);
    }

    @media (max-width: 600px) {
        .form-grid-2 { grid-template-columns: 1fr; }
        .card-form-center { padding: 1.25rem; }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radioControlaStock = document.querySelectorAll('input[name="controla_stock"]');
    const seccionStock = document.getElementById('seccionStock');
    const inputStockActual = document.getElementById('stock_actual');
    const inputStockMinimo = document.getElementById('stock_minimo');

    function toggleStockFields() {
        const controla = document.querySelector('input[name="controla_stock"]:checked').value;
        if (controla === '1') {
            seccionStock.style.display = 'grid';
        } else {
            seccionStock.style.display = 'none';
            inputStockActual.value = 0;
            inputStockMinimo.value = 0;
        }
    }

    radioControlaStock.forEach(radio => {
        radio.addEventListener('change', toggleStockFields);
    });

    // Ejecutar al inicio
    toggleStockFields();
});
</script>

<?= $this->endSection(); ?>
