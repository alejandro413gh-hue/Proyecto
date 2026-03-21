-- Ejecutar en phpMyAdmin → vision_real → SQL
USE vision_real;

-- Agregar columna imagen si no existe
ALTER TABLE productos ADD COLUMN IF NOT EXISTS imagen VARCHAR(255) DEFAULT NULL AFTER categoria_id;
