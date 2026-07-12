<?php
/**
 * views/pedidos_online.php
 * Panel interno de pedidos online para vendedor, bodeguero y administrador.
 * Se integra al sistema existente (sidebar, layout, sesión).
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_error_handler(function($severity, $message, $file, $line) {
    echo "<pre style='color:red;font-size:0.9rem;'>PHP Error: {$message} in {$file} on line {$line}</pre>";
    return false;
});
set_exception_handler(function($exception) {
    echo "<pre style='color:red;font-size:0.9rem;'>Uncaught Exception: " . $exception->getMessage()
         . " in " . $exception->getFile() . " on line " . $exception->getLine() . "</pre>";
});
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null) {
        echo "<pre style='color:red;font-size:0.9rem;'>Shutdown error: {$error['message']} in {$error['file']} on line {$error['line']}</pre>";
    }
});

$pageTitle = 'Pedidos Online';
require_once __DIR__ . '/../config/config.php';
requireLogin();
require_once __DIR__ . '/../models/tienda/Pedido.php';
require_once __DIR__ . '/../models/tienda/ProductoTienda.php';

$pedidoM = new Pedido();
$ptM     = new ProductoTienda();

$filtroEstado = $_GET['estado'] ?? '';
$pedidos      = $pedidoM->getAllAdmin($filtroEstado, 100);
$statsPorEstado = $pedidoM->countPorEstado();
$statsVentas  = $ptM->statsVentasPorTipo();
$totalHoy     = $pedidoM->getTotalOnlineHoy();

include __DIR__ . '/partials/head.php';
?>

<div class="layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main">

  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" id="menu-toggle">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>
      <h1 class="page-title">Pedidos Online</h1>
    </div>
    <div class="topbar-right">
      <span class="badge-gold"><?= date('d M Y') ?></span>
    </div>
  </header>

  <div class="content">

    <!-- ── Stats resumen ── -->
    <div class="stats-grid" style="margin-bottom:24px">
      <div class="stat-card">
        <div class="stat-label">Online Hoy</div>
        <div class="stat-value">$<?= number_format($totalHoy/1000, 0) ?>k</div>
        <div class="stat-sub">COP <?= number_format($totalHoy, 0, ',', '.') ?></div>
        <div class="stat-icon">🌐</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Pendientes</div>
        <div class="stat-value" style="color:#E65100"><?= $statsPorEstado['pendiente'] ?? 0 ?></div>
        <div class="stat-sub">Esperando confirmación</div>
        <div class="stat-icon">⏳</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Ventas Físicas</div>
        <div class="stat-value">$<?= number_format(($statsVentas['fisica']['monto'] ?? 0)/1000, 0) ?>k</div>
        <div class="stat-sub"><?= $statsVentas['fisica']['ventas'] ?? 0 ?> ventas</div>
        <div class="stat-icon">🏪</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Ventas Online</div>
        <div class="stat-value">$<?= number_format(($statsVentas['online']['monto'] ?? 0)/1000, 0) ?>k</div>
        <div class="stat-sub"><?= $statsVentas['online']['ventas'] ?? 0 ?> pedidos</div>
        <div class="stat-icon">💻</div>
      </div>
    </div>

    <!-- ── Filtros por estado ── -->
    <div class="card" style="margin-bottom:20px">
      <div class="card-body" style="padding:16px 20px">
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
          <span style="font-size:.82rem;color:var(--white-muted);margin-right:4px">Filtrar:</span>
          <?php
          $estados = ['' => 'Todos', 'pendiente' => 'Pendientes', 'pagado' => 'Pagados',
                      'preparando' => 'Preparando', 'enviado' => 'Enviados',
                      'entregado' => 'Entregados', 'cancelado' => 'Cancelados'];
          foreach ($estados as $val => $label):
            $active = $filtroEstado === $val;
            $cnt    = $val ? ($statsPorEstado[$val] ?? 0) : array_sum($statsPorEstado);
          ?>
          <a href="?estado=<?= $val ?>"
             style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;font-size:.78rem;font-weight:500;text-decoration:none;
                    background:<?= $active ? 'var(--gold)' : 'var(--bg-hover)' ?>;
                    color:<?= $active ? 'var(--black)' : 'var(--white-muted)' ?>;
                    border:1px solid <?= $active ? 'var(--gold)' : 'transparent' ?>">
            <?= $label ?>
            <?php if ($cnt): ?><span style="background:rgba(0,0,0,.2);border-radius:10px;padding:1px 6px"><?= $cnt ?></span><?php endif; ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- ── Tabla de pedidos ── -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">
          <?= $filtroEstado ? ucfirst($filtroEstado) : 'Todos los pedidos' ?>
          (<?= count($pedidos) ?>)
        </span>
        <button onclick="location.reload()" class="btn btn-outline btn-sm">
          ↺ Actualizar
        </button>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Pedido</th>
              <th>Cliente</th>
              <th>Entrega</th>
              <th>Total</th>
              <th>Pago</th>
              <th>Estado</th>
              <th>Fecha</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($pedidos)): ?>
            <tr><td colspan="8" class="table-empty">No hay pedidos<?= $filtroEstado ? " en estado '$filtroEstado'" : '' ?></td></tr>
            <?php else: foreach ($pedidos as $p):
              $detalle = $pedidoM->getDetalle($p['id']);
            ?>
            <tr>
              <td>
                <span style="color:var(--gold-light);font-weight:600"><?= htmlspecialchars($p['numero_pedido']) ?></span>
                <br><small style="color:var(--white-muted)"><?= count($detalle) ?> productos</small>
              </td>
              <td>
                <strong><?= htmlspecialchars($p['cliente_nombre']) ?></strong>
                <br><small style="color:var(--white-muted)"><?= htmlspecialchars($p['cliente_email']) ?></small>
              </td>
              <td>
                <?php if (in_array($p['tipo_entrega'], ['recoge_tienda','recoger_tienda'], true)): ?>
                  <span style="color:var(--gold)">🏪 En tienda</span>
                <?php else: ?>
                  <span>🚚 Domicilio</span>
                  <?php if ($p['envio_ciudad']): ?>
                    <br><small style="color:var(--white-muted)"><?= htmlspecialchars($p['envio_ciudad']) ?></small>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
              <td style="color:var(--gold-light);font-weight:600">
                $<?= number_format($p['total'], 0, ',', '.') ?>
                <?php if ($p['descuento'] > 0): ?>
                  <br><small style="color:#81C784">-$<?= number_format($p['descuento'], 0, ',', '.') ?></small>
                <?php endif; ?>
              </td>
              <td>
                <?= htmlspecialchars($p['metodo_pago'] ?? '—') ?>
                <?php if ($p['comprobante_img']): ?>
                  <br><a href="<?= BASE_URL ?>/assets/img/comprobantes/<?= $p['comprobante_img'] ?>"
                         target="_blank" style="color:var(--gold);font-size:.75rem">Ver comprobante</a>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge badge-<?= $p['estado'] === 'completada' ? 'success' : ($p['estado'] === 'cancelado' ? 'danger' : 'warning') ?>"
                      style="text-transform:capitalize">
                  <?= htmlspecialchars($p['estado']) ?>
                </span>
              </td>
              <td style="font-size:.8rem;color:var(--white-muted)">
                <?= date('d/m/Y H:i', strtotime($p['creado_at'])) ?>
              </td>
              <td>
                <?php $whatsCliente = preg_replace('/[^0-9]/', '', $p['envio_telefono'] ?: $p['cliente_telefono'] ?? ''); ?>
                <div style="display:flex;flex-direction:column;gap:4px">

                  <?php if ($p['estado'] === 'pendiente' && (isAdmin() || isVendedor())): ?>
                  <button onclick="confirmarPago(<?= $p['id'] ?>)"
                          class="btn btn-sm" style="background:var(--gold);color:var(--black);font-size:.72rem;padding:4px 8px">
                    ✓ Confirmar pago
                  </button>
                  <?php endif; ?>

                  <?php if ($p['estado'] === 'pagado' && (isAdmin() || isGestor())): ?>
                  <button onclick="cambiarEstadoPedido(<?= $p['id'] ?>, 'preparando')"
                          class="btn btn-outline btn-sm" style="font-size:.72rem;padding:4px 8px">
                    📦 Preparando
                  </button>
                  <?php endif; ?>

                  <?php if ($p['estado'] === 'preparando' && (isAdmin() || isGestor())): ?>
                  <button onclick="cambiarEstadoPedido(<?= $p['id'] ?>, 'enviado')"
                          class="btn btn-outline btn-sm" style="font-size:.72rem;padding:4px 8px">
                    🚚 Marcar enviado
                  </button>
                  <?php endif; ?>

                  <?php if ($p['estado'] === 'enviado' && (isAdmin() || isVendedor())): ?>
                  <button onclick="cambiarEstadoPedido(<?= $p['id'] ?>, 'entregado')"
                          class="btn btn-outline btn-sm" style="font-size:.72rem;padding:4px 8px">
                    ✅ Entregado
                  </button>
                  <?php endif; ?>

                  <?php if (!in_array($p['estado'], ['entregado','cancelado']) && isAdmin()): ?>
                  <button onclick="cambiarEstadoPedido(<?= $p['id'] ?>, 'cancelado')"
                          class="btn btn-sm" style="background:#c0392b;color:#fff;font-size:.72rem;padding:4px 8px">
                    ✕ Cancelar
                  </button>
                  <?php endif; ?>

                  <button onclick="window.open('<?= BASE_URL ?>/views/pedido_factura.php?id=<?= $p['id'] ?>','_blank')"
                          class="btn btn-outline btn-sm" style="font-size:.72rem;padding:4px 8px">
                    🖨 Factura
                  </button>

                  <?php if (!empty($whatsCliente)): ?>
                  <button onclick="abrirChatCliente('<?= $whatsCliente ?>','<?= htmlspecialchars($p['numero_pedido'], ENT_QUOTES) ?>')"
                          class="btn btn-outline btn-sm" style="font-size:.72rem;padding:4px 8px">
                    💬 WhatsApp cliente
                  </button>
                  <?php endif; ?>

                  <!-- Ver detalle -->
                  <button onclick="verDetalle(<?= $p['id'] ?>)"
                          class="btn btn-outline btn-sm" style="font-size:.72rem;padding:4px 8px">
                    👁 Detalle
                  </button>
                </div>
              </td>
            </tr>

            <!-- Fila de detalle colapsable -->
            <tr id="detalle-<?= $p['id'] ?>" style="display:none;background:var(--bg-hover)">
              <td colspan="8" style="padding:16px 20px">
                <strong style="color:var(--gold)">Detalle del pedido <?= htmlspecialchars($p['numero_pedido']) ?></strong>
                <table style="margin-top:10px;width:100%;font-size:.82rem">
                  <thead><tr>
                    <th style="padding:4px 8px;text-align:left">Producto</th>
                    <th style="padding:4px 8px">Talla</th>
                    <th style="padding:4px 8px">Cant.</th>
                    <th style="padding:4px 8px">Precio</th>
                    <th style="padding:4px 8px">Subtotal</th>
                  </tr></thead>
                  <tbody>
                  <?php foreach ($detalle as $d): ?>
                    <tr>
                      <td style="padding:4px 8px"><?= htmlspecialchars($d['producto_nombre']) ?></td>
                      <td style="padding:4px 8px;text-align:center"><?= htmlspecialchars($d['talla'] ?? '—') ?></td>
                      <td style="padding:4px 8px;text-align:center"><?= $d['cantidad'] ?></td>
                      <td style="padding:4px 8px;text-align:right">$<?= number_format($d['precio_unitario'], 0, ',', '.') ?></td>
                      <td style="padding:4px 8px;text-align:right;color:var(--gold-light)">$<?= number_format($d['subtotal'], 0, ',', '.') ?></td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
                <?php if ($p['notas']): ?>
                  <p style="margin-top:10px;font-size:.8rem;color:var(--white-muted)">
                    <strong>Notas:</strong> <?= htmlspecialchars($p['notas']) ?>
                  </p>
                <?php endif; ?>
                <?php if ($p['envio_direccion']): ?>
                  <p style="margin-top:6px;font-size:.8rem;color:var(--white-muted)">
                    <strong>Dirección:</strong>
                    <?= htmlspecialchars($p['envio_nombre']) ?> —
                    <?= htmlspecialchars($p['envio_direccion']) ?>,
                    <?= htmlspecialchars($p['envio_ciudad']) ?>
                    (<?= htmlspecialchars($p['envio_telefono']) ?>)
                  </p>
                <?php endif; ?>
              </td>
            </tr>

            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div><!-- /content -->
</div><!-- /main -->
</div><!-- /layout -->

<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script src="<?= BASE_URL ?>/tienda/assets/js/tienda.js"></script>
<script>
const VR = { baseUrl: '<?= BASE_URL ?>', loggedIn: true };

function verDetalle(id) {
  const row = document.getElementById('detalle-' + id);
  if (!row) return;
  row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}

function abrirChatCliente(numero, pedido) {
  if (!numero) return;
  let telefono = numero.toString().replace(/[^0-9]/g, '');
  if (!telefono) return;
  const defaultCountry = '57';
  if (telefono.length <= 10) telefono = defaultCountry + telefono;
  const texto = encodeURIComponent(`Hola, soy de Visión Real. Quisiera comunicarme sobre el pedido ${pedido}.`);
  window.open(`https://wa.me/${telefono}?text=${texto}`, '_blank');
}
</script>
