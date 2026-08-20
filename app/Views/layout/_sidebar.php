<?php
use App\Models\UsuarioModel;

$usuarioModel = new UsuarioModel();
$rolActual = session()->get('rol') ?? 'CAJERO';
$uri = service('uri')->getPath();

// Función helper para determinar permiso
$can = function($permiso) use ($usuarioModel, $rolActual) {
    return $usuarioModel->rolTienePermiso($rolActual, $permiso);
};
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div>
            <a href="<?= site_url('/'); ?>" class="sidebar-logo">
                <span>🍺</span> BAR MANAGER
            </a>
            <div class="sidebar-tagline">El control total de tu bar</div>
        </div>
    </div>

    <div class="sidebar-menu">
        <!-- Dashboard Principal -->
        <a href="<?= site_url('dashboard'); ?>" class="menu-item <?= ($uri === '' || $uri === 'dashboard' || $uri === 'index.php/dashboard') ? 'active' : ''; ?>">
            <span class="menu-icon">📊</span>
            <span>Dashboard</span>
        </a>

        <!-- OPERACIONES -->
        <?php if ($can('VENTAS_VER') || $can('FIADOS_VER') || $can('COMPRAS_VER')): ?>
            <div class="menu-category">Operaciones</div>

            <?php if ($can('VENTAS_VER')): ?>
                <a href="<?= site_url('ventas'); ?>" class="menu-item <?= (strpos($uri, 'ventas') !== false) ? 'active' : ''; ?>">
                    <span class="menu-icon">🍻</span>
                    <span>Ventas</span>
                </a>
            <?php endif; ?>

            <?php if ($can('COMPRAS_VER')): ?>
                <a href="<?= site_url('compras'); ?>" class="menu-item <?= (strpos($uri, 'compras') !== false) ? 'active' : ''; ?>">
                    <span class="menu-icon">🛒</span>
                    <span>Compras</span>
                </a>
            <?php endif; ?>

            <?php if ($can('FIADOS_VER')): ?>
                <a href="<?= site_url('fiados'); ?>" class="menu-item <?= (strpos($uri, 'fiados') !== false) ? 'active' : ''; ?>">
                    <span class="menu-icon">💳</span>
                    <span>Fiados</span>
                </a>
            <?php endif; ?>

        <?php endif; ?>

        <!-- CATÁLOGOS -->
        <?php if ($can('PRODUCTOS_VER') || $can('INVENTARIO_VER') || $can('MESEROS_VER') || $can('CLIENTES_VER') || $can('PROVEEDORES_VER') || $can('ADMIN')): ?>
            <div class="menu-category">Catálogos</div>



            <?php if ($can('PRODUCTOS_VER')): ?>
                <a href="<?= site_url('productos'); ?>" class="menu-item <?= (strpos($uri, 'productos') !== false) ? 'active' : ''; ?>">
                    <span class="menu-icon">🍺</span>
                    <span>Productos</span>
                </a>
                <a href="<?= site_url('promociones'); ?>" class="menu-item <?= (strpos($uri, 'promociones') !== false) ? 'active' : ''; ?>">
                    <span class="menu-icon">🔥</span>
                    <span>Promociones</span>
                </a>
            <?php endif; ?>

            <?php if ($rolActual === 'ADMIN'): ?>
                <a href="<?= site_url('categorias'); ?>" class="menu-item <?= (strpos($uri, 'categorias') !== false) ? 'active' : ''; ?>">
                    <span class="menu-icon">📁</span>
                    <span>Categorías</span>
                </a>
            <?php endif; ?>

            <?php if ($can('MESEROS_VER')): ?>
                <a href="<?= site_url('meseros'); ?>" class="menu-item <?= (strpos($uri, 'meseros') !== false) ? 'active' : ''; ?>">
                    <span class="menu-icon">👨‍🍳</span>
                    <span>Meseros</span>
                </a>
            <?php endif; ?>

            <?php if ($can('CLIENTES_VER')): ?>
                <a href="<?= site_url('clientes'); ?>" class="menu-item <?= (strpos($uri, 'clientes') !== false) ? 'active' : ''; ?>">
                    <span class="menu-icon">👥</span>
                    <span>Clientes</span>
                </a>
            <?php endif; ?>

            <?php if ($can('PROVEEDORES_VER')): ?>
                <a href="<?= site_url('proveedores'); ?>" class="menu-item <?= (strpos($uri, 'proveedores') !== false) ? 'active' : ''; ?>">
                    <span class="menu-icon">🚚</span>
                    <span>Proveedores</span>
                </a>
            <?php endif; ?>

            <?php //if ($can('INVENTARIO_VER')): ?>
                <!--<a href="#" class="menu-item module-unbuilt">
                    <span class="menu-icon">📦</span>
                    <span>Inventario</span>
                </a>-->
            <?php //endif; ?>

        <?php endif; ?>

        <!-- ANÁLISIS -->
        <?php //if ($can('REPORTES_VER')): ?>
            <!--<div class="menu-category">Análisis</div>

            <a href="#" class="menu-item module-unbuilt">
                <span class="menu-icon">📊</span>
                <span>Reportes</span>
            </a>
            <a href="#" class="menu-item module-unbuilt">
                <span class="menu-icon">📈</span>
                <span>Estadísticas</span>
            </a>
            <a href="#" class="menu-item module-unbuilt">
                <span class="menu-icon">🔮</span>
                <span>Predicciones</span>
            </a>-->
        <?php //endif; ?>

        <!-- ADMINISTRACIÓN -->
        <?php if ($can('USUARIOS_VER')): ?>
            <div class="menu-category">Administración</div>

            <a href="<?= site_url('usuarios'); ?>" class="menu-item <?= (strpos($uri, 'usuarios') !== false) ? 'active' : ''; ?>">
                <span class="menu-icon">👤</span>
                <span>Usuarios</span>
            </a>
            <a href="#" class="menu-item module-unbuilt">
                <span class="menu-icon">⚙️</span>
                <span>Configuración</span>
            </a>
        <?php endif; ?>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
