-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Servidor: sql209.infinityfree.com
-- Tiempo de generación: 12-07-2026 a las 22:40:43
-- Versión del servidor: 11.4.12-MariaDB
-- Versión de PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `if0_41735490_vision_real`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrito`
--

CREATE TABLE `carrito` (
  `id` int(11) NOT NULL,
  `cliente_online_id` int(11) NOT NULL,
  `creado_at` timestamp NULL DEFAULT current_timestamp(),
  `actualizado_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `carrito`
--

INSERT INTO `carrito` (`id`, `cliente_online_id`, `creado_at`, `actualizado_at`) VALUES
(1, 1, '2026-05-27 20:12:37', '2026-05-27 20:12:37'),
(2, 3, '2026-05-28 11:27:05', '2026-05-28 11:27:05'),
(3, 5, '2026-07-11 00:05:07', '2026-07-11 00:05:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrito_items`
--

CREATE TABLE `carrito_items` (
  `id` int(11) NOT NULL,
  `carrito_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `talla` varchar(20) DEFAULT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(12,2) NOT NULL,
  `agregado_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `carrito_items`
--

INSERT INTO `carrito_items` (`id`, `carrito_id`, `producto_id`, `talla`, `cantidad`, `precio_unitario`, `agregado_at`) VALUES
(19, 3, 12, 'L', 1, '47000.00', '2026-07-11 00:49:19'),
(20, 3, 2, 'L', 1, '85000.00', '2026-07-11 01:10:06');

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
  `sexo` enum('M','F','O') DEFAULT 'O',
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `factura` varchar(50) DEFAULT NULL,
  `nit` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `sexo`, `telefono`, `email`, `direccion`, `created_at`, `factura`, `nit`) VALUES
(12, 'Alejandro Gutierrez', 'M', '3102813913', 'alejandro413gh@gmail.com', '', '2026-05-28 04:44:08', NULL, ''),
(13, 'Kenyi Contreras', 'M', '3115523760', 'brayanarmesto17@gmail.com', '', '2026-05-28 11:27:52', NULL, ''),
(14, 'susana', 'F', '', 'su@gmail.com', '', '2026-07-11 00:05:22', NULL, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes_online`
--

CREATE TABLE `clientes_online` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `nombre` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefono` varchar(30) DEFAULT '',
  `direccion` text DEFAULT '',
  `ciudad` varchar(80) DEFAULT '',
  `sexo` enum('M','F','O') DEFAULT 'O',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `verificado` tinyint(1) NOT NULL DEFAULT 0,
  `token_verificar` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes_online`
--

INSERT INTO `clientes_online` (`id`, `cliente_id`, `nombre`, `email`, `password`, `telefono`, `direccion`, `ciudad`, `sexo`, `activo`, `verificado`, `token_verificar`, `created_at`, `updated_at`) VALUES
(5, 14, 'susana', 'su@gmail.com', '$2y$10$uIfNOpAsb.qAfbGJnW4JQuXSUDqAxtBB92IXlYYrLe8R2RGWZRDoS', '', '', '', 'F', 1, 0, '11272a3056ce370c317befff4188bb948346bd885368f776787b22054f5e73da', '2026-07-11 00:04:27', '2026-07-11 00:05:22'),
(6, NULL, 'Pedro', 'p@gmail.com', '$2y$10$koM9YhuaPKRvHI/f9a.Px.HkTfCltBurYJOCtgQbjbvKDT/KXZk0u', '', '', '', 'O', 1, 0, '46ea452f59549fe735f5646ab7b4b20b0c3162b9a017b3f76a8e4cde631d7d9d', '2026-07-12 02:25:00', '2026-07-12 02:25:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes_online_sesiones`
--

CREATE TABLE `clientes_online_sesiones` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expira_at` datetime NOT NULL,
  `creado_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes_online_sesiones`
--

INSERT INTO `clientes_online_sesiones` (`id`, `cliente_id`, `token`, `expira_at`, `creado_at`) VALUES
(1, 4, 'e8137bbc9f1af5ac50237bfee2b50e8015891eb949affbdca46609b070aa839d', '2026-08-09 17:24:04', '2026-07-10 22:24:04'),
(9, 5, 'b0f103e3d4d447d83d65b92863c00bdb93431009df822945212fedf9c51ec4fb', '2026-08-09 19:04:28', '2026-07-11 00:04:29'),
(10, 6, 'fbdd4caacd87127634c5dc7f1b88e594e277812960225f3f3807cb5f698f3fcd', '2026-08-10 21:25:00', '2026-07-12 02:25:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_tienda`
--

CREATE TABLE `configuracion_tienda` (
  `clave` varchar(60) NOT NULL,
  `valor` text NOT NULL DEFAULT '',
  `actualizada` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion_tienda`
--

INSERT INTO `configuracion_tienda` (`clave`, `valor`, `actualizada`) VALUES
('admin_email', 'rufay0813@gmail.com', '2026-06-03 13:14:18'),
('tienda_direccion', 'Recoge tu pedido en tienda', '2026-06-03 13:14:18'),
('tienda_maps_link', '', '2026-06-03 13:14:18'),
('whatsapp_numero', '573125420576', '2026-06-03 13:14:18');

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
(1, 'Día de la Mujer', '15% para toda la colección de dama el 8 de marzo', 'porcentaje', '15.00', NULL, NULL, 'dama', 0, '2026-03-08', '2026-03-08', 1, '2026-04-10 23:05:15'),
(2, 'Día de la Madre', '20% para dama en el día de la madre', 'porcentaje', '20.00', NULL, NULL, '', 0, '2026-05-11', '2026-05-12', 1, '2026-04-10 23:05:15'),
(3, 'Día del Hombre', '15% para caballeros el día del hombre', 'porcentaje', '15.00', NULL, NULL, 'caballero', 0, '2026-11-19', '2026-11-19', 1, '2026-04-10 23:05:15'),
(6, 'descuento aprendiz sena', '', 'porcentaje', '10.00', NULL, NULL, 'caballero', 0, '2026-05-14', '2026-06-16', 1, '2026-05-14 13:23:19');

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

--
-- Volcado de datos para la tabla `detalle_venta`
--

INSERT INTO `detalle_venta` (`id`, `venta_id`, `producto_id`, `cantidad`, `talla`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 8, 1, '28', '80000.00', '80000.00'),
(2, 2, 2, 1, 'XL', '85000.00', '85000.00'),
(3, 2, 2, 1, 'M', '85000.00', '85000.00'),
(4, 2, 2, 1, 'L', '85000.00', '85000.00'),
(5, 3, 7, 1, 'XL', '65000.00', '65000.00'),
(6, 3, 7, 1, 'M', '65000.00', '65000.00'),
(7, 4, 3, 4, 'XL', '55000.00', '220000.00'),
(8, 4, 3, 1, 'L', '55000.00', '55000.00'),
(9, 5, 3, 4, 'XL', '55000.00', '220000.00'),
(10, 5, 3, 2, 'L', '55000.00', '110000.00'),
(11, 6, 1, 10, 'L', '45000.00', '450000.00'),
(12, 7, 6, 1, '30', '90000.00', '90000.00'),
(13, 8, 12, 1, 'L', '47000.00', '47000.00'),
(14, 9, 3, 1, 'XL', '55000.00', '55000.00'),
(15, 9, 7, 1, 'XL', '65000.00', '65000.00'),
(16, 9, 7, 1, 'L', '65000.00', '65000.00'),
(17, 9, 7, 1, 'M', '65000.00', '65000.00'),
(18, 9, 1, 2, 'XS', '45000.00', '90000.00'),
(19, 10, 12, 3, 'S', '47000.00', '141000.00'),
(20, 11, 1, 7, 'XXL', '45000.00', '315000.00'),
(21, 12, 12, 1, 'L', '47000.00', '47000.00'),
(22, 13, 12, 30, 'S', '47000.00', '1410000.00'),
(23, 14, 3, 1, 'XS', '55000.00', '55000.00'),
(24, 15, 12, 1, 'S', '47000.00', '47000.00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

CREATE TABLE `facturas` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `numero_factura` varchar(50) NOT NULL,
  `cliente_nombre` varchar(100) NOT NULL,
  `cliente_documento` varchar(50) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `descuento` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `fecha` timestamp NULL DEFAULT current_timestamp(),
  `estado` varchar(20) DEFAULT 'generada'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `facturas`
--

INSERT INTO `facturas` (`id`, `venta_id`, `numero_factura`, `cliente_nombre`, `cliente_documento`, `subtotal`, `descuento`, `total`, `fecha`, `estado`) VALUES
(1, 1, 'FAC-20260506-00001', 'RU', '1213212', '80000.00', '0.00', '80000.00', '2026-05-06 13:43:08', 'generada'),
(2, 2, 'FAC-20260506-00002', 'dd', '1212', '255000.00', '0.00', '255000.00', '2026-05-06 14:19:20', 'generada'),
(3, 3, 'FAC-20260506-00003', 'r', '123454', '130000.00', '0.00', '130000.00', '2026-05-06 14:22:38', 'generada'),
(4, 4, 'FAC-20260507-00001', 'nelson', '121221', '275000.00', '0.00', '275000.00', '2026-05-07 14:22:47', 'generada'),
(5, 5, 'FAC-20260507-00002', 'sdnkdsnk.', 'casrfffw', '330000.00', '0.00', '330000.00', '2026-05-07 14:25:16', 'generada'),
(6, 6, 'FAC-20260508-00001', 'alex vera', '108732786487', '450000.00', '0.00', '450000.00', '2026-05-08 16:03:33', 'generada'),
(7, 7, 'FAC-20260514-00001', 'nelson rincon', '8821515612', '90000.00', '0.00', '90000.00', '2026-05-14 13:24:53', 'generada'),
(8, 8, 'FAC-20260525-00001', 'fg', '123412424', '47000.00', '4700.00', '42300.00', '2026-05-25 11:47:34', 'generada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_tienda`
--

CREATE TABLE `pagos_tienda` (
  `id` int(11) NOT NULL,
  `metodo` varchar(50) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `valor` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pagos_tienda`
--

INSERT INTO `pagos_tienda` (`id`, `metodo`, `clave`, `valor`, `imagen`, `updated_at`) VALUES
(1, 'transferencia', 'banco', '', NULL, '2026-05-27 20:28:16'),
(2, 'nequi', 'numero', '3107071707', NULL, '2026-05-27 20:38:41'),
(4, 'nequi', 'qr', NULL, 'nequi_qr_1779939517.png', '2026-05-27 20:38:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `numero_pedido` varchar(30) NOT NULL,
  `cliente_online_id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `venta_id` int(11) DEFAULT NULL,
  `estado` enum('pendiente','pagado','preparando','enviado','entregado','cancelado') DEFAULT 'pendiente',
  `tipo_entrega` enum('domicilio','recoge_tienda','recoger_tienda') DEFAULT 'domicilio',
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `descuento` decimal(12,2) NOT NULL DEFAULT 0.00,
  `descuento_id` int(11) DEFAULT NULL,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notas` text DEFAULT NULL,
  `envio_nombre` varchar(120) DEFAULT NULL,
  `envio_telefono` varchar(30) DEFAULT NULL,
  `envio_direccion` text DEFAULT NULL,
  `envio_ciudad` varchar(80) DEFAULT NULL,
  `metodo_pago` varchar(50) DEFAULT NULL,
  `referencia_pago` varchar(100) DEFAULT NULL,
  `comprobante_img` varchar(255) DEFAULT NULL,
  `creado_at` timestamp NULL DEFAULT current_timestamp(),
  `actualizado_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `printable_token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `numero_pedido`, `cliente_online_id`, `cliente_id`, `venta_id`, `estado`, `tipo_entrega`, `subtotal`, `descuento`, `descuento_id`, `total`, `notas`, `envio_nombre`, `envio_telefono`, `envio_direccion`, `envio_ciudad`, `metodo_pago`, `referencia_pago`, `comprobante_img`, `creado_at`, `actualizado_at`, `printable_token`) VALUES
(1, 'ON-20260527-0001', 1, NULL, 9, 'cancelado', 'domicilio', '340000.00', '34000.00', 6, '306000.00', '', 'Alejandro Gutierrez', '3102813913', 'Carrera 13 3N-80 Barrio San Gregorio', 'Villa del rosario', 'nequi', '310 7971707', NULL, '2026-05-27 22:35:28', '2026-05-27 22:38:09', NULL),
(2, 'ON-20260527-0002', 1, NULL, NULL, 'cancelado', 'domicilio', '145000.00', '14500.00', 6, '130500.00', '', 'Alejandro Gutierrez', '3102813913', 'Carrera 13 3N-80 Barrio San Gregorio villa del rosario', 'Villa del rosario', 'contraentrega', NULL, NULL, '2026-05-27 23:19:05', '2026-05-28 03:34:36', NULL),
(3, 'ON-20260527-0003', 1, NULL, NULL, 'cancelado', 'domicilio', '141000.00', '14100.00', 6, '126900.00', '', 'Alejandro Gutierrez', '3102813913', 'Carrera 13 3N-80 Barrio San Gregorio', 'Villa del rosario', 'contraentrega', NULL, NULL, '2026-05-28 03:39:17', '2026-05-28 04:11:14', '04dca6a5cd9e55980bf55b1f600bc375'),
(4, 'ON-20260527-0004', 1, NULL, NULL, 'cancelado', 'domicilio', '141000.00', '14100.00', 6, '126900.00', '', 'Alejandro Gutierrez', '3102813913', 'Carrera 13 3N-80 Barrio San Gregorio', 'Villa del rosario', 'contraentrega', NULL, NULL, '2026-05-28 03:39:22', '2026-05-28 04:11:19', '16ff2c7c6722d2140295a27580b9e399'),
(5, 'ON-20260527-0005', 1, NULL, NULL, 'cancelado', 'domicilio', '141000.00', '14100.00', 6, '126900.00', '', 'Alejandro Gutierrez', '3102813913', 'Carrera 13 3N-80 Barrio San Gregorio', 'Villa del rosario', 'contraentrega', NULL, NULL, '2026-05-28 04:08:07', '2026-05-28 04:11:23', '3d4f2de592659eb61c19edded3351e55'),
(6, 'ON-20260527-0006', 1, NULL, NULL, 'cancelado', 'domicilio', '141000.00', '14100.00', 6, '126900.00', '', 'Alejandro Gutierrez', '3102813913', 'Carrera 13 3N-80 Barrio San Gregorio', 'Villa del rosario', 'contraentrega', NULL, NULL, '2026-05-28 04:08:09', '2026-05-28 04:11:27', '1b43ee4ad793b68d0b7d6c7b62ea8c08'),
(7, 'ON-20260527-0007', 1, NULL, NULL, 'cancelado', 'domicilio', '141000.00', '14100.00', 6, '126900.00', '', 'Alejandro Gutierrez', '3102813913', 'Carrera 13 3N-80 Barrio San Gregorio', 'Villa del rosario', 'contraentrega', NULL, NULL, '2026-05-28 04:08:45', '2026-05-28 04:11:32', '4cf20c1f25a62cf82f394f4a05987a31'),
(8, 'ON-20260527-0008', 1, NULL, NULL, 'cancelado', 'domicilio', '141000.00', '14100.00', 6, '126900.00', '', 'Alejandro Gutierrez', '3102813913', 'Carrera 13 3N-80 Barrio San Gregorio', 'Villa del rosario', 'contraentrega', NULL, NULL, '2026-05-28 04:08:52', '2026-05-28 04:14:37', 'f3ad207b5a013b922158240bd6d28221'),
(9, 'ON-20260527-0009', 1, NULL, 10, 'cancelado', 'domicilio', '141000.00', '14100.00', 6, '126900.00', '', 'Alejandro Gutierrez', '3102813913', 'Carrera 13 3N-80 Barrio San Gregorio', 'Villa del rosario', 'contraentrega', 'listo', NULL, '2026-05-28 04:13:20', '2026-05-28 04:34:57', '4e9a978b4b218e66ba6d536e974f914c'),
(10, 'ON-20260527-0010', 1, NULL, 11, 'entregado', 'domicilio', '315000.00', '31500.00', 6, '283500.00', '', 'Alejandro Gutierrez', '3102813913', 'Carrera 13 3N-80 Barrio San Gregorio', 'Villa del rosario', 'contraentrega', 'nequi', NULL, '2026-05-28 04:31:18', '2026-05-28 04:32:49', '3967e289ba5463cbba61c7cf80a8e9e4'),
(11, 'ON-20260527-0011', 1, 12, 12, 'entregado', 'domicilio', '47000.00', '4700.00', 6, '42300.00', '', 'Alejandro Gutierrez', '3102813913', 'Carrera 13 3N-80 Barrio San Gregorio', 'Villa Del rosario', 'contraentrega', 'nequi', NULL, '2026-05-28 04:44:08', '2026-05-28 04:45:15', '6d2a3bd5e6d3ebfabe0488427ed0f3e0'),
(12, 'ON-20260528-0001', 1, 12, NULL, 'cancelado', 'domicilio', '47000.00', '4700.00', 6, '42300.00', '', 'Alejandro Gutierrez', '3102813913', 'Varre', 'Cucuta', 'contraentrega', NULL, NULL, '2026-05-28 11:12:09', '2026-05-28 14:04:42', '20d59d3d8e3e49a6c10100eb0ebb5ea7'),
(13, 'ON-20260528-0002', 1, 12, NULL, 'cancelado', 'recoge_tienda', '190000.00', '19000.00', 6, '171000.00', '', 'Alejandro Gutierrez', '3102813913', '', '', 'nequi', NULL, NULL, '2026-05-28 11:23:49', '2026-05-28 14:04:41', '2271ea337aa8c9450a80121655e8218b'),
(14, 'ON-20260528-0003', 3, 13, 13, 'entregado', 'recoge_tienda', '1410000.00', '141000.00', 6, '1269000.00', '', 'Kenyi Contreras', '3115523760', '', '', 'contraentrega', 'nequi', NULL, '2026-05-28 11:27:52', '2026-05-28 14:04:32', '843234d1a0f96527f00f7df2848e3f1a'),
(15, 'ON-20260528-0004', 3, 13, 14, 'entregado', 'domicilio', '55000.00', '5500.00', 6, '49500.00', 'Pene', 'Kenyi Contreras', '3115523760', 'Dónde suma', 'Venezuela', 'contraentrega', 'nequi', NULL, '2026-05-28 11:30:07', '2026-05-28 14:04:21', 'defe40ccdee14f62a983a5a68633b3dd'),
(16, 'ON-20260528-0005', 3, 13, 15, 'entregado', 'domicilio', '47000.00', '4700.00', 6, '42300.00', '', 'Kenyi Contreras', '3115523760', 'Cara', 'Venezuela', 'contraentrega', 'j', NULL, '2026-05-28 11:31:28', '2026-05-28 11:34:52', '608b1b828f599555bb0da0314265f099'),
(17, 'ON-20260710-0001', 5, 14, NULL, 'cancelado', 'recoge_tienda', '45000.00', '0.00', NULL, '45000.00', '', 'susana', '312122222', '', '', 'transferencia', NULL, NULL, '2026-07-11 00:05:22', '2026-07-11 00:06:07', 'cb0b194b927ebe625c0d5f00f73a7276');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido_detalle`
--

CREATE TABLE `pedido_detalle` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `talla` varchar(20) DEFAULT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedido_detalle`
--

INSERT INTO `pedido_detalle` (`id`, `pedido_id`, `producto_id`, `talla`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 3, 'XL', 1, '55000.00', '55000.00'),
(2, 1, 7, 'XL', 1, '65000.00', '65000.00'),
(3, 1, 7, 'L', 1, '65000.00', '65000.00'),
(4, 1, 7, 'M', 1, '65000.00', '65000.00'),
(5, 1, 1, 'XS', 2, '45000.00', '90000.00'),
(6, 2, 3, 'L', 1, '55000.00', '55000.00'),
(7, 2, 1, 'XS', 2, '45000.00', '90000.00'),
(8, 3, 12, 'S', 3, '47000.00', '141000.00'),
(9, 4, 12, 'S', 3, '47000.00', '141000.00'),
(10, 5, 12, 'S', 3, '47000.00', '141000.00'),
(11, 6, 12, 'S', 3, '47000.00', '141000.00'),
(12, 7, 12, 'S', 3, '47000.00', '141000.00'),
(13, 8, 12, 'S', 3, '47000.00', '141000.00'),
(14, 9, 12, 'S', 3, '47000.00', '141000.00'),
(15, 10, 1, 'XXL', 7, '45000.00', '315000.00'),
(16, 11, 12, 'L', 1, '47000.00', '47000.00'),
(17, 12, 12, 'S', 1, '47000.00', '47000.00'),
(18, 13, 10, 'Único', 2, '95000.00', '190000.00'),
(19, 14, 12, 'S', 30, '47000.00', '1410000.00'),
(20, 15, 3, 'XS', 1, '55000.00', '55000.00'),
(21, 16, 12, 'S', 1, '47000.00', '47000.00'),
(22, 17, 1, 'L', 1, '45000.00', '45000.00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido_historial`
--

CREATE TABLE `pedido_historial` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `estado_ant` varchar(30) DEFAULT NULL,
  `estado_new` varchar(30) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `nota` text DEFAULT NULL,
  `creado_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedido_historial`
--

INSERT INTO `pedido_historial` (`id`, `pedido_id`, `estado_ant`, `estado_new`, `usuario_id`, `nota`, `creado_at`) VALUES
(1, 1, NULL, 'pendiente', NULL, 'Pedido creado online', '2026-05-27 22:35:28'),
(2, 1, 'pendiente', 'pagado', 15, 'Pago confirmado. Venta #9', '2026-05-27 22:37:49'),
(3, 1, 'pagado', 'preparando', 15, '', '2026-05-27 22:38:04'),
(4, 1, 'preparando', 'cancelado', 15, 'k', '2026-05-27 22:38:09'),
(5, 1, 'cancelado', 'cancelado', 15, 'j', '2026-05-27 22:38:16'),
(6, 2, NULL, 'pendiente', NULL, 'Pedido creado online', '2026-05-27 23:19:05'),
(7, 2, 'pendiente', 'cancelado', 15, 'cancelado', '2026-05-28 03:34:36'),
(14, 3, 'pendiente', 'cancelado', 15, 'l', '2026-05-28 04:11:14'),
(15, 4, 'pendiente', 'cancelado', 15, 'l', '2026-05-28 04:11:19'),
(16, 5, 'pendiente', 'cancelado', 15, 'l', '2026-05-28 04:11:23'),
(17, 6, 'pendiente', 'cancelado', 15, 'l', '2026-05-28 04:11:27'),
(18, 7, 'pendiente', 'cancelado', 15, 'l', '2026-05-28 04:11:32'),
(19, 9, NULL, 'pendiente', NULL, 'Pedido creado online', '2026-05-28 04:13:20'),
(20, 8, 'pendiente', 'cancelado', 15, 'j', '2026-05-28 04:14:37'),
(21, 9, 'pendiente', 'pagado', 15, 'Pago confirmado. Venta #10', '2026-05-28 04:23:28'),
(22, 9, 'pagado', 'preparando', 15, 'empacado', '2026-05-28 04:24:01'),
(23, 9, 'preparando', 'enviado', 15, '', '2026-05-28 04:26:43'),
(24, 10, NULL, 'pendiente', NULL, 'Pedido creado online', '2026-05-28 04:31:18'),
(25, 10, 'pendiente', 'pagado', 15, 'Pago confirmado. Venta #11', '2026-05-28 04:32:12'),
(26, 10, 'pagado', 'preparando', 15, '', '2026-05-28 04:32:39'),
(27, 10, 'preparando', 'enviado', 15, '', '2026-05-28 04:32:45'),
(28, 10, 'enviado', 'entregado', 15, '', '2026-05-28 04:32:49'),
(29, 9, 'enviado', 'cancelado', 15, '', '2026-05-28 04:34:57'),
(30, 11, NULL, 'pendiente', NULL, 'Pedido creado online', '2026-05-28 04:44:08'),
(31, 11, 'pendiente', 'pagado', 15, 'Pago confirmado. Venta #12', '2026-05-28 04:45:02'),
(32, 11, 'pagado', 'preparando', 15, '', '2026-05-28 04:45:07'),
(33, 11, 'preparando', 'preparando', 15, '', '2026-05-28 04:45:09'),
(34, 11, 'preparando', 'enviado', 15, '', '2026-05-28 04:45:11'),
(35, 11, 'enviado', 'entregado', 15, '', '2026-05-28 04:45:15'),
(36, 12, NULL, 'pendiente', NULL, 'Pedido creado online', '2026-05-28 11:12:09'),
(37, 13, NULL, 'pendiente', NULL, 'Pedido creado online', '2026-05-28 11:23:49'),
(38, 14, NULL, 'pendiente', NULL, 'Pedido creado online', '2026-05-28 11:27:52'),
(39, 14, 'pendiente', 'pagado', 15, 'Pago confirmado. Venta #13', '2026-05-28 11:29:12'),
(40, 14, 'pagado', 'preparando', 15, '', '2026-05-28 11:29:42'),
(41, 15, NULL, 'pendiente', NULL, 'Pedido creado online', '2026-05-28 11:30:07'),
(42, 15, 'pendiente', 'pagado', 15, 'Pago confirmado. Venta #14', '2026-05-28 11:30:37'),
(43, 16, NULL, 'pendiente', NULL, 'Pedido creado online', '2026-05-28 11:31:28'),
(44, 16, 'pendiente', 'pagado', 15, 'Pago confirmado. Venta #15', '2026-05-28 11:31:50'),
(45, 15, 'pagado', 'preparando', 15, 'preparando', '2026-05-28 11:33:12'),
(46, 16, 'pagado', 'preparando', 15, 'preparando', '2026-05-28 11:33:38'),
(47, 16, 'preparando', 'enviado', 15, 'envi', '2026-05-28 11:34:34'),
(48, 16, 'enviado', 'entregado', 15, '.', '2026-05-28 11:34:52'),
(49, 15, 'preparando', 'enviado', 15, '', '2026-05-28 14:04:16'),
(50, 15, 'enviado', 'enviado', 15, '', '2026-05-28 14:04:19'),
(51, 15, 'enviado', 'entregado', 15, '', '2026-05-28 14:04:21'),
(52, 15, 'entregado', 'entregado', 15, '', '2026-05-28 14:04:23'),
(53, 14, 'preparando', 'enviado', 15, '', '2026-05-28 14:04:27'),
(54, 14, 'enviado', 'entregado', 15, '', '2026-05-28 14:04:32'),
(55, 13, 'pendiente', 'cancelado', 15, '', '2026-05-28 14:04:41'),
(56, 12, 'pendiente', 'cancelado', 15, '', '2026-05-28 14:04:42'),
(57, 17, NULL, 'pendiente', NULL, 'Pedido creado online', '2026-07-11 00:05:22'),
(58, 17, 'pendiente', 'cancelado', 15, 'no pago', '2026-07-11 00:06:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `categoria_id` int(11) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `visible_tienda` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `codigo`, `nombre`, `descripcion`, `precio`, `stock`, `categoria_id`, `imagen`, `activo`, `visible_tienda`, `created_at`) VALUES
(1, 'VR-0001', 'Blusa Floral Manga Corta', '', '45000.00', 189, 1, 'prod_1779710006_3328.webp', 1, 1, '2026-04-10 23:05:14'),
(2, 'VR-0002', 'Vestido Casual Verano', '', '85000.00', 2972, 1, 'prod_1779710033_3812.jpg', 1, 1, '2026-04-10 23:05:14'),
(3, 'VR-0003', 'Blusa Formal Blanca', '', '55000.00', 148, 2, 'prod_1779710059_2793.jpg', 1, 1, '2026-04-10 23:05:14'),
(4, 'VR-0004', 'Pantalón Formal Dama', '', '75000.00', 37, 2, 'prod_1779710104_9964.jpg', 1, 1, '2026-04-10 23:05:14'),
(5, 'VR-0005', 'Camisa Casual Cuadros', '', '48000.00', 37, 3, 'prod_1779710164_1144.webp', 1, 1, '2026-04-10 23:05:14'),
(6, 'VR-0006', 'Jean Slim Fit Caballero', '', '90000.00', 35, 3, 'prod_1779710194_4382.webp', 1, 1, '2026-04-10 23:05:14'),
(7, 'VR-0007', 'Camisa Formal Blanca', '', '65000.00', 24, 4, 'prod_1779710216_2173.webp', 1, 1, '2026-04-10 23:05:14'),
(8, 'VR-0008', 'Pantalón Formal Caballero', '', '80000.00', 27, 4, 'prod_1779710237_7820.jpg', 1, 1, '2026-04-10 23:05:14'),
(9, 'VR-0009', 'Cinturón de Cuero', '', '35000.00', 30, 5, 'prod_1779710263_7758.webp', 1, 1, '2026-04-10 23:05:14'),
(10, 'VR-0010', 'Bolso Casual Dama', '', '95000.00', 8, 5, 'prod_1779710313_8389.jpg', 1, 1, '2026-04-10 23:05:14'),
(11, 'DEL001177971016', 'camisa obersai', '', '54000.00', 19, 3, NULL, 0, 0, '2026-04-11 18:31:34'),
(12, 'VR-0011', 'Camisa oversize', '', '47000.00', 54, 3, 'prod_1779710387_1835.webp', 1, 1, '2026-04-11 19:02:40'),
(13, 'DEL001377971016', 'camisa ovbersai', '', '43000.00', 34, 3, NULL, 0, 0, '2026-05-06 12:56:45'),
(14, 'DEL001477980795', 'aaaa', '', '12333.00', 1, 5, NULL, 0, 0, '2026-06-30 21:58:53');

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
(1, 1, 'XS', 43),
(2, 1, 'S', 8),
(3, 1, 'M', 8),
(4, 1, 'L', 90),
(5, 1, 'XL', 30),
(6, 2, 'S', 7),
(7, 2, 'M', 8),
(8, 2, 'L', 8),
(9, 2, 'XL', 2949),
(10, 3, 'XS', 6),
(11, 3, 'S', 7),
(12, 3, 'M', 7),
(13, 3, 'L', 97),
(14, 4, '26', 7),
(15, 4, '28', 7),
(16, 4, '30', 8),
(17, 4, '32', 7),
(18, 4, '34', 8),
(19, 5, 'S', 8),
(20, 5, 'M', 8),
(21, 5, 'L', 7),
(22, 5, 'XL', 7),
(23, 5, 'XXL', 7),
(24, 6, '28', 7),
(25, 6, '30', 7),
(26, 6, '32', 6),
(27, 6, '34', 8),
(28, 6, '36', 7),
(29, 7, 'S', 6),
(30, 7, 'M', 7),
(31, 7, 'L', 6),
(32, 7, 'XL', 5),
(33, 8, '28', 8),
(34, 8, '30', 6),
(35, 8, '32', 6),
(36, 8, '34', 7),
(37, 9, 'Único', 30),
(38, 10, 'Único', 8),
(44, 3, 'XL', 31),
(51, 1, 'XXL', 10),
(52, 11, 'L', 13),
(53, 11, 'S', 6),
(54, 12, 'L', 43),
(55, 12, 'S', 11);

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
(1, 'Bienvenida', 'Descuento para clientes con al menos 1 compra', 'porcentaje', '5.00', 1, 1, '2026-04-10 23:05:15'),
(2, 'Cliente Frecuente', 'Descuento especial por 3 o más compras', 'porcentaje', '10.00', 3, 1, '2026-04-10 23:05:15'),
(3, 'Cliente VIP', '15% para clientes con 5 o más compras', 'porcentaje', '15.00', 5, 1, '2026-04-10 23:05:15'),
(4, 'Descuento $20.000', 'Descuento fijo para clientes con 2 o más compras', 'monto_fijo', '20000.00', 2, 1, '2026-04-10 23:05:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `store_settings`
--

CREATE TABLE `store_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `store_settings`
--

INSERT INTO `store_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('google_maps_url', 'https://www.google.com/maps/place/7%C2%B050\'26.2%22N+72%C2%B028\'32.1%22W/@7.840597,-72.475572,17z/data=!3m1!4b1!4m4!3m3!8m2!3d7.840597!4d-72.475572!18m1!1e1?entry=ttu&g_ep=EgoyMDI2MDcwOC4wIKXMDSoASAFQAw%3D%3D', '2026-07-11 01:46:58'),
('latitude', '7.840611', '2026-07-11 01:46:36'),
('longitude', '-72.475583', '2026-07-11 01:46:36'),
('physical_address', 'Carrera 13 3N-80 Barrio Sangregorio', '2026-07-11 01:09:36'),
('store_name', 'Visión Real', '2026-07-11 01:09:36'),
('support_email', 'rufay0813@gmail.com', '2026-07-11 01:09:36'),
('whatsapp_number', '573125420576', '2026-07-11 01:44:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `telegram_alert_state`
--

CREATE TABLE `telegram_alert_state` (
  `id` int(11) NOT NULL,
  `alert_key` varchar(80) NOT NULL,
  `alert_hash` varchar(64) NOT NULL,
  `payload_hash` varchar(64) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `telegram_alert_state`
--

INSERT INTO `telegram_alert_state` (`id`, `alert_key`, `alert_hash`, `payload_hash`, `sent_at`, `updated_at`) VALUES
(1, 'low_stock', 'f4b0536a8faebc30b10cc9357bb2fa4f', NULL, '2026-07-01 22:39:41', '2026-07-01 22:39:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `telegram_subscribers`
--

CREATE TABLE `telegram_subscribers` (
  `id` int(11) NOT NULL,
  `chat_id` varchar(64) NOT NULL,
  `first_name` varchar(150) DEFAULT NULL,
  `last_name` varchar(150) DEFAULT NULL,
  `username` varchar(150) DEFAULT NULL,
  `chat_type` varchar(50) DEFAULT NULL,
  `language_code` varchar(20) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `last_seen_at` timestamp NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(12, 'Admin', 'admin@visionreal.com', '$2y$10$/rzel7vB1xGpfs8F5/7v7eTD2fU3zmCRtYkRJEbfsm9b2IqE9Pfzu', 'administrador', 0, '2026-05-04 12:37:54'),
(13, 'Vendedor', 'vendedor@visionreal.com', '$2y$10$92IXUNpkmORZe9bW4lKvSuXYPWxKTzPJC2hNtNMBgcOgX6uPFiI4K', 'vendedor', 1, '2026-05-04 12:37:54'),
(14, 'Kenyi contreras', 'brahyannarmesto17@gmail.com', '$2y$10$G5LK.inI5FrGMSsfvPjIMOvSRzT2f1Aykl70ji4oHSrR6jbIPcFRe', 'administrador', 1, '2026-05-06 12:20:06'),
(15, 'Rufay', 'rufay@gmail.com', '$2y$10$QG55CrSTGtE9GquQiq5zEey..smRcvMxuVLvmS2dq2kJpI8nfLq/2', 'administrador', 1, '2026-05-06 12:40:59'),
(16, 'Santiago Rangel', 'santirangelaparicio248@gmail.com', '$2y$10$u4u/SlT83MRpe4ASZyEq.OSp2ofZdmRxNy1BVIwY5Ju97Mj6oN0g2', 'administrador', 1, '2026-05-07 13:17:01'),
(17, 'alexandra medina', 'medinasharik82@gmail.com', '$2y$10$hKWbXlBies3UY9hn4Hn1lefJXX1CihYnfbv7EgddfuukHIGZWKqZW', 'administrador', 1, '2026-06-30 12:16:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `sexo` enum('M','F','O') DEFAULT 'O',
  `usuario_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `estado` enum('completada','pendiente','cancelada') DEFAULT 'completada',
  `tipo_venta` enum('fisica','online') NOT NULL DEFAULT 'fisica',
  `notas` text DEFAULT NULL,
  `promocion_id` int(11) DEFAULT NULL,
  `descuento` decimal(10,2) DEFAULT 0.00,
  `descuento_id` int(11) DEFAULT NULL,
  `descuento_aplicado` text DEFAULT NULL,
  `total_sin_descuento` decimal(10,2) DEFAULT 0.00,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `cliente_id`, `sexo`, `usuario_id`, `total`, `estado`, `tipo_venta`, `notas`, `promocion_id`, `descuento`, `descuento_id`, `descuento_aplicado`, `total_sin_descuento`, `fecha`) VALUES
(1, NULL, 'O', 15, '80000.00', 'completada', 'fisica', '', NULL, '0.00', NULL, NULL, '80000.00', '2026-05-06 13:43:08'),
(2, NULL, 'O', 15, '255000.00', 'completada', 'fisica', '', NULL, '0.00', NULL, NULL, '255000.00', '2026-05-06 14:19:20'),
(3, NULL, 'O', 15, '130000.00', 'completada', 'fisica', '', NULL, '0.00', NULL, NULL, '130000.00', '2026-05-06 14:22:38'),
(4, NULL, 'O', 15, '275000.00', 'completada', 'fisica', '', NULL, '0.00', NULL, NULL, '275000.00', '2026-05-07 14:22:47'),
(5, NULL, 'O', 15, '330000.00', 'completada', 'fisica', '', NULL, '0.00', NULL, NULL, '330000.00', '2026-05-07 14:25:16'),
(6, NULL, 'O', 14, '450000.00', 'completada', 'fisica', '', NULL, '0.00', NULL, NULL, '450000.00', '2026-05-08 16:03:33'),
(7, NULL, 'O', 15, '90000.00', 'completada', 'fisica', 'no pago', NULL, '0.00', NULL, NULL, '90000.00', '2026-05-14 13:24:53'),
(8, NULL, 'O', 15, '42300.00', 'completada', 'fisica', '', NULL, '4700.00', 6, 'descuento aprendiz sena: 10.00%', '47000.00', '2026-05-25 11:47:34'),
(9, NULL, 'O', 15, '306000.00', 'completada', 'online', 'Pedido online #ON-20260527-0001', NULL, '34000.00', 6, NULL, '340000.00', '2026-05-27 22:37:49'),
(10, NULL, 'O', 15, '126900.00', 'completada', 'online', 'Pedido online #ON-20260527-0009', NULL, '14100.00', 6, NULL, '141000.00', '2026-05-28 04:23:28'),
(11, NULL, 'O', 15, '283500.00', 'completada', 'online', 'Pedido online #ON-20260527-0010', NULL, '31500.00', 6, NULL, '315000.00', '2026-05-28 04:32:12'),
(12, 12, 'O', 15, '42300.00', 'completada', 'online', 'Pedido online #ON-20260527-0011', NULL, '4700.00', 6, NULL, '47000.00', '2026-05-28 04:45:02'),
(13, 13, 'O', 15, '1269000.00', 'completada', 'online', 'Pedido online #ON-20260528-0003', NULL, '141000.00', 6, NULL, '1410000.00', '2026-05-28 11:29:12'),
(14, 13, 'O', 15, '49500.00', 'completada', 'online', 'Pedido online #ON-20260528-0004', NULL, '5500.00', 6, NULL, '55000.00', '2026-05-28 11:30:37'),
(15, 13, 'O', 15, '42300.00', 'completada', 'online', 'Pedido online #ON-20260528-0005', NULL, '4700.00', 6, NULL, '47000.00', '2026-05-28 11:31:50');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_cliente` (`cliente_online_id`);

--
-- Indices de la tabla `carrito_items`
--
ALTER TABLE `carrito_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_item` (`carrito_id`,`producto_id`,`talla`),
  ADD KEY `producto_id` (`producto_id`);

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
-- Indices de la tabla `clientes_online`
--
ALTER TABLE `clientes_online`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `idx_email` (`email`);

--
-- Indices de la tabla `clientes_online_sesiones`
--
ALTER TABLE `clientes_online_sesiones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `idx_token` (`token`);

--
-- Indices de la tabla `configuracion_tienda`
--
ALTER TABLE `configuracion_tienda`
  ADD PRIMARY KEY (`clave`);

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
-- Indices de la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `venta_id` (`venta_id`),
  ADD UNIQUE KEY `numero_factura` (`numero_factura`),
  ADD KEY `numero_factura_2` (`numero_factura`),
  ADD KEY `cliente_documento` (`cliente_documento`);

--
-- Indices de la tabla `pagos_tienda`
--
ALTER TABLE `pagos_tienda`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pago` (`metodo`,`clave`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_pedido` (`numero_pedido`),
  ADD UNIQUE KEY `venta_id` (`venta_id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_numero` (`numero_pedido`),
  ADD KEY `idx_cliente` (`cliente_online_id`);

--
-- Indices de la tabla `pedido_detalle`
--
ALTER TABLE `pedido_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `pedido_historial`
--
ALTER TABLE `pedido_historial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
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
-- Indices de la tabla `store_settings`
--
ALTER TABLE `store_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indices de la tabla `telegram_alert_state`
--
ALTER TABLE `telegram_alert_state`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `alert_key` (`alert_key`);

--
-- Indices de la tabla `telegram_subscribers`
--
ALTER TABLE `telegram_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chat_id` (`chat_id`);

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
-- AUTO_INCREMENT de la tabla `carrito`
--
ALTER TABLE `carrito`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `carrito_items`
--
ALTER TABLE `carrito_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `clientes_online`
--
ALTER TABLE `clientes_online`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `clientes_online_sesiones`
--
ALTER TABLE `clientes_online_sesiones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `descuentos`
--
ALTER TABLE `descuentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `facturas`
--
ALTER TABLE `facturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `pagos_tienda`
--
ALTER TABLE `pagos_tienda`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `pedido_detalle`
--
ALTER TABLE `pedido_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `pedido_historial`
--
ALTER TABLE `pedido_historial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `producto_tallas`
--
ALTER TABLE `producto_tallas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT de la tabla `promociones`
--
ALTER TABLE `promociones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `telegram_alert_state`
--
ALTER TABLE `telegram_alert_state`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `telegram_subscribers`
--
ALTER TABLE `telegram_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD CONSTRAINT `carrito_ibfk_1` FOREIGN KEY (`cliente_online_id`) REFERENCES `clientes_online` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `carrito_items`
--
ALTER TABLE `carrito_items`
  ADD CONSTRAINT `carrito_items_ibfk_1` FOREIGN KEY (`carrito_id`) REFERENCES `carrito` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `carrito_items_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `clientes_online`
--
ALTER TABLE `clientes_online`
  ADD CONSTRAINT `clientes_online_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `clientes_online_sesiones`
--
ALTER TABLE `clientes_online_sesiones`
  ADD CONSTRAINT `clientes_online_sesiones_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes_online` (`id`) ON DELETE CASCADE;

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
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`cliente_online_id`) REFERENCES `clientes_online` (`id`),
  ADD CONSTRAINT `pedidos_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pedidos_ibfk_3` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `pedido_detalle`
--
ALTER TABLE `pedido_detalle`
  ADD CONSTRAINT `pedido_detalle_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedido_detalle_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `pedido_historial`
--
ALTER TABLE `pedido_historial`
  ADD CONSTRAINT `pedido_historial_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedido_historial_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

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
