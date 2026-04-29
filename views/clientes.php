<?php
$pageTitle = 'Clientes';
require_once __DIR__ . '/../config/config.php';
requireLogin();
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/Venta.php';

$cm = new Cliente();
$vm = new Venta();
$msg = ''; $error = '';

// Solo editar, no crear desde aquí
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    
    if ($action === 'editar' && $id > 0) {
        $nombre = trim($_POST['nombre'] ?? '');
        $nit = trim($_POST['nit'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        
        if (empty($nombre)) { $error = 'El nombre es obligatorio'; }
        else {
            if ($cm->update($id, $nombre, $nit, $telefono, $email, $direccion)) {
                $msg = 'Cliente actualizado correctamente';
            } else {
                $error = 'Error al actualizar';
            }
        }
    }
}

$clientes = $cm->getAll();
include __DIR__ . '/partials/head.php';
?>
<div class="layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" id="menu-toggle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <h1 class="page-title">Clientes</h1>
    </div>
  </header>

  <div class="content">
    <?php if($msg): ?><div class="alert alert-success" style="margin-bottom:16px">✓ <?=htmlspecialchars($msg)?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error" style="margin-bottom:16px">⚠ <?=htmlspecialchars($error)?></div><?php endif; ?>

    <!-- INFORMACIÓN DE REGISTRO -->
    <div style="background:rgba(39,174,96,0.08);border:1px solid rgba(39,174,96,0.25);border-radius:10px;padding:14px;margin-bottom:20px;font-size:.82rem;color:var(--success);display:flex;gap:12px">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:2px"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
      <div>
        <strong style="color:var(--white);display:block;margin-bottom:4px">📌 Clientes automáticos</strong>
        <span style="color:var(--white-muted)">Los clientes se registran automáticamente cuando se realiza una venta. Este módulo es para consultar, editar información y ver historial de compras.</span>
      </div>
    </div>

    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
      <div class="stat-card">
        <div class="stat-label">Total Clientes</div>
        <div class="stat-value" style="font-size:1.6rem"><?=count($clientes)?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Con Compras</div>
        <div class="stat-value" style="font-size:1.6rem;color:var(--success)"><?=count(array_filter($clientes, fn($c) => $vm->countComprasPorCliente($c['id']) > 0))?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Sin Compras</div>
        <div class="stat-value" style="font-size:1.6rem;color:var(--warning)"><?=count(array_filter($clientes, fn($c) => $vm->countComprasPorCliente($c['id']) === 0))?></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <span class="card-title">Base de Clientes</span>
        <div class="search-input-wrap"><span class="search-icon">🔍</span><input type="text" id="buscador" placeholder="Buscar por nombre o email..." oninput="filtrar(this.value)"></div>
      </div>
      <div class="table-wrap">
        <table id="tbl">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>NIT / CC</th>
              <th>Teléfono</th>
              <th>Email</th>
              <th>Compras</th>
              <th>Última Compra</th>
              <th>Total Gastado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($clientes)): ?>
            <tr><td colspan="8" class="table-empty">No hay clientes registrados</td></tr>
            <?php else: foreach($clientes as $c):
              $compras = $vm->getComprasPorCliente($c['id']);
              $num_compras = count($compras);
              $total_gastado = array_sum(array_map(fn($v) => $v['total'], $compras));
              $ultima_compra = $num_compras > 0 ? $compras[0]['fecha'] : null;
            ?>
            <tr>
              <td><strong><?=htmlspecialchars($c['nombre'])?></strong></td>
              <td style="font-size:.85rem;font-weight:600;color:var(--gold-light)"><?=htmlspecialchars($c['nit']??'—')?></td>
              <td style="font-size:.85rem"><?=htmlspecialchars($c['telefono']??'—')?></td>
              <td style="font-size:.85rem"><?=htmlspecialchars($c['email']??'—')?></td>
              <td>
                <span style="font-weight:700;color:var(--gold-light)"><?=$num_compras?></span>
                <span style="color:var(--white-muted);font-size:.75rem"> compra<?=$num_compras===1?'':'s'?></span>
              </td>
              <td style="font-size:.82rem;color:var(--white-muted)">
                <?=$ultima_compra ? date('d/m/Y',strtotime($ultima_compra)) : '—'?>
              </td>
              <td style="font-weight:600;color:var(--gold-light)">$<?=number_format($total_gastado,0,',','.')?></td>
              <td>
                <div style="display:flex;gap:5px;flex-wrap:wrap">
                  <button class="btn btn-outline btn-sm" onclick="abrirEditar(this)">✏️ Editar</button>
                  <button class="btn btn-primary btn-sm" onclick="verHistorial(this)">📜 Historial</button>
                  <button class="btn btn-primary btn-sm" onclick="verFacturas(this)">📄 Facturas</button>
                </div>
                <!-- Datos ocultos -->
                <span style="display:none" class="d-id"><?=(int)$c['id']?></span>
                <span style="display:none" class="d-nombre"><?=htmlspecialchars($c['nombre'],ENT_QUOTES)?></span>
                <span style="display:none" class="d-nit"><?=htmlspecialchars($c['nit']??'',ENT_QUOTES)?></span>
                <span style="display:none" class="d-telefono"><?=htmlspecialchars($c['telefono']??'',ENT_QUOTES)?></span>
                <span style="display:none" class="d-email"><?=htmlspecialchars($c['email']??'',ENT_QUOTES)?></span>
                <span style="display:none" class="d-direccion"><?=htmlspecialchars($c['direccion']??'',ENT_QUOTES)?></span>
                <span style="display:none" class="d-compras"><?=implode('|',array_map(fn($v)=>'#'.$v['id'].' - $'.number_format($v['total'],0,',','.'),array_slice($compras,0,5)))?></span>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</div>

<!-- MODAL EDITAR -->
<div id="overlay-editar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:1000;align-items:center;justify-content:center;padding:20px" onclick="if(event.target===this)cerrar()">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:520px;box-shadow:0 20px 60px rgba(0,0,0,.6)">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border)">
      <span style="font-family:var(--font-display);font-size:1.1rem;color:var(--gold-light)">✏️ Editar Cliente</span>
      <button onclick="cerrar()" style="background:none;border:none;color:var(--white-muted);cursor:pointer;font-size:1.2rem">✕</button>
    </div>
    <form method="POST" style="padding:24px">
      <input type="hidden" name="action" value="editar">
      <input type="hidden" name="id" id="f-id" value="">
      <div class="form-grid">
        <div class="form-group span-2">
          <label>Nombre Completo *</label>
          <input type="text" name="nombre" id="f-nombre" required>
        </div>
        <div class="form-group">
          <label>NIT / CC</label>
          <input type="text" name="nit" id="f-nit">
        </div>
        <div class="form-group">
          <label>Teléfono</label>
          <input type="text" name="telefono" id="f-telefono">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" id="f-email">
        </div>
        <div class="form-group span-2">
          <label>Dirección</label>
          <textarea name="direccion" id="f-direccion" style="min-height:80px"></textarea>
        </div>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
        <button type="button" class="btn btn-outline" onclick="cerrar()">Cancelar</button>
        <button type="submit" class="btn btn-primary">✓ Guardar Cambios</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL HISTORIAL -->
<div id="overlay-historial" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:1000;align-items:center;justify-content:center;padding:20px;overflow-y:auto" onclick="if(event.target===this)cerrarHistorial()">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:600px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.6)">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg-card);z-index:1">
      <span style="font-family:var(--font-display);font-size:1.1rem;color:var(--gold-light)">📜 Historial de Compras</span>
      <button onclick="cerrarHistorial()" style="background:none;border:none;color:var(--white-muted);cursor:pointer;font-size:1.2rem">✕</button>
    </div>
    <div style="padding:20px">
      <div id="historial-contenido"></div>
    </div>
  </div>
</div>

<!-- MODAL FACTURAS -->
<div id="overlay-facturas" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:1000;align-items:center;justify-content:center;padding:20px;overflow-y:auto" onclick="if(event.target===this)cerrarFacturas()">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:700px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.6)">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg-card);z-index:1">
      <span style="font-family:var(--font-display);font-size:1.1rem;color:var(--gold-light)">📄 Facturas del Cliente</span>
      <button onclick="cerrarFacturas()" style="background:none;border:none;color:var(--white-muted);cursor:pointer;font-size:1.2rem">✕</button>
    </div>
    <div style="padding:20px">
      <div id="facturas-contenido"></div>
    </div>
  </div>
</div>

<script>
function filtrar(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#tbl tbody tr').forEach(function(r) {
    r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

function abrirEditar(btn) {
  var td = btn.closest('td');
  document.getElementById('f-id').value       = td.querySelector('.d-id').textContent.trim();
  document.getElementById('f-nombre').value   = td.querySelector('.d-nombre').textContent.trim();
  document.getElementById('f-nit').value      = td.querySelector('.d-nit').textContent.trim();
  document.getElementById('f-telefono').value = td.querySelector('.d-telefono').textContent.trim();
  document.getElementById('f-email').value    = td.querySelector('.d-email').textContent.trim();
  document.getElementById('f-direccion').value= td.querySelector('.d-direccion').textContent.trim();
  document.getElementById('overlay-editar').style.display = 'flex';
}

function verHistorial(btn) {
  var td = btn.closest('td');
  var compras = td.querySelector('.d-compras').textContent.trim();
  var contenido = document.getElementById('historial-contenido');
  if (!compras) {
    contenido.innerHTML = '<div style="text-align:center;color:var(--white-muted);padding:40px;font-size:.9rem">Sin compras registradas</div>';
  } else {
    contenido.innerHTML = '<div style="display:flex;flex-direction:column;gap:8px">' + 
      compras.split('|').map(function(c) {
        return '<div style="background:var(--bg-panel);padding:12px;border-radius:8px;border:1px solid var(--border);font-size:.85rem"><strong style="color:var(--gold-light)">' + c + '</strong></div>';
      }).join('') + 
      '</div>';
  }
  document.getElementById('overlay-historial').style.display = 'flex';
}

function verFacturas(btn) {
  var td = btn.closest('td');
  var clienteId = td.querySelector('.d-id').textContent.trim();
  var clienteNombre = td.querySelector('.d-nombre').textContent.trim();
  var contenido = document.getElementById('facturas-contenido');

  contenido.innerHTML = '<div style="text-align:center;padding:40px"><p>Cargando facturas...</p></div>';
  document.getElementById('overlay-facturas').style.display = 'flex';

  fetch(CTRL_FACTURA + '?action=listar_por_cliente&cliente_id=' + clienteId)
    .then(response => {
      console.log('Response status:', response.status);
      if (!response.ok) {
        throw new Error('HTTP ' + response.status + ': ' + response.statusText);
      }
      return response.json();
    })
    .then(data => {
      console.log('Data received:', data);
      if (data.success) {
        if (data.facturas.length > 0) {
          var html = '<div style="padding:20px"><h3 style="margin-bottom:20px;color:var(--gold-light)">Facturas de ' + clienteNombre + '</h3>';
          html += '<div style="max-height:400px;overflow-y:auto">';
          data.facturas.forEach(f => {
            var fecha = new Date(f.fecha).toLocaleDateString('es-ES');
            html += '<div style="border:1px solid var(--border);border-radius:8px;padding:15px;margin-bottom:10px;background:var(--card-bg)">';
            html += '<div style="display:flex;justify-content:space-between;margin-bottom:10px">';
            html += '<strong>' + f.numero_factura + '</strong>';
            html += '<span style="color:var(--gold-light);font-weight:bold">$' + f.total.toLocaleString('es-CO') + '</span>';
            html += '</div>';
            html += '<div style="color:var(--white-muted);font-size:.9rem;margin-bottom:10px">' + fecha + '</div>';
            html += '<button class="btn btn-sm btn-primary" onclick="abrirFactura(' + f.venta_id + ')">Ver Factura</button>';
            html += '</div>';
          });
          html += '</div></div>';
          contenido.innerHTML = html;
        } else {
          contenido.innerHTML = '<div style="text-align:center;color:var(--white-muted);padding:40px;font-size:.9rem">' +
            '<p style="margin-bottom:20px">No se encontraron facturas para: <strong style="color:var(--gold-light)">' + clienteNombre + '</strong></p>' +
            '<p style="font-size:.8rem;color:var(--white-muted)">Las facturas se generan automáticamente con cada venta.</p>' +
            '<button class="btn btn-primary" style="margin-top:20px;padding:10px 20px" onclick="cerrarFacturas()">Cerrar</button>' +
            '</div>';
        }
      } else {
        contenido.innerHTML = '<div style="text-align:center;color:red;padding:40px"><p>Error: ' + (data.error || 'No se pudieron cargar las facturas') + '</p></div>';
      }
    })
    .catch(error => {
      contenido.innerHTML = '<div style="text-align:center;color:red;padding:40px"><p>Error al cargar facturas: ' + error.message + '</p></div>';
      console.error('Error completo:', error);
    });
}

function cerrar() { document.getElementById('overlay-editar').style.display = 'none'; }
function cerrarHistorial() { document.getElementById('overlay-historial').style.display = 'none'; }
function cerrarFacturas() { document.getElementById('overlay-facturas').style.display = 'none'; }

const CTRL_FACTURA = '<?=BASE_URL?>/controllers/FacturaController.php';

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

var mt = document.getElementById('menu-toggle'), sb = document.getElementById('sidebar');
if (mt && sb) mt.addEventListener('click', function() { sb.classList.toggle('open'); });
</script>
</body>
</html>

