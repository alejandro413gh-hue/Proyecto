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
$output = fopen('php://output', 'w');
// BOM para Excel
fputs($output, "\xEF\xBB\xBF");

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
fputcsv($output, $headers);

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
    fputcsv($output, [
        $row['venta_id'],
        $row['fecha'],
        $row['estado'],
        $row['subtotal'],
        $row['descuento'],
        $row['descuento_aplicado'],
        $row['total'],
        $row['numero_factura'],
        $row['cliente_id'],
        $row['cliente_nombre'],
        $row['cliente_nit'],
        $row['cliente_telefono'],
        $row['cliente_email'],
        $row['vendedor_id'],
        $row['vendedor_nombre'],
        $row['producto_id'],
        $row['producto_nombre'],
        $row['talla'],
        $row['cantidad'],
        $row['precio_unitario'],
        $row['producto_subtotal'],
    ]);
}

fclose($output);
exit();
