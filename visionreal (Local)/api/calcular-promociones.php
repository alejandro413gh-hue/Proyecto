<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
requireLogin();
require_once __DIR__ . '/../models/Promocion.php';

try {
    $sexo_cliente = $_POST['sexo'] ?? 'O';
    $items = json_decode($_POST['items'] ?? '[]', true);
    $total = floatval($_POST['total'] ?? 0);

    if (empty($items) || $total <= 0) {
        echo json_encode(['promociones_aplicables' => [], 'descuento_total' => 0]);
        exit;
    }

    $pm = new Promocion();
    $promociones = $pm->getAplicables($sexo_cliente, $total);

    $promociones_aplicables = [];
    $descuento_total = 0;

    foreach ($promociones as $promo) {
        $descuento = 0;
        if ($promo['tipo'] === 'porcentaje') {
            $descuento = ($total * $promo['valor']) / 100;
        } else {
            $descuento = $promo['valor'];
        }

        $promociones_aplicables[] = [
            'id' => $promo['id'],
            'nombre' => $promo['nombre'],
            'descripcion' => $promo['descripcion'],
            'tipo' => $promo['tipo'],
            'valor' => $promo['valor'],
            'descuento' => round($descuento, 0)
        ];

        // Tomar la promoción con mayor descuento
        if ($descuento > $descuento_total) {
            $descuento_total = $descuento;
        }
    }

    echo json_encode([
        'success' => true,
        'promociones_aplicables' => $promociones_aplicables,
        'descuento_total' => round($descuento_total, 0),
        'total_con_descuento' => max(0, round($total - $descuento_total, 0))
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>