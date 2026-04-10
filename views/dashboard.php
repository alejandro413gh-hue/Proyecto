<?php
$pageTitle='Dashboard';
require_once __DIR__ . '/../config/config.php';
requireLogin();
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/Venta.php';

$pm=new Producto();$cm=new Cliente();$vm=new Venta();
$totalProductos=$pm->countAll();$totalClientes=$cm->countAll();
$totalHoy=$vm->getTotalHoy();$totalMes=$vm->getTotalMes();
$lowStock=$pm->getLowStock(5);$ventasRecientes=$vm->getAll(8);
$topProductos=$vm->getTopProductos(5);$ventasDia=$vm->getVentasPorDia(7);
include __DIR__ . '/partials/head.php';
?>
<div class="layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" id="menu-toggle">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <h1 class="page-title">Dashboard</h1>
    </div>
    <div class="topbar-right"><span class="badge-gold"><?=date('d M Y')?></span></div>
  </header>
  <div class="content">
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-label">Ventas Hoy</div><div class="stat-value">$<?=number_format($totalHoy/1000,0)?>k</div><div class="stat-sub">COP <?=number_format($totalHoy,0,',','.')?></div><div class="stat-icon">💰</div></div>
      <div class="stat-card"><div class="stat-label">Ventas del Mes</div><div class="stat-value">$<?=number_format($totalMes/1000,0)?>k</div><div class="stat-sub"><?=date('F Y')?></div><div class="stat-icon">📈</div></div>
      <div class="stat-card"><div class="stat-label">Productos</div><div class="stat-value"><?=$totalProductos?></div><div class="stat-sub"><?=count($lowStock)?> con stock bajo</div><div class="stat-icon">📦</div></div>
      <div class="stat-card"><div class="stat-label">Clientes</div><div class="stat-value"><?=$totalClientes?></div><div class="stat-sub">Registrados</div><div class="stat-icon">👥</div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">
      <div class="card">
        <div class="card-header"><span class="card-title">Ventas — Últimos 7 días</span></div>
        <div class="card-body" style="padding:16px 20px"><canvas id="chartVentas" height="180"></canvas></div>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title">Productos más vendidos</span></div>
        <div class="card-body" style="padding:16px 20px">
          <?php if(empty($topProductos)): ?>
            <p style="color:var(--white-muted);text-align:center;padding:40px 0;font-size:.85rem">Sin ventas aún</p>
          <?php else: foreach($topProductos as $i=>$p): ?>
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
            <div style="width:24px;height:24px;border-radius:50%;background:var(--gold-dim);color:var(--gold);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;flex-shrink:0"><?=$i+1?></div>
            <div style="flex:1">
              <div style="font-size:.82rem;font-weight:500;margin-bottom:3px"><?=htmlspecialchars($p['nombre'])?></div>
              <div style="height:4px;background:var(--bg-hover);border-radius:2px"><div style="height:4px;background:linear-gradient(90deg,var(--gold),var(--gold-light));border-radius:2px;width:<?=min(100,($p['total_vendido']/max(1,$topProductos[0]['total_vendido']))*100)?>%"></div></div>
            </div>
            <div style="font-size:.78rem;color:var(--gold-light);font-weight:500"><?=$p['total_vendido']?> uds</div>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
      <div class="card">
        <div class="card-header"><span class="card-title">Ventas Recientes</span><a href="<?=BASE_URL?>/views/ventas.php" class="btn btn-outline btn-sm">Ver todas</a></div>
        <div class="table-wrap">
          <table><thead><tr><th>#</th><th>Cliente</th><th>Vendedor</th><th>Total</th><th>Fecha</th><th>Estado</th></tr></thead>
          <tbody>
            <?php if(empty($ventasRecientes)): ?>
            <tr><td colspan="6" class="table-empty">Sin ventas registradas</td></tr>
            <?php else: foreach($ventasRecientes as $v): ?>
            <tr>
              <td><span style="color:var(--gold-light);font-weight:500">#<?=$v['id']?></span></td>
              <td><strong><?=htmlspecialchars($v['cliente_nombre']??'Cliente General')?></strong></td>
              <td><?=htmlspecialchars($v['vendedor_nombre'])?></td>
              <td style="color:var(--gold-light);font-weight:600">$<?=number_format($v['total'],0,',','.')?></td>
              <td><?=date('d/m/Y H:i',strtotime($v['fecha']))?></td>
              <td><span class="badge badge-success"><?=$v['estado']?></span></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody></table>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title">⚠ Stock Bajo</span><a href="<?=BASE_URL?>/views/inventario.php" class="btn btn-outline btn-sm">Ver</a></div>
        <div class="card-body" style="padding:12px 16px">
          <?php if(empty($lowStock)): ?>
            <p style="color:var(--white-muted);text-align:center;padding:30px 0;font-size:.85rem">✓ Inventario OK</p>
          <?php else: foreach($lowStock as $p): ?>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border)">
            <div><div style="font-size:.82rem;font-weight:500"><?=htmlspecialchars($p['nombre'])?></div><div style="font-size:.7rem;color:var(--white-muted)"><?=htmlspecialchars($p['categoria_nombre']??'—')?></div></div>
            <span class="badge <?=$p['stock']==0?'badge-danger':'badge-warning'?>"><?=$p['stock']?> uds</span>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>
  </div>
</div></div>
<script>window.BASE_URL='<?=BASE_URL?>';</script>
<script src="<?=BASE_URL?>/assets/js/app.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const vd=<?=json_encode($ventasDia)?>;
const labels=[],data=[];
for(let i=6;i>=0;i--){const d=new Date();d.setDate(d.getDate()-i);const k=d.toISOString().split('T')[0];labels.push(d.toLocaleDateString('es-CO',{weekday:'short',day:'numeric'}));const f=vd.find(v=>v.dia===k);data.push(f?parseFloat(f.total):0);}
const ctx=document.getElementById('chartVentas');
if(ctx){new Chart(ctx,{type:'bar',data:{labels,datasets:[{label:'COP',data,backgroundColor:'rgba(201,168,76,0.25)',borderColor:'#c9a84c',borderWidth:2,borderRadius:6,borderSkipped:false}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'rgba(244,242,238,0.5)',font:{size:11}}},y:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'rgba(244,242,238,0.5)',font:{size:11},callback:v=>'$'+(v/1000).toFixed(0)+'k'}}}}})}
</script>
</body></html>
