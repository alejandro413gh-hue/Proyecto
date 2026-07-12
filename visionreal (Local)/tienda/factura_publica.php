<?php
/**
 * tienda/factura_publica.php
 * Factura imprimible pública mediante id + token (uso para domiciliario o cliente con enlace).
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/tienda/Pedido.php';

$id = (int)($_GET['id'] ?? 0);
$token = trim($_GET['t'] ?? '');
if (!$id || !$token) {
    http_response_code(400);
    echo "<h1>Enlace inválido</h1>";
    exit();
}

$pedidoM = new Pedido();
$pedido = $pedidoM->getById($id);
if (!$pedido || empty($pedido['printable_token']) || !hash_equals($pedido['printable_token'], $token)) {
    http_response_code(404);
    echo "<h1>Factura no encontrada o token inválido</h1>";
    exit();
}
$detalle = $pedidoM->getDetalle($id);

?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Factura <?= htmlspecialchars($pedido['numero_pedido']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/miestilo.css" rel="stylesheet">
<style>
  body{background:#f4f4f7;font-family:Inter,system-ui,Arial}
  .factura{max-width:900px;margin:24px auto;background:#fff;padding:22px;border-radius:12px}
  .factura-header{display:flex;justify-content:space-between}
  .factura-table{width:100%;border-collapse:collapse;margin-top:14px}
  .factura-table th,.factura-table td{padding:10px;border-bottom:1px solid #eee;text-align:left;color:#111}
  .factura-table th{font-weight:600;color:#111}
  .factura-table td{color:#111}
  .factura-table td strong, .factura-table th strong{color:#111}
  .btn-print{background:#0f5cba;color:#fff;border:none;padding:10px 14px;border-radius:8px;cursor:pointer}
  @media print{.no-print{display:none!important}}
</style>
</head>
<body>
<div class="factura">
  <div class="factura-header">
    <div>
      <h2>Factura / Pedido</h2>
      <p>Pedido #: <strong><?= htmlspecialchars($pedido['numero_pedido']) ?></strong></p>
      <p>Fecha: <strong><?= date('d/m/Y H:i', strtotime($pedido['creado_at'])) ?></strong></p>
    </div>
    <div style="text-align:right">
      <strong>Visión Real</strong>
      <p>Pedido online</p>
    </div>
  </div>

  <div style="display:flex;gap:16px;margin-top:16px;flex-wrap:wrap">
    <div style="flex:1;min-width:220px">
      <h4>Cliente</h4>
      <p><strong><?= htmlspecialchars($pedido['envio_nombre']) ?></strong></p>
      <p><?= htmlspecialchars($pedido['envio_telefono']) ?></p>
      <?php if (!empty($pedido['envio_direccion'])): ?><p><?= htmlspecialchars($pedido['envio_direccion']) ?></p><?php endif; ?>
      <?php if (!empty($pedido['envio_ciudad'])): ?><p><?= htmlspecialchars($pedido['envio_ciudad']) ?></p><?php endif; ?>
    </div>
    <div style="min-width:220px">
      <h4>Detalle</h4>
      <p><strong>Método:</strong> <?= htmlspecialchars($pedido['metodo_pago']) ?></p>
      <p><strong>Tipo:</strong> <?= in_array($pedido['tipo_entrega'], ['recoge_tienda','recoger_tienda'], true) ? 'Recoger en tienda' : 'Domicilio' ?></p>
      <p><strong>Estado:</strong> <?= htmlspecialchars(ucfirst($pedido['estado'])) ?></p>
    </div>
  </div>

  <h4 style="margin-top:18px">Productos</h4>
  <table class="factura-table">
    <thead><tr><th>Producto</th><th>Talla</th><th>Cant.</th><th>Precio</th><th>Subtotal</th></tr></thead>
    <tbody>
      <?php foreach ($detalle as $item): ?>
      <tr>
        <td><?= htmlspecialchars($item['producto_nombre']) ?></td>
        <td><?= htmlspecialchars($item['talla'] ?? '—') ?></td>
        <td><?= (int)$item['cantidad'] ?></td>
        <td>$<?= number_format($item['precio_unitario'],0,',','.') ?></td>
        <td>$<?= number_format($item['subtotal'],0,',','.') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div style="text-align:right;margin-top:12px">
    <p><strong>Subtotal:</strong> $<?= number_format($pedido['subtotal'],0,',','.') ?></p>
    <p><strong>Descuento:</strong> -$<?= number_format($pedido['descuento'],0,',','.') ?></p>
    <p style="font-size:1.15rem"><strong>Total:</strong> $<?= number_format($pedido['total'],0,',','.') ?></p>
  </div>

  <div style="margin-top:18px;text-align:right" class="no-print">
    <button class="btn-print" onclick="window.print()">Imprimir factura</button>
  </div>
</div>
</body>
</html>
