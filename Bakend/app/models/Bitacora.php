<?php
/**
 * BITACORA · Registro de auditoría de las operaciones del sistema.
 */

declare(strict_types=1);

final class Bitacora
{
    /**
     * Registra una acción. Si no se indica $idusuario se usa el de la sesión.
     * Un fallo al auditar nunca debe interrumpir la operación principal.
     */
    public static function registrar(string $accion, string $modulo, ?string $tabla, ?int $idregistro, string $descripcion, int|null|false $idusuario = false): void
    {
        try {
            Database::query(
                'insert into bitacora (accion, modulo, tabla, idregistro, descripcion, ip, idusuario)
                 values (:a, :m, :t, :id, :d, :ip, :u)',
                [
                    'a' => $accion, 'm' => $modulo, 't' => $tabla, 'id' => $idregistro,
                    'd' => mb_substr($descripcion, 0, 1000), 'ip' => ip_cliente(),
                    'u' => $idusuario === false ? Auth::id() : $idusuario,
                ]
            );
        } catch (Throwable $e) {
            error_log('[NovaSol Salud] No se pudo registrar la bitácora: ' . $e->getMessage());
        }
    }

    public static function listar(array $f, int $limite, int $offset): array
    {
        [$where, $p] = self::filtros($f);
        return Database::filas(
            "select b.*, u.nombre as usuario, u.username from bitacora b
             left join usuario u on u.idusuario = b.idusuario $where
             order by b.fecha desc limit $limite offset $offset",
            $p
        );
    }

    public static function contar(array $f): int
    {
        [$where, $p] = self::filtros($f);
        return (int) Database::valor("select count(*) from bitacora b left join usuario u on u.idusuario = b.idusuario $where", $p);
    }

    public static function recientes(int $n = 6): array
    {
        return Database::filas(
            "select b.accion, b.descripcion, b.fecha, u.nombre as usuario from bitacora b
             left join usuario u on u.idusuario = b.idusuario
             where b.accion <> 'LOGIN_FALLIDO' order by b.fecha desc limit $n"
        );
    }

    public static function acciones(): array
    {
        return array_column(Database::filas('select distinct accion from bitacora order by accion'), 'accion');
    }

    private static function filtros(array $f): array
    {
        $w = [];
        $p = [];
        if (!empty($f['q'])) {
            $w[] = '(b.descripcion ilike :q or u.nombre ilike :q or u.username ilike :q)';
            $p['q'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['accion'])) {
            $w[] = 'b.accion = :accion';
            $p['accion'] = $f['accion'];
        }
        if (!empty($f['desde'])) {
            $w[] = 'b.fecha >= cast(:desde as date)';
            $p['desde'] = $f['desde'];
        }
        if (!empty($f['hasta'])) {
            $w[] = "b.fecha < cast(:hasta as date) + 1";
            $p['hasta'] = $f['hasta'];
        }
        return [$w ? 'where ' . implode(' and ', $w) : '', $p];
    }
}
