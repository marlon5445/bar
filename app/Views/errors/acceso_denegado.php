<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado - BAR MANAGER</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #090d16;
            --card-bg: rgba(26, 32, 48, 0.9);
            --border-color: rgba(239, 68, 68, 0.3);
            --danger: #ef4444;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(at 50% 0%, rgba(239, 68, 68, 0.15) 0px, transparent 60%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            text-align: center;
        }

        .error-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 3rem 2rem;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(239, 68, 68, 0.25);
        }

        .icon-lock {
            font-size: 4rem;
            margin-bottom: 1rem;
            display: inline-block;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }

        .error-code {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.25rem;
            color: var(--danger);
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 0.5rem;
        }

        .error-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-main);
            line-height: 1.3;
        }

        .error-description {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 2rem;
            line-height: 1.5;
        }

        .btn-back {
            display: inline-block;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: #fff;
            padding: 0.85rem 1.75rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.6);
        }
    </style>
</head>
<body>

    <div class="error-card">
        <div class="icon-lock">🚫</div>
        <div class="error-code">ERROR 403</div>
        <h1 class="error-title">NO TIENES PERMISOS PARA REALIZAR ESTA ACCIÓN</h1>
        <p class="error-description">
            Tu rol actual (<strong><?= session()->get('rol') ?? 'INVITADO'; ?></strong>) no cuenta con las atribuciones requeridas para acceder a este módulo.
        </p>

        <?php if (session()->get('rol') === 'ADMIN'): ?>
            <a href="<?= site_url('usuarios'); ?>" class="btn-back">Volver a Gestión de Usuarios</a>
        <?php else: ?>
            <a href="<?= site_url('logout'); ?>" class="btn-back">Cerrar Sesión / Ir al Inicio</a>
        <?php endif; ?>
    </div>

</body>
</html>
