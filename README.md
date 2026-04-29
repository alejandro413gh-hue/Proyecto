# VISIÓN REAL — Sistema de Gestión Comercial v2.0

Sistema POS para tienda de ropa con facturación automática.

## 🚀 Instalación Rápida (3 pasos)

### 1️⃣ Descomprimir
```
Extrae el contenido de este ZIP en:
C:\xampp\htdocs\vision_real\
```

### 2️⃣ Base de datos
```
Abre phpMyAdmin:
http://localhost/phpmyadmin

Importa el archivo:
vision_real.sql
```

### 3️⃣ Acceder
```
Abre en navegador:
http://localhost/vision_real

Login demo:
Email: admin@visionreal.com
Pass: password
```

---

## 📋 Estructura de Carpetas

```
vision_real/
├── config/            ← Configuración (BD, session)
├── models/            ← Lógica de datos
├── controllers/       ← Endpoints/acciones
├── views/             ← Pantallas (PHP)
├── assets/            ← CSS, JS, imágenes
├── tienda/            ← Tienda pública
├── index.php          ← Login
└── vision_real.sql    ← Base de datos
```

---

## 🎯 Módulos Disponibles

- 📊 **Dashboard** — Gráficos y resumen
- 📦 **Productos** — Gestión de inventario
- 💰 **Ventas (POS)** — Sistema de caja con facturación
- 👥 **Clientes** — Registro automático desde ventas
- 🎁 **Promociones** — Descuentos por compras
- 👨‍💻 **Usuarios** — Gestión de permisos (Admin)
- 👤 **Mi Perfil** — Cambiar contraseña propia

---

## ✨ Novedades v2.0

✅ **Registro manual de cliente** — Búsqueda automática + registro en venta  
✅ **Facturación automática** — Número único (FAC-YYYYMMDD-00001)  
✅ **Factura imprimible** — HTML listo para PDF desde navegador  
✅ **Seguridad mejorada** — Recuperación de contraseña solo admin  

---

## 📖 Documentación

- **RESUMEN_PROYECTO_FINAL.md** — Guía completa del sistema
- **EXTENSION_POS_FACTURACION.md** — Detalles de facturación

---

## 👤 Usuarios Demo

| Rol | Email | Contraseña |
|-----|-------|-----------|
| Admin | admin@visionreal.com | password |
| Gestor | gestor@visionreal.com | password |
| Vendedor | vendedor@visionreal.com | password |

---

## 🔐 Requisitos

- PHP 7.4+
- MySQL 5.7+
- XAMPP o servidor equivalente

---

## 💡 Funcionalidades Principales

### Módulo Ventas (POS)
- Búsqueda rápida de productos por código o nombre
- Selección de tallas con stock real
- Carrito con totales en tiempo real
- **Búsqueda/registro de cliente automático**
- **Facturación automática con número único**
- Impresión directa desde navegador

### Sistema de Roles
- **Admin**: Acceso total
- **Gestor Inventario**: Productos + tallas + inventario
- **Vendedor**: Ventas + consultas básicas

---

¿Problemas? Revisa los archivos de documentación incluidos.

**Versión:** 2.0 | **Fecha:** Abril 2026 | **Status:** Production Ready ✓
