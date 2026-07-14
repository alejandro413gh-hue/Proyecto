<?php
/**
 * views/clientes_export.php
 * Exporta un reporte con los clientes, sus ventas y los productos de cada venta.
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="clientes_ventas_' . date('Ymd_His') . '.xls"');

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
    'Cliente ID',
    'Cliente Nombre',
    'Sexo',
    'NIT / CC',
    'Teléfono',
    'Email',
    'Dirección',
    'Venta ID',
    'Fecha Venta',
    'Estado Venta',
    'Total Venta',
    'Producto ID',
    'Producto Nombre',
    'Talla',
    'Cantidad',
    'Precio Unitario',
    'Subtotal Producto',
    'Vendedor'
];
foreach ($headers as $header) {
    echo "<th>" . htmlspecialchars($header, ENT_QUOTES, 'UTF-8') . "</th>";
}
echo "</tr></thead><tbody>";

$query = "SELECT
    c.id AS cliente_id,
    c.nombre AS cliente_nombre,
    c.sexo AS cliente_sexo,
    c.nit AS cliente_nit,
    c.telefono AS cliente_telefono,
    c.email AS cliente_email,
    c.direccion AS cliente_direccion,
    v.id AS venta_id,
    v.fecha AS venta_fecha,
    v.estado AS venta_estado,
    v.total AS venta_total,
    dv.producto_id AS producto_id,
    p.nombre AS producto_nombre,
    dv.talla AS producto_talla,
    dv.cantidad AS producto_cantidad,
    dv.precio_unitario AS producto_precio,
    dv.subtotal AS producto_subtotal,
    u.nombre AS vendedor_nombre
FROM ventas v
LEFT JOIN clientes c ON v.cliente_id = c.id
LEFT JOIN usuarios u ON v.usuario_id = u.id
LEFT JOIN detalle_venta dv ON dv.venta_id = v.id
LEFT JOIN productos p ON dv.producto_id = p.id
WHERE v.cliente_id IS NOT NULL
ORDER BY c.nombre ASC, v.fecha DESC, v.id ASC";

$result = $db->prepare($query);
$result->execute();
$res = $result->get_result();
while ($row = $res->fetch_assoc()) {
    echo "<tr>";
    echo "<td class='center'>" . (int)$row['cliente_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['cliente_nombre'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td class='center'>" . htmlspecialchars($row['cliente_sexo'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td>" . htmlspecialchars($row['cliente_nit'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td>" . htmlspecialchars($row['cliente_telefono'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td>" . htmlspecialchars($row['cliente_email'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td>" . nl2br(htmlspecialchars($row['cliente_direccion'], ENT_QUOTES, 'UTF-8')) . "</td>";
    echo "<td class='center'>" . (int)$row['venta_id'] . "</td>";
    echo "<td class='center'>" . htmlspecialchars($row['venta_fecha'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td class='center'>" . htmlspecialchars($row['venta_estado'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td class='numeric'>" . number_format((float)$row['venta_total'], 0, '.', '') . "</td>";
    echo "<td class='center'>" . (int)$row['producto_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['producto_nombre'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td class='center'>" . htmlspecialchars($row['producto_talla'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td class='center'>" . (int)$row['producto_cantidad'] . "</td>";
    echo "<td class='numeric'>" . number_format((float)$row['producto_precio'], 0, '.', '') . "</td>";
    echo "<td class='numeric'>" . number_format((float)$row['producto_subtotal'], 0, '.', '') . "</td>";
    echo "<td>" . htmlspecialchars($row['vendedor_nombre'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "</tr>";
}

echo "</tbody></table></body></html>";
exit();
