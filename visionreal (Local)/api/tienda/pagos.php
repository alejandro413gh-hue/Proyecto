<?php
/**
 * api/tienda/pagos.php
 * Gestión de datos de pago y QR para la tienda online.
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/tienda/Pago.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$pm = new Pago();

switch ($action) {
    case 'get':
        echo json_encode(['success' => true, 'config' => $pm->getConfig()]);
        break;

    case 'guardar':
        requireLogin();
        requireAdmin();
        $metodo = $_POST['metodo'] ?? '';
        $clave  = $_POST['clave']  ?? '';
        $valor  = $_POST['valor']  ?? '';
        if (!$metodo || !$clave) {
            echo json_encode(['error' => 'Datos incompletos']);
            break;
        }
        if ($pm->guardarTexto($metodo, $clave, $valor)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'No se pudo guardar la configuración']);
        }
        break;

    case 'subir_qr':
        requireLogin();
        requireAdmin();
        $metodo = $_POST['metodo'] ?? '';
        if (!$metodo || empty($_FILES['qr'])) {
            echo json_encode(['error' => 'Datos incompletos']);
            break;
        }
        $result = $pm->subirQr($metodo, $_FILES['qr']);
        echo json_encode($result);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida']);
}
