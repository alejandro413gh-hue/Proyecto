<?php
$pageTitle = 'Productos';
require_once __DIR__ . '/../config/config.php';
requireLogin();
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Talla.php';

$pm = new Producto(); $cm = new Categoria(); $tm = new Talla();
$msg = ''; $error = '';

$db = Database::getInstance();
$check = $db->query("SHOW COLUMNS FROM productos LIKE 'imagen'");
if ($check->num_rows === 0) $db->query("ALTER TABLE productos ADD COLUMN imagen VARCHAR(255) NULL AFTER activo");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && puedeGestionarInventario()) {
    $action  = $_POST['action'] ?? '';
    $id      = intval($_POST['id'] ?? 0);
    $nombre  = trim($_POST['nombre'] ?? '');
    $desc    = trim($_POST['descripcion'] ?? '');
    $precio  = floatval($_POST['precio'] ?? 0);
    $stock   = intval($_POST['stock'] ?? 0);
    $cat     = intval($_POST['categoria_id'] ?? 0);
    $codigo  = strtoupper(trim($_POST['codigo'] ?? ''));

    if (empty($nombre)) { $error = 'El nombre es obligatorio'; }
    elseif ($precio <= 0) { $error = 'El precio debe ser mayor a 0'; }
    else {
        $imagen_nueva = null;
        if (isset($_FILES['imagen']) && !empty($_FILES['imagen']['name']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) { $error = 'Formato no válido.'; }
            elseif ($_FILES['imagen']['size'] > 3*1024*1024) { $error = 'Imagen supera 3MB.'; }
            else {
                $dir = __DIR__ . '/../assets/img/productos/';
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                $fn = 'prod_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $dir.$fn)) $imagen_nueva = $fn;
                else $error = 'No se pudo guardar la imagen.';
            }
        }
        if (!$error) {
            if ($action === 'create') {
                $r = $pm->create($nombre, $desc, $precio, $stock, $cat, $imagen_nueva, $codigo ?: null);
                if (isset($r['success'])) $msg = 'Producto registrado — Código: ' . $r['codigo'];
                else $error = $r['error'];
            } elseif ($action === 'update' && $id > 0) {
                if ($imagen_nueva) {
                    $v = $pm->getById($id);
                    if (!empty($v['imagen'])) { $rp=__DIR__.'/../assets/img/productos/'.$v['imagen']; if(file_exists($rp))@unlink($rp); }
                }
                $r = $pm->update($id, $nombre, $desc, $precio, $stock, $cat, $imagen_nueva, $codigo ?: null);
                if (isset($r['success'])) $msg = 'Producto actualizado';
                else $error = $r['error'];
            } elseif ($action === 'delete' && $id > 0) {
                $v = $pm->getById($id);
                if (!empty($v['imagen'])) { $rp=__DIR__.'/../assets/img/productos/'.$v['imagen']; if(file_exists($rp))@unlink($rp); }
                $pm->delete($id); $msg = 'Producto eliminado';
            }
        }
    }
}

$productos  = $pm->getAll();
$categorias = $cm->getAll();
include __DIR__ . '/partials/head.php';
?>
<div class="layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" id="menu-toggle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <h1 class="page-title">Productos</h1>
    </div>
    <?php if(puedeGestionarInventario()): ?>
    <div class="topbar-right">
      <button class="btn btn-primary" onclick="abrirNuevo()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nuevo Producto
      </button>
    </div>
    <?php endif; ?>
  </header>

  <div class="content">
    <?php if($msg): ?><div class="alert alert-success" style="margin-bottom:16px">✓ <?=htmlspecialchars($msg)?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error" style="margin-bottom:16px">⚠ <?=htmlspecialchars($error)?></div><?php endif; ?>

    <?php if(isVendedor()): ?>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:10px 16px;margin-bottom:16px;font-size:.82rem;color:var(--white-muted);display:flex;align-items:center;gap:8px">
      👁 Como <strong style="color:var(--white)">vendedor</strong> puedes consultar el catálogo y buscar productos por código.
    </div>
    <?php endif; ?>

    <!-- BUSCADOR POR CÓDIGO -->
    <div class="card" style="margin-bottom:20px">
      <div class="card-header">
        <span class="card-title">🔍 Buscar Producto por Código</span>
      </div>
      <div style="padding:16px 20px">
        <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
          <div class="form-group" style="margin:0;flex:1;min-width:200px">
            <label>Código del Producto</label>
            <input type="text" id="buscar-codigo" placeholder="Ej: VR-0001" style="text-transform:uppercase" oninput="this.value=this.value.toUpperCase()">
          </div>
          <button class="btn btn-primary" onclick="buscarPorCodigo()">🔍 Buscar</button>
        </div>

        <!-- Resultado búsqueda -->
        <div id="resultado-busqueda" style="display:none;margin-top:16px;padding:16px;background:var(--bg-panel);border-radius:10px;border:1px solid var(--border)">
          <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
            <div>
              <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.1em;color:var(--gold);font-weight:700;margin-bottom:4px" id="res-codigo-lbl"></div>
              <div style="font-size:1.1rem;font-weight:700;color:var(--white)" id="res-nombre"></div>
              <div style="font-size:.82rem;color:var(--white-muted);margin-top:2px" id="res-categoria"></div>
            </div>
            <div style="text-align:right">
              <div style="font-family:var(--font-display);font-size:1.5rem;font-weight:700;color:var(--gold-light)" id="res-precio"></div>
              <div style="font-size:.75rem;color:var(--white-muted)" id="res-stock"></div>
            </div>
          </div>
          <div id="res-tallas" style="margin-top:12px;display:flex;flex-wrap:wrap;gap:6px"></div>

          <?php if(puedeGestionarInventario()): ?>
          <!-- Actualizar precio -->
          <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
            <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:var(--white-muted);font-weight:600;margin-bottom:10px">Actualizar Precio</div>
            <div style="display:flex;gap:10px;align-items:flex-end">
              <div class="form-group" style="margin:0;flex:1">
                <label>Nuevo Precio (COP)</label>
                <input type="number" id="nuevo-precio" placeholder="Nuevo precio" min="1" step="1">
              </div>
              <button class="btn btn-primary" onclick="actualizarPrecio()">✓ Actualizar Precio</button>
            </div>
            <div id="precio-msg" style="display:none;margin-top:8px;padding:8px 12px;border-radius:7px;font-size:.82rem"></div>
          </div>
          <?php endif; ?>
        </div>

        <!-- No encontrado -->
        <div id="no-encontrado" style="display:none;margin-top:16px;padding:14px 16px;background:var(--bg-panel);border-radius:10px;border:1px solid rgba(231,76,60,.3)">
          <div style="color:var(--danger);font-size:.88rem;font-weight:600;margin-bottom:6px">⚠ Código no encontrado</div>
          <div style="font-size:.8rem;color:var(--white-muted)" id="no-enc-msg"></div>
          <?php if(puedeGestionarInventario()): ?>
          <button class="btn btn-primary btn-sm" style="margin-top:10px" onclick="crearConCodigo()">+ Crear producto con este código</button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
      <div class="stat-card"><div class="stat-label">Total Productos</div><div class="stat-value" style="font-size:1.6rem"><?=count($productos)?></div></div>
      <div class="stat-card"><div class="stat-label">Categorías</div><div class="stat-value" style="font-size:1.6rem"><?=count($categorias)?></div></div>
      <div class="stat-card"><div class="stat-label">Stock Bajo (≤5)</div><div class="stat-value" style="font-size:1.6rem;color:var(--warning)"><?=$pm->countLowStock()?></div></div>
    </div>

    <div class="card">
      <div class="card-header">
        <span class="card-title">Catálogo de Productos</span>
        <div class="search-input-wrap"><span class="search-icon">🔍</span><input type="text" id="buscador" placeholder="Buscar nombre o código..." oninput="filtrar(this.value)"></div>
      </div>
      <div class="table-wrap">
        <table id="tbl">
          <thead>
            <tr>
              <th>Foto</th>
              <th>Código</th>
              <th>Nombre</th>
              <th>Categoría</th>
              <th>Precio</th>
              <th>Stock</th>
              <th>Tallas</th>
              <th>Estado</th>
              <?php if(puedeGestionarInventario()): ?><th>Acciones</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($productos)): ?>
            <tr><td colspan="9" class="table-empty">No hay productos registrados</td></tr>
            <?php else: foreach($productos as $p):
              $sc     = $p['stock']==0 ? 'stock-danger' : ($p['stock']<=5 ? 'stock-warn' : 'stock-ok');
              $img    = !empty($p['imagen']) ? BASE_URL.'/assets/img/productos/'.rawurlencode($p['imagen']) : '';
              $tallas = $tm->getPorProducto($p['id']);
              $codigo = $p['codigo'] ?? 'VR-'.str_pad($p['id'],4,'0',STR_PAD_LEFT);
            ?>
            <tr>
              <td>
                <?php if($img): ?>
                <img src="<?=$img?>" style="width:48px;height:48px;object-fit:cover;border-radius:8px;border:1px solid var(--border);cursor:zoom-in" onclick="verImagen('<?=$img?>')" onerror="this.outerHTML='<div style=\'width:48px;height:48px;border-radius:8px;background:var(--bg-hover);display:flex;align-items:center;justify-content:center;font-size:1.3rem\'>🧥</div>'">
                <?php else: ?><div style="width:48px;height:48px;border-radius:8px;background:var(--bg-hover);display:flex;align-items:center;justify-content:center;font-size:1.3rem">🧥</div><?php endif; ?>
              </td>
              <td>
                <span style="font-family:monospace;font-size:.82rem;font-weight:700;color:var(--gold-light);background:var(--gold-dim);padding:3px 8px;border-radius:6px;border:1px solid rgba(201,168,76,.3);cursor:pointer" title="Clic para buscar" onclick="document.getElementById('buscar-codigo').value='<?=htmlspecialchars($codigo)?>'; buscarPorCodigo()">
                  <?=htmlspecialchars($codigo)?>
                </span>
              </td>
              <td><strong><?=htmlspecialchars($p['nombre'])?></strong></td>
              <td><?=htmlspecialchars($p['categoria_nombre']??'—')?></td>
              <td style="color:var(--gold-light);font-weight:600">$<?=number_format($p['precio'],0,',','.')?></td>
              <td><span class="<?=$sc?>" style="font-weight:600"><?=$p['stock']?></span> <span style="color:var(--white-muted);font-size:.72rem">uds</span></td>
              <td>
                <?php if(empty($tallas)): ?>
                  <span style="color:var(--white-muted);font-size:.75rem">Sin tallas</span>
                <?php else: ?>
                  <div style="display:flex;flex-wrap:wrap;gap:4px;max-width:160px">
                    <?php foreach($tallas as $t): ?>
                    <span title="<?=$t['stock']?> uds" style="font-size:.68rem;padding:2px 7px;border-radius:12px;font-weight:600;border:1px solid;<?=
                      $t['stock']==0  ? 'border-color:var(--danger);color:var(--danger);background:var(--danger-dim)' :
                      ($t['stock']<=3 ? 'border-color:var(--warning);color:var(--warning);background:var(--warning-dim)' :
                                        'border-color:var(--gold);color:var(--gold-light);background:var(--gold-dim)')
                    ?>"><?=htmlspecialchars($t['talla'])?><sup style="margin-left:2px;font-size:.6rem"><?=$t['stock']?></sup></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </td>
              <td><?php if($p['stock']==0):?><span class="badge badge-danger">Agotado</span><?php elseif($p['stock']<=5):?><span class="badge badge-warning">Stock bajo</span><?php else:?><span class="badge badge-success">Disponible</span><?php endif;?></td>

              <?php if(puedeGestionarInventario()): ?>
              <td>
                <div style="display:flex;gap:5px;flex-wrap:wrap">
                  <button class="btn btn-outline btn-sm" onclick="abrirEditar(this)">✏️</button>
                  <button class="btn btn-primary btn-sm" onclick="abrirTallas(this)">👕 Tallas</button>
                  <button class="btn btn-danger btn-sm" onclick="borrar(this)">🗑</button>
                </div>
                <span style="display:none" class="d-id"><?=(int)$p['id']?></span>
                <span style="display:none" class="d-codigo"><?=htmlspecialchars($codigo,ENT_QUOTES)?></span>
                <span style="display:none" class="d-nombre"><?=htmlspecialchars($p['nombre'],ENT_QUOTES)?></span>
                <span style="display:none" class="d-desc"><?=htmlspecialchars($p['descripcion']??'',ENT_QUOTES)?></span>
                <span style="display:none" class="d-precio"><?=$p['precio']?></span>
                <span style="display:none" class="d-stock"><?=$p['stock']?></span>
                <span style="display:none" class="d-cat"><?=(int)$p['categoria_id']?></span>
                <span style="display:none" class="d-img"><?=htmlspecialchars($p['imagen']??'',ENT_QUOTES)?></span>
              </td>
              <?php endif; ?>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</div>

<?php if(puedeGestionarInventario()): ?>
<!-- MODAL PRODUCTO -->
<div id="overlay-prod" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:1000;align-items:center;justify-content:center;padding:20px" onclick="if(event.target===this)cerrar()">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:620px;max-height:90vh;overflow-y:auto">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg-card);z-index:1">
      <span id="modal-titulo" style="font-family:var(--font-display);font-size:1.1rem;color:var(--gold-light)">Nuevo Producto</span>
      <button onclick="cerrar()" style="background:none;border:none;color:var(--white-muted);cursor:pointer;font-size:1.2rem">✕</button>
    </div>
    <form method="POST" enctype="multipart/form-data" style="padding:24px">
      <input type="hidden" name="action" id="f-action" value="create">
      <input type="hidden" name="id"     id="f-id"     value="">
      <div class="form-grid">
        <!-- Código -->
        <div class="form-group span-2">
          <label>Código del Producto <span style="color:var(--white-muted);font-size:.7rem">(vacío = se genera automáticamente)</span></label>
          <div style="display:flex;gap:8px;align-items:center">
            <input type="text" name="codigo" id="f-codigo" placeholder="Ej: VR-0001 o déjalo vacío" style="text-transform:uppercase;flex:1" oninput="this.value=this.value.toUpperCase()">
            <button type="button" class="btn btn-outline btn-sm" onclick="generarCodigo()" title="Generar código automático">⚡ Auto</button>
          </div>
          <div id="codigo-feedback" style="font-size:.72rem;margin-top:4px;min-height:16px"></div>
        </div>
        <div class="form-group span-2"><label>Nombre *</label><input type="text" name="nombre" id="f-nombre" required></div>
        <div class="form-group"><label>Precio (COP) *</label><input type="number" name="precio" id="f-precio" min="1" step="1" required></div>
        <div class="form-group"><label>Stock General</label><input type="number" name="stock" id="f-stock" min="0" step="1" value="0"></div>
        <div class="form-group span-2"><label>Categoría</label>
          <select name="categoria_id" id="f-cat">
            <option value="0">— Sin categoría —</option>
            <?php foreach($categorias as $c): ?><option value="<?=$c['id']?>"><?=htmlspecialchars($c['nombre'])?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group span-2"><label>Descripción</label><textarea name="descripcion" id="f-desc" style="min-height:60px"></textarea></div>
        <div class="form-group span-2">
          <label>Imagen <span style="color:var(--white-muted);font-size:.7rem">(JPG/PNG/WEBP — máx. 3MB)</span></label>
          <div id="img-actual-wrap" style="display:none;align-items:center;gap:10px;margin-bottom:10px;padding:10px;background:var(--bg-panel);border-radius:8px;border:1px solid var(--border)">
            <img id="img-actual" src="" style="width:56px;height:56px;object-fit:cover;border-radius:6px">
            <span style="font-size:.75rem;color:var(--white-muted)">Imagen actual — sube una nueva para reemplazar</span>
          </div>
          <div id="drop-zone" onclick="document.getElementById('f-img').click()"
            ondragover="event.preventDefault();this.style.borderColor='var(--gold)'" ondragleave="this.style.borderColor='var(--border)'" ondrop="handleDrop(event)"
            style="border:2px dashed var(--border);border-radius:10px;padding:24px;text-align:center;cursor:pointer;transition:.2s;background:var(--bg-panel)">
            <div id="dz-placeholder"><div style="font-size:2rem;margin-bottom:8px">📷</div><div style="font-size:.85rem;color:var(--white-dim)">Clic o arrastra la imagen aquí</div></div>
            <img id="img-preview" src="" style="display:none;max-width:100%;max-height:160px;border-radius:8px;margin:0 auto;object-fit:contain">
          </div>
          <input type="file" name="imagen" id="f-img" accept="image/*" style="display:none" onchange="previewImg(this)">
          <div style="display:flex;justify-content:space-between;margin-top:6px;min-height:18px">
            <span id="img-fn" style="font-size:.72rem;color:var(--white-muted)"></span>
            <button type="button" id="btn-quitar" onclick="quitarImg()" style="display:none;background:none;border:none;color:var(--danger);font-size:.75rem;cursor:pointer">✕ Quitar</button>
          </div>
        </div>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
        <button type="button" class="btn btn-outline" onclick="cerrar()">Cancelar</button>
        <button type="submit" class="btn btn-primary">✓ Guardar Producto</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL TALLAS -->
<div id="overlay-tallas" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:1000;align-items:center;justify-content:center;padding:20px" onclick="if(event.target===this)cerrarTallas()">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:580px;max-height:90vh;overflow-y:auto">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg-card);z-index:1">
      <div>
        <div style="font-family:var(--font-display);font-size:1.1rem;color:var(--gold-light)">👕 Gestión de Tallas</div>
        <div id="talla-prod-nombre" style="font-size:.78rem;color:var(--white-muted);margin-top:2px"></div>
      </div>
      <button onclick="cerrarTallas()" style="background:none;border:none;color:var(--white-muted);cursor:pointer;font-size:1.2rem">✕</button>
    </div>
    <div style="padding:24px">
      <input type="hidden" id="talla-prod-id">
      <div style="background:var(--gold-dim);border:1px solid rgba(201,168,76,0.2);border-radius:8px;padding:12px 14px;margin-bottom:20px;font-size:.82rem;color:var(--white-dim);line-height:1.6">
        💡 Agrega tallas con su cantidad. Usa XS, S, M, L, XL, XXL o tallas numéricas (28, 30, 32...).
      </div>
      <div style="background:var(--bg-panel);border-radius:10px;padding:16px;margin-bottom:20px;border:1px solid var(--border)">
        <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.1em;color:var(--white-muted);margin-bottom:12px;font-weight:600">+ Agregar / Actualizar Talla</div>
        <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:10px;align-items:end">
          <div class="form-group" style="margin:0"><label>Talla</label><input type="text" id="nueva-talla" placeholder="Ej: M, XL, 32..." style="text-transform:uppercase"></div>
          <div class="form-group" style="margin:0"><label>Cantidad</label><input type="number" id="nueva-cantidad" placeholder="Ej: 10" min="0" step="1" value="0"></div>
          <button class="btn btn-primary" onclick="guardarTalla()">Agregar</button>
        </div>
        <div style="margin-top:12px">
          <div style="font-size:.7rem;color:var(--white-muted);margin-bottom:7px">Tallas rápidas:</div>
          <div style="display:flex;flex-wrap:wrap;gap:6px">
            <?php foreach(['XS','S','M','L','XL','XXL','XXXL','28','30','32','34','36','38','Único'] as $t): ?>
            <button onclick="document.getElementById('nueva-talla').value='<?=$t?>'" class="btn btn-outline btn-sm" style="padding:4px 10px;font-size:.72rem"><?=$t?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div id="talla-error" style="display:none;margin-top:10px;padding:8px 12px;background:var(--danger-dim);color:var(--danger);border-radius:7px;font-size:.82rem"></div>
      </div>
      <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.1em;color:var(--white-muted);margin-bottom:10px;font-weight:600">Tallas Registradas</div>
      <div id="tallas-lista"><div style="text-align:center;padding:30px;color:var(--white-muted);font-size:.85rem">Cargando tallas...</div></div>
    </div>
  </div>
</div>
<?php endif; ?>

<div id="img-zoom" onclick="this.style.display='none'" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.88);z-index:2000;align-items:center;justify-content:center;cursor:zoom-out">
  <img id="img-zoom-src" src="" style="max-width:90vw;max-height:90vh;border-radius:10px">
</div>

<script>
const CTRL_TALLA  = '<?=BASE_URL?>/controllers/TallaController.php';
const CTRL_CODIGO = '<?=BASE_URL?>/controllers/CodigoController.php';
const PUEDE_GESTIONAR = <?=puedeGestionarInventario()?'true':'false'?>;

var prodEncontradoId = null;

// ===== BUSCAR POR CÓDIGO =====
function buscarPorCodigo() {
  var codigo = document.getElementById('buscar-codigo').value.trim().toUpperCase();
  if (!codigo) return;

  document.getElementById('resultado-busqueda').style.display = 'none';
  document.getElementById('no-encontrado').style.display = 'none';

  var xhr = new XMLHttpRequest();
  xhr.open('GET', CTRL_CODIGO + '?action=buscar&codigo=' + encodeURIComponent(codigo), true);
  xhr.onload = function() {
    var d = JSON.parse(xhr.responseText);
    if (d.encontrado) {
      prodEncontradoId = d.id;
      document.getElementById('res-codigo-lbl').textContent = d.codigo;
      document.getElementById('res-nombre').textContent     = d.nombre;
      document.getElementById('res-categoria').textContent  = d.categoria || '—';
      document.getElementById('res-precio').textContent     = '$' + Math.round(d.precio).toLocaleString('es-CO');
      document.getElementById('res-stock').textContent      = d.stock + ' unidades disponibles';

      var tallasDiv = document.getElementById('res-tallas');
      if (d.tallas && d.tallas.length > 0) {
        tallasDiv.innerHTML = '<span style="font-size:.72rem;color:var(--white-muted);margin-right:8px">Tallas:</span>' +
          d.tallas.map(function(t) {
            return '<span style="font-size:.75rem;padding:3px 9px;border-radius:8px;font-weight:700;border:1px solid var(--gold);color:var(--gold-light);background:var(--gold-dim)">' + t.talla + ' <sup>' + t.stock + '</sup></span>';
          }).join('');
      } else {
        tallasDiv.innerHTML = '<span style="font-size:.75rem;color:var(--white-muted)">Sin tallas configuradas</span>';
      }

      if (PUEDE_GESTIONAR) {
        document.getElementById('nuevo-precio').value = Math.round(d.precio);
        document.getElementById('precio-msg').style.display = 'none';
      }
      document.getElementById('resultado-busqueda').style.display = 'block';
    } else {
      prodEncontradoId = null;
      document.getElementById('no-enc-msg').textContent = 'No existe ningún producto con el código "' + codigo + '".';
      document.getElementById('no-encontrado').style.display = 'block';
    }
  };
  xhr.send();
}

// Buscar al presionar Enter
document.getElementById('buscar-codigo').addEventListener('keydown', function(e) {
  if (e.key === 'Enter') buscarPorCodigo();
});

<?php if(puedeGestionarInventario()): ?>
// ===== ACTUALIZAR PRECIO =====
function actualizarPrecio() {
  if (!prodEncontradoId) return;
  var precio = parseFloat(document.getElementById('nuevo-precio').value);
  var msgEl  = document.getElementById('precio-msg');
  if (!precio || precio <= 0) {
    msgEl.textContent = '⚠ Ingresa un precio válido'; msgEl.style.background='var(--danger-dim)'; msgEl.style.color='var(--danger)'; msgEl.style.display='block'; return;
  }
  var fd = new FormData();
  fd.append('action','actualizar_precio'); fd.append('id', prodEncontradoId); fd.append('precio', precio);
  var xhr = new XMLHttpRequest();
  xhr.open('POST', CTRL_CODIGO, true);
  xhr.onload = function() {
    var d = JSON.parse(xhr.responseText);
    if (d.success) {
      document.getElementById('res-precio').textContent = '$' + Math.round(precio).toLocaleString('es-CO');
      msgEl.textContent = '✓ ' + d.success; msgEl.style.background='rgba(39,174,96,.1)'; msgEl.style.color='var(--success)'; msgEl.style.display='block';
      setTimeout(function(){ msgEl.style.display='none'; }, 3000);
    } else {
      msgEl.textContent = '⚠ ' + d.error; msgEl.style.background='var(--danger-dim)'; msgEl.style.color='var(--danger)'; msgEl.style.display='block';
    }
  };
  xhr.send(fd);
}

// ===== CREAR CON CÓDIGO =====
function crearConCodigo() {
  var codigo = document.getElementById('buscar-codigo').value.trim().toUpperCase();
  document.getElementById('no-encontrado').style.display = 'none';
  abrirNuevo();
  document.getElementById('f-codigo').value = codigo;
}

// ===== GENERAR CÓDIGO AUTOMÁTICO =====
function generarCodigo() {
  var xhr = new XMLHttpRequest();
  xhr.open('GET', CTRL_CODIGO + '?action=generar', true);
  xhr.onload = function() {
    var d = JSON.parse(xhr.responseText);
    document.getElementById('f-codigo').value = d.codigo;
    var fb = document.getElementById('codigo-feedback');
    fb.textContent = '✓ Código generado automáticamente';
    fb.style.color = 'var(--success)';
  };
  xhr.send();
}

// Validar código en tiempo real
document.getElementById('f-codigo').addEventListener('blur', function() {
  var codigo = this.value.trim().toUpperCase();
  var fb = document.getElementById('codigo-feedback');
  if (!codigo) { fb.textContent = ''; return; }
  var xhr = new XMLHttpRequest();
  var idActual = document.getElementById('f-id').value || 0;
  xhr.open('GET', CTRL_CODIGO + '?action=buscar&codigo=' + encodeURIComponent(codigo), true);
  xhr.onload = function() {
    var d = JSON.parse(xhr.responseText);
    if (d.encontrado && d.id != idActual) {
      fb.textContent = '⚠ Este código ya está en uso por: ' + d.nombre;
      fb.style.color = 'var(--danger)';
    } else if (!d.encontrado) {
      fb.textContent = '✓ Código disponible';
      fb.style.color = 'var(--success)';
    } else {
      fb.textContent = '';
    }
  };
  xhr.send();
});

// ===== PRODUCTO MODAL =====
function abrirNuevo() {
  document.getElementById('modal-titulo').textContent='Nuevo Producto';
  document.getElementById('f-action').value='create'; document.getElementById('f-id').value='';
  ['f-nombre','f-precio','f-desc','f-codigo'].forEach(function(id){document.getElementById(id).value='';});
  document.getElementById('f-stock').value='0';
  document.getElementById('f-cat').value='0';
  document.getElementById('codigo-feedback').textContent='';
  document.getElementById('img-actual-wrap').style.display='none';
  resetImg(); document.getElementById('overlay-prod').style.display='flex';
}

function abrirEditar(btn) {
  var td=btn.closest('td');
  document.getElementById('modal-titulo').textContent='Editar Producto';
  document.getElementById('f-action').value='update';
  document.getElementById('f-id').value     =td.querySelector('.d-id').textContent.trim();
  document.getElementById('f-codigo').value =td.querySelector('.d-codigo').textContent.trim();
  document.getElementById('f-nombre').value =td.querySelector('.d-nombre').textContent.trim();
  document.getElementById('f-desc').value   =td.querySelector('.d-desc').textContent.trim();
  document.getElementById('f-precio').value =td.querySelector('.d-precio').textContent.trim();
  document.getElementById('f-stock').value  =td.querySelector('.d-stock').textContent.trim();
  document.getElementById('f-cat').value    =td.querySelector('.d-cat').textContent.trim();
  document.getElementById('codigo-feedback').textContent='';
  var img=td.querySelector('.d-img').textContent.trim();
  var wrap=document.getElementById('img-actual-wrap');
  if(img){document.getElementById('img-actual').src='<?=BASE_URL?>/assets/img/productos/'+img;wrap.style.display='flex';}
  else wrap.style.display='none';
  resetImg(); document.getElementById('overlay-prod').style.display='flex';
}

function cerrar(){document.getElementById('overlay-prod').style.display='none';}

function borrar(btn){
  var td=btn.closest('td');
  var nombre=td.querySelector('.d-nombre').textContent.trim();
  var id=td.querySelector('.d-id').textContent.trim();
  if(!confirm('¿Eliminar "'+nombre+'"?')) return;
  var f=document.createElement('form'); f.method='POST'; f.style.display='none';
  f.innerHTML='<input name="action" value="delete"><input name="id" value="'+id+'">';
  document.body.appendChild(f); f.submit();
}

// ===== IMAGEN =====
function previewImg(input){if(!input.files||!input.files[0])return;var fr=new FileReader();fr.onload=function(e){mostrarPrev(e.target.result,input.files[0].name);};fr.readAsDataURL(input.files[0]);}
function handleDrop(e){e.preventDefault();var file=e.dataTransfer.files[0];if(!file||!file.type.startsWith('image/'))return;try{var dt=new DataTransfer();dt.items.add(file);document.getElementById('f-img').files=dt.files;}catch(err){}var fr=new FileReader();fr.onload=function(ev){mostrarPrev(ev.target.result,file.name);};fr.readAsDataURL(file);}
function mostrarPrev(src,nombre){document.getElementById('dz-placeholder').style.display='none';var p=document.getElementById('img-preview');p.src=src;p.style.display='block';document.getElementById('drop-zone').style.borderColor='var(--gold)';document.getElementById('img-fn').textContent='📎 '+nombre;document.getElementById('btn-quitar').style.display='inline';}
function quitarImg(){document.getElementById('f-img').value='';resetImg();}
function resetImg(){document.getElementById('dz-placeholder').style.display='block';document.getElementById('img-preview').style.display='none';document.getElementById('img-preview').src='';document.getElementById('drop-zone').style.borderColor='var(--border)';document.getElementById('img-fn').textContent='';document.getElementById('btn-quitar').style.display='none';try{document.getElementById('f-img').value='';}catch(e){}}

// ===== TALLAS =====
function abrirTallas(btn){
  var td=btn.closest('td');
  document.getElementById('talla-prod-id').value=td.querySelector('.d-id').textContent.trim();
  document.getElementById('talla-prod-nombre').textContent=td.querySelector('.d-nombre').textContent.trim();
  document.getElementById('nueva-talla').value=''; document.getElementById('nueva-cantidad').value='0';
  document.getElementById('talla-error').style.display='none';
  document.getElementById('overlay-tallas').style.display='flex';
  cargarTallas(td.querySelector('.d-id').textContent.trim());
}
function cerrarTallas(){document.getElementById('overlay-tallas').style.display='none';}

async function cargarTallas(prodId){
  try{var r=await fetch(CTRL_TALLA+'?action=get&producto_id='+prodId);var d=await r.json();renderTallas(d.tallas||[]);}
  catch(e){document.getElementById('tallas-lista').innerHTML='<div style="color:var(--danger);padding:20px;text-align:center">Error al cargar</div>';}
}
function renderTallas(tallas){
  var cont=document.getElementById('tallas-lista');
  if(!tallas.length){cont.innerHTML='<div style="text-align:center;padding:30px;color:var(--white-muted);font-size:.85rem">📭 No hay tallas registradas</div>';return;}
  var total=tallas.reduce(function(a,t){return a+parseInt(t.stock);},0);
  cont.innerHTML='<div style="background:var(--bg-panel);border-radius:8px;overflow:hidden;border:1px solid var(--border)"><table style="width:100%;border-collapse:collapse"><thead><tr>'+
    '<th style="padding:10px 14px;text-align:left;font-size:.7rem;text-transform:uppercase;color:var(--white-muted);border-bottom:1px solid var(--border)">Talla</th>'+
    '<th style="padding:10px 14px;text-align:center;font-size:.7rem;text-transform:uppercase;color:var(--white-muted);border-bottom:1px solid var(--border)">Stock</th>'+
    '<th style="padding:10px 14px;text-align:center;font-size:.7rem;text-transform:uppercase;color:var(--white-muted);border-bottom:1px solid var(--border)">Editar</th>'+
    '<th style="padding:10px 14px;font-size:.7rem;text-transform:uppercase;color:var(--white-muted);border-bottom:1px solid var(--border)">Estado</th>'+
    '<th style="padding:10px 14px;border-bottom:1px solid var(--border)"></th></tr></thead><tbody>'+
    tallas.map(function(t){return '<tr style="border-bottom:1px solid rgba(255,255,255,0.04)">'+
      '<td style="padding:11px 14px"><span style="font-size:1rem;font-weight:700;color:var(--white);font-family:var(--font-display)">'+t.talla+'</span></td>'+
      '<td style="padding:11px 14px;text-align:center"><strong style="font-size:1.1rem;color:'+(t.stock==0?'var(--danger)':t.stock<=3?'var(--warning)':'var(--success)')+'">'+t.stock+'</strong></td>'+
      '<td style="padding:11px 14px;text-align:center"><input type="number" min="0" value="'+t.stock+'" style="width:70px;text-align:center;padding:5px 8px;background:var(--bg-hover);border:1px solid var(--border);border-radius:6px;color:var(--white);font-size:.85rem" onchange="actualizarStock(\''+t.talla+'\',this.value)"></td>'+
      '<td style="padding:11px 14px">'+(t.stock==0?'<span class="badge badge-danger">Agotada</span>':t.stock<=3?'<span class="badge badge-warning">Poco stock</span>':'<span class="badge badge-success">Disponible</span>')+'</td>'+
      '<td style="padding:11px 14px"><button onclick="eliminarTalla('+t.id+',\''+t.talla+'\')" style="background:var(--danger-dim);border:1px solid rgba(192,57,43,.3);color:var(--danger);border-radius:6px;padding:4px 10px;cursor:pointer;font-size:.75rem">✕</button></td>'+
    '</tr>';}).join('')+
    '</tbody><tfoot><tr><td style="padding:10px 14px;font-size:.78rem;color:var(--white-muted);font-weight:600">TOTAL</td><td style="padding:10px 14px;text-align:center;font-weight:700;color:var(--gold-light)">'+total+' uds</td><td colspan="3"></td></tr></tfoot></table></div>';
}
async function guardarTalla(){
  var prodId=document.getElementById('talla-prod-id').value;
  var talla=document.getElementById('nueva-talla').value.trim().toUpperCase();
  var cantidad=parseInt(document.getElementById('nueva-cantidad').value)||0;
  var errEl=document.getElementById('talla-error');
  if(!talla){errEl.textContent='Escribe la talla';errEl.style.display='block';return;}
  errEl.style.display='none';
  var fd=new FormData();fd.append('action','guardar');fd.append('producto_id',prodId);fd.append('talla',talla);fd.append('stock',cantidad);
  var r=await fetch(CTRL_TALLA,{method:'POST',body:fd});var d=await r.json();
  if(d.success){renderTallas(d.tallas);document.getElementById('nueva-talla').value='';document.getElementById('nueva-cantidad').value='0';}
  else{errEl.textContent=d.error||'Error';errEl.style.display='block';}
}
async function actualizarStock(talla,nuevoStock){
  var prodId=document.getElementById('talla-prod-id').value;
  var fd=new FormData();fd.append('action','guardar');fd.append('producto_id',prodId);fd.append('talla',talla);fd.append('stock',parseInt(nuevoStock)||0);
  var r=await fetch(CTRL_TALLA,{method:'POST',body:fd});var d=await r.json();if(d.success)renderTallas(d.tallas);
}
async function eliminarTalla(id,talla){
  if(!confirm('¿Eliminar la talla "'+talla+'"?'))return;
  var prodId=document.getElementById('talla-prod-id').value;
  var fd=new FormData();fd.append('action','eliminar');fd.append('id',id);fd.append('producto_id',prodId);
  var r=await fetch(CTRL_TALLA,{method:'POST',body:fd});var d=await r.json();if(d.success)renderTallas(d.tallas);
}
<?php endif; ?>

function filtrar(q){q=q.toLowerCase();document.querySelectorAll('#tbl tbody tr').forEach(function(r){r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});}
function verImagen(src){document.getElementById('img-zoom-src').src=src;document.getElementById('img-zoom').style.display='flex';}
var mt=document.getElementById('menu-toggle'),sb=document.getElementById('sidebar');
if(mt&&sb)mt.addEventListener('click',function(){sb.classList.toggle('open');});
</script>
</body>
</html>
