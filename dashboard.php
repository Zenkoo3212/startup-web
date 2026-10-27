<?php
session_start();
require 'helpers.php';
requerir_sesion_pagina();

$nombre = $_SESSION['nombre'] ?? 'Jugador';
$correo = $_SESSION['correo'] ?? '';
$rol    = $_SESSION['rol'] ?? 'Jugador';
if (function_exists('mb_substr')) {
    $inicial = mb_strtoupper(mb_substr($nombre, 0, 1));
} else {
    $inicial = strtoupper(substr($nombre, 0, 1));
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard · Startup</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
    <div class="topbar">
        <a href="dashboard.php" class="brand"><span class="brand-badge">SP</span> Startup</a>
        <div class="topbar-user">
            <span class="pill"><?php echo htmlspecialchars($rol); ?></span>
            <span>Hola, <b><?php echo htmlspecialchars($nombre); ?></b></span>
            <a href="logout.php" class="btn btn-ghost btn-sm">Cerrar sesión</a>
        </div>
    </div>

    <div class="wrap">
        <div class="profile-card">
            <div class="avatar-circle"><?php echo htmlspecialchars($inicial); ?></div>
            <div style="flex:1;min-width:200px;">
                <h2 style="font-size:1.3rem;"><?php echo htmlspecialchars($nombre); ?></h2>
                <p style="color:var(--muted);font-size:.88rem;margin-top:4px;"><?php echo htmlspecialchars($correo); ?></p>
            </div>
        </div>

        <div id="statsLoading" class="loading">Cargando tu progreso...</div>
        <div class="stats-row" id="statsRow" style="display:none;">
            <div class="stat-box"><div class="label">Nivel</div><div class="value" id="statNivel">—</div></div>
            <div class="stat-box"><div class="label">XP</div><div class="value" id="statXp">—</div></div>
            <div class="stat-box"><div class="label">Monedas</div><div class="value" id="statMonedas">—</div></div>
            <div class="stat-box"><div class="label">Negocios creados</div><div class="value" id="statNegocios">—</div></div>
        </div>

        <?php if ($rol === 'Admin'): ?>
            <div class="page-head">
                <div>
                    <h1>Panel de administración</h1>
                    <p>Gestiona el contenido del juego. Estas pantallas solo las ve el rol Admin.</p>
                </div>
            </div>

            <div class="grid-menu">
                <a href="categorias.php" class="menu-card">
                    <div class="icon">🏷️</div>
                    <h3>Categorías de objetos</h3>
                    <span class="desc">Crea y organiza las categorías del catálogo de objetos.</span>
                </a>
                <a href="objetos.php" class="menu-card">
                    <div class="icon">🎒</div>
                    <h3>Objetos</h3>
                    <span class="desc">Administra la ropa, accesorios y demás objetos del juego.</span>
                </a>
                <a href="npcs.php" class="menu-card">
                    <div class="icon">🧑‍💼</div>
                    <h3>NPCs</h3>
                    <span class="desc">Crea los personajes no jugables que interactúan en el juego.</span>
                </a>
                <a href="actividades.php" class="menu-card">
                    <div class="icon">📋</div>
                    <h3>Actividades</h3>
                    <span class="desc">Define las actividades, su dificultad y recompensas.</span>
                </a>
                <a href="tiendas.php" class="menu-card">
                    <div class="icon">🏪</div>
                    <h3>Tiendas</h3>
                    <span class="desc">Administra las tiendas donde los jugadores compran objetos.</span>
                </a>
            </div>
        <?php else: ?>
            <div class="page-head">
                <div>
                    <h1>Tu aventura</h1>
                    <p>Tu progreso, actividades y compras se sincronizan automáticamente desde el juego.</p>
                </div>
            </div>

            <div class="grid-menu">
                <div class="menu-card" style="cursor:default;">
                    <div class="icon">🎮</div>
                    <h3>Abre el juego</h3>
                    <span class="desc">Inicia el juego en Unreal Engine con este mismo correo para seguir jugando; tu nivel, XP y monedas se leen y guardan aquí automáticamente.</span>
                </div>
                <div class="menu-card" style="cursor:default;">
                    <div class="icon">📋</div>
                    <h3>Actividades</h3>
                    <span class="desc">Complétalas hablando con los NPCs dentro del juego para ganar XP y monedas.</span>
                </div>
                <div class="menu-card" style="cursor:default;">
                    <div class="icon">🏪</div>
                    <h3>Tienda</h3>
                    <span class="desc">Usa tus monedas dentro del juego para comprar objetos y accesorios.</span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($rol === 'Admin'): ?>
            <div class="panel" style="margin-top:24px;">
                <h2>Base de datos</h2>
                <p class="sub">Descarga el esquema completo para respaldarlo o importarlo en otro servidor.</p>
                <a href="descargar_bd.php" class="btn btn-primary">⬇ Descargar base de datos</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        (async function cargarStats() {
            const loading = document.getElementById('statsLoading');
            const row = document.getElementById('statsRow');
            try {
                const res = await fetch('obtener_usuario.php?correo=' + encodeURIComponent(<?php echo json_encode($correo); ?>));
                const data = await res.json();
                if (data.success) {
                    document.getElementById('statNivel').textContent = data.usuario.nivel ?? '—';
                    document.getElementById('statXp').textContent = data.usuario.xp ?? '—';
                    document.getElementById('statMonedas').textContent = data.usuario.monedas ?? '—';
                    document.getElementById('statNegocios').textContent = data.usuario.negocios_creados ?? '—';
                    loading.style.display = 'none';
                    row.style.display = 'grid';
                } else {
                    loading.textContent = 'No se pudo cargar tu progreso.';
                }
            } catch (err) {
                loading.textContent = 'No se pudo conectar con el servidor.';
            }
        })();
    </script>
</body>
</html>
