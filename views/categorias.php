<?php
$pageTitle='Categorías';
require_once __DIR__ . '/../config/config.php';
requireAdmin();
require_once __DIR__ . '/../models/Categoria.php';
$m=new Categoria();$msg='';$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $a=$_POST['action']??'';
    if($a==='create'){$n=trim($_POST['nombre']??'');$d=trim($_POST['descripcion']??'');if(!empty($n)){$m->create($n,$d);$msg='Categoría creada';}else $error='Nombre obligatorio';}
    elseif($a==='update'){$id=intval($_POST['id']);$n=trim($_POST['nombre']??'');$d=trim($_POST['descripcion']??'');if($id>0&&!empty($n)){$m->update($id,$n,$d);$msg='Categoría actualizada';}}
    elseif($a==='delete'){$id=intval($_POST['id']);if($id>0){$m->delete($id);$msg='Categoría eliminada';}}
}
$categorias=$m->getAll();
include __DIR__ . '/partials/head.php';
?>
<div class="layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" id="menu-toggle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <h1 class="page-title">Categorías</h1>
    </div>
    <div class="topbar-right"><button class="btn btn-primary" onclick="openModal('modal-cat')">+ Nueva Categoría</button></div>
  </header>
  <div class="content">
    <?php if($msg):?><div class="alert alert-success">✓ <?=htmlspecialchars($msg)?></div><?php endif;?>
    <?php if($error):?><div class="alert alert-error">⚠ <?=htmlspecialchars($error)?></div><?php endif;?>
    <div class="card">
      <div class="card-header"><span class="card-title">Categorías</span><span class="badge badge-info"><?=count($categorias)?></span></div>
      <div class="table-wrap">
        <table><thead><tr><th>#</th><th>Nombre</th><th>Descripción</th><th>Creada</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php if(empty($categorias)):?><tr><td colspan="5" class="table-empty">No hay categorías</td></tr>
          <?php else: foreach($categorias as $c):?>
          <tr>
            <td style="color:var(--white-muted);font-size:.8rem"><?=$c['id']?></td>
            <td><strong><?=htmlspecialchars($c['nombre'])?></strong></td>
            <td style="color:var(--white-muted)"><?=htmlspecialchars($c['descripcion']?:'—')?></td>
            <td style="font-size:.8rem;color:var(--white-muted)"><?=date('d/m/Y',strtotime($c['created_at']))?></td>
            <td><div style="display:flex;gap:6px">
              <button class="btn btn-outline btn-sm" onclick="editCat(<?=$c['id']?>,<?=json_encode($c['nombre'])?>,<?=json_encode($c['descripcion']??'')?>)">✏️ Editar</button>
              <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$c['id']?>"><button type="submit" class="btn btn-danger btn-sm">🗑</button></form>
            </div></td>
          </tr>
          <?php endforeach; endif;?>
        </tbody></table>
      </div>
    </div>
  </div>
</div></div>

<div class="modal-overlay" id="modal-cat">
  <div class="modal" style="max-width:480px">
    <div class="modal-header"><span class="modal-title" id="modal-cat-title">Nueva Categoría</span><button class="modal-close" onclick="closeModal('modal-cat')">✕</button></div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" id="cat-action" value="create">
        <input type="hidden" name="id" id="cat-id" value="">
        <div class="form-group" style="margin-bottom:14px"><label>Nombre *</label><input type="text" name="nombre" id="cat-nombre" required placeholder="Ej: Dama - Casual"></div>
        <div class="form-group"><label>Descripción</label><textarea name="descripcion" id="cat-desc" placeholder="Opcional..."></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('modal-cat')">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
    </form>
  </div>
</div>
<script>window.BASE_URL='<?=BASE_URL?>';</script>
<script src="<?=BASE_URL?>/assets/js/app.js"></script>
<script>
function editCat(id,nombre,desc){
  document.getElementById('modal-cat-title').textContent='Editar Categoría';
  document.getElementById('cat-action').value='update';
  document.getElementById('cat-id').value=id;
  document.getElementById('cat-nombre').value=nombre;
  document.getElementById('cat-desc').value=desc;
  openModal('modal-cat');
}
</script>
</body></html>
