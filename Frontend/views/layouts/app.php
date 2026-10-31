<?php
/**
 * Layout principal (usuarios con sesión): barra lateral según permisos,
 * barra superior y zona de contenido.
 * @var string $contenido HTML de la vista
 */
$u = Auth::usuario();
$ruta = '/' . trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$noLeidas = $u && $u['idpaciente'] && Auth::puede('portal.paciente') ? Notificacion::noLeidas((int) $u['idpaciente']) : 0;

// Menú: [título del grupo => [[ruta, etiqueta, icono, permisos…]]]
$menu = [
    'Mi salud' => [
        ['/portal', 'Inicio', 'inicio', 'portal.paciente'],
        ['/portal/reservar', 'Reservar cita', 'calendario-mas', 'portal.paciente'],
        ['/portal/citas', 'Mis citas', 'calendario-ok', 'portal.paciente'],
        ['/portal/historial', 'Historial médico', 'historial', 'portal.paciente'],
        ['/portal/perfil', 'Mi perfil', 'cuenta', 'portal.paciente'],
    ],
    'General' => [
        ['/panel', 'Panel', 'panel', 'panel.ver'],
        ['/agenda', Auth::puede('agenda.ver') ? 'Agenda médica' : 'Mi agenda', 'agenda', 'agenda.ver', 'agenda.propia'],
    ],
    'Citas y pacientes' => [
        ['/citas/nueva', 'Programar cita', 'calendario-mas', 'citas.gestionar'],
        ['/citas', 'Citas', 'calendario', 'citas.ver'],
        ['/pacientes', 'Pacientes', 'pulso', 'pacientes.ver', 'pacientes.gestionar'],
    ],
    'Atención médica' => [
        ['/estudios', 'Estudios médicos', 'portapapeles', 'estudios.gestionar'],
    ],
    'Gestión médica' => [
        ['/medicos', 'Médicos', 'estetoscopio', 'medicos.ver', 'medicos.gestionar'],
        ['/especialidades', 'Especialidades', 'cruz', 'especialidades.gestionar'],
        ['/horarios', 'Horarios', 'reloj', 'horarios.gestionar'],
        ['/estudios/catalogo', 'Catálogo de estudios', 'documento', 'estudios.catalogo'],
    ],
    'Reportes' => [
        ['/reportes', 'Reportes', 'reporte', 'reportes.ver'],
    ],
    'Administración' => [
        ['/usuarios', 'Usuarios', 'usuarios', 'usuarios.gestionar'],
        ['/roles', 'Roles y permisos', 'escudo', 'roles.gestionar'],
        ['/configuracion', 'Configuración', 'ajustes', 'configuracion.gestionar'],
        ['/bitacora', 'Bitácora', 'bitacora', 'bitacora.ver'],
    ],
];
$activo = function (string $destino) use ($ruta): bool {
    if (in_array($destino, ['/portal', '/citas', '/estudios'], true)) {
        return $ruta === $destino || ($destino === '/citas' && in_array($ruta, ['/citas/ver', '/citas/reprogramar'], true))
            || ($destino === '/estudios' && $ruta === '/estudios/resultado');
    }
    return $ruta === $destino || str_starts_with($ruta, $destino . '/');
};
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'NovaSol Salud') ?> · <?= e(config('NOMBRE_CLINICA', 'NovaSol Salud')) ?></title>
    <meta name="theme-color" content="#0f766e">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <script>try { var t = localStorage.getItem('hs-tema'); document.documentElement.dataset.theme = t || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'); } catch (e) {}</script>
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/componentes.css') ?>">
</head>
<body>
<div class="app">
    <aside class="sidebar" aria-label="Menú principal">
        <a href="<?= e(Auth::inicio()) ?>" class="brand">
            <span class="brand-logo"><?= icono('cruz', 22) ?></span>
            <span><strong>NovaSol Salud</strong><small><?= e(config('NOMBRE_CLINICA', 'Citas médicas')) ?></small></span>
        </a>
        <nav>
            <?php foreach ($menu as $grupo => $items):
                $visibles = array_filter($items, fn($i) => Auth::puede(...array_slice($i, 3)));
                if (!$visibles) continue; ?>
                <div class="nav-title"><?= e($grupo) ?></div>
                <?php foreach ($visibles as [$destino, $etiqueta, $ico]): ?>
                    <a href="<?= e($destino) ?>" class="nav-link<?= $activo($destino) ? ' active' : '' ?>"<?= $activo($destino) ? ' aria-current="page"' : '' ?>>
                        <?= icono($ico, 19) ?><?= e($etiqueta) ?>
                        <?php if ($destino === '/portal' && $noLeidas): ?><span class="count" data-no-leidas><?= $noLeidas ?></span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <?php if (!Auth::puede('portal.paciente')): ?>
                <div class="nav-title">Cuenta</div>
                <a href="/cuenta" class="nav-link<?= $ruta === '/cuenta' ? ' active' : '' ?>"><?= icono('cuenta', 19) ?>Mi cuenta</a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-foot">
            <div class="avatar"><?= e(iniciales($u['nombre'] ?? '')) ?></div>
            <div class="who">
                <strong><?= e($u['nombre'] ?? '') ?></strong>
                <small><?= e($u['rol'] === 'MEDICO' ? 'Médico' : mb_convert_case($u['rol'] ?? '', MB_CASE_TITLE)) ?></small>
            </div>
            <form method="post" action="/salir" class="inline">
                <?= csrf_field() ?>
                <button class="btn btn-ghost btn-icon" title="Cerrar sesión" aria-label="Cerrar sesión" data-sin-carga="1"><?= icono('salir') ?></button>
            </form>
        </div>
    </aside>
    <div class="backdrop" hidden></div>

    <div class="main">
        <header class="topbar">
            <button class="btn btn-ghost btn-icon menu-btn" data-menu aria-label="Abrir menú"><?= icono('menu', 20) ?></button>
            <div class="crumbs"><?= e(config('NOMBRE_CLINICA', 'NovaSol Salud')) ?> / <b><?= e($titulo ?? '') ?></b></div>
            <div class="spacer"></div>
            <span class="faint no-print" style="font-size:13px;font-weight:600"><?= e(fecha_larga(hoy())) ?></span>
            <button class="btn btn-ghost btn-icon" data-tema aria-label="Cambiar tema claro/oscuro" title="Cambiar tema"><?= icono('luna', 19) ?></button>
        </header>
        <main class="content page" id="contenido">
            <?= $contenido ?>
        </main>
    </div>
</div>
<?= View::parcial('toasts') ?>
<?= View::parcial('modal_confirmar') ?>
<script src="<?= asset('assets/js/app.js') ?>" defer></script>
<?php if (!empty($scripts)): foreach ($scripts as $s): ?>
<script src="<?= asset('assets/js/' . $s) ?>" defer></script>
<?php endforeach; endif; ?>
</body>
</html>
