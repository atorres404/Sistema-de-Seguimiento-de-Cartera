-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Sistema de Recuperaciones - Base de Datos
-- NOTA: Este es un proyecto de demostración con datos ficticios
--
-- Servidor: 127.0.0.1
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_recuperaciones_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contratos`
--

CREATE TABLE `contratos` (
  `id` int(11) NOT NULL,
  `razon_social` varchar(255) DEFAULT NULL,
  `numero_contrato` varchar(100) DEFAULT NULL,
  `importe_ministrado` decimal(15,2) DEFAULT NULL,
  `saldo` decimal(15,2) DEFAULT NULL,
  `intereses` decimal(15,2) DEFAULT NULL,
  `vencimiento` date DEFAULT NULL,
  `empresa` varchar(50) NOT NULL,
  `ultima_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `contratos`
-- NOTA: Estos son datos de ejemplo con fines demostrativos
--

INSERT INTO `contratos` (`id`, `razon_social`, `numero_contrato`, `importe_ministrado`, `saldo`, `intereses`, `vencimiento`, `empresa`) VALUES
(1, 'Comercializadora López y Asociados S.A.', 'DC-2024-001', 850000.00, 425000.00, 68000.00, '2025-03-15', 'DISPERSORA_CREDITO'),
(2, 'Grupo Industrial Martínez S.A. de C.V.', 'DC-2024-002', 1200000.00, 840000.00, 144000.00, '2025-04-20', 'DISPERSORA_CREDITO'),
(3, 'Servicios Empresariales González', 'DC-2024-003', 650000.00, 325000.00, 52000.00, '2025-02-28', 'DISPERSORA_CREDITO'),
(4, 'Distribuidora Hernández Hnos.', 'DC-2024-004', 920000.00, 552000.00, 88320.00, '2025-05-10', 'DISPERSORA_CREDITO'),
(5, 'Inversiones Rodríguez S.C.', 'DC-2024-005', 750000.00, 375000.00, 60000.00, '2025-03-30', 'DISPERSORA_CREDITO'),
(6, 'Tecnología y Soluciones García', 'DC-2024-006', 1100000.00, 770000.00, 132000.00, '2025-06-15', 'DISPERSORA_CREDITO'),
(7, 'Constructora Sánchez y Cía.', 'DC-2024-007', 1500000.00, 900000.00, 180000.00, '2025-07-01', 'DISPERSORA_CREDITO'),
(8, 'Transportes Ramírez S.A.', 'DC-2024-008', 680000.00, 340000.00, 54400.00, '2025-04-05', 'DISPERSORA_CREDITO'),
(9, 'Alimentos y Bebidas Torres', 'DC-2024-009', 890000.00, 445000.00, 71200.00, '2025-05-20', 'DISPERSORA_CREDITO'),
(10, 'Manufacturas Flores S.A. de C.V.', 'DC-2024-010', 1050000.00, 630000.00, 126000.00, '2025-06-30', 'DISPERSORA_CREDITO'),
(11, 'Comercial Vargas y Asociados', 'DC-2024-011', 720000.00, 360000.00, 57600.00, '2025-03-25', 'DISPERSORA_CREDITO'),
(12, 'Agroindustrias Morales S.C.', 'DC-2024-012', 980000.00, 588000.00, 94080.00, '2025-04-15', 'DISPERSORA_CREDITO'),
(13, 'Textiles Jiménez S.A.', 'DC-2024-013', 620000.00, 310000.00, 49600.00, '2025-02-20', 'DISPERSORA_CREDITO'),
(14, 'Equipos Industriales Ortiz', 'DC-2024-014', 1350000.00, 945000.00, 162000.00, '2025-08-10', 'DISPERSORA_CREDITO'),
(15, 'Servicios Logísticos Castillo', 'DC-2024-015', 780000.00, 390000.00, 62400.00, '2025-03-18', 'DISPERSORA_CREDITO'),
(16, 'Inmobiliaria Mendoza S.A.', 'DC-2024-016', 1650000.00, 1155000.00, 198000.00, '2025-09-05', 'DISPERSORA_CREDITO'),
(17, 'Electrónica Pérez y Cía.', 'DC-2024-017', 870000.00, 435000.00, 69600.00, '2025-04-28', 'DISPERSORA_CREDITO'),
(18, 'Farmacias Cruz S.A. de C.V.', 'DC-2024-018', 950000.00, 570000.00, 114000.00, '2025-05-15', 'DISPERSORA_CREDITO'),
(19, 'Automotriz Reyes Hnos.', 'DC-2024-019', 1280000.00, 896000.00, 153600.00, '2025-07-20', 'DISPERSORA_CREDITO'),
(20, 'Mueblería Silva S.C.', 'DC-2024-020', 690000.00, 345000.00, 55200.00, '2025-03-12', 'DISPERSORA_CREDITO'),
(21, 'Procesadora Aguilar S.A.', 'FS-2024-001', 1400000.00, 840000.00, 168000.00, '2025-04-10', 'FINANCIERA_SOFOM'),
(22, 'Grupo Comercial Fernández', 'FS-2024-002', 960000.00, 576000.00, 115200.00, '2025-03-22', 'FINANCIERA_SOFOM'),
(23, 'Industrias Rojas S.A. de C.V.', 'FS-2024-003', 1750000.00, 1225000.00, 210000.00, '2025-08-15', 'FINANCIERA_SOFOM'),
(24, 'Servicios Integrales Mejía', 'FS-2024-004', 820000.00, 410000.00, 65600.00, '2025-02-28', 'FINANCIERA_SOFOM'),
(25, 'Distribuidora Luna y Asociados', 'FS-2024-005', 1120000.00, 784000.00, 134400.00, '2025-06-05', 'FINANCIERA_SOFOM'),
(26, 'Construcciones Vega S.C.', 'FS-2024-006', 1580000.00, 1106000.00, 189600.00, '2025-07-18', 'FINANCIERA_SOFOM'),
(27, 'Alimentos Frescos Medina', 'FS-2024-007', 740000.00, 370000.00, 59200.00, '2025-03-08', 'FINANCIERA_SOFOM'),
(28, 'Tecnología Empresarial Campos', 'FS-2024-008', 1320000.00, 924000.00, 158400.00, '2025-05-30', 'FINANCIERA_SOFOM'),
(29, 'Papelería y Suministros Ruiz', 'FS-2024-009', 580000.00, 290000.00, 46400.00, '2025-02-15', 'FINANCIERA_SOFOM'),
(30, 'Transportes Especializados Gómez', 'FS-2024-010', 1180000.00, 826000.00, 141600.00, '2025-06-20', 'FINANCIERA_SOFOM'),
(31, 'Laboratorios Químicos Navarro', 'FS-2024-011', 1450000.00, 1015000.00, 174000.00, '2025-07-05', 'FINANCIERA_SOFOM'),
(32, 'Ferretería Industrial Paredes', 'FS-2024-012', 890000.00, 445000.00, 71200.00, '2025-04-18', 'FINANCIERA_SOFOM'),
(33, 'Maquinaria y Herramientas Delgado', 'FS-2024-013', 1620000.00, 1134000.00, 194400.00, '2025-08-25', 'FINANCIERA_SOFOM'),
(34, 'Gasolinera y Servicios Moreno', 'FS-2024-014', 980000.00, 588000.00, 117600.00, '2025-05-12', 'FINANCIERA_SOFOM'),
(35, 'Restaurantes Velázquez S.A.', 'FS-2024-015', 760000.00, 380000.00, 60800.00, '2025-03-28', 'FINANCIERA_SOFOM'),
(36, 'Joyería y Relojes Cortés', 'FS-2024-016', 620000.00, 310000.00, 49600.00, '2025-02-25', 'FINANCIERA_SOFOM'),
(37, 'Equipos Médicos Salazar', 'FS-2024-017', 1540000.00, 1078000.00, 184800.00, '2025-07-30', 'FINANCIERA_SOFOM'),
(38, 'Librería y Papelería Ríos', 'FS-2024-018', 540000.00, 270000.00, 43200.00, '2025-02-10', 'FINANCIERA_SOFOM'),
(39, 'Productos Químicos Sandoval', 'FS-2024-019', 1280000.00, 896000.00, 153600.00, '2025-06-15', 'FINANCIERA_SOFOM'),
(40, 'Gimnasios y Wellness Domínguez', 'FS-2024-020', 880000.00, 440000.00, 70400.00, '2025-04-22', 'FINANCIERA_SOFOM');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre_completo` varchar(50) NOT NULL,
  `correo` varchar(50) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena` varchar(150) NOT NULL,
  `rol` enum('admin','usuario') NOT NULL DEFAULT 'usuario'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `usuarios`
-- NOTA: Datos de ejemplo. Contraseñas hasheadas con SHA-512
-- Usuario admin: correo: admin@sistema.com, contraseña: Admin123
-- Usuario normal: correo: usuario@sistema.com, contraseña: User123
--

INSERT INTO `usuarios` (`id`, `nombre_completo`, `correo`, `usuario`, `contrasena`, `rol`) VALUES
(1, 'Administrador del Sistema', 'admin@sistema.com', 'admin', '6d09ebb590990a2cc56cac6a4a9f96acab25a762559eb791b1cdbd0cc7d35517b56e37657a1f364a1c7d4d7bc1c4281579c37ab1158a0eefa1e173a8d47ed5e8', 'admin'),
(2, 'Usuario de Consulta', 'usuario@sistema.com', 'usuario', 'bba0249f91ae2d0f4c13922d3bec09145eb11810afbb15f715f654c86c3566ef9fc8c448f48bc7090d8d113a4eac6a1fd569ddbe8961c395a92c9617eae507b3', 'usuario');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `contratos`
--
ALTER TABLE `contratos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_empresa` (`empresa`),
  ADD KEY `idx_vencimiento` (`vencimiento`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `contratos`
--
ALTER TABLE `contratos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;