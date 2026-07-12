<?php
require_once __DIR__ . '/../config/database.php';

class Usuario {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function getAll() {
        $r = $this->db->query("SELECT id, nombre, email, rol, activo, created_at FROM usuarios ORDER BY rol ASC, nombre ASC");
        $a = []; while($row=$r->fetch_assoc()) $a[]=$row; return $a;
    }

    public function findById($id) {
        $s = $this->db->prepare("SELECT id, nombre, email, rol, activo FROM usuarios WHERE id=?");
        $s->bind_param("i",$id); $s->execute();
        return $s->get_result()->fetch_assoc();
    }

    public function findByEmail($email) {
        $s = $this->db->prepare("SELECT * FROM usuarios WHERE email=? AND activo=1");
        $s->bind_param("s",$email); $s->execute();
        return $s->get_result()->fetch_assoc();
    }

    public function emailExiste($email, $excluir_id = 0) {
        $s = $this->db->prepare("SELECT id FROM usuarios WHERE email=? AND id!=?");
        $s->bind_param("si",$email,$excluir_id); $s->execute();
        return $s->get_result()->num_rows > 0;
    }

    public function create($nombre, $email, $password, $rol) {
        if ($this->emailExiste($email)) return ['error' => 'El correo ya está registrado'];
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $s = $this->db->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?,?,?,?)");
        $s->bind_param("ssss",$nombre,$email,$hash,$rol);
        if ($s->execute()) return ['success' => true, 'id' => $this->db->lastInsertId()];
        return ['error' => 'Error al crear usuario'];
    }

    public function update($id, $nombre, $email, $rol, $activo) {
        if ($this->emailExiste($email, $id)) return ['error' => 'El correo ya está en uso'];
        $s = $this->db->prepare("UPDATE usuarios SET nombre=?, email=?, rol=?, activo=? WHERE id=?");
        $s->bind_param("sssii",$nombre,$email,$rol,$activo,$id);
        if ($s->execute()) return ['success' => true];
        return ['error' => 'Error al actualizar'];
    }

    public function cambiarPassword($id, $nueva) {
        $hash = password_hash($nueva, PASSWORD_BCRYPT);
        $s = $this->db->prepare("UPDATE usuarios SET password=? WHERE id=?");
        $s->bind_param("si",$hash,$id);
        return $s->execute();
    }

    public function verificarPassword($id, $password) {
        $s = $this->db->prepare("SELECT password FROM usuarios WHERE id=?");
        $s->bind_param("i",$id); $s->execute();
        $r = $s->get_result()->fetch_assoc();
        return $r && password_verify($password, $r['password']);
    }

    public function toggleActivo($id) {
        $s = $this->db->prepare("UPDATE usuarios SET activo = NOT activo WHERE id=?");
        $s->bind_param("i",$id); return $s->execute();
    }

    public function delete($id) {
        // Solo desactivar, no borrar (para mantener historial de ventas)
        $s = $this->db->prepare("UPDATE usuarios SET activo=0 WHERE id=?");
        $s->bind_param("i",$id); return $s->execute();
    }

    public function verifyPassword($p,$h) { return password_verify($p,$h); }

    public function countAll() {
        return (int)$this->db->query("SELECT COUNT(*) as t FROM usuarios WHERE activo=1")->fetch_assoc()['t'];
    }

    public function getVentasPorUsuario($usuario_id, $limit=50) {
        $s = $this->db->prepare(
            "SELECT v.*, c.nombre as cliente_nombre
             FROM ventas v
             LEFT JOIN clientes c ON v.cliente_id=c.id
             WHERE v.usuario_id=?
             ORDER BY v.fecha DESC
             LIMIT ?"
        );
        $s->bind_param("ii",$usuario_id,$limit); $s->execute();
        $r=$s->get_result(); $a=[];
        while($row=$r->fetch_assoc()) $a[]=$row; return $a;
    }

    public function getResumenVentas($usuario_id) {
        $s = $this->db->prepare(
            "SELECT COUNT(*) as total_ventas, COALESCE(SUM(total),0) as total_monto
             FROM ventas WHERE usuario_id=? AND estado='completada'"
        );
        $s->bind_param("i",$usuario_id); $s->execute();
        return $s->get_result()->fetch_assoc();
    }
}
?>
