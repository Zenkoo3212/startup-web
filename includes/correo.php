<?php
// Los secretos se leen del servidor o de config.local.php (excluido de Git).
function app_config(string $key, $default = null) {
    static $local = null;
    if ($local === null) {
        $file = dirname(__DIR__) . '/configuracion/config.local.php';
        // Compatibilidad con instalaciones que ya usaban la configuración en la raíz.
        if (!is_file($file)) $file = dirname(__DIR__) . '/config.local.php';
        $local = is_file($file) ? require $file : [];
        if (!is_array($local)) throw new RuntimeException('config.local.php debe devolver un array.');
    }
    $value = getenv($key);
    return $value !== false ? $value : ($local[$key] ?? $default);
}

function app_url(): string {
    $url = rtrim((string) app_config('APP_URL', ''), '/');
    $parts = parse_url($url);
    if (!$parts || empty($parts['host']) || !filter_var($url, FILTER_VALIDATE_URL)
        || isset($parts['user']) || isset($parts['pass'])
        || isset($parts['query']) || isset($parts['fragment'])) {
        throw new RuntimeException('Configura APP_URL con la dirección pública de Startup.');
    }
    $local = in_array($parts['host'], ['localhost', '127.0.0.1', '[::1]'], true);
    if (($parts['scheme'] ?? '') !== 'https' && !(($parts['scheme'] ?? '') === 'http' && $local)) {
        throw new RuntimeException('APP_URL debe usar HTTPS, excepto en localhost.');
    }
    return $url;
}

function respuesta_json(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function leer_peticion_json(): array {
    $raw = file_get_contents('php://input', false, null, 0, 8193);
    if ($raw === false || strlen($raw) > 8192) throw new InvalidArgumentException('La solicitud es demasiado grande.');
    $data = json_decode($raw, true);
    if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
        throw new InvalidArgumentException('La solicitud no contiene datos válidos.');
    }
    return $data;
}


function crear_correo_smtp(): \PHPMailer\PHPMailer\PHPMailer {
    require_once __DIR__ . '/PHPMailer/Exception.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/SMTP.php';
    $host = trim((string) app_config('SMTP_HOST', ''));
    $port = (int) app_config('SMTP_PORT', 587);
    $encryption = (string) app_config('SMTP_ENCRYPTION', 'tls');
    $from = (string) app_config('MAIL_FROM_ADDRESS', '');
    $auth = filter_var(app_config('SMTP_AUTH', true), FILTER_VALIDATE_BOOLEAN);
    $username = (string) app_config('SMTP_USERNAME', '');
    $password = (string) app_config('SMTP_PASSWORD', '');
    if (!$host || $port < 1 || $port > 65535 || !filter_var($from, FILTER_VALIDATE_EMAIL)
        || !in_array($encryption, ['tls', 'ssl', 'none'], true)
        || ($auth && ($username === '' || $password === ''))) {
        throw new RuntimeException('Completa la configuración SMTP en el servidor.');
    }
    if ($encryption === 'none' && !in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
        throw new RuntimeException('El correo público requiere una conexión SMTP cifrada.');
    }
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $host;
    $mail->Port = $port;
    $mail->SMTPAuth = $auth;
    $mail->Username = $username;
    $mail->Password = $password;
    $mail->SMTPSecure = $encryption === 'none' ? '' : $encryption;
    $mail->SMTPAutoTLS = $encryption !== 'none';
    $mail->Timeout = 15;
    $mail->getSMTPInstance()->Timelimit = 20;
    $mail->SMTPDebug = 0;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($from, (string) app_config('MAIL_FROM_NAME', 'Startup'));
    return $mail;
}

function enviar_enlace_recuperacion(string $correo, string $enlace): void {
    $mail = crear_correo_smtp();
    $mail->addAddress($correo);
    $mail->Subject = 'Restablece tu contraseña de Startup';
    $mail->isHTML(true);
    $url = htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8');
    $mail->Body = '<!doctype html><html lang="es"><meta charset="utf-8"><body style="font-family:Arial,sans-serif;color:#172033;line-height:1.6">'
        . '<h1>Recupera tu cuenta de Startup</h1>'
        . '<p>Recibimos una solicitud para cambiar tu contraseña.</p>'
        . '<p><a href="' . $url . '" style="display:inline-block;padding:12px 20px;background:#f5b942;color:#172033;border-radius:8px">Cambiar mi contraseña</a></p>'
        . '<p>El enlace vence en 1 hora y solo se puede usar una vez.</p>'
        . '<p>Si el botón no abre, copia esta dirección en tu navegador:<br>' . $url . '</p>'
        . '<p>Si no solicitaste este cambio, puedes ignorar el mensaje. Tu contraseña seguirá igual.</p>'
        . '</body></html>';
    $mail->AltBody = "Recibimos una solicitud para cambiar tu contraseña de Startup.\n\n"
        . "Abre este enlace: $enlace\n\n"
        . "Vence en 1 hora y solo se puede usar una vez.\n"
        . 'Si no lo solicitaste, ignora este mensaje. Tu contraseña seguirá igual.';
    if (!$mail->send()) throw new RuntimeException('El servidor SMTP no aceptó el correo.');
}
