<?php
require_once __DIR__ . '/../config/database.php';

class Venta {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function getAll($limit = 50) {
        $s = $this->db->prepare(
            "SELECT v.*, c.nombre as cliente_nombre, u.nombre as vendedor_nombre,
                    p.nombre as promocion_nombre
             FROM ventas v
             LEFT JOIN clientes c ON v.cliente_id = c.id
             LEFT JOIN usuarios u ON v.usuario_id = u.id
             LEFT JOIN promociones p ON v.promocion_id = p.id
             ORDER BY v.fecha DESC LIMIT ?"
        );
        $s->bind_param("i", $limit); $s->execute();
        $r = $s->get_result(); $a = [];
        while($row = $r->fetch_assoc()) $a[] = $row;
        return $a;
    }

    public function getById($id) {
        $s = $this->db->prepare(
            "SELECT v.*, c.nombre as cliente_nombre, u.nombre as vendedor_nombre,
                    p.nombre as promocion_nombre, p.tipo as promocion_tipo, p.valor as promocion_valor
             FROM ventas v
             LEFT JOIN clientes c ON v.cliente_id = c.id
             LEFT JOIN usuarios u ON v.usuario_id = u.id
             LEFT JOIN promociones p ON v.promocion_id = p.id
             WHERE v.id = ?"
        );
        $s->bind_param("i", $id); $s->execute();
        return $s->get_result()->fetch_assoc();
    }

    public function getDetalle($vid) {
        $s = $this->db->prepare(
            "SELECT dv.*, p.nombre as producto_nombre
             FROM detalle_venta dv
             JOIN productos p ON dv.producto_id = p.id
             WHERE dv.venta_id = ?"
        );
        $s->bind_param("i", $vid); $s->execute();
        $r = $s->get_result(); $a = [];
        while($row = $r->fetch_assoc()) $a[] = $row;
        return $a;
    }

    public function create($cliente_id, $usuario_id, $productos, $notas = '', $promocion_id = null, $descuento = 0, $total_sin_descuento = 0) {
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        try {
            $subtotal = array_sum(array_map(fn($p) => $p['precio'] * $p['cantidad'], $productos));
            $total_sin_descuento = $subtotal;
            $total = max(0, $subtotal - $descuento);

            $s = $conn->prepare(
                "INSERT INTO ventas (cliente_id, usuario_id, total, notas, promocion_id, descuento, total_sin_descuento)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $s->bind_param("iidsidd", $cliente_id, $usuario_id, $total, $notas, $promocion_id, $descuento, $total_sin_descuento);
            $s->execute();
            $vid = $conn->insert_id;

            // Verificar columna talla UNA SOLA VEZ antes del loop (no dentro del loop)
            $chk = $conn->query("SHOW COLUMNS FROM detalle_venta LIKE 'talla'");
            if ($chk->num_rows === 0) {
                $conn->query("ALTER TABLE detalle_venta ADD COLUMN talla VARCHAR(20) NULL AFTER cantidad");
            }

            foreach ($productos as $p) {
                $sub   = $p['precio'] * $p['cantidad'];
                $talla = isset($p['talla']) && $p['talla'] !== '' ? $p['talla'] : null;

                $s2 = $conn->prepare(
                    "INSERT INTO detalle_venta (venta_id, producto_id, cantidad, talla, precio_unitario, subtotal) VALUES (?,?,?,?,?,?)"
                );
                $s2->bind_param("iiisdd", $vid, $p['producto_id'], $p['cantidad'], $talla, $p['precio'], $sub);
                $s2->execute();

                // Descontar stock de talla si aplica
                if ($talla) {
                    $s3t = $conn->prepare("UPDATE producto_tallas SET stock = stock - ? WHERE producto_id = ? AND talla = ? AND stock >= ?");
                    $s3t->bind_param("iisi", $p['cantidad'], $p['producto_id'], $talla, $p['cantidad']);
                    $s3t->execute();
                }

                $s3 = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?");
                $s3->bind_param("iii", $p['cantidad'], $p['producto_id'], $p['cantidad']);
                $s3->execute();
                if ($conn->affected_rows === 0)
                    throw new Exception("Stock insuficiente para producto ID: " . $p['producto_id']);
            }

            $conn->commit();
            return ['success' => true, 'venta_id' => $vid, 'descuento' => $descuento, 'total' => $total];
        } catch (Exception $e) {
            $conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function countAll() {
        return $this->db->query("SELECT COUNT(*) as t FROM ventas WHERE estado!='cancelada'")->fetch_assoc()['t'];
    }
    public function getTotalHoy() {
        return $this->db->query("SELECT COALESCE(SUM(total),0) as t FROM ventas WHERE DATE(fecha)=CURDATE() AND estado='completada'")->fetch_assoc()['t'];
    }
    public function getTotalMes() {
        return $this->db->query("SELECT COALESCE(SUM(total),0) as t FROM ventas WHERE MONTH(fecha)=MONTH(NOW()) AND YEAR(fecha)=YEAR(NOW()) AND estado='completada'")->fetch_assoc()['t'];
    }
    public function getVentasPorDia($dias = 7) {
        $s = $this->db->prepare(
            "SELECT DATE(fecha) as dia, SUM(total) as total, COUNT(*) as cantidad
             FROM ventas WHERE fecha >= DATE_SUB(NOW(), INTERVAL ? DAY) AND estado='completada'
             GROUP BY DATE(fecha) ORDER BY dia ASC"
        );
        $s->bind_param("i", $dias); $s->execute();
        $r = $s->get_result(); $a = [];
        while($row = $r->fetch_assoc()) $a[] = $row;
        return $a;
    }
    public function getTopProductos($limit = 5) {
        $s = $this->db->prepare(
            "SELECT p.nombre, SUM(dv.cantidad) as total_vendido, SUM(dv.subtotal) as total_ingresos
             FROM detalle_venta dv JOIN productos p ON dv.producto_id=p.id
             JOIN ventas v ON dv.venta_id=v.id WHERE v.estado='completada'
             GROUP BY p.id, p.nombre ORDER BY total_vendido DESC LIMIT ?"
        );
        $s->bind_param("i", $limit); $s->execute();
        $r = $s->get_result(); $a = [];
        while($row = $r->fetch_assoc()) $a[] = $row;
        return $a;
    }

    public function countComprasPorCliente($cliente_id) {
        $s = $this->db->prepare(
            "SELECT COUNT(*) as total FROM ventas WHERE cliente_id = ? AND estado != 'cancelada'"
        );
        $s->bind_param("i", $cliente_id);
        $s->execute();
        $r = $s->get_result();
        return $r->fetch_assoc()['total'] ?? 0;
    }

    public function getComprasPorCliente($cliente_id) {
        $s = $this->db->prepare(
            "SELECT v.*, u.nombre as vendedor_nombre, p.nombre as promocion_nombre
             FROM ventas v
             LEFT JOIN usuarios u ON v.usuario_id = u.id
             LEFT JOIN promociones p ON v.promocion_id = p.id
             WHERE v.cliente_id = ? AND v.estado != 'cancelada'
             ORDER BY v.fecha DESC"
        );
        $s->bind_param("i", $cliente_id);
        $s->execute();
        $r = $s->get_result();
        $a = [];
        while($row = $r->fetch_assoc()) $a[] = $row;
        return $a;
    }

    // Alias para create()
    public function crear($cliente_id, $usuario_id, $productos, $notas = '', $promocion_id = null, $descuento = 0, $total_sin_descuento = 0) {
        return $this->create($cliente_id, $usuario_id, $productos, $notas, $promocion_id, $descuento, $total_sin_descuento);
    }
}
?>
