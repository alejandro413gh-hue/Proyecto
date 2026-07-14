<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Talla.php';
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../models/Factura.php';
require_once __DIR__ . '/../models/TelegramBot.php';

class VentaPosController {
    private Producto $productos;
    private Talla $tallas;
    private Cliente $clientes;
    private Venta $ventas;
    private Factura $facturas;

    public function __construct() {
        requireLogin();
        $this->productos = new Producto();
        $this->tallas = new Talla();
        $this->clientes = new Cliente();
        $this->ventas = new Venta();
        $this->facturas = new Factura();
    }

    public function respond(array $payload, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    public function bootstrap(): array {
        $categories = $this->productos->getPosCategories();
        array_unshift($categories, [
            'id' => 0,
            'nombre' => 'Todas',
            'total' => $this->productos->countAll(),
        ]);

        return [
            'success' => true,
            'categories' => $categories,
            'favorites' => $this->productos->getPosProducts(['order' => 'favorites', 'limit' => 24]),
            'popular' => $this->productos->getPosProducts(['order' => 'popular', 'limit' => 24]),
            'recent' => $this->productos->getPosProducts(['order' => 'recent', 'limit' => 24]),
            'auth' => [
                'is_admin' => isAdmin(),
                'is_gestor' => isGestor(),
                'is_vendedor' => isVendedor(),
                'can_edit_price' => isAdmin() || isGestor(),
                'can_see_margin' => isAdmin(),
            ],
            'default_customer' => [
                'nombre' => 'Consumidor Final',
                'telefono' => '',
                'nit' => '',
                'sexo' => 'O',
            ],
        ];
    }

    public function catalog(): array {
        $q = trim((string) ($_GET['q'] ?? ''));
        $categoriaId = (int) ($_GET['categoria_id'] ?? 0);
        $order = trim((string) ($_GET['order'] ?? 'popular'));
        $limit = (int) ($_GET['limit'] ?? 120);
        $offset = (int) ($_GET['offset'] ?? 0);

        return [
            'success' => true,
            'products' => $this->productos->getPosProducts([
                'q' => $q,
                'categoria_id' => $categoriaId,
                'order' => $order,
                'limit' => $limit,
                'offset' => $offset,
            ]),
        ];
    }

    public function barcode(): array {
        $term = trim((string) ($_GET['term'] ?? $_GET['codigo'] ?? ''));
        if ($term === '') {
            return ['success' => false, 'error' => 'Código requerido'];
        }

        $producto = $this->productos->getBySearchTerm($term);
        if (!$producto) {
            return ['success' => false, 'error' => 'Producto no encontrado'];
        }

        $tallas = $this->tallas->getDisponibles((int) $producto['id']);
        $producto['imagen_url'] = Producto::getImageUrl($producto, BASE_URL) ?: (BASE_URL . '/tienda/assets/img/sin-imagen.svg');
        $producto['tallas'] = $tallas;
        $producto['tiene_tallas'] = !empty($tallas);
        $producto['auto_talla'] = $this->pickAutoTalla($tallas);

        return [
            'success' => true,
            'producto' => $producto,
        ];
    }

    public function tallas(): array {
        $productoId = (int) ($_GET['producto_id'] ?? 0);
        if ($productoId <= 0) {
            return ['success' => false, 'error' => 'Producto inválido'];
        }

        $producto = $this->productos->getById($productoId);
        if (!$producto) {
            return ['success' => false, 'error' => 'Producto no encontrado'];
        }

        return [
            'success' => true,
            'producto' => [
                'id' => (int) $producto['id'],
                'nombre' => (string) ($producto['nombre'] ?? ''),
            ],
            'tallas' => $this->tallas->getPorProducto($productoId),
        ];
    }

    public function toggleFavorito(): array {
        requireLogin();
        $id = (int) ($_POST['producto_id'] ?? 0);
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Producto inválido'];
        }

        if ($this->productos->toggleFavorito($id)) {
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'No se pudo actualizar favorito'];
    }

    public function checkout(): array {
        $payload = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        $items = $payload['items'] ?? [];
        if (is_string($items)) {
            $items = json_decode($items, true);
        }
        if (!is_array($items) || empty($items)) {
            return ['success' => false, 'error' => 'Agregue al menos un producto'];
        }

        $clienteNombre = trim((string) ($payload['cliente_nombre'] ?? 'Consumidor Final'));
        $clienteTelefono = trim((string) ($payload['cliente_telefono'] ?? ''));
        $clienteNit = trim((string) ($payload['cliente_nit'] ?? $payload['cliente_documento'] ?? ''));
        $sexo = strtoupper(trim((string) ($payload['sexo'] ?? 'O')));
        if (!in_array($sexo, ['M', 'F', 'O'], true)) {
            $sexo = 'O';
        }

        if ($clienteNombre === '') {
            $clienteNombre = 'Consumidor Final';
        }

        $clienteId = (int) ($payload['cliente_id'] ?? 0);
        $clienteError = '';
        if ($clienteId <= 0) {
            $clienteId = $this->findOrCreateCustomer($clienteNombre, $clienteTelefono, $clienteNit, $sexo, $clienteError) ?: null;
        }

        $descuento = (float) ($payload['descuento_total'] ?? $payload['descuento'] ?? 0);
        $descuentoId = (int) ($payload['descuento_id'] ?? 0) ?: null;
        $descuentoAplicado = trim((string) ($payload['descuento_aplicado'] ?? ''));
        $notas = trim((string) ($payload['notas'] ?? ''));

        $normalizedItems = [];
        foreach ($items as $item) {
            $productoId = (int) ($item['producto_id'] ?? 0);
            $cantidad = max(1, (int) ($item['cantidad'] ?? 0));
            $precio = (float) ($item['precio'] ?? 0);
            $talla = trim((string) ($item['talla'] ?? ''));
            if ($productoId <= 0 || $precio <= 0 || $cantidad <= 0) {
                return ['success' => false, 'error' => 'Datos de carrito inválidos'];
            }
            $normalizedItems[] = [
                'producto_id' => $productoId,
                'cantidad' => $cantidad,
                'precio' => $precio,
                'talla' => $talla,
            ];
        }

        $stockCheck = $this->tallas->validarItems($normalizedItems);
        if (!($stockCheck['success'] ?? false)) {
            return [
                'success' => false,
                'error' => $stockCheck['error'] ?? 'No hay suficiente inventario para completar la venta.',
            ];
        }

        $subtotal = array_sum(array_map(static fn($p) => $p['precio'] * $p['cantidad'], $normalizedItems));
        $total = max(0, $subtotal - $descuento);

        $sale = $this->ventas->crear(
            $clienteId,
            (int) $_SESSION['user_id'],
            $normalizedItems,
            $notas,
            null,
            $descuento,
            $subtotal,
            $descuentoId,
            $descuentoAplicado
        );

        if (!($sale['success'] ?? false)) {
            return [
                'success' => false,
                'error' => $sale['error'] ?? 'No se pudo registrar la venta',
            ];
        }

        $ventaId = (int) ($sale['venta_id'] ?? 0);
        $clienteVenta = $clienteId ? ($this->clientes->getById($clienteId) ?: []) : [];
        $nombreFactura = (string) ($clienteVenta['nombre'] ?? $clienteNombre);
        $telefonoFactura = (string) ($clienteVenta['telefono'] ?? $clienteTelefono);
        $nitFactura = trim((string) ($clienteVenta['nit'] ?? $clienteNit ?? ''));
        $docFactura = $nitFactura !== '' ? $nitFactura : 'CF';
        $factura = $this->facturas->crear($ventaId, $nombreFactura, $docFactura, $subtotal, $descuento, $total, $telefonoFactura);

        try {
            $detalleVenta = [];
            foreach ($normalizedItems as $item) {
                $productoId = (int) ($item['producto_id'] ?? 0);
                $producto = $productoId > 0 ? $this->productos->getById($productoId) : null;
                $detalleVenta[] = [
                    'producto_id' => $productoId,
                    'nombre' => (string) ($producto['nombre'] ?? ''),
                    'referencia' => (string) ($producto['codigo'] ?? $producto['referencia'] ?? ''),
                    'talla' => trim((string) ($item['talla'] ?? '')),
                    'cantidad' => (int) ($item['cantidad'] ?? 0),
                    'precio_unitario' => (float) ($item['precio'] ?? 0),
                    'subtotal' => (float) ($item['precio'] ?? 0) * (int) ($item['cantidad'] ?? 0),
                ];
            }

            $bot = new TelegramBot();
            $bot->enviarReporteVenta([
                'origen' => 'POS',
                'cliente_nombre' => $nombreFactura,
                'cliente_nit' => $nitFactura !== '' ? $nitFactura : 'No registrado',
                'cliente_telefono' => $telefonoFactura,
                'cliente_correo' => trim((string) ($clienteVenta['email'] ?? '')),
                'items' => $detalleVenta,
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'envio' => 0.0,
                'total' => $total,
                'tipo_entrega' => 'No registrado',
                'direccion' => 'No registrado',
                'ciudad' => 'No registrado',
                'metodo_pago' => 'No registrado',
                'estado_pago' => 'Completado',
                'numero_factura' => $factura['numero_factura'] ?? '',
                'fecha_factura' => date('Y-m-d'),
                'hora_factura' => date('H:i'),
            ]);
        } catch (Throwable $e) {
            error_log('VentaPosController::checkout telegram: ' . $e->getMessage());
        }

        return [
            'success' => true,
            'venta_id' => $ventaId,
            'numero_factura' => $factura['numero_factura'] ?? null,
            'cliente' => $nombreFactura,
            'cliente_guardado' => (bool) $clienteId,
            'cliente_error' => $clienteId ? null : ($clienteError ?: null),
            'total' => $total,
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'factura_ok' => (bool) ($factura['success'] ?? false),
            'mensaje' => $clienteId
                ? 'Venta registrada correctamente'
                : 'Venta registrada, pero el cliente NO se pudo guardar: ' . ($clienteError ?: 'motivo desconocido'),
        ];
    }

    private function findOrCreateCustomer(string $nombre, string $telefono, string $nit, string $sexo, ?string &$error = null): int {
        $error = '';
        $nombreNorm = strtolower(trim($nombre));
        $clientes = $this->clientes->getAll();
        foreach ($clientes as $cliente) {
            $nitCliente = trim((string) ($cliente['nit'] ?? ''));
            $telefonoCliente = trim((string) ($cliente['telefono'] ?? ''));
            $nombreCliente = strtolower(trim((string) ($cliente['nombre'] ?? '')));
            if ($nit !== '' && $nitCliente === $nit) {
                return (int) $cliente['id'];
            }
            if ($telefono !== '' && $telefonoCliente === $telefono) {
                return (int) $cliente['id'];
            }
            if ($telefono === '' && $nombreCliente === $nombreNorm && $telefonoCliente === '') {
                return (int) $cliente['id'];
            }
        }

        if (!$this->clientes->create($nombre, $nit, $telefono, '', '', $sexo)) {
            $error = $this->clientes->lastError ?: 'No se pudo insertar el cliente en la base de datos.';
            error_log('findOrCreateCustomer: fallo al crear cliente "' . $nombre . '": ' . $error);
            return 0;
        }

        $clientes = $this->clientes->getAll();
        foreach ($clientes as $cliente) {
            $nitCliente = trim((string) ($cliente['nit'] ?? ''));
            $telefonoCliente = trim((string) ($cliente['telefono'] ?? ''));
            $nombreCliente = strtolower(trim((string) ($cliente['nombre'] ?? '')));
            if (($nit !== '' && $nitCliente === $nit) || ($telefono !== '' && $telefonoCliente === $telefono) || ($telefono === '' && $nombreCliente === $nombreNorm)) {
                return (int) $cliente['id'];
            }
        }

        $error = 'El cliente se insertó pero no se pudo volver a encontrar justo después (revisar collation/charset de la tabla clientes).';
        error_log('findOrCreateCustomer: ' . $error);
        return 0;
    }

    private function pickAutoTalla(array $tallas): string {
        if (empty($tallas)) {
            return '';
        }
        foreach ($tallas as $talla) {
            if ((int) ($talla['stock'] ?? 0) > 0) {
                return (string) ($talla['talla'] ?? '');
            }
        }
        return (string) ($tallas[0]['talla'] ?? '');
    }
}

$controller = new VentaPosController();
$action = strtolower(trim((string) ($_GET['action'] ?? $_POST['action'] ?? 'bootstrap')));

switch ($action) {
    case 'bootstrap':
        $controller->respond($controller->bootstrap());
        break;
    case 'catalog':
        $controller->respond($controller->catalog());
        break;
    case 'barcode':
        $controller->respond($controller->barcode());
        break;
    case 'tallas':
        $controller->respond($controller->tallas());
        break;
    case 'toggle_favorito':
        $controller->respond($controller->toggleFavorito());
        break;
    case 'checkout':
        $controller->respond($controller->checkout());
        break;
    default:
        $controller->respond(['success' => false, 'error' => 'Acción inválida'], 400);
}
