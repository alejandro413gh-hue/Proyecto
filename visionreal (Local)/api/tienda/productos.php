<?php
/**
 * api/tienda/productos.php
 * API interna JSON — catálogo de productos para la tienda online.
 * Acceso público (no requiere login).
 * Parámetros GET:
 *   action        = catalogo | detalle | tallas | destacados | categorias
 *   id            = producto_id  (para action=detalle|tallas)
 *   busqueda      = string
 *   categoria_id  = int
 *   orden         = reciente|precio_asc|precio_desc|nombre
 *   pagina        = int
 */
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/tienda/ProductoTienda.php';

$action = $_GET['action'] ?? 'catalogo';
$pt = new ProductoTienda();

try {
    switch ($action) {

        case 'catalogo':
            $result = $pt->getCatalogo([
                'busqueda'    => $_GET['busqueda']     ?? '',
                'categoria_id'=> (int)($_GET['categoria_id'] ?? 0),
                'orden'       => $_GET['orden']        ?? 'reciente',
                'pagina'      => (int)($_GET['pagina'] ?? 1),
            ]);
            // Agregar URL de imagen a cada producto
            foreach ($result['productos'] as &$p) {
                $p['imagen_url'] = !empty($p['imagen'])
                    ? BASE_URL . '/assets/img/productos/' . $p['imagen']
                    : BASE_URL . '/tienda/assets/img/sin-imagen.svg';
            }
            echo json_encode(['success' => true] + $result);
            break;

        case 'detalle':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); break; }
            $p = $pt->getDetalle($id);
            if (!$p)  { http_response_code(404); echo json_encode(['error' => 'Producto no encontrado']); break; }
            $p['imagen_url'] = !empty($p['imagen'])
                ? BASE_URL . '/assets/img/productos/' . $p['imagen']
                : BASE_URL . '/tienda/assets/img/sin-imagen.svg';
            echo json_encode(['success' => true, 'producto' => $p]);
            break;

        case 'tallas':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); break; }
            $tallas = $pt->getTallasProducto($id);
            echo json_encode(['success' => true, 'tallas' => $tallas]);
            break;

        case 'destacados':
            $limit = min(20, (int)($_GET['limit'] ?? 8));
            $items = $pt->getDestacados($limit);
            foreach ($items as &$p) {
                $p['imagen_url'] = !empty($p['imagen'])
                    ? BASE_URL . '/assets/img/productos/' . $p['imagen']
                    : BASE_URL . '/tienda/assets/img/sin-imagen.svg';
            }
            echo json_encode(['success' => true, 'productos' => $items]);
            break;

        case 'categorias':
            echo json_encode(['success' => true, 'categorias' => $pt->getCategoriasActivas()]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
    error_log('[API tienda/productos] ' . $e->getMessage());
}
