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

        <div id="seccionManejaUnidades" class="form-group" style="display: none;">
            <label class="checkbox-item-custom">
                <input type="checkbox" name="maneja_unidades" id="maneja_unidades" value="1" <?= old('maneja_unidades') == '1' ? 'checked' : ''; ?>>
                <span class="checkbox-label">Permitir venta por unidades sueltas</span>
            </label>
        </div>

        <div id="seccionUnidades" style="display: none;">
            <div class="form-grid-2">
                <div class="form-group">
                    <label for="precio_unidad" class="form-label">Precio por Unidad Suelta (S/)</label>
                    <input type="number" id="precio_unidad" name="precio_unidad" step="0.01" min="0" value="<?= old('precio_unidad'); ?>" class="form-input-custom" placeholder="Ej: 2.00">
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Precio que se aplicará cuando se venda una unidad suelta.</p>
                </div>
                <div class="form-group">
                    <label for="unidades_por_caja" class="form-label">Unidades por caja/cajetilla</label>
                    <input type="number" id="unidades_por_caja" name="unidades_por_caja" value="<?= old('unidades_por_caja', '0'); ?>" class="form-input-custom" placeholder="Ej: 20">
                </div>
            </div>

            <div class="form-group">
                <label for="stock_unidades" class="form-label">Stock Inicial de Unidades</label>
                <input type="number" id="stock_unidades" name="stock_unidades" value="<?= old('stock_unidades', '0'); ?>" class="form-input-custom" placeholder="0">
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Cantidad inicial de unidades individuales disponibles para venta.</p>
            </div>
        </div>

        <div id="seccionStock" class="form-grid-2">
            <div class="form-group">
                <label for="stock_actual" id="label_stock_actual" class="form-label">Stock Inicial</label>
                <input type="number" id="stock_actual" name="stock_actual" value="<?= old('stock_actual', '0'); ?>" class="form-input-custom" placeholder="0">
                <p id="help_stock_actual" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Cantidad inicial disponible.</p>
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
    /* Estilos específicos de esta vista si los hubiera */
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radioControlaStock = document.querySelectorAll('input[name="controla_stock"]');
    const seccionStock = document.getElementById('seccionStock');
    const seccionManejaUnidades = document.getElementById('seccionManejaUnidades');
    const seccionUnidades = document.getElementById('seccionUnidades');
    const manejaUnidadesCheckbox = document.getElementById('maneja_unidades');
    const inputStockActual = document.getElementById('stock_actual');
    const inputStockMinimo = document.getElementById('stock_minimo');
    const inputUnidadesPorCaja = document.getElementById('unidades_por_caja');
    const inputStockUnidades = document.getElementById('stock_unidades');
    const inputPrecioUnidad = document.getElementById('precio_unidad');
    const labelStockActual = document.getElementById('label_stock_actual');
    const helpStockActual = document.getElementById('help_stock_actual');

    function toggleStockFields() {
        const controla = document.querySelector('input[name="controla_stock"]:checked').value;
        if (controla === '1') {
            seccionStock.style.display = 'grid';
            seccionManejaUnidades.style.display = 'block';
            toggleUnidadesSection();
        } else {
            seccionStock.style.display = 'none';
            seccionManejaUnidades.style.display = 'none';
            seccionUnidades.style.display = 'none';
            inputStockActual.value = 0;
            inputStockMinimo.value = 0;
            manejaUnidadesCheckbox.checked = false;
            inputUnidadesPorCaja.value = 0;
            inputStockUnidades.value = 0;
            inputPrecioUnidad.value = 0;
        }
    }

    function toggleUnidadesSection() {
        if (manejaUnidadesCheckbox.checked && document.querySelector('input[name="controla_stock"]:checked').value === '1') {
            seccionUnidades.style.display = 'block';
            labelStockActual.textContent = 'Stock Inicial (Cajas/Paquetes Cerrados)';
            helpStockActual.textContent = 'Cantidad de cajas, cajetillas o botellas sin abrir disponibles';
        } else {
            seccionUnidades.style.display = 'none';
            labelStockActual.textContent = 'Stock Inicial';
            helpStockActual.textContent = 'Cantidad inicial disponible.';
            inputUnidadesPorCaja.value = 0;
            inputStockUnidades.value = 0;
            inputPrecioUnidad.value = 0;
        }
    }

    radioControlaStock.forEach(radio => {
        radio.addEventListener('change', toggleStockFields);
    });

    manejaUnidadesCheckbox.addEventListener('change', toggleUnidadesSection);

    // Ejecutar al inicio
    toggleStockFields();
    toggleUnidadesSection();
});
</script>

<?= $this->endSection(); ?>
