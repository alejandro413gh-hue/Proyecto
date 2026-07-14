/**
 * tienda/assets/js/tienda.js
 * Lógica cliente de la tienda online Visión Real.
 */

/* ════════════════════════════════════════════
   UTILIDADES
════════════════════════════════════════════ */

function toast(msg, type = 'success', duracion = 3500) {
  const t = document.createElement('div');
  t.className = `vr-toast ${type}`;
  t.innerHTML = `<span>${type === 'success' ? '✓' : '✕'}</span> ${msg}`;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), duracion);
}

function fmtPrecio(v) {
  return '$' + Number(v).toLocaleString('es-CO');
}

async function apiPost(url, data) {
  const fd = new FormData();
  for (const [k, v] of Object.entries(data)) fd.append(k, v);
  const r = await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', cache: 'no-store' });
  const text = await r.text();
  if (!r.ok) throw new Error(`HTTP ${r.status}: ${text}`);
  try {
    return JSON.parse(text);
  } catch (e) {
    throw new Error(`Invalid JSON response: ${text.slice(0, 300)}`);
  }
}

async function apiGet(url) {
  const r = await fetch(url, { credentials: 'same-origin', cache: 'no-store' });
  const text = await r.text();
  if (!r.ok) throw new Error(`HTTP ${r.status}: ${text}`);
  try {
    return JSON.parse(text);
  } catch (e) {
    throw new Error(`Invalid JSON response: ${text.slice(0, 300)}`);
  }
}

/* ════════════════════════════════════════════
   CARRITO
════════════════════════════════════════════ */

async function cargarCarrito() {
  if (!VR.loggedIn) {
    document.getElementById('carritoBody').innerHTML =
      `<div class="text-center text-muted py-5">
        <i class="bi bi-person-circle fs-1 d-block mb-2"></i>
        <a href="${VR.baseUrl}/tienda/login.php" class="btn btn-gold btn-sm">Inicia sesión para ver tu carrito</a>
      </div>`;
    return;
  }

  document.getElementById('carritoBody').innerHTML = '<div class="vr-spinner"></div>';

  try {
    const data = await apiGet(`${VR.baseUrl}/api/tienda/carrito.php?action=obtener&i=1`);
    if (data.error) throw new Error(data.error);
    renderCarrito(data);
  } catch (err) {
    document.getElementById('carritoBody').innerHTML =
      `<p class="text-center text-danger">Error al cargar el carrito: ${err?.message || 'conexión'}</p>`;
    document.getElementById('carritoFooter').style.display = 'none';
  }
}

function renderCarrito(data) {
  const body   = document.getElementById('carritoBody');
  const footer = document.getElementById('carritoFooter');

  if (!data.items || data.items.length === 0) {
    body.innerHTML = `<div class="text-center text-muted py-5">
      <i class="bi bi-bag fs-1 d-block mb-2"></i>Tu carrito está vacío</div>`;
    footer.style.display = 'none';
    return;
  }

  body.innerHTML = data.items.map(item => `
    <div class="carrito-item" id="citem-${item.id}">
      <img src="${item.imagen_url}" alt="${item.nombre}" class="carrito-item-img">
      <div class="carrito-item-info">
        <div class="carrito-item-name">${item.nombre}</div>
        ${item.talla ? `<div class="carrito-item-talla">Talla: <strong>${item.talla}</strong></div>` : ''}
        <div class="carrito-item-precio">${fmtPrecio(item.precio_unitario)}</div>
        <div class="carrito-item-actions">
          <button class="qty-btn" onclick="cambiarCantidad(${item.id}, ${item.cantidad - 1})">−</button>
          <span class="qty-value">${item.cantidad}</span>
          <button class="qty-btn" onclick="cambiarCantidad(${item.id}, ${item.cantidad + 1})">+</button>
          <button class="btn-remove-item" onclick="eliminarItem(${item.id})">Eliminar</button>
        </div>
      </div>
    </div>
  `).join('');

  document.getElementById('carritoSubtotal').textContent = fmtPrecio(data.subtotal);
  footer.style.display = 'block';

  // Actualizar badge
  actualizarBadge(data.total_items);
}

async function agregarAlCarrito(productoId, talla, cantidad = 1) {
  if (!VR.loggedIn) {
    window.location.href = `${VR.baseUrl}/tienda/login.php`;
    return;
  }
  if (!talla) { toast('Selecciona una talla', 'error'); return; }

  try {
    const data = await apiPost(`${VR.baseUrl}/api/tienda/carrito.php`, {
      action: 'agregar', producto_id: productoId, talla, cantidad
    });
    if (data.success) {
      toast('Producto agregado al carrito ✓');
      actualizarBadge(data.total_items);
      abrirCarrito();
    } else {
      toast(data.error || 'Error al agregar', 'error');
    }
  } catch {
    toast('Error de conexión', 'error');
  }
}

async function cambiarCantidad(itemId, nuevaCantidad) {
  const data = await apiPost(`${VR.baseUrl}/api/tienda/carrito.php`, {
    action: 'actualizar', item_id: itemId, cantidad: nuevaCantidad
  });
  if (data.success) {
    actualizarBadge(); cargarCarrito();
  } else {
    toast(data.error || 'Error', 'error');
  }
}

async function eliminarItem(itemId) {
  const data = await apiPost(`${VR.baseUrl}/api/tienda/carrito.php`, {
    action: 'eliminar', item_id: itemId
  });
  if (data.success) {
    document.getElementById('citem-' + itemId)?.remove();
    actualizarBadge(data.total_items);
    cargarCarrito();
  }
}

function actualizarBadge(n) {
  const el = document.getElementById('carritoCount');
  if (el) el.textContent = n ?? '';
}

/* ════════════════════════════════════════════
   SELECCIÓN DE TALLA (página producto)
════════════════════════════════════════════ */

let tallaSeleccionada = null;

function seleccionarTalla(talla, stock, btn) {
  if (stock === 0) return;
  tallaSeleccionada = talla;
  document.querySelectorAll('.talla-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');

  // Actualizar info de stock
  const stockEl = document.getElementById('infoStock');
  if (stockEl) {
    let cls, txt;
    if (stock > 10)     { cls = 'disponible'; txt = `${stock} disponibles`; }
    else if (stock > 0) { cls = 'bajo';       txt = `¡Quedan ${stock}!`; }
    else                { cls = 'agotado';    txt = 'Agotado'; }
    stockEl.className = `stock-chip ${cls}`;
    stockEl.textContent = txt;
  }
}

/* ════════════════════════════════════════════
   CHECKOUT — descuentos
════════════════════════════════════════════ */

async function calcularDescuento() {
  try {
    const data = await apiPost(`${VR.baseUrl}/api/tienda/descuentos.php`, {
      action: 'calcular'
    });
    if (data.mejor_descuento) {
      const d = data.mejor_descuento;
      const el = document.getElementById('descuentoInfo');
      if (el) {
        el.innerHTML = `
          <div class="alert alert-success d-flex align-items-center gap-2 py-2 mb-2">
            <i class="bi bi-tag-fill"></i>
            <div>
              <strong>${d.nombre}</strong><br>
              <small>${d.etiqueta} — Ahorra ${fmtPrecio(d.monto)}</small>
            </div>
          </div>`;
        // Guardar ID para enviar al crear pedido
        const inp = document.getElementById('descuentoId');
        if (inp) inp.value = d.id;
        // Actualizar total
        actualizarTotalCheckout(d.monto);
      }
    }
  } catch { /* no interrumpir checkout */ }
}

async function cargarConfiguracionPagos() {
  try {
    const data = await apiGet(`${VR.baseUrl}/api/tienda/pagos.php?action=get`);
    return data.config || {};
  } catch {
    return {};
  }
}

function renderInfoMetodoPago(config, metodo) {
  if (!config) return 'No se encontró la configuración de pagos.';

  if (metodo === 'nequi') {
    const nequi = config.nequi || {};
    return `
      <strong>Nequi</strong><br>
      ${nequi.numero ? `Número: <strong>${nequi.numero}</strong><br>` : 'Número de Nequi no disponible.<br>'}
      ${nequi.instrucciones ? `${nequi.instrucciones}<br>` : ''}
      ${nequi.qr_url ? `<img src="${nequi.qr_url}" alt="QR Nequi" style="max-width:180px;display:block;margin-top:10px;border-radius:12px;">` : ''}`;
  }

  if (metodo === 'transferencia') {
    const ban = config.transferencia || {};
    return `
      <strong>Transferencia bancaria</strong><br>
      ${ban.banco ? `Banco: <strong>${ban.banco}</strong><br>` : ''}
      ${ban.titular ? `Titular: <strong>${ban.titular}</strong><br>` : ''}
      ${ban.cuenta ? `Cuenta: <strong>${ban.cuenta}</strong><br>` : ''}
      ${ban.tipo_cuenta ? `Tipo de cuenta: <strong>${ban.tipo_cuenta}</strong><br>` : ''}
      ${ban.instrucciones ? `${ban.instrucciones}<br>` : ''}
      ${ban.qr_url ? `<img src="${ban.qr_url}" alt="QR Transferencia" style="max-width:180px;display:block;margin-top:10px;border-radius:12px;">` : ''}`;
  }

  if (metodo === 'contraentrega') {
    return `
      <strong>Contraentrega</strong><br>
      El pedido se genera automáticamente y podrás imprimir la factura para enviarla con el domiciliario.<br>
      Recibirás un resumen del pedido y el estado por WhatsApp.`;
  }

  return 'Selecciona un método de pago.';
}

function actualizarInfoPagoCheckout(metodo, config) {
  const contenedor = document.getElementById('checkoutMetodoPagoInfo');
  if (!contenedor) return;
  contenedor.innerHTML = renderInfoMetodoPago(config, metodo);
}

function actualizarTotalCheckout(descuento = 0) {
  const subtEl   = document.getElementById('checkoutSubtotal');
  const totalEl  = document.getElementById('checkoutTotal');
  const descEl   = document.getElementById('checkoutDescuento');
  if (!subtEl || !totalEl) return;

  const subtotal = parseFloat(subtEl.dataset.valor || 0);
  const total    = Math.max(0, subtotal - descuento);
  if (descEl) descEl.textContent = descuento > 0 ? '− ' + fmtPrecio(descuento) : '$0';
  totalEl.textContent = fmtPrecio(total);
}

/* ════════════════════════════════════════════
   PEDIDOS — panel admin / vendedor / bodeguero
════════════════════════════════════════════ */

async function cambiarEstadoPedido(pedidoId, estado) {
  const nota = prompt(`Nota para cambio a "${estado}" (opcional):`);
  if (nota === null) return; // Canceló

  const data = await apiPost(`${VR.baseUrl}/api/tienda/pedidos.php`, {
    action: 'cambiar_estado', pedido_id: pedidoId, estado, nota: nota || ''
  });

  if (data.success) {
    toast(`Pedido actualizado a: ${estado}`);
    setTimeout(() => location.reload(), 1000);
  } else {
    toast(data.error || 'Error', 'error');
  }
}

async function confirmarPago(pedidoId) {
  const ref = prompt('Referencia o número de pago:');
  if (!ref) return;

  const data = await apiPost(`${VR.baseUrl}/api/tienda/pedidos.php`, {
    action: 'confirmar_pago', pedido_id: pedidoId, referencia: ref
  });

  if (data.success) {
    toast('Pago confirmado. Venta #' + data.venta_id + ' creada.');
    setTimeout(() => location.reload(), 1200);
  } else {
    toast(data.error || 'Error', 'error');
  }
}

/* ════════════════════════════════════════════
   FILTROS CATÁLOGO
════════════════════════════════════════════ */

function aplicarFiltros() {
  const form   = document.getElementById('formFiltros');
  if (!form) return;
  const params = new URLSearchParams(new FormData(form));
  window.location.href = `${VR.baseUrl}/tienda/catalogo.php?${params.toString()}`;
}

/* ════════════════════════════════════════════
   WHATSAPP
════════════════════════════════════════════ */

function comprarWhatsapp(id, nombre, precio) {
  const msg = encodeURIComponent(
    `¡Hola Visión Real! 👋\nMe interesa el producto: *${nombre}*\nPrecio: $${Number(precio).toLocaleString('es-CO')}\nCódigo: #${id}\n\n¿Tienen disponible en mi talla?`
  );
  const target = (window.VR_STORE_WHATSAPP_NUMBER || window.STORE_WHATSAPP_NUMBER || '573125420576').toString().trim();
  window.open(`https://wa.me/${target}?text=${msg}`, '_blank');
}

/* ════════════════════════════════════════════
   PANEL CARRITO
════════════════════════════════════════════ */

function abrirCarrito() {
  document.getElementById('carritoOverlay')?.classList.add('activo');
  document.getElementById('carritoPanel')?.classList.add('open');
  cargarCarrito();
}

function cerrarCarrito() {
  document.getElementById('carritoOverlay')?.classList.remove('activo');
  document.getElementById('carritoPanel')?.classList.remove('open');
}

/* ════════════════════════════════════════════
   INIT
════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {
  // Cerrar carrito con ESC
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') cerrarCarrito?.();
  });
});
