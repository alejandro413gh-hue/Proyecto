<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../models/Promocion.php';

class VentaController {
    private $m;
    private $pm;
    public function __construct() {
        requireLogin();
        $this->m  = new Venta();
        $this->pm = new Promocion();
    }

    public function create() {
        $cliente_id   = intval($_POST['cliente_id'] ?? 0) ?: null;
        $notas        = trim($_POST['notas'] ?? '');
        $promocion_id = intval($_POST['promocion_id'] ?? 0) ?: null;
        $productos    = json_decode($_POST['productos'] ?? '[]', true);

        if (empty($productos)) return ['error' => 'Agrega al menos un producto'];

        foreach ($productos as $p) {
            if (!isset($p['producto_id'], $p['cantidad'], $p['precio']))
                return ['error' => 'Datos de productos inválidos'];
            if ($p['cantidad'] <= 0 || $p['precio'] <= 0)
                return ['error' => 'Cantidad y precio deben ser mayores a 0'];
        }

        // Calcular descuento si hay promoción
        $subtotal = array_sum(array_map(fn($p) => $p['precio'] * $p['cantidad'], $productos));
        $descuento = 0;
        if ($promocion_id) {
            $descuento = $this->pm->calcularDescuento($promocion_id, $subtotal);
        }

        return $this->m->create(
            $cliente_id,
            $_SESSION['user_id'],
            $productos,
            $notas,
            $promocion_id,
            $descuento,
            $subtotal
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
