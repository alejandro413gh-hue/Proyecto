-- ============================================
-- SISTEMA DE TALLAS — Visión Real
-- Ejecutar en phpMyAdmin sobre la BD vision_real
-- ============================================
USE vision_real;

-- Tabla de tallas por producto
CREATE TABLE IF NOT EXISTS producto_tallas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    talla VARCHAR(20) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    UNIQUE KEY uk_prod_talla (producto_id, talla),
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Agregar columna talla al detalle de venta
ALTER TABLE detalle_venta
    ADD COLUMN IF NOT EXISTS talla VARCHAR(20) NULL AFTER cantidad;

-- Tallas de ejemplo para los productos existentes
INSERT IGNORE INTO producto_tallas (producto_id, talla, stock) VALUES
-- Blusa Floral Manga Corta (id=1)
(1,'XS',3),(1,'S',5),(1,'M',6),(1,'L',4),(1,'XL',2),
-- Vestido Casual Verano (id=2)
(2,'S',4),(2,'M',5),(2,'L',3),(2,'XL',1),
-- Blusa Formal Blanca (id=3)
(3,'XS',2),(3,'S',4),(3,'M',4),(3,'L',2),
-- Pantalón Formal Dama (id=4)
(4,'26',3),(4,'28',4),(4,'30',5),(4,'32',4),(4,'34',2),
-- Camisa Casual Cuadros (id=5)
(5,'S',5),(5,'M',8),(5,'L',7),(5,'XL',4),(5,'XXL',1),
-- Jean Slim Fit Caballero (id=6)
(6,'28',3),(6,'30',5),(6,'32',6),(6,'34',4),(6,'36',2),
-- Camisa Formal Blanca (id=7)
(7,'S',3),(7,'M',5),(7,'L',4),(7,'XL',2),(7,'XXL',1),
-- Pantalón Formal Caballero (id=8)
(8,'28',2),(8,'30',3),(8,'32',3),(8,'34',1),(8,'36',1),
-- Cinturón de Cuero (id=9)
(9,'Único',30),
-- Bolso Casual Dama (id=10)
(10,'Único',8);
