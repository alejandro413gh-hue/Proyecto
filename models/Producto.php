<?php
require_once __DIR__ . '/../config/database.php';

class Producto {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function getAll($search = '') {
        // El stock mostrado siempre es la suma real de tallas (si las tiene),
        // o el stock general si no tiene tallas configuradas.
        $base = "SELECT p.*, c.nombre as categoria_nombre,
                    COALESCE(
                        (SELECT SUM(pt.stock) FROM producto_tallas pt WHERE pt.producto_id = p.id),
                        p.stock
                    ) as stock
                 FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id
                 WHERE p.activo = 1";
        if (!empty($search)) {
            $like = '%' . $search . '%';
            $s = $this->db->prepare($base . " AND (p.nombre LIKE ? OR c.nombre LIKE ?) ORDER BY p.id");
            $s->bind_param("ss", $like, $like);
            $s->execute();
            $r = $s->get_result();
        } else {
            $r = $this->db->query($base . " ORDER BY p.id");
        }
        $a = []; while ($row = $r->fetch_assoc()) $a[] = $row; return $a;
    }

    public function getById($id) {
        $s = $this->db->prepare(
            "SELECT p.*,
                    c.nombre as categoria_nombre,
                    COALESCE(
                        (SELECT SUM(pt.stock) FROM producto_tallas pt WHERE pt.producto_id = p.id),
                        p.stock
                    ) as stock
             FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id 
             WHERE p.id = ?"
        );
        $s->bind_param("i", $id); $s->execute();
        return $s->get_result()->fetch_assoc();
    }

    public function create($nombre, $desc, $precio, $stock, $cat, $imagen = null) {
        if ($imagen !== null) {
            $s = $this->db->prepare(
                "INSERT INTO productos (nombre, descripcion, precio, stock, categoria_id, imagen) VALUES (?,?,?,?,?,?)"
            );
            $s->bind_param("ssdiss", $nombre, $desc, $precio, $stock, $cat, $imagen);
        } else {
            $s = $this->db->prepare(
                "INSERT INTO productos (nombre, descripcion, precio, stock, categoria_id) VALUES (?,?,?,?,?)"
            );
            $s->bind_param("ssdii", $nombre, $desc, $precio, $stock, $cat);
        }
        return $s->execute();
    }

    public function update($id, $nombre, $desc, $precio, $stock, $cat, $imagen = null) {
        $conn = $this->db->getConnection();
        if ($imagen) {
            $s = $conn->prepare("UPDATE productos SET nombre=?, descripcion=?, precio=?, stock=?, categoria_id=?, imagen=? WHERE id=?");
            $s->bind_param("ssdissi", $nombre, $desc, $precio, $stock, $cat, $imagen, $id);
        } else {
            $s = $conn->prepare("UPDATE productos SET nombre=?, descripcion=?, precio=?, stock=?, categoria_id=? WHERE id=?");
            $s->bind_param("ssdiii", $nombre, $desc, $precio, $stock, $cat, $id);
        }
        return $s->execute();
    }

    public function updateImagen($id, $imagen) {
        $s = $this->db->prepare("UPDATE productos SET imagen=? WHERE id=?");
        $s->bind_param("si", $imagen, $id);
        return $s->execute();
    }

    public function delete($id) {
        $s = $this->db->prepare("UPDATE productos SET activo=0 WHERE id=?");
        $s->bind_param("i", $id); return $s->execute();
    }

    public function getLowStock($t = 5) {
        $s = $this->db->prepare(
            "SELECT p.*, c.nombre as categoria_nombre,
                    COALESCE(
                        (SELECT SUM(pt.stock) FROM producto_tallas pt WHERE pt.producto_id = p.id),
                        p.stock
                    ) as stock
             FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id 
             WHERE p.activo = 1
             HAVING stock <= ?
             ORDER BY stock"
        );
        $s->bind_param("i", $t); $s->execute();
        $r = $s->get_result(); $a = [];
        while ($row = $r->fetch_assoc()) $a[] = $row; return $a;
    }

    public function countAll() {
        return $this->db->query("SELECT COUNT(*) as t FROM productos WHERE activo=1")->fetch_assoc()['t'];
    }

    public function countLowStock() {
        // Contar productos cuyo stock real (suma de tallas o stock general) sea <= 5
        return (int)$this->db->query(
            "SELECT COUNT(*) as t FROM (
                SELECT COALESCE(
                    (SELECT SUM(pt.stock) FROM producto_tallas pt WHERE pt.producto_id = p.id),
                    p.stock
                ) as stock_real
                FROM productos p WHERE p.activo = 1
             ) AS sub WHERE stock_real <= 5"
        )->fetch_assoc()['t'];
    }

    /** Devuelve la URL de la imagen o null */
    public static function getImageUrl($producto, $baseUrl) {
        if (!empty($producto['imagen'])) {
            return $baseUrl . '/assets/img/productos/' . $producto['imagen'];
        }
        return null;
    }
}
?>
