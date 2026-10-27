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
        <title>Crear cuenta · Startup</title>
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
        .box{width:100%;max-width:440px;margin:24px auto;padding:30px;border-radius:var(--radius);background:var(--bg-panel);border:1px solid var(--line);}
        .box h2{margin:0 0 6px;font-size:1.5rem;}
        .box p.sub{color:var(--muted);margin:0 0 22px;font-size:.92rem;}
        label{display:block;margin:14px 0 6px;color:var(--muted);font-size:.85rem;font-family:'JetBrains Mono';letter-spacing:.02em;}
        input,select{width:100%;padding:12px;border-radius:10px;border:1px solid var(--line-strong);background:var(--bg-void);color:var(--text);font-family:'Inter';font-size:.95rem;}
        input:focus,select:focus{outline:none;border-color:var(--teal);}
        .hint{margin-top:6px;font-size:.78rem;color:var(--muted);}
        .req-list{list-style:none;margin:8px 0 0;padding:0;display:flex;flex-direction:column;gap:4px;}
        .req-list li{font-size:.78rem;color:var(--muted);display:flex;align-items:center;gap:7px;}
        .req-list li::before{content:'○';color:var(--muted);font-size:.7rem;}
        .req-list li.met{color:var(--teal);}
        .req-list li.met::before{content:'●';color:var(--teal);}
        button{width:100%;margin-top:22px;padding:13px 14px;border-radius:10px;border:none;background:linear-gradient(180deg,#ffc95c,var(--gold));color:#1a1200;font-weight:700;font-size:.95rem;cursor:pointer;font-family:'Inter';}
        button:active{transform:scale(.98);}
        .links{margin-top:16px;font-size:.88rem;color:var(--muted);text-align:center;}
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
            <h2>Crear cuenta</h2>
            <p class="sub">Regístrate con tu correo institucional para empezar a jugar.</p>
            <form id="registerForm">
                <label for="nombre">Nombre completo</label>
                <input id="nombre" name="nombre" type="text" required placeholder="Nombre y apellido">
                <label for="grado">Grado</label>
                <select id="grado" name="grado" required>
                    <option value="">Selecciona tu grado</option>
                    <option value="8">8°</option>
                    <option value="9">9°</option>
                    <option value="10">10°</option>
                    <option value="11">11°</option>
                    <option value="otro">Otro / no aplica</option>
                </select>
                <label for="correo">Correo</label>
                <input id="correo" name="correo" type="email" required placeholder="tunombre@ejemplo.com">
                <label for="pass">Contraseña</label>
                <input id="pass" name="contrasena" type="password" required minlength="8" placeholder="Mínimo 8 caracteres">
                <ul class="req-list" id="reqList">
                    <li data-rule="len">Al menos 8 caracteres</li>
                    <li data-rule="letra">Al menos una letra</li>
                    <li data-rule="num">Al menos un número</li>
                    <li data-rule="esp">Al menos un carácter especial (! @ # $ % &amp; *)</li>
                </ul>
                <label for="pass2">Confirmar contraseña</label>
                <input id="pass2" name="contrasena2" type="password" required minlength="8" placeholder="Repite tu contraseña">
                <p class="hint">Tu contraseña se guarda cifrada, nunca en texto plano.</p>
                <button type="submit">Crear mi cuenta</button>
            </form>
            <div id="msg"></div>
            <div class="links"><a href="login.php">¿Ya tienes cuenta? Inicia sesión</a></div>
        </div>
        <script>
            const f = document.getElementById('registerForm');
            const msg = document.getElementById('msg');
            const passInput = document.getElementById('pass');
            const reqItems = document.querySelectorAll('#reqList li');

            function evaluarPassword(pass) {
                const reglas = {
                    len:  pass.length >= 8,
                    letra: /[A-Za-z]/.test(pass),
                    num:  /[0-9]/.test(pass),
                    esp:  /[^A-Za-z0-9]/.test(pass),
                };
                reqItems.forEach(li => {
                    li.classList.toggle('met', reglas[li.dataset.rule]);
                });
                return Object.values(reglas).every(Boolean);
            }

            passInput.addEventListener('input', () => evaluarPassword(passInput.value));

            f.addEventListener('submit', async (e) => {
                e.preventDefault();

                if (!evaluarPassword(f.contrasena.value)) {
                    msg.textContent = 'Tu contraseña no cumple los requisitos mínimos.';
                    msg.className = 'err';
                    return;
                }
                if (f.contrasena.value !== f.contrasena2.value) {
                    msg.textContent = 'Las contraseñas no coinciden.';
                    msg.className = 'err';
                    return;
                }
                const payload = {
                    nombre: f.nombre.value,
                    grado: f.grado.value,
                    correo: f.correo.value,
                    contrasena: f.contrasena.value
                };
                try {
                    const res = await fetch('register.php', {
                        method: 'POST',
                        headers: {'Content-Type':'application/json'},
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    msg.textContent = data.message || 'Respuesta del servidor';
                    msg.className = data.success ? 'ok' : 'err';
                    if (data.success) {
                        f.reset();
                        setTimeout(() => { window.location.href = 'login.php'; }, 1000);
                    }
                } catch (err) {
                    msg.textContent = 'Error conectando al servidor';
                    msg.className = 'err';
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

$data = json_decode(file_get_contents('php://input'), true);

$nombre     = trim($data['nombre'] ?? '');
$grado      = trim($data['grado'] ?? '');
$correo     = trim($data['correo'] ?? '');
$contrasena = $data['contrasena'] ?? '';

if (!$nombre || !$grado || !$correo || !$contrasena) {
    echo json_encode(["success" => false, "message" => "Completa todos los campos."]);
    exit;
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "El correo no es válido."]);
    exit;
}

$errorPass = validar_password($contrasena);
if ($errorPass !== null) {
    echo json_encode(["success" => false, "message" => $errorPass]);
    exit;
}

// Detectar columnas reales
$cols = get_table_columns($pdo, 'usuarios');
$nameCol = in_array('nombre_completo', $cols) ? 'nombre_completo' : (in_array('nombre', $cols) ? 'nombre' : null);
$correoCol = in_array('correo', $cols) ? 'correo' : (in_array('email', $cols) ? 'email' : null);
$gradoCol = in_array('grado', $cols) ? 'grado' : null;
$passCol = in_array('contrasena_hash', $cols) ? 'contrasena_hash' : (in_array('contrasena', $cols) ? 'contrasena' : (in_array('contraseña', $cols) ? 'contraseña' : null));
$rolCol = in_array('rol', $cols) ? 'rol' : null;

if (!$nameCol || !$correoCol || !$passCol) {
    echo json_encode(["success" => false, "message" => "La tabla usuarios no tiene las columnas necesarias (nombre/correo/contraseña)."]);
    exit;
}

$stmt = $pdo->prepare("SELECT 1 FROM usuarios WHERE $correoCol = ?");
$stmt->execute([$correo]);
if ($stmt->fetch()) {
    echo json_encode(["success" => false, "message" => "Ya existe una cuenta con ese correo."]);
    exit;
}

$hash = password_hash($contrasena, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    // Build insert columns/values only for columns that actually exist
    $insertCols = [];
    $insertVals = [];
    $insertCols[] = $nameCol; $insertVals[] = $nombre;
    $insertCols[] = $correoCol; $insertVals[] = $correo;
    if ($gradoCol) { $insertCols[] = $gradoCol; $insertVals[] = $grado; }
    if ($rolCol) { $insertCols[] = $rolCol; $insertVals[] = 'Jugador'; }
    $insertCols[] = $passCol; $insertVals[] = $hash;

    $placeholders = implode(', ', array_fill(0, count($insertCols), '?'));
    $sql = "INSERT INTO usuarios (" . implode(', ', $insertCols) . ") VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($insertVals);

    $usuarioId = $pdo->lastInsertId();
    $pdo->commit();

    // Intentar crear fila de progreso sólo si la tabla/columnas existen.
    try {
        $progCols = get_table_columns($pdo, 'progreso_jugador');
        if (!empty($progCols)) {
            // buscar columna que referencie usuario
            $usuarioFk = in_array('usuario_id', $progCols) ? 'usuario_id' : (in_array('id_usuario', $progCols) ? 'id_usuario' : null);
            if ($usuarioFk) {
                $stmt = $pdo->prepare(
                    "INSERT INTO progreso_jugador ($usuarioFk, nivel, xp, negocios_creados, monedas) VALUES (?, 1, 0, 0, 0)"
                );
                $stmt->execute([$usuarioId]);
            }
        }
    } catch (Exception $e) {
        error_log('[register.php] progreso_jugador insert failed: ' . $e->getMessage());
    }

    echo json_encode(["success" => true, "message" => "Cuenta creada. ¡Ya puedes iniciar sesión!"]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("[register.php] Exception: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "No se pudo crear la cuenta. Intenta de nuevo.", "error" => $e->getMessage()]);
}
