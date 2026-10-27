<?php
session_start();
header('Content-Type: application/json');
require 'config.php';
require 'helpers.php';

requerir_sesion_json();

$data = json_decode(file_get_contents('php://input'), true);

$opciones = [
    'color_piel'    => ['claro', 'medio', 'moreno', 'oscuro'],
    'color_cabello' => ['negro', 'castano', 'rubio', 'pelirrojo', 'azul', 'verde'],
    'gorra'         => ['ninguna', 'gorra_plana', 'gorra_curva', 'gorro_lana'],
    'chaqueta'      => ['basica', 'hoodie', 'chaqueta_cuero', 'blazer', 'chaleco'],
    'pantalon'      => ['basico', 'jean', 'jogger', 'cargo'],
    'calzado'       => ['basico', 'tenis', 'botas', 'sandalias'],
    'accesorio'     => ['ninguno', 'gafas', 'mochila', 'reloj', 'audifonos'],
];

$valores = [];
foreach ($opciones as $campo => $permitidos) {
    $val = $data[$campo] ?? null;
    if ($val === null || !in_array($val, $permitidos, true)) {
        echo json_encode(["success" => false, "message" => "Valor no válido para '$campo'."]);
        exit;
    }
    $valores[$campo] = $val;
}


$progCols = get_table_columns($pdo, 'progreso_jugador');
$usuarioFk = in_array('usuario_id', $progCols) ? 'usuario_id' : (in_array('id_usuario', $progCols) ? 'id_usuario' : null);
if (!$usuarioFk) {
    echo json_encode(["success" => false, "message" => "La tabla de progreso no tiene columna de referencia al usuario."]);
    exit;
}

$updates = [];
$params = [];
foreach (['color_piel','color_cabello','gorra','chaqueta','pantalon','calzado','accesorio'] as $campo) {
    if (in_array($campo, $progCols)) {
        $updates[] = "$campo = ?";
        $params[] = $valores[$campo];
    }
}

if (empty($updates)) {
    echo json_encode(["success" => false, "message" => "No hay campos del personaje para actualizar en la base de datos."]);
    exit;
}

$params[] = $_SESSION['usuario_id'];
$sql = "UPDATE progreso_jugador SET " . implode(', ', $updates) . " WHERE $usuarioFk = ?";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(["success" => true, "message" => "¡Tu personaje se guardó con éxito!"]);
} catch (Exception $e) {
    error_log('[actualizar_personaje.php] Update failed: ' . $e->getMessage());
    echo json_encode(["success" => false, "message" => "No se pudo guardar. Intenta de nuevo." ]);
}
