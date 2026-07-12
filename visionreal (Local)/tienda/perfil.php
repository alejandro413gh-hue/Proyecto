<?php
/**
 * tienda/perfil.php
 * Perfil del cliente online — editar datos y cambiar contraseña.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/includes/session_tienda.php';
requireTiendaLogin();

require_once __DIR__ . '/../models/tienda/ClienteOnline.php';
$cm      = new ClienteOnline();
$cliente = getTiendaCliente();
$perfil  = $cm->getById($cliente['id']);
$carritoN = getCarritoCount();
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Mi Perfil — Visión Real</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="<?= BASE_URL ?>/tienda/assets/css/tienda.css" rel="stylesheet">
</head>
<body>

<nav class="vr-navbar navbar sticky-top">
  <div class="container">
    <a class="navbar-brand vr-brand" href="<?= BASE_URL ?>/tienda/">
      <span class="brand-name">Visión Real</span><span class="brand-sub">Mi Perfil</span>
    </a>
    <div class="navbar-nav flex-row gap-2 align-items-center">
      <div class="dropdown">
        <button class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($cliente['nombre']) ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="<?= BASE_URL ?>/tienda/mis-pedidos.php"><i class="bi bi-box-seam me-2"></i>Mis pedidos</a></li>
          <li><a class="dropdown-item active" href="<?= BASE_URL ?>/tienda/perfil.php"><i class="bi bi-person me-2"></i>Mi perfil</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/api/tienda/auth.php?action=logout">Cerrar sesión</a></li>
        </ul>
      </div>
      <button class="btn btn-carrito position-relative" onclick="abrirCarrito()">
        <i class="bi bi-bag"></i><span class="carrito-badge" id="carritoCount"><?= $carritoN ?></span>
      </button>
    </div>
  </div>
</nav>

<div class="container py-5">
  <div class="row justify-content-center g-4">
    <div class="col-lg-8">

      <h2 class="fw-bold mb-4">Mi perfil</h2>

      <!-- Mensajes -->
      <div id="msgExito" class="alert alert-success" style="display:none"></div>
      <div id="msgError" class="alert alert-danger"  style="display:none"></div>

      <!-- ── Datos personales ── -->
      <div class="checkout-card mb-4">
        <h5 class="fw-bold mb-4"><i class="bi bi-person me-2"></i>Datos personales</h5>
        <div class="row g-3 form-tienda">
          <div class="col-md-6">
            <label>Nombre completo</label>
            <input type="text" class="form-control" id="pNombre" value="<?= htmlspecialchars($perfil['nombre']) ?>">
          </div>
          <div class="col-md-6">
            <label>Correo electrónico</label>
            <input type="email" class="form-control" value="<?= htmlspecialchars($perfil['email']) ?>" disabled
                   style="opacity:.6" title="El correo no se puede cambiar">
          </div>
          <div class="col-md-6">
            <label>Teléfono</label>
            <input type="text" class="form-control" id="pTelefono" value="<?= htmlspecialchars($perfil['telefono'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label>Soy</label>
            <select class="form-select" id="pSexo">
              <option value="O" <?= $perfil['sexo']==='O'?'selected':'' ?>>Prefiero no indicar</option>
              <option value="F" <?= $perfil['sexo']==='F'?'selected':'' ?>>Dama</option>
              <option value="M" <?= $perfil['sexo']==='M'?'selected':'' ?>>Caballero</option>
            </select>
          </div>
          <div class="col-12">
            <label>Dirección</label>
            <input type="text" class="form-control" id="pDireccion" value="<?= htmlspecialchars($perfil['direccion'] ?? '') ?>" placeholder="Tu dirección habitual de envío">
          </div>
          <div class="col-md-6">
            <label>Ciudad</label>
            <input type="text" class="form-control" id="pCiudad" value="<?= htmlspecialchars($perfil['ciudad'] ?? '') ?>">
          </div>
          <div class="col-12 mt-2">
            <button class="btn btn-gold px-4" onclick="guardarPerfil()">
              <i class="bi bi-check-circle me-1"></i>Guardar cambios
            </button>
          </div>
        </div>
      </div>

      <!-- ── Cambiar contraseña ── -->
      <div class="checkout-card mb-4">
        <h5 class="fw-bold mb-4"><i class="bi bi-lock me-2"></i>Cambiar contraseña</h5>
        <div class="row g-3 form-tienda">
          <div class="col-md-4">
            <label>Contraseña actual</label>
            <input type="password" class="form-control" id="passActual" placeholder="••••••••">
          </div>
          <div class="col-md-4">
            <label>Nueva contraseña</label>
            <input type="password" class="form-control" id="passNueva" placeholder="Mín. 6 caracteres">
          </div>
          <div class="col-md-4">
            <label>Confirmar nueva</label>
            <input type="password" class="form-control" id="passConfirm" placeholder="Repetir">
          </div>
          <div class="col-12 mt-2">
            <button class="btn btn-outline-dark px-4" onclick="cambiarPassword()">
              <i class="bi bi-key me-1"></i>Cambiar contraseña
            </button>
          </div>
        </div>
      </div>

      <!-- ── Accesos rápidos ── -->
      <div class="row g-3">
        <div class="col-6">
          <a href="<?= BASE_URL ?>/tienda/mis-pedidos.php" class="btn btn-outline-dark w-100 py-3">
            <i class="bi bi-box-seam d-block fs-4 mb-1"></i>Mis pedidos
          </a>
        </div>
        <div class="col-6">
          <a href="<?= BASE_URL ?>/tienda/catalogo.php" class="btn btn-gold w-100 py-3">
            <i class="bi bi-bag d-block fs-4 mb-1"></i>Ir al catálogo
          </a>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Panel carrito -->
<div class="vr-carrito-overlay" id="carritoOverlay" onclick="cerrarCarrito()"></div>
<aside class="vr-carrito-panel" id="carritoPanel">
  <div class="carrito-header">
    <h5><i class="bi bi-bag me-2"></i>Mi Carrito</h5>
    <button class="btn-close-panel" onclick="cerrarCarrito()">✕</button>
  </div>
  <div class="carrito-body" id="carritoBody"></div>
  <div class="carrito-footer" id="carritoFooter" style="display:none">
    <div class="d-flex justify-content-between mb-2"><strong>Subtotal:</strong><strong id="carritoSubtotal">$0</strong></div>
    <a href="<?= BASE_URL ?>/tienda/checkout.php" class="btn btn-gold w-100 mb-2">Ir al checkout</a>
  </div>
</aside>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="<?= BASE_URL ?>/tienda/assets/js/tienda.js" defer></script>
<script>
const VR = { baseUrl: '<?= BASE_URL ?>', loggedIn: true };

function mostrarMsg(msg, tipo = 'exito') {
  const ok  = document.getElementById('msgExito');
  const err = document.getElementById('msgError');
  ok.style.display  = 'none';
  err.style.display = 'none';
  if (tipo === 'exito') { ok.textContent  = msg; ok.style.display  = 'block'; }
  else                  { err.textContent = msg; err.style.display = 'block'; }
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function guardarPerfil() {
  const fd = new FormData();
  fd.append('action',    'actualizar_perfil');
  fd.append('nombre',    document.getElementById('pNombre').value.trim());
  fd.append('telefono',  document.getElementById('pTelefono').value.trim());
  fd.append('sexo',      document.getElementById('pSexo').value);
  fd.append('direccion', document.getElementById('pDireccion').value.trim());
  fd.append('ciudad',    document.getElementById('pCiudad').value.trim());

  const r    = await fetch(VR.baseUrl + '/api/tienda/auth.php', { method: 'POST', body: fd });
  const data = await r.json();
  data.success ? mostrarMsg('Perfil actualizado correctamente ✓') : mostrarMsg(data.error || 'Error al guardar', 'error');
}

async function cambiarPassword() {
  const actual   = document.getElementById('passActual').value;
  const nueva    = document.getElementById('passNueva').value;
  const confirma = document.getElementById('passConfirm').value;

  if (!actual || !nueva) { mostrarMsg('Completa todos los campos de contraseña', 'error'); return; }
  if (nueva !== confirma) { mostrarMsg('Las contraseñas nuevas no coinciden', 'error'); return; }
  if (nueva.length < 6)  { mostrarMsg('La nueva contraseña debe tener al menos 6 caracteres', 'error'); return; }

  const fd = new FormData();
  fd.append('action',           'cambiar_password');
  fd.append('password_actual',  actual);
  fd.append('password_nueva',   nueva);

  const r    = await fetch(VR.baseUrl + '/api/tienda/auth.php', { method: 'POST', body: fd });
  const data = await r.json();

  if (data.success) {
    mostrarMsg('Contraseña cambiada correctamente ✓');
    document.getElementById('passActual').value  = '';
    document.getElementById('passNueva').value   = '';
    document.getElementById('passConfirm').value = '';
  } else {
    mostrarMsg(data.error || 'Error al cambiar contraseña', 'error');
  }
}
</script>
</body>
</html>
