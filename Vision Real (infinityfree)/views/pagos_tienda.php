<?php
/**
 * views/pagos_tienda.php
 * Configuración de métodos de pago y carga de QR para la tienda online.
 */
$pageTitle = 'Pagos Tienda';
require_once __DIR__ . '/../config/config.php';
requireLogin();
if (!isAdmin()) {
    header('Location: ' . BASE_URL . '/views/dashboard.php');
    exit();
}
require_once __DIR__ . '/../models/tienda/Pago.php';
$pm = new Pago();
$config = $pm->getConfig();
include __DIR__ . '/partials/head.php';
?>
<div class="layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" id="menu-toggle">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>
      <h1 class="page-title">Configuración de pagos</h1>
    </div>
  </header>

  <div class="content">
    <div class="card" style="margin-bottom:20px">
      <div class="card-header">
        <span class="card-title">Métodos de pago</span>
      </div>
      <div class="card-body" style="padding:24px">
        <div class="row g-4">
          <div class="col-lg-6">
            <div class="card p-4" style="border-radius:18px;">
              <h5 class="fw-bold mb-3">Nequi</h5>
              <div class="mb-3">
                <label class="form-label">Número de Nequi</label>
                <input id="nequiNumero" class="form-control" type="text" value="<?= htmlspecialchars($config['nequi']['numero']) ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Instrucciones</label>
                <textarea id="nequiInstrucciones" class="form-control" rows="3"><?= htmlspecialchars($config['nequi']['instrucciones']) ?></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label">QR de Nequi</label>
                <input id="nequiQr" type="file" accept="image/*" class="form-control" onchange="subirQr('nequi', this)">
              </div>
              <div id="nequiPreview" class="mb-3" style="min-height:120px">
                <?php if (!empty($config['nequi']['qr_url'])): ?>
                <img src="<?= htmlspecialchars($config['nequi']['qr_url']) ?>" alt="QR Nequi" style="max-width:240px;border-radius:12px;box-shadow:0 10px 20px rgba(0,0,0,.08)">
                <?php else: ?>
                <div class="text-muted" style="font-size:.9rem">No hay QR cargado.</div>
                <?php endif; ?>
              </div>
              <button class="btn btn-gold" onclick="guardarPago('nequi', 'numero', document.getElementById('nequiNumero').value)">Guardar número</button>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="card p-4" style="border-radius:18px;">
              <h5 class="fw-bold mb-3">Transferencia bancaria</h5>
              <div class="mb-3">
                <label class="form-label">Banco</label>
                <input id="transBanco" class="form-control" type="text" value="<?= htmlspecialchars($config['transferencia']['banco']) ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Titular</label>
                <input id="transTitular" class="form-control" type="text" value="<?= htmlspecialchars($config['transferencia']['titular']) ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Cuenta</label>
                <input id="transCuenta" class="form-control" type="text" value="<?= htmlspecialchars($config['transferencia']['cuenta']) ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Tipo de cuenta</label>
                <input id="transTipo" class="form-control" type="text" value="<?= htmlspecialchars($config['transferencia']['tipo_cuenta']) ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">QR de Transferencia</label>
                <input id="transQr" type="file" accept="image/*" class="form-control" onchange="subirQr('transferencia', this)">
              </div>
              <div id="transferenciaPreview" class="mb-3" style="min-height:120px">
                <?php if (!empty($config['transferencia']['qr_url'])): ?>
                <img src="<?= htmlspecialchars($config['transferencia']['qr_url']) ?>" alt="QR Transferencia" style="max-width:240px;border-radius:12px;box-shadow:0 10px 20px rgba(0,0,0,.08)">
                <?php else: ?>
                <div class="text-muted" style="font-size:.9rem">No hay QR cargado.</div>
                <?php endif; ?>
              </div>
              <button class="btn btn-gold" onclick="guardarPago('transferencia', 'banco', document.getElementById('transBanco').value)">Guardar datos</button>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
</div>

<script>
async function guardarPago(metodo, clave, valor) {
  const fd = new FormData();
  fd.append('action', 'guardar');
  fd.append('metodo', metodo);
  fd.append('clave', clave);
  fd.append('valor', valor);

  const res = await fetch(BASE_URL + '/api/tienda/pagos.php', { method: 'POST', body: fd, credentials: 'same-origin' });
  const data = await res.json();
  if (data.success) {
    toast('Configuración guardada.');
  } else {
    toast(data.error || 'Error al guardar', 'error');
  }
}

async function subirQr(metodo, input) {
  const file = input.files[0];
  if (!file) return;

  const fd = new FormData();
  fd.append('action', 'subir_qr');
  fd.append('metodo', metodo);
  fd.append('qr', file);

  const res = await fetch(BASE_URL + '/api/tienda/pagos.php', { method: 'POST', body: fd, credentials: 'same-origin' });
  const data = await res.json();
  if (data.success) {
    const preview = document.getElementById(metodo === 'nequi' ? 'nequiPreview' : 'transferenciaPreview');
    if (preview) {
      preview.innerHTML = `<img src="${data.url}" alt="QR ${metodo}" style="max-width:240px;border-radius:12px;box-shadow:0 10px 20px rgba(0,0,0,.08)">`;
    }
    toast('QR cargado correctamente.');
  } else {
    toast(data.error || 'Error al subir QR', 'error');
  }
}

</script>
</body>
</html>
