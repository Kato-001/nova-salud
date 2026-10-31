<?php
/**
 * USUARIO · Cuentas de acceso al sistema.
 */

declare(strict_types=1);

final class Usuario
{
    private const COLUMNAS = 'u.idusuario, u.nombre, u.username, u.email, u.estado, u.ultimoacceso, u.idrol, r.nombre as rol';

    public static function buscar(int $id): ?array
    {
        return Database::fila('select ' . self::COLUMNAS . ' from usuario u join rol r on r.idrol = u.idrol where u.idusuario = :id', ['id' => $id]);
    }

    public static function listar(array $f, int $limite, int $offset): array
    {
        [$where, $p] = self::filtros($f);
        return Database::filas(
            'select ' . self::COLUMNAS . ", m.idmedico, pa.idpaciente
             from usuario u join rol r on r.idrol = u.idrol
             left join medico m on m.idusuario = u.idusuario
             left join paciente pa on pa.idusuario = u.idusuario
             $where order by u.nombre limit $limite offset $offset",
            $p
        );
    }

    public static function contar(array $f): int
    {
        [$where, $p] = self::filtros($f);
        return (int) Database::valor("select count(*) from usuario u join rol r on r.idrol = u.idrol $where", $p);
    }

    /** Cantidad de cuentas por estado (para las pestañas del listado). */
    public static function conteoEstados(): array
    {
        $r = Database::fila("select count(*) as todos, count(*) filter (where estado = 'ACTIVO') as activo,
                                    count(*) filter (where estado = 'INACTIVO') as inactivo from usuario");
        return ['' => (int) $r['todos'], 'ACTIVO' => (int) $r['activo'], 'INACTIVO' => (int) $r['inactivo']];
    }

    private static function filtros(array $f): array
    {
        $w = [];
        $p = [];
        if (!empty($f['q'])) {
            $w[] = '(u.nombre ilike :q or u.username ilike :q or u.email ilike :q)';
            $p['q'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['rol'])) {
            $w[] = 'u.idrol = :rol';
            $p['rol'] = (int) $f['rol'];
        }
        if (!empty($f['estado'])) {
            $w[] = 'u.estado = :estado';
            $p['estado'] = $f['estado'];
        }
        return [$w ? 'where ' . implode(' and ', $w) : '', $p];
    }

    /** Crea la cuenta guardando la contraseña con password_hash() (RF01). */
    public static function crear(array $d): int
    {
        return (int) Database::valor(
            'insert into usuario (nombre, username, email, password, idrol)
             values (:nombre, :username, lower(:email), :password, :idrol) returning idusuario',
            [
                'nombre' => $d['nombre'], 'username' => $d['username'], 'email' => $d['email'],
                'password' => password_hash($d['password'], PASSWORD_DEFAULT), 'idrol' => (int) $d['idrol'],
            ]
        );
    }

    public static function actualizar(int $id, array $d): void
    {
        Database::query(
            'update usuario set nombre = :nombre, username = :username, email = lower(:email), idrol = :idrol where idusuario = :id',
            ['nombre' => $d['nombre'], 'username' => $d['username'], 'email' => $d['email'], 'idrol' => (int) $d['idrol'], 'id' => $id]
        );
        if (!empty($d['password'])) {
            self::cambiarPassword($id, $d['password']);
        }
    }

    public static function cambiarPassword(int $id, string $password): void
    {
        Database::query('update usuario set password = :p where idusuario = :id',
            ['p' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]);
    }

    public static function verificarPassword(int $id, string $password): bool
    {
        $hash = Database::valor('select password from usuario where idusuario = :id', ['id' => $id]);
        return is_string($hash) && password_verify($password, $hash);
    }

    public static function cambiarEstado(int $id, string $estado): void
    {
        Database::query('update usuario set estado = :e where idusuario = :id', ['e' => $estado, 'id' => $id]);
        if ($estado === 'INACTIVO') {
            // Cierra sus sesiones abiertas para que el bloqueo sea inmediato
            Database::query("delete from sesion where datos like :patron", ['patron' => '%idusuario|i:' . $id . ';%']);
        }
    }

    public static function existeUsername(string $username, ?int $excepto = null): bool
    {
        return (bool) Database::valor('select 1 from usuario where lower(username) = lower(:u) and idusuario <> :id',
            ['u' => $username, 'id' => $excepto ?? 0]);
    }

    public static function existeEmail(string $email, ?int $excepto = null): bool
    {
        return (bool) Database::valor('select 1 from usuario where lower(email) = lower(:e) and idusuario <> :id',
            ['e' => $email, 'id' => $excepto ?? 0]);
    }

    public static function idRol(string $nombre): int
    {
        return (int) Database::valor('select idrol from rol where nombre = :n', ['n' => $nombre]);
    }
}
