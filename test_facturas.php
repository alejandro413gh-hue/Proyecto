<?php
require_once 'C:/xampp/htdocs/vision_real/config/config.php';
require_once 'C:/xampp/htdocs/vision_real/models/Factura.php';
$fm = new Factura();
$facturas = $fm->getByClienteId(8);
echo 'Facturas encontradas: ' . count($facturas) . PHP_EOL;
var_export($facturas);
?>