<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Talla.php';

header('Content-Type: application/json');
requireLogin();

$pm = new Producto();
$tm = new Talla();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ---- BUSCAR POR CÓDIGO (todos los roles) ----
if ($action === 'buscar' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $codigo = strtoupper(trim($_GET['codigo'] ?? ''));
    if (empty($codigo)) { echo json_encode(['encontrado' => false]); exit(); }

    $p = $pm->getByCodigo($codigo);
    if (!$p) {
        echo json_encode(['encontrado' => false, 'codigo' => $codigo]);
    } else {
        $tallas = $tm->getDisponibles($p['id']);
        echo json_encode([
            'encontrado' => true,
            'id'         => (int)$p['id'],
            'codigo'     => $p['codigo'],
            'nombre'     => $p['nombre'],
            'precio'     => (float)$p['precio'],
            'stock'      => (int)$p['stock'],
            'categoria'  => $p['categoria_nombre'] ?? '',
            'tallas'     => $tallas,
            'tiene_tallas' => !empty($tallas),
        ]);
    }
    exit();
}

// ---- GENERAR CÓDIGO (solo gestor/admin) ----
if ($action === 'generar' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    requireGestorOAdmin();
    echo json_encode(['codigo' => $pm->generarCodigo()]);
    exit();
}

// ---- ACTUALIZAR PRECIO (solo gestor/admin) ----
if ($action === 'actualizar_precio' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireGestorOAdmin();
    $id     = intval($_POST['id'] ?? 0);
    $precio = floatval($_POST['precio'] ?? 0);
    if ($id <= 0 || $precio <= 0) { echo json_encode(['error' => 'Datos inválidos']); exit(); }
    if ($pm->updatePrecio($id, $precio))
        echo json_encode(['success' => 'Precio actualizado correctamente']);
    else
        echo json_encode(['error' => 'Error al actualizar precio']);
    exit();
}

echo json_encode(['error' => 'Acción no válida']);
?>
