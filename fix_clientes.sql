-- Add missing columns to clientes table
ALTER TABLE clientes ADD COLUMN nit VARCHAR(50) DEFAULT NULL AFTER nombre;
ALTER TABLE clientes ADD COLUMN factura VARCHAR(100) DEFAULT NULL AFTER direccion;