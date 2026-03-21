-- Agregar a la base de datos vision_real
USE vision_real;

CREATE TABLE IF NOT EXISTS promociones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    tipo ENUM('porcentaje','monto_fijo') NOT NULL DEFAULT 'porcentaje',
    valor DECIMAL(10,2) NOT NULL COMMENT 'Porcentaje (ej: 15) o monto fijo (ej: 10000)',
    compras_minimas INT NOT NULL DEFAULT 1 COMMENT 'Número mínimo de compras del cliente para aplicar',
    activa TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Agregar columna promocion_id a ventas
ALTER TABLE ventas 
    ADD COLUMN promocion_id INT NULL AFTER notas,
    ADD COLUMN descuento DECIMAL(10,2) DEFAULT 0 AFTER promocion_id,
    ADD COLUMN total_sin_descuento DECIMAL(10,2) DEFAULT 0 AFTER descuento,
    ADD FOREIGN KEY (promocion_id) REFERENCES promociones(id) ON DELETE SET NULL;

-- Datos de ejemplo
INSERT INTO promociones (nombre, descripcion, tipo, valor, compras_minimas) VALUES
('Bienvenida', 'Descuento para clientes con al menos 1 compra', 'porcentaje', 5, 1),
('Cliente Frecuente', 'Descuento especial por 3 o más compras', 'porcentaje', 10, 3),
('Cliente VIP', '15% de descuento para clientes con 5 o más compras', 'porcentaje', 15, 5),
('Descuento Especial $20.000', 'Descuento fijo para clientes con 2 o más compras', 'monto_fijo', 20000, 2);
