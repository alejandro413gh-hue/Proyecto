<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Factura.php';
require_once __DIR__ . '/../models/Venta.php';

header('Content-Type: application/json');
requireLogin();

$fm = new Factura();
$vm = new Venta();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'generar_pdf' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $venta_id = intval($_GET['venta_id'] ?? 0);
    if ($venta_id <= 0) { echo json_encode(['error' => 'ID venta inválido']); exit(); }

    $venta = $vm->getById($venta_id);
    if (!$venta) { echo json_encode(['error' => 'Venta no encontrada']); exit(); }

    $factura = $fm->getByVentaId($venta_id);
    if (!$factura) { echo json_encode(['error' => 'Factura no encontrada']); exit(); }

    $detalle = $vm->getDetalle($venta_id);
    $html = generarHTMLFactura($factura, $detalle, $venta);
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'numero_factura' => $factura['numero_factura'],
        'cliente' => $factura['cliente_nombre']
    ]);
    exit();
}

if ($action === 'crear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $venta_id = intval($_POST['venta_id'] ?? 0);
    $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
    $cliente_documento = trim($_POST['cliente_documento'] ?? '');
    $subtotal = floatval($_POST['subtotal'] ?? 0);
    $descuento = floatval($_POST['descuento'] ?? 0);
    $total = floatval($_POST['total'] ?? 0);

    if ($venta_id <= 0 || empty($cliente_nombre) || empty($cliente_documento)) {
        echo json_encode(['error' => 'Datos incompletos']);
        exit();
    }

    $r = $fm->crear($venta_id, $cliente_nombre, $cliente_documento, $subtotal, $descuento, $total);
    echo json_encode($r);
    exit();
}

if ($action === 'listar_por_cliente' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $cliente_id = intval($_GET['cliente_id'] ?? 0);
    if ($cliente_id <= 0) { echo json_encode(['error' => 'ID cliente inválido']); exit(); }

    $facturas = $fm->getByClienteId($cliente_id);
    echo json_encode(['success' => true, 'facturas' => $facturas]);
    exit();
}

echo json_encode(['error' => 'Acción no válida']);

function generarHTMLFactura($factura, $detalle, $venta) {
    $fecha = date('d/m/Y H:i', strtotime($factura['fecha']));
    
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Factura {$factura['numero_factura']}</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
            .header h1 { font-size: 24px; margin-bottom: 5px; }
            .header p { font-size: 12px; color: #666; }
            .info-section { display: flex; justify-content: space-between; margin-bottom: 20px; }
            .info-block { flex: 1; }
            .info-block label { font-weight: bold; font-size: 12px; display: block; margin-bottom: 3px; }
            .info-block p { font-size: 13px; margin-bottom: 5px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            table th { background: #f0f0f0; padding: 8px; text-align: left; font-size: 12px; font-weight: bold; border-bottom: 1px solid #333; }
            table td { padding: 8px; font-size: 12px; border-bottom: 1px solid #eee; }
            .totales { margin: 20px 0; text-align: right; }
            .totales-row { display: flex; justify-content: flex-end; margin-bottom: 8px; }
            .totales-label { width: 150px; font-weight: bold; }
            .totales-valor { width: 100px; text-align: right; }
            .total-grande { font-size: 18px; font-weight: bold; border-top: 2px solid #333; padding-top: 10px; }
            .footer { text-align: center; margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 11px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>FACTURA</h1>
                <p>Visión Real - Sistema de Gestión Comercial</p>
            </div>

            <div class='info-section'>
                <div class='info-block'>
                    <label>CLIENTE</label>
                    <p>{$factura['cliente_nombre']}</p>
                    <label>DOCUMENTO</label>
                    <p>{$factura['cliente_documento']}</p>
                </div>
                <div class='info-block'>
                    <label>NÚMERO DE FACTURA</label>
                    <p>{$factura['numero_factura']}</p>
                    <label>FECHA</label>
                    <p>{$fecha}</p>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style='width: 50%'>Producto</th>
                        <th style='width: 15%'>Talla</th>
                        <th style='width: 10%; text-align: center'>Cantidad</th>
                        <th style='width: 15%; text-align: right'>Valor Unit.</th>
                        <th style='width: 10%; text-align: right'>Subtotal</th>
                    </tr>
                </thead>
                <tbody>";
    
    foreach ($detalle as $item) {
        $subtotal_item = $item['precio_unitario'] * $item['cantidad'];
        $talla = !empty($item['talla']) ? $item['talla'] : '—';
        
        $html .= "
                    <tr>
                        <td>{$item['producto_nombre']}</td>
                        <td>{$talla}</td>
                        <td style='text-align: center'>{$item['cantidad']}</td>
                        <td style='text-align: right'>\$" . number_format($item['precio_unitario'], 0, ',', '.') . "</td>
                        <td style='text-align: right'>\$" . number_format($subtotal_item, 0, ',', '.') . "</td>
                    </tr>";
    }
    
    $html .= "
                </tbody>
            </table>

            <div class='totales'>
                <div class='totales-row'>
                    <div class='totales-label'>Subtotal:</div>
                    <div class='totales-valor'>\$" . number_format($factura['subtotal'], 0, ',', '.') . "</div>
                </div>";
    
    if ($factura['descuento'] > 0) {
        $html .= "
                <div class='totales-row'>
                    <div class='totales-label'>Descuento:</div>
                    <div class='totales-valor'>-\$" . number_format($factura['descuento'], 0, ',', '.') . "</div>
                </div>";
    }
    
    $html .= "
                <div class='totales-row total-grande'>
                    <div class='totales-label'>TOTAL:</div>
                    <div class='totales-valor'>\$" . number_format($factura['total'], 0, ',', '.') . "</div>
                </div>
            </div>

            <div class='footer'>
                <p>Factura generada automáticamente por Visión Real</p>
                <p>Gracias por su compra</p>
            </div>
        </div>
    </body>
    </html>";
    
    return $html;
}
?>
