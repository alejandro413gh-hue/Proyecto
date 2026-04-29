<?php
$pageTitle = 'Ventas';
require_once __DIR__ . '/../config/config.php';
requireLogin();
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../models/Talla.php';
require_once __DIR__ . '/../models/Factura.php';

$pm = new Producto(); $cm = new Cliente(); $vm = new Venta(); $tm = new Talla(); $fm = new Factura();
$msg = ''; $error = ''; $venta_generada = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'registrar_venta') {
        $cliente_id = intval($_POST['cliente_id'] ?? 0) ?: null;
        $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
        $cliente_documento = trim($_POST['cliente_documento'] ?? '');
        $items = json_decode($_POST['items'] ?? '[]', true);
        $notas = trim($_POST['notas'] ?? '');

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
                    // Cliente no existe, crear con NIT en posición correcta
                    if ($cm->create($cliente_nombre, $cliente_documento, '', '', '')) {
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
                $r = $vm->crear($cliente_id, $_SESSION['user_id'], $items, $notas);
                if (isset($r['success'])) {
                    $venta_id = $r['venta_id'];
                    
                    // Obtener datos cliente para factura
                    $cliente_venta = $cm->getById($cliente_id);
                    $nombre_fact = $cliente_venta['nombre'] ?? $cliente_nombre;
                    $doc_fact = $cliente_venta['nit'] ?? $cliente_documento;

                    // Crear factura automáticamente
                    $subtotal = array_sum(array_map(fn($i) => $i['precio'] * $i['cantidad'], $items));
                    $descuento = floatval($_POST['descuento'] ?? 0);
                    $total = floatval($_POST['total'] ?? 0);
                    
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
  </header>

  <div class="content" style="display:grid;grid-template-columns:1fr 380px;gap:20px;height:calc(100vh - 100px);overflow:hidden">
    <div style="display:flex;flex-direction:column;gap:16px;overflow:hidden">
      <?php if($msg): ?><div class="alert alert-success">✓ <?=htmlspecialchars($msg)?> <?php if($venta_generada): ?><button onclick="abrirFactura(<?=$venta_generada['id']?>)" style="margin-left:10px;padding:6px 12px;background:var(--gold);color:var(--bg);border:none;border-radius:4px;cursor:pointer;font-weight:600;font-size:.85rem">📄 Ver Factura</button><?php endif; ?></div><?php endif; ?>
      <?php if($error): ?><div class="alert alert-error">⚠ <?=htmlspecialchars($error)?></div><?php endif; ?>

      <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:14px;display:flex;gap:10px">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--gold);flex-shrink:0;margin-top:2px"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" id="buscar-producto" placeholder="Código o nombre... (TAB selecciona)" style="flex:1;background:transparent;border:none;color:var(--white);font-size:.9rem" autofocus>
      </div>

      <div id="productos-grid" style="flex:1;overflow-y:auto;display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;padding-right:10px">
        <?php foreach($productos as $p):
          $tallas = $tm->getPorProducto($p['id']);
          $stock_total = max($p['stock'], array_sum(array_map(fn($t) => $t['stock'], $tallas)));
        ?>
        <div class="prod-btn" tabindex="0" data-id="<?=$p['id']?>" data-codigo="<?=$p['codigo']??''?>" data-nombre="<?=htmlspecialchars($p['nombre'])?>" data-precio="<?=(int)$p['precio']?>" style="padding:10px;background:var(--bg-panel);border:1px solid var(--border);border-radius:8px;cursor:pointer;transition:all .2s;text-align:center">
          <div style="font-size:.7rem;color:var(--gold);font-weight:700;margin-bottom:4px"><?=$p['codigo']??'VR-'.str_pad($p['id'],4,'0',STR_PAD_LEFT)?></div>
          <div style="font-size:.8rem;font-weight:600;color:var(--white);margin-bottom:6px;line-height:1.2" title="<?=htmlspecialchars($p['nombre'])?>"><?=htmlspecialchars(substr($p['nombre'],0,20))?></div>
          <div style="font-family:var(--font-display);font-size:.95rem;font-weight:700;color:var(--gold-light);margin-bottom:6px">$<?=number_format($p['precio'],0,',','.')?></div>
          <div style="font-size:.65rem;color:var(--success);font-weight:600">📦 <?=$stock_total?> disponibles</div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- PANEL DE CAJA -->
    <div style="display:flex;flex-direction:column;gap:14px;background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:16px;height:100%;overflow:hidden">
      
      <!-- CLIENTE: DOS INPUTS (Como antes) -->
      <div style="display:flex;flex-direction:column;gap:6px">
        <label style="font-size:.65rem;text-transform:uppercase;color:var(--white-muted);font-weight:700">Cliente</label>
        <input type="text" id="cliente-nombre" placeholder="Nombre del cliente..." style="background:var(--bg-panel);border:1px solid var(--border);color:var(--white);padding:8px;border-radius:6px;font-size:.85rem">
        <input type="text" id="cliente-documento" placeholder="NIT/CC del cliente..." style="background:var(--bg-panel);border:1px solid var(--border);color:var(--white);padding:8px;border-radius:6px;font-size:.85rem">
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

var prodActualId = null, carrito = [];

// ===== PRODUCTOS =====
var prodBtns = document.querySelectorAll('.prod-btn');
prodBtns.forEach(function(btn, idx) {
  btn.onclick = function() { seleccionarProducto(this); };
  btn.onkeydown = function(e) {
    if (e.key === 'Enter') { e.preventDefault(); seleccionarProducto(this); }
    if (e.key === 'ArrowDown' && idx < prodBtns.length - 1) prodBtns[idx + 1].focus();
    if (e.key === 'ArrowUp' && idx > 0) prodBtns[idx - 1].focus();
  };
});

document.getElementById('buscar-producto').addEventListener('keydown', function(e) {
  if (e.key === 'Tab') {
    e.preventDefault();
    var q = this.value.toLowerCase();
    var visible = Array.from(prodBtns).filter(function(b) {
      return b.getAttribute('data-nombre').toLowerCase().includes(q) || 
             b.getAttribute('data-codigo').toLowerCase().includes(q);
    });
    if (visible.length > 0) visible[0].focus();
  }
});

document.getElementById('buscar-producto').addEventListener('input', function() {
  var q = this.value.toLowerCase();
  prodBtns.forEach(function(b) {
    var match = b.getAttribute('data-nombre').toLowerCase().includes(q) || 
                b.getAttribute('data-codigo').toLowerCase().includes(q);
    b.style.display = match ? 'block' : 'none';
  });
});

function seleccionarProducto(btn) {
  prodActualId = parseInt(btn.getAttribute('data-id'));
  document.getElementById('prod-nombre').textContent = btn.getAttribute('data-nombre');
  document.getElementById('prod-precio').textContent = '$' + parseInt(btn.getAttribute('data-precio')).toLocaleString('es-CO');
  document.getElementById('panel-producto').style.display = 'flex';
  document.getElementById('talla-error').style.display = 'none';
  cargarTallas(prodActualId);
}

async function cargarTallas(prodId) {
  var r = await fetch(CTRL_TALLA + '?action=get&producto_id=' + prodId);
  var d = await r.json();
  var tallas = (d.tallas || []).filter(function(t) { return t.stock > 0; });
  var div = document.getElementById('tallas-grid');
  div.innerHTML = '';
  
  if (tallas.length === 0) {
    div.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:var(--danger);padding:15px;font-size:.8rem">SIN STOCK</div>';
    return;
  }

  tallas.forEach(function(t, i) {
    var btn = document.createElement('button');
    btn.className = 'btn btn-outline';
    btn.style.cssText = 'padding:8px;font-size:.75rem;font-weight:600;border-color:var(--gold);color:var(--gold-light);cursor:pointer';
    btn.textContent = t.talla + ' (' + t.stock + ')';
    btn.onkeydown = function(e) {
      if (e.key === 'Enter') { e.preventDefault(); agregarAlCarrito(prodId, t.talla, t.stock, btn); }
      if (e.key === 'ArrowRight' && i < tallas.length - 1) btn.nextElementSibling?.focus();
      if (e.key === 'ArrowLeft' && i > 0) btn.previousElementSibling?.focus();
    };
    btn.onclick = function() { agregarAlCarrito(prodId, t.talla, t.stock, btn); };
    div.appendChild(btn);
  });
  
  div.querySelector('button')?.focus();
}

function agregarAlCarrito(prodId, talla, stock, btn) {
  var existe = carrito.find(function(i) { return i.producto_id === prodId && i.talla === talla; });
  
  if (existe) {
    if (existe.cantidad < stock) {
      existe.cantidad++;
    } else {
      var err = document.getElementById('talla-error');
      err.textContent = 'Stock agotado: ' + talla;
      err.style.display = 'block';
      setTimeout(function() { err.style.display = 'none'; }, 2000);
      return;
    }
  } else {
    carrito.push({
      producto_id: prodId,
      nombre: document.getElementById('prod-nombre').textContent,
      precio: parseInt(document.getElementById('prod-precio').textContent.replace(/[$\.]/g, '')),
      talla: talla,
      cantidad: 1
    });
  }

  actualizarCarrito();
  btn.style.borderColor = 'var(--success)';
  btn.style.color = 'var(--success)';
}

function actualizarCarrito() {
  var div = document.getElementById('carrito-items');
  if (carrito.length === 0) {
    div.innerHTML = '<div style="text-align:center;color:var(--white-muted);padding:20px 10px;font-size:.8rem">Sin productos</div>';
    document.getElementById('btn-registrar').disabled = true;
    return;
  }

  div.innerHTML = carrito.map(function(i, idx) {
    return '<div style="display:flex;justify-content:space-between;padding:8px;background:var(--bg-hover);border-radius:5px;margin-bottom:6px;font-size:.75rem">'+
      '<div><strong>' + i.nombre.substring(0,12) + '</strong><br>' + i.talla + '×' + i.cantidad + '</div>'+
      '<div style="text-align:right"><span style="color:var(--gold-light);font-weight:700">$' + (i.precio*i.cantidad).toLocaleString('es-CO') + '</span><br>'+
      '<button style="background:var(--danger-dim);border:none;color:var(--danger);padding:2px 5px;border-radius:3px;cursor:pointer;font-size:.65rem" onclick="carrito.splice(' + idx + ',1);actualizarCarrito()">✕</button></div>'+
      '</div>';
  }).join('');
  
  var sub = carrito.reduce(function(a,i) { return a + i.precio*i.cantidad; }, 0);
  document.getElementById('subtotal').textContent = '$' + sub.toLocaleString('es-CO');
  document.getElementById('total').textContent = '$' + sub.toLocaleString('es-CO');
  document.getElementById('btn-registrar').disabled = false;
}

function limpiarCarrito() {
  if (carrito.length > 0 && !confirm('¿Cancelar?')) return;
  carrito = []; prodActualId = null;
  document.getElementById('panel-producto').style.display = 'none';
  document.getElementById('cliente-nombre').value = '';
  document.getElementById('cliente-documento').value = '';
  document.getElementById('notas').value = '';
  actualizarCarrito();
  document.getElementById('buscar-producto').focus();
}

function registrarVenta() {
  var nombre = document.getElementById('cliente-nombre').value.trim();
  var documento = document.getElementById('cliente-documento').value.trim();
  
  if (!nombre || !documento) { alert('Ingrese nombre y documento del cliente'); return; }
  if (carrito.length === 0) { alert('Agregue productos'); return; }
  
  var f = document.createElement('form');
  f.method = 'POST';
  var sub = carrito.reduce(function(a,i) { return a + i.precio*i.cantidad; }, 0);
  f.innerHTML = '<input name="action" value="registrar_venta">'+
                '<input name="cliente_id" value="">'+
                '<input name="cliente_nombre" value="' + nombre.replace(/"/g, '&quot;') + '">'+
                '<input name="cliente_documento" value="' + documento.replace(/"/g, '&quot;') + '">'+
                '<input name="items" value=\'' + JSON.stringify(carrito) + '\'>'+
                '<input name="notas" value="' + document.getElementById('notas').value.replace(/"/g, '&quot;') + '">'+
                '<input name="subtotal" value="' + sub + '">'+
                '<input name="descuento" value="0">'+
                '<input name="total" value="' + sub + '">';
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

document.getElementById('buscar-producto').focus();
</script>
</body>
</html>
