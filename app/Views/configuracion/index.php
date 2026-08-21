<?= $this->extend('layout/app_layout'); ?>

<?= $this->section('content'); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">⚙️ Configuración del Sistema</h1>
        <p class="page-subtitle">Personaliza la apariencia y datos básicos de tu negocio.</p>
    </div>
</div>

<div class="card card-form-center">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alerta-exito" style="padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; background: var(--success-bg); color: var(--success); border: 1px solid var(--success);">
            <span>✅</span> <?= session()->getFlashdata('success'); ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alerta-error" style="padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; background: var(--danger-bg); color: var(--danger); border: 1px solid var(--danger);">
            <span>⚠️</span> <?= session()->getFlashdata('error'); ?>
        </div>
    <?php endif; ?>

    <form action="<?= site_url('configuracion/guardar'); ?>" method="POST" enctype="multipart/form-data" autocomplete="off" class="modern-form">
        <?= csrf_field(); ?>

        <div class="form-group">
            <label for="nombre_negocio" class="form-label">Nombre del Negocio <span class="text-danger">*</span></label>
            <input type="text" id="nombre_negocio" name="nombre_negocio" value="<?= old('nombre_negocio', $config['nombre_negocio']); ?>" required maxlength="100" class="form-input-custom" placeholder="Ej: Mi Bar Favorito">
        </div>

        <div class="form-group">
            <label for="logo" class="form-label">Logo del Negocio</label>
            <div class="logo-preview-container" style="margin-bottom: 1rem; text-align: center; background: var(--bg-body); padding: 2rem; border-radius: 15px; border: 2px dashed var(--border-color);">
                <?php if ($config['logo']): ?>
                    <img src="<?= base_url($config['logo']); ?>" alt="Logo actual" id="img-preview" style="max-height: 150px; border-radius: 10px; box-shadow: var(--shadow);">
                <?php else: ?>
                    <div id="logo-placeholder" style="font-size: 3rem;">🍺</div>
                    <img src="" alt="Vista previa" id="img-preview" style="max-height: 150px; border-radius: 10px; box-shadow: var(--shadow); display: none;">
                <?php endif; ?>
            </div>
            <input type="file" id="logo" name="logo" accept="image/*" class="form-input-custom" onchange="previewImage(this)">
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem;">Se recomienda una imagen cuadrada con fondo transparente (PNG).</p>
        </div>

        <div class="form-actions-full">
            <button type="submit" class="btn-primary-full">
                <span>💾</span> Guardar Configuración
            </button>
        </div>
    </form>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('img-preview');
    const placeholder = document.getElementById('logo-placeholder');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'inline-block';
            if (placeholder) placeholder.style.display = 'none';
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<style>
    .card-form-center {
        max-width: 600px;
        margin: 0 auto;
    }
    .btn-primary-full {
        width: 100%;
        padding: 1rem;
        background: var(--accent);
        color: #000;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    .btn-primary-full:hover {
        background: var(--accent-hover);
        transform: translateY(-2px);
    }
</style>

<?= $this->endSection(); ?>
