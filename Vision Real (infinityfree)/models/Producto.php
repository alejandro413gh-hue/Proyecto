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
        if ($r && $r->num_rows === 0) {
            $this->db->query("ALTER TABLE productos ADD COLUMN codigo VARCHAR(20) NULL UNIQUE AFTER id");
            $this->db->query("UPDATE productos SET codigo = CONCAT('VR-', LPAD(id, 4, '0')) WHERE codigo IS NULL");
        }
    }

    private function formatearCodigo(int $numero): string {
        return 'VR-' . str_pad((string) $numero, 4, '0', STR_PAD_LEFT);
    }

    private function codigoTemporal(string $prefijo, int $id): string {
        $micro = substr(str_replace('.', '', (string) microtime(true)), -8);
        $codigo = $prefijo . str_pad((string) $id, 4, '0', STR_PAD_LEFT) . $micro;
        return substr($codigo, 0, 20);
    }

    public function renumerarCodigosActivos(): bool {
        $conn = $this->db->getConnection();

        try {
            $inactivos = $conn->query("SELECT id FROM productos WHERE activo = 0 AND codigo REGEXP '^VR-[0-9]+$' ORDER BY id ASC");
            if ($inactivos) {
                while ($row = $inactivos->fetch_assoc()) {
                    $id = (int) ($row['id'] ?? 0);
                    if ($id > 0) {
                        $tmp = $this->codigoTemporal('DEL', $id);
                        $s = $conn->prepare("UPDATE productos SET codigo = ? WHERE id = ?");
                        $s->bind_param('si', $tmp, $id);
                        $s->execute();
                    }
                }
            }

            $activos = $conn->query("SELECT id FROM productos WHERE activo = 1 ORDER BY id ASC");
            if (!$activos) {
                throw new RuntimeException($conn->error ?: 'No se pudieron leer los productos activos.');
            }

            $ids = [];
            while ($row = $activos->fetch_assoc()) {
                $ids[] = (int) $row['id'];
            }

            foreach ($ids as $id) {
                $tmp = $this->codigoTemporal('TMP', $id);
                $s = $conn->prepare("UPDATE productos SET codigo = ? WHERE id = ?");
                $s->bind_param('si', $tmp, $id);
                $s->execute();
            }

            foreach ($ids as $index => $id) {
                $codigo = $this->formatearCodigo($index + 1);
                $s = $conn->prepare("UPDATE productos SET codigo = ? WHERE id = ?");
                $s->bind_param('si', $codigo, $id);
                $s->execute();
            }

            return true;
        } catch (Throwable $e) {
            error_log('Producto::renumerarCodigosActivos: ' . $e->getMessage());
            return false;
        }
    }

    public function generarCodigo() {
        $this->renumerarCodigosActivos();
        $r = $this->db->query(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(codigo, 4) AS UNSIGNED)), 0) AS max_num
             FROM productos
             WHERE activo = 1 AND codigo REGEXP '^VR-[0-9]+$'"
        );
        $row = $r ? $r->fetch_assoc() : null;
        $siguiente = ((int) ($row['max_num'] ?? 0)) + 1;
        return $this->formatearCodigo($siguiente);
    }

    public function getByCodigo($codigo) {
        $s = $this->db->prepare(
            "SELECT p.*, c.nombre as categoria_nombre,
                    COALESCE((SELECT SUM(pt.stock) FROM producto_tallas pt WHERE pt.producto_id = p.id), p.stock) as stock
             FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id
             WHERE p.codigo = ? AND p.activo = 1"
        );
        $s->bind_param("s", $codigo);
        $s->execute();
        return $s->get_result()->fetch_assoc();
    }

    public function codigoExiste($codigo, $excluir_id = 0) {
        $s = $this->db->prepare("SELECT id FROM productos WHERE codigo = ? AND id != ?");
        $s->bind_param("si", $codigo, $excluir_id);
        $s->execute();
        $r = $s->get_result();
        return $r ? $r->num_rows > 0 : false;
    }

    public function getAll($search = '') {
        $base = "SELECT p.*, c.nombre as categoria_nombre,
                    COALESCE((SELECT SUM(pt.stock) FROM producto_tallas pt WHERE pt.producto_id = p.id), p.stock) as stock
                 FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id WHERE p.activo = 1";
        if (!empty($search)) {
            $like = '%' . $search . '%';
            $s = $this->db->prepare($base . " AND (p.nombre LIKE ? OR p.codigo LIKE ? OR c.nombre LIKE ?) ORDER BY p.codigo");
            $s->bind_param("sss", $like, $like, $like);
            $s->execute();
            $r = $s->get_result();
        } else {
            $r = $this->db->query($base . " ORDER BY p.codigo");
        }
        $a = [];
        while ($r && ($row = $r->fetch_assoc())) {
            $a[] = $row;
        }
        return $a;
    }

    public function getById($id) {
        $s = $this->db->prepare(
            "SELECT p.*, c.nombre as categoria_nombre,
                    COALESCE((SELECT SUM(pt.stock) FROM producto_tallas pt WHERE pt.producto_id = p.id), p.stock) as stock
             FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id WHERE p.id = ?"
        );
        $s->bind_param("i", $id);
        $s->execute();
        return $s->get_result()->fetch_assoc();
    }

    public function create($nombre, $desc, $precio, $stock, $cat, $imagen = null, $codigo = null) {
        $codigo = $this->generarCodigo();
        $conn = $this->db->getConnection();

        if ($imagen !== null) {
            $s = $conn->prepare("INSERT INTO productos (codigo,nombre,descripcion,precio,stock,categoria_id,imagen) VALUES (?,?,?,?,?,?,?)");
            $s->bind_param("sssdiis", $codigo, $nombre, $desc, $precio, $stock, $cat, $imagen);
        } else {
            $s = $conn->prepare("INSERT INTO productos (codigo,nombre,descripcion,precio,stock,categoria_id) VALUES (?,?,?,?,?,?)");
            $s->bind_param("sssdii", $codigo, $nombre, $desc, $precio, $stock, $cat);
        }

        if ($s->execute()) {
            return ['success' => true, 'codigo' => $codigo, 'id' => $conn->insert_id];
        }
        return ['error' => 'Error al crear el producto'];
    }

    public function update($id, $nombre, $desc, $precio, $stock, $cat, $imagen = null, $codigo = null) {
        $conn = $this->db->getConnection();

        if ($imagen) {
            $s = $conn->prepare("UPDATE productos SET nombre=?,descripcion=?,precio=?,stock=?,categoria_id=?,imagen=? WHERE id=?");
            $s->bind_param("ssdissi", $nombre, $desc, $precio, $stock, $cat, $imagen, $id);
        } else {
            $s = $conn->prepare("UPDATE productos SET nombre=?,descripcion=?,precio=?,stock=?,categoria_id=? WHERE id=?");
            $s->bind_param("ssdiii", $nombre, $desc, $precio, $stock, $cat, $id);
        }

        if ($s->execute()) {
            return ['success' => true];
        }
        return ['error' => 'Error al actualizar'];
    }

    public function updatePrecio($id, $precio) {
        $s = $this->db->prepare("UPDATE productos SET precio=? WHERE id=?");
        $s->bind_param("di", $precio, $id);
        return $s->execute();
    }

    public function updateImagen($id, $imagen) {
        $s = $this->db->prepare("UPDATE productos SET imagen=? WHERE id=?");
        $s->bind_param("si", $imagen, $id);
        return $s->execute();
    }

    public function delete($id) {
        $conn = $this->db->getConnection();
        $conn->begin_transaction();

        try {
            $s = $conn->prepare("SELECT id FROM productos WHERE id = ?");
            $s->bind_param("i", $id);
            $s->execute();
            $existe = $s->get_result()->fetch_assoc();

            if (!$existe) {
                $conn->rollback();
                return false;
            }

            $codigoArchivado = $this->codigoTemporal('DEL', $id);
            $s = $conn->prepare("UPDATE productos SET activo = 0, codigo = ? WHERE id = ?");
            $s->bind_param("si", $codigoArchivado, $id);
            if (!$s->execute()) {
                throw new RuntimeException('No se pudo desactivar el producto.');
            }

            if (!$this->renumerarCodigosActivos()) {
                throw new RuntimeException('No se pudieron renumerar los codigos.');
            }

            $conn->commit();
            return true;
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('Producto::delete: ' . $e->getMessage());
            return false;
        }
    }

    public function getLowStock($t = 5) {
        $s = $this->db->prepare(
            "SELECT p.*, c.nombre as categoria_nombre,
                    COALESCE((SELECT SUM(pt.stock) FROM producto_tallas pt WHERE pt.producto_id = p.id), p.stock) as stock
             FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id
             WHERE p.activo = 1 HAVING stock <= ? ORDER BY stock"
        );
        $s->bind_param("i", $t);
        $s->execute();
        $r = $s->get_result();
        $a = [];
        while ($r && ($row = $r->fetch_assoc())) {
            $a[] = $row;
        }
        return $a;
    }

    public function countAll() {
        $r = $this->db->query("SELECT COUNT(*) as t FROM productos WHERE activo=1");
        $row = $r ? $r->fetch_assoc() : null;
        return (int) ($row['t'] ?? 0);
    }

    public function countLowStock() {
        $r = $this->db->query(
            "SELECT COUNT(*) as t FROM (
                SELECT COALESCE((SELECT SUM(pt.stock) FROM producto_tallas pt WHERE pt.producto_id = p.id), p.stock) as stock_real
                FROM productos p WHERE p.activo = 1
             ) AS sub WHERE stock_real <= 5"
        );
        $row = $r ? $r->fetch_assoc() : null;
        return (int) ($row['t'] ?? 0);
    }

    public static function getImageUrl($producto, $baseUrl) {
        return !empty($producto['imagen']) ? $baseUrl . '/assets/img/productos/' . $producto['imagen'] : null;
    }
}
?>
