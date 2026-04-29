# 📋 EXTENSIÓN POS — Facturación + Registro Manual de Cliente

## 🎯 Cambios Implementados (SIN modificar diseño)

### ✅ **1. Registro Manual de Cliente Mejorado**

**Cambio funcional:**
- Input "Cliente" ahora acepta nombre O documento directamente
- Sistema busca automáticamente si existe
- Si existe → usa el cliente
- Si NO existe → registra automáticamente en la venta

**Flujo:**
```
Usuario escribe: "Juan Pérez" o "CC 123456"
         ↓
ENTER o tab
         ↓
Sistema busca en BD
         ↓
¿EXISTE?
  ├─ SÍ → ✓ Cliente existente (color dorado)
  └─ NO → ⚠ Nuevo cliente (color naranja)
         ↓
Al registrar venta → Se crea automáticamente
```

**Archivo modificado:**
- `views/ventas.php` — Nueva lógica en JavaScript

**Validaciones:**
- ✅ Nombre cliente obligatorio
- ✅ Documento cliente obligatorio
- ✅ No permite venta sin cliente
- ✅ Búsqueda case-insensitive

---

### ✅ **2. Facturación Automática**

**Qué pasa al registrar venta:**

```
1. Valida cliente (debe existir o se crea)
2. Valida productos (debe haber al menos 1)
3. Guarda venta en BD
4. AUTOMÁTICAMENTE genera factura:
   - Número único: FAC-YYYYMMDD-00001
   - Datos cliente
   - Productos + tallas + cantidades
   - Totales (subtotal, descuento, total)
   - Fecha/hora
```

**Respuesta al usuario:**
```
✓ Venta registrada — Factura: FAC-20260425-00001
[📄 Ver Factura]  ← Botón para abrir/descargar
```

**Archivos creados:**
- `models/Factura.php` — Modelo de facturación
- `controllers/FacturaController.php` — API de facturas

---

### ✅ **3. Generación de Factura (HTML/Print-Ready)**

**Contenido de factura:**

```
┌─────────────────────────────────────────┐
│             VISIÓN REAL                 │
│       FACTURA DE VENTA                  │
├─────────────────────────────────────────┤
│                                         │
│  CLIENTE:           FACTURA:            │
│  Juan Pérez        FAC-20260425-00001  │
│  CC 123456         25/04/2026 14:30    │
│                                         │
├─────────────────────────────────────────┤
│ Producto      | Talla | Cant | Valor  │
├─────────────────────────────────────────┤
│ Camisa Roja   | M     | 2    | $48000 │
│ Pantalón Azul | 32    | 1    | $55000 │
│                             Subtotal:  │
│                             $151000    │
│                             Descuento: │
│                             -$0        │
│                             TOTAL:     │
│                             $151000    │
└─────────────────────────────────────────┘
```

**Acciones disponibles:**
- 📄 Ver en nueva pestaña
- 🖨️ Imprimir (Ctrl+P)
- 📥 Descargar como PDF (desde navegador)

---

## 🛠️ Archivos Modificados/Creados

### **CREADOS (Nuevos):**

```
models/Factura.php
├─ Tabla: facturas (auto-creada si no existe)
├─ Métodos:
│  ├─ generarNumeroFactura() → FAC-YYYYMMDD-00001
│  ├─ crear() → INSERT factura
│  ├─ getByVentaId() → Obtener factura
│  └─ getAll() → Listar facturas

controllers/FacturaController.php
├─ action=crear → Crear factura (POST)
├─ action=generar_pdf → Generar HTML factura (GET)
└─ Función: generarHTMLFactura() → HTML print-ready
```

### **MODIFICADOS (Sin cambios visuales):**

```
views/ventas.php
├─ Input cliente:
│  - Antes: Búsqueda + opción crear
│  - Ahora: Input directo + búsqueda automática
├─ JavaScript:
│  - buscarOCrearCliente() → Nueva función
│  - registrarVenta() → Crea factura automáticamente
│  - abrirFactura() → Abre factura en nueva pestaña
└─ POST backend:
   - Valida cliente
   - Crea si no existe
   - Genera factura
   - Retorna número de factura
```

---

## 📊 Base de Datos

### **Nueva tabla (auto-creada):**

```sql
CREATE TABLE facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL UNIQUE,
    numero_factura VARCHAR(50) UNIQUE NOT NULL,
    cliente_nombre VARCHAR(100) NOT NULL,
    cliente_documento VARCHAR(50) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    descuento DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(20) DEFAULT 'generada',
    FOREIGN KEY (venta_id) REFERENCES ventas(id),
    INDEX (numero_factura),
    INDEX (cliente_documento)
);
```

**Relaciones:**
- `facturas.venta_id` ← `ventas.id` (1:1)
- `facturas.numero_factura` (único)

---

## 🔄 Flujo Completo (Ejemplo)

### **Paso 1: Cajero inicia venta**
```
Abre módulo VENTAS
Busca/selecciona productos
Selecciona tallas
Agrega al carrito
```

### **Paso 2: Ingresa cliente**
```
Input: "Juan Pérez" o "CC 123456"
Presiona ENTER
Sistema busca...
¿Existe?
  NO → ⚠ Muestra "Nuevo cliente"
```

### **Paso 3: Registra venta**
```
Clic: ✓ REGISTRAR

Backend hace:
1. Valida cliente
2. Si no existe → INSERT en clientes
3. INSERT en ventas
4. UPDATE stock (productos + tallas)
5. INSERT en facturas → FAC-20260425-00001
6. Retorna número factura
```

### **Paso 4: Ver factura**
```
Muestra: ✓ Venta registrada — Factura: FAC-20260425-00001
Clic: [📄 Ver Factura]
Se abre nueva pestaña con factura
Usuario puede:
- Imprimir (Ctrl+P)
- Guardar como PDF (Ctrl+S)
- Cerrar pestaña
```

---

## 💡 Detalles Técnicos

### **Cliente Input Logic:**

```javascript
// Usuario escribe en input cliente
buscarOCrearCliente(valor)
  ├─ GET /ClienteController.php?action=buscar_o_crear
  ├─ Si existe → clienteSeleccionado.id = id
  └─ Si no existe → clienteSeleccionado.id = null
```

### **Registro de Venta + Factura:**

```php
// views/ventas.php (POST)
1. Valida: cliente_id O (cliente_nombre AND cliente_documento)
2. Valida: items no vacío
3. Si cliente no existe:
   - $cm->create($nombre, $documento)
   - Obtiene ID del nuevo cliente
4. $vm->crear() → INSERT venta
5. $fm->crear() → INSERT factura
6. Retorna número_factura
```

### **Generación de HTML Factura:**

```php
// controllers/FacturaController.php
generarHTMLFactura($factura, $detalle, $venta)
  ├─ Build HTML (no PDF, solo HTML)
  ├─ CSS embebido para print
  ├─ Datos: cliente, productos, totales
  └─ Retorna JSON con HTML
```

---

## 🎨 UI/UX (NO CAMBIÓ)

### **Antes y después:**

| Aspecto | Antes | Después |
|--------|-------|---------|
| Diseño panel | ✓ Igual | ✓ Igual |
| Colores | ✓ Iguales | ✓ Iguales |
| Layout | ✓ Igual | ✓ Igual |
| Input cliente | Búsqueda | Input directo + búsqueda |
| Productos grid | ✓ Igual | ✓ Igual |
| Carrito | ✓ Igual | ✓ Igual |
| Botones | ✓ Iguales | ✓ Iguales |
| Factura | N/A | Nueva (no interfiere) |

**Cambios visuales:** CERO ✓

---

## ✅ Validaciones Implementadas

```
✓ Cliente obligatorio (nombre + documento)
✓ Productos obligatorios (min 1)
✓ Stock validado (no permite vender sin stock)
✓ Documento no duplicado (en búsqueda)
✓ Número factura único
✓ Fecha/hora automática
```

---

## 📝 Ejemplo de Uso

### **Escenario: Venta a nuevo cliente**

```
CAJERO:
1. Abre VENTAS
2. Búsqueda producto: "Camisa" → Selecciona
3. Selecciona talla: M
4. Repite con otro producto: "Pantalón" → L
5. Input cliente: "Juan Pérez" → ENTER
6. Sistema: ⚠ Nuevo cliente
7. Clic: ✓ REGISTRAR

SISTEMA:
- Crea cliente "Juan Pérez"
- Registra venta
- Genera factura FAC-20260425-00001
- Descuenta stock

RESULTADO:
✓ Venta registrada — Factura: FAC-20260425-00001
[📄 Ver Factura]
```

---

## 🧪 Testing

### **Prueba 1: Cliente existente**
```
Input: "Juan Pérez" (existe en BD)
Espera: ✓ Cliente existente (dorado)
Resultado: Usa cliente, no crea nuevo
```

### **Prueba 2: Cliente nuevo**
```
Input: "María García" (NO existe)
Espera: ⚠ Nuevo cliente (naranja)
Resultado: Registra en venta automáticamente
```

### **Prueba 3: Factura visible**
```
Después de venta:
Espera: Número de factura en mensaje
Clic: [📄 Ver Factura]
Resultado: Abre en nueva pestaña, con HTML imprimible
```

### **Prueba 4: Imprimir**
```
Con factura abierta:
Ctrl+P → Abre diálogo imprimir
Selecciona impresora
Click imprimir
Resultado: Factura lista para papel
```

---

## 🔗 Integración

**Todo está integrado sin romper:**
- ✅ Selección de productos
- ✅ Selección de tallas
- ✅ Carrito y totales
- ✅ Búsqueda de clientes
- ✅ Stock actualizado
- ✅ Descuentos (si aplican)

**Flujo de datos:**
```
Cliente Input → Búsqueda automática → Registro venta
                                         ↓
                                   Auto crea cliente
                                         ↓
                                   Auto crea factura
                                         ↓
                                   User ve número factura
```

---

## 📌 Notas Importantes

1. **La tabla `facturas` se crea automáticamente** — No requiere SQL manual
2. **Las facturas NO son PDF** — Son HTML imprimible desde navegador
3. **El diseño POS NO cambió** — Solo funcionalidad interna
4. **La búsqueda es case-insensitive** — Busca "juan" o "JUAN"
5. **El cliente se registra automáticamente** — No requiere paso extra

---

## 🚀 Instalación

```
1. Descarga vision_real_v2.0_FINAL.zip
2. Descomprime en C:\xampp\htdocs\vision_real\
3. XAMPP: Reinicia Apache + MySQL
4. Accede a http://localhost/vision_real
5. Módulo VENTAS funcionará con:
   - Input cliente mejorado
   - Facturación automática
   - Número factura generado
```

---

**Resultado:** Sistema POS con facturación automática, mismo diseño, mejor funcionalidad. ✓

Última actualización: Abril 25, 2026
