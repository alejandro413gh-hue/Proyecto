<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Talla.php';

class InventarioController {
    private $pm;
    private $tm;

    public function __construct() {
        requireLogin();
        $this->pm = new Producto();
        $this->tm = new Talla();
    }

    // Buscar producto por código (para AJAX)
    public function buscarPorCodigo() {
        $codigo = trim($_GET['codigo'] ?? '');
        if (empty($codigo)) {
            echo json_encode(['error' => 'Código requerido']);
            return;
        }
        $producto = $this->pm->getByCodigo($codigo);
        if ($producto) {
            echo json_encode([
                'success' => true,
                'producto' => [
                    'id' => $producto['id'],
                    'codigo' => $producto['codigo'],
                    'nombre' => $producto['nombre'],
                    'precio' => $producto['precio'],
                    'stock' => $producto['stock']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Producto no encontrado']);
        }
    }

    // Agregar inventario (sumar stock, actualizar precio si se proporciona)
    public function agregarInventario() {
        $codigo = trim($_POST['codigo'] ?? '');
        $cantidad = intval($_POST['cantidad'] ?? 0);
        $talla = trim($_POST['talla'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);

        if (empty($codigo)) return ['error' => 'Código requerido'];
        if ($cantidad <= 0) return ['error' => 'Cantidad debe ser positiva'];
        if ($precio < 0) return ['error' => 'Precio no puede ser negativo'];

        $producto = $this->pm->getByCodigo($codigo);
        if (!$producto) {
            // Producto no existe, crear uno nuevo
            $nombre = trim($_POST['nombre'] ?? '');
            if (empty($nombre)) return ['error' => 'Nombre requerido para nuevo producto'];
            $desc = trim($_POST['descripcion'] ?? '');
            $categoria_id = intval($_POST['categoria_id'] ?? 0);

            $codigo_generado = $this->pm->generarCodigo();
            $ok = $this->pm->create($nombre, $desc, $precio > 0 ? $precio : 0, 0, $categoria_id, $codigo_generado);
            if (!$ok) return ['error' => 'Error al crear producto'];

            // Ahora agregar la talla si se especificó
            if (!empty($talla)) {
                $this->tm->guardar($this->pm->db->insert_id, $talla, $cantidad);
            } else {
                // Sin talla, actualizar stock general
                $conn = $this->pm->db->getConnection();
                $conn->query("UPDATE productos SET stock = stock + $cantidad WHERE id = " . $conn->insert_id);
            }
            return ['success' => 'Producto creado y stock agregado'];
        } else {
            // Producto existe, agregar stock
            if (!empty($talla)) {
                $ok = $this->tm->guardar($producto['id'], $talla, $cantidad);
                if (!$ok) return ['error' => 'Error al agregar stock a talla'];
            } else {
                // Sin talla, sumar al stock general
                $conn = $this->pm->db->getConnection();
                $conn->query("UPDATE productos SET stock = stock + $cantidad WHERE id = " . $producto['id']);
            }

            // Actualizar precio si se proporcionó
            if ($precio > 0) {
                $conn = $this->pm->db->getConnection();
                $conn->query("UPDATE productos SET precio = $precio WHERE id = " . $producto['id']);
            }

            return ['success' => 'Stock actualizado'];
        }
    }
}

// Manejo de requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    $c = new InventarioController();
    switch ($_GET['action']) {
        case 'buscar_codigo':
            $c->buscarPorCodigo();
            break;
        default:
            echo json_encode(['error' => 'Acción inválida']);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $c = new InventarioController();
    switch ($_POST['action']) {
        case 'agregar_inventario':
            echo json_encode($c->agregarInventario());
            break;
        default:
            echo json_encode(['error' => 'Acción inválida']);
    }
    exit();
}
?>