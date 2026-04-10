<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Producto.php';

class ProductoController {
    private $m;
    public function __construct() { requireLogin(); $this->m = new Producto(); }

    public function create() {
        requireGestorOAdmin();
        $nombre = trim($_POST['nombre'] ?? '');
        $desc   = trim($_POST['descripcion'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);
        $stock  = intval($_POST['stock'] ?? 0);
        $cat    = intval($_POST['categoria_id'] ?? 0);
        if (empty($nombre) || $precio <= 0) return ['error' => 'Nombre y precio obligatorios'];
        $conn = Database::getInstance()->getConnection();
        $s = $conn->prepare("INSERT INTO productos (nombre,descripcion,precio,stock,categoria_id) VALUES(?,?,?,?,?)");
        $s->bind_param("ssdii",$nombre,$desc,$precio,$stock,$cat);
        if ($s->execute()) return ['success' => 'Producto registrado'];
        return ['error' => 'Error al registrar'];
    }

    public function update() {
        requireGestorOAdmin();
        $id     = intval($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $desc   = trim($_POST['descripcion'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);
        $stock  = intval($_POST['stock'] ?? 0);
        $cat    = intval($_POST['categoria_id'] ?? 0);
        if ($id <= 0 || empty($nombre) || $precio <= 0) return ['error' => 'Datos inválidos'];
        $conn = Database::getInstance()->getConnection();
        $s = $conn->prepare("UPDATE productos SET nombre=?,descripcion=?,precio=?,stock=?,categoria_id=? WHERE id=?");
        $s->bind_param("ssdiii",$nombre,$desc,$precio,$stock,$cat,$id);
        if ($s->execute()) return ['success' => 'Producto actualizado'];
        return ['error' => 'Error al actualizar'];
    }

    public function delete() {
        requireGestorOAdmin();
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) return ['error' => 'ID inválido'];
        if ($this->m->delete($id)) return ['success' => 'Producto eliminado'];
        return ['error' => 'Error al eliminar'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $c = new ProductoController();
    switch ($_POST['action']) {
        case 'create': echo json_encode($c->create()); break;
        case 'update': echo json_encode($c->update()); break;
        case 'delete': echo json_encode($c->delete()); break;
        default: echo json_encode(['error' => 'Acción inválida']);
    }
    exit();
}
?>
