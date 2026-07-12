<?php
require_once __DIR__ . '/../config/database.php';

class Promocion {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance();
        $this->crearTabla();
    }

    private function crearTabla() {
        $sql = "CREATE TABLE IF NOT EXISTS promociones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            descripcion TEXT,
            tipo ENUM('porcentaje', 'fijo') NOT NULL DEFAULT 'porcentaje',
            valor DECIMAL(10,2) NOT NULL,
            fecha_inicio DATETIME,
            fecha_fin DATETIME,
            aplica_sexo ENUM('todos', 'masculino', 'femenino') DEFAULT 'todos',
            activa BOOLEAN DEFAULT 1,
            creada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            actualizada TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    public function getAll() {
        $r = $this->db->query("SELECT * FROM promociones WHERE activa = 1 ORDER BY nombre");
        $a = [];
        while($row = $r->fetch_assoc()) $a[] = $row;
        return $a;
    }

    public function getById($id) {
        $s = $this->db->prepare("SELECT * FROM promociones WHERE id = ?");
        $s->bind_param("i", $id);
        $s->execute();
        return $s->get_result()->fetch_assoc();
    }

    public function create($nombre, $descripcion, $tipo, $valor, $fecha_inicio = null, $fecha_fin = null, $aplica_sexo = 'todos') {
        $s = $this->db->prepare(
            "INSERT INTO promociones (nombre, descripcion, tipo, valor, fecha_inicio, fecha_fin, aplica_sexo)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $s->bind_param("sssdsss", $nombre, $descripcion, $tipo, $valor, $fecha_inicio, $fecha_fin, $aplica_sexo);
        if ($s->execute()) {
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        }
        return ['success' => false, 'error' => 'Error al crear promoción'];
    }

    public function update($id, $nombre, $descripcion, $tipo, $valor, $fecha_inicio = null, $fecha_fin = null, $aplica_sexo = 'todos') {
        $s = $this->db->prepare(
            "UPDATE promociones SET nombre = ?, descripcion = ?, tipo = ?, valor = ?, fecha_inicio = ?, fecha_fin = ?, aplica_sexo = ? WHERE id = ?"
        );
        $s->bind_param("sssdsssi", $nombre, $descripcion, $tipo, $valor, $fecha_inicio, $fecha_fin, $aplica_sexo, $id);
        return $s->execute();
    }

    public function delete($id) {
        $s = $this->db->prepare("UPDATE promociones SET activa = 0 WHERE id = ?");
        $s->bind_param("i", $id);
        return $s->execute();
    }

    public function calcularDescuento($promocion_id, $total) {
        $promo = $this->getById($promocion_id);
        if (!$promo) return 0;

        if ($promo['tipo'] === 'porcentaje') {
            return ($total * $promo['valor']) / 100;
        } else {
            return $promo['valor'];
        }
    }

    public function getAplicables($sexo_cliente = 'O', $total = 0) {
        $ahora = date('Y-m-d H:i:s');
        $sexo_map = ['M' => 'masculino', 'F' => 'femenino', 'O' => 'todos']; // O defaults to todos
        $sexo_promo = $sexo_map[$sexo_cliente] ?? 'todos';

        $s = $this->db->prepare(
            "SELECT * FROM promociones 
             WHERE activa = 1 
             AND (fecha_inicio IS NULL OR fecha_inicio <= ?) 
             AND (fecha_fin IS NULL OR fecha_fin >= ?)
             AND (aplica_sexo = 'todos' OR aplica_sexo = ?)
             ORDER BY valor DESC"
        );
        $s->bind_param("sss", $ahora, $ahora, $sexo_promo);
        $s->execute();
        $r = $s->get_result();
        $a = [];
        while($row = $r->fetch_assoc()) $a[] = $row;
        return $a;
    }
}
?>