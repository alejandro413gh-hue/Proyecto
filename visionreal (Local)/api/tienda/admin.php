<?php
/**
 * api/tienda/admin.php
 * Acciones administrativas de la integración tienda.
 * Requiere login del sistema interno.
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
requireLogin();

require_once __DIR__ . '/../../models/tienda/ProductoTienda.php';
require_once __DIR__ . '/../../models/tienda/Pedido.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$pt     = new ProductoTienda();
$pm     = new Pedido();

switch ($action) {

    /* ── Toggle visibilidad en tienda ── */
    case 'toggle_visible':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'ID requerido']); break; }

        $ok = $pt->toggleVisible($id);
        if ($ok) {
            // Leer nuevo valor
            require_once __DIR__ . '/../../config/database.php';
            $db = Database::getInstance();
            $s  = $db->prepare("SELECT visible_tienda FROM productos WHERE id=?");
            $s->bind_param("i", $id); $s->execute();
            $row = $s->get_result()->fetch_assoc();
            echo json_encode(['success' => true, 'visible' => (bool)$row['visible_tienda']]);
        } else {
            echo json_encode(['error' => 'Error al actualizar']);
        }
        break;

    /* ── Activar todos los productos en tienda ── */
    case 'activar_todos':
        requireAdmin();
        $pt->activarTodos();
        echo json_encode(['success' => true]);
        break;

    /* ── Estadísticas físico vs online ── */
    case 'stats_ventas':
        requireAdmin();
        $stats = $pt->statsVentasPorTipo();
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;

    /* ── Resumen de pedidos para dashboard ── */
    case 'resumen_pedidos':
        $estados = $pm->countPorEstado();
        $hoy     = $pm->getTotalOnlineHoy();
        echo json_encode([
            'success'  => true,
            'estados'  => $estados,
            'hoy'      => $hoy,
            'pendientes' => $estados['pendiente'] ?? 0,
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida']);
}
