<?php
/**
 * ROL y PERMISO · Roles de usuario y los permisos asignados a cada uno.
 */

declare(strict_types=1);

final class Rol
{
    public const DEL_SISTEMA = ['ADMINISTRADOR', 'RECEPCIONISTA', 'MEDICO', 'PACIENTE'];

    /** Permisos que el ADMINISTRADOR nunca pierde (evita quedarse sin acceso). */
    public const PERMISOS_ADMIN_FIJOS = ['usuarios.gestionar', 'roles.gestionar'];

    public static function todos(): array
    {
        return Database::filas(
            'select r.*, (select count(*) from usuario u where u.idrol = r.idrol) as usuarios
             from rol r order by r.idrol'
        );
    }

    public static function buscar(int $id): ?array
    {
        return Database::fila('select * from rol where idrol = :id', ['id' => $id]);
    }

    public static function crear(string $nombre, ?string $descripcion): int
    {
        return (int) Database::valor('insert into rol (nombre, descripcion) values (:n, :d) returning idrol',
            ['n' => $nombre, 'd' => $descripcion]);
    }

    public static function actualizarDescripcion(int $id, ?string $descripcion): void
    {
        Database::query('update rol set descripcion = :d where idrol = :id', ['d' => $descripcion, 'id' => $id]);
    }

    public static function eliminar(int $id): void
    {
        Database::query('delete from rol where idrol = :id', ['id' => $id]);
    }

    public static function permisos(): array
    {
        return Database::filas('select * from permiso order by modulo, idpermiso');
    }

    /** @return array<int, int[]> idrol => [idpermiso, …] */
    public static function matrizPermisos(): array
    {
        $m = [];
        foreach (Database::filas('select idrol, idpermiso from rolpermiso') as $f) {
            $m[(int) $f['idrol']][] = (int) $f['idpermiso'];
        }
        return $m;
    }

    /** asignarPermisos() / quitarPermisos(): reemplaza los permisos del rol. */
    public static function asignarPermisos(int $idrol, array $idsPermiso): void
    {
        Database::transaccion(function () use ($idrol, $idsPermiso) {
            Database::query('delete from rolpermiso where idrol = :r', ['r' => $idrol]);
            foreach (array_unique(array_map('intval', $idsPermiso)) as $idp) {
                Database::query('insert into rolpermiso (idrol, idpermiso) select :r, idpermiso from permiso where idpermiso = :p',
                    ['r' => $idrol, 'p' => $idp]);
            }
        });
    }
}
