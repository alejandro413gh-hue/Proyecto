<?php
/**
 * tienda/login.php
 * Login y registro unificado para clientes de la tienda.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/includes/session_tienda.php';

// Ya está logueado → redirigir
if (tiendaLoggedIn()) {
    $redirect = $_GET['redirect'] ?? (BASE_URL . '/tienda/');
    header('Location: ' . $redirect);
    exit();
}

$modo = $_GET['modo'] ?? 'login'; // login | registro
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= $modo === 'registro' ? 'Crear cuenta' : 'Iniciar sesión' ?> — Visión Real</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="<?= BASE_URL ?>/tienda/assets/css/tienda.css" rel="stylesheet">
<style>
.auth-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #0D0D0D 0%, #2C2415 60%, #1A1A1A 100%);
  display: flex; align-items: center; justify-content: center;
  padding: 24px;
}
.auth-card {
  background: #fff;
  border-radius: 20px;
  padding: 40px;
  width: 100%;
  max-width: 440px;
  box-shadow: 0 24px 64px rgba(0,0,0,.4);
}
.auth-brand { color: #C9A84C; font-weight: 800; font-size: 1.5rem; }
.auth-brand-sub { color: #999; font-size: .75rem; letter-spacing: .1em; text-transform: uppercase; }
.tab-btn {
  flex: 1; padding: 10px; border: none; background: #f5f5f5;
  font-weight: 600; font-size: .88rem; cursor: pointer; transition: .2s;
}
.tab-btn:first-child { border-radius: 8px 0 0 8px; }
.tab-btn:last-child  { border-radius: 0 8px 8px 0; }
.tab-btn.active { background: #0D0D0D; color: #fff; }
</style>
</head>
<body>
<div class="auth-page">
  <div class="auth-card">

    <div class="text-center mb-4">
      <div class="auth-brand">Visión Real</div>
      <div class="auth-brand-sub">Tienda Online</div>
    </div>

    <!-- Tabs login / registro -->
    <div class="d-flex mb-4 overflow-hidden" style="border-radius:10px;border:1px solid #eee">
      <button class="tab-btn <?= $modo !== 'registro' ? 'active' : '' ?>" onclick="mostrarTab('login')">
        <i class="bi bi-box-arrow-in-right me-1"></i>Iniciar sesión
      </button>
      <button class="tab-btn <?= $modo === 'registro' ? 'active' : '' ?>" onclick="mostrarTab('registro')">
        <i class="bi bi-person-plus me-1"></i>Crear cuenta
      </button>
    </div>

    <!-- Mensajes -->
    <div id="msgError" class="alert alert-danger py-2" style="display:none;font-size:.85rem"></div>
    <div id="msgExito" class="alert alert-success py-2" style="display:none;font-size:.85rem"></div>

    <!-- ── Formulario Login ── -->
    <div id="tabLogin" style="display:<?= $modo !== 'registro' ? 'block' : 'none' ?>">
      <div class="form-tienda">
        <div class="mb-3">
          <label>Correo electrónico</label>
          <input type="email" class="form-control" id="loginEmail" placeholder="correo@ejemplo.com" autocomplete="email">
        </div>
        <div class="mb-4">
          <label>Contraseña</label>
          <input type="password" class="form-control" id="loginPass" placeholder="••••••••" autocomplete="current-password">
        </div>
        <button class="btn btn-gold w-100 btn-lg" onclick="doLogin()">
          <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar sesión
        </button>
      </div>
    </div>

    <!-- ── Formulario Registro ── -->
    <div id="tabRegistro" style="display:<?= $modo === 'registro' ? 'block' : 'none' ?>">
      <div class="form-tienda row g-3">
        <div class="col-12">
          <label>Nombre completo *</label>
          <input type="text" class="form-control" id="regNombre" placeholder="Tu nombre" autocomplete="name">
        </div>
        <div class="col-12">
          <label>Correo electrónico *</label>
          <input type="email" class="form-control" id="regEmail" placeholder="correo@ejemplo.com" autocomplete="email">
        </div>
        <div class="col-md-6">
          <label>Contraseña *</label>
          <input type="password" class="form-control" id="regPass" placeholder="Mín. 6 caracteres" autocomplete="new-password">
        </div>
        <div class="col-md-6">
          <label>Confirmar *</label>
          <input type="password" class="form-control" id="regPass2" placeholder="Repetir contraseña">
        </div>
        <div class="col-md-6">
          <label>Teléfono</label>
          <input type="tel" class="form-control" id="regTelefono" placeholder="Opcional">
        </div>
        <div class="col-md-6">
          <label>Soy</label>
          <select class="form-select" id="regSexo">
            <option value="O">Prefiero no indicar</option>
            <option value="F">Dama</option>
            <option value="M">Caballero</option>
          </select>
        </div>
        <div class="col-12">
          <button class="btn btn-gold w-100 btn-lg" onclick="doRegistro()">
            <i class="bi bi-person-check me-2"></i>Crear cuenta gratis
          </button>
        </div>
      </div>
    </div>

    <div class="text-center mt-4">
      <a href="<?= BASE_URL ?>/tienda/" class="text-muted" style="font-size:.82rem">
        <i class="bi bi-arrow-left me-1"></i>Volver a la tienda
      </a>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE_URL = '<?= BASE_URL ?>';
const REDIRECT = '<?= addslashes($_GET['redirect'] ?? BASE_URL . '/tienda/') ?>';

function mostrarTab(tab) {
  document.getElementById('tabLogin').style.display    = tab === 'login'    ? 'block' : 'none';
  document.getElementById('tabRegistro').style.display = tab === 'registro' ? 'block' : 'none';
  document.querySelectorAll('.tab-btn').forEach((b,i) => {
    b.classList.toggle('active', (i === 0) === (tab === 'login'));
  });
  ocultarMsg();
}

function mostrarError(msg)  { const e = document.getElementById('msgError'); e.textContent = msg; e.style.display = 'block'; document.getElementById('msgExito').style.display = 'none'; }
function mostrarExito(msg)  { const e = document.getElementById('msgExito'); e.textContent = msg; e.style.display = 'block'; document.getElementById('msgError').style.display  = 'none'; }
function ocultarMsg()       { document.getElementById('msgError').style.display = 'none'; document.getElementById('msgExito').style.display = 'none'; }

async function doLogin() {
  const email = document.getElementById('loginEmail').value.trim();
  const pass  = document.getElementById('loginPass').value;
  if (!email || !pass) { mostrarError('Completa todos los campos.'); return; }

  const fd = new FormData();
  fd.append('action',   'login');
  fd.append('email',    email);
  fd.append('password', pass);
  fd.append('redirect', REDIRECT);

  const r    = await fetch(BASE_URL + '/api/tienda/auth.php', { method: 'POST', body: fd });
  const data = await r.json();

  if (data.success) {
    mostrarExito('¡Bienvenido! Redirigiendo…');
    setTimeout(() => { window.location.href = data.redirect || REDIRECT; }, 800);
  } else {
    mostrarError(data.error || 'Error al iniciar sesión.');
  }
}

async function doRegistro() {
  const nombre  = document.getElementById('regNombre').value.trim();
  const email   = document.getElementById('regEmail').value.trim();
  const pass    = document.getElementById('regPass').value;
  const pass2   = document.getElementById('regPass2').value;
  const tel     = document.getElementById('regTelefono').value.trim();
  const sexo    = document.getElementById('regSexo').value;

  if (!nombre || !email || !pass) { mostrarError('Nombre, correo y contraseña son obligatorios.'); return; }
  if (pass !== pass2)              { mostrarError('Las contraseñas no coinciden.'); return; }
  if (pass.length < 6)             { mostrarError('La contraseña debe tener al menos 6 caracteres.'); return; }

  const fd = new FormData();
  fd.append('action',   'registrar');
  fd.append('nombre',   nombre);
  fd.append('email',    email);
  fd.append('password', pass);
  fd.append('telefono', tel);
  fd.append('sexo',     sexo);

  const r    = await fetch(BASE_URL + '/api/tienda/auth.php', { method: 'POST', body: fd });
  const data = await r.json();

  if (data.success) {
    mostrarExito('¡Cuenta creada! Redirigiendo…');
    setTimeout(() => { window.location.href = data.redirect || BASE_URL + '/tienda/'; }, 800);
  } else {
    mostrarError(data.error || 'Error al crear la cuenta.');
  }
}

// Enter para login
document.addEventListener('keydown', e => {
  if (e.key === 'Enter') {
    if (document.getElementById('tabLogin').style.display !== 'none') doLogin();
    else doRegistro();
  }
});
</script>
</body>
</html>
