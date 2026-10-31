<?php
/**
 * Layout de páginas públicas (login, registro, errores sin sesión).
 */
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'Ingresar') ?> · NovaSol Salud</title>
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
<?= $contenido ?>
<?= View::parcial('toasts') ?>
<script src="<?= asset('assets/js/app.js') ?>" defer></script>
</body>
</html>
