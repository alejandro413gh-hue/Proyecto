<?php
require_once __DIR__ . '/../config/database.php';

class Producto {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->agregarColumnaCodigo();
        $this->ensurePosSchema();
    }

    private function ensurePosSchema(): void {
        $conn = $this->db->getConnection();

        $columns = [
            'codigo_barras' => "ALTER TABLE productos ADD COLUMN codigo_barras VARCHAR(50) NULL AFTER codigo",
            'referencia' => "ALTER TABLE productos ADD COLUMN referencia VARCHAR(80) NULL AFTER codigo_barras",
            'costo' => "ALTER TABLE productos ADD COLUMN costo DECIMAL(10,2) NULL DEFAULT NULL AFTER precio",
        ];

        foreach ($columns as $column => $sql) {
            $check = $conn->query("SHOW COLUMNS FROM productos LIKE '" . $conn->real_escape_string($column) . "'");
            if ($check && $check->num_rows === 0) {
                @$conn->query($sql);
            }
        }

        $indexes = [
            'idx_productos_categoria_id' => 'ALTER TABLE productos ADD INDEX idx_productos_categoria_id (categoria_id)',
            'idx_productos_nombre' => 'ALTER TABLE productos ADD INDEX idx_productos_nombre (nombre(100))',
            'idx_productos_codigo_barras' => 'ALTER TABLE productos ADD INDEX idx_productos_codigo_barras (codigo_barras)',
            'idx_productos_referencia' => 'ALTER TABLE productos ADD INDEX idx_productos_referencia (referencia)',
        ];

        foreach ($indexes as $indexName => $sql) {
            $check = $conn->query("SHOW INDEX FROM productos WHERE Key_name = '" . $conn->real_escape_string($indexName) . "'");
            if ($check && $check->num_rows === 0) {
                @$conn->query($sql);
            }
        }

        @$conn->query(
            "CREATE TABLE IF NOT EXISTS producto_favoritos (
                producto_id INT NOT NULL PRIMARY KEY,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
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

    private function bindParams(mysqli_stmt $stmt, string $types, array $params): bool {
        if ($types === '' || empty($params)) {
            return true;
        }

        $bind = [$types];
        foreach ($params as $k => $v) {
            $bind[$k + 1] = &$params[$k];
        }

        return call_user_func_array([$stmt, 'bind_param'], $bind);
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
             WHERE p.activo = 1 AND (
                p.codigo = ? OR p.codigo_barras = ? OR p.referencia = ?
             )"
        );
        $s->bind_param("sss", $codigo, $codigo, $codigo);
        $s->execute();
        return $s->get_result()->fetch_assoc();
    }

    public function getPosCategories(): array {
        $r = $this->db->query(
            "SELECT c.id, c.nombre, COUNT(p.id) as total
             FROM categorias c
             LEFT JOIN productos p ON p.categoria_id = c.id AND p.activo = 1 AND p.visible_tienda = 1
             GROUP BY c.id, c.nombre
             ORDER BY c.nombre ASC"
        );
        $a = [];
        while ($r && ($row = $r->fetch_assoc())) {
            $a[] = [
                'id' => (int) $row['id'],
                'nombre' => (string) $row['nombre'],
                'total' => (int) $row['total'],
            ];
        }
        return $a;
    }

    public function getFavoriteIds(): array {
        $r = $this->db->query("SELECT producto_id FROM producto_favoritos ORDER BY created_at DESC");
        $ids = [];
        while ($r && ($row = $r->fetch_assoc())) {
            $ids[] = (int) ($row['producto_id'] ?? 0);
        }
        return $ids;
    }

    public function toggleFavorito(int $productoId): bool {
        $exists = $this->db->prepare("SELECT producto_id FROM producto_favoritos WHERE producto_id = ?");
        $exists->bind_param("i", $productoId);
        $exists->execute();
        $row = $exists->get_result()->fetch_assoc();

        if ($row) {
            $del = $this->db->prepare("DELETE FROM producto_favoritos WHERE producto_id = ?");
            $del->bind_param("i", $productoId);
            return $del->execute();
        }

        $ins = $this->db->prepare("INSERT INTO producto_favoritos (producto_id) VALUES (?)");
        $ins->bind_param("i", $productoId);
        return $ins->execute();
    }

    public function getPosProducts(array $filters = []): array {
        $q = trim((string) ($filters['q'] ?? ''));
        $categoriaId = (int) ($filters['categoria_id'] ?? 0);
        $order = strtolower(trim((string) ($filters['order'] ?? 'popular')));
        $limit = max(1, min(300, (int) ($filters['limit'] ?? 120)));
        $offset = max(0, (int) ($filters['offset'] ?? 0));

        $where = "WHERE p.activo = 1 AND p.visible_tienda = 1";
        $params = [];
        $types = '';

        if ($categoriaId > 0) {
            $where .= " AND p.categoria_id = ?";
            $params[] = $categoriaId;
            $types .= 'i';
        }

        if ($q !== '') {
            $where .= " AND (p.codigo LIKE ? OR p.codigo_barras LIKE ? OR p.referencia LIKE ? OR p.nombre LIKE ? OR p.descripcion LIKE ?)";
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like, $like);
            $types .= 'sssss';
        }

        $orderBy = match ($order) {
            'recent' => "p.created_at DESC, p.id DESC",
            'favorites' => "favorito DESC, vendidos DESC, p.created_at DESC",
            'alpha' => "p.nombre ASC",
            'stock' => "stock DESC, p.nombre ASC",
            default => "favorito DESC, vendidos DESC, p.created_at DESC, p.id DESC",
        };

        $sql = "
           SELECT p.id, p.codigo, p.codigo_barras, p.referencia, p.nombre, p.descripcion, p.precio, p.costo, p.stock,
                   p.categoria_id, p.imagen, p.created_at, c.nombre AS categoria_nombre,
                   COALESCE((SELECT COUNT(*) FROM producto_tallas pt4 WHERE pt4.producto_id = p.id), 0) AS tallas_count,
                   COALESCE((SELECT SUM(pt.stock) FROM producto_tallas pt WHERE pt.producto_id = p.id), p.stock) AS stock_real,
                   CASE WHEN EXISTS(SELECT 1 FROM producto_tallas pt2 WHERE pt2.producto_id = p.id) THEN 1 ELSE 0 END AS tiene_tallas,
                   COALESCE((
                       SELECT pt3.talla
                       FROM producto_tallas pt3
                       WHERE pt3.producto_id = p.id AND pt3.stock > 0
                       ORDER BY pt3.stock DESC, pt3.id ASC
                       LIMIT 1
                   ), '') AS talla_defecto,
                   COALESCE(vt.vendidos, 0) AS vendidos,
                   CASE WHEN pf.producto_id IS NULL THEN 0 ELSE 1 END AS favorito
            FROM productos p
            LEFT JOIN categorias c ON c.id = p.categoria_id
            LEFT JOIN (
                SELECT dv.producto_id, SUM(dv.cantidad) AS vendidos
                FROM detalle_venta dv
                JOIN ventas v ON v.id = dv.venta_id AND v.estado = 'completada'
                GROUP BY dv.producto_id
            ) vt ON vt.producto_id = p.id
            LEFT JOIN producto_favoritos pf ON pf.producto_id = p.id
            $where
            ORDER BY $orderBy
            LIMIT $limit OFFSET $offset
        ";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $this->bindParams($stmt, $types, $params);
            $stmt->execute();
            $r = $stmt->get_result();
        } else {
            $r = $this->db->query($sql);
        }

        $rows = [];
        while ($r && ($row = $r->fetch_assoc())) {
            $row['id'] = (int) $row['id'];
            $row['categoria_id'] = (int) ($row['categoria_id'] ?? 0);
            $row['precio'] = (float) ($row['precio'] ?? 0);
            $row['costo'] = isset($row['costo']) ? (float) $row['costo'] : null;
            $row['stock'] = (int) ($row['stock_real'] ?? $row['stock'] ?? 0);
            $row['vendidos'] = (int) ($row['vendidos'] ?? 0);
            $row['favorito'] = (bool) ($row['favorito'] ?? false);
            $row['tiene_tallas'] = (bool) ($row['tiene_tallas'] ?? false);
            $row['tallas_count'] = (int) ($row['tallas_count'] ?? 0);
            $row['talla_defecto'] = (string) ($row['talla_defecto'] ?? '');
            $row['imagen_url'] = self::getImageUrl($row, BASE_URL) ?: (BASE_URL . '/tienda/assets/img/sin-imagen.svg');
            $rows[] = $row;
        }

        return $rows;
    }

    public function buscarPos(string $term, int $limit = 20): array {
        return $this->getPosProducts([
            'q' => $term,
            'limit' => $limit,
            'order' => 'popular',
        ]);
    }

    public function getBySearchTerm(string $term) {
        $like = '%' . $term . '%';
        $s = $this->db->prepare(
            "SELECT p.*, c.nombre as categoria_nombre,
                    COALESCE((SELECT SUM(pt.stock) FROM producto_tallas pt WHERE pt.producto_id = p.id), p.stock) as stock
             FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id
             WHERE p.activo = 1 AND (
                p.codigo = ? OR p.codigo_barras = ? OR p.referencia = ? OR
                p.nombre LIKE ? OR p.descripcion LIKE ?
             )
             ORDER BY p.nombre ASC
             LIMIT 1"
        );
        $s->bind_param("sssss", $term, $term, $term, $like, $like);
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
