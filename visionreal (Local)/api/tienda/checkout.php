<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Carrito.php';
require_once __DIR__ . '/../../models/Venta.php';
require_once __DIR__ . '/../../models/Pedido.php';
require_once __DIR__ . '/../../models/Cliente.php';

 $carrito = new Carrito();
 $venta = new Venta();
 $pedido = new Pedido();
 $clienteModel = new Cliente();

 $nombre = trim($_POST['nombre'] ?? '');
 $telefono = trim($_POST['telefono'] ?? '');
 $email = trim($_POST['email'] ?? '');
 $direccion = trim($_POST['direccion'] ?? '');
 $tipo_entrega = trim($_POST['tipo_entrega'] ?? 'domicilio');
 $notas = trim($_POST['notas'] ?? '');

if (empty($nombre) || empty($telefono)) {
    echo json_encode(['success' => false, 'error' => 'Nombre y teléfono son obligatorios']);
    exit;
}

 $items = $carrito->getItems(session_id());
if (empty($items)) {
    echo json_encode(['success' => false, 'error' => 'El carrito está vacío']);
    exit;
}

 $total = $carrito->getTotal(session_id());

// Registrar cliente online si no existe
$cliente_id = null;
$clientes = $clienteModel->getAll();
foreach ($clientes as $c) {
    if ($c['telefono'] === $telefono) {
        $cliente_id = $c['id'];
        break;
    }
}
if (!$cliente_id) {
    $clienteModel->create($nombre, '', $telefono, $email, $direccion);
    $cliente_id = $clienteModel->db->getConnection()->insert_id ?? null;
}

$articulos = array_map(function($item) {
    return [
        'producto_id' => $item['producto_id'],
        'cantidad' => $item['cantidad'],
        'precio' => $item['precio_unitario'],
        'talla' => $item['talla']
    ];
}, $items);

$result = $venta->create($cliente_id, 0, $articulos, $notas, null, 0, $total, null, null, 'online', 'pendiente');
if (empty($result['success'])) {
    echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Error al registrar venta']);
    exit;
}

$venta_id = $result['venta_id'];
$carrito_id = $carrito->getCarritoId(session_id());

$pedidoResult = $pedido->crearPedidoDesdeVenta($venta_id, $carrito_id, $cliente_id, [
    'nombre' => $nombre,
    'telefono' => $telefono,
    'email' => $email,
    'direccion' => $direccion
], $tipo_entrega, $notas);
if (empty($pedidoResult['success'])) {
    echo json_encode(['success' => false, 'error' => $pedidoResult['error'] ?? 'No se pudo crear pedido']);
    exit;
}

$carrito->vaciarCarrito(session_id());

echo json_encode(['success' => true, 'pedido_id' => $pedidoResult['pedido_id'], 'venta_id' => $venta_id]);
