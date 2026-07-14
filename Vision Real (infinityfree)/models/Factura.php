<?php
require_once __DIR__ . '/../config/database.php';

class Factura {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->crearTabla();
        $this->normalizarEsquema();
    }

    private function crearTabla() {
        $sql = "CREATE TABLE IF NOT EXISTS facturas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            venta_id INT NOT NULL UNIQUE,
            numero_factura VARCHAR(50) UNIQUE NOT NULL,
            cliente_nombre VARCHAR(100) NOT NULL,
            cliente_documento VARCHAR(50) NOT NULL,
            cliente_telefono VARCHAR(50) DEFAULT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            descuento DECIMAL(10,2) DEFAULT 0,
            total DECIMAL(10,2) NOT NULL,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            estado VARCHAR(20) DEFAULT 'generada',
            FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
            INDEX (numero_factura),
            INDEX (cliente_documento)
        )";
        $this->db->query($sql);
        
        // Agregar columna subtotal si no existe
        $check = $this->db->query("SHOW COLUMNS FROM facturas LIKE 'subtotal'");
        if ($check->num_rows === 0) {
            $this->db->query("ALTER TABLE facturas ADD COLUMN subtotal DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER cliente_documento");
        }
        
        // Agregar columna descuento si no existe
        $check2 = $this->db->query("SHOW COLUMNS FROM facturas LIKE 'descuento'");
        if ($check2->num_rows === 0) {
            $this->db->query("ALTER TABLE facturas ADD COLUMN descuento DECIMAL(10,2) DEFAULT 0 AFTER subtotal");
        }

        $check3 = $this->db->query("SHOW COLUMNS FROM facturas LIKE 'cliente_telefono'");
        if ($check3->num_rows === 0) {
            $this->db->query("ALTER TABLE facturas ADD COLUMN cliente_telefono VARCHAR(50) DEFAULT NULL AFTER cliente_documento");
        }
    }

    private function normalizarEsquema(): void {
        $this->db->query("ALTER TABLE facturas ENGINE=InnoDB");
        $this->db->ensureTableCollation('facturas');
        if (!$this->tieneForeignKey('facturas', 'venta_id', 'ventas', 'id')) {
            $this->db->query("ALTER TABLE facturas ADD CONSTRAINT facturas_ibfk_1 FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE");
        }
    }

    private function tieneForeignKey(string $table, string $column, string $refTable, string $refColumn): bool {
        $dbName = DB_NAME;
        $s = $this->db->prepare(
            "SELECT COUNT(*) as c
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME = ?
               AND REFERENCED_COLUMN_NAME = ?"
        );
        if (!$s) {
            return false;
        }
        $s->bind_param('sssss', $dbName, $table, $column, $refTable, $refColumn);
        $s->execute();
        $r = $s->get_result();
        $row = $r ? $r->fetch_assoc() : null;
        return !empty($row['c']);
    }

    public function generarNumeroFactura() {
        $fecha = date('Ymd');
        $r = $this->db->query(
            "SELECT COUNT(*) as total FROM facturas 
             WHERE DATE(fecha) = CURDATE()"
        );
        $count = $r->fetch_assoc()['total'] + 1;
        return 'FAC-' . $fecha . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    public function crear($venta_id, $cliente_nombre, $cliente_documento, $subtotal, $descuento, $total, $cliente_telefono = '') {
        $numero = $this->generarNumeroFactura();
        $s = $this->db->prepare(
            "INSERT INTO facturas 
             (venta_id, numero_factura, cliente_nombre, cliente_documento, cliente_telefono, subtotal, descuento, total) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $s->bind_param(
            "issssddd",
            $venta_id, $numero, $cliente_nombre, $cliente_documento, $cliente_telefono, $subtotal, $descuento, $total
        );
        
        if ($s->execute()) {
            return [
                'success' => true,
                'numero_factura' => $numero,
                'id' => $this->db->getConnection()->insert_id
            ];
        }
        return ['success' => false, 'error' => 'Error al crear factura: ' . $s->error];
    }

    public function getByVentaId($venta_id) {
        $s = $this->db->prepare(
            "SELECT * FROM facturas WHERE venta_id = ?"
        );
        $s->bind_param("i", $venta_id);
        $s->execute();
        return $s->get_result()->fetch_assoc();
    }

    public function getByNumero($numero) {
        $s = $this->db->prepare(
            "SELECT * FROM facturas WHERE numero_factura = ?"
        );
        $s->bind_param("s", $numero);
        $s->execute();
        return $s->get_result()->fetch_assoc();
    }

    public function getAll($limit = 50) {
        $s = $this->db->prepare(
            "SELECT * FROM facturas ORDER BY fecha DESC LIMIT ?"
        );
        $s->bind_param("i", $limit);
        $s->execute();
        $r = $s->get_result();
        $a = [];
        while ($row = $r->fetch_assoc()) $a[] = $row;
        return $a;
    }

    public function getByClienteId($cliente_id) {
        $s = $this->db->prepare(
            "SELECT f.* FROM facturas f
             JOIN ventas v ON f.venta_id = v.id
             WHERE v.cliente_id = ?
             ORDER BY f.fecha DESC"
        );
        $s->bind_param("i", $cliente_id);
        $s->execute();
        $r = $s->get_result();
        $a = [];
        while ($row = $r->fetch_assoc()) $a[] = $row;
        return $a;
    }
}
?>
