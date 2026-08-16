<?= $this->extend('layout/app_layout'); ?>

<?= $this->section('content'); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">🔑 Cambiar Contraseña</h1>
        <p class="page-subtitle">Modificando credenciales de: <strong><?= esc($usuario['usuario']); ?></strong></p>
    </div>
    <div class="page-header-actions">
        <a href="<?= site_url('usuarios'); ?>" class="btn-secondary-custom">
            Volver al listado
        </a>
    </div>
</div>

<div class="card card-form-center">
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alerta-error" style="padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <span>⚠️</span>
            <div><?= session()->getFlashdata('error'); ?></div>
        </div>
    <?php endif; ?>

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

    <form action="<?= site_url('usuarios/guardar-password'); ?>" method="POST" autocomplete="off" class="modern-form">
        <?= csrf_field(); ?>
        <input type="hidden" name="id" value="<?= $usuario['id']; ?>">

        <?php if ($usuario['id'] == session()->get('usuario_id')): ?>
            <div class="form-group">
                <label for="password_actual" class="form-label">Contraseña Actual</label>
                <input type="password" id="password_actual" name="password_actual" required class="form-input-custom" placeholder="Ingresa tu contraseña actual">
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="password_nueva" class="form-label">Nueva Contraseña</label>
            <input type="password" id="password_nueva" name="password_nueva" required minlength="6" class="form-input-custom" placeholder="Mínimo 6 caracteres">
        </div>

        <div class="form-group">
            <label for="password_confirmacion" class="form-label">Confirmar Nueva Contraseña</label>
            <input type="password" id="password_confirmacion" name="password_confirmacion" required minlength="6" class="form-input-custom" placeholder="Repite la nueva contraseña">
        </div>

        <div class="form-actions-full">
            <button type="submit" class="btn-primary-full">
                Actualizar Contraseña
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
