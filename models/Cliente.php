<?php
require_once __DIR__ . '/../config/database.php';
class Cliente {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function getAll($search='') {
        $sql="SELECT * FROM clientes";
        if(!empty($search)){$s=$this->db->escape($search);$sql.=" WHERE nombre LIKE '%$s%' OR telefono LIKE '%$s%' OR email LIKE '%$s%'";}
        $sql.=" ORDER BY nombre";
        $r=$this->db->query($sql);$a=[];while($row=$r->fetch_assoc())$a[]=$row;return $a;
    }
    public function getById($id) {
        $s=$this->db->prepare("SELECT * FROM clientes WHERE id=?");
        $s->bind_param("i",$id);$s->execute();return $s->get_result()->fetch_assoc();
    }
    public function create($n,$t,$e,$d='') {
        $s=$this->db->prepare("INSERT INTO clientes(nombre,telefono,email,direccion)VALUES(?,?,?,?)");
        $s->bind_param("ssss",$n,$t,$e,$d);return $s->execute();
    }
    public function update($id,$n,$t,$e,$d) {
        $s=$this->db->prepare("UPDATE clientes SET nombre=?,telefono=?,email=?,direccion=? WHERE id=?");
        $s->bind_param("ssssi",$n,$t,$e,$d,$id);return $s->execute();
    }
    public function delete($id) {
        $s=$this->db->prepare("DELETE FROM clientes WHERE id=?");
        $s->bind_param("i",$id);return $s->execute();
    }
    public function countAll() {
        return $this->db->query("SELECT COUNT(*) as t FROM clientes")->fetch_assoc()['t'];
    }
}
?>
