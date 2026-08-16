<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BAR MANAGER</title>
    <!-- CSS del Sistema de Diseño BAR MANAGER -->
    <link rel="stylesheet" href="<?= base_url('css/index.css'); ?>">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            min-height: 100vh;
        }

        .login-wrapper {
            width: 100%;
            max-width: 440px;
            position: relative;
        }

        .login-header-tools {
            position: absolute;
            top: -45px;
            right: 0;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-logo {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2.25rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
        }

        .brand-tagline {
            color: var(--text-muted);
            font-size: 0.95rem;
            font-weight: 400;
        }

        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2.5rem 2rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .card-title {
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
            color: var(--text-primary);
        }

        .alert {
            padding: 0.85rem 1rem;
            border-radius: 12px;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-danger {
            background: var(--danger-bg);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--danger);
        }

        .alert-success {
            background: var(--success-bg);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--success);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 0.85rem 1rem;
            font-size: 0.95rem;
            color: var(--text-primary);
            font-family: inherit;
            transition: var(--transition);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px var(--accent-glow);
        }

        .btn-submit {
            width: 100%;
            background: var(--accent);
            border: none;
            border-radius: 12px;
            padding: 0.95rem;
            font-size: 1rem;
            font-weight: 700;
            color: #000;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 15px var(--accent-glow);
            margin-top: 0.75rem;
        }

        .btn-submit:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
        }

        .login-footer {
            margin-top: 1.75rem;
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .badge-role {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            background: var(--bg-body);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            margin: 0 0.15rem;
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <!-- Selector de Tema en el Login -->
        <div class="login-header-tools">
            <button class="theme-toggle-btn" id="themeToggleBtn" title="Cambiar tema claro/oscuro">
                <span id="themeIcon">🌙</span>
                <span id="themeText">Tema Oscuro</span>
            </button>
        </div>

        <div class="brand-header">
            <div class="brand-logo">
                <span>🍺</span> BAR MANAGER
            </div>
            <p class="brand-tagline">"El control total de tu bar"</p>
        </div>

        <div class="login-card">
            <h2 class="card-title">Iniciar Sesión</h2>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger">
                    <span>⚠️</span> <?= session()->getFlashdata('error'); ?>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success">
                    <span>✅</span> <?= session()->getFlashdata('success'); ?>
                </div>
            <?php endif; ?>

            <form action="<?= site_url('login'); ?>" method="POST" autocomplete="off">
                <?= csrf_field(); ?>

                <div class="form-group">
                    <label class="form-label" for="usuario">Usuario del Sistema</label>
                    <input type="text" id="usuario" name="usuario" class="form-input" placeholder="Ej: admin o cajero" value="<?= old('usuario'); ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn-submit">
                    Ingresar al Sistema
                </button>
            </form>

            <div class="login-footer">
                Acceso restringido para <span class="badge-role">ADMIN</span> y <span class="badge-role">CAJERO</span>
            </div>
        </div>
    </div>

    <!-- JS Global para alternar Tema en el Login -->
    <script src="<?= base_url('js/app.js'); ?>"></script>
</body>
</html>
