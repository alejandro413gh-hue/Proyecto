<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Promocion.php';
require_once __DIR__ . '/Talla.php';

class Venta {
    private $db;
    private Talla $inventario;
    public function __construct() {
        $this->db = Database::getInstance();
        $this->inventario = new Talla();
        $this->crearColumnasSiNoExisten();
        $this->ensureSchema();
    }

    private function crearColumnasSiNoExisten() {
        // Agregar columna promocion_id si no existe
        $check = $this->db->query("SHOW COLUMNS FROM ventas LIKE 'promocion_id'");
        if ($check->num_rows === 0) {
            $this->db->query("ALTER TABLE ventas ADD COLUMN promocion_id INT NULL AFTER usuario_id");
        }

        // Agregar columna sexo si no existe
        $check2 = $this->db->query("SHOW COLUMNS FROM ventas LIKE 'sexo'");
        if ($check2->num_rows === 0) {
            $this->db->query("ALTER TABLE ventas ADD COLUMN sexo ENUM('M', 'F', 'O') DEFAULT 'O' AFTER cliente_id");
        }
    }

    private function ensureSchema() {
        $check = $this->db->query("SHOW COLUMNS FROM ventas LIKE 'tipo_venta'");
        if ($check->num_rows === 0) {
            $this->db->query("ALTER TABLE ventas ADD COLUMN tipo_venta ENUM('fisica','online') NOT NULL DEFAULT 'fisica' AFTER usuario_id");
        }
    }

    public function getAll($limit = 50) {
        $s = $this->db->prepare(
            "SELECT v.*, c.nombre as cliente_nombre, u.nombre as vendedor_nombre
             FROM ventas v
             LEFT JOIN clientes c ON v.cliente_id = c.id
             LEFT JOIN usuarios u ON v.usuario_id = u.id
             ORDER BY v.fecha DESC LIMIT ?"
        );
        $s->bind_param("i", $limit); $s->execute();
        $r = $s->get_result(); $a = [];
        while($row = $r->fetch_assoc()) $a[] = $row;
        return $a;
    }

    public function getById($id) {
        $s = $this->db->prepare(
            "SELECT v.*, c.nombre as cliente_nombre, u.nombre as vendedor_nombre
             FROM ventas v
             LEFT JOIN clientes c ON v.cliente_id = c.id
             LEFT JOIN usuarios u ON v.usuario_id = u.id
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

    public function create($cliente_id, $usuario_id, $productos, $notas = '', $promocion_id = null, $descuento = 0, $total_sin_descuento = 0, $descuento_id = null, $descuento_aplicado = null, $tipo_venta = 'fisica', $estado = 'completada', bool $usarTransaccion = true) {
        $conn = $this->db->getConnection();
        $stockCheck = $this->inventario->validarItems(is_array($productos) ? $productos : []);
        if (!($stockCheck['success'] ?? false)) {
            return [
                'success' => false,
                'error' => $stockCheck['error'] ?? 'No hay suficiente inventario para completar la venta.',
            ];
        }

        if ($usarTransaccion) {
            $conn->begin_transaction();
        }
        try {
            $subtotal = array_sum(array_map(fn($p) => $p['precio'] * $p['cantidad'], $productos));
            $total_sin_descuento = $subtotal;
            $total = max(0, $subtotal - $descuento);

            error_log('Venta -> subtotal: ' . $subtotal . ' descuento: ' . $descuento . ' total: ' . $total . ' cliente_id: ' . $cliente_id);

            $hasCliente = is_numeric($cliente_id) && (int) $cliente_id > 0;
            if ($hasCliente) {
                $sql = "INSERT INTO ventas (cliente_id, usuario_id, promocion_id, descuento, descuento_id, descuento_aplicado, total, notas, total_sin_descuento, tipo_venta, estado)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            } else {
                $sql = "INSERT INTO ventas (cliente_id, usuario_id, promocion_id, descuento, descuento_id, descuento_aplicado, total, notas, total_sin_descuento, tipo_venta, estado)
                        VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            }
            $s = $conn->prepare($sql);
            if ($hasCliente) {
                $s->bind_param("iiidisdsdss", $cliente_id, $usuario_id, $promocion_id, $descuento, $descuento_id, $descuento_aplicado, $total, $notas, $total_sin_descuento, $tipo_venta, $estado);
            } else {
                $s->bind_param("iidisdsdss", $usuario_id, $promocion_id, $descuento, $descuento_id, $descuento_aplicado, $total, $notas, $total_sin_descuento, $tipo_venta, $estado);
            }
            if (!$s->execute()) {
                throw new Exception('No se pudo registrar la venta: ' . $s->error);
            }
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
                if (!$s2) {
                    throw new Exception('No se pudo preparar el detalle de venta: ' . $conn->error);
                }
                $s2->bind_param("iiisdd", $vid, $p['producto_id'], $p['cantidad'], $talla, $p['precio'], $sub);
                if (!$s2->execute()) {
                    throw new Exception('No se pudo registrar el detalle de la venta: ' . $s2->error);
                }

                error_log('Actualizando stock producto: ' . $p['producto_id'] . ' talla: ' . ($talla ?? 'N/A') . ' cantidad: ' . $p['cantidad']);

                if ($talla) {
                    $s3t = $conn->prepare("UPDATE producto_tallas SET stock = stock - ? WHERE producto_id = ? AND talla = ? AND stock >= ?");
                    if (!$s3t) {
                        throw new Exception('No se pudo preparar actualización de talla: ' . $conn->error);
                    }
                    $s3t->bind_param("iisi", $p['cantidad'], $p['producto_id'], $talla, $p['cantidad']);
                    if (!$s3t->execute()) {
                        throw new Exception('No se pudo actualizar el stock por talla: ' . $s3t->error);
                    }
                    if ($s3t->affected_rows === 0) {
                        throw new Exception("Stock insuficiente para talla {$talla} del producto ID: {$p['producto_id']}");
                    }

                    $s3sync = $conn->prepare(
                        "UPDATE productos SET stock = (
                            SELECT COALESCE(SUM(stock),0) FROM producto_tallas WHERE producto_id = ?
                        ) WHERE id = ?"
                    );
                    if (!$s3sync) {
                        throw new Exception('No se pudo preparar sincronización de stock: ' . $conn->error);
                    }
                    $s3sync->bind_param("ii", $p['producto_id'], $p['producto_id']);
                    if (!$s3sync->execute()) {
                        throw new Exception('No se pudo sincronizar el stock del producto: ' . $s3sync->error);
                    }
                } else {
                    $s3 = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?");
                    if (!$s3) {
                        throw new Exception('No se pudo preparar actualización de stock: ' . $conn->error);
                    }
                    $s3->bind_param("iii", $p['cantidad'], $p['producto_id'], $p['cantidad']);
                    if (!$s3->execute()) {
                        throw new Exception('No se pudo actualizar el stock del producto: ' . $s3->error);
                    }
                    if ($s3->affected_rows === 0) {
                        throw new Exception("Stock insuficiente para producto ID: " . $p['producto_id']);
                    }
                }
            }

            if ($usarTransaccion) {
                $conn->commit();
            }
            return ['success' => true, 'venta_id' => $vid, 'total' => $total];
        } catch (Exception $e) {
            if ($usarTransaccion) {
                $conn->rollback();
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Alias para create()
    public function crear($cliente_id, $usuario_id, $productos, $notas = '', $promocion_id = null, $descuento = 0, $total_sin_descuento = 0, $descuento_id = null, $descuento_aplicado = null) {
        return $this->create($cliente_id, $usuario_id, $productos, $notas, $promocion_id, $descuento, $total_sin_descuento, $descuento_id, $descuento_aplicado);
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
}
?>
