<?php
/**
 * Funciones auxiliares usadas en controladores y vistas.
 */

declare(strict_types=1);

/** Escapa un valor para imprimirlo en HTML (previene XSS). */
function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Construye una URL interna con parámetros opcionales. */
function url(string $ruta = '/', array $params = []): string
{
    $params = array_filter($params, fn($v) => $v !== null && $v !== '');
    return $ruta . ($params ? '?' . http_build_query($params) : '');
}

/** Ruta de un recurso estático con versión para evitar caché vieja. */
function asset(string $ruta): string
{
    $archivo = PUBLIC_PATH . '/' . $ruta;
    $version = is_file($archivo) ? substr((string) filemtime($archivo), -6) : '1';
    return '/' . $ruta . '?v=' . $version;
}

function redirect(string $ruta): never
{
    header('Location: ' . $ruta);
    exit;
}

/** Vuelve a la página anterior (solo rutas internas). */
function volver(string $porDefecto = '/'): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $ruta = parse_url($ref, PHP_URL_PATH) ?: $porDefecto;
    $query = parse_url($ref, PHP_URL_QUERY);
    redirect($ruta . ($query ? "?$query" : ''));
}

function json_response(array $datos, int $codigo = 200): never
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function es_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

/** Valor de un campo de texto recibido (recortado). */
function entrada(string $clave, string $porDefecto = ''): string
{
    $v = $_POST[$clave] ?? $_GET[$clave] ?? $porDefecto;
    return is_string($v) ? trim($v) : $porDefecto;
}

function entrada_int(string $clave): ?int
{
    $v = $_POST[$clave] ?? $_GET[$clave] ?? null;
    return is_numeric($v) ? (int) $v : null;
}

// ---------------------------------------------------------------------
// Mensajes flash y datos del formulario anterior
// ---------------------------------------------------------------------
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['_flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function tomar_flash(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/** Guarda errores y datos enviados para volver a mostrar el formulario. */
function con_errores(array $errores, array $datos = []): void
{
    unset($datos['password'], $datos['password_confirmacion'], $datos['_csrf']);
    $_SESSION['_errores'] = $errores;
    $_SESSION['_old'] = $datos;
}

function old(string $campo, mixed $porDefecto = ''): string
{
    return (string) ($GLOBALS['_old'][$campo] ?? $porDefecto ?? '');
}

function error_campo(string $campo): string
{
    $msg = $GLOBALS['_errores'][$campo] ?? null;
    return $msg ? '<span class="error">' . e($msg) . '</span>' : '';
}

function clase_error(string $campo): string
{
    return isset($GLOBALS['_errores'][$campo]) ? ' invalid' : '';
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

// ---------------------------------------------------------------------
// Formatos
// ---------------------------------------------------------------------
const DIAS_SEMANA = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

function fecha(?string $v): string
{
    if (!$v) return '—';
    $t = strtotime($v);
    return date('d', $t) . ' ' . MESES[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
}

function fecha_hora(?string $v): string
{
    return $v ? fecha($v) . ' · ' . date('H:i', strtotime($v)) : '—';
}

function hora(?string $v): string
{
    return $v ? substr($v, 0, 5) : '—';
}

function fecha_larga(string $v): string
{
    $t = strtotime($v);
    return DIAS_SEMANA[(int) date('N', $t)] . ' ' . date('j', $t) . ' de ' . [
        'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
    ][(int) date('n', $t) - 1];
}

function hace_cuanto(?string $v): string
{
    if (!$v) return 'Nunca';
    $s = time() - strtotime($v);
    return match (true) {
        $s < 60 => 'Hace un momento',
        $s < 3600 => 'Hace ' . intdiv($s, 60) . ' min',
        $s < 86400 => 'Hace ' . intdiv($s, 3600) . ' h',
        $s < 604800 => 'Hace ' . intdiv($s, 86400) . ' d',
        default => fecha($v),
    };
}

function edad(?string $nacimiento): string
{
    return $nacimiento ? (new DateTime($nacimiento))->diff(new DateTime())->y . ' años' : '—';
}

function iniciales(?string $nombre): string
{
    $nombre = preg_replace('/^(Dr|Dra)\.?\s+/i', '', (string) $nombre);
    $partes = array_slice(preg_split('/\s+/', trim($nombre)) ?: [], 0, 2);
    return mb_strtoupper(implode('', array_map(fn($p) => mb_substr($p, 0, 1), $partes)));
}

function hoy(): string
{
    return date('Y-m-d');
}

/** Valor de la tabla CONFIGURACION (con caché por petición). */
function config(string $clave, string $porDefecto = ''): string
{
    static $cache = null;
    $cache ??= Configuracion::todas();
    return $cache[$clave] ?? $porDefecto;
}

function ip_cliente(): string
{
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    return substr(trim(explode(',', $ip)[0]), 0, 64);
}

// ---------------------------------------------------------------------
// Insignias (badges) reutilizables
// ---------------------------------------------------------------------
function badge_estado(string $estado): string
{
    $activo = $estado === 'ACTIVO';
    return '<span class="badge ' . ($activo ? 'badge-ok' : 'badge-off') . '"><span class="dot"></span>' . ($activo ? 'Activo' : 'Inactivo') . '</span>';
}

const ESTADOS_CITA = [
    'PROGRAMADA'   => ['Programada', 'badge-info'],
    'CONFIRMADA'   => ['Confirmada', 'badge-accent'],
    'ATENDIDA'     => ['Atendida', 'badge-ok'],
    'CANCELADA'    => ['Cancelada', 'badge-off'],
    'INASISTENCIA' => ['Inasistencia', 'badge-warn'],
];

function badge_cita(string $codigo): string
{
    [$nombre, $clase] = ESTADOS_CITA[$codigo] ?? [$codigo, 'badge-off'];
    return '<span class="badge ' . $clase . '"><span class="dot"></span>' . e($nombre) . '</span>';
}

function badge_rol(?string $rol): string
{
    $clase = ['ADMINISTRADOR' => 'badge-accent', 'MEDICO' => 'badge-primary', 'RECEPCIONISTA' => 'badge-warn', 'PACIENTE' => 'badge-info'][$rol] ?? 'badge-off';
    $nombre = $rol === 'MEDICO' ? 'Médico' : mb_convert_case((string) $rol, MB_CASE_TITLE);
    return '<span class="badge ' . $clase . '">' . e($nombre) . '</span>';
}

/** Icono SVG (subconjunto de Lucide) por nombre. */
function icono(string $nombre, int $tam = 18): string
{
    return Iconos::svg($nombre, $tam);
}
