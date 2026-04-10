<?php
require_once __DIR__ . '/config/config.php';
if(isLoggedIn()){ header('Location:'.BASE_URL.'/views/dashboard.php'); exit(); }
$error='';
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
    <?php if($error): ?>
    <div class="alert alert-error" style="margin-bottom:18px">⚠ <?=htmlspecialchars($error)?></div>
    <?php endif; ?>
    <form method="POST" class="login-form">
      <div class="form-group">
        <label>Correo Electrónico</label>
        <input type="email" name="email" placeholder="usuario@visionreal.com" required value="<?=htmlspecialchars($_POST['email']??'')?>">
      </div>
      <div class="form-group">
        <label>Contraseña</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-login">Iniciar Sesión</button>
    </form>
    <div class="login-hint">
      <strong style="color:var(--gold-light)">Demo:</strong>
      admin@visionreal.com / <code>password</code><br>
      vendedor@visionreal.com / <code>password</code>
    </div>
  </div>
</div>
<script>window.BASE_URL='<?=BASE_URL?>';</script>
<script src="<?=BASE_URL?>/assets/js/app.js"></script>
</body></html>
