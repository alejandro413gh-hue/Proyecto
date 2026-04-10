<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Descuento.php';

class DescuentoController {
    private $m;
    public function __construct() { requireLogin(); $this->m=new Descuento(); }

    private function sanitize() {
        $cat  = intval($_POST['aplica_categoria_id']??0) ?: null;
        $prod = intval($_POST['aplica_producto_id']??0)  ?: null;
        $fi   = trim($_POST['fecha_inicio']??'') ?: null;
        $ff   = trim($_POST['fecha_fin']??'')    ?: null;
        return [
            'nombre'              => trim($_POST['nombre']??''),
            'descripcion'         => trim($_POST['descripcion']??''),
            'tipo_descuento'      => in_array($_POST['tipo_descuento']??'',['porcentaje','monto_fijo']) ? $_POST['tipo_descuento'] : 'porcentaje',
            'valor'               => floatval($_POST['valor']??0),
            'aplica_categoria_id' => $cat,
            'aplica_producto_id'  => $prod,
            'aplica_genero'       => in_array($_POST['aplica_genero']??'',['dama','caballero','todos']) ? $_POST['aplica_genero'] : 'todos',
            'compras_minimas'     => intval($_POST['compras_minimas']??0),
            'fecha_inicio'        => $fi,
            'fecha_fin'           => $ff,
            'activo'              => intval($_POST['activo']??1),
        ];
    }

    public function create() {
        requireAdmin();
        $d=$this->sanitize();
        if(empty($d['nombre'])||$d['valor']<=0) return['error'=>'Nombre y valor son obligatorios'];
        if($this->m->create($d)) return['success'=>'Descuento creado correctamente'];
        return['error'=>'Error al crear'];
    }

    public function update() {
        requireAdmin();
        $id=intval($_POST['id']??0);
        if($id<=0) return['error'=>'ID inválido'];
        $d=$this->sanitize();
        if(empty($d['nombre'])||$d['valor']<=0) return['error'=>'Datos inválidos'];
        if($this->m->update($id,$d)) return['success'=>'Descuento actualizado'];
        return['error'=>'Error al actualizar'];
    }

    public function delete() {
        requireAdmin();
        $id=intval($_POST['id']??0);
        if($this->m->delete($id)) return['success'=>'Descuento eliminado'];
        return['error'=>'Error al eliminar'];
    }

    public function toggle() {
        requireAdmin();
        $id=intval($_POST['id']??0);
        if($this->m->toggle($id)) return['success'=>'Estado actualizado'];
        return['error'=>'Error'];
    }

    /** Endpoint AJAX: calcular mejor descuento para un carrito */
    public function calcular() {
        $items      = json_decode($_POST['items']??'[]',true);
        $cliente_id = intval($_POST['cliente_id']??0)?:null;
        $genero     = trim($_POST['genero']??'todos');
        if(empty($items)) return['descuento'=>null];
        $mejor=$this->m->calcularMejor($items,$cliente_id,$genero);
        return['descuento'=>$mejor];
    }

    /** Endpoint público (sin login) para tienda */
    public function calcularPublico() {
        $items      = json_decode($_POST['items']??'[]',true);
        $cliente_id = intval($_POST['cliente_id']??0)?:null;
        $genero     = trim($_POST['genero']??'todos');
        if(empty($items)) return['descuento'=>null];
        $mejor=$this->m->calcularMejor($items,$cliente_id,$genero);
        return['descuento'=>$mejor];
    }
}

if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['action'])){
    header('Content-Type: application/json');
    $c=new DescuentoController();
    switch($_POST['action']){
        case'create':   echo json_encode($c->create());   break;
        case'update':   echo json_encode($c->update());   break;
        case'delete':   echo json_encode($c->delete());   break;
        case'toggle':   echo json_encode($c->toggle());   break;
        case'calcular': echo json_encode($c->calcular()); break;
        default: echo json_encode(['error'=>'Acción inválida']);
    }
    exit();
}
?>
