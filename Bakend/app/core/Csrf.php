<?php
/**
 * Protección contra CSRF: cada formulario POST lleva un token ligado a la sesión.
 */

declare(strict_types=1);

final class Csrf
{
    public static function token(): string
    {
        return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function verificar(): void
    {
        $enviado = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($enviado) || !hash_equals(self::token(), $enviado)) {
            http_response_code(419);
            View::render('errors/419', [], 'auth');
            exit;
        }
    }
}
