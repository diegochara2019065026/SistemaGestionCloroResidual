-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 03-06-2026 a las 23:43:20
-- Versión del servidor: 8.4.7
-- Versión de PHP: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sgmcr`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alertas`
--

DROP TABLE IF EXISTS `alertas`;
CREATE TABLE IF NOT EXISTS `alertas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `solicitud_id` int DEFAULT NULL,
  `medicion_id` int DEFAULT NULL,
  `fecha_alerta` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `estado` enum('Pendiente','Atendida') COLLATE utf8mb4_unicode_ci DEFAULT 'Pendiente',
  PRIMARY KEY (`id`),
  KEY `solicitud_id` (`solicitud_id`),
  KEY `medicion_id` (`medicion_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `centros_poblados`
--

DROP TABLE IF EXISTS `centros_poblados`;
CREATE TABLE IF NOT EXISTS `centros_poblados` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `distrito` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provincia` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departamento` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `centros_poblados`
--

INSERT INTO `centros_poblados` (`id`, `nombre`, `distrito`, `provincia`, `departamento`) VALUES
(1, 'CONCHACHIRI', 'TARATA', 'TARATA', 'TACNA'),
(2, 'CHAPI II', 'PALCA', 'TACNA', 'TACNA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `centro_cuestionario`
--

DROP TABLE IF EXISTS `centro_cuestionario`;
CREATE TABLE IF NOT EXISTS `centro_cuestionario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `centro_poblado_id` int NOT NULL,
  `nombre_conocido` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `patron_ccpp` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ubigeo_dd` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ubigeo_pp` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ubigeo_ddi` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ubigeo_ccpp` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zona_utm` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coordenada_este` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coordenada_norte` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `altitud_msnm` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entrevistados_json` longtext COLLATE utf8mb4_unicode_ci,
  `condicion_centro` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `viviendas_total` int DEFAULT NULL,
  `viviendas_habitadas` int DEFAULT NULL,
  `poblacion_total` int DEFAULT NULL,
  `lengua_1` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lengua_1_otro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lengua_2` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lengua_2_otro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `servicio_energia` enum('SI','NO') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `servicio_internet` enum('SI','NO') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `servicio_celular` enum('SI','NO') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `servicio_telecable` enum('SI','NO') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `servicio_telefono` enum('SI','NO') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `municipalidad_en_cp` enum('SI','NO') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vias_acceso_json` longtext COLLATE utf8mb4_unicode_ci,
  `cuenta_sistema_agua` enum('SI','NO') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `abastecimiento_agua` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `abastecimiento_agua_otro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cuenta_ubs` enum('SI','NO') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `excretas_json` longtext COLLATE utf8mb4_unicode_ci,
  `familias_pagan` enum('SI','NO') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `obra_anio_tipo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `obra_anio` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `obra_costo_tipo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `obra_costo` decimal(12,2) DEFAULT NULL,
  `constructor` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `constructor_otro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `intervencion_tipo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `intervencion_anio` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `percepcion_json` longtext COLLATE utf8mb4_unicode_ci,
  `prestador_asistencia` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `centro_poblado_id` (`centro_poblado_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ficha_red_distribucion`
--

DROP TABLE IF EXISTS `ficha_red_distribucion`;
CREATE TABLE IF NOT EXISTS `ficha_red_distribucion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ficha_id` int NOT NULL,
  `numero` int NOT NULL,
  `ubicacion_punto` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `punto_toma` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_muestreo` date DEFAULT NULL,
  `hora_muestreo` time DEFAULT NULL,
  `cloro_residual_mgL` decimal(5,2) DEFAULT NULL,
  `usuario_nombre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_dni` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_firma` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ficha_id` (`ficha_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ficha_tecnica`
--

DROP TABLE IF EXISTS `ficha_tecnica`;
CREATE TABLE IF NOT EXISTS `ficha_tecnica` (
  `id` int NOT NULL AUTO_INCREMENT,
  `centro_poblado_id` int DEFAULT NULL,
  `localidad_anexo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `distrito` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provincia` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departamento` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `establecimiento_salud` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_registro` date DEFAULT NULL,
  `municipalidad` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jass` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_sistema_agua` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_bombeo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre_fuente_captacion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reservorio_1_fecha` date DEFAULT NULL,
  `reservorio_1_hora` time DEFAULT NULL,
  `reservorio_1_valor` decimal(5,2) DEFAULT NULL,
  `reservorio_2_fecha` date DEFAULT NULL,
  `reservorio_2_hora` time DEFAULT NULL,
  `reservorio_2_valor` decimal(5,2) DEFAULT NULL,
  `ubicacion_punto` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `punto_toma` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_muestreo` date DEFAULT NULL,
  `hora_muestreo` time DEFAULT NULL,
  `cloro_residual_mgL` decimal(5,2) DEFAULT NULL,
  `usuario_nombre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_dni` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_firma` tinyint(1) DEFAULT '0',
  `observacion_1` text COLLATE utf8mb4_unicode_ci,
  `observacion_2` text COLLATE utf8mb4_unicode_ci,
  `representante_oc` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `responsable_area_tecnica` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `representante_drvcs_grvcs` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pdf_archivo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pdf_nombre_original` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ficha_centro_poblado` (`centro_poblado_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jass`
--

DROP TABLE IF EXISTS `jass`;
CREATE TABLE IF NOT EXISTS `jass` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `centro_poblado_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `centro_poblado_id` (`centro_poblado_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `municipalidades`
--

DROP TABLE IF EXISTS `municipalidades`;
CREATE TABLE IF NOT EXISTS `municipalidades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `distrito` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provincia` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departamento` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `periodos_monitoreo`
--

DROP TABLE IF EXISTS `periodos_monitoreo`;
CREATE TABLE IF NOT EXISTS `periodos_monitoreo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_periodo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` enum('Activo','Inactivo') COLLATE utf8mb4_unicode_ci DEFAULT 'Activo',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `programacion_asistencia`
--

DROP TABLE IF EXISTS `programacion_asistencia`;
CREATE TABLE IF NOT EXISTS `programacion_asistencia` (
  `id` int NOT NULL AUTO_INCREMENT,
  `solicitud_id` int NOT NULL,
  `fecha_programada` date NOT NULL,
  `hora_programada` time NOT NULL,
  `tecnico_asignado` int NOT NULL,
  `zona_localizacion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('Programada','Confirmada','Reprogramada','Finalizada') COLLATE utf8mb4_unicode_ci DEFAULT 'Programada',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `fecha_confirmacion` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `solicitud_id` (`solicitud_id`),
  KEY `tecnico_asignado` (`tecnico_asignado`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `programacion_asistencia`
--

INSERT INTO `programacion_asistencia` (`id`, `solicitud_id`, `fecha_programada`, `hora_programada`, `tecnico_asignado`, `zona_localizacion`, `estado`, `observaciones`, `fecha_confirmacion`) VALUES
(1, 1, '2026-06-20', '10:00:00', 3, 'CHAPI II', 'Programada', 'SE PIDE QUE ESTE PRESENTE UN MIEMBRO DE LAS JASS', '2026-06-01 21:14:06'),
(2, 2, '2026-06-11', '11:00:00', 3, 'CHAPI II', 'Programada', 'hunhunuhnh', '2026-06-01 21:16:20'),
(3, 3, '2026-06-05', '11:00:00', 3, 'D', 'Programada', '', '2026-06-03 23:39:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `puntos_muestreo`
--

DROP TABLE IF EXISTS `puntos_muestreo`;
CREATE TABLE IF NOT EXISTS `puntos_muestreo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sistema_agua_id` int NOT NULL,
  `tipo_punto` enum('Reservorio','Primera Vivienda','Vivienda Intermedia','Última Vivienda') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sistema_agua_id` (`sistema_agua_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes`
--

DROP TABLE IF EXISTS `reportes`;
CREATE TABLE IF NOT EXISTS `reportes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo` enum('Cloro','Asistencia','Solicitud','Alerta') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_generacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `generado_por` int NOT NULL,
  `contenido` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `generado_por` (`generado_por`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre_rol` (`nombre_rol`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre_rol`, `descripcion`) VALUES
(1, 'Administrador', 'Encargado de Administrar toda la funcionalidad del sistema '),
(2, 'Municipalidad', 'Usuario municipal que solicita asistencia tecnica'),
(3, 'Gobierno Regional', 'Usuario del gobierno regional que agenda y atiende asistencias tecnicas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sistemas_agua`
--

DROP TABLE IF EXISTS `sistemas_agua`;
CREATE TABLE IF NOT EXISTS `sistemas_agua` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_sistema` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `centro_poblado_id` int NOT NULL,
  `municipalidad_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `centro_poblado_id` (`centro_poblado_id`),
  KEY `municipalidad_id` (`municipalidad_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_asistencia`
--

DROP TABLE IF EXISTS `solicitudes_asistencia`;
CREATE TABLE IF NOT EXISTS `solicitudes_asistencia` (
  `id` int NOT NULL AUTO_INCREMENT,
  `centro_poblado_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `motivo` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `fecha_solicitud` date NOT NULL,
  `estado` enum('Pendiente','En Proceso','Atendida') COLLATE utf8mb4_unicode_ci DEFAULT 'Pendiente',
  `tecnico_asignado` int DEFAULT NULL,
  `fecha_atencion` date DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `centro_poblado_id` (`centro_poblado_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `tecnico_asignado` (`tecnico_asignado`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `solicitudes_asistencia`
--

INSERT INTO `solicitudes_asistencia` (`id`, `centro_poblado_id`, `usuario_id`, `motivo`, `descripcion`, `fecha_solicitud`, `estado`, `tecnico_asignado`, `fecha_atencion`, `observaciones`) VALUES
(1, 2, 5, 'SE SOLICITA ASITENCIA TECNICA PORQUE ESTA MALA  LA CLORACION', 'CON URGENCIA', '2026-06-01', 'En Proceso', 3, '2026-06-20', 'SE PIDE QUE ESTE PRESENTE UN MIEMBRO DE LAS JASS'),
(2, 1, 3, 'hbhububhubhubhu', 'uh8huhuhuh', '2026-06-01', 'En Proceso', 3, '2026-06-11', 'hunhunuhnh'),
(3, 1, 3, 'VISITA TECNICA', 'DIKHDASKDADAKJDSALS', '2026-06-03', 'En Proceso', 3, '2026-06-05', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contrasena` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol_id` int NOT NULL,
  `estado` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`),
  KEY `rol_id` (`rol_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `correo`, `contrasena`, `rol_id`, `estado`, `fecha_creacion`) VALUES
(1, 'Diego', 'edgarsenati@gmail.com', 'diego123', 1, 1, '2026-05-25 00:22:03'),
(2, 'erika', 'erika@gmail.com', 'erika123', 1, 1, '2026-05-25 00:38:21'),
(3, 'Javier', 'Javier@gmail.com', '$2y$10$OYMg34T9TZ987LhdggV6j.MnPf/sSA6SbUIzt0AivR9LfKRIsUU42', 1, 1, '2026-05-25 01:14:21'),
(4, 'alfredo', 'alfredo@gmail.com', '$2y$10$5sLd4qsSLz6I2QDAMfMDwOirqkjlg0KrA9oRmksWLq2xkFJGEHwc.', 1, 1, '2026-05-25 01:25:59'),
(5, 'luis', 'luis@gmail.com', '$2y$10$X/wa/4CyuUbYkAtNFiP96Ow0DWxI3mXYcnPUd.uCQjbgMi0TU.nLW', 2, 1, '2026-06-01 17:03:06');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
