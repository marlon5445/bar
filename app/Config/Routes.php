<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Rutas Públicas de Autenticación
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::procesarLogin');
$routes->get('logout', 'Auth::logout');
$routes->get('acceso-denegado', 'Auth::accesoDenegado');

// Rutas Principales Protegidas
$routes->get('/', 'Dashboard::index', ['filter' => 'auth']);
$routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth']);

// Grupo de Gestión de Usuarios (Protegido por Autenticación y Permiso USUARIOS_VER)
$routes->group('usuarios', ['filter' => ['auth', 'permission:USUARIOS_VER']], static function ($routes) {
    $routes->get('/', 'Usuarios::index');
    $routes->get('crear', 'Usuarios::crear');
    $routes->post('guardar', 'Usuarios::guardar');
    $routes->get('editar/(:num)', 'Usuarios::editar/$1');
    $routes->post('actualizar/(:num)', 'Usuarios::actualizar/$1');
    $routes->post('cambiar-estado/(:num)', 'Usuarios::cambiarEstado/$1');
    $routes->get('cambiar-password/(:num)', 'Usuarios::cambiarPassword/$1');
    $routes->post('guardar-password', 'Usuarios::guardarPassword');
});

// Grupo de Gestión de Categorías (Protegido por Autenticación y Restringido a ADMIN)
$routes->group('categorias', ['filter' => ['auth', 'permission:ADMIN']], static function ($routes) {
    $routes->get('/', 'Categorias::index');
    $routes->get('crear', 'Categorias::crear');
    $routes->post('guardar', 'Categorias::guardar');
    $routes->get('editar/(:num)', 'Categorias::editar/$1');
    $routes->post('actualizar/(:num)', 'Categorias::actualizar/$1');
    $routes->post('cambiar-estado/(:num)', 'Categorias::cambiarEstado/$1');
});

// Grupo de Gestión de Productos (Protegido por Autenticación y Permisos)
$routes->group('productos', ['filter' => ['auth', 'permission:PRODUCTOS_VER']], static function ($routes) {
    $routes->get('/', 'Productos::index');
    $routes->get('crear', 'Productos::crear', ['filter' => 'permission:PRODUCTOS_CREAR']);
    $routes->post('guardar', 'Productos::guardar', ['filter' => 'permission:PRODUCTOS_CREAR']);
    $routes->get('editar/(:num)', 'Productos::editar/$1', ['filter' => 'permission:PRODUCTOS_EDITAR']);
    $routes->post('actualizar/(:num)', 'Productos::actualizar/$1', ['filter' => 'permission:PRODUCTOS_EDITAR']);
    $routes->post('ajustar-stock', 'Productos::ajustarStock', ['filter' => 'permission:PRODUCTOS_EDITAR']);
    $routes->post('cambiar-estado/(:num)', 'Productos::cambiarEstado/$1', ['filter' => 'permission:PRODUCTOS_EDITAR']);
});

// Grupo de Gestión de Promociones (Protegido por Autenticación y Permisos similares a Productos)
$routes->group('promociones', ['filter' => ['auth', 'permission:PRODUCTOS_VER']], static function ($routes) {
    $routes->get('/', 'Promociones::index');
    $routes->get('nuevo', 'Promociones::crear', ['filter' => 'permission:PRODUCTOS_CREAR']);
    $routes->post('guardar', 'Promociones::guardar', ['filter' => 'permission:PRODUCTOS_CREAR']);
    $routes->get('editar/(:num)', 'Promociones::editar/$1', ['filter' => 'permission:PRODUCTOS_EDITAR']);
    $routes->post('actualizar/(:num)', 'Promociones::actualizar/$1', ['filter' => 'permission:PRODUCTOS_EDITAR']);
    $routes->post('cambiar-estado/(:num)', 'Promociones::cambiarEstado/$1', ['filter' => 'permission:PRODUCTOS_EDITAR']);
});

// Grupo de Gestión de Ventas (Protegido por Autenticación y Permiso VENTAS_VER)
$routes->group('ventas', ['filter' => ['auth', 'permission:VENTAS_VER']], static function ($routes) {
    $routes->get('/',            'Ventas::index');
    $routes->get('nueva',        'Ventas::index');
    $routes->get('historial',    'Ventas::historial');
    $routes->get('detalle/(:num)','Ventas::detalle/$1');
    $routes->post('procesar',    'Ventas::procesar', ['filter' => 'permission:VENTAS_CREAR']);
    $routes->post('anular/(:num)','Ventas::anular/$1',  ['filter' => 'permission:VENTAS_ANULAR']);
    $routes->get('clientes',     'Ventas::clientes');
    $routes->post('crear-cliente','Ventas::crearCliente');
});

// Grupo de Gestión de Fiados (Protegido por Autenticación y Permiso FIADOS_VER)
$routes->group('fiados', ['filter' => ['auth', 'permission:FIADOS_VER']], static function ($routes) {
    $routes->get('/',                'Fiados::index');
    $routes->get('cliente/(:num)',   'Fiados::cliente/$1');
    $routes->get('resumen/(:num)',   'Fiados::resumen/$1');
    $routes->post('pagar',           'Fiados::pagar');
    $routes->post('guardar-garantia','Fiados::guardarGarantia');
});

// Grupo de Gestión de Clientes (Protegido por Autenticación y Permiso CLIENTES_VER)
$routes->group('clientes', ['filter' => ['auth', 'permission:CLIENTES_VER']], static function ($routes) {
    $routes->get('/', 'Clientes::index');
    $routes->get('crear', 'Clientes::crear', ['filter' => 'permission:CLIENTES_CREAR']);
    $routes->post('guardar', 'Clientes::guardar', ['filter' => 'permission:CLIENTES_CREAR']);
    $routes->get('editar/(:num)', 'Clientes::editar/$1', ['filter' => 'permission:CLIENTES_EDITAR']);
    $routes->post('actualizar/(:num)', 'Clientes::actualizar/$1', ['filter' => 'permission:CLIENTES_EDITAR']);
    $routes->post('cambiar-estado/(:num)', 'Clientes::cambiarEstado/$1', ['filter' => 'permission:CLIENTES_EDITAR']);
});

// Grupo de Gestión de Meseros (Protegido por Autenticación y Permiso MESEROS_VER)
$routes->group('meseros', ['filter' => ['auth', 'permission:MESEROS_VER']], static function ($routes) {
    $routes->get('/', 'Meseros::index');
    $routes->get('crear', 'Meseros::crear', ['filter' => 'permission:MESEROS_CREAR']);
    $routes->post('guardar', 'Meseros::guardar', ['filter' => 'permission:MESEROS_CREAR']);
    $routes->get('editar/(:num)', 'Meseros::editar/$1', ['filter' => 'permission:MESEROS_EDITAR']);
    $routes->post('actualizar/(:num)', 'Meseros::actualizar/$1', ['filter' => 'permission:MESEROS_EDITAR']);
    $routes->post('cambiar-estado/(:num)', 'Meseros::cambiarEstado/$1', ['filter' => 'permission:MESEROS_EDITAR']);
});

// Grupo de Gestión de Compras (Protegido por Autenticación y Permiso COMPRAS_VER)
$routes->group('compras', ['filter' => ['auth', 'permission:COMPRAS_VER']], static function ($routes) {
    $routes->get('/',            'Compras::index');
    $routes->get('nuevo',        'Compras::nuevo',   ['filter' => 'permission:COMPRAS_CREAR']);
    $routes->post('guardar',     'Compras::guardar', ['filter' => 'permission:COMPRAS_CREAR']);
    $routes->get('detalle/(:num)','Compras::detalle/$1');
    $routes->post('anular/(:num)','Compras::anular/$1',  ['filter' => 'permission:COMPRAS_ANULAR']);
});

// Grupo de Gestión de Proveedores (Protegido por Autenticación y Permiso PROVEEDORES_VER)
$routes->group('proveedores', ['filter' => ['auth', 'permission:PROVEEDORES_VER']], static function ($routes) {
    $routes->get('/',                    'Proveedores::index');
    $routes->post('guardar',             'Proveedores::guardar',        ['filter' => 'permission:PROVEEDORES_CREAR']);
    $routes->post('actualizar/(:num)',   'Proveedores::actualizar/$1',  ['filter' => 'permission:PROVEEDORES_EDITAR']);
    $routes->post('eliminar/(:num)',     'Proveedores::eliminar/$1',    ['filter' => 'permission:PROVEEDORES_EDITAR']);
    $routes->post('cambiar-estado/(:num)','Proveedores::cambiarEstado/$1',['filter' => 'permission:PROVEEDORES_EDITAR']);
});

