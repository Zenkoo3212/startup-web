<?php
session_start();
require 'config.php';
require 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requerir_rol_pagina(['Admin']);
    ?>
    <!doctype html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Tiendas · Startup</title>
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
                    <h1>Tiendas</h1>
                    <p>Lugares donde los jugadores compran objetos con sus monedas.</p>
                </div>
            </div>

            <div id="msg"></div>

            <div class="panel">
                <h2 id="formTitle">Nueva tienda</h2>
                <p class="sub">Completa el nombre y una breve descripción.</p>
                <form id="form">
                    <input type="hidden" id="id_tienda" value="">
                    <label for="nombre_tienda">Nombre de la tienda</label>
                    <input id="nombre_tienda" required maxlength="120" placeholder="Ej: Tienda Central">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" maxlength="500" placeholder="¿Qué tipo de objetos vende esta tienda?"></textarea>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="submitBtn">Guardar tienda</button>
                        <button type="button" class="btn btn-ghost" id="cancelBtn" style="display:none;">Cancelar edición</button>
                    </div>
                </form>
            </div>

            <div class="panel">
                <h2>Listado</h2>
                <p class="sub">Todas las tiendas registradas.</p>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr></thead>
                        <tbody id="tbody">
                            <tr class="empty-row"><td colspan="4">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="modalConfirm">
            <div class="modal-box">
                <h3>¿Eliminar tienda?</h3>
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
        const idField = document.getElementById('id_tienda');
        const nombreField = document.getElementById('nombre_tienda');
        const descField = document.getElementById('descripcion');
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
            const res = await fetch('tiendas.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ accion, ...payload })
            });
            return res.json();
        }

        async function cargarLista() {
            tbody.innerHTML = '<tr class="empty-row"><td colspan="4">Cargando...</td></tr>';
            const data = await api('listar');
            if (!data.success) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="4">' + (data.message || 'Error al cargar') + '</td></tr>';
                return;
            }
            if (!data.items.length) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="4">Aún no hay tiendas. Crea la primera arriba.</td></tr>';
                return;
            }
            tbody.innerHTML = data.items.map(it => `
                <tr>
                    <td data-label="ID">${it.id_tienda}</td>
                    <td data-label="Nombre">${escapeHtml(it.nombre_tienda)}</td>
                    <td data-label="Descripción">${escapeHtml(it.descripcion)}</td>
                    <td data-label="Acciones">
                        <div class="row-actions">
                            <button class="btn btn-ghost btn-sm" onclick='editar(${JSON.stringify(it)})'>Editar</button>
                            <button class="btn btn-danger btn-sm" onclick="pedirEliminar(${it.id_tienda})">Eliminar</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        window.editar = function(it) {
            idField.value = it.id_tienda;
            nombreField.value = it.nombre_tienda;
            descField.value = it.descripcion ?? '';
            formTitle.textContent = 'Editar tienda';
            submitBtn.textContent = 'Actualizar tienda';
            cancelBtn.style.display = 'inline-flex';
            window.scrollTo({top: 0, behavior: 'smooth'});
        };

        cancelBtn.addEventListener('click', () => {
            idField.value = '';
            form.reset();
            formTitle.textContent = 'Nueva tienda';
            submitBtn.textContent = 'Guardar tienda';
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
            const data = await api('eliminar', { id_tienda: idAEliminar });
            mostrarMsg(data.message, data.success);
            modal.classList.remove('open');
            idAEliminar = null;
            if (data.success) cargarLista();
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                nombre_tienda: nombreField.value.trim(),
                descripcion: descField.value.trim()
            };
            const accion = idField.value ? 'actualizar' : 'crear';
            if (idField.value) payload.id_tienda = idField.value;
            const data = await api(accion, payload);
            mostrarMsg(data.message, data.success);
            if (data.success) {
                idField.value = '';
                form.reset();
                formTitle.textContent = 'Nueva tienda';
                submitBtn.textContent = 'Guardar tienda';
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
            $stmt = $pdo->query("SELECT id_tienda, nombre_tienda, descripcion FROM tienda ORDER BY nombre_tienda ASC");
            echo json_encode(["success" => true, "items" => $stmt->fetchAll()]);
            break;
        }
        case 'crear': {
            $nombre = limpiar_texto($data['nombre_tienda'] ?? '', 120);
            $descripcion = limpiar_texto($data['descripcion'] ?? '', 500);
            if ($nombre === '') {
                echo json_encode(["success" => false, "message" => "El nombre de la tienda es obligatorio."]);
                break;
            }
            $stmt = $pdo->prepare("INSERT INTO tienda (nombre_tienda, descripcion) VALUES (?, ?)");
            $stmt->execute([$nombre, $descripcion]);
            echo json_encode(["success" => true, "message" => "Tienda creada correctamente."]);
            break;
        }
        case 'actualizar': {
            $id = limpiar_id($data['id_tienda'] ?? null);
            $nombre = limpiar_texto($data['nombre_tienda'] ?? '', 120);
            $descripcion = limpiar_texto($data['descripcion'] ?? '', 500);
            if (!$id || $nombre === '') {
                echo json_encode(["success" => false, "message" => "Datos inválidos para actualizar."]);
                break;
            }
            $stmt = $pdo->prepare("UPDATE tienda SET nombre_tienda=?, descripcion=? WHERE id_tienda=?");
            $stmt->execute([$nombre, $descripcion, $id]);
            echo json_encode(["success" => true, "message" => "Tienda actualizada."]);
            break;
        }
        case 'eliminar': {
            $id = limpiar_id($data['id_tienda'] ?? null);
            if (!$id) {
                echo json_encode(["success" => false, "message" => "ID inválido."]);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM tienda WHERE id_tienda = ?");
            $stmt->execute([$id]);
            echo json_encode(["success" => true, "message" => "Tienda eliminada."]);
            break;
        }
        default:
            echo json_encode(["success" => false, "message" => "Acción no reconocida."]);
    }
} catch (Exception $e) {
    error_log('[tiendas.php] ' . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Ocurrió un error procesando la solicitud."]);
}
