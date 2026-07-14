<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../models/Carrito.php';
require_once __DIR__ . '/../models/tienda/Pedido.php';
require_once __DIR__ . '/../models/tienda/ClienteOnline.php';

// Ejecutar migraciones automáticas de modelos.
new Producto();
new Venta();
new Carrito();
new Pedido();
new ClienteOnline();

?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Migraciones Vision Real</title></head>
<body style="font-family:sans-serif;background:#111;color:#eee;padding:24px;">
  <h1>Migraciones ejecutadas</h1>
  <p>Se han inicializado los modelos y creado las columnas/tablas necesarias para la tienda online.</p>
  <ul>
    <li>Productos: visible_tienda</li>
    <li>Ventas: tipo_venta</li>
    <li>Carrito: carrito + carrito_items</li>
    <li>Pedidos: pedidos + pedido_detalle</li>
  </ul>
  <p>Si alguna tabla ya existía, no se creó de nuevo.</p>
</body>
</html>
