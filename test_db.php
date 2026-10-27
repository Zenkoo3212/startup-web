<?php
require 'config.php';
header('Content-Type: application/json; charset=utf-8');
try {
    $stmt = $pdo->query("SELECT 1 as ok");
    $r = $stmt->fetch();
    echo json_encode(['ok' => $r['ok'] ?? null, 'db' => $DB_NAME ?? 'desconocida']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
