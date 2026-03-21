CREATE DATABASE IF NOT EXISTS vision_real CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vision_real;

CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'vendedor') NOT NULL DEFAULT 'vendedor',
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(150),
    direccion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    categoria_id INT,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT,
    usuario_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('completada','pendiente','cancelada') DEFAULT 'completada',
    notas TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE detalle_venta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB;

INSERT INTO categorias (nombre, descripcion) VALUES
('Dama - Casual','Ropa casual para dama'),
('Dama - Formal','Ropa formal para dama'),
('Caballero - Casual','Ropa casual para caballero'),
('Caballero - Formal','Ropa formal para caballero'),
('Accesorios','Accesorios y complementos');

-- password: password
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador','admin@visionreal.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','administrador'),
('Vendedor Demo','vendedor@visionreal.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','vendedor');

INSERT INTO clientes (nombre, telefono, email) VALUES
('Cliente General','000-000-0000','general@cliente.com'),
('María López','300-123-4567','maria@email.com'),
('Carlos Martínez','310-987-6543','carlos@email.com');

INSERT INTO productos (nombre, precio, stock, categoria_id) VALUES
('Blusa Floral Manga Corta',45000,20,1),
('Vestido Casual Verano',85000,15,1),
('Blusa Formal Blanca',55000,12,2),
('Pantalón Formal Dama',75000,18,2),
('Camisa Casual Cuadros',48000,25,3),
('Jean Slim Fit Caballero',90000,20,3),
('Camisa Formal Blanca',65000,15,4),
('Pantalón Formal Caballero',80000,10,4),
('Cinturón de Cuero',35000,30,5),
('Bolso Casual Dama',95000,8,5);
