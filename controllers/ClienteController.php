<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Cliente.php';

class ClienteController {
    private $m;
    public function __construct() { 
        requireLogin(); 
        $this->m = new Cliente(); 
    }

    // ===== CREAR CLIENTE (solo desde módulo admin, NO desde POS) =====
    public function create() {
        $n = trim($_POST['nombre'] ?? '');
        $t = trim($_POST['telefono'] ?? '');
        $e = trim($_POST['email'] ?? '');
        $d = trim($_POST['direccion'] ?? '');
        if (empty($n)) return ['error' => 'Nombre obligatorio'];
        if ($this->m->create($n, $t, $e, $d)) return ['success' => 'Cliente registrado'];
        return ['error' => 'Error al registrar'];
    }

    // ===== ACTUALIZAR CLIENTE =====
    public function update() {
        $id = intval($_POST['id'] ?? 0);
        $n = trim($_POST['nombre'] ?? '');
        $t = trim($_POST['telefono'] ?? '');
        $e = trim($_POST['email'] ?? '');
        $d = trim($_POST['direccion'] ?? '');
        if ($id <= 0 || empty($n)) return ['error' => 'Datos inválidos'];
        if ($this->m->update($id, $n, $t, $e, $d)) return ['success' => 'Cliente actualizado'];
        return ['error' => 'Error al actualizar'];
    }

    // ===== ELIMINAR CLIENTE =====
    public function delete() {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) return ['error' => 'ID inválido'];
        if ($this->m->delete($id)) return ['success' => 'Cliente eliminado'];
        return ['error' => 'Error al eliminar'];
    }

    // ===== BUSCAR O CREAR CLIENTE (WORKFLOW POS) =====
    public function buscarOCrear() {
        $documento = trim($_GET['documento'] ?? '');
        $nombre = trim($_GET['nombre'] ?? '');
        
        if (empty($documento) && empty($nombre)) {
            return ['error' => 'Ingrese documento o nombre'];
        }

        // Buscar por documento o nombre
        $cliente = null;
        if (!empty($documento)) {
            // Buscar por teléfono como identificador único
            $clientes = $this->m->getAll();
            $cliente = array_values(array_filter($clientes, function($c) use ($documento) {
                return $c['telefono'] === $documento;
            }))[0] ?? null;
        }

        if (!$cliente && !empty($nombre)) {
            $clientes = $this->m->getAll();
            $cliente = array_values(array_filter($clientes, function($c) use ($nombre) {
                return strtolower($c['nombre']) === strtolower($nombre);
            }))[0] ?? null;
        }

        if ($cliente) {
            return [
                'encontrado' => true,
                'id' => $cliente['id'],
                'nombre' => $cliente['nombre'],
                'documento' => $cliente['telefono'],
                'email' => $cliente['email'],
                'direccion' => $cliente['direccion']
            ];
        }

        // No encontrado: devolver datos para que el usuario complete
        return [
            'encontrado' => false,
            'documento' => $documento,
            'nombre' => $nombre,
            'mensaje' => 'Cliente no existe. Complete los datos para registrarlo.'
        ];
    }

    // ===== CREAR CLIENTE DESDE POS =====
    public function crearDesdeVenta() {
        $n = trim($_POST['nombre'] ?? '');
        $documento = trim($_POST['documento'] ?? '');
        $e = trim($_POST['email'] ?? '');
        $d = trim($_POST['direccion'] ?? '');

        if (empty($n)) return ['error' => 'El nombre es obligatorio'];
        if (empty($documento)) return ['error' => 'El documento/teléfono es obligatorio'];

        // Validar que no exista
        $clientes = $this->m->getAll();
        $existe = array_filter($clientes, function($c) use ($documento) {
            return $c['telefono'] === $documento;
        });
        
        if (!empty($existe)) {
            return ['error' => 'Este documento ya está registrado'];
        }

        if ($this->m->create($n, $documento, $e, $d)) {
            $db = Database::getInstance();
            $id = $db->insert_id;
            return [
                'success' => true,
                'id' => $id,
                'nombre' => $n,
                'documento' => $documento
            ];
        }
        return ['error' => 'Error al crear el cliente'];
    }
}

    // ===== VALIDAR CLIENTE ANTES DE VENTA =====
    public function validarParaVenta() {
        $cliente_id = intval($_POST['cliente_id'] ?? 0);
        if ($cliente_id <= 0) return ['error' => 'Cliente es requerido'];
        
        $cliente = $this->m->getById($cliente_id);
        if (!$cliente) return ['error' => 'Cliente no encontrado'];
        
        return ['success' => true, 'cliente' => $cliente];
    }
}

if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['action'])){
    header('Content-Type: application/json');
    $c=new ClienteController();
    switch($_POST['action']){
        case'create':echo json_encode($c->create());break;
        case'update':echo json_encode($c->update());break;
        case'delete':echo json_encode($c->delete());break;
        case'crear_desde_venta':echo json_encode($c->crearDesdeVenta());break;
        case'validar_para_venta':echo json_encode($c->validarParaVenta());break;
        default:echo json_encode(['error'=>'Acción inválida']);
    }
    exit();
}

if($_SERVER['REQUEST_METHOD']==='GET'&&isset($_GET['action'])){
    header('Content-Type: application/json');
    $c=new ClienteController();
    switch($_GET['action']){
        case'buscar_o_crear':echo json_encode($c->buscarOCrear());break;
        default:echo json_encode(['error'=>'Acción inválida']);
    }
    exit();
}
?>
