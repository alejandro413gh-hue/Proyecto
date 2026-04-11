-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 11-04-2026 a las 21:06:48
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `vision_real`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `created_at`) VALUES
(1, 'Dama - Casual', 'Ropa casual para dama', '2026-04-10 23:05:14'),
(2, 'Dama - Formal', 'Ropa formal para dama', '2026-04-10 23:05:14'),
(3, 'Caballero - Casual', 'Ropa casual para caballero', '2026-04-10 23:05:14'),
(4, 'Caballero - Formal', 'Ropa formal para caballero', '2026-04-10 23:05:14'),
(5, 'Accesorios', 'Accesorios y complementos', '2026-04-10 23:05:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `telefono`, `email`, `direccion`, `created_at`) VALUES
(1, 'Cliente General', '000-000-0000', 'general@cliente.com', NULL, '2026-04-10 23:05:14'),
(2, 'María López', '300-123-4567', 'maria@email.com', NULL, '2026-04-10 23:05:14'),
(3, 'Carlos Martínez', '310-987-6543', 'carlos@email.com', NULL, '2026-04-10 23:05:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `descuentos`
--

CREATE TABLE `descuentos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo_descuento` enum('porcentaje','monto_fijo') NOT NULL DEFAULT 'porcentaje',
  `valor` decimal(10,2) NOT NULL,
  `aplica_categoria_id` int(11) DEFAULT NULL,
  `aplica_producto_id` int(11) DEFAULT NULL,
  `aplica_genero` enum('dama','caballero','todos') DEFAULT 'todos',
  `compras_minimas` int(11) DEFAULT 0,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `descuentos`
--

INSERT INTO `descuentos` (`id`, `nombre`, `descripcion`, `tipo_descuento`, `valor`, `aplica_categoria_id`, `aplica_producto_id`, `aplica_genero`, `compras_minimas`, `fecha_inicio`, `fecha_fin`, `activo`, `created_at`) VALUES
(1, 'Día de la Mujer', '15% para toda la colección de dama el 8 de marzo', 'porcentaje', 15.00, NULL, NULL, 'dama', 0, '2026-03-08', '2026-03-08', 1, '2026-04-10 23:05:15'),
(2, 'Día de la Madre', '20% para dama en el día de la madre', 'porcentaje', 20.00, NULL, NULL, 'dama', 0, '2026-05-11', '2026-05-12', 1, '2026-04-10 23:05:15'),
(3, 'Día del Hombre', '15% para caballeros el día del hombre', 'porcentaje', 15.00, NULL, NULL, 'caballero', 0, '2026-11-19', '2026-11-19', 1, '2026-04-10 23:05:15'),
(4, 'Cliente Fiel', '10% para clientes con 3 o más compras', 'porcentaje', 10.00, NULL, NULL, 'todos', 3, NULL, NULL, 1, '2026-04-10 23:05:15'),
(5, 'Cliente VIP', '20% para clientes con 5 o más compras', 'porcentaje', 20.00, NULL, NULL, 'todos', 5, NULL, NULL, 1, '2026-04-10 23:05:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `talla` varchar(20) DEFAULT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `categoria_id` int(11) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `nombre`, `descripcion`, `precio`, `stock`, `categoria_id`, `imagen`, `activo`, `created_at`) VALUES
(1, 'Blusa Floral Manga Corta', '', 45000.00, 194, 1, NULL, 1, '2026-04-10 23:05:14'),
(2, 'Vestido Casual Verano', NULL, 85000.00, 13, 1, NULL, 1, '2026-04-10 23:05:14'),
(3, 'Blusa Formal Blanca', NULL, 55000.00, 151, 2, NULL, 1, '2026-04-10 23:05:14'),
(4, 'Pantalón Formal Dama', NULL, 75000.00, 18, 2, NULL, 1, '2026-04-10 23:05:14'),
(5, 'Camisa Casual Cuadros', NULL, 48000.00, 25, 3, NULL, 1, '2026-04-10 23:05:14'),
(6, 'Jean Slim Fit Caballero', NULL, 90000.00, 20, 3, NULL, 1, '2026-04-10 23:05:14'),
(7, 'Camisa Formal Blanca', NULL, 65000.00, 14, 4, NULL, 1, '2026-04-10 23:05:14'),
(8, 'Pantalón Formal Caballero', NULL, 80000.00, 9, 4, NULL, 1, '2026-04-10 23:05:14'),
(9, 'Cinturón de Cuero', NULL, 35000.00, 30, 5, NULL, 1, '2026-04-10 23:05:14'),
(10, 'Bolso Casual Dama', NULL, 95000.00, 8, 5, NULL, 1, '2026-04-10 23:05:14'),
(11, 'camisa obersai', '', 54000.00, 19, 3, NULL, 0, '2026-04-11 18:31:34'),
(12, 'camisa obversai', '', 47000.00, 79, 3, NULL, 1, '2026-04-11 19:02:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_tallas`
--

CREATE TABLE `producto_tallas` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `talla` varchar(20) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `producto_tallas`
--

INSERT INTO `producto_tallas` (`id`, `producto_id`, `talla`, `stock`) VALUES
(1, 1, 'XS', 45),
(2, 1, 'S', 8),
(3, 1, 'M', 8),
(4, 1, 'L', 100),
(5, 1, 'XL', 30),
(6, 2, 'S', 4),
(7, 2, 'M', 5),
(8, 2, 'L', 3),
(9, 2, 'XL', 1),
(10, 3, 'XS', 3),
(11, 3, 'S', 4),
(12, 3, 'M', 4),
(13, 3, 'L', 100),
(14, 4, '26', 3),
(15, 4, '28', 4),
(16, 4, '30', 5),
(17, 4, '32', 4),
(18, 4, '34', 2),
(19, 5, 'S', 5),
(20, 5, 'M', 8),
(21, 5, 'L', 7),
(22, 5, 'XL', 4),
(23, 5, 'XXL', 1),
(24, 6, '28', 3),
(25, 6, '30', 5),
(26, 6, '32', 6),
(27, 6, '34', 4),
(28, 6, '36', 2),
(29, 7, 'S', 3),
(30, 7, 'M', 5),
(31, 7, 'L', 4),
(32, 7, 'XL', 2),
(33, 8, '28', 2),
(34, 8, '30', 3),
(35, 8, '32', 3),
(36, 8, '34', 1),
(37, 9, 'Único', 30),
(38, 10, 'Único', 8),
(44, 3, 'XL', 40),
(51, 1, 'XXL', 4),
(52, 11, 'L', 13),
(53, 11, 'S', 6),
(54, 12, 'L', 45),
(55, 12, 'S', 34);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `promociones`
--

CREATE TABLE `promociones` (
  `id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` enum('porcentaje','monto_fijo') NOT NULL DEFAULT 'porcentaje',
  `valor` decimal(10,2) NOT NULL,
  `compras_minimas` int(11) NOT NULL DEFAULT 1,
  `activa` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `promociones`
--

INSERT INTO `promociones` (`id`, `nombre`, `descripcion`, `tipo`, `valor`, `compras_minimas`, `activa`, `created_at`) VALUES
(1, 'Bienvenida', 'Descuento para clientes con al menos 1 compra', 'porcentaje', 5.00, 1, 1, '2026-04-10 23:05:15'),
(2, 'Cliente Frecuente', 'Descuento especial por 3 o más compras', 'porcentaje', 10.00, 3, 1, '2026-04-10 23:05:15'),
(3, 'Cliente VIP', '15% para clientes con 5 o más compras', 'porcentaje', 15.00, 5, 1, '2026-04-10 23:05:15'),
(4, 'Descuento $20.000', 'Descuento fijo para clientes con 2 o más compras', 'monto_fijo', 20000.00, 2, 1, '2026-04-10 23:05:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('administrador','gestor_inventario','vendedor') NOT NULL DEFAULT 'vendedor',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `activo`, `created_at`) VALUES
(1, 'Rufay Gutierrez', 'rufay@gmail.com', '$2y$10$z5lwsJnSWo.ZCkeH9JLa7OTDfu/qypU4isDeETgcSRgTpJmURI41i', 'administrador', 1, '2026-04-10 23:05:14'),
(2, 'Vendedor', 'vendedor@gmail.com', '$2y$10$RbOuDkEsVV8v1WyeM0SOXOD1zYD6Jskc/x65qdcM.ZvGevlg9yOT.', 'vendedor', 1, '2026-04-10 23:05:14'),
(3, 'dd', 'alejandro@gamil.com', '$2y$10$RenL3pdw6qDr.yM46yt3P.p0X5xMP8ZYlCCqYurO17o3TmxBr93Ya', 'gestor_inventario', 1, '2026-04-11 17:15:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `estado` enum('completada','pendiente','cancelada') DEFAULT 'completada',
  `notas` text DEFAULT NULL,
  `promocion_id` int(11) DEFAULT NULL,
  `descuento` decimal(10,2) DEFAULT 0.00,
  `descuento_id` int(11) DEFAULT NULL,
  `descuento_aplicado` text DEFAULT NULL,
  `total_sin_descuento` decimal(10,2) DEFAULT 0.00,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `descuentos`
--
ALTER TABLE `descuentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aplica_categoria_id` (`aplica_categoria_id`),
  ADD KEY `aplica_producto_id` (`aplica_producto_id`);

--
-- Indices de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venta_id` (`venta_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Indices de la tabla `producto_tallas`
--
ALTER TABLE `producto_tallas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_prod_talla` (`producto_id`,`talla`);

--
-- Indices de la tabla `promociones`
--
ALTER TABLE `promociones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `descuentos`
--
ALTER TABLE `descuentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `producto_tallas`
--
ALTER TABLE `producto_tallas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT de la tabla `promociones`
--
ALTER TABLE `promociones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `descuentos`
--
ALTER TABLE `descuentos`
  ADD CONSTRAINT `descuentos_ibfk_1` FOREIGN KEY (`aplica_categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `descuentos_ibfk_2` FOREIGN KEY (`aplica_producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `detalle_venta_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_venta_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `producto_tallas`
--
ALTER TABLE `producto_tallas`
  ADD CONSTRAINT `producto_tallas_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
