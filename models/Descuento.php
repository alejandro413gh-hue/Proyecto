<?php
require_once __DIR__ . '/../config/database.php';

class Descuento {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->inicializar();
    }

    private function inicializar() {
        $this->db->query("CREATE TABLE IF NOT EXISTS descuentos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(200) NOT NULL,
            descripcion TEXT,
            tipo_descuento ENUM('porcentaje','monto_fijo') NOT NULL DEFAULT 'porcentaje',
            valor DECIMAL(10,2) NOT NULL,
            aplica_categoria_id INT NULL,
            aplica_producto_id  INT NULL,
            aplica_genero ENUM('dama','caballero','todos') DEFAULT 'todos',
            compras_minimas INT DEFAULT 0,
            fecha_inicio DATE NULL,
            fecha_fin    DATE NULL,
            activo TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");

        $r = $this->db->query("SHOW COLUMNS FROM ventas LIKE 'descuento_id'");
        if ($r->num_rows === 0) {
            $this->db->query("ALTER TABLE ventas ADD COLUMN descuento_id INT NULL AFTER descuento");
            $this->db->query("ALTER TABLE ventas ADD COLUMN descuento_aplicado TEXT NULL AFTER descuento_id");
        }
    }

    public function getAll() {
        $r = $this->db->query("SELECT d.*, c.nombre as cat_nombre, p.nombre as prod_nombre
            FROM descuentos d
            LEFT JOIN categorias c ON d.aplica_categoria_id=c.id
            LEFT JOIN productos  p ON d.aplica_producto_id=p.id
            ORDER BY d.activo DESC, d.nombre");
        $a=[]; while($row=$r->fetch_assoc()) $a[]=$row; return $a;
    }

    public function getById($id) {
        $s=$this->db->prepare("SELECT * FROM descuentos WHERE id=?");
        $s->bind_param("i",$id); $s->execute();
        return $s->get_result()->fetch_assoc();
    }

    public function create($d) {
        $conn=$this->db->getConnection();
        $s=$conn->prepare("INSERT INTO descuentos (nombre,descripcion,tipo_descuento,valor,aplica_categoria_id,aplica_producto_id,aplica_genero,compras_minimas,fecha_inicio,fecha_fin) VALUES(?,?,?,?,?,?,?,?,?,?)");
        $s->bind_param("sssdiiisss",$d['nombre'],$d['descripcion'],$d['tipo_descuento'],$d['valor'],$d['aplica_categoria_id'],$d['aplica_producto_id'],$d['aplica_genero'],$d['compras_minimas'],$d['fecha_inicio'],$d['fecha_fin']);
        return $s->execute();
    }

    public function update($id,$d) {
        $conn=$this->db->getConnection();
        $s=$conn->prepare("UPDATE descuentos SET nombre=?,descripcion=?,tipo_descuento=?,valor=?,aplica_categoria_id=?,aplica_producto_id=?,aplica_genero=?,compras_minimas=?,fecha_inicio=?,fecha_fin=?,activo=? WHERE id=?");
        $s->bind_param("sssdiiisssii",$d['nombre'],$d['descripcion'],$d['tipo_descuento'],$d['valor'],$d['aplica_categoria_id'],$d['aplica_producto_id'],$d['aplica_genero'],$d['compras_minimas'],$d['fecha_inicio'],$d['fecha_fin'],$d['activo'],$id);
        return $s->execute();
    }

    public function delete($id) {
        $s=$this->db->prepare("DELETE FROM descuentos WHERE id=?");
        $s->bind_param("i",$id); return $s->execute();
    }

    public function toggle($id) {
        $s=$this->db->prepare("UPDATE descuentos SET activo=NOT activo WHERE id=?");
        $s->bind_param("i",$id); return $s->execute();
    }

    public function getActivos() {
        $hoy=date('Y-m-d');
        $r=$this->db->query("SELECT d.*,c.nombre as cat_nombre,p.nombre as prod_nombre
            FROM descuentos d
            LEFT JOIN categorias c ON d.aplica_categoria_id=c.id
            LEFT JOIN productos  p ON d.aplica_producto_id=p.id
            WHERE d.activo=1
              AND (d.fecha_inicio IS NULL OR d.fecha_inicio<='$hoy')
              AND (d.fecha_fin   IS NULL OR d.fecha_fin  >='$hoy')
            ORDER BY d.valor DESC");
        $a=[]; while($row=$r->fetch_assoc()) $a[]=$row; return $a;
    }

    /**
     * Dado un carrito de items, cliente_id y genero, devuelve el mejor descuento aplicable
     * items: [{producto_id, categoria_id, precio, cantidad}]
     */
    public function calcularMejor($items, $cliente_id=null, $genero='todos') {
        $hoy=date('Y-m-d');
        $compras=0;
        if ($cliente_id) {
            $s=$this->db->prepare("SELECT COUNT(*) as c FROM ventas WHERE cliente_id=? AND estado='completada'");
            $s->bind_param("i",$cliente_id); $s->execute();
            $compras=(int)$s->get_result()->fetch_assoc()['c'];
        }

        $subtotal=array_sum(array_map(fn($i)=>$i['precio']*$i['cantidad'],$items));
        $r=$this->db->query("SELECT * FROM descuentos WHERE activo=1
            AND (fecha_inicio IS NULL OR fecha_inicio<='$hoy')
            AND (fecha_fin   IS NULL OR fecha_fin  >='$hoy')
            ORDER BY valor DESC");

        $mejorMonto=0; $mejorDesc=null;

        while($d=$r->fetch_assoc()) {
            // Compras mínimas
            if ($d['compras_minimas']>0 && $compras<$d['compras_minimas']) continue;
            // Género
            if ($d['aplica_genero']!=='todos' && $genero!=='todos' && $d['aplica_genero']!==$genero) continue;

            // Base de cálculo
            $base=0;
            if ($d['aplica_producto_id']) {
                foreach($items as $it) {
                    if (($it['producto_id']??0)==$d['aplica_producto_id']) $base+=$it['precio']*$it['cantidad'];
                }
                if ($base==0) continue;
            } elseif ($d['aplica_categoria_id']) {
                foreach($items as $it) {
                    if (($it['categoria_id']??0)==$d['aplica_categoria_id']) $base+=$it['precio']*$it['cantidad'];
                }
                if ($base==0) continue;
            } else {
                $base=$subtotal;
            }

            $monto = $d['tipo_descuento']==='porcentaje'
                ? round($base*$d['valor']/100)
                : min((float)$d['valor'],$base);

            if ($monto>$mejorMonto) {
                $mejorMonto=$monto;
                $mejorDesc=[
                    'id'          =>(int)$d['id'],
                    'nombre'      =>$d['nombre'],
                    'tipo'        =>$d['tipo_descuento'],
                    'valor'       =>(float)$d['valor'],
                    'monto'       =>$monto,
                    'descripcion' =>$d['descripcion'],
                    'genero'      =>$d['aplica_genero'],
                    'etiqueta'    =>$d['nombre'].': '.($d['tipo_descuento']==='porcentaje'?$d['valor'].'%':'$'.number_format($d['valor'],0,',','.'))
                ];
            }
        }
        return $mejorDesc ?: null;
    }

    public function contarActivos() {
        return (int)$this->db->query("SELECT COUNT(*) as c FROM descuentos WHERE activo=1")->fetch_assoc()['c'];
    }
}
?>
