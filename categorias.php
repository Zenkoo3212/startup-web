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
        <title>Categorías de objetos · Startup</title>
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
                    <h1>Categorías de objetos</h1>
                    <p>Agrupa los objetos del juego (ropa, accesorios, herramientas, etc.).</p>
                </div>
            </div>

            <div id="msg"></div>

            <div class="panel">
                <h2 id="formTitle">Nueva categoría</h2>
                <p class="sub">Escribe un nombre claro; se usará en el catálogo de objetos.</p>
                <form id="form">
                    <input type="hidden" id="id_categoria" value="">
                    <label for="nombre_categoria">Nombre de la categoría</label>
                    <input id="nombre_categoria" required maxlength="100" placeholder="Ej: Ropa, Accesorios...">
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="submitBtn">Guardar categoría</button>
                        <button type="button" class="btn btn-ghost" id="cancelBtn" style="display:none;">Cancelar edición</button>
                    </div>
                </form>
            </div>

            <div class="panel">
                <h2>Listado</h2>
                <p class="sub">Todas las categorías registradas.</p>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr></thead>
                        <tbody id="tbody">
                            <tr class="empty-row"><td colspan="3">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="modalConfirm">
            <div class="modal-box">
                <h3>¿Eliminar categoría?</h3>
                <p>Esta acción no se puede deshacer. Los objetos que la usan quedarán sin categoría.</p>
                <div class="form-actions">
                    <button class="btn btn-danger" id="confirmDeleteBtn">Sí, eliminar</button>
                    <button class="btn btn-ghost" id="cancelDeleteBtn">Cancelar</button>
                </div>
            </div>
        </div>

        <script>
        const msg = document.getElementById('msg');
        const form = document.getElementById('form');
        const idField = document.getElementById('id_categoria');
        const nombreField = document.getElementById('nombre_categoria');
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

        async function api(accion, payload = {}) {
            const res = await fetch('categorias.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ accion, ...payload })
            });
            return res.json();
        }

        async function cargarLista() {
            tbody.innerHTML = '<tr class="empty-row"><td colspan="3">Cargando...</td></tr>';
            const data = await api('listar');
            if (!data.success) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="3">' + (data.message || 'Error al cargar') + '</td></tr>';
                return;
            }
            if (!data.items.length) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="3">Aún no hay categorías. Crea la primera arriba.</td></tr>';
                return;
            }
            tbody.innerHTML = data.items.map(it => `
                <tr>
                    <td data-label="ID">${it.id_categoria}</td>
                    <td data-label="Nombre">${escapeHtml(it.nombre_categoria)}</td>
                    <td data-label="Acciones">
                        <div class="row-actions">
                            <button class="btn btn-ghost btn-sm" onclick="editar(${it.id_categoria}, ${JSON.stringify(it.nombre_categoria)})">Editar</button>
                            <button class="btn btn-danger btn-sm" onclick="pedirEliminar(${it.id_categoria})">Eliminar</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        window.editar = function(id, nombre) {
            idField.value = id;
            nombreField.value = nombre;
            formTitle.textContent = 'Editar categoría';
            submitBtn.textContent = 'Actualizar categoría';
            cancelBtn.style.display = 'inline-flex';
            window.scrollTo({top: 0, behavior: 'smooth'});
        };

        cancelBtn.addEventListener('click', () => {
            idField.value = '';
            form.reset();
            formTitle.textContent = 'Nueva categoría';
            submitBtn.textContent = 'Guardar categoría';
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
            const data = await api('eliminar', { id_categoria: idAEliminar });
            mostrarMsg(data.message, data.success);
            modal.classList.remove('open');
            idAEliminar = null;
            if (data.success) cargarLista();
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = { nombre_categoria: nombreField.value.trim() };
            const accion = idField.value ? 'actualizar' : 'crear';
            if (idField.value) payload.id_categoria = idField.value;
            const data = await api(accion, payload);
            mostrarMsg(data.message, data.success);
            if (data.success) {
                idField.value = '';
                form.reset();
                formTitle.textContent = 'Nueva categoría';
                submitBtn.textContent = 'Guardar categoría';
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
            $stmt = $pdo->query("SELECT id_categoria, nombre_categoria FROM categoria_objeto ORDER BY nombre_categoria ASC");
            echo json_encode(["success" => true, "items" => $stmt->fetchAll()]);
            break;
        }
        case 'crear': {
            $nombre = limpiar_texto($data['nombre_categoria'] ?? '', 100);
            if ($nombre === '') {
                echo json_encode(["success" => false, "message" => "El nombre de la categoría es obligatorio."]);
                break;
            }
            $stmt = $pdo->prepare("INSERT INTO categoria_objeto (nombre_categoria) VALUES (?)");
            $stmt->execute([$nombre]);
            echo json_encode(["success" => true, "message" => "Categoría creada correctamente."]);
            break;
        }
        case 'actualizar': {
            $id = limpiar_id($data['id_categoria'] ?? null);
            $nombre = limpiar_texto($data['nombre_categoria'] ?? '', 100);
            if (!$id || $nombre === '') {
                echo json_encode(["success" => false, "message" => "Datos inválidos para actualizar."]);
                break;
            }
            $stmt = $pdo->prepare("UPDATE categoria_objeto SET nombre_categoria = ? WHERE id_categoria = ?");
            $stmt->execute([$nombre, $id]);
            echo json_encode(["success" => true, "message" => "Categoría actualizada."]);
            break;
        }
        case 'eliminar': {
            $id = limpiar_id($data['id_categoria'] ?? null);
            if (!$id) {
                echo json_encode(["success" => false, "message" => "ID inválido."]);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM categoria_objeto WHERE id_categoria = ?");
            $stmt->execute([$id]);
            echo json_encode(["success" => true, "message" => "Categoría eliminada."]);
            break;
        }
        default:
            echo json_encode(["success" => false, "message" => "Acción no reconocida."]);
    }
} catch (Exception $e) {
    error_log('[categorias.php] ' . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Ocurrió un error procesando la solicitud."]);
}
