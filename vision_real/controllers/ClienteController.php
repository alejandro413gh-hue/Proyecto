<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Cliente.php';

class ClienteController {
    private $m;
    public function __construct() { requireLogin(); $this->m=new Cliente(); }

    public function create() {
        $n=trim($_POST['nombre']??'');$t=trim($_POST['telefono']??'');
        $e=trim($_POST['email']??'');$d=trim($_POST['direccion']??'');
        if(empty($n)) return['error'=>'Nombre obligatorio'];
        if($this->m->create($n,$t,$e,$d)) return['success'=>'Cliente registrado'];
        return['error'=>'Error al registrar'];
    }
    public function update() {
        $id=intval($_POST['id']??0);$n=trim($_POST['nombre']??'');
        $t=trim($_POST['telefono']??'');$e=trim($_POST['email']??'');$d=trim($_POST['direccion']??'');
        if($id<=0||empty($n)) return['error'=>'Datos inválidos'];
        if($this->m->update($id,$n,$t,$e,$d)) return['success'=>'Cliente actualizado'];
        return['error'=>'Error al actualizar'];
    }
    public function delete() {
        $id=intval($_POST['id']??0);
        if($id<=0) return['error'=>'ID inválido'];
        if($this->m->delete($id)) return['success'=>'Cliente eliminado'];
        return['error'=>'Error al eliminar'];
    }
}

if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['action'])){
    header('Content-Type: application/json');
    $c=new ClienteController();
    switch($_POST['action']){
        case'create':echo json_encode($c->create());break;
        case'update':echo json_encode($c->update());break;
        case'delete':echo json_encode($c->delete());break;
        default:echo json_encode(['error'=>'Acción inválida']);
    }
    exit();
}
?>
