<?php
session_start();
header('Content-Type: application/json');
require 'config.php';
require 'helpers.php';

requerir_sesion_json();


$correo = trim($_GET['correo'] ?? '');

if (!$correo || $correo !== ($_SESSION['correo'] ?? '')) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No autorizado para ver esta cuenta."]);
    exit;
}

$cols = get_table_columns($pdo, 'usuarios');
$idCol = in_array('id', $cols) ? 'id' : (in_array('id_usuario', $cols) ? 'id_usuario' : null);
$nameCol = in_array('nombre_completo', $cols) ? 'nombre_completo' : (in_array('nombre', $cols) ? 'nombre' : null);
$correoCol = in_array('correo', $cols) ? 'correo' : (in_array('email', $cols) ? 'email' : null);
$rolCol = in_array('rol', $cols) ? 'rol' : null;

$select = [];
if ($idCol) $select[] = "u.$idCol AS id";
if ($nameCol) $select[] = "u.$nameCol AS nombre_completo";
if ($correoCol) $select[] = "u.$correoCol AS correo";
if ($rolCol) $select[] = "u.$rolCol AS rol";
$select[] = "u.fecha_registro";
$select = array_merge($select, [
    'p.nivel', 'p.xp', 'p.negocios_creados', 'p.monedas',
    'p.color_piel', 'p.color_cabello', 'p.gorra', 'p.chaqueta', 'p.pantalon', 'p.calzado', 'p.accesorio'
]);

$joinId = $idCol;
if (!$joinId) {
    echo json_encode(["success" => false, "message" => "Error de esquema: la tabla usuarios no tiene columna ID esperada."]);
    exit;
}

// Si la tabla progreso_jugador no existe, devolver solo datos de usuario con valores por defecto
$progCols = get_table_columns($pdo, 'progreso_jugador');
if (empty($progCols)) {
    $stmt = $pdo->prepare("SELECT " . implode(', ', array_filter($select, function($s){ return strpos($s, 'p.') === false; })) . " FROM usuarios u WHERE u.$correoCol = ?");
    $stmt->execute([$correo]);
    $u = $stmt->fetch();
    if (!$u) {
        echo json_encode(["success" => false, "message" => "No se encontró información para este correo."]);
        exit;
    }
    // Añadir campos de progreso por defecto
    $u['nivel'] = 1;
    $u['xp'] = 0;
    $u['negocios_creados'] = 0;
    $u['monedas'] = 0;
    $u['color_piel'] = 'claro';
    $u['color_cabello'] = 'castano';
    $u['gorra'] = 'ninguna';
    $u['chaqueta'] = 'basica';
    $u['pantalon'] = 'basico';
    $u['calzado'] = 'basico';
    $u['accesorio'] = 'ninguno';

    echo json_encode(["success" => true, "usuario" => $u]);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT " . implode(', ', $select) . " FROM usuarios u JOIN progreso_jugador p ON p.usuario_id = u.$joinId WHERE u.$correoCol = ?"
);
$stmt->execute([$correo]);
$usuario = $stmt->fetch();

if (!$usuario) {
    echo json_encode(["success" => false, "message" => "No se encontró información para este correo."]);
    exit;
}

echo json_encode(["success" => true, "usuario" => $usuario]);
