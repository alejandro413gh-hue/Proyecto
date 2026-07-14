<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Factura.php';
require_once __DIR__ . '/../models/Venta.php';

requireLogin();

$fm = new Factura();
$vm = new Venta();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'descargar_pdf' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/pdf');
} else {
    header('Content-Type: application/json; charset=utf-8');
}

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

if ($action === 'descargar_pdf' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $venta_id = intval($_GET['venta_id'] ?? 0);
    if ($venta_id <= 0) {
        echo "ID venta invalido";
        exit();
    }

    $venta = $vm->getById($venta_id);
    if (!$venta) {
        echo "Venta no encontrada";
        exit();
    }

    $factura = $fm->getByVentaId($venta_id);
    if (!$factura) {
        echo "Factura no encontrada";
        exit();
    }

    $detalle = $vm->getDetalle($venta_id);
    $pdf = generarPDFFactura($factura, $detalle, $venta);
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $factura['numero_factura']) . '.pdf"');
    echo $pdf;
    exit();
}

if ($action === 'crear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $venta_id = intval($_POST['venta_id'] ?? 0);
    $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
    $cliente_documento = trim($_POST['cliente_documento'] ?? '');
    $cliente_telefono = trim($_POST['cliente_telefono'] ?? '');
    $subtotal = floatval($_POST['subtotal'] ?? 0);
    $descuento = floatval($_POST['descuento'] ?? 0);
    $total = floatval($_POST['total'] ?? 0);

    if ($venta_id <= 0 || empty($cliente_nombre)) {
        echo json_encode(['error' => 'Datos incompletos']);
        exit();
    }

    if ($cliente_documento === '') {
        $cliente_documento = $cliente_telefono !== '' ? $cliente_telefono : 'CF';
    }

    $r = $fm->crear($venta_id, $cliente_nombre, $cliente_documento, $subtotal, $descuento, $total, $cliente_telefono);
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
    $downloadUrl = BASE_URL . '/controllers/FacturaController.php?action=descargar_pdf&venta_id=' . (int) ($venta['id'] ?? 0);
    
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
            .header p { font-size: 12px; color: #666; margin-bottom: 4px; }
            .invoice-actions { display: flex; gap: 10px; justify-content: center; margin: 14px 0 20px; flex-wrap: wrap; }
            .invoice-actions button,
            .invoice-actions a { border: 1px solid #333; background: #111; color: #fff; padding: 10px 14px; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
            .invoice-actions button.primary,
            .invoice-actions a.primary { background: #d6b25f; color: #111; border-color: #d6b25f; font-weight: bold; }
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
            @media print { .invoice-actions { display: none !important; } .container { border: none; margin: 0; padding: 0; } }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>FACTURA</h1>
                <p>Vision Real - Sistema de Gestion Comercial</p>
            </div>

            <div class='invoice-actions'>
                <button type='button' class='primary' onclick='window.print()'>Imprimir factura</button>
                <a href='{$downloadUrl}' class='primary' target='_blank' rel='noopener'>Descargar PDF</a>
                <button type='button' onclick='window.open(\"\", \"_blank\").document.write(document.documentElement.outerHTML)'>Abrir copia</button>
            </div>

            <div class='info-section'>
                <div class='info-block'>
                    <label>CLIENTE</label>
                    <p>{$factura['cliente_nombre']}</p>
                    <label>DOCUMENTO</label>
                    <p>{$factura['cliente_documento']}</p>
                    <label>TELEFONO</label>
                    <p>" . htmlspecialchars($factura['cliente_telefono'] ?? '-') . "</p>
                </div>
                <div class='info-block'>
                    <label>NUMERO DE FACTURA</label>
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
        $talla = !empty($item['talla']) ? $item['talla'] : '-';
        
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
    
    if (!empty($venta['descuento_aplicado'])) {
        $html .= "
                <div class='totales-row'>
                    <div class='totales-label'>Descuento aplicado:</div>
                    <div class='totales-valor'>" . htmlspecialchars($venta['descuento_aplicado']) . "</div>
                </div>";
    }

    if ($factura['descuento'] > 0) {
        $html .= "
                <div class='totales-row'>
                    <div class='totales-label'>Monto descuento:</div>
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
                <p>Factura generada automaticamente por Vision Real</p>
                <p>Gracias por su compra</p>
            </div>
        </div>
    </body>
    </html>";
    
    return $html;
}

function generarPDFFactura($factura, $detalle, $venta): string {
    $downloadUrl = BASE_URL . '/controllers/FacturaController.php?action=descargar_pdf&venta_id=' . (int) ($venta['id'] ?? 0);
    $lines = [];
    $lines[] = 'FACTURA';
    $lines[] = 'Vision Real - Sistema de Gestion Comercial';
    $lines[] = 'Numero: ' . ($factura['numero_factura'] ?? 'N/D');
    $lines[] = 'Fecha: ' . date('d/m/Y H:i', strtotime($factura['fecha'] ?? 'now'));
    $lines[] = 'Cliente: ' . sanitizePdfText((string) ($factura['cliente_nombre'] ?? ''));
    $lines[] = 'Telefono: ' . sanitizePdfText((string) ($factura['cliente_telefono'] ?? '-'));
    $lines[] = 'Documento: ' . sanitizePdfText((string) ($factura['cliente_documento'] ?? 'CF'));
    $lines[] = ' ';
    $lines[] = 'Productos:';

    foreach ($detalle as $index => $item) {
        $nombre = sanitizePdfText((string) ($item['producto_nombre'] ?? 'Producto'));
        $talla = trim((string) ($item['talla'] ?? '')) ?: '-';
        $cantidad = (int) ($item['cantidad'] ?? 0);
        $precio = number_format((float) ($item['precio_unitario'] ?? 0), 0, ',', '.');
        $subtotalItem = number_format((float) ($item['subtotal'] ?? 0), 0, ',', '.');
        $linea = ($index + 1) . '. ' . $nombre . ' | Talla: ' . sanitizePdfText($talla) . ' | Cant: ' . $cantidad . ' | $' . $precio . ' | Sub: $' . $subtotalItem;
        foreach (wrapPdfText($linea, 92) as $part) {
            $lines[] = $part;
        }
    }

    $lines[] = ' ';
    $lines[] = 'Subtotal: $' . number_format((float) ($factura['subtotal'] ?? 0), 0, ',', '.');
    if (!empty($factura['descuento'])) {
        $lines[] = 'Descuento: -$' . number_format((float) $factura['descuento'], 0, ',', '.');
    }
    $lines[] = 'Total: $' . number_format((float) ($factura['total'] ?? 0), 0, ',', '.');
    $lines[] = ' ';
    $lines[] = 'Gracias por su compra';
    $lines[] = ' ';
    $lines[] = 'Descarga web: ' . sanitizePdfText($downloadUrl);

    return buildSimplePdf($lines);
}

function sanitizePdfText(string $text): string {
    $text = trim($text);
    if ($text === '') return '';
    $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    if ($converted !== false && $converted !== '') {
        $text = $converted;
    } else {
        $text = preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';
    }
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function wrapPdfText(string $text, int $maxLen = 90): array {
    $text = sanitizePdfText($text);
    if ($text === '') return [''];
    $out = [];
    while (strlen($text) > $maxLen) {
        $break = strrpos(substr($text, 0, $maxLen), ' ');
        if ($break === false || $break < 20) {
            $break = $maxLen;
        }
        $out[] = trim(substr($text, 0, $break));
        $text = ltrim(substr($text, $break));
    }
    if ($text !== '') $out[] = $text;
    return $out;
}

function buildSimplePdf(array $lines): string {
    $width = 595;
    $height = 842;
    $top = 790;
    $leading = 15;
    $content = "BT\n/F1 11 Tf\n";
    $y = $top;
    foreach ($lines as $line) {
        $safe = sanitizePdfText((string) $line);
        if ($safe === '') {
            $y -= $leading;
            continue;
        }
        if ($y < 50) {
            break;
        }
        $content .= sprintf("1 0 0 1 40 %d Tm (%s) Tj\n", $y, $safe);
        $y -= $leading;
    }
    $content .= "ET";
    $contentLength = strlen($content);

    $objects = [];
    $objects[] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj";
    $objects[] = "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj";
    $objects[] = "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 {$width} {$height}] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj";
    $objects[] = "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj";
    $objects[] = "5 0 obj << /Length {$contentLength} >> stream\n{$content}\nendstream endobj";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $obj) {
        $offsets[] = strlen($pdf);
        $pdf .= $obj . "\n";
    }

    $xrefPos = strlen($pdf);
    $pdf .= "xref\n0 6\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= 5; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer << /Size 6 /Root 1 0 R >>\n";
    $pdf .= "startxref\n{$xrefPos}\n%%EOF";

    return $pdf;
}
?>



