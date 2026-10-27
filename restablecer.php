<?php
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rawToken = $_GET['token'] ?? '';
    $validFormat = is_string($rawToken) && preg_match('/\A[a-f0-9]{64}\z/', $rawToken);
    $token = $validFormat ? $rawToken : ''; 
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!doctype html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Restablecer contraseña · Startup</title>
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
        .req-list{list-style:none;margin:8px 0 0;padding:0;display:flex;flex-direction:column;gap:4px;}
        .req-list li{font-size:.78rem;color:var(--muted);display:flex;align-items:center;gap:7px;}
        .req-list li::before{content:'○';color:var(--muted);font-size:.7rem;}
        .req-list li.met{color:var(--teal);}
        .req-list li.met::before{content:'●';color:var(--teal);}
        button{width:100%;margin-top:22px;padding:13px 14px;border-radius:10px;border:none;background:linear-gradient(180deg,#ffc95c,var(--gold));color:#1a1200;font-weight:700;font-size:.95rem;cursor:pointer;font-family:'Inter';}
        .links{margin-top:16px;font-size:.88rem;color:var(--muted);text-align:center;}
        .links a{color:var(--teal);text-decoration:none;}
        #msg{margin-top:14px;padding:10px 12px;border-radius:8px;font-size:.88rem;display:none;}
        #msg.ok{display:block;background:rgba(46,230,197,.1);border:1px solid rgba(46,230,197,.3);color:#bff6df;}
        #msg.err{display:block;background:rgba(255,107,94,.1);border:1px solid rgba(255,107,94,.3);color:#ffb4b4;}
        @media(max-width:480px){.box{padding:22px;margin:12px auto;}}
        </style>
    </head>
    <body>
        <a href="index.html" class="brand"><span class="brand-badge">SP</span> Startup</a>
        <div class="box">
            <h2>Restablecer contraseña</h2>
            <p class="sub">Escribe tu nueva contraseña. Este enlace vence en 1 hora y solo se puede usar una vez.</p>
            <?php if (!$validFormat): ?>
                <p style="color:var(--coral)">Este enlace no es válido. <a href="recuperar.php">Solicita uno nuevo</a>.</p>
            <?php endif; ?>
            <form id="resetForm" <?php if (!$validFormat) echo 'hidden'; ?>>
                <input type="hidden" id="token" value="<?php echo $token; ?>">
                <label for="pass">Nueva contraseña</label>
                <input id="pass" type="password" required minlength="8" maxlength="72" autocomplete="new-password" placeholder="Mínimo 8 caracteres">
                <ul class="req-list" id="reqList">
                    <li data-rule="len">Al menos 8 caracteres</li>
                    <li data-rule="letra">Al menos una letra</li>
                    <li data-rule="num">Al menos un número</li>
                    <li data-rule="esp">Al menos un carácter especial (! @ # $ % &amp; *)</li>
                </ul>
                <label for="pass2">Confirmar contraseña</label>
                <input id="pass2" type="password" required minlength="8" maxlength="72" autocomplete="new-password" placeholder="Repite la contraseña">
                <button type="submit">Guardar nueva contraseña</button>
            </form>
            <div id="msg" role="status" aria-live="polite"></div>
            <div class="links"><a href="login.php">Volver a iniciar sesión</a></div>
        </div>
        <script>
            const f = document.getElementById('resetForm');
            const msg = document.getElementById('msg');
            if (window.location.search) history.replaceState(null, '', window.location.pathname);
            const passInput = document.getElementById('pass');
            const reqItems = document.querySelectorAll('#reqList li');

            function evaluarPassword(pass) {
                const reglas = {
                    len:  pass.length >= 8,
                    letra: /[A-Za-z]/.test(pass),
                    num:  /[0-9]/.test(pass),
                    esp:  /[^A-Za-z0-9]/.test(pass),
                };
                reqItems.forEach(li => li.classList.toggle('met', reglas[li.dataset.rule]));
                return Object.values(reglas).every(Boolean);
            }
            passInput.addEventListener('input', () => evaluarPassword(passInput.value));

            f.addEventListener('submit', async (e) => {
                e.preventDefault();
                const pass = document.getElementById('pass').value;
                const pass2 = document.getElementById('pass2').value;

                if (!evaluarPassword(pass)) {
                    msg.textContent = 'Tu contraseña no cumple los requisitos mínimos.';
                    msg.className = 'err';
                    return;
                }
                if (pass !== pass2) {
                    msg.textContent = 'Las contraseñas no coinciden.';
                    msg.className = 'err';
                    return;
                }
                const button = f.querySelector('button');
                button.disabled = true;
                button.textContent = 'Guardando…';
                try {
                    const res = await fetch('restablecer.php', {
                        method: 'POST',
                        headers: {'Content-Type':'application/json'},
                        body: JSON.stringify({ token: document.getElementById('token').value, contrasena: pass, confirmar_contrasena: pass2 })
                    });
                    const data = await res.json();
                    msg.textContent = data.message || 'Respuesta del servidor';
                    msg.className = data.success ? 'ok' : 'err';
                    if (data.success) {
                        f.hidden = true;
                        setTimeout(() => { window.location.href = 'login.php'; }, 1800);
                    } else if (res.status === 410) {
                        f.hidden = true;
                        const link = document.createElement('a');
                        link.href = 'recuperar.php';
                        link.textContent = ' Solicitar otro enlace';
                        msg.appendChild(link);
                    }
                } catch (err) {
                    msg.textContent = 'Error conectando al servidor';
                    msg.className = 'err';
                } finally {
                    button.disabled = false;
                    button.textContent = 'Guardar nueva contraseña';
                }
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

require_once __DIR__ . '/includes/helpers.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    respuesta_json(['success' => false, 'message' => 'Método no permitido.'], 405);
    exit;
}
try {
    $data = leer_peticion_json();
    $token = $data['token'] ?? null;
    $password = $data['contrasena'] ?? null;
    $confirmation = $data['confirmar_contrasena'] ?? null;
    if (!is_string($token) || !is_string($password) || !is_string($confirmation)) {
        throw new InvalidArgumentException('Completa la nueva contraseña y su confirmación.');
    }
    if ($password !== $confirmation) throw new InvalidArgumentException('Las contraseñas no coinciden.');
    $error = validar_password($password);
    if ($error !== null) throw new InvalidArgumentException($error);
} catch (InvalidArgumentException $e) {
    respuesta_json(['success' => false, 'message' => $e->getMessage()], 422);
    exit;
}
require __DIR__ . '/configuracion/config.php';
try {
    if (!cambiar_password_recuperada($pdo, $token, $password)) {
        respuesta_json(['success' => false, 'message' => 'Este enlace venció, ya se utilizó o no es válido. Solicita uno nuevo.'], 410);
        exit;
    }
    respuesta_json(['success' => true, 'message' => 'Contraseña actualizada. Ya puedes iniciar sesión con la nueva contraseña.']);
} catch (Throwable $e) {
    error_log('[Startup] Falló el cambio de contraseña: ' . get_class($e));
    respuesta_json(['success' => false, 'message' => 'No se pudo actualizar la contraseña. Intenta más tarde.'], 503);
}
