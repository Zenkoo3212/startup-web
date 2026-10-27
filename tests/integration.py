#!/usr/bin/env python3
"""Flujo HTTP real contra MySQL y un receptor SMTP local; nunca envía a internet."""
import concurrent.futures
import email
import email.policy
import hashlib
import json
import os
from pathlib import Path
import re
import signal
import socket
import socketserver
import subprocess
import tempfile
import threading
import time
import urllib.error
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
ENV = dict(os.environ)
ENV.setdefault('DB_HOST', '127.0.0.1')
ENV.setdefault('DB_PORT', '3306')
ENV.setdefault('DB_USER', 'root')
ENV.setdefault('DB_PASS', '')
ENV.setdefault('DB_NAME', 'startup_reset_test')
PHP = ENV.get('PHP_BINARY', 'php')
assert re.fullmatch(r'[a-zA-Z0-9_]+_test', ENV['DB_NAME']), 'Usa una base exclusiva cuyo nombre termine en _test.'
SQL_PHP = r'''
$d = json_decode(stream_get_contents(STDIN), true);
$pdo = new PDO('mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';charset=utf8mb4', getenv('DB_USER'), getenv('DB_PASS'), [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pdo->exec("SET time_zone = '+00:00'");
if ($d['use']) $pdo->exec('USE `'.getenv('DB_NAME').'`');
if ($d['exec']) { $pdo->exec($d['sql']); echo '[]'; }
else { $s = $pdo->prepare($d['sql']); $s->execute($d['params']); echo json_encode($s->fetchAll(PDO::FETCH_ASSOC)); }
'''


def sql(statement, params=(), execute=False, use=True):
    r = subprocess.run([PHP, '-r', SQL_PHP], env=ENV, input=json.dumps({
        'sql': statement, 'params': list(params), 'exec': execute, 'use': use,
    }), text=True, capture_output=True, check=True)
    return json.loads(r.stdout)


class SMTP(socketserver.StreamRequestHandler):
    def handle(self):
        self.wfile.write(b'220 localhost test SMTP\r\n')
        while True:
            line = self.rfile.readline()
            if not line:
                return
            command = line.split(b' ', 1)[0].strip().upper()
            if command in (b'EHLO', b'HELO'):
                self.wfile.write(b'250-localhost\r\n250 8BITMIME\r\n')
            elif command == b'DATA':
                self.wfile.write(b'354 End with dot\r\n')
                body = []
                while True:
                    row = self.rfile.readline()
                    if row in (b'.\r\n', b''):
                        break
                    body.append(row[1:] if row.startswith(b'..') else row)
                if self.server.reject:
                    self.wfile.write(b'550 Message rejected for test\r\n')
                else:
                    self.server.messages.append(b''.join(body))
                    self.wfile.write(b'250 Message accepted\r\n')
            elif command == b'QUIT':
                self.wfile.write(b'221 Bye\r\n')
                return
            else:
                self.wfile.write(b'250 OK\r\n')


def free_port():
    with socket.socket() as s:
        s.bind(('127.0.0.1', 0))
        return s.getsockname()[1]


def main():
    checks = []
    def ok(label, condition):
        assert condition, label
        checks.append(label)
        print('OK:', label, flush=True)

    # Únicamente la base de prueba validada arriba se elimina y vuelve a crear.
    sql(f"DROP DATABASE IF EXISTS `{ENV['DB_NAME']}`; CREATE DATABASE `{ENV['DB_NAME']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", execute=True, use=False)
    schema = (ROOT / 'database.sql').read_text()
    schema = re.sub(r'CREATE DATABASE IF NOT EXISTS startup_db\s+CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;', '', schema)
    schema = schema.replace('USE startup_db;', '')
    sql(schema, execute=True)
    sql(schema, execute=True)  # La importación inicial es repetible.
    sql((ROOT / 'migrations/001_recuperacion_correo.sql').read_text(), execute=True)
    ok('Esquema nuevo y migración cargan en MySQL/MariaDB', True)

    smtp = socketserver.ThreadingTCPServer(('127.0.0.1', 0), SMTP)
    smtp.daemon_threads = True
    smtp.messages = []
    smtp.reject = False
    threading.Thread(target=smtp.serve_forever, daemon=True).start()
    port = free_port()
    base = f'http://127.0.0.1:{port}'
    ENV.update(APP_URL=base, SMTP_HOST='127.0.0.1', SMTP_PORT=str(smtp.server_address[1]),
               SMTP_ENCRYPTION='none', SMTP_AUTH='false', MAIL_FROM_ADDRESS='startup@example.test',
               PHP_CLI_SERVER_WORKERS='4')
    opener = urllib.request.build_opener(urllib.request.ProxyHandler({}))
    def request(path, data=None, method=None, headers=None, raw=None):
        body = json.dumps(data).encode() if data is not None else raw
        req = urllib.request.Request(base + '/' + path, data=body, method=method,
            headers={'Content-Type': 'application/json', **(headers or {})})
        try:
            response = opener.open(req, timeout=25)
        except urllib.error.HTTPError as error:
            response = error
        with response:
            content = response.read().decode()
            result = json.loads(content) if 'application/json' in response.headers.get('Content-Type', '') else content
            return response.status, result, response.headers

    def reset_token():
        message = email.message_from_bytes(smtp.messages[-1], policy=email.policy.default)
        text = message.get_body(preferencelist=('plain',)).get_content()
        match = re.search(r'(https?://[^\s]+/restablecer.php\?token=([a-f0-9]{64}))', text)
        assert match and match.group(1).startswith(base + '/'), 'URL absoluta configurada'
        return match.group(2)

    def change(token, password='NuevaClave123!'):
        return request('restablecer.php', {'token': token, 'contrasena': password, 'confirmar_contrasena': password})

    def recover(correo='jugador@example.test', **kwargs):
        return request('recuperar.php', {'correo': correo}, **kwargs)

    def clear_limits():
        sql('DELETE FROM password_reset_limits', execute=True)

    with tempfile.TemporaryFile(mode='w+') as log, tempfile.TemporaryDirectory(prefix='startup-sessions-') as sessions:
        server = subprocess.Popen([PHP, '-d', 'session.save_path=' + sessions, '-S', f'127.0.0.1:{port}', '-t', str(ROOT)],
            cwd=ROOT, env=ENV, stdout=log, stderr=log, start_new_session=True)
        try:
            for _ in range(80):
                try:
                    request('recuperar.php')
                    break
                except (OSError, urllib.error.URLError):
                    time.sleep(0.1)
            else:
                raise AssertionError('El servidor PHP no inició')

            status, registered, _ = request('register.php', {'nombre': 'Jugador de prueba', 'grado': '11',
                'correo': 'jugador@example.test', 'contrasena': 'Anterior123!'})
            if not isinstance(registered, dict):
                raise AssertionError('Respuesta del registro: ' + str(registered)[:1500])
            ok('Registro original sigue funcionando', status == 200 and registered['success'])
            for bad in ({'correo': []}, {'correo': 'incorrecto'}, {'correo': 33}):
                ok('Rechaza correo inválido sin enviar', request('recuperar.php', bad)[0] == 422)
            ok('Rechaza JSON roto y métodos no permitidos', request('recuperar.php', raw=b'{')[0] == 422
                and request('recuperar.php', method='DELETE')[0] == 405)

            status, known, headers = recover(headers={'Host': 'sitio-ajeno.example'})
            token = reset_token()
            ok('Correo SMTP aceptado y enlace absoluto sin confiar en Host', status == 200 and len(smtp.messages) == 1)
            ok('No devuelve el enlace ni permite caché', set(known) == {'success', 'message'} and headers['Cache-Control'] == 'no-store')
            rows = sql('SELECT token FROM password_resets WHERE usado = 0')
            ok('Guarda hash del token, nunca el token del correo', rows[0]['token'] == hashlib.sha256(token.encode()).hexdigest())
            count = len(smtp.messages)
            status, unknown, _ = recover('nadie@example.test')
            ok('Correo inexistente recibe igual respuesta y no genera mensaje', status == 200 and unknown == known and len(smtp.messages) == count)
            html_status, html, h = request('restablecer.php?token=' + token)
            ok('Formulario de cambio y protección del enlace', html_status == 200 and token in html
                and h['Referrer-Policy'] == 'no-referrer' and h['Cache-Control'] == 'no-store')
            ok('Rechaza confirmación diferente', request('restablecer.php', {'token': token,
                'contrasena': 'NuevaClave123!', 'confirmar_contrasena': 'Distinta123!'})[0] == 422)
            ok('Rechaza contraseña débil y demasiado larga', change(token, 'corta')[0] == 422 and change(token, 'A1!' * 30)[0] == 422)
            ok('Cambia la contraseña con el enlace recibido', change(token)[1]['success'])
            ok('Enlace de un solo uso', change(token)[0] == 410)
            new_login = request('login.php', {'correo': 'jugador@example.test', 'contrasena': 'NuevaClave123!'})[1]
            old_login = request('login.php', {'correo': 'jugador@example.test', 'contrasena': 'Anterior123!'})[1]
            ok('Acepta contraseña nueva y rechaza la anterior', new_login['success'] and not old_login['success'])

            recover(); previous = reset_token()
            recover(); newest = reset_token()
            ok('Un nuevo correo invalida enlaces previos', change(previous)[0] == 410)
            sql('UPDATE password_resets SET expira_en = UTC_TIMESTAMP() - INTERVAL 1 SECOND WHERE usado = 0', execute=True)
            ok('Rechaza enlaces vencidos y tokens inventados', change(newest)[0] == 410 and change('f' * 64)[0] == 410)
            ok('Limita la cantidad de correos por cuenta', recover()[0] == 429)
            clear_limits()
            recover(); valid_before_failure = reset_token()
            smtp.reject = True
            failed_status = recover()[0]
            smtp.reject = False
            ok('Fallo SMTP no anuncia éxito ni invalida el enlace anterior', failed_status == 503
                and change(valid_before_failure, 'TrasFallo123!')[1]['success'])

            clear_limits(); recover(); simultaneous = reset_token()
            with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
                results = list(pool.map(lambda _: change(simultaneous)[0], range(2)))
            ok('Dos cambios simultáneos: solo uno consume el enlace', sorted(results) == [200, 410])
            clear_limits()
            for i in range(20):
                assert recover(f'ausente{i}@example.test')[0] == 200
            ok('Límite persistente por IP', recover('otro@example.test')[0] == 429)
            ok('GET sin token no expone formulario usable', 'form id="resetForm" hidden' in request('restablecer.php')[1])
            log.flush(); log.seek(0); logs = log.read()
            # El acceso inicial contiene la URL, como en cualquier servidor web.
            # Los mensajes de error de la aplicación no deben incluir secretos.
            app_logs = '\n'.join(line for line in logs.splitlines() if '[Startup]' in line)
            ok('No escribe tokens en mensajes de error', token not in app_logs and newest not in app_logs)
        finally:
            os.killpg(server.pid, signal.SIGTERM)
            server.wait(timeout=10)
            smtp.shutdown(); smtp.server_close()
    print(f'\n{len(checks)} comprobaciones correctas. SMTP local: no se enviaron correos a internet.')


if __name__ == '__main__':
    main()
