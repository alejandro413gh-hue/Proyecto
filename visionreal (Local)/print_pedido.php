<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/tienda/includes/session_tienda.php';
require_once __DIR__ . '/models/tienda/Pedido.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    echo 'Pedido no encontrado';
    exit();
}

$pedidoM = new Pedido();
$pedido = $pedidoM->getById($id);
if (!$pedido) {
    http_response_code(404);
    echo 'Pedido no encontrado';
    exit();
}

$esCliente = tiendaLoggedIn() && $pedido['cliente_online_id'] === getTiendaCliente()['id'];
$esInterno = isLoggedIn() && (isAdmin() || isVendedor() || isGestor());

if (!$esCliente && !$esInterno) {
    if (tiendaLoggedIn()) {
        http_response_code(403);
        echo 'No tienes permiso para ver este pedido';
        exit();
    }
    requireLogin();
}

$detalle = $pedidoM->getDetalle($id);
$historial = $pedidoM->getHistorial($id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pedido <?= htmlspecialchars($pedido['numero_pedido']) ?> — Visión Real</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: Inter, system-ui, sans-serif; color: #222; background: #f7f7f7; margin:0; padding:0; }
    .page { max-width:900px; margin:24px auto; padding:20px; background:#fff; border-radius:16px; box-shadow:0 16px 50px rgba(0,0,0,.08); }
    h1,h2,h3,h4 { margin:0; }
    .header, .section { margin-bottom:24px; }
    .header-top { display:flex; flex-wrap:wrap; justify-content:space-between; gap:16px; }
    .badge { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:999px; background:#fde5b2; color:#8c5e13; font-weight:700; }
    .grid { display:grid; gap:14px; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); }
    .card { background:#faf9f5; padding:18px; border-radius:14px; border:1px solid #eee; }
    table { width:100%; border-collapse:collapse; margin-top:16px; }
    table th, table td { padding:12px 10px; border-bottom:1px solid #eee; text-align:left; }
    table th { background:#f7f7f7; font-weight:700; }
    .text-muted { color:#666; }
    .total-row { display:flex; justify-content:space-between; margin-top:16px; font-size:1rem; font-weight:700; }
    .print-bar { display:flex; flex-wrap:wrap; gap:12px; align-items:center; margin-top:10px; }
    .btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:12px 18px; border-radius:999px; border:0; cursor:pointer; text-decoration:none; color:#111; background:#f4c150; font-weight:700; }
    .btn-secondary { background:#eef0f2; color:#111; }
    .notes { margin-top:14px; padding:14px; background:#f4f6f8; border-radius:12px; }
</style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="header-top">
            <div>
                <h1>Pedido <?= htmlspecialchars($pedido['numero_pedido']) ?></h1>
                <p class="text-muted">Generado el <?= date('d/m/Y H:i', strtotime($pedido['creado_at'])) ?></p>
            </div>
            <div>
                <span class="badge">Estado: <?= htmlspecialchars(ucfirst($pedido['estado'])) ?></span>
            </div>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <h3 style="margin-bottom:10px">Cliente</h3>
            <p><strong><?= htmlspecialchars($pedido['envio_nombre'] ?: $pedido['cliente_nombre']) ?></strong></p>
            <p><?= htmlspecialchars($pedido['envio_telefono'] ?: $pedido['cliente_telefono']) ?></p>
        </div>
        <div class="card">
            <h3 style="margin-bottom:10px">Entrega</h3>
            <p><?= in_array($pedido['tipo_entrega'], ['recoge_tienda','recoger_tienda'], true) ? 'Recoge en tienda' : 'Domicilio' ?></p>
            <?php if (!empty($pedido['envio_direccion'])): ?>
            <p><?= htmlspecialchars($pedido['envio_direccion']) ?></p>
            <p><?= htmlspecialchars($pedido['envio_ciudad']) ?></p>
            <?php endif; ?>
        </div>
        <div class="card">
            <h3 style="margin-bottom:10px">Pago</h3>
            <p><strong><?= htmlspecialchars(ucfirst($pedido['metodo_pago'] ?? 'No definido')) ?></strong></p>
            <p>Total: <strong>$<?= number_format($pedido['total'], 0, ',', '.') ?></strong></p>
        </div>
    </div>

    <div class="section">
        <h2 style="margin-bottom:12px">Productos</h2>
        <table>
            <thead>
                <tr><th>Producto</th><th>Talla</th><th style="text-align:center">Cantidad</th><th style="text-align:right">Valor</th><th style="text-align:right">Subtotal</th></tr>
            </thead>
            <tbody>
            <?php foreach ($detalle as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['producto_nombre']) ?></td>
                    <td><?= htmlspecialchars($item['talla'] ?: '—') ?></td>
                    <td style="text-align:center"><?= $item['cantidad'] ?></td>
                    <td style="text-align:right">$<?= number_format($item['precio_unitario'], 0, ',', '.') ?></td>
                    <td style="text-align:right">$<?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="total-row">
            <span>Subtotal</span>
            <span>$<?= number_format($pedido['subtotal'], 0, ',', '.') ?></span>
        </div>
        <?php if ($pedido['descuento'] > 0): ?>
        <div class="total-row" style="color:#1e7e34">
            <span>Descuento</span>
            <span>-$<?= number_format($pedido['descuento'], 0, ',', '.') ?></span>
        </div>
        <?php endif; ?>
        <div class="total-row" style="font-size:1.2rem;color:#111;margin-top:8px">
            <span>Total</span>
            <span>$<?= number_format($pedido['total'], 0, ',', '.') ?></span>
        </div>
    </div>

    <?php if (!empty($pedido['notas'])): ?>
    <div class="notes">
        <strong>Nota del pedido:</strong>
        <p><?= nl2br(htmlspecialchars($pedido['notas'])) ?></p>
    </div>
    <?php endif; ?>

    <?php if (!empty($historial)): ?>
    <div class="section">
        <h2 style="margin-bottom:12px">Historial de estado</h2>
        <table>
            <thead>
                <tr><th>Fecha</th><th>Usuario</th><th>Estado anterior</th><th>Nuevo estado</th><th>Nota</th></tr>
            </thead>
            <tbody>
            <?php foreach ($historial as $row): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($row['creado_at'])) ?></td>
                    <td><?= htmlspecialchars($row['usuario_nombre'] ?: 'Sistema') ?></td>
                    <td><?= htmlspecialchars($row['estado_ant'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($row['estado_new']) ?></td>
                    <td><?= htmlspecialchars($row['nota'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="print-bar">
        <button class="btn" onclick="window.print()">🖨️ Imprimir pedido</button>
        <a class="btn btn-secondary" href="<?= BASE_URL ?><?= $esCliente ? '/tienda/mis-pedidos.php' : '/views/pedidos_online.php' ?>">Volver</a>
    </div>
</div>
</body>
</html>
