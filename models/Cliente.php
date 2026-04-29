<?php
require_once __DIR__ . '/../config/database.php';
class Cliente {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function getAll($search='') {
        if (!empty($search)) {
            $like = '%' . $search . '%';
            $s = $this->db->prepare(
                "SELECT c.*, COALESCE(num_facturas, 0) as num_facturas FROM clientes c LEFT JOIN (SELECT v.cliente_id, COUNT(f.id) as num_facturas FROM ventas v LEFT JOIN facturas f ON v.id = f.venta_id GROUP BY v.cliente_id) vf ON c.id = vf.cliente_id WHERE c.nombre LIKE ? OR c.nit LIKE ? OR c.telefono LIKE ? OR c.email LIKE ? ORDER BY c.nombre"
            );
            $s->bind_param("ssss", $like, $like, $like, $like);
            $s->execute();
            $r = $s->get_result();
        } else {
            $r = $this->db->query("SELECT c.*, COALESCE(num_facturas, 0) as num_facturas FROM clientes c LEFT JOIN (SELECT v.cliente_id, COUNT(f.id) as num_facturas FROM ventas v LEFT JOIN facturas f ON v.id = f.venta_id GROUP BY v.cliente_id) vf ON c.id = vf.cliente_id ORDER BY c.nombre");
        }
        $a = []; while($row = $r->fetch_assoc()) $a[] = $row; return $a;
    }
    public function getById($id) {
        $s=$this->db->prepare("SELECT * FROM clientes WHERE id=?");
        $s->bind_param("i",$id);$s->execute();return $s->get_result()->fetch_assoc();
    }
    public function create($n,$nit='',$t='',$e='',$d='',$f='') {
        $s=$this->db->prepare("INSERT INTO clientes(nombre,telefono,email,direccion,factura,nit)VALUES(?,?,?,?,?,?)");
        $s->bind_param("ssssss",$n,$t,$e,$d,$f,$nit);return $s->execute();
    }
    public function update($id,$n,$nit='',$t='',$e='',$d='',$f='') {
        $s=$this->db->prepare("UPDATE clientes SET nombre=?,telefono=?,email=?,direccion=?,factura=?,nit=? WHERE id=?");
        $s->bind_param("ssssssi",$n,$t,$e,$d,$f,$nit,$id);return $s->execute();
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
