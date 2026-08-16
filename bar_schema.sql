-- ==================================================
-- BASE DE DATOS: BAR MANAGER
-- SCRIPT DE ESTRUCTURA Y DATOS DE DEMOSTRACIÓN
-- ==================================================

CREATE DATABASE IF NOT EXISTS `bar` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `bar`;

-- Desactivar verificación de claves foráneas durante la creación
SET FOREIGN_KEY_CHECKS = 0;

-- Eliminar tablas existentes
DROP TABLE IF EXISTS `rol_permisos`;
DROP TABLE IF EXISTS `permisos`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `pagos_fiado`;
DROP TABLE IF EXISTS `fiados`;
DROP TABLE IF EXISTS `movimientos_stock`;
DROP TABLE IF EXISTS `detalle_ventas`;
DROP TABLE IF EXISTS `venta_detalle`;
DROP TABLE IF EXISTS `ventas`;
DROP TABLE IF EXISTS `promocion_detalle`;
DROP TABLE IF EXISTS `promociones`;
DROP TABLE IF EXISTS `detalle_compras`;
DROP TABLE IF EXISTS `compra_detalle`;
DROP TABLE IF EXISTS `compras`;
DROP TABLE IF EXISTS `productos`;
DROP TABLE IF EXISTS `categorias`;
DROP TABLE IF EXISTS `clientes`;
DROP TABLE IF EXISTS `meseros`;
DROP TABLE IF EXISTS `proveedores`;
DROP TABLE IF EXISTS `usuarios`;

-- --------------------------------------------------
-- 0. TABLA: proveedores
-- --------------------------------------------------
CREATE TABLE `proveedores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(150) NOT NULL,
  `ruc` VARCHAR(20) NULL,
  `telefono` VARCHAR(20) NULL,
  `direccion` VARCHAR(255) NULL,
  `estado` ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_proveedores_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------
-- 1. TABLA: roles
-- --------------------------------------------------
CREATE TABLE `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(50) NOT NULL UNIQUE,
  `descripcion` VARCHAR(255) NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------
-- 2. TABLA: permisos
-- --------------------------------------------------
CREATE TABLE `permisos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL UNIQUE,
  `descripcion` VARCHAR(255) NULL,
  `modulo` VARCHAR(50) NOT NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------
-- 3. TABLA: rol_permisos
-- --------------------------------------------------
CREATE TABLE `rol_permisos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rol_id` INT NOT NULL,
  `permiso_id` INT NOT NULL,
  INDEX `idx_rol_permisos_rol` (`rol_id`),
  INDEX `idx_rol_permisos_permiso` (`permiso_id`),
  CONSTRAINT `fk_rol_permisos_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rol_permisos_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------
-- 4. TABLA: usuarios
-- --------------------------------------------------
CREATE TABLE `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `usuario` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `rol` ENUM('ADMIN', 'CAJERO') NOT NULL DEFAULT 'CAJERO',
  `estado` ENUM('ACTIVO', 'INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_usuarios_rol` (`rol`),
  INDEX `idx_usuarios_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------
-- 5. TABLA: meseros
-- --------------------------------------------------
CREATE TABLE `meseros` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `telefono` VARCHAR(20) NULL,
  `estado` ENUM('ACTIVO', 'INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_meseros_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------
-- 6. TABLA: categorias
-- --------------------------------------------------
CREATE TABLE `categorias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT NULL,
  `estado` ENUM('ACTIVO', 'INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_categorias_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------
-- 7. TABLA: productos
-- --------------------------------------------------
CREATE TABLE `productos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `categoria_id` INT NOT NULL,
  `codigo` VARCHAR(50) NULL UNIQUE,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT NULL,
  `precio_venta` DECIMAL(10,2) NOT NULL,
  `costo` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `stock_actual` INT NOT NULL DEFAULT 0,
  `stock_minimo` INT NOT NULL DEFAULT 0,
  `controla_stock` TINYINT(1) NOT NULL DEFAULT 1,
  `estado` ENUM('ACTIVO', 'INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_productos_categoria` (`categoria_id`),
  INDEX `idx_productos_estado` (`estado`),
  CONSTRAINT `fk_productos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------
-- 8. TABLA: promociones
-- --------------------------------------------------
CREATE TABLE `promociones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT NULL,
  `precio` DECIMAL(10,2) NOT NULL,
  `estado` ENUM('ACTIVO', 'INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_promociones_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------
-- 9. TABLA: promocion_detalle
-- --------------------------------------------------
CREATE TABLE `promocion_detalle` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `promocion_id` INT NOT NULL,
  `producto_id` INT NOT NULL,
  `cantidad` INT NOT NULL DEFAULT 1,
  INDEX `idx_promocion_det_promocion` (`promocion_id`),
  INDEX `idx_promocion_det_producto` (`producto_id`),
  CONSTRAINT `fk_promocion_det_promocion` FOREIGN KEY (`promocion_id`) REFERENCES `promociones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_promocion_det_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------
-- 10. TABLA: clientes
-- --------------------------------------------------
CREATE TABLE `clientes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `telefono` VARCHAR(20) NULL,
  `limite_credito` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `estado` ENUM('ACTIVO', 'INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_clientes_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------
-- 11. TABLA: ventas
-- --------------------------------------------------
CREATE TABLE `ventas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `mesero_id` INT NULL DEFAULT NULL,
  `cliente_id` INT NULL DEFAULT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `descuento` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL,
  `tipo_pago` ENUM('EFECTIVO', 'YAPE', 'PLIN', 'TARJETA', 'FIADO') NOT NULL,
  `estado` ENUM('COMPLETADA', 'CANCELADA', 'ANULADA', 'PENDIENTE_PAGO') NOT NULL DEFAULT 'COMPLETADA',
  `fecha_venta` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ventas_usuario` (`usuario_id`),
  INDEX `idx_ventas_mesero` (`mesero_id`),
  INDEX `idx_ventas_cliente` (`cliente_id`),
  INDEX `idx_ventas_tipo_pago` (`tipo_pago`),
  INDEX `idx_ventas_fecha` (`fecha_venta`),
  CONSTRAINT `fk_ventas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ventas_mesero` FOREIGN KEY (`mesero_id`) REFERENCES `meseros` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ventas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------
-- 12. TABLA: venta_detalle
-- --------------------------------------------------
CREATE TABLE `venta_detalle` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `venta_id` INT NOT NULL,
  `producto_id` INT NULL DEFAULT NULL,
  `promocion_id` INT NULL DEFAULT NULL,
  `cantidad` INT NOT NULL,
  `precio_unitario` DECIMAL(10,2) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  INDEX `idx_venta_det_venta` (`venta_id`),
  INDEX `idx_venta_det_producto` (`producto_id`),
  INDEX `idx_venta_det_promocion` (`promocion_id`),
  CONSTRAINT `fk_venta_det_venta` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_venta_det_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_venta_det_promocion` FOREIGN KEY (`promocion_id`) REFERENCES `promociones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------
-- 13. TABLA: movimientos_stock
-- --------------------------------------------------
CREATE TABLE `movimientos_stock` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `producto_id` INT NOT NULL,
  `tipo_movimiento` ENUM('COMPRA', 'VENTA', 'MERMA', 'AJUSTE') NOT NULL,
  `cantidad` INT NOT NULL,
  `stock_anterior` INT NOT NULL,
  `stock_posterior` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `referencia_id` INT NULL DEFAULT NULL,
  `observacion` TEXT NULL,
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_mov_stock_producto` (`producto_id`),
  INDEX `idx_mov_stock_usuario` (`usuario_id`),
  INDEX `idx_mov_stock_tipo` (`tipo_movimiento`),
  INDEX `idx_mov_stock_fecha` (`fecha`),
  CONSTRAINT `fk_mov_stock_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_mov_stock_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------
-- 14. TABLA: fiados
-- --------------------------------------------------
CREATE TABLE `fiados` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cliente_id` INT NOT NULL,
  `venta_id` INT NOT NULL,
  `monto` DECIMAL(10,2) NOT NULL,
  `saldo` DECIMAL(10,2) NOT NULL,
  `observacion` VARCHAR(255) NULL,
  `estado` ENUM('PENDIENTE', 'PAGADO_PARCIAL', 'PAGADO', 'CANCELADO') NOT NULL DEFAULT 'PENDIENTE',
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_fiados_cliente` (`cliente_id`),
  INDEX `idx_fiados_venta` (`venta_id`),
  INDEX `idx_fiados_estado` (`estado`),
  CONSTRAINT `fk_fiados_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fiados_venta` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------
-- 15. TABLA: pagos_fiado
-- --------------------------------------------------
CREATE TABLE `pagos_fiado` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cliente_id` INT NOT NULL,
  `fiado_id` INT NULL DEFAULT NULL,
  `usuario_id` INT NOT NULL,
  `monto` DECIMAL(10,2) NOT NULL,
  `tipo_pago` ENUM('EFECTIVO', 'YAPE', 'PLIN', 'TARJETA') NOT NULL,
  `observacion` VARCHAR(255) NULL,
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pagos_fiado_cliente` (`cliente_id`),
  INDEX `idx_pagos_fiado_fiado` (`fiado_id`),
  INDEX `idx_pagos_fiado_usuario` (`usuario_id`),
  CONSTRAINT `fk_pagos_fiado_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pagos_fiado_fiado` FOREIGN KEY (`fiado_id`) REFERENCES `fiados` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pagos_fiado_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------
-- 16. TABLA: compras
-- --------------------------------------------------
CREATE TABLE `compras` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `proveedor_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `total` DECIMAL(10,2) NOT NULL,
  `observacion` TEXT NULL,
  `estado` ENUM('COMPLETADA','ANULADA') NOT NULL DEFAULT 'COMPLETADA',
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_compras_proveedor` (`proveedor_id`),
  INDEX `idx_compras_usuario` (`usuario_id`),
  INDEX `idx_compras_estado` (`estado`),
  INDEX `idx_compras_fecha` (`fecha`),
  CONSTRAINT `fk_compras_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_compras_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------
-- 17. TABLA: compra_detalle
-- --------------------------------------------------
CREATE TABLE `compra_detalle` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `compra_id` INT NOT NULL,
  `producto_id` INT NOT NULL,
  `cantidad` INT NOT NULL,
  `costo_unitario` DECIMAL(10,2) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  INDEX `idx_compra_det_compra` (`compra_id`),
  INDEX `idx_compra_det_producto` (`producto_id`),
  CONSTRAINT `fk_compra_det_compra` FOREIGN KEY (`compra_id`) REFERENCES `compras` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_compra_det_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ==================================================
-- DATOS DE DEMOSTRACIÓN Y PERMISOS
-- ==================================================

-- 1. Roles
INSERT INTO `roles` (`id`, `nombre`, `descripcion`) VALUES
(1, 'ADMIN', 'Administrador Principal con acceso total al sistema'),
(2, 'CAJERO', 'Cajero encargado del punto de venta y operaciones de caja');

-- 2. Permisos del Sistema
INSERT INTO `permisos` (`id`, `nombre`, `descripcion`, `modulo`) VALUES
(1, 'USUARIOS_VER', 'Ver listado de usuarios del sistema', 'USUARIOS'),
(2, 'USUARIOS_CREAR', 'Crear nuevos usuarios del sistema', 'USUARIOS'),
(3, 'USUARIOS_EDITAR', 'Editar datos de usuarios del sistema', 'USUARIOS'),
(4, 'USUARIOS_CAMBIAR_ESTADO', 'Activar o desactivar usuarios', 'USUARIOS'),
(5, 'USUARIOS_CAMBIAR_PASSWORD', 'Cambiar contraseñas de usuarios', 'USUARIOS'),
(6, 'VENTAS_VER', 'Ver ventas realizadas', 'VENTAS'),
(7, 'VENTAS_CREAR', 'Registrar nuevas ventas', 'VENTAS'),
(8, 'VENTAS_ANULAR', 'Anular ventas registradas', 'VENTAS'),
(9, 'INVENTARIO_VER', 'Ver el estado del inventario', 'INVENTARIO'),
(10, 'INVENTARIO_CREAR', 'Registrar ingresos de mercadería', 'INVENTARIO'),
(11, 'INVENTARIO_EDITAR', 'Editar ítems del inventario', 'INVENTARIO'),
(12, 'INVENTARIO_AJUSTAR', 'Realizar ajustes de inventario', 'INVENTARIO'),
(13, 'FIADOS_VER', 'Ver cuentas fiadas de clientes', 'FIADOS'),
(14, 'FIADOS_CREAR', 'Registrar ventas fiadas', 'FIADOS'),
(15, 'FIADOS_PAGAR', 'Registrar pagos de fiados', 'FIADOS'),
(16, 'PRODUCTOS_VER', 'Ver catálogo de productos', 'PRODUCTOS'),
(17, 'PRODUCTOS_CREAR', 'Crear nuevos productos', 'PRODUCTOS'),
(18, 'PRODUCTOS_EDITAR', 'Editar productos', 'PRODUCTOS'),
(19, 'MESEROS_VER', 'Ver lista de meseros', 'MESEROS'),
(20, 'MESEROS_CREAR', 'Registrar nuevos meseros', 'MESEROS'),
(21, 'MESEROS_EDITAR', 'Editar datos de meseros', 'MESEROS'),
(22, 'CLIENTES_VER', 'Ver lista de clientes', 'CLIENTES'),
(23, 'CLIENTES_CREAR', 'Registrar nuevos clientes', 'CLIENTES'),
(24, 'CLIENTES_EDITAR', 'Editar clientes', 'CLIENTES'),
(25, 'REPORTES_VER', 'Ver reportes generales y del sistema', 'REPORTES'),
(26, 'COMPRAS_VER', 'Ver compras realizadas', 'COMPRAS'),
(27, 'COMPRAS_CREAR', 'Registrar nuevas compras', 'COMPRAS'),
(28, 'COMPRAS_ANULAR', 'Anular compras registradas', 'COMPRAS'),
(29, 'PROVEEDORES_VER', 'Ver lista de proveedores', 'PROVEEDORES'),
(30, 'PROVEEDORES_CREAR', 'Registrar nuevos proveedores', 'PROVEEDORES'),
(31, 'PROVEEDORES_EDITAR', 'Editar proveedores', 'PROVEEDORES');

-- 3. Asignación de Permisos a Roles (rol_permisos)
-- ADMIN: Todos los permisos (IDs 1 al 31)
INSERT INTO `rol_permisos` (`rol_id`, `permiso_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5),
(1, 6), (1, 7), (1, 8), (1, 9), (1, 10),
(1, 11), (1, 12), (1, 13), (1, 14), (1, 15),
(1, 16), (1, 17), (1, 18), (1, 19), (1, 20),
(1, 21), (1, 22), (1, 23), (1, 24), (1, 25),
(1, 26), (1, 27), (1, 28), (1, 29), (1, 30), (1, 31);

-- CAJERO: Solo Ventas, Clientes, Fiados, Productos ver (SIN acceso a Usuarios)
INSERT INTO `rol_permisos` (`rol_id`, `permiso_id`) VALUES
(2, 6), (2, 7), -- VENTAS_VER, VENTAS_CREAR
(2, 13), (2, 14), (2, 15), -- FIADOS_VER, FIADOS_CREAR, FIADOS_PAGAR
(2, 16), -- PRODUCTOS_VER
(2, 19), -- MESEROS_VER
(2, 22), (2, 23); -- CLIENTES_VER, CLIENTES_CREAR

-- 4. Usuarios de Demostración (Passwords: admin123 y cajero123)
INSERT INTO `usuarios` (`id`, `nombre`, `usuario`, `password`, `rol`, `estado`) VALUES
(1, 'Administrador Principal', 'admin', '$2y$10$ubGpQ0T9mCX8FmJCb7pY..LS3CaeLzX5InbbiA1tBEfEnhJhMhD0y', 'ADMIN', 'ACTIVO'),
(2, 'Carlos Caja (Cajero)', 'cajero', '$2y$10$q17yGTVs.FSeOqZzCP7T9ObV9ODColAVPrMFSjYPZMg1twnsDMTxC', 'CAJERO', 'ACTIVO');

-- 5. Meseros (No dependen de usuarios)
INSERT INTO `meseros` (`id`, `nombre`, `telefono`, `estado`) VALUES
(1, 'Carlos', '987654321', 'ACTIVO'),
(2, 'Miguel', '987654322', 'ACTIVO'),
(3, 'José', '987654323', 'ACTIVO'),
(4, 'Luis', '987654324', 'ACTIVO');

-- 6. Categorías
INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `estado`) VALUES
(1, 'Cervezas', 'Cervezas nacionales e importadas en botella y lata', 'ACTIVO'),
(2, 'Licores', 'Botellas de Whisky, Rum, Vodka y Tequila', 'ACTIVO'),
(3, 'Tragos', 'Cocteles y tragos preparados en barra', 'ACTIVO'),
(4, 'Promociones', 'Combos y promociones especiales del bar', 'ACTIVO'),
(5, 'Cigarrillos', 'Cigarrillos por cajetilla', 'ACTIVO'),
(6, 'Gaseosas', 'Bebidas no alcohólicas y refrescos', 'ACTIVO'),
(7, 'Otros', 'Piqueos y adicionales', 'ACTIVO');

-- 7. Productos
INSERT INTO `productos` (`id`, `categoria_id`, `codigo`, `nombre`, `descripcion`, `precio_venta`, `costo`, `stock_actual`, `stock_minimo`, `controla_stock`, `estado`) VALUES
(1, 1, 'CERV-001', 'Pilsen Callao 620ml', 'Cerveza Pilsen en botella 620ml', 10.00, 6.50, 117, 24, 1, 'ACTIVO'),
(2, 1, 'CERV-002', 'Cusqueña Negra 620ml', 'Cerveza Cusqueña Negra 620ml', 12.00, 7.80, 80, 12, 1, 'ACTIVO'),
(3, 2, 'LIC-001', 'Whisky Red Label 750ml', 'Johnnie Walker Red Label 750ml', 85.00, 55.00, 14, 3, 1, 'ACTIVO'),
(4, 2, 'LIC-002', 'Ron Cartavio Solera 750ml', 'Ron Cartavio Solera 12 Años', 45.00, 28.00, 19, 5, 1, 'ACTIVO'),
(5, 3, 'TRAG-001', 'Chilcano Clásico', 'Pisco, Ginger Ale y Limón', 18.00, 6.00, 0, 0, 0, 'ACTIVO'),
(6, 3, 'TRAG-002', 'Jarra de Sangría 1.5L', 'Vino tinto, frutas y licor', 35.00, 15.00, 0, 0, 0, 'ACTIVO'),
(7, 5, 'CIG-001', 'Lucky Strike Convertible', 'Cajetilla de 20 cigarrillos', 15.00, 10.00, 50, 10, 1, 'ACTIVO'),
(8, 6, 'GAS-001', 'Coca Cola 500ml', 'Gaseosa en botella 500ml', 5.00, 2.80, 100, 20, 1, 'ACTIVO');

-- 8. Promociones
INSERT INTO `promociones` (`id`, `nombre`, `descripcion`, `precio`, `estado`) VALUES
(1, '3 Jarras S/20', 'Promoción especial de 3 Jarras de Sangría por S/ 20', 20.00, 'ACTIVO');

-- 9. Detalle de Promoción
INSERT INTO `promocion_detalle` (`id`, `promocion_id`, `producto_id`, `cantidad`) VALUES
(1, 1, 6, 3);

-- 10. Clientes
INSERT INTO `clientes` (`id`, `nombre`, `telefono`, `limite_credito`, `estado`) VALUES
(1, 'Juan Pérez', '912345678', 200.00, 'ACTIVO'),
(2, 'María Gómez', '923456789', 150.00, 'ACTIVO');

-- 11. Ventas
INSERT INTO `ventas` (`id`, `usuario_id`, `mesero_id`, `cliente_id`, `subtotal`, `descuento`, `total`, `tipo_pago`, `estado`, `fecha_venta`) VALUES
(1, 2, 1, NULL, 30.00, 0.00, 30.00, 'EFECTIVO', 'COMPLETADA', NOW() - INTERVAL 5 HOUR),
(2, 2, NULL, NULL, 18.00, 0.00, 18.00, 'YAPE', 'COMPLETADA', NOW() - INTERVAL 4 HOUR),
(3, 2, 2, NULL, 85.00, 5.00, 80.00, 'TARJETA', 'COMPLETADA', NOW() - INTERVAL 3 HOUR),
(4, 2, 3, NULL, 20.00, 0.00, 20.00, 'PLIN', 'COMPLETADA', NOW() - INTERVAL 2 HOUR),
(5, 2, 4, 1, 45.00, 0.00, 45.00, 'FIADO', 'PENDIENTE_PAGO', NOW() - INTERVAL 1 HOUR);

-- 12. Venta Detalle
INSERT INTO `venta_detalle` (`id`, `venta_id`, `producto_id`, `promocion_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 1, NULL, 3, 10.00, 30.00),
(2, 2, 5, NULL, 1, 18.00, 18.00),
(3, 3, 3, NULL, 1, 85.00, 85.00),
(4, 4, NULL, 1, 1, 20.00, 20.00),
(5, 5, 4, NULL, 1, 45.00, 45.00);

-- 13. Movimientos de Stock
INSERT INTO `movimientos_stock` (`id`, `producto_id`, `tipo_movimiento`, `cantidad`, `stock_anterior`, `stock_posterior`, `usuario_id`, `referencia_id`, `observacion`, `fecha`) VALUES
(1, 1, 'COMPRA', 120, 0, 120, 1, NULL, 'Carga inicial de inventario Pilsen 620ml', NOW() - INTERVAL 1 DAY),
(2, 2, 'COMPRA', 80, 0, 80, 1, NULL, 'Carga inicial de inventario Cusqueña Negra', NOW() - INTERVAL 1 DAY),
(3, 3, 'COMPRA', 15, 0, 15, 1, NULL, 'Carga inicial de inventario Red Label', NOW() - INTERVAL 1 DAY),
(4, 4, 'COMPRA', 20, 0, 20, 1, NULL, 'Carga inicial de inventario Ron Cartavio', NOW() - INTERVAL 1 DAY),
(5, 1, 'VENTA', 3, 120, 117, 2, 1, 'Venta #1 realizada por mesero Carlos', NOW() - INTERVAL 5 HOUR),
(6, 3, 'VENTA', 1, 15, 14, 2, 3, 'Venta #3 realizada por mesero Miguel', NOW() - INTERVAL 3 HOUR),
(7, 4, 'VENTA', 1, 20, 19, 2, 5, 'Venta #5 (Fiado Juan Pérez) realizada por mesero Luis', NOW() - INTERVAL 1 HOUR);

-- 14. Fiados
INSERT INTO `fiados` (`id`, `cliente_id`, `venta_id`, `monto`, `saldo`, `estado`, `fecha`) VALUES
(1, 1, 5, 45.00, 45.00, 'PENDIENTE', NOW() - INTERVAL 1 HOUR);

-- Reactivar verificación de claves foráneas
SET FOREIGN_KEY_CHECKS = 1;
