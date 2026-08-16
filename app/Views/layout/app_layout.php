<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? 'BAR MANAGER'; ?></title>
    <!-- Script Anti-Flicker de Tema (Aplica el tema antes del renderizado de la página) -->
    <script>
        (function () {
            var savedTheme = localStorage.getItem('bar_manager_theme') || 'light';
            if (savedTheme === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
            } else {
                document.documentElement.removeAttribute('data-theme');
            }
        })();
    </script>
    <!-- CSS del Sistema de Diseño BAR MANAGER -->
    <link rel="stylesheet" href="<?= base_url('css/index.css'); ?>">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Chart.js para Gráficos Reactivos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <div class="app-wrapper">
        <!-- Sidebar Navigation -->
        <?= $this->include('layout/_sidebar'); ?>

        <!-- Main Wrapper -->
        <div class="main-wrapper">
            <!-- Topbar Navigation -->
            <?= $this->include('layout/_topbar'); ?>

            <!-- Main Content Area -->
            <main class="content-body">
                <?= $this->renderSection('content'); ?>
            </main>

            <!-- Footer -->
            <?= $this->include('layout/_footer'); ?>
        </div>
    </div>

    <!-- JS Global para Temas y Drawer Responsive -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="<?= base_url('js/app.js'); ?>"></script>
    <?= $this->renderSection('scripts'); ?>

</body>
</html>
