<?php
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!doctype html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Recuperar contraseña · Startup</title>
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
        input{width:100%;padding:12px;border-radius:10px;border:1px solid var(--line-strong);background:var(--bg-void);color:var(--text);font-family:'Inter';font-size:.95rem;}
        input:focus{outline:none;border-color:var(--teal);}
        button{width:100%;margin-top:22px;padding:13px 14px;border-radius:10px;border:none;background:linear-gradient(180deg,#ffc95c,var(--gold));color:#1a1200;font-weight:700;font-size:.95rem;cursor:pointer;font-family:'Inter';}
        .links{margin-top:16px;font-size:.88rem;color:var(--muted);text-align:center;}
        .links a{color:var(--teal);text-decoration:none;}
        .links a:hover{text-decoration:underline;}
        #msg{margin-top:14px;padding:10px 12px;border-radius:8px;font-size:.88rem;display:none;word-break:break-word;}
        #msg.ok{display:block;background:rgba(46,230,197,.1);border:1px solid rgba(46,230,197,.3);color:#bff6df;}
        #msg.err{display:block;background:rgba(255,107,94,.1);border:1px solid rgba(255,107,94,.3);color:#ffb4b4;}
        #msg a{color:var(--gold);font-weight:600;}
        @media(max-width:480px){.box{padding:22px;margin:12px auto;}}
        </style>
    </head>
    <body>
        <a href="index.html" class="brand"><span class="brand-badge">SP</span> Startup</a>
        <div class="box">
            <h2>Recuperar contraseña</h2>
            <p class="sub">Escribe el correo con el que creaste tu cuenta y te enviaremos un enlace para restablecerla.</p>
            <form id="recForm">
                <label for="correo">Correo</label>
                <input id="correo" name="correo" type="email" required maxlength="150" autocomplete="email" placeholder="tunombre@ejemplo.com">
                <button type="submit">Enviar enlace por correo</button>
            </form>
            <div id="msg" role="status" aria-live="polite"></div>
            <div class="links"><a href="login.php">Volver a iniciar sesión</a></div>
        </div>
        <script>
            const f = document.getElementById('recForm');
            const msg = document.getElementById('msg');
            f.addEventListener('submit', async (e) => {
                e.preventDefault();
                const button = f.querySelector('button');
                button.disabled = true;
                button.textContent = 'Enviando…';
                msg.className = '';
                try {
                    const res = await fetch('recuperar.php', {
                        method: 'POST',
                        headers: {'Content-Type':'application/json'},
                        body: JSON.stringify({ correo: f.correo.value })
                    });
                    const data = await res.json();
                    msg.className = data.success ? 'ok' : 'err';
                    msg.textContent = data.message || 'No se pudo enviar la solicitud.';
                } catch (err) {
                    msg.textContent = 'Error conectando al servidor';
                    msg.className = 'err';
                } finally {
                    button.disabled = false;
                    button.textContent = 'Enviar enlace por correo';
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
    $correo = $data['correo'] ?? null;
    if (!is_string($correo) || strlen($correo) > 150
        || !filter_var(trim($correo), FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Escribe un correo válido.');
    }
    $correo = trim($correo);
} catch (InvalidArgumentException $e) {
    respuesta_json(['success' => false, 'message' => $e->getMessage()], 422);
    exit;
}
require __DIR__ . '/configuracion/config.php';
try {
    if (!permitir_solicitud_recuperacion($pdo, $correo, $_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
        header('Retry-After: 3600');
        respuesta_json(['success' => false, 'message' => 'Has solicitado varios enlaces. Espera una hora antes de intentarlo de nuevo.'], 429);
        exit;
    }
    solicitar_recuperacion($pdo, $correo);
    respuesta_json(['success' => true, 'message' => MENSAJE_RECUPERACION]);
} catch (Throwable $e) {
    // No registrar destinatarios, enlaces, tokens ni credenciales SMTP.
    error_log('[Startup] Falló la recuperación por correo: ' . get_class($e));
    respuesta_json(['success' => false, 'message' => 'No pudimos procesar el envío. Intenta más tarde o contacta al administrador.'], 503);
}
