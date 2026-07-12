<?php
/**
 * views/clientes_export.php
 * Exporta un reporte CSV con los clientes, sus ventas y los productos de cada venta.
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="clientes_ventas_' . date('Ymd_His') . '.xls"');
$output = fopen('php://output', 'w');
// BOM para Excel
fputs($output, "\xEF\xBB\xBF");

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
fputcsv($output, $headers);

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
    fputcsv($output, [
        $row['cliente_id'],
        $row['cliente_nombre'],
        $row['cliente_sexo'],
        $row['cliente_nit'],
        $row['cliente_telefono'],
        $row['cliente_email'],
        $row['cliente_direccion'],
        $row['venta_id'],
        $row['venta_fecha'],
        $row['venta_estado'],
        $row['venta_total'],
        $row['producto_id'],
        $row['producto_nombre'],
        $row['producto_talla'],
        $row['producto_cantidad'],
        $row['producto_precio'],
        $row['producto_subtotal'],
        $row['vendedor_nombre'],
    ]);
}

fclose($output);
exit();
