# Auditoría de base de datos y código - Visión Real

Fecha: 2026-07-13

## Resumen ejecutivo

- La base de datos y el código están mayormente sincronizados en los módulos críticos.
- No encontré evidencia de que el panel de Pedidos Online use caché, LocalStorage, SessionStorage o datos simulados para mostrar pedidos.
- El módulo de pedidos online lee directamente la tabla `pedidos` y sus detalles desde PHP.
- Sí existe una tabla heredada sin referencias directas en el código: `configuracion_tienda`.
- También hay dos tablas que el código usa o crea, pero que no están incluidas en el dump SQL exportado: `producto_favoritos` y `cron_reporte_auditorias`.
- No eliminé ninguna tabla.

## Tablas activas y su uso

### Núcleo del sistema

- `productos`
- `categorias`
- `usuarios`
- `clientes`
- `ventas`
- `detalle_venta`
- `facturas`
- `producto_tallas`
- `descuentos`
- `promociones`

Estas tablas sí están referenciadas de forma directa en modelos, controladores, APIs, vistas y exportaciones.

### Tienda online y pedidos

- `clientes_online`
- `clientes_online_sesiones`
- `carrito`
- `carrito_items`
- `pedidos`
- `pedido_detalle`
- `pedido_historial`
- `pagos_tienda`

Estas tablas se usan en el carrito, checkout, pedidos online, historial, pagos y panel de administración.

### Configuración y alertas

- `store_settings`
- `telegram_subscribers`
- `telegram_alert_state`

Estas tablas están activas y referenciadas por la configuración de tienda, Telegram y notificaciones.

## Tablas que el código usa pero que no aparecen en el dump SQL

### `producto_favoritos`

- Referencias directas en `models/Producto.php:44`, `models/Producto.php:177`, `models/Producto.php:186`, `models/Producto.php:192`, `models/Producto.php:197`, `models/Producto.php:257`.
- Se usa para marcar productos favoritos en POS y ordenar el catálogo.
- El código la crea automáticamente con `CREATE TABLE IF NOT EXISTS`.

### `cron_reporte_auditorias`

- Referencias directas en `api/cron-reporter.php:82`, `api/cron-reporter.php:432`, `api/cron-reporter.php:462`.
- También aparece en `B/setup-cron-db.php` y `tienda/dashboard-reportes.php:42`.
- Se usa para auditoría y reportes del cron.
- El código la crea automáticamente si no existe.

## Tabla heredada sin uso directo encontrado

### `configuracion_tienda`

- Aparece en el dump SQL, pero no encontré referencias reales en el código fuente.
- El sistema actual usa `store_settings` en:
  - `models/TiendaConfig.php:26`
  - `models/TiendaConfig.php:36`
  - `models/TiendaConfig.php:52`
  - `config/config.php:114`
  - `config/config.php:138`
- Conclusión: `configuracion_tienda` parece ser una tabla legada o duplicada.
- No se eliminó por seguridad.

## Pedidos Online

### Fuente real de datos

- El panel de pedidos online usa `models/tienda/Pedido.php` y `api/tienda/pedidos.php`.
- `views/pedidos_online.php:35` carga pedidos con `getAllAdmin(...)`.
- `api/tienda/pedidos.php:103` a `api/tienda/pedidos.php:136` expone las acciones del panel.
- `models/tienda/Pedido.php:30`, `models/tienda/Pedido.php:55` y `models/tienda/Pedido.php:67` crean `pedidos`, `pedido_detalle` y `pedido_historial` si faltan.

### Conclusión

- No hay evidencia de caché, JSON estático, LocalStorage o datos hardcodeados para poblar esa vista.
- Si esas tablas se eliminan, el propio modelo las recrea al iniciarse.
- Por eso el módulo sigue mostrando pedidos reales y no una vista vacía o simulada.

## Relaciones referenciales relevantes

- `productos.categoria_id` -> `categorias.id`
- `producto_tallas.producto_id` -> `productos.id`
- `ventas.cliente_id` -> `clientes.id`
- `ventas.usuario_id` -> `usuarios.id`
- `detalle_venta.venta_id` -> `ventas.id`
- `detalle_venta.producto_id` -> `productos.id`
- `carrito.cliente_online_id` -> `clientes_online.id`
- `carrito_items.carrito_id` -> `carrito.id`
- `carrito_items.producto_id` -> `productos.id`
- `clientes_online.cliente_id` -> `clientes.id`
- `clientes_online_sesiones.cliente_id` -> `clientes_online.id`
- `descuentos.aplica_categoria_id` -> `categorias.id`
- `descuentos.aplica_producto_id` -> `productos.id`
- `pedidos.cliente_online_id` -> `clientes_online.id`
- `pedidos.cliente_id` -> `clientes.id`
- `pedidos.venta_id` -> `ventas.id`
- `pedido_detalle.pedido_id` -> `pedidos.id`
- `pedido_detalle.producto_id` -> `productos.id`
- `pedido_historial.pedido_id` -> `pedidos.id`
- `pedido_historial.usuario_id` -> `usuarios.id`

## Tablas candidatas a revisión futura

- `configuracion_tienda`

Motivo:

- No tiene referencias directas en el código.
- Parece duplicar parte de la configuración ya centralizada en `store_settings`.
- Requiere backup y verificación manual antes de considerar cualquier eliminación.

## Conclusión

- El sistema conserva sus tablas críticas y las usa correctamente.
- La mayor inconsistencia detectada es una tabla legada no usada directamente: `configuracion_tienda`.
- Hay dos tablas que deben incluirse en el despliegue o migración aunque no aparezcan en el dump: `producto_favoritos` y `cron_reporte_auditorias`.
- No se encontró una base para afirmar que los pedidos online se deban a caché o datos inventados.

