<?= $this->extend('layout/app_layout'); ?>

<?= $this->section('content'); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">✏️ Editar Producto</h1>
        <p class="page-subtitle">Modifica los detalles del producto: <strong><?= esc($producto['nombre']); ?></strong></p>
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

    <form action="<?= site_url('productos/actualizar/' . $producto['id']); ?>" method="POST" autocomplete="off" class="modern-form">
        <?= csrf_field(); ?>

        <div class="form-group">
            <label for="nombre" class="form-label">Nombre del Producto <span class="text-danger">*</span></label>
            <input type="text" id="nombre" name="nombre" value="<?= old('nombre', $producto['nombre']); ?>" required maxlength="100" class="form-input-custom" placeholder="Ej: Pilsen Callao 630ml">
        </div>

        <div class="form-group">
            <label for="codigo" class="form-label">Código (SKU/Barras)</label>
            <input type="text" id="codigo" name="codigo" value="<?= old('codigo', $producto['codigo']); ?>" maxlength="50" class="form-input-custom" placeholder="Ej: PILS-001">
        </div>

        <div class="form-group">
            <label for="categoria_id" class="form-label">Categoría <span class="text-danger">*</span></label>
            <select id="categoria_id" name="categoria_id" required class="form-input-custom">
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id']; ?>" <?= old('categoria_id', $producto['categoria_id']) == $cat['id'] ? 'selected' : ''; ?>>
                        <?= esc($cat['nombre']); ?> <?= $cat['estado'] === 'INACTIVO' ? '(INACTIVA)' : ''; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="precio_venta" class="form-label">Precio de Venta (S/) <span class="text-danger">*</span></label>
                <input type="number" id="precio_venta" name="precio_venta" step="0.01" value="<?= old('precio_venta', $producto['precio_venta']); ?>" required class="form-input-custom" placeholder="0.00">
            </div>

            <div class="form-group">
                <label for="costo" class="form-label">Costo (S/) <span class="text-muted">(Opcional)</span></label>
                <input type="number" id="costo" name="costo" step="0.01" value="<?= old('costo', $producto['costo']); ?>" class="form-input-custom" placeholder="0.00">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Control de inventario</label>
            <div style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 10px; padding: 0.75rem 1rem; color: var(--text-muted); font-weight: 600;">
                <?= $producto['controla_stock'] == '1' ? '📦 Sí controla inventario' : '🚫 No controla inventario'; ?>
                <input type="hidden" name="controla_stock" value="<?= $producto['controla_stock']; ?>">
            </div>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Esta opción no se puede cambiar después de la creación.</p>
        </div>

        <?php if ($producto['controla_stock'] == '1'): ?>
        <div class="form-group">
            <label class="form-label">Manejo de unidades sueltas</label>
            <div style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 10px; padding: 0.75rem 1rem; color: var(--text-muted); font-weight: 600;">
                <?= $producto['maneja_unidades'] == '1' ? '✅ Permite venta por unidades sueltas' : '❌ No permite venta por unidades sueltas'; ?>
                <input type="hidden" name="maneja_unidades" value="<?= $producto['maneja_unidades']; ?>">
            </div>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Esta opción no se puede cambiar después de la creación.</p>
        </div>

        <?php if ($producto['maneja_unidades'] == '1'): ?>
        <div class="form-group">
            <label for="precio_unidad" class="form-label">Precio por Unidad Suelta (S/)</label>
            <input type="number" id="precio_unidad" name="precio_unidad" step="0.01" min="0" value="<?= old('precio_unidad', $producto['precio_unidad'] ?? 0); ?>" class="form-input-custom" placeholder="Ej: 2.00">
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Precio que se aplicará cuando se venda una unidad suelta.</p>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="unidades_por_caja" class="form-label">Unidades por caja/cajetilla</label>
                <input type="number" id="unidades_por_caja" name="unidades_por_caja" value="<?= old('unidades_por_caja', $producto['unidades_por_caja']); ?>" class="form-input-custom" placeholder="Ej: 20">
                <p style="font-size: 0.75rem; color: var(--text-muted);">Cantidad de unidades que vienen en una caja cerrada.</p>
            </div>

            <div class="form-group">
                <label class="form-label">Stock Actual de Unidades</label>
                <div style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 10px; padding: 0.75rem 1rem; color: var(--text-primary); font-weight: 700;">
                    <?= $producto['stock_unidades']; ?>
                </div>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Cantidad de unidades individuales disponibles para venta.</p>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label"><?= $producto['maneja_unidades'] == '1' ? 'Stock Actual (Cajas/Paquetes Cerrados)' : 'Stock Actual'; ?></label>
                <div style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 10px; padding: 0.75rem 1rem; color: var(--text-primary); font-weight: 700;">
                    <?= $producto['stock_actual']; ?>
                </div>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                    <?= $producto['maneja_unidades'] == '1' ? 'Cantidad de cajas, cajetillas o botellas sin abrir disponibles.' : 'Cantidad actual disponible.'; ?>
                </p>
            </div>

            <div class="form-group">
                <label for="stock_minimo" class="form-label">Stock Mínimo (Alerta)</label>
                <input type="number" id="stock_minimo" name="stock_minimo" value="<?= old('stock_minimo', $producto['stock_minimo']); ?>" class="form-input-custom" placeholder="0">
            </div>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="descripcion" class="form-label">Descripción <span class="text-muted">(Opcional)</span></label>
            <textarea id="descripcion" name="descripcion" class="form-input-custom textarea-custom" placeholder="Información adicional del producto..."><?= old('descripcion', $producto['descripcion']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="estado" class="form-label">Estado</label>
            <select id="estado" name="estado" required class="form-input-custom">
                <option value="ACTIVO" <?= old('estado', $producto['estado']) === 'ACTIVO' ? 'selected' : ''; ?>>ACTIVO</option>
                <option value="INACTIVO" <?= old('estado', $producto['estado']) === 'INACTIVO' ? 'selected' : ''; ?>>INACTIVO</option>
            </select>
        </div>

        <div class="form-actions-full">
            <button type="submit" class="btn-primary-full">
                Actualizar Producto
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

<?= $this->endSection(); ?>
