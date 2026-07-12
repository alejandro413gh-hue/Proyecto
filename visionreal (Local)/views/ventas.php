<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = 'Ventas';
require_once __DIR__ . '/../config/config.php';
requireLogin();
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../models/Descuento.php';
require_once __DIR__ . '/../models/Talla.php';
require_once __DIR__ . '/../models/Factura.php';

$pm = new Producto(); $cm = new Cliente(); $vm = new Venta(); $dm = new Descuento(); $tm = new Talla(); $fm = new Factura();
$msg = ''; $error = ''; $venta_generada = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'registrar_venta') {
        $cliente_id = intval($_POST['cliente_id'] ?? 0) ?: null;
        $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
        $cliente_documento = trim($_POST['cliente_documento'] ?? '');
        $items = json_decode(urldecode($_POST['items'] ?? '[]'), true);
        $notas = trim($_POST['notas'] ?? '');
        $descuento = floatval($_POST['descuento'] ?? 0);
        $descuento_id = intval($_POST['descuento_id'] ?? 0) ?: null;
        $descuento_aplicado = trim($_POST['descuento_aplicado'] ?? '');
        $cliente_sexo = trim($_POST['sexo'] ?? 'O');

        // Validación: debe haber cliente
        if (empty($cliente_nombre) || empty($cliente_documento)) {
            $error = '⚠ Debe ingresar nombre y documento del cliente';
        }
        // Validación: debe haber productos
        elseif (empty($items)) {
            $error = '⚠ Agregue al menos un producto';
        }
        else {
            // Si no hay cliente_id, buscar o crear
            if (!$cliente_id) {
                // Buscar por documento (NIT/CC)
                $clientes_existe = $cm->getAll();
                $cliente_existe = null;
                foreach ($clientes_existe as $c) {
                    if (trim($c['nit']) === trim($cliente_documento)) {
                        $cliente_existe = $c;
                        break;
                    }
                }

                if ($cliente_existe) {
                    // Cliente existe, usar su ID
                    $cliente_id = $cliente_existe['id'];
                } else {
                    // Cliente no existe, crear con sexo
                    if ($cm->create($cliente_nombre, $cliente_documento, '', '', '', $cliente_sexo)) {
                        // Obtener el cliente recién creado
                        $clientes_todos = $cm->getAll();
                        foreach ($clientes_todos as $c) {
                            if (trim($c['nit']) === trim($cliente_documento)) {
                                $cliente_id = $c['id'];
                                break;
                            }
                        }
                    } else {
                        $error = '⚠ Error al registrar el cliente';
                    }
                }
            }

            // Registrar venta
            if (!$error && $cliente_id) {
                $subtotal = array_sum(array_map(fn($i) => $i['precio'] * $i['cantidad'], $items));
                $genero = 'todos';
                if ($cliente_sexo === 'M') $genero = 'caballero';
                elseif ($cliente_sexo === 'F') $genero = 'dama';
                elseif ($cliente_sexo === 'O') $genero = 'todos';

                $descuento_calculado = $dm->calcularMejor($items, $cliente_id, $genero);
                $descuento = floatval($descuento_calculado['monto'] ?? 0);
                $descuento_id = intval($descuento_calculado['id'] ?? 0) ?: null;
                $descuento_aplicado = trim($descuento_calculado['etiqueta'] ?? $descuento_aplicado);
                $total = max(0, $subtotal - $descuento);

                error_log('Venta POST -> subtotal: ' . $subtotal . ' descuento: ' . $descuento . ' total: ' . $total);
                error_log('Venta POST -> cliente_id: ' . $cliente_id . ' sexo: ' . $cliente_sexo . ' descuento_id: ' . ($descuento_id ?? 'null'));

                $r = $vm->crear($cliente_id, $_SESSION['user_id'], $items, $notas, null, $descuento, $subtotal, $descuento_id, $descuento_aplicado);
                if (isset($r['success'])) {
                    $venta_id = $r['venta_id'];

                    // Obtener datos cliente para factura
                    $cliente_venta = $cm->getById($cliente_id);
                    $nombre_fact = $cliente_venta['nombre'] ?? $cliente_nombre;
                    $doc_fact = $cliente_venta['nit'] ?? $cliente_documento;

                    // Crear factura automáticamente
                    $fact_r = $fm->crear($venta_id, $nombre_fact, $doc_fact, $subtotal, $descuento, $total);

                    if (isset($fact_r['success'])) {
                        $venta_generada = [
                            'id' => $venta_id,
                            'numero_factura' => $fact_r['numero_factura'],
                            'cliente' => $nombre_fact,
                            'total' => $total
                        ];
                        $msg = '✓ Venta registrada — Factura: ' . $fact_r['numero_factura'];
                    }
                } else {
                    $error = $r['error'] ?? '⚠ Error al registrar la venta';
                }
            }
        }
    }
}

$productos = $pm->getAll();
include __DIR__ . '/partials/head.php';
?>
<div class="layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" id="menu-toggle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <h1 class="page-title">Ventas — Sistema POS</h1>
    </div>
    <div class="topbar-right">
      <a href="<?= BASE_URL ?>/views/ventas_export.php" class="btn btn-outline btn-sm">⬇️ Exportar Excel</a>
    </div>
  </header>

  <div class="content ventas-layout">
    <div class="panel-left">
      <div class="panel-scroll">
        <?php if($msg): ?><div class="alert alert-success">✓ <?=htmlspecialchars($msg)?> <?php if($venta_generada): ?><button onclick="abrirFactura(<?=$venta_generada['id']?>)" style="margin-left:10px;padding:6px 12px;background:var(--gold);color:var(--bg);border:none;border-radius:4px;cursor:pointer;font-weight:600;font-size:.85rem">📄 Ver Factura</button><?php endif; ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-error">⚠ <?=htmlspecialchars($error)?></div><?php endif; ?>

        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:14px;display:flex;gap:10px;flex-wrap:wrap;align-items:center">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--gold);flex-shrink:0;margin-top:2px"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input type="text" id="buscar-producto" placeholder="Código o nombre... (TAB selecciona)" style="flex:1;min-width:180px;background:transparent;border:none;color:var(--white);font-size:.9rem" autofocus>
        </div>

        <div id="productos-grid" style="flex:1;overflow-y:auto;display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;padding-right:10px;min-height:0;">
          <?php foreach($productos as $p):
            $tallas = $tm->getPorProducto($p['id']);
            $stock_total = max($p['stock'], array_sum(array_map(fn($t) => $t['stock'], $tallas)));
          ?>
          <div class="prod-btn" tabindex="0" data-id="<?=$p['id']?>" data-codigo="<?=$p['codigo']??''?>" data-nombre="<?=htmlspecialchars($p['nombre'])?>" data-precio="<?=(int)$p['precio']?>" data-categoria-id="<?=intval($p['categoria_id']??0)?>" style="padding:10px;background:var(--bg-panel);border:1px solid var(--border);border-radius:8px;cursor:pointer;transition:all .2s;text-align:center;min-height:110px;display:flex;flex-direction:column;justify-content:space-between;">
            <div>
              <div style="font-size:.7rem;color:var(--gold);font-weight:700;margin-bottom:4px"><?=htmlspecialchars($p['codigo'] ?? 'SIN-COD')?></div>
              <div style="font-size:.8rem;font-weight:600;color:var(--white);margin-bottom:6px;line-height:1.2" title="<?=htmlspecialchars($p['nombre'])?>"><?=htmlspecialchars(substr($p['nombre'],0,20))?></div>
            </div>
            <div>
              <div style="font-family:var(--font-display);font-size:.95rem;font-weight:700;color:var(--gold-light);margin-bottom:6px">$<?=number_format($p['precio'],0,',','.')?></div>
              <div style="font-size:.65rem;color:var(--success);font-weight:600">📦 <?=$stock_total?> disponibles</div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- PANEL DE CAJA -->
    <div class="panel-right">
      <div class="panel-scroll" style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:16px;min-height:0;">
      
      <!-- CLIENTE: DATOS + SEXO -->
      <div style="display:flex;flex-direction:column;gap:8px">
        <label style="font-size:.65rem;text-transform:uppercase;color:var(--white-muted);font-weight:700">Cliente</label>
        <div style="display:flex;gap:6px">
          <input type="text" id="cliente-nombre" placeholder="Nombre..." style="flex:1;background:var(--bg-panel);border:1px solid var(--border);color:var(--white);padding:8px;border-radius:6px;font-size:.85rem">
          <input type="text" id="cliente-documento" placeholder="NIT/CC..." style="flex:1;background:var(--bg-panel);border:1px solid var(--border);color:var(--white);padding:8px;border-radius:6px;font-size:.85rem">
        </div>

        <!-- Sexo del Cliente -->
        <div style="display:flex;gap:6px;align-items:center">
          <span style="font-size:.65rem;text-transform:uppercase;color:var(--white-muted);font-weight:700;min-width:40px">Sexo:</span>
          <div style="display:flex;gap:4px;flex:1">
            <button type="button" class="btn-sexo" data-sexo="M" style="flex:1;padding:6px;font-size:.75rem;background:var(--bg-panel);border:2px solid var(--border);color:var(--white);border-radius:6px;cursor:pointer;transition:all .15s;font-weight:600">👨 M</button>
            <button type="button" class="btn-sexo" data-sexo="F" style="flex:1;padding:6px;font-size:.75rem;background:var(--bg-panel);border:2px solid var(--border);color:var(--white);border-radius:6px;cursor:pointer;transition:all .15s;font-weight:600">👩 F</button>
            <button type="button" class="btn-sexo" data-sexo="O" style="flex:1;padding:6px;font-size:.75rem;background:var(--bg-panel);border:2px solid var(--border);color:var(--white);border-radius:6px;cursor:pointer;transition:all .15s;font-weight:600">⚬ Otro</button>
          </div>
        </div>
      </div>

      <!-- PRODUCTO Y TALLAS -->
      <div id="panel-producto" style="display:none;flex-direction:column;gap:10px;padding-bottom:12px;border-bottom:1px solid var(--border)">
        <div id="prod-info">
          <div style="font-size:.65rem;color:var(--white-muted);text-transform:uppercase;font-weight:700;margin-bottom:4px">Producto</div>
          <div id="prod-nombre" style="font-weight:700;color:var(--white);font-size:.9rem"></div>
          <div id="prod-precio" style="font-family:var(--font-display);font-size:1.1rem;color:var(--gold-light);font-weight:700;margin-top:4px"></div>
        </div>
        <div id="tallas-grid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:6px;margin-top:6px"></div>
        <div id="talla-error" style="display:none;background:var(--danger-dim);color:var(--danger);padding:8px;border-radius:6px;font-size:.7rem;text-align:center;font-weight:600"></div>
      </div>

      <!-- CARRITO -->
      <div style="flex:1;overflow-y:auto;border:1px solid var(--border);border-radius:8px;background:var(--bg-panel);padding:10px">
        <div style="font-size:.65rem;text-transform:uppercase;color:var(--white-muted);font-weight:700;margin-bottom:8px">Resumen</div>
        <div id="carrito-items" style="font-size:.8rem"><div style="text-align:center;color:var(--white-muted);padding:20px 10px">Sin productos</div></div>
      </div>

      <!-- TOTALES -->
      <div style="background:var(--gold-dim);border:1px solid rgba(201,168,76,.3);border-radius:8px;padding:12px;display:flex;flex-direction:column;gap:6px">
        <div style="display:flex;justify-content:space-between;font-size:.8rem"><span style="color:var(--white-muted)">Subtotal:</span><span id="subtotal" style="font-weight:700;color:var(--gold-light)">$0</span></div>
        <div id="descuento-row" style="display:none;flex-direction:column;gap:4px;padding-top:4px;color:var(--success)">
          <div style="display:flex;justify-content:space-between;font-size:.8rem"><span id="descuento-label">Descuento aplicado</span><span id="descuento-value" style="font-weight:700">-$0</span></div>
          <div id="descuento-detail" style="font-size:.7rem;color:var(--white-muted);display:none"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:1rem;font-weight:700;border-top:1px solid rgba(201,168,76,.3);padding-top:8px"><span style="color:var(--gold)">TOTAL:</span><span id="total" style="color:var(--gold-light)">$0</span></div>
      </div>

      <!-- NOTAS -->
      <textarea id="notas" placeholder="Notas..." style="background:var(--bg-panel);border:1px solid var(--border);color:var(--white);padding:8px;border-radius:6px;font-size:.75rem;height:40px;resize:none"></textarea>

      <!-- BOTONES -->
      <div style="display:flex;gap:8px">
        <button class="btn btn-outline" style="flex:1;font-size:.8rem;padding:10px" onclick="limpiarCarrito()">✕ CANCELAR</button>
        <button class="btn btn-primary" style="flex:1;font-size:.8rem;padding:10px;font-weight:700" onclick="registrarVenta()" id="btn-registrar">✓ REGISTRAR</button>
      </div>
    </div>
  </div>
</div>
</div>

<script>
const CTRL_TALLA = '<?=BASE_URL?>/controllers/TallaController.php';
const CTRL_FACTURA = '<?=BASE_URL?>/controllers/FacturaController.php';
const CTRL_DESCUENTO = '<?=BASE_URL?>/controllers/DescuentoController.php';

var prodActualId = null;
var prodCategoriaId = null;
var carrito = [];
var sexoActual = null;
var currentDescuento = null;

// Declaraciones de elementos del DOM
var prodBtns = Array.from(document.querySelectorAll('.prod-btn'));
var btnRegistrar = document.getElementById('btn-registrar');
var buscarProductoInput = document.getElementById('buscar-producto');

// Referencias del carrito y totales
var carritoContainer = document.getElementById('carrito-items');
var subtotalEl = document.getElementById('subtotal');
var totalEl = document.getElementById('total');


function formatCurrency(value) {
  return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(value);
}

function actualizarEstadoBotones() {
  if (btnRegistrar) btnRegistrar.disabled = carrito.length === 0;
}



prodBtns.forEach(function(btn, idx) {
  btn.addEventListener('click', function() { seleccionarProducto(this); });
  btn.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); seleccionarProducto(this); }
    if (e.key === 'ArrowDown' && idx < prodBtns.length - 1) prodBtns[idx + 1].focus();
    if (e.key === 'ArrowUp' && idx > 0) prodBtns[idx - 1].focus();
  });
});

if (buscarProductoInput) {
  buscarProductoInput.addEventListener('keydown', function(e) {
    if (e.key === 'Tab') {
      e.preventDefault();
      var q = this.value.toLowerCase();
      var visible = prodBtns.filter(function(b) {
        return b.getAttribute('data-nombre').toLowerCase().includes(q) ||
               b.getAttribute('data-codigo').toLowerCase().includes(q);
      });
      if (visible.length > 0) visible[0].focus();
    }
  });

  buscarProductoInput.addEventListener('input', function() {
    var q = this.value.toLowerCase();
    prodBtns.forEach(function(b) {
      var match = b.getAttribute('data-nombre').toLowerCase().includes(q) ||
                  b.getAttribute('data-codigo').toLowerCase().includes(q);
      b.style.display = match ? 'block' : 'none';
    });
  });
}

actualizarEstadoBotones();

// Botones de sexo del cliente
document.querySelectorAll('.btn-sexo').forEach(function(btn) {
  btn.addEventListener('click', function() {
    sexoActual = this.getAttribute('data-sexo');
    document.querySelectorAll('.btn-sexo').forEach(function(b) {
      b.style.borderColor = 'var(--border)';
      b.style.background = 'var(--bg-panel)';
      b.style.color = 'var(--white)';
    });
    this.style.borderColor = 'var(--gold)';
    this.style.background = 'var(--gold-dim)';
    this.style.color = 'var(--gold-light)';
    calcularDescuento();
  });
});

function seleccionarProducto(btn) {
  prodActualId = parseInt(btn.getAttribute('data-id'));
  prodCategoriaId = parseInt(btn.getAttribute('data-categoria-id') || 0);
  document.getElementById('prod-nombre').textContent = btn.getAttribute('data-nombre');
  document.getElementById('prod-precio').textContent = '$' + parseInt(btn.getAttribute('data-precio')).toLocaleString('es-CO');
  document.getElementById('panel-producto').style.display = 'flex';
  document.getElementById('talla-error').style.display = 'none';
  cargarTallas(prodActualId);
}

async function cargarTallas(prodId) {
  var r = await fetch(CTRL_TALLA + '?action=get&producto_id=' + prodId);
  var d = await r.json();
  var tallas = (d.tallas || []).filter(function(t) { return t.stock >= 0; });
  var div = document.getElementById('tallas-grid');
  div.innerHTML = '';

  if (tallas.length === 0) {
    div.innerHTML = '<div class="talla-empty">Sin tallas disponibles</div>';
    return;
  }

  tallas.forEach(function(t) {
    var row = document.createElement('div');
    row.className = 'talla-item';
    var existing = carrito.find(function(item) {
      return item.producto_id === prodId && item.talla === t.talla;
    });
    row.innerHTML = '<div class="talla-details">' +
      '<span class="talla-label">' + t.talla + '</span>' +
      '<span class="talla-stock">Stock: ' + t.stock + '</span>' +
      '</div>' +
      '<input type="number" min="0" max="' + t.stock + '" value="' + (existing ? existing.cantidad : 0) + '" class="talla-input" data-talla="' + t.talla + '" data-stock="' + t.stock + '" aria-label="Cantidad talla ' + t.talla + '">';
    var input = row.querySelector('input');
    input.addEventListener('input', function() {
      actualizarCantidadTalla(prodId, t.talla, t.stock, this);
    });
    div.appendChild(row);
  });
}

function actualizarCantidadTalla(prodId, talla, stock, input) {
  var qty = parseInt(input.value) || 0;
  if (qty < 0) qty = 0;
  if (qty > stock) {
    qty = stock;
    input.value = qty;
    mostrarErrorTalla('Stock máximo: ' + stock);
  }

  var itemIndex = carrito.findIndex(function(item) {
    return item.producto_id === prodId && item.talla === talla;
  });

  if (qty === 0) {
    if (itemIndex >= 0) carrito.splice(itemIndex, 1);
  } else {
    var nombre = document.getElementById('prod-nombre').textContent.trim();
    var precio = parseInt(document.getElementById('prod-precio').textContent.replace(/[$\.]/g, '')) || 0;
    if (itemIndex >= 0) {
      carrito[itemIndex].cantidad = qty;
      carrito[itemIndex].stock = stock;
    } else {
      carrito.push({
        producto_id: prodId,
        categoria_id: prodCategoriaId || null,
        nombre: nombre,
        precio: precio,
        talla: talla,
        cantidad: qty,
        stock: stock
      });
    }
  }
  // Actualizar vista del carrito después de modificar los items
  actualizarCarrito();
}

function mostrarErrorTalla(text) {
  var err = document.getElementById('talla-error');
  err.textContent = text;
  err.style.display = 'block';
  setTimeout(function() { err.style.display = 'none'; }, 2500);
}

function mostrarDescuento(desc) {
  var descuentoRow = document.getElementById('descuento-row');
  var descuentoLabel = document.getElementById('descuento-label');
  var descuentoValue = document.getElementById('descuento-value');
  var descuentoDetail = document.getElementById('descuento-detail');

  if (!desc) {
    if (descuentoRow) descuentoRow.style.display = 'none';
    currentDescuento = null;
    return;
  }

  if (descuentoLabel) descuentoLabel.textContent = desc.etiqueta || 'Descuento aplicado';
  if (descuentoValue) descuentoValue.textContent = '- ' + formatCurrency(desc.monto || 0);
  if (descuentoDetail) {
    descuentoDetail.style.display = 'block';
    descuentoDetail.textContent = desc.descripcion ? desc.descripcion : '';
  }
  if (descuentoRow) descuentoRow.style.display = 'flex';
  currentDescuento = desc;
}

async function calcularDescuento() {
  if (carrito.length === 0) {
    mostrarDescuento(null);
    return;
  }

  var genero = 'O';
  if (sexoActual === 'M') genero = 'M';
  else if (sexoActual === 'F') genero = 'F';
  else genero = 'O';

  var cliente_id = document.getElementById('cliente-id-hidden') ? parseInt(document.getElementById('cliente-id-hidden').value) || null : null;
  var data = new URLSearchParams();
  data.append('action', 'calcular');
  data.append('items', JSON.stringify(carrito));
  data.append('cliente_id', cliente_id || '');
  data.append('genero', genero);

  console.log('[Descuento] calculando descuento', {
    fecha_servidor: new Date().toISOString(),
    cliente_id: cliente_id,
    genero: genero,
    items: carrito
  });

  try {
    var response = await fetch(CTRL_DESCUENTO, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: data.toString()
    });
    var result = await response.json();
    console.log('[Descuento] resultado', result);

    if (result && result.descuento) {
      mostrarDescuento(result.descuento);
      var subtotal = carrito.reduce(function(sum, item) { return sum + item.precio * item.cantidad; }, 0);
      var total = Math.max(0, subtotal - (result.descuento.monto || 0));
      subtotalEl.textContent = formatCurrency(subtotal);
      totalEl.textContent = formatCurrency(total);
      actualizarEstadoBotones();
    } else {
      mostrarDescuento(null);
      var subtotal = carrito.reduce(function(sum, item) { return sum + item.precio * item.cantidad; }, 0);
      subtotalEl.textContent = formatCurrency(subtotal);
      totalEl.textContent = formatCurrency(subtotal);
    }

    if (result && result.server_date) console.log('[Descuento] server_date', result.server_date);
    if (result && result.mysql_now) console.log('[Descuento] mysql_now', result.mysql_now);
  } catch (err) {
    console.error('[Descuento] error al calcular', err);
  }
}

function actualizarCarrito() {
  if (!carritoContainer || !subtotalEl || !totalEl) return;
  if (carrito.length === 0) {
    carritoContainer.innerHTML = '<div style="text-align:center;color:var(--white-muted);padding:20px 10px;font-size:.8rem">Sin productos</div>';
    subtotalEl.textContent = '$0';
    totalEl.textContent = '$0';
    actualizarEstadoBotones();
    mostrarDescuento(null);
    return;
  }

  var total = 0;
  carritoContainer.innerHTML = carrito.map(function(item, idx) {
    var sub = item.precio * item.cantidad;
    total += sub;
    return '<div class="cart-item">' +
      '<div class="cart-item-name"><strong>' + item.nombre + '</strong><br><small style="color:var(--white-muted);">' + item.talla + '</small></div>' +
      '<input type="number" min="1" max="' + item.stock + '" value="' + item.cantidad + '" onchange="actualizarCantidadCarrito(' + idx + ', this.value)">' +
      '<div class="cart-item-price">' + formatCurrency(sub) + '</div>' +
      '<button type="button" class="btn btn-icon btn-danger" onclick="quitarItem(' + idx + ')">✕</button>' +
      '</div>';
  }).join('');

  subtotalEl.textContent = formatCurrency(total);
  totalEl.textContent = formatCurrency(total);
  actualizarEstadoBotones();
  calcularDescuento();
}

function actualizarCantidadCarrito(idx, value) {
  var qty = parseInt(value) || 1;
  if (!carrito[idx]) return;
  if (qty <= 0) { quitarItem(idx); return; }
  if (qty > carrito[idx].stock) {
    qty = carrito[idx].stock;
    mostrarErrorTalla('Stock insuficiente');
  }
  carrito[idx].cantidad = qty;
  actualizarCarrito();
}

function quitarItem(idx) {
  carrito.splice(idx, 1);
  actualizarCarrito();
}

function limpiarCarrito() {
  if (carrito.length > 0 && !confirm('¿Cancelar?')) return;
  carrito = [];
  prodActualId = null;
  document.getElementById('panel-producto').style.display = 'none';
  document.getElementById('cliente-nombre').value = '';
  document.getElementById('cliente-documento').value = '';
  document.getElementById('notas').value = '';
  actualizarCarrito();
  if (buscarProductoInput) buscarProductoInput.focus();
}

function registrarVenta() {
  var nombre = document.getElementById('cliente-nombre').value.trim();
  var documento = document.getElementById('cliente-documento').value.trim();
  if (!nombre || !documento) { alert('Ingrese nombre y documento del cliente'); return; }
  if (carrito.length === 0) { alert('Agregue productos'); return; }
  if (sexoActual === null) { alert('Seleccione el sexo del cliente'); return; }

  var f = document.createElement('form');
  f.method = 'POST';
  f.style.display = 'none';

  var sub = carrito.reduce(function(a, i) { return a + i.precio * i.cantidad; }, 0);
  var descuento = currentDescuento ? parseFloat(currentDescuento.monto || 0) : 0;
  var total = Math.max(0, sub - descuento);

  var fields = {
    action: 'registrar_venta',
    cliente_id: '',
    cliente_nombre: nombre,
    cliente_documento: documento,
    sexo: sexoActual || 'O',
    items: JSON.stringify(carrito),
    notas: document.getElementById('notas').value,
    subtotal: sub,
    descuento: descuento,
    descuento_id: currentDescuento ? currentDescuento.id : '',
    descuento_aplicado: currentDescuento ? currentDescuento.etiqueta : '',
    total: total
  };

  Object.entries(fields).forEach(function(entry) {
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = entry[0];
    input.value = entry[1];
    f.appendChild(input);
  });

  document.body.appendChild(f);
  f.submit();
}

function abrirFactura(ventaId) {
  var xhr = new XMLHttpRequest();
  xhr.open('GET', CTRL_FACTURA + '?action=generar_pdf&venta_id=' + ventaId, true);
  xhr.onload = function() {
    var d = JSON.parse(xhr.responseText);
    if (d.success) {
      var win = window.open();
      win.document.write(d.html);
      win.document.close();
    } else {
      alert(d.error);
    }
  };
  xhr.send();
}

if (buscarProductoInput) buscarProductoInput.focus();
</script>
</body>
</html>
