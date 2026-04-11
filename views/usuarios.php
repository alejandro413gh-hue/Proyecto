<?php
$pageTitle = 'Usuarios';
require_once __DIR__ . '/../config/config.php';
requireAdmin();
require_once __DIR__ . '/../models/Usuario.php';

$um  = new Usuario();
$msg = ''; $error = '';
$verVentas = null; // usuario cuyas ventas se muestran

// ---- PROCESAR ACCIONES ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nombre = trim($_POST['nombre'] ?? '');
        $email  = trim($_POST['email']  ?? '');
        $pass   = $_POST['password']    ?? '';
        $rol    = $_POST['rol']         ?? 'vendedor';
        if (empty($nombre)||empty($email)||empty($pass)) { $error='Todos los campos son obligatorios'; }
        elseif (strlen($pass)<6) { $error='La contraseña debe tener mínimo 6 caracteres'; }
        elseif (!in_array($rol,['administrador','gestor_inventario','vendedor'])) { $error='Rol inválido'; }
        else {
            $r = $um->create($nombre,$email,$pass,$rol);
            if (isset($r['success'])) $msg='Usuario creado correctamente';
            else $error=$r['error'];
        }

    } elseif ($action === 'update') {
        $id     = intval($_POST['id']??0);
        $nombre = trim($_POST['nombre']??'');
        $email  = trim($_POST['email'] ??'');
        $rol    = $_POST['rol']        ?? 'vendedor';
        $activo = intval($_POST['activo']??1);
        if ($id<=0||empty($nombre)||empty($email)) { $error='Datos inválidos'; }
        elseif ($id===$_SESSION['user_id']&&$rol!=='administrador') { $error='No puedes quitarte el rol de administrador'; }
        else {
            $r=$um->update($id,$nombre,$email,$rol,$activo);
            if (isset($r['success'])) $msg='Usuario actualizado correctamente';
            else $error=$r['error'];
        }

    } elseif ($action === 'cambiar_password') {
        $id      = intval($_POST['id']??0);
        $nueva   = $_POST['nueva_password']??'';
        $confirm = $_POST['confirmar_password']??'';
        if (strlen($nueva)<6) { $error='La contraseña debe tener mínimo 6 caracteres'; }
        elseif ($nueva!==$confirm) { $error='Las contraseñas no coinciden'; }
        else {
            if ($um->cambiarPassword($id,$nueva)) $msg='Contraseña actualizada correctamente';
            else $error='Error al cambiar contraseña';
        }

    } elseif ($action === 'toggle') {
        $id=intval($_POST['id']??0);
        if ($id===$_SESSION['user_id']) { $error='No puedes desactivar tu propia cuenta'; }
        else { $um->toggleActivo($id); $msg='Estado actualizado'; }

    } elseif ($action === 'delete') {
        $id=intval($_POST['id']??0);
        if ($id===$_SESSION['user_id']) { $error='No puedes eliminar tu propia cuenta'; }
        else { $um->delete($id); $msg='Usuario desactivado'; }
    }
}

// Ver ventas de un usuario
if (isset($_GET['ventas']) && is_numeric($_GET['ventas'])) {
    $verVentas = $um->findById((int)$_GET['ventas']);
}

$usuarios = $um->getAll();
include __DIR__ . '/partials/head.php';
?>
<div class="layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" id="menu-toggle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <h1 class="page-title">Usuarios</h1>
    </div>
    <div class="topbar-right">
      <button class="btn btn-primary" onclick="abrirNuevo()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nuevo Usuario
      </button>
    </div>
  </header>

  <div class="content">
    <?php if($msg): ?><div class="alert alert-success" style="margin-bottom:16px">✓ <?=htmlspecialchars($msg)?></div><?php endif;?>
    <?php if($error): ?><div class="alert alert-error" style="margin-bottom:16px">⚠ <?=htmlspecialchars($error)?></div><?php endif;?>

    <!-- VENTAS DE UN USUARIO -->
    <?php if($verVentas): ?>
    <?php
      $ventas  = $um->getVentasPorUsuario($verVentas['id']);
      $resumen = $um->getResumenVentas($verVentas['id']);
    ?>
    <div class="card" style="margin-bottom:24px;border-color:rgba(201,168,76,.3)">
      <div class="card-header">
        <div>
          <span class="card-title">📊 Ventas de <span style="color:var(--gold)"><?=htmlspecialchars($verVentas['nombre'])?></span></span>
          <div style="font-size:.75rem;color:var(--white-muted);margin-top:3px"><?=htmlspecialchars($verVentas['email'])?> · <?=htmlspecialchars($verVentas['rol'])?></div>
        </div>
        <a href="usuarios.php" class="btn btn-outline btn-sm">✕ Cerrar</a>
      </div>
      <div class="card-body" style="padding:16px 20px">
        <div style="display:flex;gap:20px;margin-bottom:20px;flex-wrap:wrap">
          <div style="background:var(--bg-panel);padding:14px 20px;border-radius:8px;border:1px solid var(--border);text-align:center">
            <div style="font-size:.7rem;color:var(--white-muted);text-transform:uppercase;letter-spacing:.1em;margin-bottom:6px">Total Ventas</div>
            <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:600;color:var(--gold-light)"><?=$resumen['total_ventas']?></div>
          </div>
          <div style="background:var(--bg-panel);padding:14px 20px;border-radius:8px;border:1px solid var(--border);text-align:center">
            <div style="font-size:.7rem;color:var(--white-muted);text-transform:uppercase;letter-spacing:.1em;margin-bottom:6px">Monto Total</div>
            <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:600;color:var(--gold-light)">$<?=number_format($resumen['total_monto']/1000,0)?>k</div>
            <div style="font-size:.72rem;color:var(--white-muted)">COP <?=number_format($resumen['total_monto'],0,',','.')?></div>
          </div>
        </div>
        <?php if(empty($ventas)): ?>
        <p style="color:var(--white-muted);text-align:center;padding:30px;font-size:.85rem">Este usuario no tiene ventas registradas</p>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>#</th><th>Cliente</th><th>Total</th><th>Descuento</th><th>Fecha</th><th>Estado</th></tr></thead>
            <tbody>
              <?php foreach($ventas as $v): ?>
              <tr>
                <td><span style="color:var(--gold-light);font-weight:600">#<?=$v['id']?></span></td>
                <td><?=htmlspecialchars($v['cliente_nombre']??'Cliente General')?></td>
                <td style="color:var(--gold-light);font-weight:600">$<?=number_format($v['total'],0,',','.')?></td>
                <td><?php echo $v['descuento']>0 ? '<span style="color:var(--success)">-$'.number_format($v['descuento'],0,',','.').'</span>' : '—'; ?></td>
                <td style="font-size:.82rem"><?=date('d/m/Y H:i',strtotime($v['fecha']))?></td>
                <td><span class="badge badge-<?=$v['estado']==='completada'?'success':'warning'?>"><?=$v['estado']?></span></td>
              </tr>
              <?php endforeach;?>
            </tbody>
          </table>
        </div>
        <?php endif;?>
      </div>
    </div>
    <?php endif;?>

    <!-- TABLA USUARIOS -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">Equipo de Trabajo <span style="color:var(--gold-light);font-size:.85rem">(<?=count($usuarios)?>)</span></span>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Usuario</th><th>Correo</th><th>Rol</th><th>Ventas</th><th>Estado</th><th>Acciones</th></tr>
          </thead>
          <tbody>
            <?php foreach($usuarios as $u):
              $resumen = $um->getResumenVentas($u['id']);
              $esYo = $u['id'] == $_SESSION['user_id'];
            ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:10px">
                  <div style="width:36px;height:36px;border-radius:50%;background:<?=$u['rol']==='administrador'?'var(--gold-dim)':'var(--bg-hover)'?>;color:<?=$u['rol']==='administrador'?'var(--gold-light)':'var(--white-muted)'?>;display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:700;flex-shrink:0;border:1px solid <?=$u['rol']==='administrador'?'rgba(201,168,76,.4)':'var(--border)'?>">
                    <?=strtoupper(substr($u['nombre'],0,1))?>
                  </div>
                  <div>
                    <div style="font-weight:600"><?=htmlspecialchars($u['nombre'])?><?=$esYo?' <span style="font-size:.68rem;background:var(--gold-dim);color:var(--gold);padding:1px 7px;border-radius:10px;margin-left:4px">Tú</span>':''?></div>
                    <div style="font-size:.72rem;color:var(--white-muted)">Desde <?=date('d/m/Y',strtotime($u['created_at']))?></div>
                  </div>
                </div>
              </td>
              <td style="font-size:.85rem"><?=htmlspecialchars($u['email'])?></td>
              <td>
                <?php
                  if($u['rol']==='administrador') echo '<span class="badge badge-info">⚙ Admin</span>';
                  elseif($u['rol']==='gestor_inventario') echo '<span class="badge badge-warning">📦 Gestor</span>';
                  else echo '<span class="badge badge-success">🏪 Vendedor</span>';
                ?>
              </td>
              <td>
                <div style="font-size:.85rem">
                  <strong style="color:var(--gold-light)"><?=$resumen['total_ventas']?></strong>
                  <span style="color:var(--white-muted)"> ventas</span><br>
                  <span style="font-size:.75rem;color:var(--white-muted)">$<?=number_format($resumen['total_monto'],0,',','.')?></span>
                </div>
              </td>
              <td>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?=$u['id']?>">
                  <?php
                    $badgeClass = $u['activo'] ? 'badge-success' : 'badge-danger';
                    $disabledAttr = $esYo ? 'disabled title="No puedes desactivarte"' : '';
                    $labelBtn = $u['activo'] ? '✓ Activo' : '✕ Inactivo';
                  ?>
                  <button type="submit" class="badge <?=$badgeClass?>" style="cursor:pointer;border:none;background:inherit" <?=$disabledAttr?>>
                    <?=$labelBtn?>
                  </button>
                </form>
              </td>
              <td>
                <div style="display:flex;gap:5px;flex-wrap:wrap">
                  <a href="usuarios.php?ventas=<?=$u['id']?>" class="btn btn-outline btn-sm" title="Ver ventas">📊</a>
                  <button class="btn btn-outline btn-sm" onclick="abrirEditar(this)">✏️ Editar</button>
                  <button class="btn btn-primary btn-sm" onclick="abrirPassword(this)">🔑</button>
                  <?php if(!$esYo): ?>
                  <button class="btn btn-danger btn-sm" onclick="desactivar(this)">🗑</button>
                  <?php endif;?>
                </div>
                <!-- Datos ocultos -->
                <span style="display:none" class="d-id"><?=$u['id']?></span>
                <span style="display:none" class="d-nombre"><?=htmlspecialchars($u['nombre'],ENT_QUOTES)?></span>
                <span style="display:none" class="d-email"><?=htmlspecialchars($u['email'],ENT_QUOTES)?></span>
                <span style="display:none" class="d-rol"><?=$u['rol']?></span>
                <span style="display:none" class="d-activo"><?=$u['activo']?></span>
              </td>
            </tr>
            <?php endforeach;?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</div>

<!-- MODAL: Crear/Editar Usuario -->
<div id="overlay-user" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:1000;align-items:center;justify-content:center;padding:20px" onclick="if(event.target===this)cerrar('overlay-user')">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:520px;box-shadow:0 20px 60px rgba(0,0,0,.6)">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border)">
      <span id="user-modal-titulo" style="font-family:var(--font-display);font-size:1.1rem;color:var(--gold-light)">Nuevo Usuario</span>
      <button onclick="cerrar('overlay-user')" style="background:none;border:none;color:var(--white-muted);cursor:pointer;font-size:1.2rem">✕</button>
    </div>
    <form method="POST" style="padding:24px">
      <input type="hidden" name="action" id="user-action" value="create">
      <input type="hidden" name="id"     id="user-id"     value="">
      <div class="form-grid">
        <div class="form-group span-2">
          <label>Nombre Completo *</label>
          <input type="text" name="nombre" id="user-nombre" placeholder="Ej: Juan Pérez" required>
        </div>
        <div class="form-group span-2">
          <label>Correo Electrónico *</label>
          <input type="email" name="email" id="user-email" placeholder="usuario@visionreal.com" required>
        </div>
        <div class="form-group" id="grupo-pass">
          <label>Contraseña *</label>
          <input type="password" name="password" id="user-pass" placeholder="Mínimo 6 caracteres">
        </div>
        <div class="form-group">
          <label>Rol *</label>
          <select name="rol" id="user-rol">
            <option value="vendedor">🏪 Vendedor</option>
            <option value="gestor_inventario">📦 Gestor de Inventario</option>
            <option value="administrador">⚙ Administrador</option>
          </select>
        </div>
        <div class="form-group" id="grupo-activo-user" style="display:none">
          <label>Estado</label>
          <select name="activo" id="user-activo">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
      </div>
      <div style="background:var(--bg-panel);border-radius:8px;padding:12px 14px;margin-top:4px;font-size:.78rem;color:var(--white-muted);line-height:1.6" id="info-rol">
        🏪 <strong style="color:var(--white)">Vendedor:</strong> registra ventas y consulta productos/clientes. No puede modificar inventario.<br>
        📦 <strong style="color:var(--white)">Gestor de Inventario:</strong> crea/edita productos, gestiona tallas y stock.<br>
        ⚙ <strong style="color:var(--white)">Administrador:</strong> acceso completo al sistema.
      </div>
      <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
        <button type="button" class="btn btn-outline" onclick="cerrar('overlay-user')">Cancelar</button>
        <button type="submit" class="btn btn-primary">✓ Guardar Usuario</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: Cambiar Contraseña -->
<div id="overlay-pass" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:1000;align-items:center;justify-content:center;padding:20px" onclick="if(event.target===this)cerrar('overlay-pass')">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:440px;box-shadow:0 20px 60px rgba(0,0,0,.6)">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border)">
      <span style="font-family:var(--font-display);font-size:1.1rem;color:var(--gold-light)">🔑 Cambiar Contraseña</span>
      <button onclick="cerrar('overlay-pass')" style="background:none;border:none;color:var(--white-muted);cursor:pointer;font-size:1.2rem">✕</button>
    </div>
    <form method="POST" style="padding:24px">
      <input type="hidden" name="action" value="cambiar_password">
      <input type="hidden" name="id" id="pass-id" value="">
      <div style="background:var(--gold-dim);border:1px solid rgba(201,168,76,.2);border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:.82rem;color:var(--gold-light)">
        Cambiando contraseña de: <strong id="pass-nombre-display"></strong>
      </div>
      <div class="form-group" style="margin-bottom:14px">
        <label>Nueva Contraseña *</label>
        <input type="password" name="nueva_password" id="pass-nueva" placeholder="Mínimo 6 caracteres" required minlength="6">
      </div>
      <div class="form-group" style="margin-bottom:4px">
        <label>Confirmar Contraseña *</label>
        <input type="password" name="confirmar_password" id="pass-confirm" placeholder="Repite la contraseña" required>
      </div>
      <div id="pass-match" style="font-size:.75rem;margin-top:6px;margin-bottom:14px;min-height:18px"></div>
      <div style="display:flex;justify-content:flex-end;gap:10px;padding-top:16px;border-top:1px solid var(--border)">
        <button type="button" class="btn btn-outline" onclick="cerrar('overlay-pass')">Cancelar</button>
        <button type="submit" class="btn btn-primary">🔑 Cambiar Contraseña</button>
      </div>
    </form>
  </div>
</div>

<script>
function cerrar(id) { document.getElementById(id).style.display='none'; }

function abrirNuevo() {
  document.getElementById('user-modal-titulo').textContent = 'Nuevo Usuario';
  document.getElementById('user-action').value = 'create';
  document.getElementById('user-id').value     = '';
  document.getElementById('user-nombre').value = '';
  document.getElementById('user-email').value  = '';
  document.getElementById('user-pass').value   = '';
  document.getElementById('user-rol').value    = 'vendedor';
  document.getElementById('grupo-pass').style.display        = 'block';
  document.getElementById('grupo-activo-user').style.display = 'none';
  document.getElementById('user-pass').required = true;
  document.getElementById('overlay-user').style.display = 'flex';
}

function abrirEditar(btn) {
  var td = btn.closest('td');
  document.getElementById('user-modal-titulo').textContent = 'Editar Usuario';
  document.getElementById('user-action').value = 'update';
  document.getElementById('user-id').value     = td.querySelector('.d-id').textContent.trim();
  document.getElementById('user-nombre').value = td.querySelector('.d-nombre').textContent.trim();
  document.getElementById('user-email').value  = td.querySelector('.d-email').textContent.trim();
  document.getElementById('user-rol').value    = td.querySelector('.d-rol').textContent.trim();
  document.getElementById('user-activo').value = td.querySelector('.d-activo').textContent.trim();
  document.getElementById('grupo-pass').style.display        = 'none';
  document.getElementById('grupo-activo-user').style.display = 'block';
  document.getElementById('user-pass').required = false;
  document.getElementById('overlay-user').style.display = 'flex';
}

function abrirPassword(btn) {
  var td = btn.closest('td');
  var id     = td.querySelector('.d-id').textContent.trim();
  var nombre = td.querySelector('.d-nombre').textContent.trim();
  document.getElementById('pass-id').value = id;
  document.getElementById('pass-nombre-display').textContent = nombre;
  document.getElementById('pass-nueva').value   = '';
  document.getElementById('pass-confirm').value = '';
  document.getElementById('pass-match').textContent = '';
  document.getElementById('overlay-pass').style.display = 'flex';
}

function desactivar(btn) {
  var td     = btn.closest('td');
  var nombre = td.querySelector('.d-nombre').textContent.trim();
  var id     = td.querySelector('.d-id').textContent.trim();
  if (!confirm('¿Desactivar al usuario "' + nombre + '"?\nSus ventas seguirán registradas.')) return;
  var f = document.createElement('form'); f.method='POST'; f.style.display='none';
  f.innerHTML = '<input name="action" value="delete"><input name="id" value="' + id + '">';
  document.body.appendChild(f); f.submit();
}

// Validar que contraseñas coincidan
document.getElementById('pass-confirm').addEventListener('input', function() {
  var nueva   = document.getElementById('pass-nueva').value;
  var confirm = this.value;
  var el      = document.getElementById('pass-match');
  if (!confirm) { el.textContent=''; return; }
  if (nueva===confirm) {
    el.textContent='✓ Las contraseñas coinciden';
    el.style.color='var(--success)';
  } else {
    el.textContent='✕ Las contraseñas no coinciden';
    el.style.color='var(--danger)';
  }
});

var mt=document.getElementById('menu-toggle'),sb=document.getElementById('sidebar');
if(mt&&sb)mt.addEventListener('click',()=>sb.classList.toggle('open'));
</script>
</body>
</html>
