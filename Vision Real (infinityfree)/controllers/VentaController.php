<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../models/Factura.php';
require_once __DIR__ . '/../models/Cliente.php';

class VentaController {
    private $m;
    private $facturas;
    private $clientes;
    public function __construct() {
        requireLogin();
        $this->m = new Venta();
        $this->facturas = new Factura();
        $this->clientes = new Cliente();
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

        $venta = $this->m->create(
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

        if (!($venta['success'] ?? false)) {
            return $venta;
        }

        $ventaId = (int) ($venta['venta_id'] ?? 0);
        $clienteNombre = 'Consumidor Final';
        $clienteDocumento = 'CF';
        $clienteTelefono = '';

        if ($cliente_id) {
            $cliente = $this->clientes->getById((int) $cliente_id);
            if ($cliente) {
                $clienteNombre = trim((string) ($cliente['nombre'] ?? $clienteNombre));
                $clienteDocumento = trim((string) ($cliente['nit'] ?? '')) ?: $clienteDocumento;
                $clienteTelefono = trim((string) ($cliente['telefono'] ?? ''));
                if ($clienteDocumento === 'CF' && $clienteTelefono !== '') {
                    $clienteDocumento = $clienteTelefono;
                }
            }
        }

        $factura = $this->facturas->crear(
            $ventaId,
            $clienteNombre,
            $clienteDocumento,
            (float) $subtotal,
            (float) $descuento,
            (float) ($venta['total'] ?? max(0, $subtotal - $descuento)),
            $clienteTelefono
        );

        $venta['factura'] = $factura;
        return $venta;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $c = new VentaController();
    if ($_POST['action'] === 'create') echo json_encode($c->create());
    exit();
}
?>
