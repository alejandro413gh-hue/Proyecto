<?php
$pageTitle = 'Promociones';
require_once __DIR__ . '/../config/config.php';
requireAdmin();
require_once __DIR__ . '/../models/Promocion.php';
$m = new Promocion();
$promociones = $m->getAll();
include __DIR__ . '/partials/head.php';
?>
<div class="layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" id="menu-toggle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <h1 class="page-title">Promociones</h1>
    </div>
    <div class="topbar-right">
      <button class="btn btn-primary" onclick="abrirNuevo()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nueva Promoción
      </button>
    </div>
  </header>

  <div class="content">
    <div id="msg-box" style="display:none;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:.85rem"></div>

    <!-- Info card -->
    <div style="background:var(--gold-dim);border:1px solid rgba(201,168,76,0.3);border-radius:10px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:flex-start;gap:14px">
      <span style="font-size:1.4rem">🎁</span>
      <div>
        <div style="font-weight:600;color:var(--gold-light);margin-bottom:4px">¿Cómo funcionan las promociones?</div>
        <div style="font-size:.83rem;color:var(--white-dim);line-height:1.6">
          Las promociones se aplican automáticamente al registrar una venta, según el <strong style="color:var(--white)">número de compras previas del cliente</strong>.
          Al seleccionar un cliente en ventas, el sistema mostrará qué descuentos tiene disponibles.
          El vendedor podrá elegir cuál aplicar y verá el ahorro reflejado en el total.
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px">
      <div class="stat-card">
        <div class="stat-label">Total Promociones</div>
        <div class="stat-value" style="font-size:1.6rem"><?=count($promociones)?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Activas</div>
        <div class="stat-value" style="font-size:1.6rem;color:var(--success)"><?=count(array_filter($promociones,fn($p)=>$p['activa']))?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Inactivas</div>
        <div class="stat-value" style="font-size:1.6rem;color:var(--white-muted)"><?=count(array_filter($promociones,fn($p)=>!$p['activa']))?></div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">Lista de Promociones</span>
      </div>
      <div class="table-wrap">
        <table id="tbl-promos">
          <thead>
            <tr>
              <th>#</th>
              <th>Nombre</th>
              <th>Descripción</th>
              <th>Descuento</th>
              <th>Compras Mín.</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($promociones)): ?>
            <tr><td colspan="7" class="table-empty">No hay promociones creadas</td></tr>
            <?php else: foreach($promociones as $p): ?>
            <tr>
              <td style="color:var(--white-muted);font-size:.8rem"><?=$p['id']?></td>
              <td>
                <div style="display:flex;align-items:center;gap:10px">
                  <span style="font-size:1.2rem"><?=$p['tipo']==='porcentaje'?'%':'$'?></span>
                  <strong><?=htmlspecialchars($p['nombre'])?></strong>
                </div>
              </td>
              <td style="color:var(--white-muted);font-size:.83rem"><?=htmlspecialchars($p['descripcion']?:'—')?></td>
              <td>
                <span style="font-family:var(--font-display);font-size:1.1rem;color:var(--gold-light);font-weight:600">
                  <?php if($p['tipo']==='porcentaje'): ?>
                    <?=$p['valor']?>%
                  <?php else: ?>
                    $<?=number_format($p['valor'],0,',','.')?>
                  <?php endif; ?>
                </span>
                <span style="font-size:.7rem;color:var(--white-muted);display:block">
                  <?=$p['tipo']==='porcentaje'?'Porcentaje':'Monto fijo COP'?>
                </span>
              </td>
              <td>
                <div style="display:flex;align-items:center;gap:8px">
                  <span style="width:28px;height:28px;border-radius:50%;background:var(--gold-dim);color:var(--gold-light);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700"><?=$p['compras_minimas']?></span>
                  <span style="font-size:.78rem;color:var(--white-muted)">compra<?=$p['compras_minimas']>1?'s':''?></span>
                </div>
              </td>
              <td>
                <button onclick="toggleEstado(<?=$p['id']?>)" class="badge <?=$p['activa']?'badge-success':'badge-danger'?>" style="cursor:pointer;border:none;background:inherit">
                  <?=$p['activa']?'✓ Activa':'✕ Inactiva'?>
                </button>
              </td>
              <td>
                <div style="display:flex;gap:6px">
                  <button class="btn btn-outline btn-sm" onclick="abrirEditar(this)">✏️ Editar</button>
                  <button class="btn btn-danger btn-sm" onclick="eliminar(this)">🗑</button>
                </div>
                <!-- Datos ocultos -->
                <span style="display:none" class="d-id"><?=$p['id']?></span>
                <span style="display:none" class="d-nombre"><?=htmlspecialchars($p['nombre'],ENT_QUOTES)?></span>
                <span style="display:none" class="d-desc"><?=htmlspecialchars($p['descripcion']??'',ENT_QUOTES)?></span>
                <span style="display:none" class="d-tipo"><?=$p['tipo']?></span>
                <span style="display:none" class="d-valor"><?=$p['valor']?></span>
                <span style="display:none" class="d-compras"><?=$p['compras_minimas']?></span>
                <span style="display:none" class="d-activa"><?=$p['activa']?></span>
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

<!-- MODAL -->
<div id="overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:1000;align-items:center;justify-content:center" onclick="if(event.target===this)cerrar()">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:90%;max-width:560px;box-shadow:0 20px 60px rgba(0,0,0,0.6)">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border)">
      <span id="modal-titulo" style="font-family:var(--font-display);font-size:1.1rem;color:var(--gold-light)">Nueva Promoción</span>
      <button onclick="cerrar()" style="background:none;border:none;color:var(--white-muted);cursor:pointer;font-size:1.2rem">✕</button>
    </div>
    <div style="padding:24px">
      <input type="hidden" id="f-id">
      <div class="form-grid">
        <div class="form-group span-2">
          <label>Nombre de la Promoción *</label>
          <input type="text" id="f-nombre" placeholder="Ej: Cliente Frecuente">
        </div>
        <div class="form-group">
          <label>Tipo de Descuento *</label>
          <select id="f-tipo" onchange="actualizarEtiqueta()">
            <option value="porcentaje">Porcentaje (%)</option>
            <option value="monto_fijo">Monto Fijo (COP)</option>
          </select>
        </div>
        <div class="form-group">
          <label id="lbl-valor">Valor del Descuento *</label>
          <input type="number" id="f-valor" placeholder="Ej: 15" min="0.01" step="0.01">
        </div>
        <div class="form-group">
          <label>Compras Mínimas del Cliente *</label>
          <input type="number" id="f-compras" placeholder="Ej: 3" min="1" step="1" value="1">
        </div>
        <div class="form-group" id="g-activa" style="display:none">
          <label>Estado</label>
          <select id="f-activa">
            <option value="1">Activa</option>
            <option value="0">Inactiva</option>
          </select>
        </div>
        <div class="form-group span-2">
          <label>Descripción</label>
          <textarea id="f-desc" placeholder="Descripción de la promoción..." style="min-height:70px"></textarea>
        </div>
      </div>
      <!-- Preview -->
      <div id="preview" style="margin-top:12px;padding:12px 16px;background:var(--gold-dim);border:1px solid rgba(201,168,76,0.25);border-radius:8px;font-size:.82rem;color:var(--gold-light)">
        💡 Esta promoción aplicará a clientes con <strong id="prev-compras">1</strong> o más compras, dando un descuento de <strong id="prev-desc">—</strong>
      </div>
      <div id="f-error" style="display:none;margin-top:10px;padding:10px 14px;background:rgba(192,57,43,0.15);color:#c0392b;border-radius:8px;font-size:.85rem;border:1px solid rgba(192,57,43,0.3)"></div>
    </div>
    <div style="display:flex;justify-content:flex-end;gap:10px;padding:16px 24px;border-top:1px solid var(--border)">
      <button class="btn btn-outline" onclick="cerrar()">Cancelar</button>
      <button class="btn btn-primary" onclick="guardar()">🎁 Guardar Promoción</button>
    </div>
  </div>
</div>

<script>
const CTRL = '<?=BASE_URL?>/controllers/PromocionController.php';

function actualizarEtiqueta() {
  const tipo = document.getElementById('f-tipo').value;
  document.getElementById('lbl-valor').textContent = tipo === 'porcentaje' ? 'Porcentaje (%) *' : 'Monto Fijo COP *';
  actualizarPreview();
}

function actualizarPreview() {
  const tipo    = document.getElementById('f-tipo').value;
  const valor   = parseFloat(document.getElementById('f-valor').value) || 0;
  const compras = document.getElementById('f-compras').value || '?';
  document.getElementById('prev-compras').textContent = compras;
  document.getElementById('prev-desc').textContent = tipo === 'porcentaje'
    ? (valor + '%')
    : ('$' + valor.toLocaleString('es-CO'));
}

document.getElementById('f-valor').addEventListener('input', actualizarPreview);
document.getElementById('f-compras').addEventListener('input', actualizarPreview);

function abrirNuevo() {
  document.getElementById('modal-titulo').textContent = 'Nueva Promoción';
  document.getElementById('f-id').value = '';
  document.getElementById('f-nombre').value = '';
  document.getElementById('f-desc').value = '';
  document.getElementById('f-tipo').value = 'porcentaje';
  document.getElementById('f-valor').value = '';
  document.getElementById('f-compras').value = '1';
  document.getElementById('f-activa').value = '1';
  document.getElementById('g-activa').style.display = 'none';
  document.getElementById('f-error').style.display = 'none';
  actualizarEtiqueta();
  document.getElementById('overlay').style.display = 'flex';
}

function abrirEditar(btn) {
  const td = btn.closest('td');
  document.getElementById('modal-titulo').textContent = 'Editar Promoción';
  document.getElementById('f-id').value      = td.querySelector('.d-id').textContent;
  document.getElementById('f-nombre').value  = td.querySelector('.d-nombre').textContent;
  document.getElementById('f-desc').value    = td.querySelector('.d-desc').textContent;
  document.getElementById('f-tipo').value    = td.querySelector('.d-tipo').textContent;
  document.getElementById('f-valor').value   = td.querySelector('.d-valor').textContent;
  document.getElementById('f-compras').value = td.querySelector('.d-compras').textContent;
  document.getElementById('f-activa').value  = td.querySelector('.d-activa').textContent;
  document.getElementById('g-activa').style.display = 'block';
  document.getElementById('f-error').style.display = 'none';
  actualizarEtiqueta();
  document.getElementById('overlay').style.display = 'flex';
}

function cerrar() { document.getElementById('overlay').style.display = 'none'; }

async function guardar() {
  const id      = document.getElementById('f-id').value.trim();
  const nombre  = document.getElementById('f-nombre').value.trim();
  const desc    = document.getElementById('f-desc').value.trim();
  const tipo    = document.getElementById('f-tipo').value;
  const valor   = document.getElementById('f-valor').value;
  const compras = document.getElementById('f-compras').value;
  const activa  = document.getElementById('f-activa').value;

  if (!nombre || !valor || !compras) { mostrarError('Completa todos los campos obligatorios'); return; }

  const fd = new FormData();
  fd.append('action', id ? 'update' : 'create');
  fd.append('nombre', nombre);
  fd.append('descripcion', desc);
  fd.append('tipo', tipo);
  fd.append('valor', valor);
  fd.append('compras_minimas', compras);
  fd.append('activa', activa);
  if (id) fd.append('id', id);

  try {
    const r = await fetch(CTRL, { method: 'POST', body: fd });
    const d = await r.json();
    if (d.success) { cerrar(); msg(d.success, 'ok'); setTimeout(() => location.reload(), 900); }
    else mostrarError(d.error || 'Error al guardar');
  } catch(e) { mostrarError('Error: ' + e.message); }
}

async function eliminar(btn) {
  const td = btn.closest('td');
  const id     = td.querySelector('.d-id').textContent;
  const nombre = td.querySelector('.d-nombre').textContent;
  if (!confirm('¿Eliminar la promoción "' + nombre + '"?')) return;
  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('id', id);
  try {
    const r = await fetch(CTRL, { method: 'POST', body: fd });
    const d = await r.json();
    if (d.success) { msg(d.success, 'ok'); setTimeout(() => location.reload(), 800); }
    else msg(d.error || 'Error', 'err');
  } catch(e) { msg('Error de conexión', 'err'); }
}

async function toggleEstado(id) {
  const fd = new FormData();
  fd.append('action', 'toggle');
  fd.append('id', id);
  try {
    const r = await fetch(CTRL, { method: 'POST', body: fd });
    const d = await r.json();
    if (d.success) location.reload();
  } catch(e) {}
}

function mostrarError(m) {
  const el = document.getElementById('f-error');
  el.textContent = '⚠ ' + m;
  el.style.display = 'block';
}

function msg(texto, tipo) {
  const el = document.getElementById('msg-box');
  el.textContent = (tipo === 'ok' ? '✓ ' : '⚠ ') + texto;
  el.style.cssText = tipo === 'ok'
    ? 'display:block;background:rgba(39,174,96,0.15);color:#27ae60;border:1px solid rgba(39,174,96,0.3);padding:12px 16px;border-radius:8px;margin-bottom:16px'
    : 'display:block;background:rgba(192,57,43,0.15);color:#c0392b;border:1px solid rgba(192,57,43,0.3);padding:12px 16px;border-radius:8px;margin-bottom:16px';
  setTimeout(() => el.style.display = 'none', 3000);
}

const mt = document.getElementById('menu-toggle');
const sb = document.getElementById('sidebar');
if (mt && sb) mt.addEventListener('click', () => sb.classList.toggle('open'));
</script>
</body>
</html>
