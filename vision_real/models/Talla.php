<?php
require_once __DIR__ . '/../config/database.php';

class Talla {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->crearTablasSiNoExisten();
    }

    private function crearTablasSiNoExisten() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS producto_tallas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                producto_id INT NOT NULL,
                talla VARCHAR(20) NOT NULL,
                stock INT NOT NULL DEFAULT 0,
                UNIQUE KEY uk_prod_talla (producto_id, talla),
                FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
            ) ENGINE=InnoDB
        ");
        // Agregar columna talla a detalle_venta si no existe
        $check = $this->db->query("SHOW COLUMNS FROM detalle_venta LIKE 'talla'");
        if ($check->num_rows === 0) {
            $this->db->query("ALTER TABLE detalle_venta ADD COLUMN talla VARCHAR(20) NULL AFTER cantidad");
        }
    }

    /** Obtener todas las tallas de un producto */
    public function getPorProducto($producto_id) {
        $s = $this->db->prepare(
            "SELECT * FROM producto_tallas WHERE producto_id = ? ORDER BY 
             FIELD(talla,'XS','S','M','L','XL','XXL','XXXL','Único') DESC,
             CAST(talla AS UNSIGNED) ASC,
             talla ASC"
        );
        $s->bind_param("i", $producto_id);
        $s->execute();
        $r = $s->get_result();
        $a = []; while($row = $r->fetch_assoc()) $a[] = $row;
        return $a;
    }

    /** Obtener tallas con stock > 0 */
    public function getDisponibles($producto_id) {
        $s = $this->db->prepare(
            "SELECT * FROM producto_tallas WHERE producto_id = ? AND stock > 0
             ORDER BY FIELD(talla,'XS','S','M','L','XL','XXL','XXXL','Único') DESC,
             CAST(talla AS UNSIGNED) ASC, talla ASC"
        );
        $s->bind_param("i", $producto_id);
        $s->execute();
        $r = $s->get_result();
        $a = []; while($row = $r->fetch_assoc()) $a[] = $row;
        return $a;
    }

    /** Guardar o actualizar una talla */
    public function guardar($producto_id, $talla, $stock) {
        $talla = strtoupper(trim($talla));
        if (empty($talla)) return false;
        $s = $this->db->prepare(
            "INSERT INTO producto_tallas (producto_id, talla, stock) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE stock = ?"
        );
        $s->bind_param("isii", $producto_id, $talla, $stock, $stock);
        return $s->execute();
    }

    /** Eliminar una talla */
    public function eliminar($id) {
        $s = $this->db->prepare("DELETE FROM producto_tallas WHERE id = ?");
        $s->bind_param("i", $id);
        return $s->execute();
    }

    /** Descontar stock de una talla */
    public function descontarStock($producto_id, $talla, $cantidad) {
        $s = $this->db->prepare(
            "UPDATE producto_tallas SET stock = stock - ? 
             WHERE producto_id = ? AND talla = ? AND stock >= ?"
        );
        $s->bind_param("iisi", $cantidad, $producto_id, $talla, $cantidad);
        $s->execute();
        return $this->db->getConnection()->affected_rows > 0;
    }

    /** Verificar stock disponible de una talla */
    public function getStock($producto_id, $talla) {
        $s = $this->db->prepare(
            "SELECT stock FROM producto_tallas WHERE producto_id = ? AND talla = ?"
        );
        $s->bind_param("is", $producto_id, $talla);
        $s->execute();
        $r = $s->get_result()->fetch_assoc();
        return $r ? (int)$r['stock'] : 0;
    }

    /** Stock total de todas las tallas de un producto */
    public function getStockTotal($producto_id) {
        $s = $this->db->prepare(
            "SELECT COALESCE(SUM(stock),0) as total FROM producto_tallas WHERE producto_id = ?"
        );
        $s->bind_param("i", $producto_id);
        $s->execute();
        return (int)$s->get_result()->fetch_assoc()['total'];
    }

    /** ¿Tiene tallas configuradas? */
    public function tieneTallas($producto_id) {
        $s = $this->db->prepare("SELECT COUNT(*) as c FROM producto_tallas WHERE producto_id = ?");
        $s->bind_param("i", $producto_id);
        $s->execute();
        return (int)$s->get_result()->fetch_assoc()['c'] > 0;
    }

    /** Todos los productos con sus tallas (para inventario) */
    public function getInventarioCompleto() {
        $r = $this->db->query(
            "SELECT p.id, p.nombre, p.precio, p.stock as stock_general,
                    c.nombre as categoria_nombre,
                    pt.id as talla_id, pt.talla, pt.stock as stock_talla
             FROM productos p
             LEFT JOIN categorias c ON p.categoria_id = c.id
             LEFT JOIN producto_tallas pt ON p.id = pt.producto_id
             WHERE p.activo = 1
             ORDER BY p.nombre, pt.talla"
        );
        $productos = [];
        while($row = $r->fetch_assoc()) {
            $pid = $row['id'];
            if (!isset($productos[$pid])) {
                $productos[$pid] = [
                    'id'               => $pid,
                    'nombre'           => $row['nombre'],
                    'precio'           => $row['precio'],
                    'stock_general'    => $row['stock_general'],
                    'categoria_nombre' => $row['categoria_nombre'],
                    'tallas'           => []
                ];
            }
            if ($row['talla_id']) {
                $productos[$pid]['tallas'][] = [
                    'id'    => $row['talla_id'],
                    'talla' => $row['talla'],
                    'stock' => $row['stock_talla']
                ];
            }
        }
        return array_values($productos);
    }
}
?>
