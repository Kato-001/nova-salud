<?php
/**
 * Layout de la página pública (portada de la clínica): sin menú lateral,
 * con barra superior y pie de página.
 */
$u = Auth::usuario();
$clinica = config('NOMBRE_CLINICA', 'Clínica Vida Salud');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'Inicio') ?> · NovaSol Salud</title>
    <meta name="description" content="<?= e($clinica) ?>: especialidades, médicos y reserva de citas en línea.">
    <meta name="theme-color" content="#0f766e">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>try { var t = localStorage.getItem('hs-tema'); document.documentElement.dataset.theme = t || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'); } catch (e) {}</script>
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/componentes.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/publico.css') ?>">
</head>
<body class="pub">
<header class="pub-nav">
    <div class="pub-wrap pub-nav-in">
        <a href="/" class="brand pub-brand">
            <div class="brand-logo"><?= icono('cruz', 20) ?></div>
            <div><strong>NovaSol Salud</strong><small><?= e($clinica) ?></small></div>
        </a>
        <nav class="pub-links" aria-label="Secciones">
            <a href="#especialidades">Especialidades</a>
            <a href="#medicos">Médicos</a>
            <a href="#como-reservar">Cómo reservar</a>
            <a href="#contacto">Contacto</a>
        </nav>
        <div class="pub-acciones">
            <button class="btn btn-ghost btn-icon" data-tema aria-label="Cambiar tema claro/oscuro" title="Cambiar tema"><?= icono('luna', 19) ?></button>
            <?php if ($u): ?>
                <a href="<?= Auth::inicio() ?>" class="btn btn-primary"><?= icono('panel', 16) ?> <?= Auth::inicio() === '/portal' ? 'Mi portal' : 'Ir a mi panel' ?></a>
            <?php else: ?>
                <a href="/login" class="btn btn-outline">Ingresar</a>
                <a href="/registro" class="btn btn-primary pub-solo-ancho">Crear cuenta</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?= $contenido ?>

<footer class="pub-pie" id="contacto">
    <div class="pub-wrap pub-pie-in">
        <div>
            <a href="/" class="brand pub-brand">
                <div class="brand-logo"><?= icono('cruz', 20) ?></div>
                <div><strong>NovaSol Salud</strong><small><?= e($clinica) ?></small></div>
            </a>
            <p>Atención ambulatoria en varias especialidades, con reserva de citas en línea y tu historial siempre disponible.</p>
        </div>
        <div>
            <h4>Atención</h4>
            <p><?= icono('reloj', 15) ?> Lunes a sábado, según el horario de cada médico</p>
            <p><?= icono('calendario-ok', 15) ?> Citas de <?= (int) config('DURACION_CITA_MINUTOS', '30') ?> minutos</p>
        </div>
        <div>
            <h4>Contacto</h4>
            <p><?= icono('inicio', 15) ?> Santa Cruz de la Sierra, Bolivia</p>
            <p><?= icono('telefono', 15) ?> Recepción de la clínica</p>
            <p><?= icono('correo', 15) ?> recepcion@vidasalud.com</p>
        </div>
    </div>
    <div class="pub-wrap pub-legal">© <?= date('Y') ?> NovaSol Salud · Proyecto académico · Grupo 20 · UAGRM – FICCT</div>
</footer>
<?= View::parcial('toasts') ?>
<script src="<?= asset('assets/js/app.js') ?>" defer></script>
</body>
</html>
