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
        <title>Objetos · Startup</title>
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
                    <h1>Objetos</h1>
                    <p>Ropa, accesorios y demás objetos que los jugadores pueden obtener o comprar.</p>
                </div>
            </div>

            <div id="msg"></div>

            <div class="panel">
                <h2 id="formTitle">Nuevo objeto</h2>
                <p class="sub">Completa los datos del objeto.</p>
                <form id="form">
                    <input type="hidden" id="id_objeto" value="">
                    <div class="form-grid">
                        <div>
                            <label for="nombre">Nombre</label>
                            <input id="nombre" required maxlength="120" placeholder="Ej: Gorra plana">
                        </div>
                        <div>
                            <label for="tipo">Tipo</label>
                            <input id="tipo" required maxlength="60" placeholder="Ej: gorra, chaqueta, accesorio">
                        </div>
                        <div>
                            <label for="credito_xp">Crédito XP</label>
                            <input id="credito_xp" type="number" min="0" value="0" required>
                        </div>
                        <div>
                            <label for="id_categoria">Categoría</label>
                            <select id="id_categoria">
                                <option value="">Sin categoría</option>
                            </select>
                        </div>
                    </div>
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" maxlength="500" placeholder="Descripción breve del objeto"></textarea>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="submitBtn">Guardar objeto</button>
                        <button type="button" class="btn btn-ghost" id="cancelBtn" style="display:none;">Cancelar edición</button>
                    </div>
                </form>
            </div>

            <div class="panel">
                <h2>Listado</h2>
                <p class="sub">Todos los objetos registrados.</p>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>XP</th><th>Categoría</th><th>Acciones</th></tr></thead>
                        <tbody id="tbody">
                            <tr class="empty-row"><td colspan="6">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="modalConfirm">
            <div class="modal-box">
                <h3>¿Eliminar objeto?</h3>
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
        const idField = document.getElementById('id_objeto');
        const nombreField = document.getElementById('nombre');
        const tipoField = document.getElementById('tipo');
        const xpField = document.getElementById('credito_xp');
        const catField = document.getElementById('id_categoria');
        const descField = document.getElementById('descripcion');
        const submitBtn = document.getElementById('submitBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const formTitle = document.getElementById('formTitle');
        const tbody = document.getElementById('tbody');
        const modal = document.getElementById('modalConfirm');
        let idAEliminar = null;
        let categorias = [];

        function mostrarMsg(texto, ok) {
            msg.textContent = texto;
            msg.className = ok ? 'ok' : 'err';
            setTimeout(() => { msg.className = ''; }, 4000);
        }
        function escapeHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        async function api(accion, payload = {}) {
            const res = await fetch('objetos.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ accion, ...payload })
            });
            return res.json();
        }

        async function cargarCategorias() {
            const data = await api('listar_categorias');
            if (data.success) {
                categorias = data.items;
                catField.innerHTML = '<option value="">Sin categoría</option>' +
                    categorias.map(c => `<option value="${c.id_categoria}">${escapeHtml(c.nombre_categoria)}</option>`).join('');
            }
        }

        function nombreCategoria(id) {
            const c = categorias.find(c => String(c.id_categoria) === String(id));
            return c ? c.nombre_categoria : '—';
        }

        async function cargarLista() {
            tbody.innerHTML = '<tr class="empty-row"><td colspan="6">Cargando...</td></tr>';
            const data = await api('listar');
            if (!data.success) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="6">' + (data.message || 'Error al cargar') + '</td></tr>';
                return;
            }
            if (!data.items.length) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="6">Aún no hay objetos. Crea el primero arriba.</td></tr>';
                return;
            }
            tbody.innerHTML = data.items.map(it => `
                <tr>
                    <td data-label="ID">${it.id_objeto}</td>
                    <td data-label="Nombre">${escapeHtml(it.nombre)}</td>
                    <td data-label="Tipo"><span class="badge">${escapeHtml(it.tipo)}</span></td>
                    <td data-label="XP">${it.credito_xp}</td>
                    <td data-label="Categoría">${escapeHtml(nombreCategoria(it.id_categoria))}</td>
                    <td data-label="Acciones">
                        <div class="row-actions">
                            <button class="btn btn-ghost btn-sm" onclick='editar(${JSON.stringify(it)})'>Editar</button>
                            <button class="btn btn-danger btn-sm" onclick="pedirEliminar(${it.id_objeto})">Eliminar</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        window.editar = function(it) {
            idField.value = it.id_objeto;
            nombreField.value = it.nombre;
            tipoField.value = it.tipo;
            xpField.value = it.credito_xp;
            catField.value = it.id_categoria ?? '';
            descField.value = it.descripcion ?? '';
            formTitle.textContent = 'Editar objeto';
            submitBtn.textContent = 'Actualizar objeto';
            cancelBtn.style.display = 'inline-flex';
            window.scrollTo({top: 0, behavior: 'smooth'});
        };

        cancelBtn.addEventListener('click', () => {
            idField.value = '';
            form.reset();
            formTitle.textContent = 'Nuevo objeto';
            submitBtn.textContent = 'Guardar objeto';
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
            const data = await api('eliminar', { id_objeto: idAEliminar });
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
                credito_xp: xpField.value,
                id_categoria: catField.value || null,
                descripcion: descField.value.trim()
            };
            const accion = idField.value ? 'actualizar' : 'crear';
            if (idField.value) payload.id_objeto = idField.value;
            const data = await api(accion, payload);
            mostrarMsg(data.message, data.success);
            if (data.success) {
                idField.value = '';
                form.reset();
                formTitle.textContent = 'Nuevo objeto';
                submitBtn.textContent = 'Guardar objeto';
                cancelBtn.style.display = 'none';
                cargarLista();
            }
        });

        (async () => { await cargarCategorias(); await cargarLista(); })();
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
            $stmt = $pdo->query("SELECT id_objeto, nombre, tipo, descripcion, credito_xp, id_categoria FROM objeto ORDER BY nombre ASC");
            echo json_encode(["success" => true, "items" => $stmt->fetchAll()]);
            break;
        }
        case 'listar_categorias': {
            $stmt = $pdo->query("SELECT id_categoria, nombre_categoria FROM categoria_objeto ORDER BY nombre_categoria ASC");
            echo json_encode(["success" => true, "items" => $stmt->fetchAll()]);
            break;
        }
        case 'crear': {
            $nombre = limpiar_texto($data['nombre'] ?? '', 120);
            $tipo = limpiar_texto($data['tipo'] ?? '', 60);
            $descripcion = limpiar_texto($data['descripcion'] ?? '', 500);
            $xp = isset($data['credito_xp']) && is_numeric($data['credito_xp']) ? (int)$data['credito_xp'] : 0;
            $idCat = limpiar_id($data['id_categoria'] ?? null);

            if ($nombre === '' || $tipo === '') {
                echo json_encode(["success" => false, "message" => "El nombre y el tipo son obligatorios."]);
                break;
            }
            $stmt = $pdo->prepare("INSERT INTO objeto (nombre, tipo, descripcion, credito_xp, id_categoria) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $tipo, $descripcion, $xp, $idCat]);
            echo json_encode(["success" => true, "message" => "Objeto creado correctamente."]);
            break;
        }
        case 'actualizar': {
            $id = limpiar_id($data['id_objeto'] ?? null);
            $nombre = limpiar_texto($data['nombre'] ?? '', 120);
            $tipo = limpiar_texto($data['tipo'] ?? '', 60);
            $descripcion = limpiar_texto($data['descripcion'] ?? '', 500);
            $xp = isset($data['credito_xp']) && is_numeric($data['credito_xp']) ? (int)$data['credito_xp'] : 0;
            $idCat = limpiar_id($data['id_categoria'] ?? null);

            if (!$id || $nombre === '' || $tipo === '') {
                echo json_encode(["success" => false, "message" => "Datos inválidos para actualizar."]);
                break;
            }
            $stmt = $pdo->prepare("UPDATE objeto SET nombre=?, tipo=?, descripcion=?, credito_xp=?, id_categoria=? WHERE id_objeto=?");
            $stmt->execute([$nombre, $tipo, $descripcion, $xp, $idCat, $id]);
            echo json_encode(["success" => true, "message" => "Objeto actualizado."]);
            break;
        }
        case 'eliminar': {
            $id = limpiar_id($data['id_objeto'] ?? null);
            if (!$id) {
                echo json_encode(["success" => false, "message" => "ID inválido."]);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM objeto WHERE id_objeto = ?");
            $stmt->execute([$id]);
            echo json_encode(["success" => true, "message" => "Objeto eliminado."]);
            break;
        }
        default:
            echo json_encode(["success" => false, "message" => "Acción no reconocida."]);
    }
} catch (Exception $e) {
    error_log('[objetos.php] ' . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Ocurrió un error procesando la solicitud."]);
}
