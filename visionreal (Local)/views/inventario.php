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

// Función para calcular estado (misma lógica que API)
function calcularEstadoProducto($p) {
  $stock_total = $p['stock_general'] ?? 0;
  $estado = 'ok';
  $descripcion_estado = '';

  if (!empty($p['tallas'])) {
    $tallas_agotadas = 0;
    $tallas_bajo_stock = 0;
    $tallas_con_stock = 0;

    foreach($p['tallas'] as $t) {
      if ($t['stock'] == 0) {
        $tallas_agotadas++;
      } elseif ($t['stock'] <= 5) {
        $tallas_bajo_stock++;
      } else {
        $tallas_con_stock++;
      }
    }

    $total_tallas = count($p['tallas']);

    // Lógica mejorada de estados:
    if ($tallas_agotadas == $total_tallas) {
      $estado = 'agotado';
      $descripcion_estado = 'Producto agotado';
    } elseif ($tallas_bajo_stock > 0 || $tallas_agotadas > 0) {
      $estado = 'bajo';
      $partes = [];
      if ($tallas_bajo_stock > 0) {
        $partes[] = $tallas_bajo_stock == 1 ? '1 talla con poco stock' : $tallas_bajo_stock . ' tallas con poco stock';
      }
      if ($tallas_agotadas > 0) {
        $partes[] = $tallas_agotadas == 1 ? '1 talla agotada' : $tallas_agotadas . ' tallas agotadas';
      }
      $descripcion_estado = implode(', ', $partes);
    } else {
      $estado = 'ok';
      $descripcion_estado = 'Stock disponible';
    }

    $p['tallas_agotadas'] = $tallas_agotadas;
    $p['tallas_bajo_stock'] = $tallas_bajo_stock;
    $p['tallas_con_stock'] = $tallas_con_stock;
  } else {
    if ($stock_total == 0) {
      $estado = 'agotado';
      $descripcion_estado = 'Producto agotado';
    } elseif ($stock_total <= 5) {
      $estado = 'bajo';
      $descripcion_estado = 'Stock bajo';
    } else {
      $estado = 'ok';
      $descripcion_estado = 'Stock disponible';
    }
  }

  $p['estado'] = $estado;
  $p['descripcion_estado'] = $descripcion_estado;
  return $p;
}

// Enriquecer inventario
$inventarioEnriquecido = [];
foreach($inventario as $p) {
  $inventarioEnriquecido[] = calcularEstadoProducto($p);
}

$totalStock = array_sum(array_column($productos,'stock'));
$totalValor = array_sum(array_map(fn($p)=>$p['stock']*$p['precio'],$productos));
$agotados   = count(array_filter($inventarioEnriquecido,fn($p)=>$p['estado']=='agotado'));
$stockBajo  = count(array_filter($inventarioEnriquecido,fn($p)=>$p['estado']=='bajo'));
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

    <!-- FILTROS Y BÚSQUEDA -->
    <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:18px">
      <!-- Buscador -->
      <div class="search-input-wrap" style="max-width:400px">
        <span class="search-icon">🔍</span>
        <input type="text" id="buscar-general" placeholder="Buscar por nombre, código o categoría..." style="padding:8px 12px">
      </div>
      
      <!-- Filtros por estado -->
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <span style="font-size:.75rem;text-transform:uppercase;letter-spacing:.1em;color:var(--white-muted)">Estado:</span>
        <button class="btn btn-outline btn-sm" data-filter="all" style="border-color:var(--gold);color:var(--gold-light)">Todos</button>
        <button class="btn btn-outline btn-sm" data-filter="agotado"><span style="color:var(--danger)">●</span> Agotados</button>
        <button class="btn btn-outline btn-sm" data-filter="bajo"><span style="color:var(--warning)">●</span> Stock Bajo</button>
        <button class="btn btn-outline btn-sm" data-filter="ok"><span style="color:var(--success)">●</span> Óptimo</button>
      </div>

      <!-- Filtros por categoría -->
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <span style="font-size:.75rem;text-transform:uppercase;letter-spacing:.1em;color:var(--white-muted)">Categoría:</span>
        <button class="btn btn-outline btn-sm" data-filter="cat-all">Todas</button>
        <?php foreach($categorias as $cat):?>
        <button class="btn btn-outline btn-sm" data-filter="cat-<?=$cat['id']?>"><?=htmlspecialchars($cat['nombre'])?></button>
        <?php endforeach;?>
      </div>
    </div>

    <!-- VISTA CON TALLAS (card por producto) -->
    <div id="vista-tallas" style="margin-bottom:24px">
      <div class="card">
        <div class="card-header">
          <span class="card-title">📦 Stock por Producto y Talla</span>
          <button class="btn btn-outline btn-sm" onclick="toggleVista()" id="btn-vista">Ver tabla simple</button>
        </div>
        <div style="padding:16px 20px">
          <div id="cards-tallas" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px">
            <?php foreach($inventarioEnriquecido as $p):
              $totalTallas = array_sum(array_column($p['tallas'],'stock'));
            ?>
            <div class="inv-card" data-nombre="<?=htmlspecialchars(strtolower($p['nombre']),ENT_QUOTES)?>" data-codigo="<?=htmlspecialchars(strtolower($p['codigo']),ENT_QUOTES)?>" data-categoria="<?=htmlspecialchars(strtolower($p['categoria_nombre']?:''),ENT_QUOTES)?>" data-estado="<?=$p['estado']?>" data-cat="cat-<?=$p['categoria_id']?>"
              style="background:var(--bg-panel);border:1px solid var(--border);border-radius:10px;overflow:hidden">
              <div style="padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
                <div style="flex:1">
                  <div style="font-weight:600;font-size:.85rem;color:var(--gold-light)"><?=htmlspecialchars($p['codigo'])?></div>
                  <div style="font-weight:600;font-size:.9rem;color:var(--white);margin-top:4px"><?=htmlspecialchars($p['nombre'])?></div>
                  <div style="font-size:.72rem;color:var(--white-muted);margin-top:2px"><?=htmlspecialchars($p['categoria_nombre']??'—')?> · $<?=number_format($p['precio'],0,',','.')?></div>
                </div>
                <div style="text-align:right;margin-left:12px">
                  <?php if($p['estado']=='agotado'):?>
                    <div style="background:var(--danger);color:white;padding:6px 12px;border-radius:6px;font-weight:700;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;text-align:center;min-width:80px">AGOTADO</div>
                  <?php elseif($p['estado']=='bajo'):?>
                    <div style="background:var(--warning);color:var(--bg-dark);padding:6px 12px;border-radius:6px;font-weight:700;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;text-align:center;min-width:80px">BAJO STOCK</div>
                  <?php else:?>
                    <div style="background:var(--success);color:white;padding:6px 12px;border-radius:6px;font-weight:700;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;text-align:center;min-width:80px">OK</div>
                  <?php endif;?>
                </div>
              </div>

              <?php if(empty($p['tallas'])): ?>
              <div style="padding:14px 16px;font-size:.8rem;color:var(--white-muted)">
                Stock general: <strong style="color:var(--white)"><?=$p['stock_general']?></strong> unidades · Sin tallas configuradas
                <div style="margin-top:8px;font-size:.75rem;color:<?=$p['estado']=='agotado'?'var(--danger)':($p['estado']=='bajo'?'var(--warning)':'var(--success)')?>;font-weight:600">
                  <?=$p['descripcion_estado']?>
                </div>
              </div>
              <?php else: ?>
              <div style="padding:12px 16px">
                <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.1em;color:var(--white-muted);margin-bottom:10px;font-weight:600">
                  TALLAS — <?=count($p['tallas'])?> tipos · <?=$totalTallas?> unidades totales
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:8px">
                  <?php foreach($p['tallas'] as $t): ?>
                  <div class="talla-card" data-talla-id="<?=$t['id']?>" data-producto-id="<?=$p['id']?>" style="display:flex;flex-direction:column;align-items:center;min-width:62px;padding:8px 10px;border-radius:8px;border:2px solid;position:relative;<?=
                    $t['stock']==0  ? 'border-color:var(--danger);background:rgba(192,57,43,.1)' :
                    ($t['stock']<=5 ? 'border-color:var(--warning);background:rgba(230,126,34,.1)' :
                                      'border-color:var(--success);background:rgba(46,204,113,.1)')
                  ?>">
                    <span style="font-size:.95rem;font-weight:800;color:var(--white);font-family:var(--font-display)"><?=htmlspecialchars($t['talla'])?></span>
                    <span class="talla-stock-value" style="font-size:.7rem;font-weight:700;color:<?=$t['stock']==0?'var(--danger)':($t['stock']<=5?'var(--warning)':'var(--success)')?>;margin-top:3px"><?=$t['stock']?> uds</span>
                    <?php if($t['stock']==0):?>
                      <span style="font-size:.6rem;color:var(--danger);font-weight:700;text-transform:uppercase">AGOTADA</span>
                    <?php elseif($t['stock']<=5):?>
                      <span style="font-size:.6rem;color:var(--warning);font-weight:700;text-transform:uppercase">BAJO</span>
                    <?php else:?>
                      <span style="font-size:.6rem;color:var(--success);font-weight:700;text-transform:uppercase">OK</span>
                    <?php endif;?>
                    <!-- Botones de edición (se muestran al pasar el mouse) -->
                    <?php if(isAdmin()):?>
                    <div class="talla-edit-controls" style="display:none;position:absolute;top:-8px;right:-8px;background:var(--bg-dark);border:1px solid var(--border);border-radius:4px;padding:2px;gap:2px;display:flex;flex-direction:column;z-index:10">
                      <button class="talla-btn-add" data-talla-id="<?=$t['id']?>" data-producto-id="<?=$p['id']?>" style="width:18px;height:18px;padding:0;font-size:.6rem;cursor:pointer;background:var(--success);border:none;border-radius:2px;color:white;font-weight:bold;display:flex;align-items:center;justify-content:center">+</button>
                      <button class="talla-btn-remove" data-talla-id="<?=$t['id']?>" data-producto-id="<?=$p['id']?>" style="width:18px;height:18px;padding:0;font-size:.6rem;cursor:pointer;background:var(--danger);border:none;border-radius:2px;color:white;font-weight:bold;display:flex;align-items:center;justify-content:center">−</button>
                    </div>
                    <?php endif;?>
                  </div>
                  <?php endforeach; ?>
                </div>
                <div style="margin-top:12px;padding-top:10px;border-top:1px solid var(--border);font-size:.75rem;color:<?=$p['estado']=='agotado'?'var(--danger)':($p['estado']=='bajo'?'var(--warning)':'var(--success)')?>;font-weight:600;text-align:center">
                  <?=$p['descripcion_estado']?>
                </div>
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
            <thead><tr><th>Código</th><th>Producto</th><th>Categoría</th><th>Precio</th><th>Stock Total</th><th>Valor</th><th>Estado</th><th>Tallas</th></tr></thead>
            <tbody>
              <?php $maxSt=max(1,...array_column($inventarioEnriquecido,'stock_general')); foreach($inventarioEnriquecido as $p):
                $pct=min(100,($p['stock_general']/$maxSt)*100);
                $col=$p['estado']=='agotado'?'var(--danger)':($p['estado']=='bajo'?'var(--warning)':'var(--success)');
                $tallasInfo = !empty($p['tallas']) ? implode(', ', array_map(fn($t) => $t['talla'].' ('.$t['stock'].')', $p['tallas'])) : 'N/A';
              ?>
              <tr data-estado="<?=$p['estado']?>" data-cat="cat-<?=$p['categoria_id']?>" data-nombre="<?=htmlspecialchars(strtolower($p['nombre']),ENT_QUOTES)?>" data-codigo="<?=htmlspecialchars(strtolower($p['codigo']),ENT_QUOTES)?>" data-categoria="<?=htmlspecialchars(strtolower($p['categoria_nombre']?:''),ENT_QUOTES)?>">
                <td style="color:var(--gold-light);font-weight:600;font-size:.85rem"><?=htmlspecialchars($p['codigo'])?></td>
                <td><strong><?=htmlspecialchars($p['nombre'])?></strong></td>
                <td><span class="badge badge-info"><?=htmlspecialchars($p['categoria_nombre']??'—')?></span></td>
                <td style="color:var(--gold-light);font-weight:500">$<?=number_format($p['precio'],0,',','.')?></td>
                <td><div style="display:flex;align-items:center;gap:10px"><div style="width:80px;height:6px;background:var(--bg-hover);border-radius:3px;overflow:hidden"><div style="height:100%;width:<?=$pct?>%;background:<?=$col?>;border-radius:3px"></div></div><span style="font-weight:600;color:<?=$col?>"><?=$p['stock_general']?></span></div></td>
                <td>$<?=number_format($p['stock_general']*$p['precio'],0,',','.')?></td>
                <td><?php if($p['estado']=='agotado'):?><span style="background:var(--danger);color:white;padding:4px 8px;border-radius:4px;font-weight:700;font-size:.7rem;text-transform:uppercase">AGOTADO</span><?php elseif($p['estado']=='bajo'):?><span style="background:var(--warning);color:var(--bg-dark);padding:4px 8px;border-radius:4px;font-weight:700;font-size:.7rem;text-transform:uppercase">BAJO STOCK</span><?php else:?><span style="background:var(--success);color:white;padding:4px 8px;border-radius:4px;font-weight:700;font-size:.7rem;text-transform:uppercase">OK</span><?php endif;?></td>
                <td style="font-size:.8rem;color:var(--white-muted)"><?=htmlspecialchars($tallasInfo)?></td>
              </tr>
              <?php endforeach;?>
            </tbody>
            <tfoot><tr style="background:var(--bg-panel)"><td colspan="5" style="text-align:right;font-weight:600;font-size:.8rem;color:var(--white-muted)">VALOR TOTAL:</td><td style="color:var(--gold-light);font-weight:700">$<?=number_format($totalValor,0,',','.')?></td><td colspan="2"></td></tr></tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<style>
.talla-card {
  transition: all 0.15s ease;
  cursor: default;
}

.talla-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.talla-card:hover .talla-edit-controls {
  display: flex !important;
}

.talla-btn-add, .talla-btn-remove {
  transition: all 0.1s ease;
}

.talla-btn-add:hover {
  background: var(--success-dark) !important;
  transform: scale(1.1);
}

.talla-btn-remove:hover {
  background: var(--danger-dark) !important;
  transform: scale(1.1);
}

.talla-actualizando {
  opacity: 0.7;
  pointer-events: none;
}
</style>

<script>
// ============================================
// MÓDULO DE INVENTARIO - Sistema de filtros, búsqueda y edición
// ============================================

let vistaCards = true;
let filtroEstadoActual = 'all';
let filtroCategoriaActual = 'cat-all';
let terminoBusqueda = '';

const InventarioModule = {
  init() {
    this.setupEventListeners();
    this.aplicarFiltros();
    this.setupEditoresInline();
  },

  setupEventListeners() {
    // Buscador general
    const inputBusqueda = document.getElementById('buscar-general');
    if (inputBusqueda) {
      inputBusqueda.addEventListener('input', (e) => {
        terminoBusqueda = e.target.value.toLowerCase().trim();
        this.aplicarFiltros();
      });
    }

    // Botones de filtro
    document.querySelectorAll('.layout [data-filter]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const filtro = e.target.dataset.filter;

        if (filtro.startsWith('cat-')) {
          filtroCategoriaActual = filtro;
          document.querySelectorAll('[data-filter^="cat-"]').forEach(b => {
            b.style.borderColor = '';
            b.style.color = '';
          });
        } else {
          filtroEstadoActual = filtro;
          document.querySelectorAll('[data-filter]:not([data-filter^="cat-"])').forEach(b => {
            b.style.borderColor = '';
            b.style.color = '';
          });
        }

        e.target.style.borderColor = 'var(--gold)';
        e.target.style.color = 'var(--gold-light)';
        this.aplicarFiltros();
      });
    });

    // Establecer filtro inicial
    document.querySelector('[data-filter="all"]').click();
  },

  setupEditoresInline() {
    // Botón + para agregar stock
    document.querySelectorAll('.talla-btn-add').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const tallaId = btn.dataset.tallaId;
        const productoId = btn.dataset.productoId;
        const tallaCard = btn.closest('.talla-card');
        const stockSpan = tallaCard.querySelector('.talla-stock-value');
        const stockActual = parseInt(stockSpan.textContent);
        this.actualizarStock(tallaId, productoId, stockActual + 1, tallaCard);
      });
    });

    // Botón - para restar stock
    document.querySelectorAll('.talla-btn-remove').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const tallaId = btn.dataset.tallaId;
        const productoId = btn.dataset.productoId;
        const tallaCard = btn.closest('.talla-card');
        const stockSpan = tallaCard.querySelector('.talla-stock-value');
        const stockActual = parseInt(stockSpan.textContent);
        if (stockActual > 0) {
          this.actualizarStock(tallaId, productoId, stockActual - 1, tallaCard);
        }
      });
    });
  },

  actualizarStock(tallaId, productoId, nuevoStock, tallaCard) {
    tallaCard.classList.add('talla-actualizando');

    const formData = new FormData();
    formData.append('action', 'actualizar');
    formData.append('id', tallaId);
    formData.append('producto_id', productoId);
    formData.append('stock', nuevoStock);

    fetch('<?=BASE_URL?>/controllers/TallaController.php', {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        this.actualizarVisualizacionCard(productoId, tallaCard, data, nuevoStock);
      } else {
        alert('Error: ' + data.error);
      }
      tallaCard.classList.remove('talla-actualizando');
    })
    .catch(err => {
      console.error('Error:', err);
      alert('Error al actualizar stock');
      tallaCard.classList.remove('talla-actualizando');
    });
  },

  actualizarVisualizacionCard(productoId, tallaCard, respuesta, nuevoStock) {
    // Actualizar el valor de stock en la talla
    const stockSpan = tallaCard.querySelector('.talla-stock-value');
    const viejo = parseInt(stockSpan.textContent);
    stockSpan.textContent = nuevoStock + ' uds';

    // Cambiar color según nuevo stock
    tallaCard.style.borderColor = nuevoStock === 0 ? 'var(--danger)' :
                                   (nuevoStock <= 5 ? 'var(--warning)' : 'var(--success)');
    tallaCard.style.background = nuevoStock === 0 ? 'rgba(192,57,43,.1)' :
                                  (nuevoStock <= 5 ? 'rgba(230,126,34,.1)' : 'rgba(46,204,113,.1)');

    // Actualizar el badge de estado de la talla
    const badgeSpan = Array.from(tallaCard.querySelectorAll('span')).find(s =>
      s.textContent === 'AGOTADA' || s.textContent === 'BAJO' || s.textContent === 'OK'
    );
    if (badgeSpan) {
      if (nuevoStock === 0) {
        badgeSpan.textContent = 'AGOTADA';
        badgeSpan.style.color = 'var(--danger)';
      } else if (nuevoStock <= 5) {
        badgeSpan.textContent = 'BAJO';
        badgeSpan.style.color = 'var(--warning)';
      } else {
        badgeSpan.textContent = 'OK';
        badgeSpan.style.color = 'var(--success)';
      }
    }

    // Actualizar el badge de estado del producto (la card principal)
    const productCard = tallaCard.closest('.inv-card');
    if (productCard) {
      const badgeProducto = productCard.querySelector('[style*="padding:6px 12px"]');
      if (badgeProducto && respuesta.estado && respuesta.descripcion) {
        const estadoColor = respuesta.estado === 'agotado' ? 'var(--danger)' :
                            (respuesta.estado === 'bajo' ? 'var(--warning)' : 'var(--success)');
        const estadoText = respuesta.estado === 'agotado' ? 'AGOTADO' :
                           (respuesta.estado === 'bajo' ? 'BAJO STOCK' : 'OK');

        badgeProducto.style.background = estadoColor;
        badgeProducto.textContent = estadoText;

        // Actualizar descripción de estado
        const descripcionSpan = productCard.querySelector('[style*="font-weight:600;text-align:center"]');
        if (descripcionSpan) {
          descripcionSpan.textContent = respuesta.descripcion;
          descripcionSpan.style.color = estadoColor;
        }
      }

      // Actualizar dataset para filtros
      productCard.dataset.estado = respuesta.estado;
      this.aplicarFiltros();
    }
  },

  aplicarFiltros() {
    const busquedaLower = terminoBusqueda;
    const enVistaTarjetas = vistaCards;

    if (enVistaTarjetas) {
      document.querySelectorAll('.inv-card').forEach(card => {
        const coincideBusqueda = this.coincideBusqueda(
          card.dataset.nombre,
          card.dataset.codigo,
          card.dataset.categoria,
          busquedaLower
        );
        const coincideEstado = filtroEstadoActual === 'all' || card.dataset.estado === filtroEstadoActual;
        const coincideCategoria = filtroCategoriaActual === 'cat-all' || card.dataset.cat === filtroCategoriaActual;

        card.style.display = (coincideBusqueda && coincideEstado && coincideCategoria) ? '' : 'none';
      });
    } else {
      document.querySelectorAll('#tbl-inv tbody tr').forEach(row => {
        const coincideBusqueda = this.coincideBusqueda(
          row.dataset.nombre,
          row.dataset.codigo,
          row.dataset.categoria,
          busquedaLower
        );
        const coincideEstado = filtroEstadoActual === 'all' || row.dataset.estado === filtroEstadoActual;
        const coincideCategoria = filtroCategoriaActual === 'cat-all' || row.dataset.cat === filtroCategoriaActual;

        row.style.display = (coincideBusqueda && coincideEstado && coincideCategoria) ? '' : 'none';
      });
    }
  },

  coincideBusqueda(nombre, codigo, categoria, termino) {
    if (!termino) return true;
    return nombre.includes(termino) || codigo.includes(termino) || categoria.includes(termino);
  }
};

function toggleVista() {
  vistaCards = !vistaCards;
  document.getElementById('vista-tallas').style.display = vistaCards ? 'block' : 'none';
  document.getElementById('vista-tabla').style.display = vistaCards ? 'none' : 'block';
  document.getElementById('btn-vista').textContent = vistaCards ? 'Ver tabla simple' : 'Ver por tallas';
  InventarioModule.aplicarFiltros();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => InventarioModule.init());
} else {
  InventarioModule.init();
}

</script>
</body>
</html>
