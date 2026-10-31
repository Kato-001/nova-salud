<?php
/**
 * ESPECIALIDAD · Especialidades médicas que ofrece la clínica (RF04).
 */

declare(strict_types=1);

final class Especialidad
{
    public static function todas(bool $soloActivas = false): array
    {
        return Database::filas(
            'select e.*,
                    (select count(*) from medicoespecialidad me join medico m on m.idmedico = me.idmedico
                     where me.idespecialidad = e.idespecialidad and m.estado = \'ACTIVO\') as medicos,
                    (select count(*) from cita c where c.idespecialidad = e.idespecialidad) as citas
             from especialidad e ' . ($soloActivas ? "where e.estado = 'ACTIVO' " : '') . 'order by e.nombre'
        );
    }

    public static function buscar(int $id): ?array
    {
        return Database::fila('select * from especialidad where idespecialidad = :id', ['id' => $id]);
    }

    /** Comprueba duplicados con el nombre normalizado (sin tildes ni mayúsculas). */
    public static function existeNombre(string $nombre, ?int $excepto = null): bool
    {
        return (bool) Database::valor('select 1 from especialidad where normalizar(nombre) = normalizar(:n) and idespecialidad <> :id',
            ['n' => $nombre, 'id' => $excepto ?? 0]);
    }

    public static function guardar(?int $id, string $nombre, ?string $descripcion): int
    {
        if ($id) {
            Database::query('update especialidad set nombre = :n, descripcion = :d where idespecialidad = :id',
                ['n' => $nombre, 'd' => $descripcion, 'id' => $id]);
            return $id;
        }
        return (int) Database::valor('insert into especialidad (nombre, descripcion) values (:n, :d) returning idespecialidad',
            ['n' => $nombre, 'd' => $descripcion]);
    }

    public static function cambiarEstado(int $id, string $estado): void
    {
        Database::query('update especialidad set estado = :e where idespecialidad = :id', ['e' => $estado, 'id' => $id]);
    }
}
