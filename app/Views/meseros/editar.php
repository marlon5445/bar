<?= $this->extend('layout/app_layout'); ?>

<?= $this->section('content'); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">👨‍🍳 Editar Mesero</h1>
        <p class="page-subtitle">Modifica la información del mesero</p>
    </div>
    <div class="page-header-actions">
        <a href="<?= site_url('meseros'); ?>" class="btn-secondary-custom">
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

    <form action="<?= site_url('meseros/actualizar/' . $mesero['id']); ?>" method="POST" autocomplete="off" class="modern-form">
        <?= csrf_field(); ?>

        <div class="form-group">
            <label for="nombre" class="form-label">Nombre del Mesero</label>
            <input type="text" id="nombre" name="nombre" value="<?= old('nombre', $mesero['nombre']); ?>" required maxlength="100" class="form-input-custom" placeholder="Ej: Juan Pérez">
        </div>

        <div class="form-group">
            <label for="telefono" class="form-label">Teléfono <span class="text-muted">(Opcional)</span></label>
            <input type="text" id="telefono" name="telefono" value="<?= old('telefono', $mesero['telefono']); ?>" maxlength="20" class="form-input-custom" placeholder="Ej: 987654321">
        </div>

        <div class="form-group">
            <label for="estado" class="form-label">Estado</label>
            <select id="estado" name="estado" required class="form-input-custom">
                <option value="ACTIVO" <?= old('estado', $mesero['estado']) === 'ACTIVO' ? 'selected' : ''; ?>>ACTIVO</option>
                <option value="INACTIVO" <?= old('estado', $mesero['estado']) === 'INACTIVO' ? 'selected' : ''; ?>>INACTIVO</option>
            </select>
        </div>

        <div class="form-actions-full">
            <button type="submit" class="btn-primary-full">
                Actualizar Mesero
            </button>
        </div>
    </form>
</div>

<style>
    .card-form-center {
        max-width: 600px;
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
    }
    .form-input-custom:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px var(--accent-glow);
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
    @media (max-width: 768px) {
        .card-form-center {
            padding: 1.25rem;
        }
    }
</style>

<?= $this->endSection(); ?>
