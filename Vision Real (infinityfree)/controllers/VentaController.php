<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Venta.php';

class VentaController {
    private $m;
    public function __construct() {
        requireLogin();
        $this->m  = new Venta();
    }

    public function create() {
        $cliente_id = intval($_POST['cliente_id'] ?? 0) ?: null;
        $notas      = trim($_POST['notas'] ?? '');
        $productos  = json_decode($_POST['productos'] ?? '[]', true);
        $descuento  = floatval($_POST['descuento'] ?? 0);
        $descuento_id = intval($_POST['descuento_id'] ?? 0) ?: null;
        $descuento_aplicado = trim($_POST['descuento_aplicado'] ?? '');

        if (empty($productos)) return ['error' => 'Agrega al menos un producto'];

        foreach ($productos as $p) {
            if (!isset($p['producto_id'], $p['cantidad'], $p['precio']))
                return ['error' => 'Datos de productos inválidos'];
            if ($p['cantidad'] <= 0 || $p['precio'] <= 0)
                return ['error' => 'Cantidad y precio deben ser mayores a 0'];
        }

        $subtotal = array_sum(array_map(fn($p) => $p['precio'] * $p['cantidad'], $productos));

        return $this->m->create(
            $cliente_id,
            $_SESSION['user_id'],
            $productos,
            $notas,
            null,
            $descuento,
            $subtotal,
            $descuento_id,
            $descuento_aplicado
        );
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $c = new VentaController();
    if ($_POST['action'] === 'create') echo json_encode($c->create());
    exit();
}
?>
