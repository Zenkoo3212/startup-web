<?php
// Los secretos se leen del servidor o de config.local.php (excluido de Git).
function app_config(string $key, $default = null) {
    static $local = null;
    if ($local === null) {
        $file = dirname(__DIR__) . '/config.local.php';
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
