<?php
/**
 * HORARIO y BLOQUEOAGENDA · Horarios de atención de cada médico (RF05)
 * y los días u horas bloqueados (feriados, vacaciones, permisos).
 */

declare(strict_types=1);

final class Horario
{
    public static function delMedico(int $idmedico): array
    {
        return Database::filas('select * from horario where idmedico = :m order by diasemana, horainicio', ['m' => $idmedico]);
    }

    public static function buscar(int $id): ?array
    {
        return Database::fila('select * from horario where idhorario = :id', ['id' => $id]);
    }

    public static function crear(int $idmedico, int $dia, string $inicio, string $fin): int
    {
        return (int) Database::valor(
            'insert into horario (idmedico, diasemana, horainicio, horafin) values (:m, :d, :i, :f) returning idhorario',
            ['m' => $idmedico, 'd' => $dia, 'i' => $inicio, 'f' => $fin]
        );
    }

    /** ¿El nuevo rango se cruza con otro horario activo del médico ese día? */
    public static function seCruza(int $idmedico, int $dia, string $inicio, string $fin, ?int $excepto = null): bool
    {
        return (bool) Database::valor(
            "select 1 from horario where idmedico = :m and diasemana = :d and estado = 'ACTIVO' and idhorario <> :id
             and cast(:i as time) < horafin and cast(:f as time) > horainicio",
            ['m' => $idmedico, 'd' => $dia, 'i' => $inicio, 'f' => $fin, 'id' => $excepto ?? 0]
        );
    }

    public static function cambiarEstado(int $id, string $estado): void
    {
        Database::query('update horario set estado = :e where idhorario = :id', ['e' => $estado, 'id' => $id]);
    }

    public static function eliminar(int $id): void
    {
        Database::query('delete from horario where idhorario = :id', ['id' => $id]);
    }

    /** Citas vigentes futuras que quedarían fuera si se quita este horario. */
    public static function citasAfectadas(array $h): int
    {
        return (int) Database::valor(
            "select count(*) from cita where idmedico = :m and codigoestado in ('PROGRAMADA', 'CONFIRMADA')
             and fecha >= current_date and extract(isodow from fecha) = :d
             and horainicio >= :i and horafin <= :f",
            ['m' => $h['idmedico'], 'd' => $h['diasemana'], 'i' => $h['horainicio'], 'f' => $h['horafin']]
        );
    }

    // ------------------------------------------------------------ Bloqueos
    public static function bloqueos(int $idmedico): array
    {
        return Database::filas('select * from bloqueoagenda where idmedico = :m and fecha >= current_date order by fecha, horainicio',
            ['m' => $idmedico]);
    }

    public static function crearBloqueo(array $d): int
    {
        return (int) Database::valor(
            'insert into bloqueoagenda (idmedico, fecha, horainicio, horafin, tipo, motivo)
             values (:m, :f, :i, :h, :t, :mo) returning idbloqueo',
            ['m' => (int) $d['idmedico'], 'f' => $d['fecha'], 'i' => $d['horainicio'] ?: null,
             'h' => $d['horafin'] ?: null, 't' => $d['tipo'], 'mo' => $d['motivo'] ?: null]
        );
    }

    public static function eliminarBloqueo(int $id): ?array
    {
        $b = Database::fila('delete from bloqueoagenda where idbloqueo = :id returning *', ['id' => $id]);
        return $b ?: null;
    }

    /** Citas vigentes que caen dentro de un bloqueo (para avisar antes de crearlo). */
    public static function citasEnBloqueo(int $idmedico, string $fecha, ?string $inicio, ?string $fin): int
    {
        return (int) Database::valor(
            "select count(*) from cita where idmedico = :m and fecha = :f and codigoestado in ('PROGRAMADA', 'CONFIRMADA')
             and (cast(:i as time) is null or (horainicio < cast(:h as time) and horafin > cast(:i as time)))",
            ['m' => $idmedico, 'f' => $fecha, 'i' => $inicio ?: null, 'h' => $fin ?: null]
        );
    }
}
