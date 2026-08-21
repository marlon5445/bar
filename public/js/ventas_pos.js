/**
 * BAR MANAGER - POS TERMINAL INTERACTIVE SCRIPT
 * Etapa 4.2: Integración Backend — Ventas Reales
 */

document.addEventListener('DOMContentLoaded', function () {

    // ─────────────────────────────────────────────────────────────────────────
    // ESTADO LOCAL DEL CARRITO EN MEMORIA
    // ─────────────────────────────────────────────────────────────────────────
    let cart = [];
    let selectedWaiterId = null;    // null = Venta Directa en Barra
    let selectedWaiterName = '🏪 Venta Directa en Barra';
    let activeCategory = 'ALL';

    // ─────────────────────────────────────────────────────────────────────────
    // ELEMENTOS DOM — CATÁLOGO
    // ─────────────────────────────────────────────────────────────────────────
    const productCards = document.querySelectorAll('.product-card');
    const categoryBtns = document.querySelectorAll('.category-btn');
    const waiterDirectBadge = document.getElementById('waiterDirectBadge');
    const waiterSelect       = document.getElementById('waiterSelect');
    const selectedWaiterIdInput = document.getElementById('selectedWaiterId');
    const searchInput = document.getElementById('posSearchInput');
    const searchClearBtn = document.getElementById('posSearchClear');

    // ─────────────────────────────────────────────────────────────────────────
    // ELEMENTOS DOM — CARRITO
    // ─────────────────────────────────────────────────────────────────────────
    const cartItemsContainer = document.getElementById('cartItemsContainer');
    const cartBadgeCount = document.getElementById('cartBadgeCount');
    const cartTotalValue = document.getElementById('cartTotalValue');
    const btnClearCart = document.getElementById('btnClearCart');
    const btnCheckout = document.getElementById('btnCheckout');

    // Modal limpiar carrito
    const clearCartModal = document.getElementById('clearCartModal');
    const btnConfirmClear = document.getElementById('btnConfirmClear');
    const btnCancelClear = document.getElementById('btnCancelClear');

    // Modal de cobro real
    const checkoutModal = document.getElementById('checkoutModal');
    const checkoutTotalDisplay = document.getElementById('checkoutTotalDisplay');
    const tipoPagoGrid = document.getElementById('tipoPagoGrid');
    const fiadoSection = document.getElementById('fiadoSection');
    const clienteSearch = document.getElementById('clienteSearch');
    const clienteDropdown = document.getElementById('clienteDropdown');
    const clienteSeleccionado = document.getElementById('clienteSeleccionado');
    const clienteIdHidden = document.getElementById('clienteIdHidden');
    const checkoutError = document.getElementById('checkoutError');
    const btnCancelCheckout = document.getElementById('btnCancelCheckout');
    const btnConfirmCheckout = document.getElementById('btnConfirmCheckout');
    const checkoutBtnText = document.getElementById('checkoutBtnText');
    const observacionFiadoSection = document.getElementById('observacionFiadoSection');
    const observacionFiadoInput = document.getElementById('observacionFiado');

    // Modal éxito
    const successModal = document.getElementById('successModal');
    const successModalMsg = document.getElementById('successModalMsg');
    const successModalTotal = document.getElementById('successModalTotal');
    const successModalId = document.getElementById('successModalId');
    const btnSuccessClose = document.getElementById('btnSuccessClose');

    // Modal Alerta Personalizado
    const posAlertModal = document.getElementById('posAlertModal');
    const posAlertIcon = document.getElementById('posAlertIcon');
    const posAlertTitle = document.getElementById('posAlertTitle');
    const posAlertText = document.getElementById('posAlertText');
    const btnClosePosAlert = document.getElementById('btnClosePosAlert');

    function showPosAlert(message, title = 'Aviso', icon = '⚠️') {
        if (!posAlertModal) return;
        posAlertIcon.textContent = icon;
        posAlertTitle.textContent = title;
        posAlertText.textContent = message;
        posAlertModal.classList.add('show');
    }

    if (btnClosePosAlert) {
        btnClosePosAlert.addEventListener('click', () => {
            posAlertModal.classList.remove('show');
        });
    }

    if (posAlertModal) {
        posAlertModal.addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('show');
            }
        });
    }

    // Móvil
    const mobileCartBar      = document.getElementById('mobileCartBar');
    const mobileCartCount    = document.getElementById('mobileCartCount');
    const mobileCartTotal    = document.getElementById('mobileCartTotal');
    const mobileCartChip     = document.getElementById('mobileCartChip');
    const mobileCartOverlay  = document.getElementById('mobileCartOverlay');
    const btnOpenMobileCart  = document.getElementById('btnOpenMobileCart');
    const btnCloseMobileCart = document.getElementById('btnCloseMobileCart');
    const posCartPanel       = document.getElementById('posCartPanel');

    // Estado del modal de cobro
    let selectedTipoPago = 'EFECTIVO';
    let selectedClienteId = null;
    let clienteSearchTimeout = null;

    const modalTipoVenta = document.getElementById('modalTipoVenta');
    const btnVentaCajetilla = document.getElementById('btnVentaCajetilla');
    const btnVentaUnidad = document.getElementById('btnVentaUnidad');
    const btnCancelarTipoVenta = document.getElementById('btnCancelarTipoVenta');
    const nombreProductoUnidad = document.getElementById('nombreProductoUnidad');
    const infoStockCajetilla = document.getElementById('infoStockCajetilla');
    const infoStockUnidad = document.getElementById('infoStockUnidad');

    const modalConfirmApertura = document.getElementById('modalConfirmApertura');
    const btnCancelarAperturaAuto = document.getElementById('btnCancelarAperturaAuto');
    const btnConfirmarAperturaAuto = document.getElementById('btnConfirmarAperturaAuto');

    let pendingItem = null;

    // ─────────────────────────────────────────────────────────────────────────
    // 1. PRODUCTOS: EVENT LISTENERS
    // ─────────────────────────────────────────────────────────────────────────
    productCards.forEach(card => {
        card.addEventListener('click', function () {
            const id = this.dataset.id;
            const type = this.dataset.type; // 'PRODUCT' o 'PROMO'
            const name = this.dataset.name;
            const price = parseFloat(this.dataset.price);
            const unitPrice = parseFloat(this.dataset.unitPrice) || 0;
            const code = this.dataset.code || '';
            const icon = this.dataset.icon || (type === 'PROMO' ? '🔥' : '🍺');
            const stock = this.dataset.stock !== undefined ? parseInt(this.dataset.stock) : null;
            const manejaUnidades = this.dataset.manejaUnidades == '1';
            const unitsPerBox = parseInt(this.dataset.unidadesPorCaja) || 0;
            const unitsStock = parseInt(this.dataset.stockUnidades) || 0;

            if (manejaUnidades) {
                pendingItem = { id, type, name, price, unitPrice, code, icon, stock, manejaUnidades, unitsPerBox, unitsStock };
                nombreProductoUnidad.textContent = name;
                infoStockCajetilla.textContent = `Stock: ${stock}`;
                infoStockUnidad.textContent = `Stock: ${unitsStock}`;
                modalTipoVenta.classList.add('show');
            } else {
                addToCart({ id, type, name, price, code, icon, stock, venderPorUnidad: false });
            }

            // Feedback visual breve al tocar
            this.style.transform = 'scale(0.95)';
            setTimeout(() => { this.style.transform = ''; }, 150);
        });
    });

    btnVentaCajetilla.addEventListener('click', function() {
        if (pendingItem) {
            addToCart({ ...pendingItem, venderPorUnidad: false });
            modalTipoVenta.classList.remove('show');
            pendingItem = null;
        }
    });

    btnVentaUnidad.addEventListener('click', function() {
        if (pendingItem) {
            if (pendingItem.unitPrice <= 0) {
                showPosAlert('Este producto no tiene precio por unidad configurado.');
                return;
            }
            if (pendingItem.unitsStock <= 0) {
                if (pendingItem.stock > 0) {
                    modalTipoVenta.classList.remove('show');
                    modalConfirmApertura.classList.add('show');
                } else {
                    showPosAlert('No hay stock disponible ni para unidades sueltas ni para apertura.');
                }
            } else {
                addToCart({ 
                    ...pendingItem, 
                    price: pendingItem.unitPrice, 
                    venderPorUnidad: true 
                });
                modalTipoVenta.classList.remove('show');
                pendingItem = null;
            }
        }
    });

    btnConfirmarAperturaAuto.addEventListener('click', function() {
        if (pendingItem) {
            // Redirigir a la pantalla de apertura
            window.location.href = `${POS_BASE_URL}ventas/apertura`;
            modalConfirmApertura.classList.remove('show');
            pendingItem = null;
        }
    });

    // Cerrar modales al hacer clic fuera
    document.querySelectorAll('.pos-modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('show');
                if (this.id === 'modalTipoVenta' || this.id === 'modalConfirmApertura') pendingItem = null;
            }
        });
    });

    // Botones de cancelar en modales
    if (btnCancelarTipoVenta) {
        btnCancelarTipoVenta.addEventListener('click', () => {
            modalTipoVenta.classList.remove('show');
            pendingItem = null;
        });
    }

    if (btnCancelarAperturaAuto) {
        btnCancelarAperturaAuto.addEventListener('click', () => {
            modalConfirmApertura.classList.remove('show');
            pendingItem = null;
        });
    }

    // Cerrar modales con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.pos-modal-overlay.show').forEach(modal => {
                modal.classList.remove('show');
                if (modal.id === 'modalTipoVenta' || modal.id === 'modalConfirmApertura') pendingItem = null;
            });
        }
    });

    // ─────────────────────────────────────────────────────────────────────────
    // 2. AGREGAR ITEM AL CARRITO (O INCREMENTAR)
    // ─────────────────────────────────────────────────────────────────────────
    function addToCart(itemData) {
        const existingIndex = cart.findIndex(i => 
            i.id === itemData.id && 
            i.type === itemData.type && 
            i.venderPorUnidad === itemData.venderPorUnidad
        );

        if (existingIndex > -1) {
            cart[existingIndex].qty += 1;
            cart[existingIndex].subtotal = cart[existingIndex].qty * cart[existingIndex].price;
        } else {
            cart.push({
                id: itemData.id,
                type: itemData.type,
                name: itemData.name,
                price: itemData.price,
                code: itemData.code,
                icon: itemData.icon,
                stock: itemData.stock,
                venderPorUnidad: itemData.venderPorUnidad,
                qty: 1,
                subtotal: itemData.price
            });
        }
        renderCart();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. RENDERIZAR CARRITO
    // ─────────────────────────────────────────────────────────────────────────
    function renderCart() {
        if (!cartItemsContainer) return;

        const totalQty = cart.reduce((s, i) => s + i.qty, 0);
        const totalAmount = cart.reduce((s, i) => s + i.subtotal, 0);

        // Actualizar badges
        if (cartBadgeCount) cartBadgeCount.textContent = totalQty;
        if (cartTotalValue) cartTotalValue.textContent = 'S/ ' + totalAmount.toFixed(2);
        if (btnCheckout) btnCheckout.disabled = cart.length === 0;
        if (btnClearCart) btnClearCart.style.display = cart.length > 0 ? 'flex' : 'none';

        // Actualizar barra móvil
        const itemLabel = totalQty === 0
            ? 'VENTA ACTUAL'
            : totalQty === 1 ? '1 PRODUCTO' : `${totalQty} PRODUCTOS`;
        if (mobileCartCount) mobileCartCount.textContent = itemLabel;
        if (mobileCartTotal) mobileCartTotal.textContent = 'S/ ' + totalAmount.toFixed(2);
        if (mobileCartChip)  mobileCartChip.textContent  = totalQty;

        if (cart.length === 0) {
            cartItemsContainer.innerHTML = `
                <div class="cart-empty-state">
                    <div class="cart-empty-icon">🛒</div>
                    <div class="cart-empty-text">Tu venta está vacía</div>
                    <div class="cart-empty-subtext">Selecciona un producto o promoción para comenzar.</div>
                </div>`;
            return;
        }

        let html = '';
        cart.forEach((item, index) => {
            const hasStock = item.stock !== null && item.stock !== undefined && item.stock !== '';
            const unidadLabel = item.venderPorUnidad ? ' <span style="color:var(--success); font-size:0.7rem; font-weight:bold;">[UNIDAD]</span>' : '';
            html += `
            <div class="cart-item-card" data-index="${index}">
                <!-- Fila Superior: Identidad del Producto y Botón Eliminar -->
                <div class="cart-item-top-bar">
                    <div class="cart-item-identity">
                        <span class="cart-item-icon-box">${escapeHtml(item.icon)}</span>
                        <div class="cart-item-details">
                            <span class="cart-item-name">${escapeHtml(item.name)}${unidadLabel}</span>
                            <div class="cart-item-submeta">
                                <span class="cart-item-unit-price">S/ ${item.price.toFixed(2)} c/u</span>
                                ${hasStock && !item.venderPorUnidad ? `<span class="cart-stock-badge">St: ${item.stock}</span>` : ''}
                            </div>
                        </div>
                    </div>
                    <button type="button" class="cart-item-btn-remove" data-action="remove" data-index="${index}" title="Eliminar ítem">
                        ✕
                    </button>
                </div>

                <!-- Fila Inferior: Subtotal (Izquierda) + Control de Cantidad Grande (Derecha) -->
                <div class="cart-item-action-bar">
                    <div class="cart-item-subtotal-group">
                        <span class="cart-item-subtotal-label">SUBTOTAL</span>
                        <span class="cart-item-subtotal-value">S/ ${item.subtotal.toFixed(2)}</span>
                    </div>
                    <div class="cart-qty-controller">
                        <button type="button" class="cart-qty-touch-btn btn-dec" data-action="dec" data-index="${index}" title="Disminuir cantidad">
                            −
                        </button>
                        <span class="cart-qty-display">${item.qty}</span>
                        <button type="button" class="cart-qty-touch-btn btn-inc" data-action="inc" data-index="${index}" title="Aumentar cantidad">
                            +
                        </button>
                    </div>
                </div>
            </div>`;
        });

        cartItemsContainer.innerHTML = html;

        // Event listeners en los botones táctiles y de eliminación del carrito
        cartItemsContainer.querySelectorAll('.cart-qty-touch-btn, .cart-item-btn-remove').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = parseInt(this.dataset.index);
                const action = this.dataset.action;
                if (action === 'inc') {
                    cart[idx].qty++;
                    cart[idx].subtotal = cart[idx].qty * cart[idx].price;
                } else if (action === 'dec') {
                    cart[idx].qty--;
                    if (cart[idx].qty <= 0) {
                        cart.splice(idx, 1);
                    } else {
                        cart[idx].subtotal = cart[idx].qty * cart[idx].price;
                    }
                } else if (action === 'remove') {
                    cart.splice(idx, 1);
                }
                renderCart();
            });
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. FILTROS DE CATEGORÍA
    // ─────────────────────────────────────────────────────────────────────────
    categoryBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            categoryBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeCategory = this.dataset.category;
            filterCatalog();
        });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // 5. BUSCADOR RÁPIDO
    // ─────────────────────────────────────────────────────────────────────────
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            searchClearBtn.style.display = this.value.trim() !== '' ? 'block' : 'none';
            filterCatalog();
        });

        searchClearBtn.addEventListener('click', function () {
            searchInput.value = '';
            searchClearBtn.style.display = 'none';
            searchInput.focus();
            filterCatalog();
        });
    }

    function filterCatalog() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';

        productCards.forEach(card => {
            const categoryId = card.dataset.category;
            const cardType = card.dataset.type;
            const name = card.dataset.name.toLowerCase();
            const code = (card.dataset.code || '').toLowerCase();

            let matchesCategory =
                activeCategory === 'ALL' ||
                (activeCategory === 'PROMO' && cardType === 'PROMO') ||
                (categoryId === activeCategory);

            let matchesQuery = query === '' || name.includes(query) || code.includes(query);

            card.style.display = (matchesCategory && matchesQuery) ? 'flex' : 'none';
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. SELECCIÓN DE MESERO / VENTA DIRECTA EN BARRA (combo)
    // ─────────────────────────────────────────────────────────────────────────

    function setDirectSale() {
        selectedWaiterId   = null;
        selectedWaiterName = '🏪 Venta Directa en Barra';
        if (waiterDirectBadge) waiterDirectBadge.classList.add('active');
        if (waiterSelect) {
            waiterSelect.value = '';
            waiterSelect.classList.remove('has-value');
        }
        if (selectedWaiterIdInput) selectedWaiterIdInput.value = '';
    }

    // Click en el badge → resetea a Venta Directa
    if (waiterDirectBadge) {
        waiterDirectBadge.addEventListener('click', function () {
            setDirectSale();
        });
    }

    // Cambio en el select → elige vendedor
    if (waiterSelect) {
        waiterSelect.addEventListener('change', function () {
            const val = this.value;
            if (!val) {
                setDirectSale();
            } else {
                selectedWaiterId   = parseInt(val);
                const opt          = this.options[this.selectedIndex];
                selectedWaiterName = '👨‍🍳 Mesero: ' + (opt.dataset.name || opt.text);
                if (waiterDirectBadge) waiterDirectBadge.classList.remove('active');
                this.classList.add('has-value');
                if (selectedWaiterIdInput) selectedWaiterIdInput.value = val;
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 7. LIMPIAR VENTA CON CONFIRMACIÓN
    // ─────────────────────────────────────────────────────────────────────────
    if (btnClearCart) {
        btnClearCart.addEventListener('click', () => clearCartModal.classList.add('show'));
    }
    if (btnCancelClear) {
        btnCancelClear.addEventListener('click', () => clearCartModal.classList.remove('show'));
    }
    if (btnConfirmClear) {
        btnConfirmClear.addEventListener('click', function () {
            cart = [];
            renderCart();
            clearCartModal.classList.remove('show');
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 8. ABRIR MODAL DE COBRO AL PRESIONAR "CONTINUAR"
    // ─────────────────────────────────────────────────────────────────────────
    if (btnCheckout) {
        btnCheckout.addEventListener('click', function () {
            if (cart.length === 0) return;

            const totalAmount = cart.reduce((s, i) => s + i.subtotal, 0);
            if (checkoutTotalDisplay) checkoutTotalDisplay.textContent = 'S/ ' + totalAmount.toFixed(2);

            // Resetear estado del modal
            setTipoPago('EFECTIVO');
            resetFiadoSection();
            hideCheckoutError();
            setBtnConfirmLoading(false);

            checkoutModal.classList.add('show');
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 9. SELECCIÓN DE TIPO DE PAGO
    // ─────────────────────────────────────────────────────────────────────────
    if (tipoPagoGrid) {
        tipoPagoGrid.querySelectorAll('.payment-method-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                setTipoPago(this.dataset.pago);
            });
        });
    }

    // DOM para sección de cliente
    const clienteLabelText = document.getElementById('clienteLabelText');
    const btnToggleNuevoCliente = document.getElementById('btnToggleNuevoCliente');
    const quickNuevoClienteForm = document.getElementById('quickNuevoClienteForm');
    const quickNombreCliente = document.getElementById('quickNombreCliente');
    const quickTelCliente = document.getElementById('quickTelCliente');
    const btnCancelQuickCliente = document.getElementById('btnCancelQuickCliente');
    const btnSaveQuickCliente = document.getElementById('btnSaveQuickCliente');
    const clienteSeleccionadoTexto = document.getElementById('clienteSeleccionadoTexto');
    const btnDeselectCliente = document.getElementById('btnDeselectCliente');
    const btnClearCliente = document.getElementById('btnClearCliente');

    // Toggle formulario de nuevo cliente rápido
    if (btnToggleNuevoCliente) {
        btnToggleNuevoCliente.addEventListener('click', function () {
            const isHidden = quickNuevoClienteForm.style.display === 'none' || !quickNuevoClienteForm.style.display;
            quickNuevoClienteForm.style.display = isHidden ? 'block' : 'none';
            if (isHidden && quickNombreCliente) {
                quickNombreCliente.focus();
            }
        });
    }

    if (btnCancelQuickCliente) {
        btnCancelQuickCliente.addEventListener('click', function () {
            if (quickNuevoClienteForm) quickNuevoClienteForm.style.display = 'none';
        });
    }

    // Guardar nuevo cliente rápido
    if (btnSaveQuickCliente) {
        btnSaveQuickCliente.addEventListener('click', function () {
            const nombre = quickNombreCliente ? quickNombreCliente.value.trim() : '';
            const tel = quickTelCliente ? quickTelCliente.value.trim() : '';

            if (!nombre) {
                showCheckoutError('Ingrese al menos el nombre del cliente.');
                if (quickNombreCliente) quickNombreCliente.focus();
                return;
            }

            btnSaveQuickCliente.disabled = true;
            btnSaveQuickCliente.textContent = 'Guardando...';

            fetch(`${POS_BASE_URL}ventas/crear-cliente`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    [POS_CSRF_NAME]: POS_CSRF_HASH,
                },
                body: JSON.stringify({ nombre: nombre, telefono: tel })
            })
                .then(r => r.json())
                .then(data => {
                    btnSaveQuickCliente.disabled = false;
                    btnSaveQuickCliente.textContent = 'Guardar y Seleccionar';

                    if (data.success && data.cliente) {
                        seleccionarCliente(data.cliente.id, data.cliente.nombre, data.cliente.telefono || '');
                        if (quickNuevoClienteForm) quickNuevoClienteForm.style.display = 'none';
                        if (quickNombreCliente) quickNombreCliente.value = '';
                        if (quickTelCliente) quickTelCliente.value = '';
                    } else {
                        showCheckoutError(data.mensaje || 'No se pudo registrar el cliente.');
                    }
                })
                .catch(() => {
                    btnSaveQuickCliente.disabled = false;
                    btnSaveQuickCliente.textContent = 'Guardar y Seleccionar';
                    showCheckoutError('Error de conexión al crear cliente.');
                });
        });
    }

    // Quitar cliente seleccionado
    if (btnDeselectCliente) {
        btnDeselectCliente.addEventListener('click', function () {
            resetFiadoSection(true);
        });
    }
    if (btnClearCliente) {
        btnClearCliente.addEventListener('click', function () {
            resetFiadoSection(true);
        });
    }

    function setTipoPago(pago) {
        selectedTipoPago = pago;
        if (tipoPagoGrid) {
            tipoPagoGrid.querySelectorAll('.payment-method-btn').forEach(b => {
                b.classList.toggle('active', b.dataset.pago === pago);
            });
        }

        // Actualizar etiqueta del cliente (Opcional para EFECTIVO/YAPE/PLIN/TARJETA, Requerido para FIADO)
        if (clienteLabelText) {
            if (pago === 'FIADO') {
                clienteLabelText.textContent = '👤 CLIENTE (OBLIGATORIO PARA FIADO)';
                clienteLabelText.style.color = 'var(--danger, #ef4444)';
                if (observacionFiadoSection) observacionFiadoSection.style.display = 'block';
            } else {
                clienteLabelText.textContent = '👤 CLIENTE (OPCIONAL)';
                clienteLabelText.style.color = 'var(--text-muted, #64748b)';
                if (observacionFiadoSection) observacionFiadoSection.style.display = 'none';
                if (observacionFiadoInput) observacionFiadoInput.value = '';
            }
        }
        hideCheckoutError();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 10. BÚSQUEDA DE CLIENTES PARA VENTA (AUTOCOMPLETE)
    // ─────────────────────────────────────────────────────────────────────────
    if (clienteSearch) {
        clienteSearch.addEventListener('input', function () {
            clearTimeout(clienteSearchTimeout);
            const q = this.value.trim();

            if (selectedClienteId) {
                resetFiadoSection(false);
            }

            if (q.length < 2) {
                hideClienteDropdown();
                return;
            }

            clienteSearchTimeout = setTimeout(() => buscarClientes(q), 350);
        });

        document.addEventListener('click', function (e) {
            if (!clienteSearch.contains(e.target) && !clienteDropdown.contains(e.target)) {
                hideClienteDropdown();
            }
        });
    }

    function buscarClientes(q) {
        fetch(`${POS_BASE_URL}ventas/clientes?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                if (!data.clientes || data.clientes.length === 0) {
                    showClienteDropdown([]);
                    return;
                }
                showClienteDropdown(data.clientes);
            })
            .catch(() => hideClienteDropdown());
    }

    function showClienteDropdown(clientes) {
        if (!clienteDropdown) return;

        if (clientes.length === 0) {
            clienteDropdown.innerHTML = '<div style="padding: 0.6rem 0.9rem; font-size: 0.85rem; color: var(--text-muted);">Sin resultados</div>';
            clienteDropdown.style.display = 'block';
            return;
        }

        clienteDropdown.innerHTML = clientes.map(c => `
            <div class="cliente-option" data-id="${c.id}" data-nombre="${escapeHtml(c.nombre)}" data-tel="${escapeHtml(c.telefono || '')}"
                style="padding: 0.65rem 0.9rem; cursor: pointer; font-size: 0.875rem; border-bottom: 1px solid var(--border-color);">
                <strong>${escapeHtml(c.nombre)}</strong>
                ${c.telefono ? `<span style="color: var(--text-muted); margin-left: 0.5rem;">${escapeHtml(c.telefono)}</span>` : ''}
            </div>`).join('');

        clienteDropdown.querySelectorAll('.cliente-option').forEach(opt => {
            opt.addEventListener('mouseenter', () => opt.style.background = 'var(--bg-card-hover)');
            opt.addEventListener('mouseleave', () => opt.style.background = '');
            opt.addEventListener('click', function () {
                seleccionarCliente(this.dataset.id, this.dataset.nombre, this.dataset.tel);
            });
        });

        clienteDropdown.style.display = 'block';
    }

    function hideClienteDropdown() {
        if (clienteDropdown) clienteDropdown.style.display = 'none';
    }

    function seleccionarCliente(id, nombre, telefono) {
        selectedClienteId = parseInt(id);
        if (clienteIdHidden) clienteIdHidden.value = id;
        if (clienteSearch) clienteSearch.value = nombre;
        if (btnClearCliente) btnClearCliente.style.display = 'block';
        hideClienteDropdown();

        if (clienteSeleccionado) {
            if (clienteSeleccionadoTexto) {
                clienteSeleccionadoTexto.textContent = `✔ ${nombre}${telefono ? ' — ' + telefono : ''}`;
            } else {
                clienteSeleccionado.textContent = `✔ ${nombre}${telefono ? ' — ' + telefono : ''}`;
            }
            clienteSeleccionado.style.display = 'flex';
        }
        hideCheckoutError();
    }

    function resetFiadoSection(clearInput = true) {
        selectedClienteId = null;
        if (clienteIdHidden) clienteIdHidden.value = '';
        if (clearInput && clienteSearch) clienteSearch.value = '';
        if (btnClearCliente) btnClearCliente.style.display = 'none';
        if (clienteSeleccionado) {
            clienteSeleccionado.style.display = 'none';
            if (clienteSeleccionadoTexto) clienteSeleccionadoTexto.textContent = '';
        }
        hideClienteDropdown();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 11. CANCELAR COBRO
    // ─────────────────────────────────────────────────────────────────────────
    if (btnCancelCheckout) {
        btnCancelCheckout.addEventListener('click', () => checkoutModal.classList.remove('show'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 12. CONFIRMAR VENTA — POST AL BACKEND
    // ─────────────────────────────────────────────────────────────────────────
    if (btnConfirmCheckout) {
        btnConfirmCheckout.addEventListener('click', function () {
            // Validar FIADO requiere cliente
            if (selectedTipoPago === 'FIADO' && !selectedClienteId) {
                showCheckoutError('Debes seleccionar un cliente para registrar una venta fiada.');
                return;
            }

            if (cart.length === 0) return;

            const items = cart.map(item => ({
                tipo: item.type === 'PROMO' ? 'promocion' : 'producto',
                id: parseInt(item.id),
                cantidad: item.qty,
                precio_unitario: item.price,
                vender_por_unidad: item.venderPorUnidad || false
            }));

            const payload = {
                items: items,
                mesero_id: selectedWaiterId,
                cliente_id: selectedClienteId ? selectedClienteId : null,
                tipo_pago: selectedTipoPago,
                descuento: 0,
                observacion: (selectedTipoPago === 'FIADO') ? document.getElementById('observacionFiado').value.trim() : '',
            };

            setBtnConfirmLoading(true);
            hideCheckoutError();

            fetch(`${POS_BASE_URL}ventas/procesar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    [POS_CSRF_NAME]: POS_CSRF_HASH,
                },
                body: JSON.stringify(payload),
            })
                .then(r => r.json())
                .then(data => {
                    setBtnConfirmLoading(false);
                    if (data.success) {
                        actualizarStockVisual(data.stock_actualizado);
                        checkoutModal.classList.remove('show');
                        mostrarExito(data);
                    } else {
                        showCheckoutError(data.mensaje || 'Error al procesar la venta.');
                    }
                })
                .catch(() => {
                    setBtnConfirmLoading(false);
                    showCheckoutError('Error de conexión. Verifica tu red e intenta de nuevo.');
                });
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 13. MODAL DE ÉXITO — LIMPIAR Y PREPARAR NUEVA VENTA
    // ─────────────────────────────────────────────────────────────────────────
    function mostrarExito(data) {
        if (successModalTotal) successModalTotal.textContent = 'Total: S/ ' + parseFloat(data.total).toFixed(2);
        if (successModalId) successModalId.textContent = 'Venta #' + data.venta_id;
        if (successModalMsg) successModalMsg.textContent = data.mensaje || '¡Venta registrada correctamente!';
        if (successModal) successModal.classList.add('show');
    }

    // Refleja exclusivamente el stock confirmado por el backend.
    function actualizarStockVisual(stockActualizado) {
        if (!Array.isArray(stockActualizado)) return;

        stockActualizado.forEach(stock => {
            const productoId = String(stock.producto_id);
            const stockActual = Number(stock.stock_actual);
            const stockUnidades = stock.stock_unidades !== undefined ? Number(stock.stock_unidades) : null;
            
            if (!Number.isFinite(stockActual)) return;

            document
                .querySelectorAll(`.product-card[data-type="PRODUCT"][data-id="${productoId}"]`)
                .forEach(card => {
                    // Actualizar datasets
                    card.dataset.stock = String(stockActual);
                    if (stockUnidades !== null) {
                        card.dataset.stockUnidades = String(stockUnidades);
                    }
                    
                    // Actualizar etiquetas visuales
                    const badges = card.querySelectorAll('.product-badge-stock');
                    badges.forEach(badge => {
                        const title = badge.getAttribute('title');
                        if (title === 'Stock Cerrado') {
                            badge.textContent = `St: ${stockActual}`;
                        } else if (title === 'Stock Unidades' && stockUnidades !== null) {
                            badge.textContent = `Un: ${stockUnidades}`;
                        } else if (!title) {
                            // Fallback para cuando no tiene title (diseño original)
                            badge.textContent = `St: ${stockActual}`;
                        }
                    });
                });

            // Mantener actualizado el cache del carrito para futuras ventas.
            cart.forEach(item => {
                if (item.type === 'PRODUCT' && String(item.id) === productoId) {
                    item.stock = stockActual;
                    if (stockUnidades !== null) {
                        item.unitsStock = stockUnidades;
                    }
                }
            });
        });
    }

    if (btnSuccessClose) {
        btnSuccessClose.addEventListener('click', function () {
            successModal.classList.remove('show');
            // Limpiar carrito para nueva venta
            cart = [];
            renderCart();
            // Resetear selección de vendedor → Venta Directa
            setDirectSale();
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 14. HELPERS DE UI DEL MODAL
    // ─────────────────────────────────────────────────────────────────────────
    function showCheckoutError(msg) {
        if (checkoutError) {
            checkoutError.textContent = msg;
            checkoutError.style.display = 'block';
        }
    }

    function hideCheckoutError() {
        if (checkoutError) {
            checkoutError.textContent = '';
            checkoutError.style.display = 'none';
        }
    }

    function setBtnConfirmLoading(loading) {
        if (!btnConfirmCheckout) return;
        btnConfirmCheckout.disabled = loading;
        if (checkoutBtnText) {
            checkoutBtnText.textContent = loading ? '⏳ Procesando...' : '✔ REGISTRAR VENTA';
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 15. MÓVIL: APERTURA Y CIERRE DEL DRAWER DEL CARRITO
    // ─────────────────────────────────────────────────────────────────────────

    function openMobileCart() {
        if (!posCartPanel) return;
        posCartPanel.classList.add('show-mobile');
        if (mobileCartOverlay) {
            mobileCartOverlay.style.display = 'block';
            // Pequeño delay para permitir que el display:block se aplique antes de la transición
            setTimeout(() => {
                mobileCartOverlay.classList.add('show');
            }, 10);
        }
        document.body.style.overflow = 'hidden';
    }

    function closeMobileCart() {
        if (!posCartPanel) return;
        posCartPanel.classList.remove('show-mobile');
        if (mobileCartOverlay) {
            mobileCartOverlay.classList.remove('show');
            setTimeout(() => {
                if (!posCartPanel.classList.contains('show-mobile')) {
                    mobileCartOverlay.style.display = 'none';
                }
            }, 300);
        }
        document.body.style.overflow = '';
    }

    if (btnOpenMobileCart)  btnOpenMobileCart.addEventListener('click',  openMobileCart);
    if (btnCloseMobileCart) btnCloseMobileCart.addEventListener('click', closeMobileCart);
    if (mobileCartOverlay)  mobileCartOverlay.addEventListener('click',  closeMobileCart);

    // ─────────────────────────────────────────────────────────────────────────
    // 16. UTILIDAD: ESCAPE HTML
    // ─────────────────────────────────────────────────────────────────────────
    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INICIALIZACIÓN
    // ─────────────────────────────────────────────────────────────────────────
    renderCart(); // carrito vacío al cargar
});
