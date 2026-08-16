<header class="topbar">
    <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebarToggle" title="Abrir menú">
            ☰
        </button>
        <h1 class="page-title"><?= $titulo ?? 'Dashboard'; ?></h1>
    </div>

    <div class="topbar-right">
        <!-- Selector de Tema Global -->
        <button class="theme-toggle-btn" id="themeToggleBtn" title="Cambiar tema claro/oscuro">
            <span id="themeIcon">🌙</span>
            <span id="themeText">Tema Oscuro</span>
        </button>

        <!-- Perfil del Usuario Autenticado -->
        <div class="user-profile">
            <div class="user-avatar">
                <?= strtoupper(substr(session()->get('nombre') ?? 'U', 0, 1)); ?>
            </div>
            <div class="user-info-text">
                <div class="user-name"><?= esc(session()->get('nombre')); ?></div>
                <span class="user-role-badge"><?= esc(session()->get('rol')); ?></span>
            </div>
        </div>

        <a href="<?= site_url('logout'); ?>" class="btn-logout" title="Cerrar sesión">
            Cerrar Sesión
        </a>
    </div>
</header>
