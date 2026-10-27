<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/mailer.php';

const MENSAJE_RECUPERACION = 'Si el correo está registrado, recibirás un enlace para cambiar tu contraseña. Revisa también la carpeta de spam.';

function columnas_recuperacion(PDO $pdo): array {
    $users = get_table_columns($pdo, 'usuarios');
    $resets = get_table_columns($pdo, 'password_resets');
    $pick = static function (array $actual, array $options): string {
        foreach ($options as $column) {
            if (in_array($column, $actual, true)) return $column;
        }
        throw new RuntimeException('El esquema de recuperación está incompleto.');
    };
    return [
        'id' => $pick($users, ['id', 'id_usuario']),
        'correo' => $pick($users, ['correo', 'email']),
        'password' => $pick($users, ['contrasena_hash', 'contrasena', 'contraseña']),
        'fk' => $pick($resets, ['usuario_id', 'id_usuario']),
    ];
}

// Persistente y compartido entre sesiones; no confía en X-Forwarded-For del cliente.
function permitir_solicitud_recuperacion(PDO $pdo, string $correo, string $ip): bool {
    $allowed = true;
    foreach ([['ip:' . $ip, 20], ['correo:' . strtolower($correo), 3]] as [$value, $limit]) {
        $key = hash('sha256', $value);
        $stmt = $pdo->prepare('INSERT INTO password_reset_limits (bucket_hash, window_start, attempts)
            VALUES (?, UTC_TIMESTAMP(), 1)
            ON DUPLICATE KEY UPDATE
            attempts = IF(window_start <= UTC_TIMESTAMP() - INTERVAL 1 HOUR, 1, attempts + 1),
            window_start = IF(window_start <= UTC_TIMESTAMP() - INTERVAL 1 HOUR, UTC_TIMESTAMP(), window_start)');
        $stmt->execute([$key]);
        $stmt = $pdo->prepare('SELECT attempts FROM password_reset_limits WHERE bucket_hash = ?');
        $stmt->execute([$key]);
        if ((int) $stmt->fetchColumn() > $limit) $allowed = false;
    }
    $pdo->exec('DELETE FROM password_reset_limits WHERE window_start < UTC_TIMESTAMP() - INTERVAL 2 DAY');
    return $allowed;
}

function solicitar_recuperacion(PDO $pdo, string $correo): void {
    $baseUrl = app_url();
    crear_correo_smtp(); // Detectar configuración incompleta también para correos inexistentes.
    $c = columnas_recuperacion($pdo);
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT `{$c['id']}` FROM usuarios WHERE `{$c['correo']}` = ? FOR UPDATE");
        $stmt->execute([$correo]);
        $id = $stmt->fetchColumn();
        if ($id === false) {
            $pdo->commit();
            return;
        }
        $stmt = $pdo->prepare("UPDATE password_resets SET usado = 1 WHERE `{$c['fk']}` = ? AND usado = 0");
        $stmt->execute([$id]);
        $stmt = $pdo->prepare("INSERT INTO password_resets (`{$c['fk']}`, token, expira_en)
            VALUES (?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 HOUR))");
        $stmt->execute([$id, $hash]);
        enviar_enlace_recuperacion($correo, $baseUrl . '/restablecer.php?token=' . $token);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function token_recuperacion_valido(PDO $pdo, string $token): bool {
    if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) return false;
    $stmt = $pdo->prepare('SELECT 1 FROM password_resets WHERE token = ? AND usado = 0 AND expira_en > UTC_TIMESTAMP()');
    $stmt->execute([hash('sha256', $token)]);
    return $stmt->fetchColumn() !== false;
}

function cambiar_password_recuperada(PDO $pdo, string $token, string $password): bool {
    if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) return false;
    $error = validar_password($password);
    if ($error !== null) throw new InvalidArgumentException($error);
    $c = columnas_recuperacion($pdo);
    $tokenHash = hash('sha256', $token);
    $stmt = $pdo->prepare("SELECT `{$c['fk']}` FROM password_resets WHERE token = ?");
    $stmt->execute([$tokenHash]);
    $id = $stmt->fetchColumn();
    if ($id === false) return false;
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    try {
        $pdo->beginTransaction();
        // Mismo orden de bloqueo que el envío: primero usuario, luego sus enlaces.
        $stmt = $pdo->prepare("SELECT `{$c['id']}` FROM usuarios WHERE `{$c['id']}` = ? FOR UPDATE");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() === false) {
            $pdo->rollBack();
            return false;
        }
        $stmt = $pdo->prepare("SELECT token FROM password_resets
            WHERE token = ? AND `{$c['fk']}` = ? AND usado = 0 AND expira_en > UTC_TIMESTAMP() FOR UPDATE");
        $stmt->execute([$tokenHash, $id]);
        if ($stmt->fetchColumn() === false) {
            $pdo->rollBack();
            return false;
        }
        $stmt = $pdo->prepare("UPDATE usuarios SET `{$c['password']}` = ? WHERE `{$c['id']}` = ?");
        $stmt->execute([$passwordHash, $id]);
        $stmt = $pdo->prepare("UPDATE password_resets SET usado = 1 WHERE `{$c['fk']}` = ? AND usado = 0");
        $stmt->execute([$id]);
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
