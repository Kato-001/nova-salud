<?php
/**
 * RECUPERACION · Códigos de verificación para recuperar la contraseña.
 */

declare(strict_types=1);

final class Recuperacion
{
    public const MINUTOS_VIGENCIA = 15;
    public const MAX_INTENTOS = 5;
    public const MAX_SOLICITUDES = 3;   // por cuenta cada 15 minutos
    public const MAX_POR_IP = 6;        // por IP cada 15 minutos

    /** Cuenta activa con ese correo (sin el hash de la contraseña). */
    public static function usuarioPorCorreo(string $correo): ?array
    {
        return Database::fila(
            "select idusuario, nombre, email from usuario where lower(email) = lower(:e) and estado = 'ACTIVO'",
            ['e' => $correo]
        );
    }

    public static function solicitudesRecientes(int $idusuario): int
    {
        return (int) Database::valor(
            'select count(*) from recuperacion where idusuario = :u and creado > now() - make_interval(mins => :m)',
            ['u' => $idusuario, 'm' => self::MINUTOS_VIGENCIA]
        );
    }

    public static function solicitudesPorIp(string $ip): int
    {
        return (int) Database::valor(
            'select count(*) from recuperacion where ip = :ip and creado > now() - make_interval(mins => :m)',
            ['ip' => $ip, 'm' => self::MINUTOS_VIGENCIA]
        );
    }

    /** Genera un código de 6 dígitos, anula los anteriores y guarda solo su hash. */
    public static function crear(int $idusuario): string
    {
        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Database::transaccion(function () use ($idusuario, $codigo) {
            Database::query('update recuperacion set usado = true where idusuario = :u and not usado', ['u' => $idusuario]);
            Database::query(
                'insert into recuperacion (idusuario, codigohash, expira, ip)
                 values (:u, :h, now() + make_interval(mins => :m), :ip)',
                ['u' => $idusuario, 'h' => password_hash($codigo, PASSWORD_DEFAULT), 'm' => self::MINUTOS_VIGENCIA, 'ip' => ip_cliente()]
            );
        });
        return $codigo;
    }

    /** Código vigente (no usado y no vencido) más reciente de la cuenta. */
    public static function vigente(int $idusuario): ?array
    {
        return Database::fila(
            'select * from recuperacion where idusuario = :u and not usado and expira > now() order by creado desc limit 1',
            ['u' => $idusuario]
        );
    }

    public static function sumarIntento(int $id): void
    {
        Database::query('update recuperacion set intentos = intentos + 1 where idrecuperacion = :id', ['id' => $id]);
    }

    public static function anular(int $id): void
    {
        Database::query('update recuperacion set usado = true where idrecuperacion = :id', ['id' => $id]);
    }

    /** Cambia la contraseña, marca el código como usado y cierra las sesiones abiertas. */
    public static function restablecer(array $r, string $password): void
    {
        Database::transaccion(function () use ($r, $password) {
            Usuario::cambiarPassword((int) $r['idusuario'], $password);
            Database::query('update recuperacion set usado = true where idusuario = :u', ['u' => $r['idusuario']]);
            Database::query('delete from sesion where datos like :p', ['p' => '%idusuario|i:' . (int) $r['idusuario'] . ';%']);
        });
    }

    /** Correo con el código (HTML y texto plano). */
    public static function correo(string $nombre, string $codigo): array
    {
        $min = self::MINUTOS_VIGENCIA;
        $marca = config('NOMBRE_CLINICA', 'Clínica Vida Salud');
        $nombre = e($nombre);
        $html = <<<HTML
<!doctype html><html><body style="margin:0;background:#f4f7fb;font-family:Segoe UI,Arial,sans-serif;color:#0f172a">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fb;padding:32px 12px"><tr><td align="center">
<table width="480" cellpadding="0" cellspacing="0" style="max-width:480px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e3e8f0">
<tr><td style="background:linear-gradient(135deg,#14b8a6,#0ea5e9,#4f46e5);background-color:#0d9488;padding:24px 28px;color:#ffffff">
<div style="font-size:20px;font-weight:800">✚ NovaSol Salud</div><div style="font-size:13px;opacity:.85">{$marca}</div></td></tr>
<tr><td style="padding:28px">
<p style="margin:0 0 12px;font-size:16px">Hola, <strong>{$nombre}</strong>:</p>
<p style="margin:0 0 20px;font-size:15px;color:#475569">Recibimos una solicitud para restablecer tu contraseña. Usa este código de verificación:</p>
<div style="text-align:center;margin:0 0 20px"><span style="display:inline-block;font-family:Consolas,monospace;font-size:34px;font-weight:700;letter-spacing:10px;padding:14px 22px;border-radius:12px;background:#f0fdfa;border:1px solid #99f6e4;color:#0f766e">{$codigo}</span></div>
<p style="margin:0 0 8px;font-size:14px;color:#475569">El código vence en <strong>{$min} minutos</strong> y solo se puede usar una vez.</p>
<p style="margin:0;font-size:13px;color:#94a3b8">Si no solicitaste este cambio, ignora este mensaje: tu contraseña seguirá siendo la misma. Nunca compartas este código.</p>
</td></tr></table></td></tr></table></body></html>
HTML;
        $texto = "Hola, {$nombre}:\n\nTu código para restablecer la contraseña de NovaSol Salud es: {$codigo}\n\n"
            . "Vence en {$min} minutos y solo se puede usar una vez. Si no lo solicitaste, ignora este mensaje.";
        return [$html, $texto];
    }
}
