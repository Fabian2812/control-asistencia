-- Base de datos nueva e independiente. Compatible con MySQL 8 y MariaDB 10.4+.
CREATE DATABASE IF NOT EXISTS control_asistencia
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE control_asistencia;

-- Credenciales administrativas. password contiene el hash creado por PHP.
CREATE TABLE IF NOT EXISTS usuarios (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  usuario VARCHAR(60) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- Empleados; cédula y código de acceso son únicos y estado controla marcación.
CREATE TABLE IF NOT EXISTS empleados (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cedula VARCHAR(30) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  correo VARCHAR(190) NOT NULL,
  telefono VARCHAR(30) NOT NULL,
  codigo_acceso CHAR(6) NOT NULL UNIQUE,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_empleados_nombre (nombre)
) ENGINE=InnoDB;

-- Cada fila representa un turno: entrada obligatoria y salida inicialmente nula.
-- Los índices ayudan a buscar por día y por empleado sin duplicar información.
CREATE TABLE IF NOT EXISTS asistencias (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empleado_id BIGINT UNSIGNED NOT NULL,
  fecha DATE NOT NULL,
  hora_entrada DATETIME NOT NULL,
  hora_salida DATETIME NULL,
  CONSTRAINT fk_asistencias_empleado FOREIGN KEY (empleado_id)
    REFERENCES empleados(id) ON DELETE RESTRICT,
  INDEX idx_asistencias_fecha (fecha),
  INDEX idx_asistencias_empleado_fecha (empleado_id, fecha)
) ENGINE=InnoDB;
