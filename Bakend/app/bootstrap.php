<?php
/**
 * Nova Salud · Arranque de la aplicación
 * ---------------------------------------------------------------------
 * Configura zona horaria, variables de entorno, manejo de errores,
 * carga automática de clases y la sesión. Todas las peticiones pasan
 * por aquí desde el controlador frontal (api/index.php).
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__, 2));             // raíz del proyecto
define('APP_PATH', __DIR__);                          // Bakend/app: lo que el usuario no ve
define('VIEWS_PATH', BASE_PATH . '/Frontend/views');  // pantallas
define('PUBLIC_PATH', BASE_PATH . '/Frontend/public'); // CSS, JS e imágenes

date_default_timezone_set('America/La_Paz');
mb_internal_encoding('UTF-8');

// ---------------------------------------------------------------------
// Variables de entorno: en Vercel vienen del panel; en local, del archivo .env
// ---------------------------------------------------------------------
$archivoEnv = BASE_PATH . '/.env';
if (is_file($archivoEnv)) {
    foreach (file($archivoEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
            continue;
        }
        [$clave, $valor] = array_map('trim', explode('=', $linea, 2));
        if (getenv($clave) === false) {
            putenv("$clave=$valor");
        }
    }
}

define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', APP_ENV === 'local');

// Nunca se muestran detalles internos al usuario en producción
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// ---------------------------------------------------------------------
// Carga automática de clases (core, modelos, servicios y controladores)
// ---------------------------------------------------------------------
spl_autoload_register(function (string $clase): void {
    foreach (['core', 'models', 'services', 'controllers'] as $carpeta) {
        $ruta = APP_PATH . "/$carpeta/$clase.php";
        if (is_file($ruta)) {
            require $ruta;
            return;
        }
    }
});

require APP_PATH . '/core/helpers.php';

// Cualquier excepción no controlada termina en una página de error genérica
set_exception_handler(function (Throwable $e): void {
    error_log('[Nova Salud] ' . $e::class . ': ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (es_ajax()) {
        echo json_encode(['ok' => false, 'error' => 'Ocurrió un error inesperado. Intente nuevamente.']);
        return;
    }
    View::render('errors/500', ['detalle' => APP_DEBUG ? $e->getMessage() : null], 'auth');
});

// Toda entrada debe ser UTF-8 válido: un texto mal codificado no se puede
// escapar con seguridad para PostgreSQL, así que la petición se rechaza
array_walk_recursive($_POST, function ($v): void {
    if (is_string($v) && !mb_check_encoding($v, 'UTF-8')) {
        http_response_code(400);
        exit('Solicitud inválida: el texto enviado no está en UTF-8.');
    }
});
array_walk_recursive($_GET, function ($v): void {
    if (is_string($v) && !mb_check_encoding($v, 'UTF-8')) {
        http_response_code(400);
        exit('Solicitud inválida: el texto enviado no está en UTF-8.');
    }
});

Session::iniciar();
