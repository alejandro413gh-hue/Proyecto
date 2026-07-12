<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Producto.php';
require_once __DIR__ . '/../../models/Talla.php';

$productoId = intval($_GET['id'] ?? 0);
if ($productoId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Producto inválido']);
    exit;
}

$producto = new Producto();
$talla = new Talla();
$p = $producto->getById($productoId);
if (!$p || $p['activo'] != 1 || empty($p['visible_tienda'])) {
    echo json_encode(['success' => false, 'error' => 'Producto no disponible']);
    exit;
}

$p['imagen_url'] = Producto::getImageUrl($p, BASE_URL);
$p['tallas'] = $talla->getDisponibles($productoId);
$p['stock_total'] = $talla->getStockTotal($productoId);

echo json_encode(['success' => true, 'producto' => $p]);
