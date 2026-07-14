(function () {
  const config = window.POS_CONFIG || {};
  const baseUrl = config.baseUrl || window.BASE_URL || '';
  const endpoints = config.endpoints || {};
  const auth = config.auth || {};
  const defaultCustomer = config.defaultCustomer || { nombre: 'Consumidor Final', telefono: '', sexo: 'O' };
  const canEditPrice = !!auth.can_edit_price;
  const canSeeMargin = !!auth.can_see_margin;
  const defaultProductImage = `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240" role="img" aria-label="Sin imagen">
      <defs>
        <linearGradient id="g" x1="0" x2="1" y1="0" y2="1">
          <stop offset="0" stop-color="#2a2239"/>
          <stop offset="1" stop-color="#16111f"/>
        </linearGradient>
      </defs>
      <rect width="240" height="240" rx="28" fill="url(#g)"/>
      <rect x="44" y="44" width="152" height="152" rx="22" fill="#0f0d16" stroke="#d6b25f" stroke-opacity=".35"/>
      <circle cx="95" cy="92" r="14" fill="#d6b25f" fill-opacity=".9"/>
      <path d="M58 168l38-42 28 31 22-20 36 31H58z" fill="#d6b25f" fill-opacity=".28"/>
      <text x="120" y="214" text-anchor="middle" font-family="Arial, sans-serif" font-size="16" fill="#d6b25f">SIN IMAGEN</text>
    </svg>
  `)}`;

  const els = {
    categoryStrip: document.getElementById('vr-categories'),
    search: document.getElementById('vr-search'),
    searchBtn: document.getElementById('vr-search-btn'),
    orderBar: document.getElementById('vr-orders'),
    grid: document.getElementById('vr-grid'),
    gridTitle: document.getElementById('vr-grid-title'),
    gridMeta: document.getElementById('vr-grid-meta'),
    cartList: document.getElementById('vr-cart-list'),
    cartCustomer: document.getElementById('vr-cart-customer'),
    subtotal: document.getElementById('vr-subtotal'),
    discount: document.getElementById('vr-discount'),
    total: document.getElementById('vr-total'),
    itemsCount: document.getElementById('vr-items-count'),
    unitsCount: document.getElementById('vr-units-count'),
    customerName: document.getElementById('vr-customer-name'),
    customerNit: document.getElementById('vr-customer-nit'),
    customerPhone: document.getElementById('vr-customer-phone'),
    notes: document.getElementById('vr-notes'),
    sexButtons: Array.from(document.querySelectorAll('[data-sex]')),
    checkoutBtn: document.getElementById('vr-checkout'),
    quickFinalBtn: document.getElementById('vr-final-customer'),
    clearBtn: document.getElementById('vr-clear'),
    clearQuickBtn: document.getElementById('vr-clear-quick'),
    discountManual: document.getElementById('vr-discount-manual'),
    hint: document.getElementById('vr-hint'),
  };

  const state = {
    categories: [],
    products: [],
    favorites: new Set(),
    order: 'popular',
    categoryId: 0,
    query: '',
    cart: [],
    cartSelected: -1,
    customer: { ...defaultCustomer },
    discountAuto: 0,
    discountManual: 0,
    currentRequestId: 0,
    discountTimer: null,
    selectedProductId: null,
    tallasModal: null,
  };

  function money(value) {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(Number(value || 0));
  }

  function productImageUrl(product) {
    return product?.imagen_url || defaultProductImage;
  }

  function stockClass(stock) {
    const s = Number(stock || 0);
    if (s <= 0) return 'stock-empty';
    if (s < 5) return 'stock-low';
    if (s <= 20) return 'stock-mid';
    return 'stock-high';
  }

  function escapeHtml(text) {
    return String(text || '').replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
  }

  function toast(message, type = 'info') {
    let node = document.getElementById('vr-toast');
    if (!node) {
      node = document.createElement('div');
      node.id = 'vr-toast';
      node.className = 'vr-toast';
      document.body.appendChild(node);
    }
    node.textContent = message;
    node.className = `vr-toast is-show vr-toast--${type}`;
    clearTimeout(node._timer);
    node._timer = setTimeout(() => {
      node.className = 'vr-toast';
    }, 2200);
  }

  window.showToast = window.showToast || toast;

  function apiUrl(name) {
    return endpoints[name] || '';
  }

  function withQuery(url, params = {}) {
    const parsed = new URL(url, window.location.origin);
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        parsed.searchParams.set(key, value);
      }
    });
    return parsed.toString();
  }

  async function fetchJson(url, options = {}) {
    const response = await fetch(url, {
      credentials: 'same-origin',
      cache: 'no-store',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers || {}),
      },
      ...options,
    });
    const data = await response.json().catch(() => null);
    if (!response.ok) {
      const error = data && (data.error || data.message) ? (data.error || data.message) : `Error HTTP ${response.status}`;
      throw new Error(error);
    }
    return data;
  }

  function renderCategories() {
    if (!els.categoryStrip) return;
    els.categoryStrip.innerHTML = state.categories.map((cat) => {
      const active = Number(cat.id) === Number(state.categoryId) ? 'is-active' : '';
      return `<button type="button" class="vr-pos__category ${active}" data-category="${Number(cat.id)}">${escapeHtml(cat.nombre)} <span style="opacity:.72">(${Number(cat.total || 0)})</span></button>`;
    }).join('');
  }

  function renderOrders() {
    if (!els.orderBar) return;
    const items = [
      { key: 'popular', label: 'Más vendidos' },
      { key: 'recent', label: 'Recientes' },
      { key: 'favorites', label: 'Favoritos' },
      { key: 'alpha', label: 'A-Z' },
      { key: 'stock', label: 'Stock' },
    ];
    els.orderBar.innerHTML = items.map((item) => `<button type="button" class="vr-pos__sort ${state.order === item.key ? 'is-active' : ''}" data-order="${item.key}">${item.label}</button>`).join('');
    syncOrderButtons();
  }

  function syncOrderButtons() {
    document.querySelectorAll('[data-order]').forEach((btn) => {
      btn.classList.toggle('is-active', btn.dataset.order === state.order);
    });
  }

  function cardTemplate(product) {
    const stock = Number(product.stock || 0);
    const hasStock = stock > 0;
    const favClass = product.favorito ? 'is-fav' : '';
    const disabledClass = hasStock ? '' : 'is-disabled';
    const stockText = hasStock ? `${stock} en stock` : 'SIN STOCK';
    const category = product.categoria_nombre || 'Sin categoría';
    const code = product.codigo_barras || product.codigo || 'SIN-COD';
    const displayCode = product.referencia ? `${code} · ${product.referencia}` : code;
    const image = productImageUrl(product);
    const defaultSize = product.talla_defecto ? ` · Talla ${escapeHtml(product.talla_defecto)}` : '';
    return `
      <article class="vr-card ${disabledClass}" data-product-id="${product.id}" data-has-stock="${hasStock ? 1 : 0}">
        <button type="button" class="vr-card__fav ${favClass}" data-favorite="${product.id}" title="Favorito">${product.favorito ? '★' : '☆'}</button>
        <div class="vr-card__imgWrap">
          <img class="vr-card__img" src="${image}" alt="${escapeHtml(product.nombre)}" loading="lazy" decoding="async" onerror="this.style.display='none';this.nextElementSibling.style.display='grid'">
          <div class="vr-card__imgPlaceholder" style="display:none">🛍️</div>
        </div>
        <div class="vr-card__body">
          <div class="vr-card__code">${escapeHtml(displayCode)}</div>
          <div class="vr-card__name">${escapeHtml(product.nombre)}</div>
          <div class="vr-card__meta">
            <div class="vr-card__price">${money(product.precio)}</div>
            <span class="vr-card__badge ${stockClass(stock)}">${stockText}</span>
          </div>
        </div>
        <div class="vr-card__footer">
          <div class="vr-card__category">${escapeHtml(category)}${defaultSize}</div>
          <div class="vr-card__order">${Number(product.vendidos || 0)} vendidos</div>
        </div>
      </article>
    `;
  }

  function renderProducts(products, title, meta) {
    state.products = products || [];
    if (els.gridTitle) els.gridTitle.textContent = title || 'Catálogo';
    if (els.gridMeta) els.gridMeta.textContent = meta || `${state.products.length} productos`;
    if (!els.grid) return;

    if (!state.products.length) {
      els.grid.innerHTML = `<div class="vr-cart__empty" style="grid-column:1/-1">No hay productos para mostrar.</div>`;
      return;
    }

    els.grid.innerHTML = state.products.map(cardTemplate).join('');
  }

  function renderCart() {
    if (!els.cartList) return;
    if (!state.cart.length) {
      els.cartList.innerHTML = `<div class="vr-cart__empty">Sin productos. Escanea o haz clic en una tarjeta.</div>`;
      els.itemsCount.textContent = '0';
      els.unitsCount.textContent = '0';
      els.subtotal.textContent = money(0);
      els.discount.textContent = money(0);
      els.total.textContent = money(0);
      if (els.cartCustomer) {
        els.cartCustomer.textContent = `${state.customer.nombre || defaultCustomer.nombre}${state.customer.telefono ? ' · ' + state.customer.telefono : ''}`;
      }
      return;
    }

    let subtotal = 0;
    let units = 0;
    const html = state.cart.map((item, index) => {
      const itemSubtotal = Number(item.price) * Number(item.qty);
      subtotal += itemSubtotal;
      units += Number(item.qty);
      const margin = canSeeMargin && item.cost != null ? Math.max(0, (Number(item.price) - Number(item.cost)) * Number(item.qty)) : null;
      const isSelected = index === state.cartSelected ? 'is-selected' : '';
      const image = item.image || defaultProductImage;
      const talla = item.talla ? ` · Talla ${escapeHtml(item.talla)}` : '';
      const marginHtml = canSeeMargin ? `<div class="vr-cart-item__sub">Utilidad: ${margin == null ? 'N/D' : money(margin)}</div>` : '';
      const priceControl = canEditPrice ? `<input type="number" step="1" min="0" value="${Number(item.price)}" data-action="price" data-index="${index}" aria-label="Precio">` : '';
      return `
        <div class="vr-cart-item ${isSelected}" data-cart-index="${index}">
          <img class="vr-cart-item__img" src="${image}" alt="${escapeHtml(item.name)}" loading="lazy" onerror="this.onerror=null;this.src='${defaultProductImage}'">
          <div>
            <div class="vr-cart-item__name">${escapeHtml(item.name)}</div>
            <div class="vr-cart-item__sub">${escapeHtml(item.code || '')}${talla}</div>
            ${priceControl ? `<div style="margin-top:6px">${priceControl}</div>` : ''}
            ${marginHtml}
          </div>
          <div class="vr-cart-item__controls">
            <div class="vr-cart-item__qty">
              <button type="button" data-action="dec" data-index="${index}">-</button>
              <input type="number" min="1" value="${Number(item.qty)}" data-action="qty" data-index="${index}">
              <button type="button" data-action="inc" data-index="${index}">+</button>
            </div>
            <div class="vr-cart-item__price">${money(itemSubtotal)}</div>
            <button type="button" class="vr-mini-btn" data-action="remove" data-index="${index}">Eliminar</button>
          </div>
        </div>
      `;
    }).join('');

    els.cartList.innerHTML = html;
    els.itemsCount.textContent = String(state.cart.length);
    els.unitsCount.textContent = String(units);
    els.subtotal.textContent = money(subtotal);
    const manual = Number(state.discountManual || 0);
    const auto = Number(state.discountAuto || 0);
    const totalDiscount = Math.min(subtotal, Math.max(0, auto + manual));
    els.discount.textContent = `-${money(totalDiscount)}`;
    els.total.textContent = money(Math.max(0, subtotal - totalDiscount));
    if (els.cartCustomer) {
      els.cartCustomer.textContent = `${state.customer.nombre || defaultCustomer.nombre}${state.customer.telefono ? ' · ' + state.customer.telefono : ''}`;
    }
  }

  function getCurrentFilters() {
    return {
      categoria_id: state.categoryId,
      q: state.query,
      order: state.order,
      limit: 120,
      offset: 0,
    };
  }

  function catalogKey(filters) {
    return `${filters.categoria_id}|${filters.q}|${filters.order}|${filters.limit}|${filters.offset}`;
  }

  async function loadCatalog({ force = false } = {}) {
    const filters = getCurrentFilters();
    const params = new URLSearchParams(filters);
    const reqId = ++state.currentRequestId;
    renderProducts([], 'Cargando...', 'Consultando productos...');
    try {
      const data = await fetchJson(withQuery(apiUrl('catalog'), Object.fromEntries(params.entries())));
      if (reqId !== state.currentRequestId) return;
      const products = data.products || [];
      renderProducts(products, state.query ? `Búsqueda: ${state.query}` : 'Catálogo', `${products.length} productos`);
    } catch (error) {
      if (reqId !== state.currentRequestId) return;
      renderProducts([], 'Catálogo', 'Error');
      toast(error.message || 'No se pudo cargar el catálogo', 'error');
    }
  }

  async function loadBootstrap() {
    try {
      const data = await fetchJson(withQuery(apiUrl('bootstrap'), { action: 'bootstrap' }));
      state.categories = data.categories || [];
      renderCategories();
      renderOrders();
      setCustomer(defaultCustomer.nombre, defaultCustomer.nit || '', defaultCustomer.telefono || '', defaultCustomer.sexo);
      renderProducts(data.popular || [], 'Más vendidos', `${(data.popular || []).length} productos`);
    } catch (error) {
      toast(error.message || 'No se pudo iniciar el POS', 'error');
    }
  }

  function addOrIncrementProduct(product, tallaOverride = '', stockOverride = null) {
    if (!product) return;
    if (Number(product.stock || 0) <= 0) {
      toast('Producto sin stock', 'error');
      return;
    }

    const talla = (tallaOverride || product.talla_defecto || '').trim();
    const key = `${product.id}|${talla}`;
    const stockMax = Number(stockOverride ?? product.stock ?? 0);
    const found = state.cart.find((item) => item.key === key);
    if (found) {
      if (Number(found.qty || 0) >= Number(found.stock || stockMax)) {
        toast(
          `Stock máximo alcanzado para esta talla${talla ? ` (${talla})` : ''}.`,
          'error'
        );
        return;
      }
      found.qty += 1;
    } else {
      state.cart.unshift({
        key,
        product_id: product.id,
        name: product.nombre,
        code: product.codigo_barras || product.codigo || '',
        category_id: Number(product.categoria_id || 0),
        category_name: product.categoria_nombre || '',
        price: Number(product.precio || 0),
        cost: product.costo != null ? Number(product.costo) : null,
        qty: 1,
        stock: stockMax > 0 ? stockMax : Number(product.stock || 0),
        talla,
        image: productImageUrl(product),
      });
    }
    state.cartSelected = 0;
    renderCart();
    scheduleDiscount();
    flashCard(product.id);
    toast(`${product.nombre} agregado`, 'success');
  }

  function flashCard(productId) {
    const card = els.grid?.querySelector(`[data-product-id="${productId}"]`);
    if (!card) return;
    card.classList.add('is-added');
    setTimeout(() => card.classList.remove('is-added'), 260);
  }

  function setCustomer(name, nit, phone, sex) {
    state.customer = {
      nombre: name || defaultCustomer.nombre,
      nit: nit || defaultCustomer.nit || '',
      telefono: phone || defaultCustomer.telefono || '',
      sexo: sex || defaultCustomer.sexo,
    };
    if (els.customerName) els.customerName.value = state.customer.nombre === defaultCustomer.nombre ? '' : state.customer.nombre;
    if (els.customerNit) els.customerNit.value = state.customer.nit || '';
    if (els.customerPhone) els.customerPhone.value = state.customer.telefono || '';
    updateSexUi(state.customer.sexo);
    renderCart();
  }

  function updateSexUi(sex) {
    state.customer.sexo = sex;
    els.sexButtons.forEach((btn) => {
      btn.classList.toggle('is-active', btn.dataset.sex === sex);
    });
  }

  function scheduleDiscount() {
    clearTimeout(state.discountTimer);
    state.discountTimer = setTimeout(updateDiscount, 180);
  }

  async function updateDiscount() {
    if (!state.cart.length) {
      state.discountAuto = 0;
      state.discountManual = 0;
      if (els.discountManual) els.discountManual.value = '0';
      renderCart();
      return;
    }

    const body = new URLSearchParams();
    body.set('action', 'calcular');
    body.set('genero', state.customer.sexo || 'O');
    body.set('cliente_id', '');
    body.set('items', JSON.stringify(state.cart.map((item) => ({
      producto_id: item.product_id,
      categoria_id: item.category_id,
      precio: item.price,
      cantidad: item.qty,
    }))));

    try {
      const data = await fetchJson(document.getElementById('vr-discount-url').value, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      });
      state.discountAuto = Number(data?.descuento?.monto || 0);
    } catch (error) {
      state.discountAuto = 0;
    }
    renderCart();
  }

  function findProductInCurrentView(productId) {
    return state.products.find((p) => Number(p.id) === Number(productId)) || null;
  }

  function getProductSizeEndpoint() {
    return apiUrl('tallas') || `${baseUrl}/controllers/VentaPosController.php?action=tallas`;
  }

  function closeSizeModal() {
    const modal = document.getElementById('vr-size-modal');
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    state.tallasModal = null;
  }

  function openSizeModal(product, tallas) {
    const modal = document.getElementById('vr-size-modal');
    const title = document.getElementById('vr-size-title');
    const list = document.getElementById('vr-size-list');
    const stock = document.getElementById('vr-size-stock');
    if (!modal || !title || !list || !stock) return;

    state.tallasModal = { product, tallas };
    title.textContent = product.nombre || 'Producto';
    stock.textContent = `${(tallas || []).length} tallas disponibles`;
    list.innerHTML = (tallas || []).map((item) => {
      const available = Number(item.stock || 0);
      const disabled = available <= 0 ? 'disabled' : '';
      return `
        <button type="button" class="vr-size-btn ${available <= 0 ? 'is-empty' : ''}" data-size="${escapeHtml(item.talla || '')}" data-stock="${available}" ${disabled}>
          <span class="vr-size-btn__name">${escapeHtml(item.talla || '')}</span>
          <span class="vr-size-btn__stock">${available > 0 ? `${available} disponibles` : 'Sin stock'}</span>
        </button>
      `;
    }).join('');
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
  }

  async function loadSizesForProduct(product) {
    const response = await fetchJson(withQuery(getProductSizeEndpoint(), { producto_id: product.id }));
    return response.tallas || [];
  }

  async function handleProductSelect(product) {
    if (!product) return;
    if (Number(product.stock || 0) <= 0) {
      toast('Producto sin stock', 'error');
      return;
    }

    const countSizes = Number(product.tallas_count || 0);
    if (countSizes > 0) {
      try {
        const sizes = await loadSizesForProduct(product);
        if (!sizes.length) {
          addOrIncrementProduct(product, product.talla_defecto || '', Number(product.stock || 0));
          return;
        }
        if (sizes.length === 1) {
          addOrIncrementProduct(product, sizes[0].talla || product.talla_defecto || '', Number(sizes[0].stock || 0));
          return;
        }
        openSizeModal(product, sizes);
        return;
      } catch (error) {
        toast(error.message || 'No se pudieron cargar las tallas', 'error');
        return;
      }
    }

    addOrIncrementProduct(product, product.talla_defecto || '', Number(product.stock || 0));
  }

  async function lookupBarcode(term, { silent = false } = {}) {
    if (!term) return;
    try {
      const data = await fetchJson(withQuery(apiUrl('barcode'), { action: 'barcode', term }));
      if (!data.producto) throw new Error('Producto no encontrado');
      if (Number(data.producto.tiene_tallas || 0) && Array.isArray(data.producto.tallas) && data.producto.tallas.length > 1) {
        openSizeModal(data.producto, data.producto.tallas);
        return;
      }
      const sizeList = Array.isArray(data.producto.tallas) ? data.producto.tallas : [];
      const onlySize = sizeList[0] || null;
      addOrIncrementProduct(
        data.producto,
        data.producto.auto_talla || (onlySize?.talla || ''),
        Number(onlySize?.stock ?? data.producto.stock ?? 0)
      );
    } catch (error) {
      if (!silent) toast(error.message || 'Código no encontrado', 'error');
      throw error;
    }
  }

  async function submitCheckout() {
    if (!state.cart.length) {
      toast('Agregue productos antes de registrar', 'error');
      return;
    }

    const payload = {
      action: 'checkout',
      items: state.cart.map((item) => ({
        producto_id: item.product_id,
        cantidad: item.qty,
        precio: canEditPrice ? Number(item.price) : Number(item.price),
        talla: item.talla || '',
      })),
      cliente_nombre: (state.customer.nombre || '').trim() || defaultCustomer.nombre,
      cliente_telefono: state.customer.telefono || '',
      cliente_nit: (state.customer.nit || '').trim() || '',
      sexo: state.customer.sexo || 'O',
      notas: els.notes ? els.notes.value : '',
      descuento_total: Number(state.discountAuto || 0) + Number(state.discountManual || 0),
      descuento: Number(state.discountAuto || 0) + Number(state.discountManual || 0),
      descuento_id: '',
      descuento_aplicado: state.discountAuto > 0 ? 'Descuento automático' : '',
    };

    try {
      const data = await fetchJson(apiUrl('checkout'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      if (!data.success) {
        throw new Error(data.error || 'No se pudo registrar la venta');
      }
      if (data.cliente_guardado === false) {
        toast(`Venta registrada${data.numero_factura ? ' · ' + data.numero_factura : ''}, PERO el cliente no se guardó: ${data.cliente_error || 'motivo desconocido'}`, 'error');
      } else {
        toast(`Venta registrada${data.numero_factura ? ' · ' + data.numero_factura : ''}`, 'success');
      }

      const facturaUrl = `${baseUrl}/controllers/FacturaController.php?action=generar_pdf&venta_id=${encodeURIComponent(data.venta_id)}`;
      try {
        const facturaData = await fetchJson(facturaUrl);
        if (!facturaData?.html) {
          throw new Error('Factura no disponible');
        }
        const blob = new Blob([facturaData.html], { type: 'text/html;charset=utf-8' });
        const blobUrl = URL.createObjectURL(blob);
        const win = window.open(blobUrl, '_blank');
        if (!win) {
          URL.revokeObjectURL(blobUrl);
          toast('Factura creada. El navegador bloqueó la nueva pestaña.', 'info');
        } else {
          win.focus();
          setTimeout(() => URL.revokeObjectURL(blobUrl), 60000);
        }
      } catch (invoiceError) {
        toast(invoiceError.message || 'La factura no pudo abrirse', 'info');
      }

      state.cart = [];
      state.discountAuto = 0;
      state.discountManual = 0;
      state.cartSelected = -1;
      renderCart();
      if (els.discountManual) els.discountManual.value = '0';
      if (els.notes) els.notes.value = '';
      await loadCatalog({ force: true });
    } catch (error) {
      toast(error.message || 'No se pudo registrar la venta', 'error');
    } finally {
      els.checkoutBtn.disabled = false;
      els.checkoutBtn.textContent = 'Registrar venta';
    }
  }

  function removeCartItem(index) {
    state.cart.splice(index, 1);
    state.cartSelected = Math.min(state.cartSelected, state.cart.length - 1);
    renderCart();
    scheduleDiscount();
  }

  function changeQty(index, qty) {
    const item = state.cart[index];
    if (!item) return;
    const requested = Number(qty || 1);
    const next = Math.max(1, requested);
    const max = Number(item.stock || 0);
    if (max > 0 && next > max) {
      item.qty = max;
      renderCart();
      scheduleDiscount();
      toast(
        `No hay suficiente inventario. La talla ${item.talla || ''} únicamente tiene ${max} unidades disponibles.`,
        'error'
      );
      return;
    }
    item.qty = next;
    renderCart();
    scheduleDiscount();
  }

  function changePrice(index, price) {
    const item = state.cart[index];
    if (!item) return;
    item.price = Math.max(0, Number(price || 0));
    renderCart();
    scheduleDiscount();
  }

  function moveQty(index, delta) {
    const item = state.cart[index];
    if (!item) return;
    changeQty(index, Number(item.qty || 1) + delta);
  }

  function clearCart(force = false) {
    if (!force && state.cart.length && !confirm('¿Cancelar la venta actual?')) {
      return;
    }
    state.cart = [];
    state.discountAuto = 0;
    state.discountManual = 0;
    state.cartSelected = -1;
    renderCart();
    if (els.discountManual) els.discountManual.value = '0';
    if (els.notes) els.notes.value = '';
    setCustomer(defaultCustomer.nombre, defaultCustomer.nit || '', defaultCustomer.telefono || '', defaultCustomer.sexo);
    toast('Venta cancelada', 'info');
  }

  async function toggleFavorite(productId) {
    try {
      const body = new URLSearchParams();
      body.set('action', 'toggle_favorito');
      body.set('producto_id', String(productId));
      const data = await fetchJson(apiUrl('toggleFavorite') || apiUrl('toggle_favorito') || `${baseUrl}/controllers/VentaPosController.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      });
      if (data.success) {
        state.products = [];
        await loadCatalog({ force: true });
        toast('Favoritos actualizados', 'success');
      } else {
        throw new Error(data.error || 'No se pudo actualizar favorito');
      }
    } catch (error) {
      toast(error.message || 'No se pudo actualizar favorito', 'error');
    }
  }

  function selectOrder(order) {
    state.order = order;
    renderOrders();
    loadCatalog({ force: true });
  }

  function selectCategory(categoryId) {
    state.categoryId = Number(categoryId || 0);
    renderCategories();
    loadCatalog({ force: true });
  }

  function syncCustomerInputs() {
    state.customer.nombre = (els.customerName?.value || '').trim() || defaultCustomer.nombre;
    state.customer.nit = (els.customerNit?.value || '').trim() || '';
    state.customer.telefono = (els.customerPhone?.value || '').trim() || '';
    renderCart();
  }

  function bindEvents() {
    if (els.categoryStrip) {
      els.categoryStrip.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-category]');
        if (!btn) return;
        selectCategory(btn.dataset.category);
      });
    }

    if (els.orderBar) {
      els.orderBar.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-order]');
        if (!btn) return;
        selectOrder(btn.dataset.order);
      });
    }

    document.querySelectorAll('.topbar-right [data-order]').forEach((btn) => {
      btn.addEventListener('click', () => selectOrder(btn.dataset.order));
    });

    if (els.grid) {
      els.grid.addEventListener('click', (e) => {
        const fav = e.target.closest('[data-favorite]');
        if (fav) {
          e.stopPropagation();
          toggleFavorite(Number(fav.dataset.favorite));
          return;
        }
        const card = e.target.closest('[data-product-id]');
        if (!card) return;
        const productId = Number(card.dataset.productId);
        const product = findProductInCurrentView(productId);
        if (!product) return;
        handleProductSelect(product);
      });
    }

    if (els.search) {
      let searchTimer = null;
      els.search.addEventListener('input', () => {
        state.query = els.search.value.trim();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
          if (!state.query) {
            loadCatalog({ force: true });
            return;
          }
          loadCatalog({ force: true });
        }, 120);
      });

      els.search.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          const term = els.search.value.trim();
          if (!term) {
            loadCatalog({ force: true });
            return;
          }
          lookupBarcode(term, { silent: true })
            .catch(() => {
              state.query = term;
              loadCatalog({ force: true });
            });
        }
      });
    }

    if (els.searchBtn) {
      els.searchBtn.addEventListener('click', () => {
        const term = els.search.value.trim();
        if (!term) loadCatalog({ force: true });
        else {
          state.query = term;
          loadCatalog({ force: true });
        }
      });
    }

    if (els.customerName) {
      els.customerName.addEventListener('input', syncCustomerInputs);
    }
    if (els.customerNit) {
      els.customerNit.addEventListener('input', syncCustomerInputs);
    }
    if (els.customerPhone) {
      els.customerPhone.addEventListener('input', syncCustomerInputs);
    }

    if (els.quickFinalBtn) {
      els.quickFinalBtn.addEventListener('click', () => {
        setCustomer(defaultCustomer.nombre, defaultCustomer.nit || '', defaultCustomer.telefono || '', defaultCustomer.sexo);
        if (els.customerName) els.customerName.value = '';
        if (els.customerNit) els.customerNit.value = '';
        if (els.customerPhone) els.customerPhone.value = '';
        toast('Consumidor final seleccionado', 'info');
      });
    }

    if (els.clearBtn) {
      els.clearBtn.addEventListener('click', () => clearCart(false));
    }
    if (els.clearQuickBtn) {
      els.clearQuickBtn.addEventListener('click', () => clearCart(false));
    }

    if (els.checkoutBtn) {
      els.checkoutBtn.addEventListener('click', submitCheckout);
    }

    if (els.discountManual) {
      els.discountManual.addEventListener('input', () => {
        state.discountManual = Number(els.discountManual.value || 0);
        renderCart();
      });
    }

    if (els.sexButtons.length) {
      els.sexButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
          updateSexUi(btn.dataset.sex);
          scheduleDiscount();
        });
      });
    }

    if (els.cartList) {
      els.cartList.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) {
          const row = e.target.closest('[data-cart-index]');
          if (row) {
            state.cartSelected = Number(row.dataset.cartIndex);
            renderCart();
          }
          return;
        }

        const index = Number(btn.dataset.index);
        const action = btn.dataset.action;
        state.cartSelected = index;

        if (action === 'inc') moveQty(index, 1);
        if (action === 'dec') moveQty(index, -1);
        if (action === 'remove') removeCartItem(index);
      });

      els.cartList.addEventListener('change', (e) => {
        const target = e.target;
        const index = Number(target.dataset.index);
        const action = target.dataset.action;
        if (action === 'qty') changeQty(index, target.value);
        if (action === 'price') changePrice(index, target.value);
      });
    }

    const sizeModal = document.getElementById('vr-size-modal');
    const sizeClose = document.getElementById('vr-size-close');
    if (sizeModal) {
      sizeModal.addEventListener('click', (e) => {
        if (e.target === sizeModal) closeSizeModal();
      });
    }
    if (sizeClose) {
      sizeClose.addEventListener('click', closeSizeModal);
    }
    const sizeList = document.getElementById('vr-size-list');
    if (sizeList) {
      sizeList.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-size]');
        if (!btn || !state.tallasModal) return;
        const talla = btn.dataset.size || '';
        const stock = Number(btn.dataset.stock || 0);
        addOrIncrementProduct(state.tallasModal.product, talla, stock);
        closeSizeModal();
      });
    }

    document.addEventListener('keydown', (e) => {
      const tag = document.activeElement && document.activeElement.tagName ? document.activeElement.tagName.toLowerCase() : '';
      const editing = ['input', 'textarea', 'select'].includes(tag);
      const modalOpen = document.getElementById('vr-size-modal')?.classList.contains('is-open');

      if (modalOpen && e.key === 'Escape') {
        e.preventDefault();
        closeSizeModal();
      } else if (e.key === 'F2') {
        e.preventDefault();
        els.search?.focus();
      } else if (e.key === 'F3') {
        e.preventDefault();
        els.customerName?.focus();
      } else if (e.key === 'F4') {
        e.preventDefault();
        submitCheckout();
      } else if (e.key === 'Escape') {
        e.preventDefault();
        clearCart(false);
      } else if (!editing && (e.key === '+' || e.key === '=')) {
        if (state.cartSelected >= 0) {
          e.preventDefault();
          moveQty(state.cartSelected, 1);
        }
      } else if (!editing && e.key === '-') {
        if (state.cartSelected >= 0) {
          e.preventDefault();
          moveQty(state.cartSelected, -1);
        }
      } else if (!editing && e.key === 'Delete') {
        if (state.cartSelected >= 0) {
          e.preventDefault();
          removeCartItem(state.cartSelected);
        }
      } else if (e.ctrlKey && e.key.toLowerCase() === 'f') {
        e.preventDefault();
        els.search?.focus();
      }
    });
  }

  function init() {
    bindEvents();
    setCustomer(defaultCustomer.nombre, defaultCustomer.telefono || '', defaultCustomer.sexo);
    if (els.customerName) els.customerName.value = '';
    if (els.customerPhone) els.customerPhone.value = '';
    renderCategories();
    renderOrders();
    renderCart();
    loadBootstrap();
    if (els.hint) {
      els.hint.innerHTML = `Atajos: <span class="vr-kbd">F2</span> buscar <span class="vr-kbd">F3</span> cliente <span class="vr-kbd">F4</span> registrar <span class="vr-kbd">Esc</span> cancelar <span class="vr-kbd">+</span>/<span class="vr-kbd">-</span> cantidad <span class="vr-kbd">Del</span> borrar`;
    }
  }

  document.addEventListener('DOMContentLoaded', init);
})();

