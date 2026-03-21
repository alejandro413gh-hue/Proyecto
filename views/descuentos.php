<?php
$pageTitle = 'Descuentos';
require_once __DIR__ . '/../config/config.php';
requireAdmin();
require_once __DIR__ . '/../models/Descuento.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Producto.php';

$dm = new Descuento();
$cm = new Categoria();
$pm = new Producto();

$msg=''; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_once __DIR__ . '/../controllers/DescuentoController.php';
    $ctrl=new DescuentoController();
    $action=$_POST['action']??'';
    if($action==='create'){$r=$ctrl->create();if(isset($r['success']))$msg=$r['success'];else $error=$r['error'];}
    elseif($action==='update'){$r=$ctrl->update();if(isset($r['success']))$msg=$r['success'];else $error=$r['error'];}
    elseif($action==='delete'){$r=$ctrl->delete();if(isset($r['success']))$msg=$r['success'];else $error=$r['error'];}
    elseif($action==='toggle'){$ctrl->toggle();header('Location: descuentos.php');exit();}
}

$descuentos = $dm->getAll();
$categorias = $cm->getAll();
$productos  = $pm->getAll();
$activos    = $dm->getActivos();
$hoy        = date('Y-m-d');

include __DIR__ . '/partials/head.php';
?>
<div class="layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" id="menu-toggle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <h1 class="page-title">Descuentos</h1>
    </div>
    <div class="topbar-right">
      <button class="btn btn-primary" onclick="abrirNuevo()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nuevo Descuento
      </button>
    </div>
  </header>

  <div class="content">
    <?php if($msg): ?><div class="alert alert-success" style="margin-bottom:16px">✓ <?=htmlspecialchars($msg)?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error" style="margin-bottom:16px">⚠ <?=htmlspecialchars($error)?></div><?php endif; ?>

    <!-- INFO -->
    <div style="background:var(--gold-dim);border:1px solid rgba(201,168,76,0.25);border-radius:10px;padding:16px 20px;margin-bottom:24px;display:flex;gap:14px;align-items:flex-start">
      <span style="font-size:1.4rem">🏷️</span>
      <div style="font-size:.83rem;color:var(--white-dim);line-height:1.7">
        <strong style="color:var(--gold-light)">¿Cómo funcionan los descuentos?</strong><br>
        El sistema evalúa automáticamente todos los descuentos activos al momento de la venta y aplica <strong style="color:var(--white)">el mejor disponible</strong>.
        Puedes crear descuentos por <strong style="color:var(--white)">fecha especial</strong> (Día de la Mujer, Día de la Madre...),
        por <strong style="color:var(--white)">producto específico</strong>, por <strong style="color:var(--white)">categoría</strong>,
        por <strong style="color:var(--white)">género del cliente</strong> o por <strong style="color:var(--white)">número de compras previas</strong>.
      </div>
    </div>

    <!-- STATS -->
    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px">
      <div class="stat-card"><div class="stat-label">Total Descuentos</div><div class="stat-value" style="font-size:1.6rem"><?=count($descuentos)?></div></div>
      <div class="stat-card"><div class="stat-label">Activos Hoy</div><div class="stat-value" style="font-size:1.6rem;color:var(--success)"><?=count($activos)?></div></div>
      <div class="stat-card"><div class="stat-label">Inactivos</div><div class="stat-value" style="font-size:1.6rem;color:var(--white-muted)"><?=count($descuentos)-count($activos)?></div></div>
    </div>

    <!-- DESCUENTOS ACTIVOS HOY -->
    <?php if(!empty($activos)): ?>
    <div style="background:rgba(39,174,96,0.08);border:1px solid rgba(39,174,96,0.25);border-radius:10px;padding:16px 20px;margin-bottom:24px">
      <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.1em;color:var(--success);font-weight:700;margin-bottom:12px">✓ Descuentos activos hoy (<?=$hoy?>)</div>
      <div style="display:flex;flex-wrap:wrap;gap:8px">
        <?php foreach($activos as $a): ?>
        <div style="background:rgba(39,174,96,0.12);border:1px solid rgba(39,174,96,0.3);border-radius:8px;padding:8px 14px">
          <div style="font-size:.85rem;font-weight:600;color:var(--success)"><?=htmlspecialchars($a['nombre'])?></div>
          <div style="font-size:.72rem;color:var(--white-muted);margin-top:2px">
            <?=$a['tipo_descuento']==='porcentaje'?$a['valor'].'%':'$'.number_format($a['valor'],0,',','.')?>
            <?php if($a['aplica_genero']!=='todos'): ?> · Solo <?=$a['aplica_genero']?><?php endif;?>
            <?php if($a['cat_nombre']): ?> · <?=htmlspecialchars($a['cat_nombre'])?><?php endif;?>
            <?php if($a['prod_nombre']): ?> · <?=htmlspecialchars($a['prod_nombre'])?><?php endif;?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- TABLA -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">Lista de Descuentos</span>
        <div class="search-input-wrap"><span class="search-icon">🔍</span><input type="text" id="buscador" placeholder="Buscar..." oninput="filtrar(this.value)"></div>
      </div>
      <div class="table-wrap">
        <table id="tbl">
          <thead>
            <tr><th>#</th><th>Nombre</th><th>Descuento</th><th>Aplica a</th><th>Condición</th><th>Vigencia</th><th>Estado</th><th>Acciones</th></tr>
          </thead>
          <tbody>
            <?php if(empty($descuentos)): ?>
            <tr><td colspan="8" class="table-empty">No hay descuentos creados</td></tr>
            <?php else: foreach($descuentos as $d):
              $vigente = $d['activo'] &&
                ($d['fecha_inicio']===null || $d['fecha_inicio']<=$hoy) &&
                ($d['fecha_fin']===null   || $d['fecha_fin']>=$hoy);
            ?>
            <tr>
              <td style="color:var(--white-muted);font-size:.8rem"><?=$d['id']?></td>
              <td>
                <strong><?=htmlspecialchars($d['nombre'])?></strong>
                <?php if($d['descripcion']): ?>
                <br><small style="color:var(--white-muted)"><?=htmlspecialchars(substr($d['descripcion'],0,50))?></small>
                <?php endif;?>
              </td>
              <td>
                <span style="font-family:var(--font-display);font-size:1.1rem;font-weight:700;color:var(--gold-light)">
                  <?=$d['tipo_descuento']==='porcentaje'?$d['valor'].'%':'$'.number_format($d['valor'],0,',','.')?>
                </span>
                <br><small style="color:var(--white-muted)"><?=$d['tipo_descuento']==='porcentaje'?'Porcentaje':'Monto fijo'?></small>
              </td>
              <td>
                <?php if($d['prod_nombre']): ?>
                  <span class="badge badge-info">📦 <?=htmlspecialchars($d['prod_nombre'])?></span>
                <?php elseif($d['cat_nombre']): ?>
                  <span class="badge badge-info">🏷 <?=htmlspecialchars($d['cat_nombre'])?></span>
                <?php else: ?>
                  <span class="badge badge-success">🛍 Todo el carrito</span>
                <?php endif;?>
                <?php if($d['aplica_genero']!=='todos'): ?>
                  <br><small style="color:var(--white-muted)">Solo <?=$d['aplica_genero']==='dama'?'👩 Dama':'👨 Caballero'?></small>
                <?php endif;?>
              </td>
              <td>
                <?php if($d['compras_minimas']>0): ?>
                  <span class="badge badge-warning">≥ <?=$d['compras_minimas']?> compras</span>
                <?php else: ?>
                  <span style="color:var(--white-muted);font-size:.8rem">Sin mínimo</span>
                <?php endif;?>
              </td>
              <td style="font-size:.8rem">
                <?php if($d['fecha_inicio']||$d['fecha_fin']): ?>
                  <?=($d['fecha_inicio']?date('d/m/Y',strtotime($d['fecha_inicio'])):'∞')?> →
                  <?=($d['fecha_fin']?date('d/m/Y',strtotime($d['fecha_fin'])):'∞')?>
                  <?php if($vigente&&$d['fecha_inicio']&&$d['fecha_fin']): ?>
                    <br><span style="color:var(--success);font-size:.72rem">● Vigente hoy</span>
                  <?php elseif($d['fecha_fin']&&$d['fecha_fin']<$hoy): ?>
                    <br><span style="color:var(--danger);font-size:.72rem">● Expirado</span>
                  <?php elseif($d['fecha_inicio']&&$d['fecha_inicio']>$hoy): ?>
                    <br><span style="color:var(--warning);font-size:.72rem">● Próximo</span>
                  <?php endif;?>
                <?php else: ?>
                  <span style="color:var(--white-muted)">Sin límite de fecha</span>
                <?php endif;?>
              </td>
              <td>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?=$d['id']?>">
                  <button type="submit" class="badge <?=$d['activo']?'badge-success':'badge-danger'?>" style="cursor:pointer;border:none;background:inherit;padding:4px 10px">
                    <?=$d['activo']?'✓ Activo':'✕ Inactivo'?>
                  </button>
                </form>
              </td>
              <td>
                <div style="display:flex;gap:6px">
                  <button class="btn btn-outline btn-sm" onclick="abrirEditar(this)">✏️ Editar</button>
                  <button class="btn btn-danger btn-sm" onclick="borrar(this)">🗑</button>
                </div>
                <!-- Datos ocultos -->
                <span style="display:none" class="d-id"><?=$d['id']?></span>
                <span style="display:none" class="d-nombre"><?=htmlspecialchars($d['nombre'],ENT_QUOTES)?></span>
                <span style="display:none" class="d-desc"><?=htmlspecialchars($d['descripcion']??'',ENT_QUOTES)?></span>
                <span style="display:none" class="d-tipo"><?=$d['tipo_descuento']?></span>
                <span style="display:none" class="d-valor"><?=$d['valor']?></span>
                <span style="display:none" class="d-catid"><?=$d['aplica_categoria_id']??0?></span>
                <span style="display:none" class="d-prodid"><?=$d['aplica_producto_id']??0?></span>
                <span style="display:none" class="d-genero"><?=$d['aplica_genero']?></span>
                <span style="display:none" class="d-compras"><?=$d['compras_minimas']?></span>
                <span style="display:none" class="d-fi"><?=$d['fecha_inicio']??''?></span>
                <span style="display:none" class="d-ff"><?=$d['fecha_fin']??''?></span>
                <span style="display:none" class="d-activo"><?=$d['activo']?></span>
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

<!-- MODAL DESCUENTO -->
<div id="overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:1000;align-items:center;justify-content:center;padding:20px;overflow-y:auto" onclick="if(event.target===this)cerrar()">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:680px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.6)">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg-card);z-index:1">
      <span id="modal-titulo" style="font-family:var(--font-display);font-size:1.1rem;color:var(--gold-light)">Nuevo Descuento</span>
      <button onclick="cerrar()" style="background:none;border:none;color:var(--white-muted);cursor:pointer;font-size:1.2rem">✕</button>
    </div>
    <form method="POST" id="form-desc" style="padding:24px">
      <input type="hidden" name="action" id="f-action" value="create">
      <input type="hidden" name="id"     id="f-id"     value="">

      <div class="form-grid">
        <!-- Nombre y descripción -->
        <div class="form-group span-2">
          <label>Nombre del Descuento *</label>
          <input type="text" name="nombre" id="f-nombre" placeholder="Ej: Día de la Mujer, Promo Verano..." required>
        </div>
        <div class="form-group span-2">
          <label>Descripción</label>
          <textarea name="descripcion" id="f-desc" placeholder="Descripción del descuento..." style="min-height:55px"></textarea>
        </div>

        <!-- Tipo y valor -->
        <div class="form-group">
          <label>Tipo de Descuento *</label>
          <select name="tipo_descuento" id="f-tipo" onchange="actualizarLblValor()">
            <option value="porcentaje">Porcentaje (%)</option>
            <option value="monto_fijo">Monto Fijo (COP)</option>
          </select>
        </div>
        <div class="form-group">
          <label id="lbl-valor">Valor (%) *</label>
          <input type="number" name="valor" id="f-valor" placeholder="Ej: 15" min="0.01" step="0.01" required>
        </div>
      </div>

      <!-- SECCIÓN: Condiciones -->
      <div style="margin:20px 0;padding:16px;background:var(--bg-panel);border-radius:10px;border:1px solid var(--border)">
        <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.1em;color:var(--gold-light);font-weight:700;margin-bottom:14px">🎯 ¿A qué aplica?</div>
        <div class="form-grid">
          <div class="form-group span-2">
            <label>Aplica a</label>
            <select id="f-aplica" onchange="cambiarAplica(this.value)">
              <option value="todo">Todo el carrito</option>
              <option value="categoria">Una categoría específica</option>
              <option value="producto">Un producto específico</option>
            </select>
          </div>
          <input type="hidden" name="aplica_categoria_id" id="f-catid" value="">
          <input type="hidden" name="aplica_producto_id"  id="f-prodid" value="">

          <div class="form-group span-2" id="grupo-categoria" style="display:none">
            <label>Categoría</label>
            <select id="f-cat-sel" onchange="document.getElementById('f-catid').value=this.value">
              <option value="">— Selecciona —</option>
              <?php foreach($categorias as $c): ?>
              <option value="<?=$c['id']?>"><?=htmlspecialchars($c['nombre'])?></option>
              <?php endforeach;?>
            </select>
          </div>

          <div class="form-group span-2" id="grupo-producto" style="display:none">
            <label>Producto</label>
            <select id="f-prod-sel" onchange="document.getElementById('f-prodid').value=this.value">
              <option value="">— Selecciona —</option>
              <?php foreach($productos as $p): ?>
              <option value="<?=$p['id']?>"><?=htmlspecialchars($p['nombre'])?> — $<?=number_format($p['precio'],0,',','.')?></option>
              <?php endforeach;?>
            </select>
          </div>

          <div class="form-group">
            <label>Aplica para</label>
            <select name="aplica_genero" id="f-genero">
              <option value="todos">👫 Todos (dama y caballero)</option>
              <option value="dama">👩 Solo Dama</option>
              <option value="caballero">👨 Solo Caballero</option>
            </select>
          </div>

          <div class="form-group">
            <label>Compras mínimas del cliente</label>
            <input type="number" name="compras_minimas" id="f-compras" value="0" min="0" step="1" placeholder="0 = sin mínimo">
            <span style="font-size:.7rem;color:var(--white-muted);margin-top:4px;display:block">0 = aplica a cualquier cliente</span>
          </div>
        </div>
      </div>

      <!-- SECCIÓN: Fechas -->
      <div style="margin-bottom:20px;padding:16px;background:var(--bg-panel);border-radius:10px;border:1px solid var(--border)">
        <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.1em;color:var(--gold-light);font-weight:700;margin-bottom:14px">📅 Vigencia</div>
        <div class="form-grid">
          <div class="form-group">
            <label>Fecha inicio</label>
            <input type="date" name="fecha_inicio" id="f-fi">
            <span style="font-size:.7rem;color:var(--white-muted);margin-top:4px;display:block">Vacío = sin límite de inicio</span>
          </div>
          <div class="form-group">
            <label>Fecha fin</label>
            <input type="date" name="fecha_fin" id="f-ff">
            <span style="font-size:.7rem;color:var(--white-muted);margin-top:4px;display:block">Vacío = sin límite de fin</span>
          </div>
        </div>
        <!-- Fechas rápidas -->
        <div style="margin-top:10px">
          <div style="font-size:.7rem;color:var(--white-muted);margin-bottom:8px">Fechas especiales rápidas:</div>
          <div style="display:flex;flex-wrap:wrap;gap:6px">
            <?php
            $year = date('Y');
            $especiales = [
              'Día de la Mujer'   => ["$year-03-08","$year-03-08"],
              'Día de la Madre'   => ["$year-05-11","$year-05-12"],
              'Día del Padre'     => ["$year-06-21","$year-06-22"],
              'Día del Hombre'    => ["$year-11-19","$year-11-19"],
              'Navidad'           => ["$year-12-24","$year-12-26"],
              'Año Nuevo'         => ["$year-12-31",($year+1)."-01-01"],
              'Halloween'         => ["$year-10-31","$year-10-31"],
              'Black Friday'      => ["$year-11-28","$year-11-29"],
            ];
            foreach($especiales as $label=>[$fi,$ff]):
            ?>
            <button type="button" class="btn btn-outline btn-sm" style="font-size:.72rem;padding:4px 10px"
              onclick="document.getElementById('f-fi').value='<?=$fi?>';document.getElementById('f-ff').value='<?=$ff?>'">
              <?=htmlspecialchars($label)?>
            </button>
            <?php endforeach;?>
          </div>
        </div>
      </div>

      <!-- Estado (solo al editar) -->
      <div id="grupo-activo" style="display:none;margin-bottom:16px">
        <div class="form-group">
          <label>Estado</label>
          <select name="activo" id="f-activo">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
      </div>

      <!-- Preview -->
      <div id="preview-desc" style="background:var(--gold-dim);border:1px solid rgba(201,168,76,0.25);border-radius:8px;padding:12px 14px;margin-bottom:16px;font-size:.82rem;color:var(--gold-light)">
        💡 <span id="preview-txt">Completa los campos para ver el resumen del descuento.</span>
      </div>

      <div style="display:flex;justify-content:flex-end;gap:10px;padding-top:16px;border-top:1px solid var(--border)">
        <button type="button" class="btn btn-outline" onclick="cerrar()">Cancelar</button>
        <button type="submit" class="btn btn-primary">🏷️ Guardar Descuento</button>
      </div>
    </form>
  </div>
</div>

<script>
function filtrar(q){q=q.toLowerCase();document.querySelectorAll('#tbl tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none'});}

function actualizarLblValor(){
  var t=document.getElementById('f-tipo').value;
  document.getElementById('lbl-valor').textContent=t==='porcentaje'?'Valor (%) *':'Monto Fijo COP *';
  actualizarPreview();
}

function cambiarAplica(v){
  document.getElementById('grupo-categoria').style.display=v==='categoria'?'block':'none';
  document.getElementById('grupo-producto').style.display =v==='producto'?'block':'none';
  if(v!=='categoria'){document.getElementById('f-catid').value='';document.getElementById('f-cat-sel').value='';}
  if(v!=='producto'){document.getElementById('f-prodid').value='';document.getElementById('f-prod-sel').value='';}
  actualizarPreview();
}

function actualizarPreview(){
  var nombre  =document.getElementById('f-nombre').value.trim()||'[Descuento]';
  var tipo    =document.getElementById('f-tipo').value;
  var valor   =parseFloat(document.getElementById('f-valor').value)||0;
  var genero  =document.getElementById('f-genero').value;
  var compras =parseInt(document.getElementById('f-compras').value)||0;
  var fi      =document.getElementById('f-fi').value;
  var ff      =document.getElementById('f-ff').value;
  var aplica  =document.getElementById('f-aplica').value;

  var etqValor=tipo==='porcentaje'?valor+'% de descuento':'$'+Math.round(valor).toLocaleString('es-CO')+' de descuento';
  var etqAplica=aplica==='todo'?'todo el carrito':(aplica==='categoria'?'la categoría seleccionada':'el producto seleccionado');
  var etqGenero=genero==='todos'?'':(genero==='dama'?' solo para dama':' solo para caballero');
  var etqCompras=compras>0?' (requiere '+compras+' compra(s) previas)':'';
  var etqFecha=fi&&ff?' del '+fi+' al '+ff:(fi?' desde '+fi:(ff?' hasta '+ff:''));

  document.getElementById('preview-txt').textContent=
    '"'+nombre+'": '+etqValor+' en '+etqAplica+etqGenero+etqCompras+etqFecha+'.';
}

// Actualizar preview en tiempo real
['f-nombre','f-tipo','f-valor','f-genero','f-compras','f-fi','f-ff'].forEach(id=>{
  var el=document.getElementById(id);
  if(el) el.addEventListener('input',actualizarPreview);
  if(el) el.addEventListener('change',actualizarPreview);
});

function abrirNuevo(){
  document.getElementById('modal-titulo').textContent='Nuevo Descuento';
  document.getElementById('f-action').value='create';
  document.getElementById('f-id').value='';
  document.getElementById('form-desc').reset();
  document.getElementById('f-aplica').value='todo';
  cambiarAplica('todo');
  document.getElementById('grupo-activo').style.display='none';
  actualizarLblValor();
  document.getElementById('overlay').style.display='flex';
}

function abrirEditar(btn){
  var td=btn.closest('td');
  document.getElementById('modal-titulo').textContent='Editar Descuento';
  document.getElementById('f-action').value='update';
  document.getElementById('f-id').value    =td.querySelector('.d-id').textContent.trim();
  document.getElementById('f-nombre').value=td.querySelector('.d-nombre').textContent.trim();
  document.getElementById('f-desc').value  =td.querySelector('.d-desc').textContent.trim();
  document.getElementById('f-tipo').value  =td.querySelector('.d-tipo').textContent.trim();
  document.getElementById('f-valor').value =td.querySelector('.d-valor').textContent.trim();
  document.getElementById('f-genero').value=td.querySelector('.d-genero').textContent.trim();
  document.getElementById('f-compras').value=td.querySelector('.d-compras').textContent.trim();
  document.getElementById('f-fi').value    =td.querySelector('.d-fi').textContent.trim();
  document.getElementById('f-ff').value    =td.querySelector('.d-ff').textContent.trim();
  document.getElementById('f-activo').value=td.querySelector('.d-activo').textContent.trim();

  var catid =td.querySelector('.d-catid').textContent.trim();
  var prodid=td.querySelector('.d-prodid').textContent.trim();

  if(prodid&&prodid!='0'){
    document.getElementById('f-aplica').value='producto';
    cambiarAplica('producto');
    document.getElementById('f-prod-sel').value=prodid;
    document.getElementById('f-prodid').value=prodid;
  } else if(catid&&catid!='0'){
    document.getElementById('f-aplica').value='categoria';
    cambiarAplica('categoria');
    document.getElementById('f-cat-sel').value=catid;
    document.getElementById('f-catid').value=catid;
  } else {
    document.getElementById('f-aplica').value='todo';
    cambiarAplica('todo');
  }

  document.getElementById('grupo-activo').style.display='block';
  actualizarLblValor();
  actualizarPreview();
  document.getElementById('overlay').style.display='flex';
}

function cerrar(){document.getElementById('overlay').style.display='none';}

function borrar(btn){
  var td=btn.closest('td');
  var id=td.querySelector('.d-id').textContent.trim();
  var nm=td.querySelector('.d-nombre').textContent.trim();
  if(!confirm('¿Eliminar el descuento "'+nm+'"?')) return;
  var f=document.createElement('form');f.method='POST';f.style.display='none';
  f.innerHTML='<input name="action" value="delete"><input name="id" value="'+id+'">';
  document.body.appendChild(f);f.submit();
}

var mt=document.getElementById('menu-toggle'),sb=document.getElementById('sidebar');
if(mt&&sb)mt.addEventListener('click',()=>sb.classList.toggle('open'));
</script>
</body>
</html>
