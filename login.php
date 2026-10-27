<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!doctype html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Iniciar sesión · Startup</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
        <style>
        :root{
            --bg-void:#080B14; --bg-panel:#101728; --bg-panel-alt:#161F35;
            --line:rgba(255,255,255,.08); --line-strong:rgba(255,255,255,.16);
            --gold:#F5B942; --gold-dim:#8a6a2c; --teal:#2EE6C5; --coral:#FF6B5E;
            --text:#EDF1FA; --muted:#8A93AC; --radius:14px;
        }
        *{box-sizing:border-box;}
        body{margin:0;min-height:100vh;background:var(--bg-void);color:var(--text);font-family:'Inter',sans-serif;line-height:1.55;-webkit-font-smoothing:antialiased;display:flex;flex-direction:column;align-items:center;padding:24px;}
        h1,h2,h3{font-family:'Space Grotesk',sans-serif;}
        a{color:inherit;}
        .brand{display:flex;align-items:center;gap:10px;font-family:'Space Grotesk';font-weight:700;font-size:1.15rem;text-decoration:none;margin:28px 0 8px;}
        .brand-badge{width:34px;height:34px;border-radius:9px;background:linear-gradient(145deg,var(--gold),#c98f22);display:flex;align-items:center;justify-content:center;color:#1a1200;font-weight:700;font-size:.85rem;box-shadow:0 0 0 1px rgba(0,0,0,.3),0 6px 16px -4px rgba(245,185,66,.5);}
        .box{width:100%;max-width:420px;margin:24px auto;padding:30px;border-radius:var(--radius);background:var(--bg-panel);border:1px solid var(--line);}
        .box h2{margin:0 0 6px;font-size:1.5rem;}
        .box p.sub{color:var(--muted);margin:0 0 22px;font-size:.92rem;}
        label{display:block;margin:14px 0 6px;color:var(--muted);font-size:.85rem;font-family:'JetBrains Mono';letter-spacing:.02em;}
        input{width:100%;padding:12px;border-radius:10px;border:1px solid var(--line-strong);background:var(--bg-void);color:var(--text);font-family:'Inter';font-size:.95rem;}
        input:focus{outline:none;border-color:var(--teal);}
        button{width:100%;margin-top:22px;padding:13px 14px;border-radius:10px;border:none;background:linear-gradient(180deg,#ffc95c,var(--gold));color:#1a1200;font-weight:700;font-size:.95rem;cursor:pointer;font-family:'Inter';}
        button:active{transform:scale(.98);}
        .links{margin-top:16px;font-size:.88rem;color:var(--muted);display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;}
        .links a{color:var(--teal);text-decoration:none;}
        .links a:hover{text-decoration:underline;}
        #msg{margin-top:14px;padding:10px 12px;border-radius:8px;font-size:.88rem;display:none;}
        #msg.ok{display:block;background:rgba(46,230,197,.1);border:1px solid rgba(46,230,197,.3);color:#bff6df;}
        #msg.err{display:block;background:rgba(255,107,94,.1);border:1px solid rgba(255,107,94,.3);color:#ffb4b4;}
        @media(max-width:480px){.box{padding:22px;margin:12px auto;}}
        </style>
    </head>
    <body>
        <a href="index.html" class="brand"><span class="brand-badge">SP</span> Startup</a>
        <div class="box">
            <h2>Iniciar sesión</h2>
            <p class="sub">Entra con tu correo y contraseña para seguir jugando.</p>
            <form id="loginForm">
                <label for="correo">Correo</label>
                <input id="correo" name="correo" type="email" required placeholder="tunombre@ejemplo.com">
                <label for="pass">Contraseña</label>
                <input id="pass" name="contrasena" type="password" required placeholder="••••••••">
                <button type="submit">Entrar al juego</button>
            </form>
            <div id="msg"></div>
            <div class="links">
                <a href="register.php">¿No tienes cuenta? Regístrate</a>
                <a href="recuperar.php">¿Olvidaste tu contraseña?</a>
            </div>
        </div>
        <script>
            const f = document.getElementById('loginForm');
            const msg = document.getElementById('msg');
            f.addEventListener('submit', async (e) => {
                e.preventDefault();
                const payload = { correo: f.correo.value, contrasena: f.contrasena.value };
                msg.className = '';
                msg.textContent = 'Conectando...';
                msg.className = 'ok';
                try {
                    const res = await fetch('login.php', {
                        method: 'POST',
                        headers: {'Content-Type':'application/json'},
                        body: JSON.stringify(payload)
                    });

                    if (!res.ok) {
                        let text;
                        try {
                            const err = await res.json();
                            text = err.message || JSON.stringify(err);
                        } catch {
                            text = res.status + ' ' + res.statusText;
                        }
                        msg.textContent = 'Error del servidor: ' + text;
                        msg.className = 'err';
                        return;
                    }

                    const data = await res.json();
                    msg.textContent = data.message || 'Respuesta del servidor';
                    msg.className = data.success ? 'ok' : 'err';
                    if (data.success) {
                        setTimeout(() => { window.location.href = data.redirect || 'dashboard.php'; }, 700);
                    }
                } catch (err) {
                    msg.textContent = 'No se pudo conectar con el servidor. Verifica que login.php esté publicado y que Apache esté activo.';
                    msg.className = 'err';
                    console.error(err);
                }
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

header('Content-Type: application/json');
require 'config.php';
require 'helpers.php';

$raw = file_get_contents('php://input');
$data = $raw ? json_decode($raw, true) : $_POST;

$correo     = trim($data['correo'] ?? '');
$contrasena = $data['contrasena'] ?? '';

if (!$correo || !$contrasena) {
    echo json_encode(["success" => false, "message" => "Completa correo y contraseña."]);
    exit;
}

// Detectar columnas reales en la tabla usuarios
$cols = get_table_columns($pdo, 'usuarios');
$idCol = in_array('id', $cols) ? 'id' : (in_array('id_usuario', $cols) ? 'id_usuario' : null);
$nameCol = in_array('nombre_completo', $cols) ? 'nombre_completo' : (in_array('nombre', $cols) ? 'nombre' : null);
$correoCol = in_array('correo', $cols) ? 'correo' : (in_array('email', $cols) ? 'email' : null);
$passCol = in_array('contrasena_hash', $cols) ? 'contrasena_hash' : (in_array('contrasena', $cols) ? 'contrasena' : (in_array('contraseña', $cols) ? 'contraseña' : null));
$rolCol = in_array('rol', $cols) ? 'rol' : null;

if (!$correoCol || !$passCol) {
    echo json_encode(["success" => false, "message" => "La tabla usuarios no tiene las columnas necesarias (correo/contraseña)."]);
    exit;
}

$select = [];
if ($idCol) $select[] = "$idCol AS id";
if ($nameCol) $select[] = "$nameCol AS nombre_completo";
$select[] = "$correoCol AS correo";
$select[] = "$passCol AS contrasena_hash";
if ($rolCol) $select[] = "$rolCol AS rol";

$sql = "SELECT " . implode(', ', $select) . " FROM usuarios WHERE $correoCol = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$correo]);
$usuario = $stmt->fetch();

if (!$usuario) {
    echo json_encode(["success" => false, "message" => "Correo o contraseña incorrectos."]);
    exit;
}

$stored = $usuario['contrasena_hash'];
$ok = false;
if (password_verify($contrasena, $stored)) {
    $ok = true;
} else {
    if (preg_match('/^[a-f0-9]{32}$/i', $stored) && md5($contrasena) === $stored) {
        $ok = true;
        try {
            $newHash = password_hash($contrasena, PASSWORD_DEFAULT);
            if ($idCol) {
                $upd = $pdo->prepare("UPDATE usuarios SET $passCol = ? WHERE $idCol = ?");
                $upd->execute([$newHash, $usuario['id']]);
            } else {
                $upd = $pdo->prepare("UPDATE usuarios SET $passCol = ? WHERE $correoCol = ?");
                $upd->execute([$newHash, $usuario['correo']]);
            }
        } catch (Exception $e) {
        }
    }
}

if (!$ok) {
    echo json_encode(["success" => false, "message" => "Correo o contraseña incorrectos."]);
    exit;
}

if ($idCol) {
    $updCol = $idCol;
    try {
        if (in_array('ultima_conexion', $cols)) {
            $uStmt = $pdo->prepare("UPDATE usuarios SET ultima_conexion = NOW() WHERE $updCol = ?");
            $uStmt->execute([$usuario['id']]);
        }
    } catch (Exception $e) {
        error_log('[login.php] ultima_conexion update failed: ' . $e->getMessage());
    }
}

session_regenerate_id(true);
$_SESSION['usuario_id'] = $usuario['id'] ?? null;
$_SESSION['nombre']     = $usuario['nombre_completo'] ?? ($usuario['nombre'] ?? 'Jugador');
$_SESSION['correo']     = $usuario['correo'];
if (isset($usuario['rol'])) $_SESSION['rol'] = $usuario['rol'];

echo json_encode([
    "success"  => true,
    "message"  => "¡Bienvenido de nuevo, " . ($_SESSION['nombre'] ?? 'Jugador') . "!",
    "redirect" => "dashboard.php"
]);
