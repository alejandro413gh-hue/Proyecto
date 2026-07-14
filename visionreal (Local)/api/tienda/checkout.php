<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../tienda/includes/session_tienda.php';
require_once __DIR__ . '/../../models/tienda/Carrito.php';
require_once __DIR__ . '/../../models/tienda/Pedido.php';

if (!tiendaLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Debes iniciar sesión para continuar.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$carrito = new Carrito();
$pedido = new Pedido();
$cliente = getTiendaCliente();

$nombre = trim($_POST['nombre'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$email = trim($_POST['email'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$ciudad = trim($_POST['ciudad'] ?? '');
$tipo_entrega = trim($_POST['tipo_entrega'] ?? 'domicilio');
$metodo_pago = trim($_POST['metodo_pago'] ?? 'transferencia');
$notas = trim($_POST['notas'] ?? '');
$descuentoId = isset($_POST['descuento_id']) ? (int) $_POST['descuento_id'] : null;

if ($nombre === '') {
    $nombre = (string) ($cliente['nombre'] ?? 'Consumidor Final');
}
if ($telefono === '') {
    $telefono = (string) ($cliente['telefono'] ?? '');
}

$contenido = $carrito->getContenido((int) $cliente['id']);
if (empty($contenido['items'])) {
    echo json_encode([
        'success' => false,
        'error' => 'El carrito está vacío.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$datosEnvio = [
    'nombre' => $nombre,
    'telefono' => $telefono,
    'email' => $email,
    'direccion' => $direccion,
    'ciudad' => $ciudad,
    'tipo_entrega' => $tipo_entrega,
    'metodo_pago' => $metodo_pago,
    'notas' => $notas,
];

$resultado = $pedido->crearDesdeCarrito((int) $cliente['id'], $datosEnvio, $contenido, $descuentoId);
if (!($resultado['success'] ?? false)) {
    echo json_encode([
        'success' => false,
        'error' => $resultado['error'] ?? 'No se pudo crear el pedido.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$carrito->vaciar((int) $cliente['id']);
invalidarCacheCarrito();

echo json_encode([
    'success' => true,
    'pedido_id' => $resultado['pedido_id'] ?? null,
    'numero' => $resultado['numero'] ?? null,
    'total' => $resultado['total'] ?? null,
    'factura_url' => $resultado['factura_url'] ?? null,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
