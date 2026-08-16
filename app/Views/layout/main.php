<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? 'BAR MANAGER'; ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0b0f19;
            --card-bg: #151c2c;
            --border-color: rgba(255, 255, 255, 0.08);
            --primary: #f59e0b;
            --primary-hover: #d97706;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --danger: #ef4444;
            --success: #10b981;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background: rgba(21, 28, 44, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-nav-info {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .user-badge {
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.25);
            color: var(--primary);
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .btn-logout {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 0.45rem 1rem;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            background: var(--danger);
            color: #fff;
        }

        .main-container {
            flex: 1;
            max-width: 1200px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.75rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: 700;
        }

        .btn-action {
            background: var(--primary);
            color: #000;
            padding: 0.6rem 1.25rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .btn-action:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .alert {
            padding: 0.9rem 1.25rem;
            border-radius: 10px;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        th, td {
            padding: 1rem 0.85rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-status {
            padding: 0.25rem 0.65rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-activo {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-inactivo {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .btn-sm {
            padding: 0.35rem 0.65rem;
            font-size: 0.8rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
            margin-right: 0.25rem;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .btn-warning {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.4);
        }

        .btn-warning:hover {
            background: var(--primary);
            color: #000;
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="<?= site_url('usuarios'); ?>" class="navbar-brand">
            <span>🍺</span> BAR MANAGER
        </a>
        <div class="user-nav-info">
            <span>👤 <?= session()->get('nombre'); ?> (<strong><?= session()->get('usuario'); ?></strong>)</span>
            <span class="user-badge"><?= session()->get('rol'); ?></span>
            <a href="<?= site_url('logout'); ?>" class="btn-logout">Cerrar Sesión</a>
        </div>
    </nav>

    <main class="main-container">
        <?= $this->renderSection('content'); ?>
    </main>

</body>
</html>
