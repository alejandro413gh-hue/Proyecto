<?php
/**
 * views/productos_export.php
 * Exporta un archivo Excel (xls) con el listado completo de productos.
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="productos_' . date('Ymd_His') . '.xls"');
$output = fopen('php://output', 'w');
// BOM para Excel
fputs($output, "\xEF\xBB\xBF");

$headers = [
    'Producto ID',
    'Código',
    'Nombre',
    'Descripción',
    'Categoría',
    'Precio',
    'Stock base',
    'Stock real',
    'Activo',
    'Imagen',
    'Fecha de creación',
    'Fecha de actualización'
];
fputcsv($output, $headers);

$query = "SELECT
    p.id AS producto_id,
    p.codigo,
    p.nombre AS producto_nombre,
    p.descripcion,
    c.nombre AS categoria_nombre,
    p.precio,
    p.stock AS stock_base,
    COALESCE((SELECT SUM(pt.stock) FROM producto_tallas pt WHERE pt.producto_id = p.id), p.stock) AS stock_real,
    p.activo,
    p.imagen,
    p.created_at
FROM productos p
LEFT JOIN categorias c ON p.categoria_id = c.id
ORDER BY p.nombre ASC";

$result = $db->prepare($query);
$result->execute();
$res = $result->get_result();
while ($row = $res->fetch_assoc()) {
    fputcsv($output, [
        $row['producto_id'],
        $row['codigo'],
        $row['producto_nombre'],
        $row['descripcion'],
        $row['categoria_nombre'],
        $row['precio'],
        $row['stock_base'],
        $row['stock_real'],
        $row['activo'] ? 'Sí' : 'No',
        $row['imagen'],
        $row['created_at'],
        '',
    ]);
}

fclose($output);
exit();
