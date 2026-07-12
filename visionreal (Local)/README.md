# Vision Real - Sistema de Gestion Comercial v2.0

Sistema POS para tienda de ropa con facturacion automatica.

## Instalacion rapida en XAMPP

### 1. Copiar el proyecto
Extrae o copia la carpeta del proyecto en:

```text
C:\xampp\htdocs\visionreal\
```

### 2. Crear la base de datos
Abre phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Crea una base llamada:

```text
vision_real
```

Luego importa este archivo:

```text
if0_41735490_vision_real.sql
```

### 3. Abrir la aplicacion
En el navegador:

```text
http://localhost/visionreal
```

## Ajustes para XAMPP

- La URL base se detecta sola, pero puedes forzarla con `APP_BASE_URL`.
- La base de datos local usa por defecto `vision_real`.
- Si tu MySQL usa otro usuario o clave, define `DB_HOST`, `DB_USER`, `DB_PASS` y `DB_NAME`.

## Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- XAMPP con Apache y MySQL activos

## Usuario demo

- Email: `admin@visionreal.com`
- Password: `password`

## Estructura

```text
visionreal/
├── config/
├── controllers/
├── models/
├── views/
├── tienda/
├── assets/
├── index.php
└── if0_41735490_vision_real.sql
```
