<?php
require_once __DIR__ . '/../config/database.php';

class Producto {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function getAll($search = '') {
        if (!empty($search)) {
            $like = '%' . $search . '%';
            $s = $this->db->prepare(
                "SELECT p.*, c.nombre as categoria_nombre 
                 FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id 
                 WHERE p.activo = 1 AND (p.nombre LIKE ? OR c.nombre LIKE ?)
                 ORDER BY p.nombre"
            );
            $s->bind_param("ss", $like, $like);
            $s->execute();
            $r = $s->get_result();
        } else {
            $r = $this->db->query(
                "SELECT p.*, c.nombre as categoria_nombre 
                 FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id 
                 WHERE p.activo = 1 ORDER BY p.nombre"
            );
        }
        $a = []; while ($row = $r->fetch_assoc()) $a[] = $row; return $a;
    }

    public function getById($id) {
        $s = $this->db->prepare(
            "SELECT p.*, c.nombre as categoria_nombre 
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
            "SELECT p.*, c.nombre as categoria_nombre 
             FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id 
             WHERE p.activo=1 AND p.stock<=? ORDER BY p.stock"
        );
        $s->bind_param("i", $t); $s->execute();
        $r = $s->get_result(); $a = [];
        while ($row = $r->fetch_assoc()) $a[] = $row; return $a;
    }

    public function countAll() {
        return $this->db->query("SELECT COUNT(*) as t FROM productos WHERE activo=1")->fetch_assoc()['t'];
    }

    public function countLowStock() {
        return $this->db->query("SELECT COUNT(*) as t FROM productos WHERE activo=1 AND stock<=5")->fetch_assoc()['t'];
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
