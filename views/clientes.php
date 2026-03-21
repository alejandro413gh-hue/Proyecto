<?php
$pageTitle='Clientes';
require_once __DIR__ . '/../config/config.php';
requireLogin();
require_once __DIR__ . '/../models/Cliente.php';
$m=new Cliente();$clientes=$m->getAll();
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
    <div class="topbar-right">
      <button class="btn btn-primary" onclick="abrirNuevo()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nuevo Cliente
      </button>
    </div>
  </header>

  <div class="content">
    <div id="msg-box" style="display:none;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:.85rem"></div>

    <div class="card">
      <div class="card-header">
        <span class="card-title">Directorio de Clientes <span style="color:var(--gold-light);font-size:.85rem">(<?=count($clientes)?>)</span></span>
        <div class="search-input-wrap">
          <span class="search-icon">🔍</span>
          <input type="text" id="buscador" placeholder="Buscar..." oninput="filtrar(this.value)">
        </div>
      </div>
      <div class="table-wrap">
        <table id="tbl">
          <thead>
            <tr><th>#</th><th>Nombre</th><th>Teléfono</th><th>Correo</th><th>Dirección</th><th>Registrado</th><th>Acciones</th></tr>
          </thead>
          <tbody>
            <?php if(empty($clientes)): ?>
            <tr><td colspan="7" class="table-empty">No hay clientes registrados</td></tr>
            <?php else: foreach($clientes as $c): ?>
            <tr>
              <td style="color:var(--white-muted);font-size:.8rem"><?=(int)$c['id']?></td>
              <td>
                <div style="display:flex;align-items:center;gap:10px">
                  <div style="width:32px;height:32px;border-radius:50%;background:var(--gold-dim);color:var(--gold-light);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;flex-shrink:0"><?=strtoupper(substr($c['nombre'],0,1))?></div>
                  <strong><?=htmlspecialchars($c['nombre'])?></strong>
                </div>
              </td>
              <td><?=htmlspecialchars($c['telefono']?:'—')?></td>
              <td><?php if($c['email']):?><a href="mailto:<?=htmlspecialchars($c['email'])?>" style="color:var(--gold-light);text-decoration:none"><?=htmlspecialchars($c['email'])?></a><?php else:?>—<?php endif;?></td>
              <td><?=htmlspecialchars($c['direccion']?:'—')?></td>
              <td style="font-size:.8rem;color:var(--white-muted)"><?=date('d/m/Y',strtotime($c['created_at']))?></td>
              <td>
                <div style="display:flex;gap:6px">
                  <button class="btn btn-outline btn-sm" onclick="abrirEditar(this)">✏️ Editar</button>
                  <button class="btn btn-danger btn-sm" onclick="borrar(this)">🗑</button>
                </div>
                <!-- Datos ocultos para JS -->
                <span style="display:none" class="d-id"><?=(int)$c['id']?></span>
                <span style="display:none" class="d-nombre"><?=htmlspecialchars($c['nombre'],ENT_QUOTES)?></span>
                <span style="display:none" class="d-tel"><?=htmlspecialchars($c['telefono']??'',ENT_QUOTES)?></span>
                <span style="display:none" class="d-email"><?=htmlspecialchars($c['email']??'',ENT_QUOTES)?></span>
                <span style="display:none" class="d-dir"><?=htmlspecialchars($c['direccion']??'',ENT_QUOTES)?></span>
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
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:90%;max-width:540px;box-shadow:0 20px 60px rgba(0,0,0,0.6)">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border)">
      <span id="modal-titulo" style="font-family:var(--font-display);font-size:1.1rem;color:var(--gold-light)">Nuevo Cliente</span>
      <button onclick="cerrar()" style="background:none;border:none;color:var(--white-muted);cursor:pointer;font-size:1.2rem">✕</button>
    </div>
    <div style="padding:24px">
      <input type="hidden" id="f-id">
      <div class="form-grid">
        <div class="form-group span-2"><label>Nombre Completo *</label><input type="text" id="f-nombre" placeholder="Ej: María López"></div>
        <div class="form-group"><label>Teléfono</label><input type="tel" id="f-tel" placeholder="300-123-4567"></div>
        <div class="form-group"><label>Correo</label><input type="email" id="f-email" placeholder="cliente@email.com"></div>
        <div class="form-group span-2"><label>Dirección</label><textarea id="f-dir" style="min-height:60px" placeholder="Opcional..."></textarea></div>
      </div>
      <div id="f-error" style="display:none;margin-top:12px;padding:10px 14px;background:rgba(192,57,43,0.15);color:#c0392b;border-radius:8px;font-size:.85rem;border:1px solid rgba(192,57,43,0.3)"></div>
    </div>
    <div style="display:flex;justify-content:flex-end;gap:10px;padding:16px 24px;border-top:1px solid var(--border)">
      <button class="btn btn-outline" onclick="cerrar()">Cancelar</button>
      <button class="btn btn-primary" onclick="guardar()">✓ Guardar</button>
    </div>
  </div>
</div>

<script>
const CTRL = '<?=BASE_URL?>/controllers/ClienteController.php';

function filtrar(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#tbl tbody tr').forEach(r => {
    r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

function abrirNuevo() {
  document.getElementById('modal-titulo').textContent = 'Nuevo Cliente';
  document.getElementById('f-id').value = '';
  document.getElementById('f-nombre').value = '';
  document.getElementById('f-tel').value = '';
  document.getElementById('f-email').value = '';
  document.getElementById('f-dir').value = '';
  document.getElementById('f-error').style.display = 'none';
  document.getElementById('overlay').style.display = 'flex';
}

function abrirEditar(btn) {
  const td = btn.closest('td');
  document.getElementById('modal-titulo').textContent = 'Editar Cliente';
  document.getElementById('f-id').value    = td.querySelector('.d-id').textContent;
  document.getElementById('f-nombre').value = td.querySelector('.d-nombre').textContent;
  document.getElementById('f-tel').value   = td.querySelector('.d-tel').textContent;
  document.getElementById('f-email').value  = td.querySelector('.d-email').textContent;
  document.getElementById('f-dir').value    = td.querySelector('.d-dir').textContent;
  document.getElementById('f-error').style.display = 'none';
  document.getElementById('overlay').style.display = 'flex';
}

function cerrar() {
  document.getElementById('overlay').style.display = 'none';
}

async function guardar() {
  const id     = document.getElementById('f-id').value.trim();
  const nombre = document.getElementById('f-nombre').value.trim();
  const tel    = document.getElementById('f-tel').value.trim();
  const email  = document.getElementById('f-email').value.trim();
  const dir    = document.getElementById('f-dir').value.trim();

  if (!nombre) { mostrarError('El nombre es obligatorio'); return; }

  const fd = new FormData();
  fd.append('action', id ? 'update' : 'create');
  fd.append('nombre', nombre);
  fd.append('telefono', tel);
  fd.append('email', email);
  fd.append('direccion', dir);
  if (id) fd.append('id', id);

  try {
    const r = await fetch(CTRL, { method:'POST', body:fd });
    const d = await r.json();
    if (d.success) { cerrar(); msg(d.success, 'ok'); setTimeout(()=>location.reload(), 900); }
    else mostrarError(d.error || 'Error al guardar');
  } catch(e) { mostrarError('Error: ' + e.message); }
}

async function borrar(btn) {
  const td = btn.closest('td');
  const id     = td.querySelector('.d-id').textContent;
  const nombre = td.querySelector('.d-nombre').textContent;
  if (!confirm('¿Eliminar al cliente "' + nombre + '"?')) return;

  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('id', id);

  try {
    const r = await fetch(CTRL, { method:'POST', body:fd });
    const d = await r.json();
    if (d.success) { msg(d.success, 'ok'); setTimeout(()=>location.reload(), 800); }
    else msg(d.error || 'Error al eliminar', 'err');
  } catch(e) { msg('Error de conexión', 'err'); }
}

function mostrarError(m) {
  const el = document.getElementById('f-error');
  el.textContent = '⚠ ' + m;
  el.style.display = 'block';
}

function msg(texto, tipo) {
  const el = document.getElementById('msg-box');
  el.textContent = (tipo==='ok'?'✓ ':'⚠ ') + texto;
  el.style.cssText = tipo==='ok'
    ? 'display:block;background:rgba(39,174,96,0.15);color:#27ae60;border:1px solid rgba(39,174,96,0.3);padding:12px 16px;border-radius:8px;margin-bottom:16px'
    : 'display:block;background:rgba(192,57,43,0.15);color:#c0392b;border:1px solid rgba(192,57,43,0.3);padding:12px 16px;border-radius:8px;margin-bottom:16px';
  setTimeout(()=>el.style.display='none', 3000);
}

// Mobile sidebar
const mt = document.getElementById('menu-toggle');
const sb = document.getElementById('sidebar');
if (mt && sb) mt.addEventListener('click', () => sb.classList.toggle('open'));
</script>
</body>
</html>
