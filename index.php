<?php
require_once __DIR__ . '/config/config.php';
if(isLoggedIn()){ header('Location:'.BASE_URL.'/views/dashboard.php'); exit(); }
$error=''; $info='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_once __DIR__ . '/controllers/AuthController.php';
    $ctrl=new AuthController();
    $r=$ctrl->login();
    if(isset($r['success'])){ header('Location:'.BASE_URL.'/views/dashboard.php'); exit(); }
    else $error=$r['error']??'Error desconocido';
}
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Acceso — Visión Real</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="<?=BASE_URL?>/assets/css/style.css">
</head>
<body>
<div id="toast-container"></div>
<div class="login-page">
  <div class="login-bg"></div>
  <div class="login-card">
    <div class="login-logo">
      <span class="brand">Visión Real</span>
      <span class="sub">Sistema Comercial</span>
      <div class="divider-gold"></div>
    </div>
    <?php if($error): ?><div class="alert alert-error" style="margin-bottom:16px">⚠ <?=htmlspecialchars($error)?></div><?php endif; ?>
    
    <form method="POST">
      <div class="form-group">
        <label>Correo Electrónico</label>
        <input type="email" name="email" placeholder="usuario@visionreal.com" required autofocus>
      </div>
      <div class="form-group">
        <label>Contraseña</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;font-weight:700;font-size:.95rem">Iniciar Sesión</button>
    </form>

    <!-- AVISO DE RECUPERACIÓN -->
    <div style="margin-top:16px;padding:12px;background:rgba(201,168,76,0.08);border:1px solid rgba(201,168,76,0.2);border-radius:8px;font-size:.78rem;color:var(--white-dim);line-height:1.6;text-align:center">
      <span style="display:block;margin-bottom:6px">🔐 <strong style="color:var(--gold)">Seguridad</strong></span>
      <span style="color:var(--white-muted)">Si olvidó su contraseña, comuníquese con el <strong style="color:var(--white)">Administrador del sistema</strong>.</span>
    </div>

    <div style="margin-top:12px;padding:10px;background:var(--bg-panel);border-radius:8px;font-size:.7rem;color:var(--white-muted);text-align:center;line-height:1.5">
      ℹ️ Credenciales de demostración para pruebas<br>
      <strong style="color:var(--white)">admin@visionreal.com / password</strong><br>
      <strong style="color:var(--white)">vendedor@visionreal.com / password</strong>
    </div>
  </div>
</div>
</body>
</html>
