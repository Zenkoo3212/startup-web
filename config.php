<?php

require_once __DIR__ . '/app/bootstrap.php';
$DB_HOST = app_config('DB_HOST', 'localhost');
$DB_NAME = app_config('DB_NAME', 'startup_db');
$DB_USER = app_config('DB_USER', 'root');
$DB_PASS = app_config('DB_PASS', '');
$DB_PORT = (int) app_config('DB_PORT', 3306);

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    $pdo->exec("SET time_zone = '+00:00'");
} catch (PDOException $e) {
    error_log('[Startup] No se pudo conectar a MySQL.');
    respuesta_json([
        "success" => false,
        "message" => "El servicio no está disponible temporalmente. Intenta más tarde."
    ], 503);
    exit;
}
