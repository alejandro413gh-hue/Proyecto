<?php
/**
 * models/tienda/Carrito.php
 * Gestiona el carrito de compras online.
 * Valida stock real contra producto_tallas (misma tabla que ventas físicas).
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Talla.php';

class Carrito {
    private $db;
    private Talla $inventario;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->inventario = new Talla();
    }

    /* ─── Obtener o crear carrito del cliente ────────────────── */

    private function obtenerOCrear(int $clienteOnlineId): int {
        $s = $this->db->prepare("SELECT id FROM carrito WHERE cliente_online_id=?");
        $s->bind_param("i", $clienteOnlineId);
        $s->execute();
        $r = $s->get_result()->fetch_assoc();
        if ($r) return (int)$r['id'];

        $s2 = $this->db->prepare("INSERT INTO carrito (cliente_online_id) VALUES (?)");
        $s2->bind_param("i", $clienteOnlineId);
        $s2->execute();
        return (int)$this->db->lastInsertId();
    }

    /* ─── Agregar / actualizar item ─────────────────────────── */

    public function agregar(int $clienteOnlineId, int $productoId, string $talla, int $cantidad): array {
        if ($cantidad < 1) return ['error' => 'Cantidad inválida.'];

        // Verificar que el producto existe y está visible en tienda
        $prod = $this->getProductoTienda($productoId);
        if (!$prod) return ['error' => 'Producto no disponible.'];

        // Verificar stock de la talla
        $stockDisponible = $this->getStockTalla($productoId, $talla);
        if ($stockDisponible < $cantidad) {
            $label = trim($talla) !== '' ? "La talla {$talla}" : 'El producto';
            return ['error' => "{$label} únicamente tiene {$stockDisponible} unidades disponibles."];
        }

        $precio    = (float)$prod['precio'];
        $carritoId = $this->obtenerOCrear($clienteOnlineId);

        // Cantidad ya en carrito para este producto+talla
        $yaEnCarrito = $this->getCantidadEnCarrito($carritoId, $productoId, $talla);
        $totalSolicitado = $yaEnCarrito + $cantidad;
        if ($totalSolicitado > $stockDisponible)
            return ['error' => "Solo hay {$stockDisponible} unidades disponibles (ya tienes {$yaEnCarrito} en el carrito)."];

        $s = $this->db->prepare(
            "INSERT INTO carrito_items (carrito_id,producto_id,talla,cantidad,precio_unitario)
             VALUES (?,?,?,?,?)
             ON DUPLICATE KEY UPDATE
               cantidad = cantidad + VALUES(cantidad),
               precio_unitario = VALUES(precio_unitario)"
        );
        $s->bind_param("iisid", $carritoId, $productoId, $talla, $cantidad, $precio);
        if (!$s->execute()) return ['error' => 'Error al agregar al carrito.'];

        return ['success' => true, 'total_items' => $this->contarItems($clienteOnlineId)];
    }

    /* ─── Actualizar cantidad de un item ────────────────────── */

    public function actualizarCantidad(int $clienteOnlineId, int $itemId, int $cantidad): array {
        if ($cantidad < 1) return $this->eliminarItem($clienteOnlineId, $itemId);

        $item = $this->getItem($itemId, $clienteOnlineId);
        if (!$item) return ['error' => 'Item no encontrado.'];

        $check = $this->inventario->validarCantidadDisponible((int) $item['producto_id'], (string) ($item['talla'] ?? ''), $cantidad);
        if (!($check['success'] ?? false)) {
            return ['error' => $check['error'] ?? 'No hay suficiente inventario.'];
        }

        $s = $this->db->prepare(
            "UPDATE carrito_items ci
             JOIN carrito c ON ci.carrito_id = c.id
             SET ci.cantidad = ?
             WHERE ci.id = ? AND c.cliente_online_id = ?"
        );
        $s->bind_param("iii", $cantidad, $itemId, $clienteOnlineId);
        $s->execute();
        return ['success' => true, 'subtotal' => $this->getSubtotal($clienteOnlineId)];
    }

    /* ─── Eliminar item ─────────────────────────────────────── */

    public function eliminarItem(int $clienteOnlineId, int $itemId): array {
        $s = $this->db->prepare(
            "DELETE ci FROM carrito_items ci
             JOIN carrito c ON ci.carrito_id = c.id
             WHERE ci.id = ? AND c.cliente_online_id = ?"
        );
        $s->bind_param("ii", $itemId, $clienteOnlineId);
        $s->execute();
        return ['success' => true, 'total_items' => $this->contarItems($clienteOnlineId)];
    }

    /* ─── Vaciar carrito ────────────────────────────────────── */

    public function vaciar(int $clienteOnlineId): void {
        $carritoId = $this->getCarritoId($clienteOnlineId);
        if (!$carritoId) return;
        $s = $this->db->prepare("DELETE FROM carrito_items WHERE carrito_id=?");
        $s->bind_param("i", $carritoId);
        $s->execute();
    }

    /* ─── Obtener contenido del carrito ─────────────────────── */

    public function getContenido(int $clienteOnlineId): array {
        $carritoId = $this->getCarritoId($clienteOnlineId);
        if (!$carritoId) return ['items' => [], 'subtotal' => 0, 'total_items' => 0];

        $s = $this->db->prepare(
            "SELECT ci.id, ci.producto_id, ci.talla, ci.cantidad, ci.precio_unitario,
                    (ci.cantidad * ci.precio_unitario) as subtotal_item,
                    p.nombre, p.imagen, p.activo, p.visible_tienda,
                    CASE
                        WHEN ci.talla IS NULL OR ci.talla = '' THEN COALESCE(p.stock, 0)
                        ELSE COALESCE(pt.stock, 0)
                    END as stock_disponible
             FROM carrito_items ci
             JOIN productos p ON ci.producto_id = p.id
             LEFT JOIN producto_tallas pt
                    ON pt.producto_id = ci.producto_id AND pt.talla COLLATE utf8mb4_general_ci = ci.talla
             WHERE ci.carrito_id = ?
             ORDER BY ci.id ASC"
        );
        $s->bind_param("i", $carritoId);
        $s->execute();
        $r = $s->get_result();

        $items    = [];
        $subtotal = 0;
        $totalItm = 0;
        while ($row = $r->fetch_assoc()) {
            $items[]   = $row;
            $subtotal += (float)$row['subtotal_item'];
            $totalItm += (int)$row['cantidad'];
        }
        return ['items' => $items, 'subtotal' => $subtotal, 'total_items' => $totalItm];
    }

    /* ─── Helpers ────────────────────────────────────────────── */

    private function getCarritoId(int $clienteOnlineId): ?int {
        $s = $this->db->prepare("SELECT id FROM carrito WHERE cliente_online_id=?");
        $s->bind_param("i", $clienteOnlineId);
        $s->execute();
        $r = $s->get_result()->fetch_assoc();
        return $r ? (int)$r['id'] : null;
    }

    private function getCantidadEnCarrito(int $carritoId, int $productoId, string $talla): int {
        $s = $this->db->prepare(
            "SELECT COALESCE(cantidad,0) as c FROM carrito_items
             WHERE carrito_id=? AND producto_id=? AND talla=?"
        );
        $s->bind_param("iis", $carritoId, $productoId, $talla);
        $s->execute();
        $r = $s->get_result()->fetch_assoc();
        return $r ? (int)$r['c'] : 0;
    }

    private function getItem(int $itemId, int $clienteOnlineId): ?array {
        $s = $this->db->prepare(
            "SELECT ci.* FROM carrito_items ci
             JOIN carrito c ON ci.carrito_id = c.id
             WHERE ci.id=? AND c.cliente_online_id=?"
        );
        $s->bind_param("ii", $itemId, $clienteOnlineId);
        $s->execute();
        return $s->get_result()->fetch_assoc() ?: null;
    }

    private function getStockTalla(int $productoId, string $talla): int {
        return $this->inventario->getStockDisponible($productoId, $talla);
    }

    public function validarItems(array $items): array {
        return $this->inventario->validarItems($items);
    }

    private function getProductoTienda(int $id): ?array {
        $s = $this->db->prepare(
            "SELECT * FROM productos WHERE id=? AND activo=1 AND visible_tienda=1"
        );
        $s->bind_param("i", $id);
        $s->execute();
        return $s->get_result()->fetch_assoc() ?: null;
    }

    public function contarItems(int $clienteOnlineId): int {
        $carritoId = $this->getCarritoId($clienteOnlineId);
        if (!$carritoId) return 0;
        $s = $this->db->prepare(
            "SELECT COALESCE(SUM(cantidad),0) as t FROM carrito_items WHERE carrito_id=?"
        );
        $s->bind_param("i", $carritoId);
        $s->execute();
        return (int)$s->get_result()->fetch_assoc()['t'];
    }

    private function getSubtotal(int $clienteOnlineId): float {
        $carritoId = $this->getCarritoId($clienteOnlineId);
        if (!$carritoId) return 0;
        $s = $this->db->prepare(
            "SELECT COALESCE(SUM(cantidad*precio_unitario),0) as t FROM carrito_items WHERE carrito_id=?"
        );
        $s->bind_param("i", $carritoId);
        $s->execute();
        return (float)$s->get_result()->fetch_assoc()['t'];
    }
}
