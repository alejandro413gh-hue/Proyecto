<?php
require_once __DIR__ . '/../config/database.php';
class Usuario {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email=? AND activo=1");
        $stmt->bind_param("s",$email); $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT id,nombre,email,rol FROM usuarios WHERE id=?");
        $stmt->bind_param("i",$id); $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    public function verifyPassword($p,$h) { return password_verify($p,$h); }
    public function countAll() {
        return $this->db->query("SELECT COUNT(*) as t FROM usuarios WHERE activo=1")->fetch_assoc()['t'];
    }
}
?>
