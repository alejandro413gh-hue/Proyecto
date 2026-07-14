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
    'Fecha de creación'
];
foreach ($headers as $header) {
    echo "<th>" . htmlspecialchars($header, ENT_QUOTES, 'UTF-8') . "</th>";
}
echo "</tr></thead><tbody>";

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
    echo "<tr>";
    echo "<td class='center'>" . (int)$row['producto_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['codigo'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td>" . htmlspecialchars($row['producto_nombre'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td>" . nl2br(htmlspecialchars($row['descripcion'], ENT_QUOTES, 'UTF-8')) . "</td>";
    echo "<td>" . htmlspecialchars($row['categoria_nombre'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td class='numeric'>" . number_format((float)$row['precio'], 0, '.', '') . "</td>";
    echo "<td class='center'>" . (int)$row['stock_base'] . "</td>";
    echo "<td class='center'>" . (int)$row['stock_real'] . "</td>";
    echo "<td class='center'>" . ($row['activo'] ? 'Sí' : 'No') . "</td>";
    echo "<td>" . htmlspecialchars($row['imagen'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td class='center'>" . htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "</tr>";
}

echo "</tbody></table></body></html>";
exit();
