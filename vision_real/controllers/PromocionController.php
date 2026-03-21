<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Promocion.php';

class PromocionController {
    private $m;
    public function __construct() { requireLogin(); $this->m = new Promocion(); }

    public function create() {
        requireAdmin();
        $nombre   = trim($_POST['nombre'] ?? '');
        $desc     = trim($_POST['descripcion'] ?? '');
        $tipo     = $_POST['tipo'] ?? 'porcentaje';
        $valor    = floatval($_POST['valor'] ?? 0);
        $compras  = intval($_POST['compras_minimas'] ?? 1);
        if (empty($nombre) || $valor <= 0 || $compras < 1)
            return ['error' => 'Completa todos los campos correctamente'];
        if (!in_array($tipo, ['porcentaje', 'monto_fijo']))
            return ['error' => 'Tipo inválido'];
        if ($tipo === 'porcentaje' && $valor > 100)
            return ['error' => 'El porcentaje no puede ser mayor a 100'];
        if ($this->m->create($nombre, $desc, $tipo, $valor, $compras))
            return ['success' => 'Promoción creada correctamente'];
        return ['error' => 'Error al crear promoción'];
    }

    public function update() {
        requireAdmin();
        $id      = intval($_POST['id'] ?? 0);
        $nombre  = trim($_POST['nombre'] ?? '');
        $desc    = trim($_POST['descripcion'] ?? '');
        $tipo    = $_POST['tipo'] ?? 'porcentaje';
        $valor   = floatval($_POST['valor'] ?? 0);
        $compras = intval($_POST['compras_minimas'] ?? 1);
        $activa  = intval($_POST['activa'] ?? 1);
        if ($id <= 0 || empty($nombre) || $valor <= 0)
            return ['error' => 'Datos inválidos'];
        if ($this->m->update($id, $nombre, $desc, $tipo, $valor, $compras, $activa))
            return ['success' => 'Promoción actualizada'];
        return ['error' => 'Error al actualizar'];
    }

    public function delete() {
        requireAdmin();
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) return ['error' => 'ID inválido'];
        if ($this->m->delete($id)) return ['success' => 'Promoción eliminada'];
        return ['error' => 'Error al eliminar'];
    }

    public function toggle() {
        requireAdmin();
        $id = intval($_POST['id'] ?? 0);
        if ($this->m->toggleActiva($id)) return ['success' => 'Estado actualizado'];
        return ['error' => 'Error'];
    }

    /** Endpoint para obtener promociones disponibles para un cliente (llamada AJAX desde ventas) */
    public function getParaCliente() {
        requireLogin();
        $cliente_id = intval($_GET['cliente_id'] ?? 0);
        if (!$cliente_id) return ['compras' => 0, 'promociones' => []];
        return $this->m->getDisponiblesParaCliente($cliente_id);
    }
}

// Dispatch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $c = new PromocionController();
    switch ($_POST['action']) {
        case 'create': echo json_encode($c->create()); break;
        case 'update': echo json_encode($c->update()); break;
        case 'delete': echo json_encode($c->delete()); break;
        case 'toggle': echo json_encode($c->toggle()); break;
        default: echo json_encode(['error' => 'Acción inválida']);
    }
    exit();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    $c = new PromocionController();
    if ($_GET['action'] === 'para_cliente') echo json_encode($c->getParaCliente());
    exit();
}
?>
