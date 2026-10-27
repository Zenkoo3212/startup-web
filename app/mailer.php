<?php
require_once __DIR__ . '/bootstrap.php';

function crear_correo_smtp(): \PHPMailer\PHPMailer\PHPMailer {
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) throw new RuntimeException('Falta instalar las dependencias con Composer.');
    require_once $autoload;
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
