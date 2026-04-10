// ============================================
// assets/js/app.js - Visión Real
// ============================================

// BASE_URL must be set inline in each page before this script, OR we detect it
const BASE_URL = window.BASE_URL || window.location.origin + '/vision_real';

function showToast(message, type = 'info') {
  const container = document.getElementById('toast-container');
  if (!container) return;
  const icons = { success: '✓', error: '✕', info: '◈' };
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `<span>${icons[type] || '•'}</span><span>${message}</span>`;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(60px)';
    toast.style.transition = '0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

const menuToggle = document.getElementById('menu-toggle');
const sidebar = document.getElementById('sidebar');
if (menuToggle && sidebar) {
  menuToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
}

function openModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.add('open');
}

function closeModal(id) {
  const m = document.getElementById(id);
  if (m) {
    m.classList.remove('open');
    const f = m.querySelector('form');
    if (f) f.reset();
    const h = m.querySelector('input[name="id"]');
    if (h) h.value = '';
  }
}

document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('open');
});

function confirmDelete(msg, cb) {
  if (confirm(msg || '¿Eliminar este registro?')) cb();
}

async function apiPost(url, data) {
  const fd = new FormData();
  Object.entries(data).forEach(([k, v]) => fd.append(k, v));
  const res = await fetch(url, { method: 'POST', body: fd });
  return res.json();
}

function formatCurrency(v) {
  return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(v);
}

function initTableSearch(inputId, tableId) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);
  if (!input || !table) return;
  input.addEventListener('input', () => {
    const q = input.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

// ============================================
// PRODUCTS MODULE
// ============================================
const ProductosModule = {
  init() { initTableSearch('search-productos', 'table-productos'); },

  openCreate() {
    document.getElementById('modal-producto-title').textContent = 'Nuevo Producto';
    document.getElementById('form-producto').reset();
    document.getElementById('producto-id').value = '';
    openModal('modal-producto');
  },

  openEdit(id, nombre, descripcion, precio, stock, categoria_id) {
    document.getElementById('modal-producto-title').textContent = 'Editar Producto';
    document.getElementById('producto-id').value = id;
    document.getElementById('producto-nombre').value = nombre;
    document.getElementById('producto-descripcion').value = descripcion;
    document.getElementById('producto-precio').value = precio;
    document.getElementById('producto-stock').value = stock;
    document.getElementById('producto-categoria').value = categoria_id;
    openModal('modal-producto');
  },

  async save() {
    const id          = document.getElementById('producto-id').value;
    const nombre      = document.getElementById('producto-nombre').value.trim();
    const precio      = document.getElementById('producto-precio').value;
    const stock       = document.getElementById('producto-stock').value;
    const categoria   = document.getElementById('producto-categoria').value;
    const descripcion = document.getElementById('producto-descripcion').value;

    if (!nombre || !precio || stock === '') { showToast('Completa los campos obligatorios', 'error'); return; }

    const action = id ? 'update' : 'create';
    const data = { action, nombre, precio, stock, categoria_id: categoria, descripcion };
    if (id) data.id = id;

    const res = await apiPost(BASE_URL + '/controllers/ProductoController.php', data);
    if (res.success) { showToast(res.success, 'success'); closeModal('modal-producto'); setTimeout(() => location.reload(), 1000); }
    else showToast(res.error || 'Error', 'error');
  },

  async delete(id, nombre) {
    confirmDelete(`¿Eliminar "${nombre}"?`, async () => {
      const res = await apiPost(BASE_URL + '/controllers/ProductoController.php', { action: 'delete', id });
      if (res.success) { showToast(res.success, 'success'); setTimeout(() => location.reload(), 800); }
      else showToast(res.error || 'Error', 'error');
    });
  }
};

// ============================================
// CLIENTS MODULE
// ============================================
const ClientesModule = {
  init() { initTableSearch('search-clientes', 'table-clientes'); },

  openCreate() {
    document.getElementById('modal-cliente-title').textContent = 'Nuevo Cliente';
    document.getElementById('form-cliente').reset();
    document.getElementById('cliente-id').value = '';
    openModal('modal-cliente');
  },

  openEdit(id, nombre, telefono, email, direccion) {
    document.getElementById('modal-cliente-title').textContent = 'Editar Cliente';
    document.getElementById('cliente-id').value = id;
    document.getElementById('cliente-nombre').value = nombre;
    document.getElementById('cliente-telefono').value = telefono;
    document.getElementById('cliente-email').value = email;
    document.getElementById('cliente-direccion').value = direccion;
    openModal('modal-cliente');
  },

  async save() {
    const id        = document.getElementById('cliente-id').value;
    const nombre    = document.getElementById('cliente-nombre').value.trim();
    const telefono  = document.getElementById('cliente-telefono').value;
    const email     = document.getElementById('cliente-email').value;
    const direccion = document.getElementById('cliente-direccion').value;

    if (!nombre) { showToast('El nombre es obligatorio', 'error'); return; }

    const action = id ? 'update' : 'create';
    const data = { action, nombre, telefono, email, direccion };
    if (id) data.id = id;

    const res = await apiPost(BASE_URL + '/controllers/ClienteController.php', data);
    if (res.success) { showToast(res.success, 'success'); closeModal('modal-cliente'); setTimeout(() => location.reload(), 1000); }
    else showToast(res.error || 'Error', 'error');
  },

  async delete(id, nombre) {
    confirmDelete(`¿Eliminar a "${nombre}"?`, async () => {
      const res = await apiPost(BASE_URL + '/controllers/ClienteController.php', { action: 'delete', id });
      if (res.success) { showToast(res.success, 'success'); setTimeout(() => location.reload(), 800); }
      else showToast(res.error || 'Error', 'error');
    });
  }
};

// ============================================
// SALES MODULE
// ============================================
const VentasModule = {
  cart: [],
  productos: [],

  init(productos) {
    this.productos = productos;
    this.renderCart();
    initTableSearch('search-ventas', 'table-ventas');
  },

  addProducto() {
    const sel = document.getElementById('sel-producto');
    const qty = parseInt(document.getElementById('sel-qty').value) || 1;
    const idx = parseInt(sel.value);
    if (isNaN(idx) || idx < 0 || sel.value === '') { showToast('Selecciona un producto', 'error'); return; }
    const prod = this.productos[idx];
    if (!prod) return;
    if (qty <= 0) { showToast('Cantidad inválida', 'error'); return; }
    if (qty > prod.stock) { showToast(`Stock insuficiente. Disponible: ${prod.stock}`, 'error'); return; }
    const existing = this.cart.find(i => i.producto_id == prod.id);
    if (existing) {
      const nq = existing.cantidad + qty;
      if (nq > prod.stock) { showToast('Stock insuficiente', 'error'); return; }
      existing.cantidad = nq;
    } else {
      this.cart.push({ producto_id: prod.id, nombre: prod.nombre, precio: parseFloat(prod.precio), cantidad: qty, stock: prod.stock });
    }
    this.renderCart();
    sel.value = '';
    document.getElementById('sel-qty').value = 1;
  },

  removeItem(idx) { this.cart.splice(idx, 1); this.renderCart(); },

  updateQty(idx, val) {
    const qty = parseInt(val);
    if (qty <= 0) { this.removeItem(idx); return; }
    if (qty > this.cart[idx].stock) { showToast('Stock insuficiente', 'error'); return; }
    this.cart[idx].cantidad = qty;
    this.renderCart();
  },

  renderCart() {
    const container = document.getElementById('cart-items');
    const totalEl   = document.getElementById('cart-total');
    if (!container) return;
    if (this.cart.length === 0) {
      container.innerHTML = '<p style="color:var(--white-muted);text-align:center;padding:20px;font-size:.85rem">No hay productos</p>';
      if (totalEl) totalEl.textContent = '$0';
      return;
    }
    let total = 0;
    container.innerHTML = this.cart.map((item, i) => {
      const sub = item.precio * item.cantidad;
      total += sub;
      return `<div class="cart-item">
        <div class="cart-item-name"><strong>${item.nombre}</strong><br><small style="color:var(--white-muted)">${formatCurrency(item.precio)} c/u</small></div>
        <input type="number" min="1" max="${item.stock}" value="${item.cantidad}" onchange="VentasModule.updateQty(${i},this.value)" style="width:60px;text-align:center;padding:6px 8px">
        <div class="cart-item-price">${formatCurrency(sub)}</div>
        <button class="btn btn-icon btn-danger" onclick="VentasModule.removeItem(${i})">✕</button>
      </div>`;
    }).join('');
    if (totalEl) totalEl.textContent = formatCurrency(total);
  },

  async save() {
    if (this.cart.length === 0) { showToast('Agrega al menos un producto', 'error'); return; }
    const cliente_id = document.getElementById('venta-cliente').value;
    const notas      = document.getElementById('venta-notas').value;
    const payload = {
      action: 'create',
      cliente_id: cliente_id || 0,
      notas,
      productos: JSON.stringify(this.cart.map(i => ({ producto_id: i.producto_id, cantidad: i.cantidad, precio: i.precio })))
    };
    const res = await apiPost(BASE_URL + '/controllers/VentaController.php', payload);
    if (res.success) {
      showToast('Venta registrada exitosamente', 'success');
      this.cart = [];
      this.renderCart();
      closeModal('modal-venta');
      setTimeout(() => location.reload(), 1200);
    } else {
      showToast(res.error || 'Error al registrar venta', 'error');
    }
  }
};
