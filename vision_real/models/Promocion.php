<?php
require_once __DIR__ . '/../config/database.php';

class Promocion {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function getAll() {
        $r = $this->db->query("SELECT * FROM promociones ORDER BY compras_minimas ASC");
        $a = []; while($row = $r->fetch_assoc()) $a[] = $row; return $a;
    }

    public function getActivas() {
        $r = $this->db->query("SELECT * FROM promociones WHERE activa = 1 ORDER BY compras_minimas ASC");
        $a = []; while($row = $r->fetch_assoc()) $a[] = $row; return $a;
    }

    public function getById($id) {
        $s = $this->db->prepare("SELECT * FROM promociones WHERE id = ?");
        $s->bind_param("i", $id); $s->execute();
        return $s->get_result()->fetch_assoc();
    }

    /** Devuelve las promociones a las que tiene derecho un cliente según sus compras previas */
    public function getDisponiblesParaCliente($cliente_id) {
        if (!$cliente_id) return [];
        // Contar compras completadas del cliente
        $s = $this->db->prepare(
            "SELECT COUNT(*) as total FROM ventas WHERE cliente_id = ? AND estado = 'completada'"
        );
        $s->bind_param("i", $cliente_id); $s->execute();
        $compras = (int)$s->get_result()->fetch_assoc()['total'];

        // Traer promociones activas que apliquen
        $s2 = $this->db->prepare(
            "SELECT * FROM promociones WHERE activa = 1 AND compras_minimas <= ? ORDER BY valor DESC"
        );
        $s2->bind_param("i", $compras); $s2->execute();
        $r = $s2->get_result();
        $a = []; while($row = $r->fetch_assoc()) $a[] = $row;
        return ['compras' => $compras, 'promociones' => $a];
    }

    public function create($nombre, $descripcion, $tipo, $valor, $compras_minimas) {
        $s = $this->db->prepare(
            "INSERT INTO promociones (nombre, descripcion, tipo, valor, compras_minimas) VALUES (?,?,?,?,?)"
        );
        $s->bind_param("sssdi", $nombre, $descripcion, $tipo, $valor, $compras_minimas);
        return $s->execute();
    }

    public function update($id, $nombre, $descripcion, $tipo, $valor, $compras_minimas, $activa) {
        $s = $this->db->prepare(
            "UPDATE promociones SET nombre=?, descripcion=?, tipo=?, valor=?, compras_minimas=?, activa=? WHERE id=?"
        );
        $s->bind_param("sssdiii", $nombre, $descripcion, $tipo, $valor, $compras_minimas, $activa, $id);
        return $s->execute();
    }

    public function delete($id) {
        $s = $this->db->prepare("DELETE FROM promociones WHERE id = ?");
        $s->bind_param("i", $id); return $s->execute();
    }

    public function toggleActiva($id) {
        $s = $this->db->prepare("UPDATE promociones SET activa = NOT activa WHERE id = ?");
        $s->bind_param("i", $id); return $s->execute();
    }

    public function calcularDescuento($promocion_id, $subtotal) {
        $p = $this->getById($promocion_id);
        if (!$p) return 0;
        if ($p['tipo'] === 'porcentaje') {
            return round($subtotal * ($p['valor'] / 100), 0);
        } else {
            return min($p['valor'], $subtotal); // descuento fijo, no mayor que el total
        }
    }
}
?>
