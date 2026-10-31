<?php
/**
 * Sesiones PHP guardadas en PostgreSQL (tabla SESION).
 * En Vercel cada petición puede atenderla una instancia distinta, por lo que
 * las sesiones en archivos no sirven; con este manejador persisten en la BD.
 */

declare(strict_types=1);

final class Session implements SessionHandlerInterface
{
    private const DURACION = 7200; // 2 horas de inactividad

    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        session_set_save_handler(new self(), true);
        session_name('HSSESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $https,
            'httponly' => true,      // inaccesible desde JavaScript
            'samesite' => 'Lax',     // ayuda contra CSRF entre sitios
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.gc_maxlifetime', (string) self::DURACION);
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '100');
        session_start();
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        return (string) (Database::valor(
            'select datos from sesion where id = :id and actualizado > now() - make_interval(secs => :ttl)',
            ['id' => $id, 'ttl' => self::DURACION]
        ) ?? '');
    }

    public function write(string $id, string $data): bool
    {
        Database::query(
            'insert into sesion (id, datos, actualizado) values (:id, :datos, now())
             on conflict (id) do update set datos = excluded.datos, actualizado = now()',
            ['id' => $id, 'datos' => $data]
        );
        return true;
    }

    public function destroy(string $id): bool
    {
        Database::query('delete from sesion where id = :id', ['id' => $id]);
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return Database::query(
            'delete from sesion where actualizado < now() - make_interval(secs => :ttl)',
            ['ttl' => $max_lifetime]
        )->rowCount();
    }
}
