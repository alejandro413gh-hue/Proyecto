<?php
require_once __DIR__ . '/../config/database.php';
class Categoria {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function getAll() {
        $r = $this->db->query("SELECT * FROM categorias ORDER BY nombre");
        $a=[];while($row=$r->fetch_assoc())$a[]=$row;return $a;
    }
    public function create($n,$d) {
        $s=$this->db->prepare("INSERT INTO categorias(nombre,descripcion)VALUES(?,?)");
        $s->bind_param("ss",$n,$d);return $s->execute();
    }
    public function update($id,$n,$d) {
        $s=$this->db->prepare("UPDATE categorias SET nombre=?,descripcion=? WHERE id=?");
        $s->bind_param("ssi",$n,$d,$id);return $s->execute();
    }
    public function delete($id) {
        $s=$this->db->prepare("DELETE FROM categorias WHERE id=?");
        $s->bind_param("i",$id);return $s->execute();
    }
}
?>
