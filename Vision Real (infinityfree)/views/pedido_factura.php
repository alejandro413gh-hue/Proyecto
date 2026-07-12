<?php
/**
 * views/pedido_factura.php
 * Factura imprimible de pedido online para administración.
 */
$pageTitle = 'Factura de pedido';
require_once __DIR__ . '/../config/config.php';
requireLogin();
if (!isAdmin() && !isVendedor()) {
    header('Location: ' . BASE_URL . '/views/dashboard.php');
    exit();
}
require_once __DIR__ . '/../models/tienda/Pedido.php';
$pedidoM = new Pedido();
$pedidoId = (int)($_GET['id'] ?? 0);
$pedido = $pedidoM->getById($pedidoId);
if (!$pedido) {
    http_response_code(404);
    echo '<h1>Pedido no encontrado</h1>';
    exit();
}
$detalle = $pedidoM->getDetalle($pedidoId);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/miestilo.css" rel="stylesheet">
<style>
  body { background:#f4f4f7; color:#111; font-family:'Inter',sans-serif; }
  .factura { max-width:900px; margin:36px auto; background:#fff; border-radius:18px; padding:28px; box-shadow:0 18px 40px rgba(0,0,0,.08); }
  .factura-header { display:flex; justify-content:space-between; flex-wrap:wrap; gap:14px; align-items:flex-start; }
  .factura-header h1 { margin:0; font-size:1.7rem; }
  .factura-meta { text-align:right; font-size:.95rem; color:#555; }
  .factura-section { margin-top:28px; }
  .factura-section h2 { margin-bottom:12px; font-size:1rem; letter-spacing:.03em; text-transform:uppercase; color:#333; }
  .factura-grid { display:grid; grid-template-columns:repeat(2,minmax(180px,1fr)); gap:16px; }
  .factura-box { padding:16px; border:1px solid #e6e6e6; border-radius:14px; background:#fafafa; }
  .factura-table { width:100%; border-collapse:collapse; margin-top:14px; }
  .factura-table th, .factura-table td { padding:12px 10px; border-bottom:1px solid #e7e7e7; text-align:left; }
  .factura-table th { background:#f8f8f8; color:#333; font-weight:600; }
  .factura-total { text-align:right; font-size:1.05rem; margin-top:16px; }
  .factura-actions { display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap; margin-top:24px; }
  .btn-print { background:#0f5cba; color:#fff; border:none; padding:12px 18px; border-radius:12px; cursor:pointer; }
  @media print { .factura-actions, .navbar, .no-print { display:none !important; } body { background:#fff; } }
</style>
</head>
<body>
<div class="factura">
  <div class="factura-header">
    <div>
      <h1>Factura de pedido</h1>
      <p style="margin:.5rem 0 0;color:#666">Pedido #: <strong><?= htmlspecialchars($pedido['numero_pedido']) ?></strong></p>
      <p style="margin:.15rem 0 0;color:#666">Fecha: <strong><?= date('d/m/Y H:i', strtotime($pedido['creado_at'])) ?></strong></p>
    </div>
    <div class="factura-meta">
      <p><strong>Visión Real</strong></p>
      <p>Pedido online</p>
      <p><?= htmlspecialchars(APP_NAME) ?></p>
    </div>
  </div>

  <div class="factura-section factura-grid">
    <div class="factura-box">
      <h2>Cliente</h2>
      <p><strong><?= htmlspecialchars($pedido['envio_nombre']) ?></strong></p>
      <p><?= htmlspecialchars($pedido['envio_telefono']) ?></p>
      <?php if (!empty($pedido['envio_direccion'])): ?>
      <p><?= htmlspecialchars($pedido['envio_direccion']) ?></p>
      <?php endif; ?>
      <?php if (!empty($pedido['envio_ciudad'])): ?>
      <p><?= htmlspecialchars($pedido['envio_ciudad']) ?></p>
      <?php endif; ?>
    </div>
    <div class="factura-box">
      <h2>Detalle de pedido</h2>
      <p><strong>Método de pago:</strong> <?= htmlspecialchars($pedido['metodo_pago']) ?></p>
      <p><strong>Tipo de entrega:</strong> <?= in_array($pedido['tipo_entrega'], ['recoge_tienda','recoger_tienda'], true) ? 'Recoger en tienda' : 'Domicilio' ?></p>
      <p><strong>Estado:</strong> <?= htmlspecialchars(ucfirst($pedido['estado'])) ?></p>
      <?php if (!empty($pedido['notas'])): ?>
      <p><strong>Notas:</strong> <?= htmlspecialchars($pedido['notas']) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <div class="factura-section">
    <h2>Productos</h2>
    <table class="factura-table">
      <thead>
        <tr>
          <th>Producto</th>
          <th>Talla</th>
          <th>Cantidad</th>
          <th>Precio</th>
          <th>Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($detalle as $item): ?>
        <tr>
          <td><?= htmlspecialchars($item['producto_nombre']) ?></td>
          <td><?= htmlspecialchars($item['talla'] ?? '—') ?></td>
          <td><?= (int)$item['cantidad'] ?></td>
          <td>$<?= number_format($item['precio_unitario'], 0, ',', '.') ?></td>
          <td>$<?= number_format($item['subtotal'], 0, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="factura-total">
      <p><strong>Subtotal:</strong> $<?= number_format($pedido['subtotal'], 0, ',', '.') ?></p>
      <p><strong>Descuento:</strong> -$<?= number_format($pedido['descuento'], 0, ',', '.') ?></p>
      <p style="font-size:1.25rem;"><strong>Total:</strong> $<?= number_format($pedido['total'], 0, ',', '.') ?></p>
    </div>
  </div>

  <div class="factura-actions no-print">
    <button class="btn-print" onclick="window.print()">Imprimir factura</button>
    <a class="btn-print" href="<?= BASE_URL ?>/views/pedidos_online.php">Volver a pedidos</a>
  </div>
</div>
</body>
</html>
