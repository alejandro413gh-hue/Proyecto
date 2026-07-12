<?php
/**
 * api/tienda/pedidos.php
 * API interna JSON para gestión de pedidos.
 * Acciones de cliente: crear, listar_mio, subir_comprobante
 * Acciones de admin/vendedor/bodeguero: listar_admin, cambiar_estado, confirmar_pago
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../tienda/includes/session_tienda.php';
require_once __DIR__ . '/../../models/tienda/Pedido.php';
require_once __DIR__ . '/../../models/tienda/Carrito.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$pedidoM = new Pedido();

/* ─── Acciones del cliente online ─────────────────────────── */

if (in_array($action, ['crear', 'listar_mio', 'subir_comprobante', 'ver'])) {
    if (!tiendaLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Sesión requerida']);
        exit();
    }

    $cliente = getTiendaCliente();

    switch ($action) {

        case 'crear':
            $carritoM   = new Carrito();
            $contenido  = $carritoM->getContenido($cliente['id']);

            if (empty($contenido['items'])) {
                echo json_encode(['error' => 'El carrito está vacío']);
                break;
            }

            $datosEnvio = [
                'nombre'      => trim($_POST['nombre']      ?? ''),
                'telefono'    => trim($_POST['telefono']    ?? ''),
                'direccion'   => trim($_POST['direccion']   ?? ''),
                'ciudad'      => trim($_POST['ciudad']      ?? ''),
                'notas'       => trim($_POST['notas']       ?? ''),
                'tipo_entrega'=> $_POST['tipo_entrega']     ?? 'domicilio',
                'metodo_pago' => $_POST['metodo_pago']      ?? 'transferencia',
            ];

            if (empty($datosEnvio['nombre']) || empty($datosEnvio['telefono'])) {
                echo json_encode(['error' => 'Nombre y teléfono son requeridos']);
                break;
            }

            $descuentoId = isset($_POST['descuento_id']) ? (int)$_POST['descuento_id'] : null;
            $r = $pedidoM->crearDesdeCarrito($cliente['id'], $datosEnvio, $contenido, $descuentoId);

            if ($r['success'] ?? false) {
                // Vaciar carrito
                $carritoM->vaciar($cliente['id']);
                invalidarCacheCarrito();
            }
            echo json_encode($r);
            break;

        case 'listar_mio':
            $pedidos = $pedidoM->getPorCliente($cliente['id']);
            echo json_encode(['success' => true, 'pedidos' => $pedidos]);
            break;

        case 'ver':
            $id = (int)($_GET['id'] ?? 0);
            $p  = $pedidoM->getById($id);
            if (!$p || $p['cliente_online_id'] != $cliente['id']) {
                http_response_code(404);
                echo json_encode(['error' => 'Pedido no encontrado']);
                break;
            }
            $p['detalle']  = $pedidoM->getDetalle($id);
            $p['historial'] = $pedidoM->getHistorial($id);
            echo json_encode(['success' => true, 'pedido' => $p]);
            break;

        case 'subir_comprobante':
            $pedidoId = (int)($_POST['pedido_id'] ?? 0);
            if (!$pedidoId || empty($_FILES['comprobante'])) {
                echo json_encode(['error' => 'Datos incompletos']);
                break;
            }
            $r = $pedidoM->subirComprobante($pedidoId, $cliente['id'], $_FILES['comprobante']);
            echo json_encode($r);
            break;
    }
    exit();
}

/* ─── Acciones del panel interno (admin/vendedor/bodeguero) ── */

requireLogin(); // Función del sistema existente

switch ($action) {

    case 'listar_admin':
        $estado = $_GET['estado'] ?? '';
        $limit  = min(100, (int)($_GET['limit'] ?? 50));
        $pedidos = $pedidoM->getAllAdmin($estado, $limit);
        foreach ($pedidos as &$p) {
            $p['detalle'] = $pedidoM->getDetalle($p['id']);
        }
        echo json_encode(['success' => true, 'pedidos' => $pedidos]);
        break;

    case 'ver_admin':
        $id = (int)($_GET['id'] ?? 0);
        $p  = $pedidoM->getById($id);
        if (!$p) { http_response_code(404); echo json_encode(['error' => 'No encontrado']); break; }
        $p['detalle']   = $pedidoM->getDetalle($id);
        $p['historial'] = $pedidoM->getHistorial($id);
        echo json_encode(['success' => true, 'pedido' => $p]);
        break;

    case 'confirmar_pago':
        if (!isAdmin() && !isVendedor()) {
            http_response_code(403);
            echo json_encode(['error' => 'Sin permiso']);
            break;
        }
        $pedidoId        = (int)($_POST['pedido_id'] ?? 0);
        $referenciaPago  = trim($_POST['referencia'] ?? '');
        $usuarioId       = getCurrentUser()['id'];
        if (!$pedidoId) { echo json_encode(['error' => 'ID requerido']); break; }
        $r = $pedidoM->confirmarPago($pedidoId, $usuarioId, $referenciaPago ?: null);
        echo json_encode($r);
        break;

    case 'cambiar_estado':
        $pedidoId    = (int)($_POST['pedido_id'] ?? 0);
        $nuevoEstado = trim($_POST['estado'] ?? '');
        $nota        = trim($_POST['nota']   ?? '');
        $usuarioId   = getCurrentUser()['id'];
        if (!$pedidoId || !$nuevoEstado) { echo json_encode(['error' => 'Datos incompletos']); break; }

        // Bodeguero solo puede cambiar a preparando/enviado
        if (isGestor()) {
            $permitidos = ['preparando', 'enviado'];
            if (!in_array($nuevoEstado, $permitidos)) {
                echo json_encode(['error' => 'No tienes permiso para ese estado']);
                break;
            }
        }

        $r = $pedidoM->cambiarEstado($pedidoId, $nuevoEstado, $usuarioId, $nota);
        echo json_encode($r);
        break;

    case 'stats':
        requireAdmin();
        echo json_encode([
            'success'  => true,
            'estados'  => $pedidoM->countPorEstado(),
            'hoy'      => $pedidoM->getTotalOnlineHoy(),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida']);
}
