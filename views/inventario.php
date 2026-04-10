<?php
$pageTitle = 'Inventario';
require_once __DIR__ . '/../config/config.php';
requireLogin();
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Talla.php';

$pm = new Producto(); $cm = new Categoria(); $tm = new Talla();
$productos  = $pm->getAll();
$categorias = $cm->getAll();
$inventario = $tm->getInventarioCompleto();

$totalStock = array_sum(array_column($productos,'stock'));
$totalValor = array_sum(array_map(fn($p)=>$p['stock']*$p['precio'],$productos));
$agotados   = count(array_filter($productos,fn($p)=>$p['stock']==0));
$stockBajo  = count(array_filter($productos,fn($p)=>$p['stock']>0&&$p['stock']<=5));
include __DIR__ . '/partials/head.php';
?>
<div class="layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" id="menu-toggle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <h1 class="page-title">Inventario</h1>
    </div>
    <?php if(isAdmin()):?><div class="topbar-right"><a href="<?=BASE_URL?>/views/productos.php" class="btn btn-primary">+ Nuevo Producto</a></div><?php endif;?>
  </header>

  <div class="content">
    <div class="stats-grid" style="margin-bottom:24px">
      <div class="stat-card"><div class="stat-label">Total Unidades</div><div class="stat-value"><?=number_format($totalStock)?></div><div class="stat-icon">📦</div></div>
      <div class="stat-card"><div class="stat-label">Valor Inventario</div><div class="stat-value" style="font-size:1.4rem">$<?=number_format($totalValor/1000000,1)?>M</div><div class="stat-sub">COP <?=number_format($totalValor,0,',','.')?></div></div>
      <div class="stat-card"><div class="stat-label">Stock Bajo (≤5)</div><div class="stat-value" style="color:var(--warning)"><?=$stockBajo?></div></div>
      <div class="stat-card"><div class="stat-label">Agotados</div><div class="stat-value" style="color:var(--danger)"><?=$agotados?></div></div>
    </div>

    <!-- FILTROS -->
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;flex-wrap:wrap">
      <span style="font-size:.75rem;text-transform:uppercase;letter-spacing:.1em;color:var(--white-muted)">Filtrar:</span>
      <button class="btn btn-outline btn-sm" onclick="filtrarEstado('all',this)" style="border-color:var(--gold);color:var(--gold-light)">Todos</button>
      <button class="btn btn-outline btn-sm" onclick="filtrarEstado('agotado',this)"><span style="color:var(--danger)">●</span> Agotados</button>
      <button class="btn btn-outline btn-sm" onclick="filtrarEstado('bajo',this)"><span style="color:var(--warning)">●</span> Stock bajo</button>
      <?php foreach($categorias as $cat):?>
      <button class="btn btn-outline btn-sm" onclick="filtrarEstado('cat-<?=$cat['id']?>',this)"><?=htmlspecialchars($cat['nombre'])?></button>
      <?php endforeach;?>
    </div>

    <!-- VISTA CON TALLAS (card por producto) -->
    <div id="vista-tallas" style="margin-bottom:24px">
      <div class="card">
        <div class="card-header">
          <span class="card-title">📦 Stock por Producto y Talla</span>
          <div style="display:flex;gap:8px">
            <div class="search-input-wrap"><span class="search-icon">🔍</span><input type="text" id="buscar-inv" placeholder="Buscar producto..." oninput="buscarInv(this.value)"></div>
            <button class="btn btn-outline btn-sm" onclick="toggleVista()" id="btn-vista">Ver tabla simple</button>
          </div>
        </div>
        <div style="padding:16px 20px">
          <div id="cards-tallas" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px">
            <?php foreach($inventario as $p):
              $est = $p['stock_general']==0?'agotado':($p['stock_general']<=5?'bajo':'ok');
              $totalTallas = array_sum(array_column($p['tallas'],'stock'));
            ?>
            <div class="inv-card" data-nombre="<?=htmlspecialchars(strtolower($p['nombre']),ENT_QUOTES)?>" data-estado="<?=$est?>" data-cat="cat-<?=$p['id']?>"
              style="background:var(--bg-panel);border:1px solid var(--border);border-radius:10px;overflow:hidden">
              <div style="padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
                <div>
                  <div style="font-weight:600;font-size:.9rem;color:var(--white)"><?=htmlspecialchars($p['nombre'])?></div>
                  <div style="font-size:.72rem;color:var(--white-muted);margin-top:2px"><?=htmlspecialchars($p['categoria_nombre']??'—')?> · $<?=number_format($p['precio'],0,',','.')?></div>
                </div>
                <div style="text-align:right">
                  <?php if($p['stock_general']==0):?><span class="badge badge-danger">Agotado</span>
                  <?php elseif($p['stock_general']<=5):?><span class="badge badge-warning">Stock bajo</span>
                  <?php else:?><span class="badge badge-success">OK</span><?php endif;?>
                </div>
              </div>

              <?php if(empty($p['tallas'])): ?>
              <div style="padding:14px 16px;font-size:.8rem;color:var(--white-muted)">
                Stock general: <strong style="color:var(--white)"><?=$p['stock_general']?></strong> unidades · Sin tallas configuradas
              </div>
              <?php else: ?>
              <div style="padding:12px 16px">
                <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.1em;color:var(--white-muted);margin-bottom:10px;font-weight:600">
                  TALLAS — <?=count($p['tallas'])?> tipos · <?=$totalTallas?> unidades totales
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:8px">
                  <?php foreach($p['tallas'] as $t): ?>
                  <div style="display:flex;flex-direction:column;align-items:center;min-width:52px;padding:8px 10px;border-radius:8px;border:1px solid;<?=
                    $t['stock']==0  ? 'border-color:rgba(192,57,43,.4);background:var(--danger-dim)' :
                    ($t['stock']<=3 ? 'border-color:rgba(230,126,34,.4);background:var(--warning-dim)' :
                                      'border-color:var(--border);background:var(--bg-hover)')
                  ?>">
                    <span style="font-size:.95rem;font-weight:800;color:var(--white);font-family:var(--font-display)"><?=htmlspecialchars($t['talla'])?></span>
                    <span style="font-size:.7rem;font-weight:700;color:<?=$t['stock']==0?'var(--danger)':($t['stock']<=3?'var(--warning)':'var(--success)')?>;margin-top:3px"><?=$t['stock']?> uds</span>
                    <?php if($t['stock']==0):?><span style="font-size:.6rem;color:var(--danger)">AGOTADA</span><?php endif;?>
                  </div>
                  <?php endforeach; ?>
                </div>
                <?php
                  $agotadasT = count(array_filter($p['tallas'],fn($t)=>$t['stock']==0));
                  $bajoT     = count(array_filter($p['tallas'],fn($t)=>$t['stock']>0&&$t['stock']<=3));
                ?>
                <?php if($agotadasT||$bajoT): ?>
                <div style="margin-top:10px;font-size:.72rem;color:var(--white-muted)">
                  <?php if($agotadasT):?><span style="color:var(--danger)">⚠ <?=$agotadasT?> talla(s) agotada(s)</span><?php endif;?>
                  <?php if($bajoT):?><span style="color:var(--warning);margin-left:8px">⚠ <?=$bajoT?> con poco stock</span><?php endif;?>
                </div>
                <?php endif; ?>
              </div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- TABLA SIMPLE (oculta por defecto) -->
    <div id="vista-tabla" style="display:none">
      <div class="card">
        <div class="card-header"><span class="card-title">Control de Stock — Tabla</span></div>
        <div class="table-wrap">
          <table id="tbl-inv">
            <thead><tr><th>#</th><th>Producto</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Valor</th><th>Estado</th></tr></thead>
            <tbody>
              <?php $maxSt=max(1,...array_column($productos,'stock')); foreach($productos as $p):
                $est=$p['stock']==0?'agotado':($p['stock']<=5?'bajo':'ok');
                $pct=min(100,($p['stock']/$maxSt)*100);
                $col=$p['stock']==0?'var(--danger)':($p['stock']<=5?'var(--warning)':'var(--success)');
              ?>
              <tr data-estado="<?=$est?>" data-cat="cat-<?=$p['categoria_id']??0?>">
                <td style="color:var(--white-muted);font-size:.8rem"><?=$p['id']?></td>
                <td><strong><?=htmlspecialchars($p['nombre'])?></strong></td>
                <td><span class="badge badge-info"><?=htmlspecialchars($p['categoria_nombre']??'—')?></span></td>
                <td style="color:var(--gold-light);font-weight:500">$<?=number_format($p['precio'],0,',','.')?></td>
                <td><div style="display:flex;align-items:center;gap:10px"><div style="width:80px;height:6px;background:var(--bg-hover);border-radius:3px;overflow:hidden"><div style="height:100%;width:<?=$pct?>%;background:<?=$col?>;border-radius:3px"></div></div><span style="font-weight:600;color:<?=$col?>"><?=$p['stock']?></span></div></td>
                <td>$<?=number_format($p['stock']*$p['precio'],0,',','.')?></td>
                <td><?php if($p['stock']==0):?><span class="badge badge-danger">Agotado</span><?php elseif($p['stock']<=5):?><span class="badge badge-warning">Stock Bajo</span><?php else:?><span class="badge badge-success">Óptimo</span><?php endif;?></td>
              </tr>
              <?php endforeach;?>
            </tbody>
            <tfoot><tr style="background:var(--bg-panel)"><td colspan="5" style="text-align:right;font-weight:600;font-size:.8rem;color:var(--white-muted)">VALOR TOTAL:</td><td style="color:var(--gold-light);font-weight:700">$<?=number_format($totalValor,0,',','.')?></td><td></td></tr></tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<script src="<?=BASE_URL?>/assets/js/app.js"></script>
<script>
let vistaCards = true;

function toggleVista() {
  vistaCards = !vistaCards;
  document.getElementById('vista-tallas').style.display = vistaCards ? 'block' : 'none';
  document.getElementById('vista-tabla').style.display  = vistaCards ? 'none'  : 'block';
  document.getElementById('btn-vista').textContent = vistaCards ? 'Ver tabla simple' : 'Ver por tallas';
}

function buscarInv(q) {
  q = q.toLowerCase();
  document.querySelectorAll('.inv-card').forEach(c => {
    c.style.display = c.dataset.nombre.includes(q) ? '' : 'none';
  });
}

function filtrarEstado(f, btn) {
  document.querySelectorAll('.layout .btn-outline.btn-sm').forEach(b => { b.style.borderColor=''; b.style.color=''; });
  btn.style.borderColor = 'var(--gold)'; btn.style.color = 'var(--gold-light)';
  document.querySelectorAll('.inv-card').forEach(c => {
    if(f==='all') c.style.display='';
    else if(f==='agotado') c.style.display = c.dataset.estado==='agotado'?'':'none';
    else if(f==='bajo') c.style.display = c.dataset.estado==='bajo'?'':'none';
    else c.style.display = '';
  });
  document.querySelectorAll('#tbl-inv tbody tr').forEach(r => {
    if(f==='all') r.style.display='';
    else if(f==='agotado') r.style.display = r.dataset.estado==='agotado'?'':'none';
    else if(f==='bajo') r.style.display = r.dataset.estado==='bajo'?'':'none';
    else r.style.display = r.dataset.cat===f?'':'none';
  });
}

const mt=document.getElementById('menu-toggle'),sb=document.getElementById('sidebar');
if(mt&&sb)mt.addEventListener('click',()=>sb.classList.toggle('open'));
</script>
</body>
</html>
