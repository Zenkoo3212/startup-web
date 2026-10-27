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
        <title>NPCs · Startup</title>
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
                    <h1>NPCs</h1>
                    <p>Personajes no jugables que asignan actividades y guían a los jugadores.</p>
                </div>
            </div>

            <div id="msg"></div>

            <div class="panel">
                <h2 id="formTitle">Nuevo NPC</h2>
                <p class="sub">Completa los datos del personaje.</p>
                <form id="form">
                    <input type="hidden" id="id_npc" value="">
                    <div class="form-grid">
                        <div>
                            <label for="nombre">Nombre</label>
                            <input id="nombre" required maxlength="120" placeholder="Ej: Profesor Álvarez">
                        </div>
                        <div>
                            <label for="tipo">Tipo</label>
                            <input id="tipo" maxlength="60" placeholder="Ej: mentor, cliente, socio">
                        </div>
                        <div>
                            <label for="nivel_inicio">Nivel de inicio</label>
                            <input id="nivel_inicio" type="number" min="1" value="1" required>
                        </div>
                        <div>
                            <label for="personalidad">Personalidad</label>
                            <input id="personalidad" maxlength="120" placeholder="Ej: exigente, amistoso...">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="submitBtn">Guardar NPC</button>
                        <button type="button" class="btn btn-ghost" id="cancelBtn" style="display:none;">Cancelar edición</button>
                    </div>
                </form>
            </div>

            <div class="panel">
                <h2>Listado</h2>
                <p class="sub">Todos los NPCs registrados.</p>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Nivel</th><th>Personalidad</th><th>Acciones</th></tr></thead>
                        <tbody id="tbody">
                            <tr class="empty-row"><td colspan="6">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="modalConfirm">
            <div class="modal-box">
                <h3>¿Eliminar NPC?</h3>
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
        const idField = document.getElementById('id_npc');
        const nombreField = document.getElementById('nombre');
        const tipoField = document.getElementById('tipo');
        const nivelField = document.getElementById('nivel_inicio');
        const persField = document.getElementById('personalidad');
        const submitBtn = document.getElementById('submitBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const formTitle = document.getElementById('formTitle');
        const tbody = document.getElementById('tbody');
        const modal = document.getElementById('modalConfirm');
        let idAEliminar = null;

        function mostrarMsg(texto, ok) {
            msg.textContent = texto;
            msg.className = ok ? 'ok' : 'err';
            setTimeout(() => { msg.className = ''; }, 4000);
        }
        function escapeHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        async function api(accion, payload = {}) {
            const res = await fetch('npcs.php', {
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
                tbody.innerHTML = '<tr class="empty-row"><td colspan="6">Aún no hay NPCs. Crea el primero arriba.</td></tr>';
                return;
            }
            tbody.innerHTML = data.items.map(it => `
                <tr>
                    <td data-label="ID">${it.id_npc}</td>
                    <td data-label="Nombre">${escapeHtml(it.nombre)}</td>
                    <td data-label="Tipo">${escapeHtml(it.tipo)}</td>
                    <td data-label="Nivel">${it.nivel_inicio}</td>
                    <td data-label="Personalidad">${escapeHtml(it.personalidad)}</td>
                    <td data-label="Acciones">
                        <div class="row-actions">
                            <button class="btn btn-ghost btn-sm" onclick='editar(${JSON.stringify(it)})'>Editar</button>
                            <button class="btn btn-danger btn-sm" onclick="pedirEliminar(${it.id_npc})">Eliminar</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        window.editar = function(it) {
            idField.value = it.id_npc;
            nombreField.value = it.nombre;
            tipoField.value = it.tipo ?? '';
            nivelField.value = it.nivel_inicio;
            persField.value = it.personalidad ?? '';
            formTitle.textContent = 'Editar NPC';
            submitBtn.textContent = 'Actualizar NPC';
            cancelBtn.style.display = 'inline-flex';
            window.scrollTo({top: 0, behavior: 'smooth'});
        };

        cancelBtn.addEventListener('click', () => {
            idField.value = '';
            form.reset();
            formTitle.textContent = 'Nuevo NPC';
            submitBtn.textContent = 'Guardar NPC';
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
            const data = await api('eliminar', { id_npc: idAEliminar });
            mostrarMsg(data.message, data.success);
            modal.classList.remove('open');
            idAEliminar = null;
            if (data.success) cargarLista();
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                nombre: nombreField.value.trim(),
                tipo: tipoField.value.trim(),
                nivel_inicio: nivelField.value,
                personalidad: persField.value.trim()
            };
            const accion = idField.value ? 'actualizar' : 'crear';
            if (idField.value) payload.id_npc = idField.value;
            const data = await api(accion, payload);
            mostrarMsg(data.message, data.success);
            if (data.success) {
                idField.value = '';
                form.reset();
                nivelField.value = 1;
                formTitle.textContent = 'Nuevo NPC';
                submitBtn.textContent = 'Guardar NPC';
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

try {
    switch ($accion) {
        case 'listar': {
            $stmt = $pdo->query("SELECT id_npc, nombre, personalidad, nivel_inicio, tipo FROM npc ORDER BY nombre ASC");
            echo json_encode(["success" => true, "items" => $stmt->fetchAll()]);
            break;
        }
        case 'crear': {
            $nombre = limpiar_texto($data['nombre'] ?? '', 120);
            $tipo = limpiar_texto($data['tipo'] ?? '', 60);
            $personalidad = limpiar_texto($data['personalidad'] ?? '', 120);
            $nivel = isset($data['nivel_inicio']) && is_numeric($data['nivel_inicio']) ? (int)$data['nivel_inicio'] : 1;

            if ($nombre === '') {
                echo json_encode(["success" => false, "message" => "El nombre es obligatorio."]);
                break;
            }
            $stmt = $pdo->prepare("INSERT INTO npc (nombre, personalidad, nivel_inicio, tipo) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nombre, $personalidad, $nivel, $tipo]);
            echo json_encode(["success" => true, "message" => "NPC creado correctamente."]);
            break;
        }
        case 'actualizar': {
            $id = limpiar_id($data['id_npc'] ?? null);
            $nombre = limpiar_texto($data['nombre'] ?? '', 120);
            $tipo = limpiar_texto($data['tipo'] ?? '', 60);
            $personalidad = limpiar_texto($data['personalidad'] ?? '', 120);
            $nivel = isset($data['nivel_inicio']) && is_numeric($data['nivel_inicio']) ? (int)$data['nivel_inicio'] : 1;

            if (!$id || $nombre === '') {
                echo json_encode(["success" => false, "message" => "Datos inválidos para actualizar."]);
                break;
            }
            $stmt = $pdo->prepare("UPDATE npc SET nombre=?, personalidad=?, nivel_inicio=?, tipo=? WHERE id_npc=?");
            $stmt->execute([$nombre, $personalidad, $nivel, $tipo, $id]);
            echo json_encode(["success" => true, "message" => "NPC actualizado."]);
            break;
        }
        case 'eliminar': {
            $id = limpiar_id($data['id_npc'] ?? null);
            if (!$id) {
                echo json_encode(["success" => false, "message" => "ID inválido."]);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM npc WHERE id_npc = ?");
            $stmt->execute([$id]);
            echo json_encode(["success" => true, "message" => "NPC eliminado."]);
            break;
        }
        default:
            echo json_encode(["success" => false, "message" => "Acción no reconocida."]);
    }
} catch (Exception $e) {
    error_log('[npcs.php] ' . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Ocurrió un error procesando la solicitud."]);
}
