<?php
session_start();
require __DIR__ . '/configuracion/config.php';
require __DIR__ . '/includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requerir_rol_pagina(['Admin']);
    ?>
    <!doctype html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Actividades · Startup</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="css/estilo.css">
    </head>
    <body>
        <div class="topbar">
            <a href="dashboard.php" class="brand"><span class="brand-badge">SP</span> Startup</a>
            <div class="topbar-user">
                <span>Hola, <b><?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Jugador'); ?></b></span>
                <a href="logout.php" class="btn btn-ghost btn-sm">Cerrar sesión</a>
            </div>
        </div>
        <div class="wrap">
            <a href="dashboard.php" class="back-link">← Volver al dashboard</a>
            <div class="page-head">
                <div>
                    <h1>Actividades</h1>
                    <p>Retos y tareas que los jugadores completan para ganar XP y monedas.</p>
                </div>
            </div>

            <div id="msg"></div>

            <div class="panel">
                <h2 id="formTitle">Nueva actividad</h2>
                <p class="sub">Define el tipo, la dificultad y las recompensas.</p>
                <form id="form">
                    <input type="hidden" id="id_actividad" value="">
                    <div class="form-grid">
                        <div>
                            <label for="tipo_actividad">Tipo de actividad</label>
                            <input id="tipo_actividad" required maxlength="80" placeholder="Ej: Vender producto">
                        </div>
                        <div>
                            <label for="dificultad">Dificultad</label>
                            <select id="dificultad" required>
                                <option value="facil">Fácil</option>
                                <option value="media">Media</option>
                                <option value="dificil">Difícil</option>
                            </select>
                        </div>
                        <div>
                            <label for="recompensa_xp">Recompensa XP</label>
                            <input id="recompensa_xp" type="number" min="0" value="0" required>
                        </div>
                        <div>
                            <label for="recompensa_monedas">Recompensa en monedas</label>
                            <input id="recompensa_monedas" type="number" min="0" value="0" required>
                        </div>
                    </div>
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" maxlength="500" placeholder="Explica en qué consiste la actividad"></textarea>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="submitBtn">Guardar actividad</button>
                        <button type="button" class="btn btn-ghost" id="cancelBtn" style="display:none;">Cancelar edición</button>
                    </div>
                </form>
            </div>

            <div class="panel">
                <h2>Listado</h2>
                <p class="sub">Todas las actividades registradas.</p>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Tipo</th><th>Dificultad</th><th>XP</th><th>Monedas</th><th>Acciones</th></tr></thead>
                        <tbody id="tbody">
                            <tr class="empty-row"><td colspan="6">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="modalConfirm">
            <div class="modal-box">
                <h3>¿Eliminar actividad?</h3>
                <p>Esta acción no se puede deshacer.</p>
                <div class="form-actions">
                    <button class="btn btn-danger" id="confirmDeleteBtn">Sí, eliminar</button>
                    <button class="btn btn-ghost" id="cancelDeleteBtn">Cancelar</button>
                </div>
            </div>
        </div>

        <script>
        const msg = document.getElementById('msg');
        const form = document.getElementById('form');
        const idField = document.getElementById('id_actividad');
        const tipoField = document.getElementById('tipo_actividad');
        const dificultadField = document.getElementById('dificultad');
        const xpField = document.getElementById('recompensa_xp');
        const monedasField = document.getElementById('recompensa_monedas');
        const descField = document.getElementById('descripcion');
        const submitBtn = document.getElementById('submitBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const formTitle = document.getElementById('formTitle');
        const tbody = document.getElementById('tbody');
        const modal = document.getElementById('modalConfirm');
        let idAEliminar = null;

        const etiquetasDificultad = { facil: 'Fácil', media: 'Media', dificil: 'Difícil' };

        function mostrarMsg(texto, ok) {
            msg.textContent = texto;
            msg.className = ok ? 'ok' : 'err';
            setTimeout(() => { msg.className = ''; }, 4000);
        }
        function escapeHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        async function api(accion, payload = {}) {
            const res = await fetch('actividades.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ accion, ...payload })
            });
            return res.json();
        }

        async function cargarLista() {
            tbody.innerHTML = '<tr class="empty-row"><td colspan="6">Cargando...</td></tr>';
            const data = await api('listar');
            if (!data.success) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="6">' + (data.message || 'Error al cargar') + '</td></tr>';
                return;
            }
            if (!data.items.length) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="6">Aún no hay actividades. Crea la primera arriba.</td></tr>';
                return;
            }
            tbody.innerHTML = data.items.map(it => `
                <tr>
                    <td data-label="ID">${it.id_actividad}</td>
                    <td data-label="Tipo">${escapeHtml(it.tipo_actividad)}</td>
                    <td data-label="Dificultad"><span class="badge">${etiquetasDificultad[it.dificultad] || escapeHtml(it.dificultad)}</span></td>
                    <td data-label="XP">${it.recompensa_xp}</td>
                    <td data-label="Monedas">${it.recompensa_monedas}</td>
                    <td data-label="Acciones">
                        <div class="row-actions">
                            <button class="btn btn-ghost btn-sm" onclick='editar(${JSON.stringify(it)})'>Editar</button>
                            <button class="btn btn-danger btn-sm" onclick="pedirEliminar(${it.id_actividad})">Eliminar</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        window.editar = function(it) {
            idField.value = it.id_actividad;
            tipoField.value = it.tipo_actividad;
            dificultadField.value = it.dificultad;
            xpField.value = it.recompensa_xp;
            monedasField.value = it.recompensa_monedas;
            descField.value = it.descripcion ?? '';
            formTitle.textContent = 'Editar actividad';
            submitBtn.textContent = 'Actualizar actividad';
            cancelBtn.style.display = 'inline-flex';
            window.scrollTo({top: 0, behavior: 'smooth'});
        };

        cancelBtn.addEventListener('click', () => {
            idField.value = '';
            form.reset();
            formTitle.textContent = 'Nueva actividad';
            submitBtn.textContent = 'Guardar actividad';
            cancelBtn.style.display = 'none';
        });

        window.pedirEliminar = function(id) {
            idAEliminar = id;
            modal.classList.add('open');
        };
        document.getElementById('cancelDeleteBtn').addEventListener('click', () => {
            idAEliminar = null;
            modal.classList.remove('open');
        });
        document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
            if (!idAEliminar) return;
            const data = await api('eliminar', { id_actividad: idAEliminar });
            mostrarMsg(data.message, data.success);
            modal.classList.remove('open');
            idAEliminar = null;
            if (data.success) cargarLista();
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                tipo_actividad: tipoField.value.trim(),
                dificultad: dificultadField.value,
                recompensa_xp: xpField.value,
                recompensa_monedas: monedasField.value,
                descripcion: descField.value.trim()
            };
            const accion = idField.value ? 'actualizar' : 'crear';
            if (idField.value) payload.id_actividad = idField.value;
            const data = await api(accion, payload);
            mostrarMsg(data.message, data.success);
            if (data.success) {
                idField.value = '';
                form.reset();
                formTitle.textContent = 'Nueva actividad';
                submitBtn.textContent = 'Guardar actividad';
                cancelBtn.style.display = 'none';
                cargarLista();
            }
        });

        cargarLista();
        </script>
    </body>
    </html>
    <?php
    exit;
}

// ---------------- API (POST) ----------------
header('Content-Type: application/json');
requerir_rol_json(['Admin']);

$data = json_decode(file_get_contents('php://input'), true);
$accion = $data['accion'] ?? '';
$dificultadesValidas = ['facil', 'media', 'dificil'];

try {
    switch ($accion) {
        case 'listar': {
            $stmt = $pdo->query("SELECT id_actividad, tipo_actividad, descripcion, dificultad, recompensa_xp, recompensa_monedas FROM actividad ORDER BY id_actividad DESC");
            echo json_encode(["success" => true, "items" => $stmt->fetchAll()]);
            break;
        }
        case 'crear': {
            $tipo = limpiar_texto($data['tipo_actividad'] ?? '', 80);
            $descripcion = limpiar_texto($data['descripcion'] ?? '', 500);
            $dificultad = in_array($data['dificultad'] ?? '', $dificultadesValidas, true) ? $data['dificultad'] : 'facil';
            $xp = isset($data['recompensa_xp']) && is_numeric($data['recompensa_xp']) ? (int)$data['recompensa_xp'] : 0;
            $monedas = isset($data['recompensa_monedas']) && is_numeric($data['recompensa_monedas']) ? (int)$data['recompensa_monedas'] : 0;

            if ($tipo === '') {
                echo json_encode(["success" => false, "message" => "El tipo de actividad es obligatorio."]);
                break;
            }
            $stmt = $pdo->prepare("INSERT INTO actividad (tipo_actividad, descripcion, dificultad, recompensa_xp, recompensa_monedas) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$tipo, $descripcion, $dificultad, $xp, $monedas]);
            echo json_encode(["success" => true, "message" => "Actividad creada correctamente."]);
            break;
        }
        case 'actualizar': {
            $id = limpiar_id($data['id_actividad'] ?? null);
            $tipo = limpiar_texto($data['tipo_actividad'] ?? '', 80);
            $descripcion = limpiar_texto($data['descripcion'] ?? '', 500);
            $dificultad = in_array($data['dificultad'] ?? '', $dificultadesValidas, true) ? $data['dificultad'] : 'facil';
            $xp = isset($data['recompensa_xp']) && is_numeric($data['recompensa_xp']) ? (int)$data['recompensa_xp'] : 0;
            $monedas = isset($data['recompensa_monedas']) && is_numeric($data['recompensa_monedas']) ? (int)$data['recompensa_monedas'] : 0;

            if (!$id || $tipo === '') {
                echo json_encode(["success" => false, "message" => "Datos inválidos para actualizar."]);
                break;
            }
            $stmt = $pdo->prepare("UPDATE actividad SET tipo_actividad=?, descripcion=?, dificultad=?, recompensa_xp=?, recompensa_monedas=? WHERE id_actividad=?");
            $stmt->execute([$tipo, $descripcion, $dificultad, $xp, $monedas, $id]);
            echo json_encode(["success" => true, "message" => "Actividad actualizada."]);
            break;
        }
        case 'eliminar': {
            $id = limpiar_id($data['id_actividad'] ?? null);
            if (!$id) {
                echo json_encode(["success" => false, "message" => "ID inválido."]);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM actividad WHERE id_actividad = ?");
            $stmt->execute([$id]);
            echo json_encode(["success" => true, "message" => "Actividad eliminada."]);
            break;
        }
        default:
            echo json_encode(["success" => false, "message" => "Acción no reconocida."]);
    }
} catch (Exception $e) {
    error_log('[actividades.php] ' . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Ocurrió un error procesando la solicitud."]);
}
