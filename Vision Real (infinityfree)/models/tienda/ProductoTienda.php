<?php
/**
 * models/tienda/ProductoTienda.php
 * Consultas del catálogo público.
 * NO duplica productos — usa la misma tabla 'productos'.
 * Solo filtra por visible_tienda = 1.
 */
require_once __DIR__ . '/../../config/database.php';

class ProductoTienda {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->asegurarColumnasExtra();
    }

    /**
     * Garantiza que las columnas nuevas existen antes de usarlas.
     * Idempotente: solo agrega si no existen.
     */
    private function asegurarColumnasExtra(): void {
        // visible_tienda
        $r = $this->db->query("SHOW COLUMNS FROM productos LIKE 'visible_tienda'");
        if ($r->num_rows === 0) {
            $this->db->query(
                "ALTER TABLE productos ADD COLUMN visible_tienda TINYINT(1) NOT NULL DEFAULT 0 AFTER activo"
            );
        }
    }

    /* ─── Catálogo público ───────────────────────────────────── */

    /**
     * Listar productos para la tienda con filtros opcionales.
     */
    public function getCatalogo(array $filtros = []): array {
        $busqueda  = trim($filtros['busqueda'] ?? '');
        $categoriaId = (int)($filtros['categoria_id'] ?? 0);
        $sexo        = $filtros['sexo'] ?? '';       // 'dama','caballero','todos'
        $orden       = $filtros['orden'] ?? 'reciente'; // reciente|precio_asc|precio_desc|nombre
        $pagina      = max(1, (int)($filtros['pagina'] ?? 1));
        $porPagina   = 12;

        $where = "WHERE p.activo = 1 AND p.visible_tienda = 1";
        $params = [];
        $types  = '';

        if ($busqueda) {
            $like   = '%' . $busqueda . '%';
            $where .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ? OR c.nombre LIKE ?)";
            $params[] = $like; $params[] = $like; $params[] = $like;
            $types   .= 'sss';
        }
        if ($categoriaId) {
            $where   .= " AND p.categoria_id = ?";
            $params[] = $categoriaId;
            $types   .= 'i';
        }

        $orderMap = [
            'reciente'    => 'p.id DESC',
            'precio_asc'  => 'p.precio ASC',
            'precio_desc' => 'p.precio DESC',
            'nombre'      => 'p.nombre ASC',
        ];
        $orderBy  = $orderMap[$orden] ?? 'p.id DESC';

        $offset = ($pagina - 1) * $porPagina;
        $sql    = "SELECT p.*, c.nombre as categoria_nombre,
                          COALESCE((SELECT SUM(pt.stock) FROM producto_tallas pt WHERE pt.producto_id=p.id), p.stock) as stock_total
                   FROM productos p
                   LEFT JOIN categorias c ON p.categoria_id = c.id
                   {$where}
                   ORDER BY {$orderBy}
                   LIMIT {$porPagina} OFFSET {$offset}";

        // Total para paginación
        $sqlTotal = "SELECT COUNT(*) as t FROM productos p
                     LEFT JOIN categorias c ON p.categoria_id = c.id
                     {$where}";

        if (!empty($params)) {
            $stmtT = $this->db->prepare($sqlTotal);
            $stmtT->bind_param($types, ...$params);
            $stmtT->execute();
            $total = (int)$stmtT->get_result()->fetch_assoc()['t'];

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $r = $stmt->get_result();
        } else {
            $total = (int)$this->db->query($sqlTotal)->fetch_assoc()['t'];
            $r     = $this->db->query($sql);
        }

        $productos = [];
        while ($row = $r->fetch_assoc()) {
            $row['tallas'] = $this->getTallasProducto($row['id']);
            $productos[]   = $row;
        }

        return [
            'productos'   => $productos,
            'total'       => $total,
            'pagina'      => $pagina,
            'por_pagina'  => $porPagina,
            'total_pages' => (int)ceil($total / $porPagina),
        ];
    }

    /**
     * Detalle de un producto para la página de producto.
     */
    public function getDetalle(int $id): ?array {
        $s = $this->db->prepare(
            "SELECT p.*, c.nombre as categoria_nombre,
                    COALESCE((SELECT SUM(pt.stock) FROM producto_tallas pt WHERE pt.producto_id=p.id), p.stock) as stock_total
             FROM productos p
             LEFT JOIN categorias c ON p.categoria_id = c.id
             WHERE p.id=? AND p.activo=1 AND p.visible_tienda=1"
        );
        $s->bind_param("i", $id);
        $s->execute();
        $p = $s->get_result()->fetch_assoc();
        if (!$p) return null;
        $p['tallas'] = $this->getTallasProducto($id);
        return $p;
    }

    /**
     * Tallas de un producto con su stock (para mostrar en tienda).
     */
    public function getTallasProducto(int $productoId): array {
        $s = $this->db->prepare(
            "SELECT talla, stock FROM producto_tallas
             WHERE producto_id=?
             ORDER BY
               FIELD(talla,'XS','S','M','L','XL','XXL','XXXL','Único') DESC,
               CAST(talla AS UNSIGNED) ASC,
               talla ASC"
        );
        $s->bind_param("i", $productoId);
        $s->execute();
        $r = $s->get_result();
        $a = [];
        while ($row = $r->fetch_assoc()) $a[] = $row;
        return $a;
    }

    /**
     * Productos destacados / más vendidos para el banner.
     */
    public function getDestacados(int $limit = 8): array {
        $r = $this->db->prepare(
            "SELECT p.*, c.nombre as categoria_nombre,
                    COALESCE(SUM(dv.cantidad),0) as total_vendido,
                    COALESCE((SELECT SUM(pt.stock) FROM producto_tallas pt WHERE pt.producto_id=p.id), p.stock) as stock_total
             FROM productos p
             LEFT JOIN categorias c ON p.categoria_id = c.id
             LEFT JOIN detalle_venta dv ON dv.producto_id = p.id
             LEFT JOIN ventas v ON v.id = dv.venta_id AND v.estado='completada'
             WHERE p.activo=1 AND p.visible_tienda=1
             GROUP BY p.id
             ORDER BY total_vendido DESC, p.id DESC
             LIMIT ?"
        );
        $r->bind_param("i", $limit);
        $r->execute();
        $rs = $r->get_result();
        $a = [];
        while ($row = $rs->fetch_assoc()) {
            $row['tallas'] = $this->getTallasProducto($row['id']);
            $a[] = $row;
        }
        return $a;
    }

    /**
     * Categorías que tienen productos visibles en tienda.
     */
    public function getCategoriasActivas(): array {
        $r = $this->db->query(
            "SELECT c.id, c.nombre, COUNT(p.id) as total
             FROM categorias c
             JOIN productos p ON p.categoria_id=c.id AND p.activo=1 AND p.visible_tienda=1
             GROUP BY c.id, c.nombre
             ORDER BY c.nombre"
        );
        $a = [];
        while ($row = $r->fetch_assoc()) $a[] = $row;
        return $a;
    }

    /**
     * Admin: activar/desactivar visibilidad en tienda.
     */
    public function toggleVisible(int $id): bool {
        $s = $this->db->prepare("UPDATE productos SET visible_tienda=NOT visible_tienda WHERE id=?");
        $s->bind_param("i", $id);
        return $s->execute();
    }

    /**
     * Admin: marcar varios productos como visibles en tienda.
     */
    public function activarTodos(): void {
        $this->db->query("UPDATE productos SET visible_tienda=1 WHERE activo=1");
    }

    /**
     * Estadísticas tienda vs físico para dashboard admin.
     */
    public function statsVentasPorTipo(): array {
        $r = $this->db->query(
            "SELECT
                COALESCE(tipo_venta,'fisica') as tipo,
                COUNT(*) as total_ventas,
                COALESCE(SUM(total),0) as total_monto
             FROM ventas
             WHERE estado NOT IN('cancelada')
             GROUP BY tipo_venta"
        );
        $a = ['fisica' => ['ventas' => 0, 'monto' => 0], 'online' => ['ventas' => 0, 'monto' => 0]];
        while ($row = $r->fetch_assoc()) {
            $t = $row['tipo'] ?: 'fisica';
            $a[$t] = ['ventas' => (int)$row['total_ventas'], 'monto' => (float)$row['total_monto']];
        }
        return $a;
    }
}
