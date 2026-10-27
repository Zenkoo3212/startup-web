<?php
session_start();
require __DIR__ . '/includes/helpers.php';
requerir_sesion_pagina();
header('Cache-Control: no-store');

// Esta página solo consulta. Las escrituras siguen en los CRUD protegidos por rol Admin.
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    http_response_code(405);
    exit('Este apartado solo permite consultar el contenido.');
}

$catalogos = [
    'actividades' => [
        'titulo' => 'Actividades', 'icono' => '📋',
        'intro' => 'Conoce los retos disponibles, su dificultad y sus recompensas.',
        'campos' => 'a.id_actividad AS id, a.tipo_actividad AS nombre, a.descripcion, a.dificultad, a.recompensa_xp, a.recompensa_monedas',
        'desde' => 'actividad a', 'buscar' => ['a.tipo_actividad', 'a.descripcion'], 'orden' => 'a.id_actividad DESC',
    ],
    'objetos' => [
        'titulo' => 'Objetos', 'icono' => '🎒',
        'intro' => 'Explora los objetos del catálogo y consulta sus características.',
        'campos' => 'o.id_objeto AS id, o.nombre, o.tipo, o.descripcion, o.credito_xp, c.nombre_categoria',
        'desde' => 'objeto o LEFT JOIN categoria_objeto c ON c.id_categoria = o.id_categoria',
        'buscar' => ['o.nombre', 'o.tipo', 'o.descripcion', 'c.nombre_categoria'], 'orden' => 'o.id_objeto DESC',
    ],
    'categorias' => [
        'titulo' => 'Categorías', 'icono' => '🏷️',
        'intro' => 'Encuentra una categoría y descubre los objetos que contiene.',
        'campos' => 'c.id_categoria AS id, c.nombre_categoria AS nombre',
        'desde' => 'categoria_objeto c', 'buscar' => ['c.nombre_categoria'], 'orden' => 'c.nombre_categoria, c.id_categoria',
    ],
    'npcs' => [
        'titulo' => 'Personajes NPC', 'icono' => '🧑‍💼',
        'intro' => 'Conoce los personajes, su tipo, personalidad y nivel inicial.',
        'campos' => 'n.id_npc AS id, n.nombre, n.tipo, n.personalidad, n.nivel_inicio',
        'desde' => 'npc n', 'buscar' => ['n.nombre', 'n.tipo', 'n.personalidad'], 'orden' => 'n.id_npc DESC',
    ],
    'tiendas' => [
        'titulo' => 'Tiendas', 'icono' => '🏪',
        'intro' => 'Consulta las tiendas registradas y los objetos de su catálogo.',
        'campos' => 't.id_tienda AS id, t.nombre_tienda AS nombre, t.descripcion',
        'desde' => 'tienda t', 'buscar' => ['t.nombre_tienda', 't.descripcion'], 'orden' => 't.id_tienda DESC',
    ],
];
function texto_explorar(string $clave, int $max = 100): string {
    return isset($_GET[$clave]) && is_string($_GET[$clave]) ? limpiar_texto($_GET[$clave], $max) : '';
}
function html_explorar($valor): string {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function enlace_explorar(array $parametros): string {
    return 'explorar.php?' . http_build_query($parametros);
}
$seccion = texto_explorar('seccion', 30);
if (!isset($catalogos[$seccion])) $seccion = 'actividades';
$catalogo = $catalogos[$seccion];
$q = texto_explorar('q');
$dificultades = ['facil' => 'Fácil', 'media' => 'Media', 'dificil' => 'Difícil'];
$dificultad = texto_explorar('dificultad', 10);
if ($seccion !== 'actividades' || !isset($dificultades[$dificultad])) $dificultad = '';
$categoria = $seccion === 'objetos' ? filter_var(texto_explorar('categoria', 10), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
$tienda = $seccion === 'objetos' ? filter_var(texto_explorar('tienda', 10), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
$pagina = filter_var(texto_explorar('pagina', 10), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$filtros = ['seccion' => $seccion];
$condiciones = []; $valores = [];
if ($q !== '') {
    $busquedas = [];
    foreach ($catalogo['buscar'] as $campo) {
        $busquedas[] = "$campo LIKE ? ESCAPE '!'";
        $valores[] = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q) . '%';
    }
    $condiciones[] = '(' . implode(' OR ', $busquedas) . ')';
    $filtros['q'] = $q;
}
if ($dificultad !== '') {
    $condiciones[] = 'a.dificultad = ?'; $valores[] = $dificultad; $filtros['dificultad'] = $dificultad;
}
if ($categoria) {
    $condiciones[] = 'o.id_categoria = ?'; $valores[] = $categoria; $filtros['categoria'] = $categoria;
}
if ($tienda) {
    $condiciones[] = 'EXISTS (SELECT 1 FROM tienda_objeto relacion WHERE relacion.id_objeto = o.id_objeto AND relacion.id_tienda = ?)';
    $valores[] = $tienda; $filtros['tienda'] = $tienda;
}
$donde = $condiciones ? ' WHERE ' . implode(' AND ', $condiciones) : '';
$items = []; $total = 0; $paginas = 1; $error = false;
require __DIR__ . '/configuracion/config.php';
try {
    // Los nombres de tablas y columnas proceden únicamente del mapa fijo anterior.
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM ' . $catalogo['desde'] . $donde);
    $stmt->execute($valores);
    $total = (int)$stmt->fetchColumn();
    $paginas = max(1, (int)ceil($total / 12));
    $pagina = min($pagina, $paginas);
    $stmt = $pdo->prepare('SELECT ' . $catalogo['campos'] . ' FROM ' . $catalogo['desde'] . $donde . ' ORDER BY ' . $catalogo['orden'] . ' LIMIT ? OFFSET ?');
    foreach ($valores as $i => $valor) $stmt->bindValue($i + 1, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
    $stmt->bindValue(count($valores) + 1, 12, PDO::PARAM_INT);
    $stmt->bindValue(count($valores) + 2, ($pagina - 1) * 12, PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('[Startup] No se pudo consultar el catálogo de ' . $seccion . '.');
    http_response_code(503);
    $error = true;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo html_explorar($catalogo['titulo']); ?> · Explorar Startup</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body class="catalog-page">
    <header class="topbar">
        <a href="dashboard.php" class="brand"><span class="brand-badge">SP</span> Startup</a>
        <div class="topbar-user">
            <span>Hola, <b><?php echo html_explorar($_SESSION['nombre'] ?? 'Jugador'); ?></b></span>
            <a href="dashboard.php" class="btn btn-ghost btn-sm">Mi panel</a>
            <a href="logout.php" class="btn btn-ghost btn-sm">Cerrar sesión</a>
        </div>
    </header>
    <main class="wrap">
        <a href="dashboard.php" class="back-link">← Volver a mi panel</a>
        <div class="page-head">
            <div><h1>Explora Startup</h1><p>Descubre las actividades, objetos y personajes disponibles.</p></div>
        </div>
        <nav class="catalog-nav" aria-label="Catálogos de Startup">
            <?php foreach ($catalogos as $clave => $datos): ?>
                <a class="btn btn-ghost<?php echo $seccion === $clave ? ' catalog-active' : ''; ?>" href="<?php echo html_explorar(enlace_explorar(['seccion' => $clave])); ?>"<?php echo $seccion === $clave ? ' aria-current="page"' : ''; ?>>
                    <span aria-hidden="true"><?php echo $datos['icono']; ?></span> <?php echo html_explorar($datos['titulo']); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <section aria-labelledby="catalog-title">
            <h2 id="catalog-title"><?php echo html_explorar($catalogo['titulo']); ?></h2>
            <p class="catalog-intro"><?php echo html_explorar($catalogo['intro']); ?></p>
            <?php if ($categoria || $tienda): ?><p class="hint">Mostrando los objetos <?php echo $categoria ? 'de la categoría' : 'de la tienda'; ?> seleccionada.</p><?php endif; ?>
            <form method="get" action="explorar.php" class="catalog-filters" role="search">
                <input type="hidden" name="seccion" value="<?php echo html_explorar($seccion); ?>">
                <?php foreach (['categoria', 'tienda'] as $campo): if (isset($filtros[$campo])): ?>
                    <input type="hidden" name="<?php echo $campo; ?>" value="<?php echo html_explorar($filtros[$campo]); ?>">
                <?php endif; endforeach; ?>
                <div class="catalog-search"><label for="catalog-q">Buscar en <?php echo html_explorar(mb_strtolower($catalogo['titulo'])); ?></label><input id="catalog-q" type="search" name="q" maxlength="100" value="<?php echo html_explorar($q); ?>" placeholder="Escribe un nombre o una palabra"></div>
                <?php if ($seccion === 'actividades'): ?>
                    <div><label for="catalog-dificultad">Dificultad</label><select id="catalog-dificultad" name="dificultad"><option value="">Todas</option><?php foreach ($dificultades as $valor => $etiqueta): ?><option value="<?php echo $valor; ?>"<?php echo $dificultad === $valor ? ' selected' : ''; ?>><?php echo $etiqueta; ?></option><?php endforeach; ?></select></div>
                <?php endif; ?>
                <button class="btn btn-primary" type="submit">Buscar</button>
                <a href="<?php echo html_explorar(enlace_explorar(['seccion' => $seccion])); ?>" class="btn btn-ghost">Limpiar filtros</a>
            </form>
            <?php if ($error): ?>
                <div class="panel catalog-empty" role="alert"><h3>No pudimos cargar este catálogo</h3><p>Intenta de nuevo en unos minutos.</p><a class="btn btn-ghost" href="<?php echo html_explorar(enlace_explorar($filtros)); ?>">Reintentar</a></div>
            <?php elseif (!$items): ?>
                <div class="panel catalog-empty"><h3><?php echo count($filtros) > 1 ? 'No encontramos resultados' : 'Todavía no hay contenido en este catálogo'; ?></h3><p><?php echo count($filtros) > 1 ? 'Prueba otra búsqueda o limpia los filtros.' : 'Vuelve más adelante para ver las novedades.'; ?></p></div>
            <?php else: ?>
                <p class="catalog-count"><?php echo $total; ?> <?php echo $total === 1 ? 'resultado' : 'resultados'; ?> · Página <?php echo $pagina; ?> de <?php echo $paginas; ?></p>
                <div class="catalog-grid">
                <?php foreach ($items as $item): ?>
                    <article class="panel catalog-card">
                        <span class="catalog-icon" aria-hidden="true"><?php echo $catalogo['icono']; ?></span>
                        <h3><?php echo html_explorar($item['nombre']); ?></h3>
                        <?php if ($seccion === 'actividades'): ?>
                            <span class="badge"><?php echo html_explorar($dificultades[$item['dificultad']] ?? $item['dificultad']); ?></span>
                            <dl class="catalog-facts"><div><dt>Recompensa XP</dt><dd><?php echo html_explorar($item['recompensa_xp']); ?></dd></div><div><dt>Monedas</dt><dd><?php echo html_explorar($item['recompensa_monedas']); ?></dd></div></dl>
                        <?php elseif ($seccion === 'objetos'): ?>
                            <span class="badge"><?php echo html_explorar($item['nombre_categoria'] ?? 'Sin categoría'); ?></span>
                            <dl class="catalog-facts"><div><dt>Tipo</dt><dd><?php echo html_explorar($item['tipo']); ?></dd></div><div><dt>Crédito XP</dt><dd><?php echo html_explorar($item['credito_xp']); ?></dd></div></dl>
                        <?php elseif ($seccion === 'npcs'): ?>
                            <dl class="catalog-facts"><div><dt>Tipo</dt><dd><?php echo html_explorar($item['tipo'] ?: 'Sin especificar'); ?></dd></div><div><dt>Nivel inicial</dt><dd><?php echo html_explorar($item['nivel_inicio']); ?></dd></div></dl>
                        <?php endif; ?>
                        <?php if ($seccion !== 'categorias'): ?>
                            <details class="catalog-details"><summary>Ver detalles<span class="sr-only"> de <?php echo html_explorar($item['nombre']); ?></span></summary><p><?php echo nl2br(html_explorar(($seccion === 'npcs' ? $item['personalidad'] : $item['descripcion']) ?: 'Aún no hay una descripción disponible.')); ?></p></details>
                        <?php endif; ?>
                        <?php if ($seccion === 'categorias' || $seccion === 'tiendas'): ?>
                            <a class="btn btn-ghost btn-sm catalog-related" href="<?php echo html_explorar(enlace_explorar(['seccion' => 'objetos', $seccion === 'categorias' ? 'categoria' : 'tienda' => $item['id']])); ?>">Ver objetos<span class="sr-only"> de <?php echo html_explorar($item['nombre']); ?></span> →</a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
                </div>
                <?php if ($paginas > 1): ?>
                    <nav class="catalog-pagination" aria-label="Páginas de resultados">
                        <?php if ($pagina > 1): ?><a class="btn btn-ghost" href="<?php echo html_explorar(enlace_explorar($filtros + ['pagina' => $pagina - 1])); ?>">← Anterior</a><?php endif; ?>
                        <span>Página <?php echo $pagina; ?> de <?php echo $paginas; ?></span>
                        <?php if ($pagina < $paginas): ?><a class="btn btn-ghost" href="<?php echo html_explorar(enlace_explorar($filtros + ['pagina' => $pagina + 1])); ?>">Siguiente →</a><?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
