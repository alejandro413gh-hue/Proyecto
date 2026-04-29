<?php
require_once __DIR__ . '/../config/database.php';

class Producto {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance();
        $this->agregarColumnaCodigo();
    }

    private function agregarColumnaCodigo() {
        $r = $this->db->query("SHOW COLUMNS FROM productos LIKE 'codigo'");
        if ($r->num_rows === 0) {
            $this->db->query("ALTER TABLE productos ADD COLUMN codigo VARCHAR(20) NULL UNIQUE AFTER id");
            $this->db->query("UPDATE productos SET codigo = CONCAT('VR-', LPAD(id, 4, '0')) WHERE codigo IS NULL");
        }
    }

    public function generarCodigo() {
        $r = $this->db->query("SELECT MAX(CAST(SUBSTRING(codigo, 4) AS UNSIGNED)) as max_num FROM productos WHERE codigo LIKE 'VR-%'");
        $row = $r->fetch_assoc();
        $siguiente = ($row['max_num'] ?? 0) + 1;
        return 'VR-' . str_pad($siguiente, 4, '0', STR_PAD_LEFT);
    }

    public function getByCodigo($codigo) {
        $s = $this->db->prepare(
            "SELECT p.*, c.nombre as categoria_nombre,
                    COALESCE((SELECT SUM(pt.stock) FROM producto_tallas pt WHERE pt.producto_id = p.id), p.stock) as stock
             FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id
             WHERE p.codigo = ? AND p.activo = 1"
        );
        $s->bind_param("s", $codigo); $s->execute();
        return $s->get_result()->fetch_assoc();
    }

    public function codigoExiste($codigo, $excluir_id = 0) {
        $s = $this->db->prepare("SELECT id FROM productos WHERE codigo = ? AND id != ?");
        $s->bind_param("si", $codigo, $excluir_id); $s->execute();
        return $s->get_result()->num_rows > 0;
    }

    public function getAll($search = '') {
        $base = "SELECT p.*, c.nombre as categoria_nombre,
                    COALESCE((SELECT SUM(pt.stock) FROM producto_tallas pt WHERE pt.producto_id = p.id), p.stock) as stock
                 FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id WHERE p.activo = 1";
        if (!empty($search)) {
            $like = '%' . $search . '%';
            $s = $this->db->prepare($base . " AND (p.nombre LIKE ? OR p.codigo LIKE ? OR c.nombre LIKE ?) ORDER BY p.codigo");
            $s->bind_param("sss", $like, $like, $like); $s->execute();
            $r = $s->get_result();
        } else {
            $r = $this->db->query($base . " ORDER BY p.codigo");
        }
        $a = []; while ($row = $r->fetch_assoc()) $a[] = $row; return $a;
    }

    public function getById($id) {
        $s = $this->db->prepare(
            "SELECT p.*, c.nombre as categoria_nombre,
                    COALESCE((SELECT SUM(pt.stock) FROM producto_tallas pt WHERE pt.producto_id = p.id), p.stock) as stock
             FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id WHERE p.id = ?"
        );
        $s->bind_param("i", $id); $s->execute();
        return $s->get_result()->fetch_assoc();
    }

    public function create($nombre, $desc, $precio, $stock, $cat, $imagen = null, $codigo = null) {
        if (empty($codigo)) $codigo = $this->generarCodigo();
        if ($this->codigoExiste($codigo)) return ['error' => 'El código ' . $codigo . ' ya está en uso'];
        $conn = $this->db->getConnection();
        if ($imagen !== null) {
            $s = $conn->prepare("INSERT INTO productos (codigo,nombre,descripcion,precio,stock,categoria_id,imagen) VALUES (?,?,?,?,?,?,?)");
            $s->bind_param("sssdiss", $codigo, $nombre, $desc, $precio, $stock, $cat, $imagen);
        } else {
            $s = $conn->prepare("INSERT INTO productos (codigo,nombre,descripcion,precio,stock,categoria_id) VALUES (?,?,?,?,?,?)");
            $s->bind_param("sssdii", $codigo, $nombre, $desc, $precio, $stock, $cat);
        }
        if ($s->execute()) return ['success' => true, 'codigo' => $codigo, 'id' => $conn->insert_id];
        return ['error' => 'Error al crear el producto'];
    }

    public function update($id, $nombre, $desc, $precio, $stock, $cat, $imagen = null, $codigo = null) {
        $conn = $this->db->getConnection();
        if (!empty($codigo) && $this->codigoExiste($codigo, $id))
            return ['error' => 'El código ' . $codigo . ' ya está en uso'];
        if ($imagen) {
            $s = $conn->prepare("UPDATE productos SET codigo=?,nombre=?,descripcion=?,precio=?,stock=?,categoria_id=?,imagen=? WHERE id=?");
            $s->bind_param("sssdissi", $codigo, $nombre, $desc, $precio, $stock, $cat, $imagen, $id);
        } else {
            $s = $conn->prepare("UPDATE productos SET codigo=?,nombre=?,descripcion=?,precio=?,stock=?,categoria_id=? WHERE id=?");
            $s->bind_param("sssdiii", $codigo, $nombre, $desc, $precio, $stock, $cat, $id);
        }
        if ($s->execute()) return ['success' => true];
        return ['error' => 'Error al actualizar'];
    }

    public function updatePrecio($id, $precio) {
        $s = $this->db->prepare("UPDATE productos SET precio=? WHERE id=?");
        $s->bind_param("di", $precio, $id); return $s->execute();
    }

    public function updateImagen($id, $imagen) {
        $s = $this->db->prepare("UPDATE productos SET imagen=? WHERE id=?");
        $s->bind_param("si", $imagen, $id); return $s->execute();
    }

    public function delete($id) {
        $s = $this->db->prepare("UPDATE productos SET activo=0 WHERE id=?");
        $s->bind_param("i", $id); return $s->execute();
    }

    public function getLowStock($t = 5) {
        $s = $this->db->prepare(
            "SELECT p.*, c.nombre as categoria_nombre,
                    COALESCE((SELECT SUM(pt.stock) FROM producto_tallas pt WHERE pt.producto_id = p.id), p.stock) as stock
             FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id
             WHERE p.activo = 1 HAVING stock <= ? ORDER BY stock"
        );
        $s->bind_param("i", $t); $s->execute();
        $r = $s->get_result(); $a = [];
        while ($row = $r->fetch_assoc()) $a[] = $row; return $a;
    }

    public function countAll() {
        return $this->db->query("SELECT COUNT(*) as t FROM productos WHERE activo=1")->fetch_assoc()['t'];
    }

    public function countLowStock() {
        return (int)$this->db->query(
            "SELECT COUNT(*) as t FROM (
                SELECT COALESCE((SELECT SUM(pt.stock) FROM producto_tallas pt WHERE pt.producto_id = p.id), p.stock) as stock_real
                FROM productos p WHERE p.activo = 1
             ) AS sub WHERE stock_real <= 5"
        )->fetch_assoc()['t'];
    }

    public static function getImageUrl($producto, $baseUrl) {
        return !empty($producto['imagen']) ? $baseUrl . '/assets/img/productos/' . $producto['imagen'] : null;
    }
}
?>
