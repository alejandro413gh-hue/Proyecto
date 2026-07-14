<?php
/**
 * views/ventas_export.php
 * Exporta un archivo Excel (xls) con las ventas completas y sus productos.
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="ventas_' . date('Ymd_His') . '.xls"');

echo "<html><head><meta charset='utf-8'><style>";
echo "table{border-collapse:collapse;width:100%;font-family:Arial,sans-serif;}";
echo "th,td{border:1px solid #666;padding:8px;vertical-align:top;}";
echo "th{background:#2F4F4F;color:#fff;font-weight:bold;text-align:center;}";
echo "td{text-align:left;white-space:normal;word-wrap:break-word;}";
echo "td.numeric{text-align:right;mso-number-format:'\\0024 #,##0';}";
echo "td.center{text-align:center;}";
echo "</style></head><body>";
echo "<table><thead><tr>";
$headers = [
    'Venta ID',
    'Fecha',
    'Estado',
    'Subtotal',
    'Descuento',
    'Descuento aplicado',
    'Total',
    'Factura',
    'Cliente ID',
    'Cliente Nombre',
    'Cliente NIT/CC',
    'Cliente Teléfono',
    'Cliente Email',
    'Vendedor ID',
    'Vendedor Nombre',
    'Producto ID',
    'Producto Nombre',
    'Talla',
    'Cantidad',
    'Precio Unitario',
    'Subtotal Producto'
];
foreach ($headers as $header) {
    echo "<th>" . htmlspecialchars($header, ENT_QUOTES, 'UTF-8') . "</th>";
}
echo "</tr></thead><tbody>";

$query = "SELECT
    v.id AS venta_id,
    v.fecha,
    v.estado,
    v.subtotal,
    v.descuento,
    v.descuento_aplicado,
    v.total,
    f.numero_factura,
    c.id AS cliente_id,
    c.nombre AS cliente_nombre,
    c.nit AS cliente_nit,
    c.telefono AS cliente_telefono,
    c.email AS cliente_email,
    u.id AS vendedor_id,
    u.nombre AS vendedor_nombre,
    dv.producto_id,
    p.nombre AS producto_nombre,
    dv.talla,
    dv.cantidad,
    dv.precio_unitario,
    dv.subtotal AS producto_subtotal
FROM ventas v
LEFT JOIN clientes c ON v.cliente_id = c.id
LEFT JOIN usuarios u ON v.usuario_id = u.id
LEFT JOIN detalle_venta dv ON dv.venta_id = v.id
LEFT JOIN productos p ON dv.producto_id = p.id
LEFT JOIN facturas f ON f.venta_id = v.id
ORDER BY v.fecha DESC, v.id DESC";

$result = $db->prepare($query);
$result->execute();
$res = $result->get_result();
while ($row = $res->fetch_assoc()) {
    echo "<tr>";
    echo "<td class='center'>" . (int)$row['venta_id'] . "</td>";
    echo "<td class='center'>" . htmlspecialchars($row['fecha'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td class='center'>" . htmlspecialchars($row['estado'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td class='numeric'>" . number_format((float)$row['subtotal'], 0, '.', '') . "</td>";
    echo "<td class='numeric'>" . number_format((float)$row['descuento'], 0, '.', '') . "</td>";
    echo "<td class='numeric'>" . number_format((float)$row['descuento_aplicado'], 0, '.', '') . "</td>";
    echo "<td class='numeric'>" . number_format((float)$row['total'], 0, '.', '') . "</td>";
    echo "<td class='center'>" . htmlspecialchars($row['numero_factura'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td class='center'>" . (int)$row['cliente_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['cliente_nombre'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td>" . htmlspecialchars($row['cliente_nit'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td>" . htmlspecialchars($row['cliente_telefono'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td>" . htmlspecialchars($row['cliente_email'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td class='center'>" . (int)$row['vendedor_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['vendedor_nombre'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td class='center'>" . (int)$row['producto_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['producto_nombre'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td class='center'>" . htmlspecialchars($row['talla'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td class='center'>" . (int)$row['cantidad'] . "</td>";
    echo "<td class='numeric'>" . number_format((float)$row['precio_unitario'], 0, '.', '') . "</td>";
    echo "<td class='numeric'>" . number_format((float)$row['producto_subtotal'], 0, '.', '') . "</td>";
    echo "</tr>";
}

echo "</tbody></table></body></html>";
exit();
