<?php

function requerir_sesion_pagina() {
    if (empty($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}

function requerir_sesion_json() {
    if (empty($_SESSION['usuario_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "message" => "Sesión no válida. Inicia sesión de nuevo."]);
        exit;
    }
}

/**
 * Exige sesión iniciada Y que el usuario tenga uno de los roles permitidos.
 * Si no cumple, redirige al dashboard con un mensaje (para páginas HTML).
 */
function requerir_rol_pagina(array $rolesPermitidos) {
    requerir_sesion_pagina();
    $rol = $_SESSION['rol'] ?? 'Jugador';
    if (!in_array($rol, $rolesPermitidos, true)) {
        header('Location: dashboard.php?error=sin_permiso');
        exit;
    }
}

/**
 * Igual que requerir_rol_pagina pero para endpoints que responden JSON.
 */
function requerir_rol_json(array $rolesPermitidos) {
    requerir_sesion_json();
    $rol = $_SESSION['rol'] ?? 'Jugador';
    if (!in_array($rol, $rolesPermitidos, true)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "message" => "No tienes permisos para realizar esta acción."]);
        exit;
    }
}

function get_table_columns(PDO $pdo, string $table): array {
    $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $stmt->execute([$table]);
    return array_map(function($r){ return $r['COLUMN_NAME']; }, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

/**
 * Valida la fortaleza de una contraseña.
 * Reglas: mínimo 8 caracteres, al menos 1 letra, 1 número y 1 carácter especial.
 * Devuelve null si es válida, o un mensaje de error en español si no lo es.
 */
function validar_password(string $contrasena): ?string {
    if (strlen($contrasena) > 72 || str_contains($contrasena, chr(0))) {
        return 'La contraseña no puede superar 72 bytes ni contener caracteres nulos.';
    }
    if (strlen($contrasena) < 8) {
        return "La contraseña debe tener al menos 8 caracteres.";
    }
    if (!preg_match('/[A-Za-z]/', $contrasena)) {
        return "La contraseña debe incluir al menos una letra.";
    }
    if (!preg_match('/[0-9]/', $contrasena)) {
        return "La contraseña debe incluir al menos un número.";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $contrasena)) {
        return "La contraseña debe incluir al menos un carácter especial (ej: ! @ # \$ % & *).";
    }
    return null;
}

/**
 * Sanitiza un entero desde input externo. Devuelve null si no es válido.
 */
function limpiar_id($valor): ?int {
    if ($valor === null || $valor === '') return null;
    if (!is_numeric($valor)) return null;
    $n = (int)$valor;
    return $n > 0 ? $n : null;
}

/**
 * Recorta y limpia un string simple (nombre, texto corto).
 */
function limpiar_texto($valor, int $max = 255): string {
    $t = trim((string)($valor ?? ''));
    if (function_exists('mb_substr')) $t = mb_substr($t, 0, $max);
    else $t = substr($t, 0, $max);
    return $t;
}
