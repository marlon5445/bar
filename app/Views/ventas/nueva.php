<?= $this->extend('layout/app_layout'); ?>

<?= $this->section('content'); ?>

<!-- BARRA DE NAVEGACIÓN SUPERIOR DE VENTAS -->
<div class="ventas-nav-header">
    <a href="<?= site_url('ventas/nueva'); ?>" class="ventas-nav-tab active">
        ➕ NUEVA VENTA
    </a>
    <a href="<?= site_url('ventas/historial'); ?>" class="ventas-nav-tab">
        📋 HISTORIAL DE VENTAS
    </a>
    <a href="<?= site_url('ventas/apertura'); ?>" class="ventas-nav-tab">
        📦 APERTURA DE PRODUCTOS
    </a>
</div>

<div class="pos-container">

    <!-- ÁREA IZQUIERDA: CATÁLOGO Y BÚSQUEDA -->
    <div class="pos-catalog">

        <!-- BARRA SUPERIOR DE BÚSQUEDA E INFORMACIÓN DE CAJERO -->
        <div class="pos-header-bar">
            <div class="pos-search-wrapper">
                <span class="pos-search-icon">🔍</span>
                <input type="text" id="posSearchInput" class="pos-search-input"
                    placeholder="Buscar por nombre o código (ej: Pilsen, CERV-001)..." autocomplete="off">
                <button type="button" id="posSearchClear" class="pos-search-clear" title="Limpiar búsqueda">✕</button>
            </div>

            <div class="cashier-info-pill">
                <span class="cashier-label">👤 CAJERO:</span>
                <strong><?= esc($cajero['nombre']); ?></strong>
            </div>
        </div>

        <!-- BARRA DE CATEGORÍAS (PESTAÑAS GRANDES TÁCTILES) -->
        <div class="category-scroll-container" id="categoryScrollContainer">
            <button type="button" class="category-btn active" data-category="ALL">
                <span>🍷</span> TODOS
            </button>

            <?php if (!empty($promociones)): ?>
                <button type="button" class="category-btn promo-btn" data-category="PROMO">
                    <span>🔥</span> PROMOCIONES
                </button>
            <?php endif; ?>

            <?php foreach ($categorias as $cat): ?>
                <?php
                // Asignación rápida de emojis por categoría
                $icon = '🍺';
                $nombreLower = mb_strtolower($cat['nombre']);
                if (str_contains($nombreLower, 'cerveza'))
                    $icon = '🍺';
                elseif (str_contains($nombreLower, 'licor'))
                    $icon = '🍾';
                elseif (str_contains($nombreLower, 'trago') || str_contains($nombreLower, 'coctel'))
                    $icon = '🥃';
                elseif (str_contains($nombreLower, 'cigarro') || str_contains($nombreLower, 'cigarrillo'))
                    $icon = '🚬';
                elseif (str_contains($nombreLower, 'gaseosa') || str_contains($nombreLower, 'bebida'))
                    $icon = '🥤';
                elseif (str_contains($nombreLower, 'promo'))
                    $icon = '🔥';
                else
                    $icon = '🍿';
                ?>
                <button type="button" class="category-btn" data-category="<?= $cat['id']; ?>">
                    <span><?= $icon; ?></span> <?= esc($cat['nombre']); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- GRILLA DE PRODUCTOS Y PROMOCIONES -->
        <div class="product-grid" id="productGrid">

            <!-- PROMOCIONES DESTACADAS -->
            <?php if (!empty($promociones)): ?>
                <?php foreach ($promociones as $promo): ?>
                    <div class="product-card promo-card" data-id="<?= $promo['id']; ?>" data-type="PROMO"
                        data-name="<?= esc($promo['nombre']); ?>" data-price="<?= $promo['precio']; ?>" data-category="PROMO"
                        data-icon="🔥">
                        <span class="promo-tag">🔥 Promoción</span>
                        <div class="product-card-thumb"
                            style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(239, 68, 68, 0.2));">
                            <span>🍻</span>
                        </div>
                        <div class="product-card-info">
                            <div>
                                <div class="product-title"><?= esc($promo['nombre']); ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); line-height: 1.2;">
                                    <?= esc($promo['descripcion'] ?? 'Promoción especial'); ?>
                                </div>
                            </div>
                            <div class="product-meta">
                                <span class="product-price">S/ <?= number_format($promo['precio'], 2); ?></span>
                                <span class="product-badge-stock" style="color: var(--accent);">Combo</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- PRODUCTOS INDIVIDUALES -->
            <?php foreach ($productos as $prod): ?>
                <?php
                $icon = '🍺';
                $catNombreLower = mb_strtolower($prod['categoria_nombre'] ?? '');
                if (str_contains($catNombreLower, 'cerveza'))
                    $icon = '🍺';
                elseif (str_contains($catNombreLower, 'licor'))
                    $icon = '🍾';
                elseif (str_contains($catNombreLower, 'trago'))
                    $icon = '🥃';
                elseif (str_contains($catNombreLower, 'cigarro') || str_contains($catNombreLower, 'cigarrillo'))
                    $icon = '🚬';
                elseif (str_contains($catNombreLower, 'gaseosa'))
                    $icon = '🥤';
                else
                    $icon = '🍷';
                ?>
                <div class="product-card" data-id="<?= $prod['id']; ?>" data-type="PRODUCT"
                    data-name="<?= esc($prod['nombre']); ?>" data-price="<?= $prod['precio_venta']; ?>"
                    data-unit-price="<?= $prod['precio_unidad']; ?>"
                    data-code="<?= esc($prod['codigo'] ?? ''); ?>" data-category="<?= $prod['categoria_id']; ?>"
                    data-icon="<?= $icon; ?>"
                    data-maneja-unidades="<?= $prod['maneja_unidades']; ?>"
                    data-unidades-por-caja="<?= $prod['unidades_por_caja']; ?>"
                    data-stock-unidades="<?= $prod['stock_unidades']; ?>"
                    data-stock="<?= $prod['controla_stock'] == 1 ? $prod['stock_actual'] : ''; ?>">

                    <div class="product-card-thumb">
                        <?php if (!empty($prod['imagen']) && file_exists(FCPATH . $prod['imagen'])): ?>
                            <img src="<?= base_url($prod['imagen']); ?>" alt="<?= esc($prod['nombre']); ?>">
                        <?php else: ?>
                            <span><?= $icon; ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="product-card-info">
                        <div>
                            <div class="product-title"><?= esc($prod['nombre']); ?></div>
                            <?php if (!empty($prod['codigo'])): ?>
                                <span style="font-size: 0.7rem; color: var(--text-muted); font-family: monospace;">
                                    <?= esc($prod['codigo']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="product-meta">
                            <span class="product-price">S/ <?= number_format($prod['precio_venta'], 2); ?></span>
                            <?php if ($prod['controla_stock'] == 1): ?>
                                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 2px;">
                                    <span class="product-badge-stock" title="Stock Cerrado">St: <?= $prod['stock_actual']; ?></span>
                                    <?php if ($prod['maneja_unidades'] == 1): ?>
                                        <span class="product-badge-stock" style="background: rgba(16, 185, 129, 0.1); color: var(--success); font-size: 0.65rem;" title="Stock Unidades">
                                            Un: <?= $prod['stock_unidades']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="product-badge-stock" style="color: var(--info);">Libre</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </div>

    <!-- ÁREA DERECHA: CARRITO / VENTA ACTUAL -->
    <div class="pos-cart-panel" id="posCartPanel">

        <!-- HEADER DEL CARRITO -->
        <div class="cart-header">
            <div class="cart-title">
                <span>🛒 VENTA ACTUAL</span>
                <span class="cart-badge-count" id="cartBadgeCount">0</span>
            </div>
            <button type="button" class="btn-clear-cart" id="btnClearCart" style="display: none;">
                🗑 Limpiar
            </button>
            <button type="button" class="sidebar-toggle" id="btnCloseMobileCart" style="display: none;"
                title="Cerrar carrito">
                ✕
            </button>
        </div>

        <!-- SELECTOR DE MESERO O VENTA DIRECTA -->
        <div class="waiter-section">
            <span class="section-label">ATENDIDO POR:</span>
            <div class="waiter-combo-row">
                <div class="waiter-direct-badge active" id="waiterDirectBadge">
                    🏪 VENTA EN BARRA
                </div>
                <div class="waiter-select-wrapper">
                    <select id="waiterSelect" class="waiter-select">
                        <option value="">👨‍🍳 Seleccionar vendedor...</option>
                        <?php foreach ($meseros as $mesero): ?>
                            <option value="<?= $mesero['id']; ?>" data-name="<?= esc($mesero['nombre']); ?>">
                                👨‍🍳 <?= esc($mesero['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <!-- Hidden input que el JS usa para saber el mesero activo -->
            <input type="hidden" id="selectedWaiterId" value="">
        </div>

        <!-- LISTADO DE ÍTEMS DEL CARRITO (SCROLLABLE) -->
        <div class="cart-items-container" id="cartItemsContainer">
            <div class="cart-empty-state">
                <div class="cart-empty-icon">🛒</div>
                <div class="cart-empty-text">Tu venta está vacía</div>
                <div class="cart-empty-subtext">Selecciona un producto o promoción para comenzar.</div>
            </div>
        </div>

        <!-- FOOTER Y TOTAL DEL CARRITO -->
        <div class="cart-footer">
            <div class="cart-total-row">
                <span class="cart-total-label">TOTAL:</span>
                <span class="cart-total-value" id="cartTotalValue">S/ 0.00</span>
            </div>

            <button type="button" class="btn-checkout" id="btnCheckout" disabled>
                <span>CONTINUAR / IR A COBRO</span>
                <span>➔</span>
            </button>
        </div>

    </div>

</div>

<div class="mobile-cart-overlay" id="mobileCartOverlay"></div>

<!-- MODAL PARA ELEGIR TIPO DE VENTA (UNIDAD/CAJETILLA) -->
<div class="pos-modal-overlay" id="modalTipoVenta">
    <div class="pos-modal-box" style="max-width: 450px;">
        <div class="pos-modal-icon">🛒</div>
        <h3 class="pos-modal-title">¿Cómo desea vender?</h3>
        <p id="nombreProductoUnidad" style="font-weight: 600; margin-bottom: 1.5rem; color: var(--accent);"></p>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
            <button type="button" id="btnVentaCajetilla" class="pos-modal-btn" style="height: auto; padding: 1.25rem 0.5rem; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; background: var(--bg-body); border: 2px solid var(--border-color); color: var(--text-primary);">
                <span style="font-size: 1.75rem;">📦</span>
                <span style="font-weight: 700; font-size: 0.9rem;">CAJA</span>
                <span style="font-size: 0.75rem; color: var(--text-muted);" id="infoStockCajetilla">Stock: 0</span>
            </button>
            <button type="button" id="btnVentaUnidad" class="pos-modal-btn" style="height: auto; padding: 1.25rem 0.5rem; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; background: var(--success-bg); border: 2px solid var(--success); color: var(--text-primary);">
                <span style="font-size: 1.75rem;">🚬</span>
                <span style="font-weight: 700; font-size: 0.9rem;">UNIDAD</span>
                <span style="font-size: 0.75rem; color: var(--success);" id="infoStockUnidad">Stock: 0</span>
            </button>
        </div>

        <div class="pos-modal-actions">
            <button type="button" class="pos-modal-btn pos-modal-btn-cancel" id="btnCancelarTipoVenta" style="grid-column: span 2;">CANCELAR</button>
        </div>
    </div>
</div>

<!-- MODAL CONFIRMACIÓN APERTURA AUTOMÁTICA -->
<div class="pos-modal-overlay" id="modalConfirmApertura">
    <div class="pos-modal-box" style="max-width: 450px;">
        <div class="pos-modal-icon" style="background: var(--warning-bg); color: var(--warning);">⚠️</div>
        <h3 class="pos-modal-title">Stock insuficiente</h3>
        
        <div style="margin-bottom: 1.5rem;">
            <p style="color: var(--text-primary); margin-bottom: 0.5rem;">No hay unidades sueltas disponibles.</p>
            <p style="font-weight: 600; color: var(--accent);">¿Desea abrir una cajetilla para realizar esta venta?</p>
        </div>

        <div class="pos-modal-actions">
            <button type="button" class="pos-modal-btn pos-modal-btn-cancel" id="btnCancelarAperturaAuto">CANCELAR</button>
            <button type="button" class="pos-modal-btn pos-modal-btn-confirm" id="btnConfirmarAperturaAuto" style="background: var(--accent);">ABRIR</button>
        </div>
    </div>
</div>

<!-- BARRA INFERIOR FLOTANTE PARA DISPOSITIVOS MÓVILES -->
<div class="mobile-cart-bar" id="mobileCartBar">
    <div class="mobile-cart-info">
        <span class="mobile-cart-items-text" id="mobileCartCount">VENTA ACTUAL</span>
        <span class="mobile-cart-total-text" id="mobileCartTotal">S/ 0.00</span>
    </div>
    <button type="button" class="btn-open-mobile-cart" id="btnOpenMobileCart">
        🛒 VER VENTA
        <span class="cart-count-chip" id="mobileCartChip">0</span>
    </button>
</div>

<!-- MODAL DE CONFIRMACIÓN: LIMPIAR CARRITO -->
<div class="pos-modal-overlay" id="clearCartModal">
    <div class="pos-modal-box">
        <div class="pos-modal-icon">🗑</div>
        <h3 class="pos-modal-title">¿Vaciar la venta actual?</h3>
        <p class="pos-modal-text">Esta acción eliminará todos los productos agregados a la lista actual.</p>
        <div class="pos-modal-actions">
            <button type="button" class="pos-modal-btn pos-modal-btn-cancel" id="btnCancelClear">Cancelar</button>
            <button type="button" class="pos-modal-btn pos-modal-btn-confirm" id="btnConfirmClear">Sí, Vaciar
                Venta</button>
        </div>
    </div>
</div>

<!-- MODAL DE COBRO REAL — ETAPA 4.2 -->
<div class="pos-modal-overlay" id="checkoutModal">
    <div class="pos-modal-box" style="max-width: 500px;">

        <div class="pos-modal-icon">💳</div>
        <h3 class="pos-modal-title">Confirmar Venta</h3>

        <!-- Resumen del total -->
        <div
            style="background: var(--bg-body); border-radius: 12px; padding: 0.85rem 1rem; margin-bottom: 1.25rem; text-align: center; border: 1px solid var(--border-color);">
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.2rem;">TOTAL A COBRAR</div>
            <div style="font-size: 2rem; font-weight: 800; color: var(--accent);" id="checkoutTotalDisplay">S/ 0.00
            </div>
        </div>

        <!-- Tipo de Pago -->
        <div style="margin-bottom: 1rem;">
            <div
                style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); letter-spacing: .05em; margin-bottom: 0.5rem;">
                MÉTODO DE PAGO</div>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem;" id="tipoPagoGrid">
                <button type="button" class="payment-method-btn active" data-pago="EFECTIVO" id="pagoEfectivo">
                    💵<span>EFECTIVO</span>
                </button>
                <button type="button" class="payment-method-btn" data-pago="YAPE" id="pagoYape">
                    📲<span>YAPE</span>
                </button>
                <button type="button" class="payment-method-btn" data-pago="PLIN" id="pagoPlin">
                    📱<span>PLIN</span>
                </button>
                <button type="button" class="payment-method-btn" data-pago="FIADO" id="pagoFiado">
                    📋<span>FIADO</span>
                </button>
            </div>
        </div>

        <!-- Sección CLIENTE: Búsqueda y Selección (Opcional en Efectivo/Tarjeta/Yape/Plin, Obligatorio en Fiado) -->
        <div id="clienteSection" style="margin-bottom: 1rem;">
            <div
                style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); letter-spacing: .05em; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
                <span id="clienteLabelText">👤 CLIENTE (OPCIONAL)</span>
                <button type="button" id="btnToggleNuevoCliente"
                    style="background: none; border: none; color: var(--accent); font-weight: 700; cursor: pointer; font-size: 0.78rem; font-family: inherit;">
                    ➕ Nuevo Cliente
                </button>
            </div>

            <!-- Búsqueda y Dropdown -->
            <div id="clienteSearchWrapper" style="position: relative;">
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" id="clienteSearch" placeholder="Buscar por nombre o teléfono..."
                        autocomplete="off"
                        style="flex: 1; padding: 0.65rem 0.9rem; border: 1px solid var(--border-color); border-radius: 10px; background: var(--bg-card); color: var(--text-primary); font-size: 0.9rem; box-sizing: border-box;">
                    <button type="button" id="btnClearCliente"
                        style="display: none; padding: 0 0.8rem; background: var(--danger-bg); border: 1px solid rgba(239, 68, 68, 0.3); color: var(--danger); border-radius: 10px; cursor: pointer;"
                        title="Quitar cliente">✕</button>
                </div>
                <div id="clienteDropdown"
                    style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; margin-top: 4px; max-height: 180px; overflow-y: auto; z-index: 9999; box-shadow: var(--shadow);">
                </div>
            </div>

            <!-- Formulario Rápido para Crear Cliente (Oculto por defecto) -->
            <div id="quickNuevoClienteForm"
                style="display: none; margin-top: 0.5rem; padding: 0.85rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 12px;">
                <div style="font-size: 0.8rem; font-weight: 700; color: var(--accent); margin-bottom: 0.6rem;">✨ CREAR
                    CLIENTE RÁPIDO</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 0.6rem;">
                    <input type="text" id="quickNombreCliente" placeholder="Nombre completo *"
                        style="padding: 0.55rem 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-card); color: var(--text-primary); font-size: 0.85rem;">
                    <input type="text" id="quickTelCliente" placeholder="Teléfono (opcional)"
                        style="padding: 0.55rem 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-card); color: var(--text-primary); font-size: 0.85rem;">
                </div>
                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                    <button type="button" id="btnCancelQuickCliente"
                        style="padding: 0.4rem 0.8rem; background: transparent; border: 1px solid var(--border); border-radius: 8px; color: var(--text-muted); font-size: 0.8rem; cursor: pointer;">Cancelar</button>
                    <button type="button" id="btnSaveQuickCliente"
                        style="padding: 0.4rem 1rem; background: var(--accent); color: #0f172a; border: none; border-radius: 8px; font-weight: 700; font-size: 0.8rem; cursor: pointer;">Guardar
                        y Seleccionar</button>
                </div>
            </div>

            <!-- Badge Cliente Seleccionado -->
            <div id="clienteSeleccionado"
                style="display: none; margin-top: 0.5rem; padding: 0.5rem 0.75rem; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 8px; font-size: 0.85rem; color: var(--success); align-items: center; justify-content: space-between;">
                <span id="clienteSeleccionadoTexto">✔ Cliente</span>
                <button type="button" id="btnDeselectCliente"
                    style="background: none; border: none; color: var(--danger); font-weight: 700; cursor: pointer; font-size: 0.8rem;">Quitar
                    ✕</button>
            </div>
            <input type="hidden" id="clienteIdHidden" value="">
        </div>

        <!-- Observación / Garantía (Solo para FIADO) -->
        <div id="observacionFiadoSection" style="display: none; margin-bottom: 1.25rem;">
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--accent); letter-spacing: .05em; margin-bottom: 0.5rem;">
                🔐 OBSERVACIÓN / GARANTÍA
            </div>
            <input type="text" id="observacionFiado" placeholder="Ej: Celular Samsung negro en garantía..."
                style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--border-color); border-radius: 12px; background: var(--bg-card); color: var(--text-primary); font-size: 0.95rem; box-sizing: border-box; transition: border-color 0.2s;">
        </div>

        <!-- Mensaje de error en el modal -->
        <div id="checkoutError"
            style="display: none; padding: 0.65rem 0.85rem; background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.4); border-radius: 8px; color: #f87171; font-size: 0.85rem; margin-bottom: 0.75rem;">
        </div>

        <!-- Acciones -->
        <div class="pos-modal-actions">
            <button type="button" class="pos-modal-btn pos-modal-btn-cancel" id="btnCancelCheckout">Cancelar</button>
            <button type="button" class="pos-modal-btn pos-modal-btn-confirm" id="btnConfirmCheckout">
                <span id="checkoutBtnText">✔ REGISTRAR VENTA</span>
            </button>
        </div>

    </div>
</div>

<!-- MODAL DE VENTA EXITOSA -->
<div class="pos-modal-overlay" id="successModal">
    <div class="pos-modal-box" style="max-width: 420px; text-align: center;">
        <div class="pos-modal-icon" style="font-size: 3rem;">✅</div>
        <h3 class="pos-modal-title">¡Venta Registrada!</h3>
        <p class="pos-modal-text" id="successModalMsg">La venta se guardó correctamente.</p>
        <div style="margin-top: 0.5rem; font-size: 1.4rem; font-weight: 800; color: var(--accent);"
            id="successModalTotal"></div>
        <div style="margin-top: 0.3rem; font-size: 0.8rem; color: var(--text-muted);" id="successModalId"></div>
        <div class="pos-modal-actions" style="margin-top: 1.25rem;">
            <button type="button" class="pos-modal-btn pos-modal-btn-confirm" id="btnSuccessClose" style="width: 100%;">
                Nueva Venta
            </button>
        </div>
    </div>
</div>

<?= $this->endSection(); ?>

<?= $this->section('scripts'); ?>
<script>
    const POS_BASE_URL = '<?= base_url(); ?>';
    const POS_CSRF_NAME = '<?= csrf_token(); ?>';
    const POS_CSRF_HASH = '<?= csrf_hash(); ?>';
</script>
<script src="<?= base_url('js/ventas_pos.js'); ?>"></script>
<?= $this->endSection(); ?>