<?php
session_start();
require 'helpers.php';
requerir_rol_pagina(['Admin']);

$archivo = __DIR__ . '/database.sql';

if (!file_exists($archivo)) {
    http_response_code(404);
    echo "El archivo database.sql no se encontró en el servidor.";
    exit;
}

header('Content-Description: File Transfer');
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="startup_db.sql"');
header('Content-Length: ' . filesize($archivo));
header('Cache-Control: no-cache, must-revalidate');
readfile($archivo);
exit;
