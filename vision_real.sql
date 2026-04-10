-- ============================================================
-- VISIÓN REAL — Base de Datos Completa
-- Versión: 1.0 | Instalación en un solo archivo
-- Ejecutar completo en phpMyAdmin → pestaña SQL
-- ============================================================

CREATE DATABASE IF NOT EXISTS vision_real CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vision_real;

-- ============================================================
-- TABLAS PRINCIPALES
-- ============================================================

CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('administrador','gestor_inventario','vendedor') NOT NULL DEFAULT 'vendedor',
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(150),
    direccion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    categoria_id INT,
    imagen VARCHAR(255),
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT,
    usuario_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('completada','pendiente','cancelada') DEFAULT 'completada',
    notas TEXT,
    promocion_id INT NULL,
    descuento DECIMAL(10,2) DEFAULT 0,
    descuento_id INT NULL,
    descuento_aplicado TEXT NULL,
    total_sin_descuento DECIMAL(10,2) DEFAULT 0,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS detalle_venta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    talla VARCHAR(20) NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLA TALLAS
-- ============================================================

CREATE TABLE IF NOT EXISTS producto_tallas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    talla VARCHAR(20) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    UNIQUE KEY uk_prod_talla (producto_id, talla),
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLA PROMOCIONES (por número de compras)
-- ============================================================

CREATE TABLE IF NOT EXISTS promociones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    tipo ENUM('porcentaje','monto_fijo') NOT NULL DEFAULT 'porcentaje',
    valor DECIMAL(10,2) NOT NULL,
    compras_minimas INT NOT NULL DEFAULT 1,
    activa TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLA DESCUENTOS ESPECIALES (por fecha, género, producto...)
-- ============================================================

CREATE TABLE IF NOT EXISTS descuentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    tipo_descuento ENUM('porcentaje','monto_fijo') NOT NULL DEFAULT 'porcentaje',
    valor DECIMAL(10,2) NOT NULL,
    aplica_categoria_id INT NULL,
    aplica_producto_id  INT NULL,
    aplica_genero ENUM('dama','caballero','todos') DEFAULT 'todos',
    compras_minimas INT DEFAULT 0,
    fecha_inicio DATE NULL,
    fecha_fin    DATE NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aplica_categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
    FOREIGN KEY (aplica_producto_id)  REFERENCES productos(id)  ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- DATOS INICIALES
-- ============================================================

INSERT INTO categorias (nombre, descripcion) VALUES
('Dama - Casual',    'Ropa casual para dama'),
('Dama - Formal',    'Ropa formal para dama'),
('Caballero - Casual', 'Ropa casual para caballero'),
('Caballero - Formal', 'Ropa formal para caballero'),
('Accesorios',       'Accesorios y complementos');

-- Contraseña de ambos usuarios: password
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador',  'admin@visionreal.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrador'),
('Vendedor Demo',  'vendedor@visionreal.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendedor');

INSERT INTO clientes (nombre, telefono, email) VALUES
('Cliente General', '000-000-0000', 'general@cliente.com'),
('María López',     '300-123-4567', 'maria@email.com'),
('Carlos Martínez', '310-987-6543', 'carlos@email.com');

INSERT INTO productos (nombre, precio, stock, categoria_id) VALUES
('Blusa Floral Manga Corta',  45000, 20, 1),
('Vestido Casual Verano',     85000, 15, 1),
('Blusa Formal Blanca',       55000, 12, 2),
('Pantalón Formal Dama',      75000, 18, 2),
('Camisa Casual Cuadros',     48000, 25, 3),
('Jean Slim Fit Caballero',   90000, 20, 3),
('Camisa Formal Blanca',      65000, 15, 4),
('Pantalón Formal Caballero', 80000, 10, 4),
('Cinturón de Cuero',         35000, 30, 5),
('Bolso Casual Dama',         95000,  8, 5);

INSERT INTO producto_tallas (producto_id, talla, stock) VALUES
(1,'XS',3),(1,'S',5),(1,'M',6),(1,'L',4),(1,'XL',2),
(2,'S',4),(2,'M',5),(2,'L',3),(2,'XL',1),
(3,'XS',2),(3,'S',4),(3,'M',4),(3,'L',2),
(4,'26',3),(4,'28',4),(4,'30',5),(4,'32',4),(4,'34',2),
(5,'S',5),(5,'M',8),(5,'L',7),(5,'XL',4),(5,'XXL',1),
(6,'28',3),(6,'30',5),(6,'32',6),(6,'34',4),(6,'36',2),
(7,'S',3),(7,'M',5),(7,'L',4),(7,'XL',2),
(8,'28',2),(8,'30',3),(8,'32',3),(8,'34',1),
(9,'Único',30),
(10,'Único',8);

INSERT INTO promociones (nombre, descripcion, tipo, valor, compras_minimas) VALUES
('Bienvenida',       'Descuento para clientes con al menos 1 compra',  'porcentaje', 5,  1),
('Cliente Frecuente','Descuento especial por 3 o más compras',         'porcentaje', 10, 3),
('Cliente VIP',      '15% para clientes con 5 o más compras',          'porcentaje', 15, 5),
('Descuento $20.000','Descuento fijo para clientes con 2 o más compras','monto_fijo', 20000, 2);

INSERT INTO descuentos (nombre, descripcion, tipo_descuento, valor, aplica_genero, fecha_inicio, fecha_fin) VALUES
('Día de la Mujer', '15% para toda la colección de dama el 8 de marzo', 'porcentaje', 15, 'dama', '2026-03-08', '2026-03-08'),
('Día de la Madre', '20% para dama en el día de la madre',              'porcentaje', 20, 'dama', '2026-05-11', '2026-05-12'),
('Día del Hombre',  '15% para caballeros el día del hombre',            'porcentaje', 15, 'caballero', '2026-11-19', '2026-11-19');

INSERT INTO descuentos (nombre, descripcion, tipo_descuento, valor, compras_minimas) VALUES
('Cliente Fiel', '10% para clientes con 3 o más compras', 'porcentaje', 10, 3),
('Cliente VIP',  '20% para clientes con 5 o más compras', 'porcentaje', 20, 5);

-- ============================================================
-- FIN — Sistema listo para usar
-- Usuario admin:    admin@visionreal.com    / password
-- Usuario vendedor: vendedor@visionreal.com / password
-- ============================================================
