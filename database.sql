-- ============================================================
-- Startup · Base de datos completa
-- Importar directamente en phpMyAdmin (pestaña "Importar")
-- Esquema para instalaciones nuevas: MySQL 8+ / MariaDB 10.6+.
-- Para actualizar una instalación existente, usa actualizar_recuperacion.sql.
-- ============================================================

CREATE DATABASE IF NOT EXISTS startup_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE startup_db;

-- ------------------------------------------------------------
-- USUARIOS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre_completo VARCHAR(150) NOT NULL,
  correo VARCHAR(150) NOT NULL UNIQUE,
  grado VARCHAR(10) NOT NULL,
  rol VARCHAR(30) NOT NULL DEFAULT 'Jugador',
  contrasena_hash VARCHAR(255) NOT NULL,
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ultima_conexion TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- PROGRESO_JUGADOR (nivel, xp, monedas y personaje)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS progreso_jugador (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  nivel INT NOT NULL DEFAULT 1,
  xp INT NOT NULL DEFAULT 0,
  negocios_creados INT NOT NULL DEFAULT 0,
  monedas INT NOT NULL DEFAULT 0,
  color_piel VARCHAR(20) NOT NULL DEFAULT 'claro',
  color_cabello VARCHAR(20) NOT NULL DEFAULT 'castano',
  gorra VARCHAR(30) NOT NULL DEFAULT 'ninguna',
  chaqueta VARCHAR(30) NOT NULL DEFAULT 'basica',
  pantalon VARCHAR(30) NOT NULL DEFAULT 'basico',
  calzado VARCHAR(30) NOT NULL DEFAULT 'basico',
  accesorio VARCHAR(30) NOT NULL DEFAULT 'ninguno',
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- PASSWORD_RESETS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expira_en DATETIME NOT NULL,
  usado TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Límites persistentes para evitar envíos masivos de recuperación.
CREATE TABLE IF NOT EXISTS password_reset_limits (
  bucket_hash CHAR(64) NOT NULL PRIMARY KEY,
  window_start DATETIME NOT NULL,
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  INDEX idx_reset_limits_window (window_start)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- CATEGORIA_OBJETO (CRUD)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categoria_objeto (
  id_categoria INT AUTO_INCREMENT PRIMARY KEY,
  nombre_categoria VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- OBJETO (CRUD) — items de inventario / tienda
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS objeto (
  id_objeto INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  tipo VARCHAR(60) NOT NULL,
  descripcion TEXT,
  credito_xp INT NOT NULL DEFAULT 0,
  id_categoria INT NULL,
  FOREIGN KEY (id_categoria) REFERENCES categoria_objeto(id_categoria) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- NPC (CRUD)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS npc (
  id_npc INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  personalidad VARCHAR(120),
  nivel_inicio INT NOT NULL DEFAULT 1,
  tipo VARCHAR(60)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- ACTIVIDAD (CRUD)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS actividad (
  id_actividad INT AUTO_INCREMENT PRIMARY KEY,
  tipo_actividad VARCHAR(80) NOT NULL,
  descripcion TEXT,
  dificultad VARCHAR(40) NOT NULL DEFAULT 'facil',
  recompensa_xp INT NOT NULL DEFAULT 0,
  recompensa_monedas INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- PARTIDA
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS partida (
  id_partida INT AUTO_INCREMENT PRIMARY KEY,
  fecha_inicio DATE NOT NULL,
  tiempo_jugado DECIMAL(10,2) NOT NULL DEFAULT 0,
  nivel_actual INT NOT NULL DEFAULT 1,
  id_usuario INT NOT NULL,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- RESULTADO_ACTIVIDAD
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS resultado_actividad (
  id_resultado INT AUTO_INCREMENT PRIMARY KEY,
  id_actividad INT NOT NULL,
  id_npc INT NULL,
  id_partida INT NOT NULL,
  fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
  estado VARCHAR(40) NOT NULL DEFAULT 'pendiente',
  progreso_actividad INT NOT NULL DEFAULT 0,
  xp_ganado INT NOT NULL DEFAULT 0,
  monedas_ganadas INT NOT NULL DEFAULT 0,
  FOREIGN KEY (id_actividad) REFERENCES actividad(id_actividad) ON DELETE CASCADE,
  FOREIGN KEY (id_npc) REFERENCES npc(id_npc) ON DELETE SET NULL,
  FOREIGN KEY (id_partida) REFERENCES partida(id_partida) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- INVENTARIO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS inventario (
  id_inventario INT AUTO_INCREMENT PRIMARY KEY,
  id_partida INT NOT NULL,
  id_objeto INT NOT NULL,
  cantidad INT NOT NULL DEFAULT 1,
  FOREIGN KEY (id_partida) REFERENCES partida(id_partida) ON DELETE CASCADE,
  FOREIGN KEY (id_objeto) REFERENCES objeto(id_objeto) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TIENDA (CRUD)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tienda (
  id_tienda INT AUTO_INCREMENT PRIMARY KEY,
  nombre_tienda VARCHAR(120) NOT NULL,
  descripcion TEXT
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TIENDA_OBJETO (tabla puente)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tienda_objeto (
  id_tienda INT NOT NULL,
  id_objeto INT NOT NULL,
  PRIMARY KEY (id_tienda, id_objeto),
  FOREIGN KEY (id_tienda) REFERENCES tienda(id_tienda) ON DELETE CASCADE,
  FOREIGN KEY (id_objeto) REFERENCES objeto(id_objeto) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- COMPRA
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS compra (
  id_compra INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  id_objeto INT NOT NULL,
  id_tienda INT NOT NULL,
  fecha_compra DATETIME DEFAULT CURRENT_TIMESTAMP,
  cantidad INT NOT NULL DEFAULT 1,
  precio_total INT NOT NULL DEFAULT 0,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (id_objeto) REFERENCES objeto(id_objeto) ON DELETE CASCADE,
  FOREIGN KEY (id_tienda) REFERENCES tienda(id_tienda) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- RANKING
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ranking (
  id_ranking INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  tipo_ranking VARCHAR(60) NOT NULL DEFAULT 'general',
  posicion INT NOT NULL DEFAULT 0,
  fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Datos de ejemplo (opcional) para probar los CRUD de una vez
-- Se insertan solo si la tabla está vacía, para poder reimportar
-- este archivo sin generar duplicados.
-- ------------------------------------------------------------
INSERT INTO categoria_objeto (nombre_categoria)
SELECT * FROM (SELECT 'Ropa' AS n UNION SELECT 'Accesorios' UNION SELECT 'Herramientas de negocio') AS tmp
WHERE NOT EXISTS (SELECT 1 FROM categoria_objeto);

INSERT INTO tienda (nombre_tienda, descripcion)
SELECT 'Tienda Central', 'La tienda principal del campus donde se compran objetos e indumentaria.'
WHERE NOT EXISTS (SELECT 1 FROM tienda);
