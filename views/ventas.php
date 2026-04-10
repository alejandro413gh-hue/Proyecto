<?php
$pageTitle = 'Ventas';
require_once __DIR__ . '/../config/config.php';
requireLogin();
require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/Promocion.php';

$vm = new Venta(); $pm = new Producto();
$cm = new Cliente(); $prm = new Promocion();

$ventas    = $vm->getAll(100);
$productos = $pm->getAll();
$clientes  = $cm->getAll();
$promoActivas = $prm->getActivas();

$ventaSel = null; $detalleVenta = null;
if (isset($_GET['ver']) && is_numeric($_GET['ver'])) {
    $ventaSel    = $vm->getById((int)$_GET['ver']);
    $detalleVenta = $vm->getDetalle((int)$_GET['ver']);
}
include __DIR__ . '/partials/head.php';
?>
<div class="layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" id="menu-toggle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <h1 class="page-title">Ventas</h1>
    </div>
    <div class="topbar-right">
      <button class="btn btn-primary" onclick="abrirModalVenta()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nueva Venta
      </button>
    </div>
  </header>

  <div class="content">
    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
      <div class="stat-card"><div class="stat-label">Ventas Hoy</div><div class="stat-value" style="font-size:1.5rem">$<?=number_format($vm->getTotalHoy()/1000,0)?>k</div><div class="stat-sub">COP</div></div>
      <div class="stat-card"><div class="stat-label">Ventas del Mes</div><div class="stat-value" style="font-size:1.5rem">$<?=number_format($vm->getTotalMes()/1000,0)?>k</div><div class="stat-sub"><?=date('F')?></div></div>
      <div class="stat-card"><div class="stat-label">Total Registros</div><div class="stat-value" style="font-size:1.5rem"><?=count($ventas)?></div></div>
    </div>

    <?php if($ventaSel && $detalleVenta): ?>
    <div class="card" style="margin-bottom:20px;border-color:rgba(201,168,76,0.3)">
      <div class="card-header">
        <span class="card-title">📋 Detalle Venta <span style="color:var(--gold)">#<?=$ventaSel['id']?></span></span>
        <a href="<?=BASE_URL?>/views/ventas.php" class="btn btn-outline btn-sm">✕ Cerrar</a>
      </div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px">
          <div><div style="font-size:.7rem;color:var(--white-muted);text-transform:uppercase;margin-bottom:4px">Cliente</div><div style="font-weight:500"><?=htmlspecialchars($ventaSel['cliente_nombre']??'Cliente General')?></div></div>
          <div><div style="font-size:.7rem;color:var(--white-muted);text-transform:uppercase;margin-bottom:4px">Vendedor</div><div style="font-weight:500"><?=htmlspecialchars($ventaSel['vendedor_nombre'])?></div></div>
          <div><div style="font-size:.7rem;color:var(--white-muted);text-transform:uppercase;margin-bottom:4px">Fecha</div><div style="font-weight:500"><?=date('d/m/Y H:i',strtotime($ventaSel['fecha']))?></div></div>
          <div><div style="font-size:.7rem;color:var(--white-muted);text-transform:uppercase;margin-bottom:4px">Estado</div><span class="badge badge-success"><?=$ventaSel['estado']?></span></div>
        </div>
        <table>
          <thead><tr><th>Producto</th><th>Precio Unit.</th><th>Cantidad</th><th>Subtotal</th></tr></thead>
          <tbody>
            <?php foreach($detalleVenta as $d): ?>
            <tr><td><strong><?=htmlspecialchars($d['producto_nombre'])?></strong></td><td>$<?=number_format($d['precio_unitario'],0,',','.')?></td><td style="text-align:center"><?=$d['cantidad']?></td><td style="color:var(--gold-light);font-weight:600">$<?=number_format($d['subtotal'],0,',','.')?></td></tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <?php if(($ventaSel['descuento']??0) > 0): ?>
            <tr>
              <td colspan="3" style="text-align:right;font-size:.82rem;color:var(--white-muted)">Subtotal:</td>
              <td style="color:var(--white-dim)">$<?=number_format($ventaSel['total_sin_descuento'],0,',','.')?></td>
            </tr>
            <tr>
              <td colspan="3" style="text-align:right;font-size:.82rem">
                🎁 Promoción: <span style="color:var(--gold-light)"><?=htmlspecialchars($ventaSel['promocion_nombre']??'')?></span>
              </td>
              <td style="color:var(--success);font-weight:600">-$<?=number_format($ventaSel['descuento'],0,',','.')?></td>
            </tr>
            <?php endif; ?>
            <tr style="border-top:2px solid var(--gold-dim)">
              <td colspan="3" style="text-align:right;font-weight:700;font-size:.85rem;text-transform:uppercase;letter-spacing:.08em;color:var(--white-muted)">TOTAL PAGADO:</td>
              <td style="color:var(--gold-light);font-weight:700;font-size:1.2rem">$<?=number_format($ventaSel['total'],0,',','.')?></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">
        <span class="card-title">Historial de Ventas</span>
        <div class="search-input-wrap"><span class="search-icon">🔍</span><input type="text" id="buscador" placeholder="Buscar..." oninput="filtrar(this.value)"></div>
      </div>
      <div class="table-wrap">
        <table id="tbl-ventas">
          <thead><tr><th>#</th><th>Cliente</th><th>Vendedor</th><th>Subtotal</th><th>Descuento</th><th>Total</th><th>Fecha</th><th>Estado</th><th></th></tr></thead>
          <tbody>
            <?php if(empty($ventas)): ?>
            <tr><td colspan="9" class="table-empty">No hay ventas registradas</td></tr>
            <?php else: foreach($ventas as $v): ?>
            <tr>
              <td><span style="color:var(--gold-light);font-weight:600">#<?=$v['id']?></span></td>
              <td><strong><?=htmlspecialchars($v['cliente_nombre']??'Cliente General')?></strong></td>
              <td style="color:var(--white-muted)"><?=htmlspecialchars($v['vendedor_nombre'])?></td>
              <td style="color:var(--white-muted);font-size:.82rem"><?=($v['descuento']>0)?'$'.number_format($v['total_sin_descuento'],0,',','.'):'—'?></td>
              <td>
                <?php if($v['descuento']>0): ?>
                <span style="color:var(--success);font-weight:500">-$<?=number_format($v['descuento'],0,',','.')?></span>
                <div style="font-size:.68rem;color:var(--white-muted)"><?=htmlspecialchars($v['promocion_nombre']??'')?></div>
                <?php else: ?>—<?php endif; ?>
              </td>
              <td style="color:var(--gold-light);font-weight:700">$<?=number_format($v['total'],0,',','.')?></td>
              <td style="font-size:.82rem"><?=date('d/m/Y H:i',strtotime($v['fecha']))?></td>
              <td><span class="badge badge-<?=$v['estado']==='completada'?'success':($v['estado']==='cancelada'?'danger':'warning')?>"><?=$v['estado']?></span></td>
              <td><a href="<?=BASE_URL?>/views/ventas.php?ver=<?=$v['id']?>" class="btn btn-outline btn-sm">👁 Ver</a></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</div>

<!-- MODAL Nueva Venta -->
<div id="overlay-venta" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:1000;align-items:center;justify-content:center;padding:20px" onclick="if(event.target===this)cerrarVenta()">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:900px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.6)">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg-card);z-index:1">
      <span style="font-family:var(--font-display);font-size:1.15rem;color:var(--gold-light)">🧾 Nueva Venta</span>
      <button onclick="cerrarVenta()" style="background:none;border:none;color:var(--white-muted);cursor:pointer;font-size:1.2rem">✕</button>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0">
      <!-- Columna izquierda -->
      <div style="padding:24px;border-right:1px solid var(--border)">
        <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.12em;color:var(--white-muted);margin-bottom:14px;font-weight:600">① Cliente y Productos</div>

        <div class="form-group" style="margin-bottom:16px">
          <label>Cliente</label>
          <select id="v-cliente" onchange="cargarPromociones()">
            <option value="">— Cliente General (sin descuentos) —</option>
            <?php foreach($clientes as $c): ?>
            <option value="<?=$c['id']?>"><?=htmlspecialchars($c['nombre'])?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Sección Promociones (se llena dinámicamente) -->
        <div id="promo-section" style="display:none;margin-bottom:16px">
          <div style="background:var(--gold-dim);border:1px solid rgba(201,168,76,0.25);border-radius:8px;padding:14px">
            <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.12em;color:var(--gold-light);margin-bottom:10px;font-weight:600">🎁 Promociones Disponibles</div>
            <div id="promo-info" style="font-size:.8rem;color:var(--white-muted);margin-bottom:10px"></div>
            <div id="promo-lista"></div>
            <div id="promo-ninguna" style="display:none">
              <select id="v-promo" onchange="aplicarPromocion()">
                <option value="">— Sin descuento —</option>
              </select>
            </div>
          </div>
        </div>

        <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.12em;color:var(--white-muted);margin-bottom:10px;font-weight:600">Agregar Producto</div>

        <div class="form-group" style="margin-bottom:10px">
          <label>Producto</label>
          <select id="v-producto">
            <option value="">— Selecciona un producto —</option>
            <?php foreach($productos as $i=>$p): if($p['stock']>0): ?>
            <option value="<?=$i?>"><?=htmlspecialchars($p['nombre'])?> — $<?=number_format($p['precio'],0,',','.')?> (<?=$p['stock']?> disp.)</option>
            <?php endif; endforeach; ?>
          </select>
        </div>

        <div style="display:flex;gap:10px;align-items:flex-end;margin-bottom:16px">
          <div class="form-group" style="flex:1;margin:0"><label>Cantidad</label><input type="number" id="v-qty" value="1" min="1" style="text-align:center"></div>
          <button class="btn btn-primary" onclick="agregarProducto()">+ Agregar</button>
        </div>

        <div class="form-group">
          <label>Notas</label>
          <textarea id="v-notas" style="min-height:55px" placeholder="Observaciones..."></textarea>
        </div>
      </div>

      <!-- Columna derecha: carrito -->
      <div style="padding:24px;display:flex;flex-direction:column">
        <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.12em;color:var(--white-muted);margin-bottom:14px;font-weight:600">② Resumen de Compra</div>

        <div id="carrito" style="flex:1;min-height:120px"></div>

        <!-- Totales -->
        <div style="margin-top:auto;padding-top:16px;border-top:1px solid var(--border)">
          <div style="display:flex;justify-content:space-between;font-size:.82rem;color:var(--white-muted);margin-bottom:6px">
            <span>Subtotal</span>
            <span id="txt-subtotal">$0</span>
          </div>
          <div id="fila-descuento" style="display:none;padding:8px 10px;background:rgba(39,174,96,0.1);border:1px solid rgba(39,174,96,0.25);border-radius:6px;margin-bottom:8px">
            <div style="display:flex;justify-content:space-between;align-items:center">
              <div>
                <div style="font-size:.78rem;color:var(--success);font-weight:600">🎁 <span id="txt-promo-nombre">Promoción</span></div>
                <div style="font-size:.7rem;color:var(--white-muted)" id="txt-promo-detalle"></div>
              </div>
              <span style="color:var(--success);font-weight:700;font-size:1rem" id="txt-descuento">-$0</span>
            </div>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:.75rem;text-transform:uppercase;letter-spacing:.1em;color:var(--white-muted)">Total a Pagar</span>
            <span id="txt-total" style="font-family:var(--font-display);font-size:2rem;font-weight:600;color:var(--gold-light)">$0</span>
          </div>
          <div id="txt-ahorro" style="display:none;text-align:right;font-size:.75rem;color:var(--success);margin-top:2px"></div>
        </div>
      </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px;padding:16px 24px;border-top:1px solid var(--border);background:var(--bg-panel)">
      <button class="btn btn-outline" onclick="cerrarVenta()">Cancelar</button>
      <button class="btn btn-success" onclick="registrarVenta()" style="padding:10px 28px;font-size:.95rem">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Registrar Venta
      </button>
    </div>
  </div>
</div>

<script>
// ===== DATOS =====
const PRODUCTOS  = <?=json_encode(array_values($productos))?>;
const BASE_URL   = '<?=BASE_URL?>';
let carrito      = [];
let promoSel     = null; // {id, nombre, tipo, valor}

// ===== FILTRAR TABLA =====
function filtrar(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#tbl-ventas tbody tr').forEach(r => {
    r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

// ===== MODAL =====
function abrirModalVenta() {
  carrito = []; promoSel = null;
  document.getElementById('v-cliente').value = '';
  document.getElementById('v-producto').value = '';
  document.getElementById('v-qty').value = 1;
  document.getElementById('v-notas').value = '';
  document.getElementById('promo-section').style.display = 'none';
  document.getElementById('fila-descuento').style.display = 'none';
  renderCarrito();
  document.getElementById('overlay-venta').style.display = 'flex';
}

function cerrarVenta() { document.getElementById('overlay-venta').style.display = 'none'; }

// ===== CARGAR PROMOCIONES AL SELECCIONAR CLIENTE =====
async function cargarPromociones() {
  promoSel = null;
  document.getElementById('fila-descuento').style.display = 'none';
  document.getElementById('promo-section').style.display = 'none';

  const clienteId = document.getElementById('v-cliente').value;
  if (!clienteId) { recalcular(); return; }

  try {
    const r = await fetch(BASE_URL + '/controllers/PromocionController.php?action=para_cliente&cliente_id=' + clienteId);
    const d = await r.json();

    const sec = document.getElementById('promo-section');
    const info = document.getElementById('promo-info');
    const lista = document.getElementById('promo-lista');

    info.innerHTML = `Este cliente tiene <strong style="color:var(--white)">${d.compras}</strong> compra(s) registrada(s).`;

    if (!d.promociones || d.promociones.length === 0) {
      lista.innerHTML = '<div style="font-size:.8rem;color:var(--white-muted);padding:4px 0">Este cliente aún no tiene promociones disponibles.</div>';
      sec.style.display = 'block';
      recalcular();
      return;
    }

    // Mostrar promociones como botones seleccionables
    lista.innerHTML = d.promociones.map(p => {
      const etiqueta = p.tipo === 'porcentaje' ? p.valor + '% OFF' : '-$' + parseInt(p.valor).toLocaleString('es-CO');
      return `<div class="promo-btn" data-id="${p.id}" data-nombre="${p.nombre}" data-tipo="${p.tipo}" data-valor="${p.valor}"
        onclick="seleccionarPromo(this)"
        style="display:flex;justify-content:space-between;align-items:center;padding:10px 12px;border-radius:8px;border:1px solid var(--border);cursor:pointer;margin-bottom:8px;transition:.2s;background:var(--bg-hover)">
        <div>
          <div style="font-size:.85rem;font-weight:600;color:var(--white)">${p.nombre}</div>
          <div style="font-size:.72rem;color:var(--white-muted)">${p.descripcion || ('Requiere ' + p.compras_minimas + ' compra(s)')}</div>
        </div>
        <div style="text-align:right">
          <div style="font-family:var(--font-display);font-size:1.2rem;font-weight:700;color:var(--gold-light)">${etiqueta}</div>
          <div style="font-size:.65rem;color:var(--white-muted)">${p.tipo === 'porcentaje' ? 'porcentaje' : 'monto fijo'}</div>
        </div>
      </div>`;
    }).join('');

    // Agregar opción "sin descuento"
    lista.innerHTML += `<div class="promo-btn" data-id="" onclick="seleccionarPromo(this)"
      style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;border-radius:8px;border:1px solid var(--border);cursor:pointer;background:transparent;opacity:.7">
      <span style="font-size:.8rem;color:var(--white-muted)">Sin descuento</span>
    </div>`;

    sec.style.display = 'block';
  } catch(e) {
    console.error(e);
  }
  recalcular();
}

function seleccionarPromo(el) {
  // Deseleccionar todos
  document.querySelectorAll('.promo-btn').forEach(b => {
    b.style.borderColor = 'var(--border)';
    b.style.background  = 'var(--bg-hover)';
  });

  const id = el.dataset.id;
  if (!id) {
    promoSel = null;
    el.style.borderColor = 'var(--gold)';
    el.style.background  = 'var(--gold-dim)';
  } else {
    promoSel = {
      id:     parseInt(id),
      nombre: el.dataset.nombre,
      tipo:   el.dataset.tipo,
      valor:  parseFloat(el.dataset.valor)
    };
    el.style.borderColor = 'var(--success)';
    el.style.background  = 'rgba(39,174,96,0.1)';
  }
  recalcular();
}

// ===== CARRITO =====
function agregarProducto() {
  const sel = document.getElementById('v-producto');
  const qty = parseInt(document.getElementById('v-qty').value) || 1;
  const idx = parseInt(sel.value);
  if (isNaN(idx) || sel.value === '') { mostrarToast('Selecciona un producto', 'err'); return; }
  const prod = PRODUCTOS[idx];
  if (qty <= 0) { mostrarToast('Cantidad inválida', 'err'); return; }
  if (qty > prod.stock) { mostrarToast('Stock insuficiente. Disponible: ' + prod.stock, 'err'); return; }
  const ex = carrito.find(i => i.producto_id == prod.id);
  if (ex) {
    const nq = ex.cantidad + qty;
    if (nq > prod.stock) { mostrarToast('Stock insuficiente', 'err'); return; }
    ex.cantidad = nq;
  } else {
    carrito.push({ producto_id: prod.id, nombre: prod.nombre, precio: parseFloat(prod.precio), cantidad: qty, stock: prod.stock });
  }
  sel.value = ''; document.getElementById('v-qty').value = 1;
  renderCarrito();
}

function quitarItem(idx) { carrito.splice(idx, 1); renderCarrito(); }

function cambiarQty(idx, val) {
  const q = parseInt(val);
  if (q <= 0) { quitarItem(idx); return; }
  if (q > carrito[idx].stock) { mostrarToast('Stock insuficiente', 'err'); return; }
  carrito[idx].cantidad = q;
  renderCarrito();
}

function renderCarrito() {
  const c = document.getElementById('carrito');
  if (carrito.length === 0) {
    c.innerHTML = '<div style="color:var(--white-muted);text-align:center;padding:30px 0;font-size:.85rem;border:1px dashed var(--border);border-radius:8px">Sin productos agregados</div>';
  } else {
    c.innerHTML = carrito.map((item, i) => `
      <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border)">
        <div style="flex:1">
          <div style="font-size:.85rem;font-weight:500">${item.nombre}</div>
          <div style="font-size:.75rem;color:var(--white-muted)">$${item.precio.toLocaleString('es-CO')} c/u</div>
        </div>
        <input type="number" min="1" max="${item.stock}" value="${item.cantidad}" onchange="cambiarQty(${i},this.value)"
          style="width:55px;text-align:center;padding:5px;background:var(--bg-panel);border:1px solid var(--border);border-radius:6px;color:var(--white)">
        <div style="width:90px;text-align:right;color:var(--gold-light);font-weight:600">$${(item.precio*item.cantidad).toLocaleString('es-CO')}</div>
        <button onclick="quitarItem(${i})" style="background:var(--danger-dim);border:1px solid rgba(192,57,43,.3);color:var(--danger);border-radius:6px;width:28px;height:28px;cursor:pointer;font-size:.9rem">✕</button>
      </div>`).join('');
  }
  recalcular();
}

function recalcular() {
  const subtotal  = carrito.reduce((a, i) => a + i.precio * i.cantidad, 0);
  let descuento   = 0;

  if (promoSel) {
    descuento = promoSel.tipo === 'porcentaje'
      ? Math.round(subtotal * promoSel.valor / 100)
      : Math.min(promoSel.valor, subtotal);
  }

  const total = Math.max(0, subtotal - descuento);
  const fmt = v => '$' + Math.round(v).toLocaleString('es-CO');

  document.getElementById('txt-subtotal').textContent = fmt(subtotal);
  document.getElementById('txt-total').textContent    = fmt(total);

  if (descuento > 0 && promoSel) {
    const detalle = promoSel.tipo === 'porcentaje'
      ? promoSel.valor + '% de descuento aplicado'
      : 'Monto fijo descontado';
    document.getElementById('txt-promo-nombre').textContent   = promoSel.nombre;
    document.getElementById('txt-promo-detalle').textContent  = detalle;
    document.getElementById('txt-descuento').textContent      = '-' + fmt(descuento);
    document.getElementById('fila-descuento').style.display   = 'block';
    document.getElementById('txt-ahorro').style.display       = 'block';
    document.getElementById('txt-ahorro').textContent         = '¡Ahorro de ' + fmt(descuento) + '!';
  } else {
    document.getElementById('fila-descuento').style.display = 'none';
    document.getElementById('txt-ahorro').style.display     = 'none';
  }
}

// ===== REGISTRAR VENTA =====
async function registrarVenta() {
  if (carrito.length === 0) { mostrarToast('Agrega al menos un producto', 'err'); return; }

  const subtotal  = carrito.reduce((a, i) => a + i.precio * i.cantidad, 0);
  let descuento   = 0;
  if (promoSel) {
    descuento = promoSel.tipo === 'porcentaje'
      ? Math.round(subtotal * promoSel.valor / 100)
      : Math.min(promoSel.valor, subtotal);
  }

  const fd = new FormData();
  fd.append('action',       'create');
  fd.append('cliente_id',   document.getElementById('v-cliente').value || 0);
  fd.append('notas',        document.getElementById('v-notas').value);
  fd.append('promocion_id', promoSel ? promoSel.id : 0);
  fd.append('descuento',    descuento);
  fd.append('productos',    JSON.stringify(carrito.map(i => ({
    producto_id: i.producto_id, cantidad: i.cantidad, precio: i.precio
  }))));

  try {
    const r = await fetch(BASE_URL + '/controllers/VentaController.php', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.success) {
      cerrarVenta();
      mostrarToast('✓ Venta registrada exitosamente', 'ok');
      setTimeout(() => location.reload(), 1200);
    } else {
      mostrarToast(d.error || 'Error al registrar', 'err');
    }
  } catch(e) {
    mostrarToast('Error de conexión', 'err');
  }
}

// ===== TOAST =====
function mostrarToast(msg, tipo) {
  let c = document.getElementById('toast-container');
  if (!c) { c = document.createElement('div'); c.id='toast-container'; c.style.cssText='position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px'; document.body.appendChild(c); }
  const t = document.createElement('div');
  t.style.cssText = `padding:12px 18px;border-radius:8px;font-size:.85rem;font-weight:500;min-width:260px;box-shadow:0 4px 16px rgba(0,0,0,.4);animation:slideIn .3s ease;border-left:3px solid;display:flex;gap:10px;align-items:center;${tipo==='ok'?'background:#0d1f15;color:#27ae60;border-color:#27ae60':'background:#1f0d0d;color:#c0392b;border-color:#c0392b'}`;
  t.textContent = msg;
  c.appendChild(t);
  setTimeout(() => { t.style.opacity='0'; t.style.transform='translateX(60px)'; t.style.transition='.3s'; setTimeout(()=>t.remove(),300); }, 3000);
}

// Mobile sidebar
const mt = document.getElementById('menu-toggle');
const sb = document.getElementById('sidebar');
if (mt && sb) mt.addEventListener('click', () => sb.classList.toggle('open'));
</script>
<style>
@keyframes slideIn { from{opacity:0;transform:translateX(60px)} to{opacity:1;transform:translateX(0)} }
</style>
</body>
</html>
