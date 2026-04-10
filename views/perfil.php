<?php
$pageTitle = 'Mi Perfil';
require_once __DIR__ . '/../config/config.php';
requireLogin();
require_once __DIR__ . '/../models/Usuario.php';

$um   = new Usuario();
$user = $um->findById($_SESSION['user_id']);
$msg  = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_perfil') {
        $nombre = trim($_POST['nombre'] ?? '');
        $email  = trim($_POST['email']  ?? '');
        if (empty($nombre)||empty($email)) {
            $error = 'Nombre y correo son obligatorios';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Correo inválido';
        } else {
            $r = $um->update($_SESSION['user_id'], $nombre, $email, $user['rol'], 1);
            if (isset($r['success'])) {
                $_SESSION['user_nombre'] = $nombre;
                $_SESSION['user_email']  = $email;
                $msg  = 'Perfil actualizado correctamente';
                $user = $um->findById($_SESSION['user_id']);
            } else {
                $error = $r['error'];
            }
        }

    } elseif ($action === 'cambiar_password') {
        $actual  = $_POST['password_actual'] ?? '';
        $nueva   = $_POST['nueva_password']  ?? '';
        $confirm = $_POST['confirmar']        ?? '';
        if (!$um->verificarPassword($_SESSION['user_id'], $actual)) {
            $error = 'La contraseña actual es incorrecta';
        } elseif (strlen($nueva) < 6) {
            $error = 'La nueva contraseña debe tener mínimo 6 caracteres';
        } elseif ($nueva !== $confirm) {
            $error = 'Las contraseñas no coinciden';
        } else {
            if ($um->cambiarPassword($_SESSION['user_id'], $nueva))
                $msg = 'Contraseña cambiada correctamente';
            else
                $error = 'Error al cambiar contraseña';
        }
    }
}

$resumen = $um->getResumenVentas($_SESSION['user_id']);
include __DIR__ . '/partials/head.php';
?>
<div class="layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" id="menu-toggle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <h1 class="page-title">Mi Perfil</h1>
    </div>
    <div class="topbar-right">
      <span class="badge-gold"><?=htmlspecialchars($user['rol'])?></span>
    </div>
  </header>

  <div class="content">
    <?php if($msg): ?><div class="alert alert-success" style="margin-bottom:20px">✓ <?=htmlspecialchars($msg)?></div><?php endif;?>
    <?php if($error): ?><div class="alert alert-error" style="margin-bottom:20px">⚠ <?=htmlspecialchars($error)?></div><?php endif;?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

      <!-- TARJETA PERFIL -->
      <div>
        <!-- Avatar card -->
        <div class="card" style="margin-bottom:20px">
          <div class="card-body" style="padding:28px;text-align:center">
            <div style="width:80px;height:80px;border-radius:50%;background:var(--gold-dim);border:2px solid var(--gold);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;color:var(--gold-light);margin:0 auto 16px;font-family:var(--font-display)">
              <?=strtoupper(substr($user['nombre'],0,1))?>
            </div>
            <div style="font-family:var(--font-display);font-size:1.3rem;font-weight:600;color:var(--white);margin-bottom:4px"><?=htmlspecialchars($user['nombre'])?></div>
            <div style="font-size:.8rem;color:var(--white-muted);margin-bottom:12px"><?=htmlspecialchars($user['email'])?></div>
            <span class="badge <?=$user['rol']==='administrador'?'badge-info':'badge-success'?>" style="font-size:.78rem;padding:5px 14px">
              <?=$user['rol']==='administrador'?'⚙ Administrador':'🏪 Vendedor'?>
            </span>

            <!-- Resumen ventas -->
            <div style="display:flex;gap:16px;justify-content:center;margin-top:20px;padding-top:20px;border-top:1px solid var(--border)">
              <div style="text-align:center">
                <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:600;color:var(--gold-light)"><?=$resumen['total_ventas']?></div>
                <div style="font-size:.72rem;color:var(--white-muted);text-transform:uppercase;letter-spacing:.1em">Ventas</div>
              </div>
              <div style="width:1px;background:var(--border)"></div>
              <div style="text-align:center">
                <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:600;color:var(--gold-light)">$<?=number_format($resumen['total_monto']/1000,0)?>k</div>
                <div style="font-size:.72rem;color:var(--white-muted);text-transform:uppercase;letter-spacing:.1em">COP Total</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Editar datos -->
        <div class="card">
          <div class="card-header"><span class="card-title">✏️ Editar mis datos</span></div>
          <form method="POST" style="padding:20px">
            <input type="hidden" name="action" value="update_perfil">
            <div class="form-group" style="margin-bottom:14px">
              <label>Nombre Completo *</label>
              <input type="text" name="nombre" value="<?=htmlspecialchars($user['nombre'])?>" required>
            </div>
            <div class="form-group" style="margin-bottom:16px">
              <label>Correo Electrónico *</label>
              <input type="email" name="email" value="<?=htmlspecialchars($user['email'])?>" required>
            </div>
            <div style="display:flex;justify-content:flex-end">
              <button type="submit" class="btn btn-primary">✓ Guardar Cambios</button>
            </div>
          </form>
        </div>
      </div>

      <!-- CAMBIAR CONTRASEÑA -->
      <div>
        <div class="card">
          <div class="card-header"><span class="card-title">🔑 Cambiar Contraseña</span></div>
          <form method="POST" style="padding:20px" id="form-pass">
            <input type="hidden" name="action" value="cambiar_password">
            <div class="form-group" style="margin-bottom:14px">
              <label>Contraseña Actual *</label>
              <input type="password" name="password_actual" id="pa-actual" placeholder="Tu contraseña actual" required>
            </div>
            <div class="form-group" style="margin-bottom:14px">
              <label>Nueva Contraseña *</label>
              <input type="password" name="nueva_password" id="pa-nueva" placeholder="Mínimo 6 caracteres" required minlength="6">
            </div>
            <div class="form-group" style="margin-bottom:6px">
              <label>Confirmar Nueva Contraseña *</label>
              <input type="password" name="confirmar" id="pa-confirmar" placeholder="Repite la nueva contraseña" required>
            </div>
            <div id="pass-feedback" style="font-size:.75rem;min-height:18px;margin-bottom:14px"></div>

            <!-- Indicador de fortaleza -->
            <div style="margin-bottom:16px">
              <div style="font-size:.72rem;color:var(--white-muted);margin-bottom:6px">Fortaleza de la contraseña:</div>
              <div style="height:6px;background:var(--bg-hover);border-radius:3px;overflow:hidden">
                <div id="pass-strength-bar" style="height:100%;width:0%;border-radius:3px;transition:.3s"></div>
              </div>
              <div id="pass-strength-txt" style="font-size:.72rem;margin-top:4px;color:var(--white-muted)"></div>
            </div>

            <div style="background:var(--bg-panel);border-radius:8px;padding:12px 14px;margin-bottom:16px;font-size:.78rem;color:var(--white-muted);line-height:1.7">
              💡 Usa al menos 8 caracteres, combinando letras, números y símbolos para mayor seguridad.
            </div>
            <div style="display:flex;justify-content:flex-end">
              <button type="submit" class="btn btn-primary" id="btn-cambiar-pass">🔑 Cambiar Contraseña</button>
            </div>
          </form>
        </div>

        <!-- Sesiones / info -->
        <div class="card" style="margin-top:20px">
          <div class="card-header"><span class="card-title">ℹ️ Información de sesión</span></div>
          <div class="card-body" style="padding:16px 20px">
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:.85rem">
              <span style="color:var(--white-muted)">Usuario ID</span>
              <strong>#<?=$user['id']?></strong>
            </div>
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:.85rem">
              <span style="color:var(--white-muted)">Rol actual</span>
              <strong><?=ucfirst($user['rol'])?></strong>
            </div>
            <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:.85rem">
              <span style="color:var(--white-muted)">Estado</span>
              <span class="badge badge-success">Activo</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<script>
// Validar contraseñas en tiempo real
document.getElementById('pa-confirmar').addEventListener('input', function() {
  var nueva    = document.getElementById('pa-nueva').value;
  var confirmar = this.value;
  var fb       = document.getElementById('pass-feedback');
  if (!confirmar) { fb.textContent=''; return; }
  if (nueva===confirmar) {
    fb.textContent='✓ Las contraseñas coinciden';
    fb.style.color='var(--success)';
  } else {
    fb.textContent='✕ Las contraseñas no coinciden';
    fb.style.color='var(--danger)';
  }
});

// Indicador de fortaleza
document.getElementById('pa-nueva').addEventListener('input', function() {
  var p=this.value;
  var score=0;
  if(p.length>=6) score++;
  if(p.length>=8) score++;
  if(/[0-9]/.test(p)) score++;
  if(/[^a-zA-Z0-9]/.test(p)) score++;
  if(/[A-Z]/.test(p)&&/[a-z]/.test(p)) score++;

  var bar=document.getElementById('pass-strength-bar');
  var txt=document.getElementById('pass-strength-txt');
  var colores=['var(--danger)','var(--warning)','var(--warning)','var(--success)','var(--success)'];
  var labels=['Muy débil','Débil','Regular','Fuerte','Muy fuerte'];
  bar.style.width=(score*20)+'%';
  bar.style.background=colores[score-1]||'var(--danger)';
  txt.textContent=p?labels[score-1]||'Muy débil':'';
  txt.style.color=colores[score-1]||'var(--danger)';
});

var mt=document.getElementById('menu-toggle'),sb=document.getElementById('sidebar');
if(mt&&sb)mt.addEventListener('click',()=>sb.classList.toggle('open'));
</script>
</body>
</html>
