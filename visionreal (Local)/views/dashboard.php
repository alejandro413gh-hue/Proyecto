<?php
$pageTitle='Dashboard';
require_once __DIR__ . '/../config/config.php';
requireLogin();
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../models/tienda/Pedido.php';
require_once __DIR__ . '/../models/TiendaConfig.php';

$pm=new Producto();$cm=new Cliente();$vm=new Venta();$pedidoTm=new Pedido();
$tiendaCfg = (new TiendaConfig())->getAll();
$totalProductos=$pm->countAll();$totalClientes=$cm->countAll();
$totalHoy=$vm->getTotalHoy();$totalMes=$vm->getTotalMes();
$lowStock=$pm->getLowStock(5);$ventasRecientes=$vm->getAll(8);
$topProductos=$vm->getTopProductos(5);$ventasDia=$vm->getVentasPorDia(7);
$pedidosPendientes=($pedidoTm->countPorEstado()['pendiente'] ?? 0);
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

    <div class="card" style="margin-bottom:24px;padding:18px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
      <div>
        <div class="card-title" style="margin-bottom:6px">Telegram Bot</div>
          
        <div style="color:var(--white-muted);font-size:.86rem;line-height:1.5">
          Envía un reporte con productos escasos, pedidos pendientes y pedidos para revisión al grupo configurado en Telegram.
        </div>
        <div style="color:var(--white-dim);font-size:.8rem;margin-top:8px">
          Stock bajo: <strong><?=count($lowStock)?></strong> | Pedidos pendientes: <strong><?=$pedidosPendientes?></strong>
        </div>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end">
        <button class="btn btn-outline btn-sm" type="button" onclick="enviarReporteTelegram(this)">
          Enviar reporte ahora
        </button>
        <button class="btn btn-sm" type="button" onclick="generarAnalisisIA(this)" style="background:linear-gradient(135deg,var(--gold),var(--gold-light));color:var(--black);font-weight:600">
          Generar análisis con IA
        </button>
      </div>
    </div>

    <div class="card" style="margin-bottom:24px;padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap">
        <div>
          <div class="card-title" style="margin-bottom:6px">Información de la Tienda</div>
          <div style="color:var(--white-muted);font-size:.86rem;line-height:1.5;max-width:720px">
            Centraliza el WhatsApp, el correo, la dirección y la ubicación del negocio desde un solo lugar. La tienda pública toma estos datos automáticamente.
          </div>
        </div>
        <a href="<?=BASE_URL?>/views/informacion_tienda.php" class="btn btn-outline btn-sm">Administrar información</a>
      </div>
      <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:16px">
        <div style="background:var(--bg-hover);padding:12px 14px;border-radius:12px">
          <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:var(--white-muted);margin-bottom:4px">WhatsApp</div>
          <div style="font-weight:600"><?=htmlspecialchars($tiendaCfg['whatsapp_number'] ?? '')?></div>
        </div>
        <div style="background:var(--bg-hover);padding:12px 14px;border-radius:12px">
          <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:var(--white-muted);margin-bottom:4px">Correo</div>
          <div style="font-weight:600;word-break:break-word"><?=htmlspecialchars($tiendaCfg['support_email'] ?? '')?></div>
        </div>
        <div style="background:var(--bg-hover);padding:12px 14px;border-radius:12px">
          <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:var(--white-muted);margin-bottom:4px">Dirección</div>
          <div style="font-weight:600"><?=htmlspecialchars($tiendaCfg['physical_address'] ?? 'Sin dirección')?></div>
        </div>
        <div style="background:var(--bg-hover);padding:12px 14px;border-radius:12px">
          <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:var(--white-muted);margin-bottom:4px">Ubicación</div>
          <div style="font-weight:600"><?=(!empty($tiendaCfg['latitude']) && !empty($tiendaCfg['longitude'])) ? 'Coordenadas activas' : 'Pendiente'?></div>
        </div>
      </div>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const vd=<?=json_encode($ventasDia)?>;
const labels=[],data=[];
for(let i=6;i>=0;i--){const d=new Date();d.setDate(d.getDate()-i);const k=d.toISOString().split('T')[0];labels.push(d.toLocaleDateString('es-CO',{weekday:'short',day:'numeric'}));const f=vd.find(v=>v.dia===k);data.push(f?parseFloat(f.total):0);}
const ctx=document.getElementById('chartVentas');
if(ctx){new Chart(ctx,{type:'bar',data:{labels,datasets:[{label:'COP',data,backgroundColor:'rgba(201,168,76,0.25)',borderColor:'#c9a84c',borderWidth:2,borderRadius:6,borderSkipped:false}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'rgba(244,242,238,0.5)',font:{size:11}}},y:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'rgba(244,242,238,0.5)',font:{size:11},callback:v=>'$'+(v/1000).toFixed(0)+'k'}}}}})}

async function enviarReporteTelegram(btn) {
  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Enviando...';
  }
  try {
    const r = await fetch('<?=BASE_URL?>/api/telegram/reportes.php?action=ambos&token=<?=urlencode(TELEGRAM_REPORT_SECRET)?>', { credentials: 'same-origin' });
    const data = await r.json();
    if (data.success) {
      if (typeof showToast === 'function') {
        showToast('Reporte enviado a Telegram.', 'success');
      } else {
        alert('Reporte enviado a Telegram.');
      }
    } else {
      const msg = data.error || 'No se pudo enviar el reporte.';
      if (typeof showToast === 'function') {
        showToast(msg, 'error');
      } else {
        alert(msg);
      }
    }
  } catch (e) {
    if (typeof showToast === 'function') {
      showToast('No se pudo enviar el reporte.', 'error');
    } else {
      alert('No se pudo enviar el reporte.');
    }
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.textContent = 'Enviar reporte ahora';
    }
  }
}

async function generarAnalisisIA(btn) {
  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Generando...';
  }
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 70000);
  try {
    const r = await fetch('<?=BASE_URL?>/api/telegram/reportes.php?action=ia&token=<?=urlencode(TELEGRAM_REPORT_SECRET)?>', { credentials: 'same-origin', signal: controller.signal });
    const data = await r.json();
    if (data.success) {
      const telegramOk = !data.telegram || data.telegram.success !== false;
      const message = telegramOk
        ? 'Análisis con IA generado y enviado a Telegram.'
        : 'El análisis se generó, pero Telegram reportó un problema al enviarlo.';
      if (typeof showToast === 'function') {
        showToast(message, telegramOk ? 'success' : 'error');
      } else {
        alert(message);
      }
    } else {
      const msg = data.error || 'No se pudo generar el análisis con IA.';
      if (typeof showToast === 'function') {
        showToast(msg, 'error');
      } else {
        alert(msg);
      }
    }
  } catch (e) {
    if (typeof showToast === 'function') {
      showToast('No se pudo generar el análisis con IA.', 'error');
    } else {
      alert('No se pudo generar el análisis con IA.');
    }
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.textContent = 'Generar análisis con IA';
    }
  }
}
</script>
</body></html>
