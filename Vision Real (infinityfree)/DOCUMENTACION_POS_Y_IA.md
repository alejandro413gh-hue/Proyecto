# Documentación del módulo POS y la integración de IA

## 1. Objetivo general

Se rediseñó el módulo **Ventas / POS** para que el cajero pueda trabajar con el menor número posible de clics y con una respuesta rápida, incluso cuando haya muchas personas en fila o el inventario tenga miles de productos.

La meta fue que el flujo se sienta más parecido a un POS profesional:

- buscar
- escanear
- agregar
- registrar

sin recargar la página y sin interrumpir la atención.

## 2. Qué se cambió en el POS

### 2.1. Vista principal

Se reemplazó la pantalla antigua por una versión más moderna y compacta:

- panel principal para catálogo
- panel lateral para carrito y cliente
- botones de acción más claros
- diseño adaptado a PC, tablet y pantallas táctiles

### 2.2. Filtro por categorías

Se agregó una barra de categorías en la parte superior del catálogo.

- categoría "Todas" por defecto
- cambio instantáneo sin recargar
- categorías desplazables horizontalmente

### 2.3. Tarjetas de productos

Cada producto ahora muestra:

- imagen
- código
- nombre
- precio
- stock
- categoría
- vendidos

Si un producto no tiene imagen, se muestra una imagen de respaldo incrustada para evitar espacios vacíos.

### 2.4. Búsqueda rápida

La búsqueda ahora funciona mientras se escribe y busca por:

- código
- nombre
- código de barras
- referencia

Además, al presionar Enter intenta primero resolver el código como si fuera un escaneo.

### 2.5. Lector de código de barras

Al escanear un producto:

- lo busca automáticamente
- lo agrega al carrito
- si ya estaba agregado, aumenta la cantidad

### 2.6. Carrito más inteligente

El carrito ahora permite:

- aumentar cantidad
- disminuir cantidad
- escribir cantidad manualmente
- eliminar producto
- editar precio para usuarios autorizados
- ver subtotal por producto
- ver utilidad por producto para administradores

### 2.7. Productos más vendidos y favoritos

El catálogo inicia mostrando primero:

- más vendidos
- recientes
- favoritos
- resto del inventario

También se agregó la opción de marcar productos como favoritos para que aparezcan primero.

### 2.8. Atajos de teclado

Se añadieron atajos para operar casi sin mouse:

- `F2` buscar producto
- `F3` buscar cliente
- `F4` registrar venta
- `ESC` cancelar venta
- `+` aumentar cantidad
- `-` disminuir cantidad
- `DEL` eliminar producto
- `CTRL + F` enfocar búsqueda

### 2.9. Cliente rápido

Se agregó la opción de usar **Consumidor Final** sin tener que escribir un cliente cada vez.

### 2.10. Stock en tiempo real

Después de registrar una venta, el stock se actualiza inmediatamente en la interfaz.

## 3. Archivos modificados

- `views/ventas.php`
- `assets/js/ventas-pos.js`
- `assets/css/ventas-pos.css`
- `controllers/VentaPosController.php`
- `models/Producto.php`

## 4. Cómo funciona técnicamente

### 4.1. Frontend

La vista `views/ventas.php` ahora carga un módulo JavaScript que:

- consume datos por `Fetch/AJAX`
- mantiene el catálogo en memoria
- actualiza el carrito sin recargar la página
- permite búsquedas y acciones instantáneas

### 4.2. Backend

Se creó un controlador específico para el POS:

- `bootstrap`
- `catalog`
- `barcode`
- `toggle_favorito`
- `checkout`

Este controlador concentra la lógica de respuesta rápida para el módulo de ventas.

### 4.3. Modelo de productos

El modelo `Producto` se amplió para soportar:

- búsqueda por código, código de barras y referencia
- categorías del POS
- favoritos
- productos más vendidos
- imagen por producto
- cálculo de stock real

## 5. Optimización de rendimiento

Se aplicaron varias ideas para que el POS sea más fluido:

- carga inicial rápida
- caché de catálogo en memoria
- carga diferida de imágenes
- consultas filtradas por categoría y texto
- mínimo uso del DOM
- actualización parcial de la interfaz

## 6. Integración de IA en el sistema

La IA del proyecto se usa para generar análisis e informes automáticos desde el panel de administración.

### 6.1. Proveedor de IA

El sistema puede trabajar con:

- **Google Gemini** como opción principal
- **Hugging Face** como alternativa cuando Gemini no tiene cuota o da error

### 6.2. Cómo funciona la IA

1. El usuario pulsa el botón de generar informe con IA.
2. El sistema toma datos del negocio.
3. Esos datos se envían al proveedor configurado en `config/config.php`.
4. La respuesta se muestra como análisis automático.

### 6.3. Qué se configuró

En la configuración se usa una clave API del proveedor elegido.

Ejemplos:

- `AI_REMOTE_ENDPOINT`
- `AI_REMOTE_TOKEN`
- `AI_ENABLE_FALLBACK`

## 7. Qué explicar en una exposición

Si necesitas presentarlo, puedes resumirlo así:

> "Rediseñé el módulo POS para convertirlo en una caja rápida, con categorías, búsqueda instantánea, lector de códigos, favoritos, carrito inteligente y stock en tiempo real. Además, el sistema incorpora IA para generar reportes automáticos desde el panel, usando un proveedor externo configurable."

## 8. Resultado final

El sistema quedó preparado para:

- atención rápida
- alto flujo de clientes
- catálogos grandes
- uso en pantalla táctil
- funcionamiento más profesional y ordenado

