<?php
/**
 * tienda/mis-pedidos.php
 * Lista de pedidos del cliente online autenticado.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/includes/session_tienda.php';
requireTiendaLogin();

require_once __DIR__ . '/../models/tienda/Pedido.php';
$pedidoM = new Pedido();
$cliente = getTiendaCliente();
$pedidos = $pedidoM->getPorCliente($cliente['id']);
$carritoN = getCarritoCount();

$colores = [
  'pendiente'  => '#E65100', 'pagado'     => '#2E7D32', 'preparando' => '#1565C0',
  'enviado'    => '#6A1B9A', 'entregado'  => '#1B5E20', 'cancelado'  => '#B71C1C',
];
$iconos = [
  'pendiente'  => '⏳', 'pagado'    => '✅', 'preparando' => '📦',
  'enviado'    => '🚚', 'entregado' => '🏠', 'cancelado'  => '❌',
];
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Mis Pedidos — Visión Real</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="<?= BASE_URL ?>/tienda/assets/css/tienda.css" rel="stylesheet">
</head>
<body>

<nav class="vr-navbar navbar sticky-top">
  <div class="container">
    <a class="navbar-brand vr-brand" href="<?= BASE_URL ?>/tienda/">
      <span class="brand-name">Visión Real</span><span class="brand-sub">Mis Pedidos</span>
    </a>
    <div class="navbar-nav flex-row gap-2 align-items-center">
      <div class="dropdown">
        <button class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($cliente['nombre']) ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="<?= BASE_URL ?>/tienda/mis-pedidos.php"><i class="bi bi-box-seam me-2"></i>Mis pedidos</a></li>
          <li><a class="dropdown-item" href="<?= BASE_URL ?>/tienda/perfil.php"><i class="bi bi-person me-2"></i>Mi perfil</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/api/tienda/auth.php?action=logout">Cerrar sesión</a></li>
        </ul>
      </div>
      <button class="btn btn-carrito position-relative" onclick="abrirCarrito()">
        <i class="bi bi-bag"></i><span class="carrito-badge" id="carritoCount"><?= $carritoN ?></span>
      </button>
    </div>
  </div>
</nav>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-9">

      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Mis pedidos</h2>
        <a href="<?= BASE_URL ?>/tienda/catalogo.php" class="btn btn-gold btn-sm">
          <i class="bi bi-plus me-1"></i>Nuevo pedido
        </a>
      </div>

      <?php if (empty($pedidos)): ?>
      <div class="text-center py-5">
        <i class="bi bi-box-seam" style="font-size:4rem;color:#ddd"></i>
        <h5 class="mt-3 text-muted">Aún no tienes pedidos</h5>
        <p class="text-muted">¡Explora el catálogo y haz tu primer pedido!</p>
        <a href="<?= BASE_URL ?>/tienda/catalogo.php" class="btn btn-gold">Ver catálogo</a>
      </div>

      <?php else: foreach ($pedidos as $p):
        $color = $colores[$p['estado']] ?? '#666';
        $icono = $iconos[$p['estado']] ?? '📋';
        $detalle = $pedidoM->getDetalle($p['id']);
      ?>
      <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;overflow:hidden">
        <!-- Header pedido -->
        <div class="card-header d-flex justify-content-between align-items-center py-3"
             style="background:#f8f8f8;border-bottom:1px solid #eee">
          <div>
            <span class="fw-bold" style="font-size:.95rem"><?= htmlspecialchars($p['numero_pedido']) ?></span>
            <span class="text-muted ms-2" style="font-size:.8rem">
              <?= date('d/m/Y H:i', strtotime($p['creado_at'])) ?>
            </span>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span style="background:<?= $color ?>20;color:<?= $color ?>;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:600">
              <?= $icono ?> <?= ucfirst($p['estado']) ?>
            </span>
          </div>
        </div>

        <div class="card-body p-4">
          <!-- Productos del pedido -->
          <?php foreach ($detalle as $d):
            $imgUrl = !empty($d['imagen'])
                ? BASE_URL . '/assets/img/productos/' . $d['imagen']
                : BASE_URL . '/tienda/assets/img/sin-imagen.svg';
          ?>
          <div class="d-flex gap-3 mb-3">
            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($d['producto_nombre']) ?>"
                 style="width:60px;height:60px;object-fit:cover;border-radius:8px;background:#f5f5f5;flex-shrink:0">
            <div class="flex-grow-1">
              <p class="mb-0 fw-semibold" style="font-size:.9rem"><?= htmlspecialchars($d['producto_nombre']) ?></p>
              <?php if ($d['talla']): ?>
                <p class="mb-0 text-muted" style="font-size:.78rem">Talla: <?= htmlspecialchars($d['talla']) ?></p>
              <?php endif; ?>
              <p class="mb-0 text-muted" style="font-size:.78rem">× <?= $d['cantidad'] ?></p>
            </div>
            <div class="text-end">
              <span class="fw-semibold" style="font-size:.9rem">$<?= number_format($d['subtotal'], 0, ',', '.') ?></span>
            </div>
          </div>
          <?php endforeach; ?>

          <hr>

          <!-- Total y tipo entrega -->
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div style="font-size:.82rem;color:#888">
              <?= in_array($p['tipo_entrega'], ['recoge_tienda','recoger_tienda'], true) ? '🏪 Recoge en tienda' : '🚚 Domicilio' ?>
              · <?= htmlspecialchars($p['metodo_pago'] ?? '') ?>
              <?php if ($p['descuento'] > 0): ?>
                · <span class="text-success">Descuento: -$<?= number_format($p['descuento'], 0, ',', '.') ?></span>
              <?php endif; ?>
            </div>
            <div class="fw-bold fs-5" style="color:var(--gold-dark)">
              Total: $<?= number_format($p['total'], 0, ',', '.') ?>
            </div>
          </div>

          <!-- Subir comprobante si está pendiente -->
          <?php if ($p['estado'] === 'pendiente' && !$p['comprobante_img']): ?>
          <div class="alert alert-warning mt-3 mb-0 d-flex align-items-center gap-3" style="border-radius:10px">
            <div class="flex-grow-1">
              <strong style="font-size:.88rem">⏳ Pedido pendiente de pago</strong>
              <p class="mb-0" style="font-size:.8rem">Sube el comprobante de pago para agilizar la confirmación.</p>
            </div>
            <div>
              <label class="btn btn-sm btn-gold" style="cursor:pointer">
                <i class="bi bi-upload me-1"></i>Subir
                <input type="file" accept="image/*,application/pdf" style="display:none"
                       onchange="subirComprobante(<?= $p['id'] ?>, this)">
              </label>
            </div>
          </div>
          <?php elseif ($p['comprobante_img']): ?>
          <div class="alert alert-info mt-3 mb-0" style="font-size:.82rem;border-radius:10px">
            <i class="bi bi-check-circle me-1"></i>Comprobante enviado. Estamos verificando tu pago.
          </div>
          <?php endif; ?>

          <?php if ($p['notas']): ?>
          <p class="text-muted mt-2 mb-0" style="font-size:.78rem">
            <i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars($p['notas']) ?>
          </p>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; endif; ?>

    </div>
  </div>
</div>

<!-- Panel carrito -->
<div class="vr-carrito-overlay" id="carritoOverlay" onclick="cerrarCarrito()"></div>
<aside class="vr-carrito-panel" id="carritoPanel">
  <div class="carrito-header">
    <h5><i class="bi bi-bag me-2"></i>Mi Carrito</h5>
    <button class="btn-close-panel" onclick="cerrarCarrito()">✕</button>
  </div>
  <div class="carrito-body" id="carritoBody"></div>
  <div class="carrito-footer" id="carritoFooter" style="display:none">
    <div class="d-flex justify-content-between mb-2"><strong>Subtotal:</strong><strong id="carritoSubtotal">$0</strong></div>
    <a href="<?= BASE_URL ?>/tienda/checkout.php" class="btn btn-gold w-100 mb-2">Ir al checkout</a>
  </div>
</aside>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="<?= BASE_URL ?>/tienda/assets/js/tienda.js" defer></script>
<script>
const VR = { baseUrl: '<?= BASE_URL ?>', loggedIn: true };

async function subirComprobante(pedidoId, input) {
  const file = input.files[0];
  if (!file) return;

  const fd = new FormData();
  fd.append('action',    'subir_comprobante');
  fd.append('pedido_id', pedidoId);
  fd.append('comprobante', file);

  const r    = await fetch(VR.baseUrl + '/api/tienda/pedidos.php', { method: 'POST', body: fd });
  const data = await r.json();

  if (data.success) {
    toast('Comprobante enviado. ¡Gracias!');
    setTimeout(() => location.reload(), 1500);
  } else {
    toast(data.error || 'Error al subir el archivo.', 'error');
  }
}
</script>
</body>
</html>
