<?php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/configuracion/config.php';
require __DIR__ . '/includes/helpers.php';

requerir_sesion_json();

$data = json_decode(file_get_contents('php://input'), true);
$contrasena = $data['contrasena'] ?? '';

if (!$contrasena) {
    echo json_encode(["success" => false, "message" => "Escribe tu contraseña para confirmar."]);
    exit;
}

$cols = get_table_columns($pdo, 'usuarios');
$idCol = in_array('id', $cols) ? 'id' : (in_array('id_usuario', $cols) ? 'id_usuario' : null);
$passCol = in_array('contrasena_hash', $cols) ? 'contrasena_hash' : (in_array('contrasena', $cols) ? 'contrasena' : null);

if (!$idCol || !$passCol) {
    echo json_encode(["success" => false, "message" => "Error en la configuración de la base de datos."]);
    exit;
}

$stmt = $pdo->prepare("SELECT $passCol FROM usuarios WHERE $idCol = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

if (!$usuario || !password_verify($contrasena, $usuario[$passCol])) {
    echo json_encode(["success" => false, "message" => "Contraseña incorrecta."]);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM usuarios WHERE $idCol = ?");
$stmt->execute([$_SESSION['usuario_id']]);

session_unset();
session_destroy();

echo json_encode(["success" => true, "message" => "Tu cuenta fue eliminada."]);
