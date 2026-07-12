-- ===================================
-- AGREGAR COLUMNA sexo A clientes
-- ===================================
ALTER TABLE `clientes` ADD COLUMN `sexo` ENUM('M', 'F', 'O') DEFAULT 'O' AFTER `telefono`;

-- ===================================
-- TABLA PROMOCIONES
-- ===================================
CREATE TABLE IF NOT EXISTS `promociones` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `descripcion` TEXT,
    `tipo` ENUM('porcentaje', 'fijo') NOT NULL DEFAULT 'porcentaje',
    `valor` DECIMAL(10,2) NOT NULL,
    `fecha_inicio` DATETIME,
    `fecha_fin` DATETIME,
    `aplica_sexo` ENUM('todos', 'masculino', 'femenino') DEFAULT 'todos',
    `activa` BOOLEAN DEFAULT 1,
    `creada` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `actualizada` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================
-- AGREGAR COLUMNA promocion_id A ventas
-- ===================================
ALTER TABLE `ventas` ADD COLUMN `promocion_id` INT NULL AFTER `usuario_id`;
ALTER TABLE `ventas` ADD FOREIGN KEY (`promocion_id`) REFERENCES `promociones`(`id`) ON DELETE SET NULL;

-- ===================================
-- DATOS INICIALES (OPCIONAL)
-- ===================================
INSERT INTO `promociones` (`nombre`, `descripcion`, `tipo`, `valor`, `aplica_sexo`, `activa`) VALUES
('Descuento 10%', 'Descuento del 10% en toda la tienda', 'porcentaje', 10, 'todos', 1),
('Descuento 15%', 'Descuento del 15% en compras mayores a $100k', 'porcentaje', 15, 'todos', 1),
('Descuento fijo $5k', 'Descuento fijo de $5.000', 'fijo', 5000, 'todos', 1),
('Black Friday', 'Descuento especial Black Friday 20%', 'porcentaje', 20, 'todos', 1),
('Día de la Madre', 'Descuento especial para mujeres el Día de la Madre', 'porcentaje', 15, 'femenino', 1);
