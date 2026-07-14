<?php
/**
 * api/tienda/carrito.php
 * API interna JSON para gestión del carrito.
 * Requiere cliente online autenticado.
 * Parámetros POST:
 *   action = agregar | actualizar | eliminar | obtener | vaciar
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../tienda/includes/session_tienda.php';

if (!tiendaLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Debes iniciar sesión para usar el carrito.']);
    exit();
}

require_once __DIR__ . '/../../models/tienda/Carrito.php';

$action    = $_POST['action'] ?? $_GET['action'] ?? 'obtener';
if ($action === 'update') $action = 'actualizar';
if ($action === 'delete') $action = 'eliminar';
if ($action === 'clear')  $action = 'vaciar';
$cliente   = getTiendaCliente();
$carritoM  = new Carrito();

try {
    switch ($action) {

        case 'agregar':
            $productoId = (int)($_POST['producto_id'] ?? 0);
            $talla      = trim($_POST['talla'] ?? '');
            $cantidad   = max(1, (int)($_POST['cantidad'] ?? 1));

            if (!$productoId) { http_response_code(400); echo json_encode(['error' => 'Producto requerido']); break; }

            $r = $carritoM->agregar($cliente['id'], $productoId, $talla, $cantidad);
            if (isset($r['success'])) invalidarCacheCarrito();
            echo json_encode($r);
            break;

        case 'actualizar':
            $itemId   = (int)($_POST['item_id'] ?? 0);
            $cantidad = (int)($_POST['cantidad'] ?? 1);
            if (!$itemId) { http_response_code(400); echo json_encode(['error' => 'Item requerido']); break; }
            $r = $carritoM->actualizarCantidad($cliente['id'], $itemId, $cantidad);
            if (isset($r['success'])) invalidarCacheCarrito();
            echo json_encode($r);
            break;

        case 'eliminar':
            $itemId = (int)($_POST['item_id'] ?? 0);
            if (!$itemId) { http_response_code(400); echo json_encode(['error' => 'Item requerido']); break; }
            $r = $carritoM->eliminarItem($cliente['id'], $itemId);
            if (isset($r['success'])) invalidarCacheCarrito();
            echo json_encode($r);
            break;

        case 'vaciar':
            $carritoM->vaciar($cliente['id']);
            invalidarCacheCarrito();
            echo json_encode(['success' => true]);
            break;

        case 'obtener':
        default:
            $contenido = $carritoM->getContenido($cliente['id']);
            // Agregar URLs de imagen
            foreach ($contenido['items'] as &$item) {
                $item['imagen_url'] = !empty($item['imagen'])
                    ? BASE_URL . '/assets/img/productos/' . $item['imagen']
                    : BASE_URL . '/tienda/assets/img/sin-imagen.svg';
            }
            echo json_encode(['success' => true] + $contenido);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno']);
    error_log('[API carrito] ' . $e->getMessage());
}
