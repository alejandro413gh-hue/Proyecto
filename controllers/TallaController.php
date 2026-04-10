<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Talla.php';

class TallaController {
    private $m;
    public function __construct() { requireLogin(); $this->m = new Talla(); }

    public function guardar() {
        requireGestorOAdmin(); // admin y gestor_inventario
        $prod  = intval($_POST['producto_id'] ?? 0);
        $talla = trim($_POST['talla'] ?? '');
        $stock = intval($_POST['stock'] ?? 0);
        if (!$prod || empty($talla)) return ['error' => 'Datos incompletos'];
        if ($stock < 0) return ['error' => 'El stock no puede ser negativo'];
        if ($this->m->guardar($prod, $talla, $stock))
            return ['success' => 'Talla guardada', 'tallas' => $this->m->getPorProducto($prod)];
        return ['error' => 'Error al guardar'];
    }

    public function eliminar() {
        requireGestorOAdmin();
        $id   = intval($_POST['id'] ?? 0);
        $prod = intval($_POST['producto_id'] ?? 0);
        if (!$id) return ['error' => 'ID inválido'];
        if ($this->m->eliminar($id))
            return ['success' => 'Talla eliminada', 'tallas' => $this->m->getPorProducto($prod)];
        return ['error' => 'Error al eliminar'];
    }

    public function getTallas() {
        $prod = intval($_GET['producto_id'] ?? 0);
        if (!$prod) return ['error' => 'Producto requerido'];
        return [
            'tallas'     => $this->m->getPorProducto($prod),
            'disponibles'=> $this->m->getDisponibles($prod)
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $c = new TallaController();
    switch ($_POST['action']) {
        case 'guardar':  echo json_encode($c->guardar());  break;
        case 'eliminar': echo json_encode($c->eliminar()); break;
        default: echo json_encode(['error' => 'Acción inválida']);
    }
    exit();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    $c = new TallaController();
    if ($_GET['action'] === 'get') echo json_encode($c->getTallas());
    exit();
}
?>
