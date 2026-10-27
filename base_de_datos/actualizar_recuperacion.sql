-- Para una instalación EXISTENTE: selecciona tu base en phpMyAdmin y ejecuta una vez.
-- No elimina usuarios ni cambia contraseñas.
CREATE TABLE IF NOT EXISTS password_reset_limits (
    bucket_hash CHAR(64) NOT NULL PRIMARY KEY,
    window_start DATETIME NOT NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    INDEX idx_reset_limits_window (window_start)
) ENGINE=InnoDB;

-- Los enlaces anteriores se mostraban en pantalla. Se revocan al migrar.
UPDATE password_resets SET usado = 1 WHERE usado = 0;
