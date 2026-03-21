-- ============================================
-- SISTEMA DE DESCUENTOS — Visión Real
-- Ejecutar en phpMyAdmin sobre la BD vision_real
-- ============================================
USE vision_real;

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

-- Agregar columnas a ventas
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS descuento_id INT NULL AFTER descuento;
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS descuento_aplicado TEXT NULL AFTER descuento_id;

-- Ejemplos
INSERT INTO descuentos (nombre, descripcion, tipo_descuento, valor, aplica_genero, fecha_inicio, fecha_fin) VALUES
('Día de la Mujer','15% para toda la colección de dama el 8 de marzo','porcentaje',15,'dama','2026-03-08','2026-03-08'),
('Día de la Madre','20% para dama en el día de la madre','porcentaje',20,'dama','2026-05-11','2026-05-12'),
('Día del Hombre','15% para caballeros el día del hombre','porcentaje',15,'caballero','2026-11-19','2026-11-19');

INSERT INTO descuentos (nombre, descripcion, tipo_descuento, valor, compras_minimas) VALUES
('Cliente Fiel','10% para clientes con 3 o más compras','porcentaje',10,3),
('Cliente VIP','20% para clientes con 5 o más compras','porcentaje',20,5);
