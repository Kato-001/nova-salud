<?php
/**
 * Enrutador para el servidor integrado de PHP (desarrollo local):
 *   php -S localhost:8000 router.php
 * Entrega solo los archivos de Frontend/public y envía el resto al controlador frontal.
 */
$ruta = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$publico = realpath(__DIR__ . '/Frontend/public');
$archivo = realpath($publico . $ruta);

if ($ruta !== '/' && $archivo && is_file($archivo) && str_starts_with($archivo, $publico)) {
    $tipos = ['css' => 'text/css', 'js' => 'application/javascript', 'svg' => 'image/svg+xml', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'webp' => 'image/webp', 'ico' => 'image/x-icon'];
    header('Content-Type: ' . ($tipos[pathinfo($archivo, PATHINFO_EXTENSION)] ?? 'application/octet-stream'));
    readfile($archivo);
    return true;
}
require __DIR__ . '/api/index.php';
