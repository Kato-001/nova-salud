<?php
/**
 * RF13 · Consultas de los reportes administrativos.
 * Todos aceptan un rango de fechas y devuelven totales.
 */

declare(strict_types=1);

final class Reporte
{
    /** Citas programadas en el período (detalle). */
    public static function citasPorFecha(array $f): array
    {
        return Cita::listar($f, 2000, 0, 'c.fecha, c.horainicio, m.apellidos');
    }

    /** Cantidad de citas por médico y estado. */
    public static function porMedico(array $f): array
    {
        return self::agrupado("m.codigo || ' · ' || m.nombres || ' ' || m.apellidos", 'join medico m on m.idmedico = c.idmedico', $f);
    }

    public static function porEspecialidad(array $f): array
    {
        return self::agrupado('e.nombre', 'join especialidad e on e.idespecialidad = c.idespecialidad', $f);
    }

    public static function porEstado(array $f): array
    {
        [$where, $p] = self::rango($f);
        return Database::filas(
            "select ec.nombre as grupo, ec.codigo, count(c.idcita) as total
             from estadocita ec left join cita c on c.codigoestado = ec.codigo $where
             group by ec.codigo, ec.nombre order by array_position(array['PROGRAMADA','CONFIRMADA','ATENDIDA','CANCELADA','INASISTENCIA'], ec.codigo::text)",
            $p
        );
    }

    /** Resumen del período: atendidas, canceladas e inasistencias. */
    public static function resumen(array $f): array
    {
        [$where, $p] = self::rango($f, 'where');
        return Database::fila(
            "select count(*) as total,
                    count(*) filter (where codigoestado = 'ATENDIDA') as atendidas,
                    count(*) filter (where codigoestado = 'CANCELADA') as canceladas,
                    count(*) filter (where codigoestado = 'INASISTENCIA') as inasistencias,
                    count(*) filter (where codigoestado in ('PROGRAMADA', 'CONFIRMADA')) as pendientes,
                    count(distinct idpaciente) as pacientes
             from cita c $where",
            $p
        ) ?? [];
    }

    /** Pacientes registrados con su cantidad de citas en el período. */
    public static function pacientes(array $f): array
    {
        $p = ['desde' => $f['desde'], 'hasta' => $f['hasta']];
        $estado = '';
        if (!empty($f['estado_paciente'])) {
            $estado = 'where pa.estado = :ep';
            $p['ep'] = $f['estado_paciente'];
        }
        return Database::filas(
            "select pa.codigo, pa.ci, pa.nombres || ' ' || pa.apellidos as paciente, pa.telefono, pa.estado,
                    count(c.idcita) as citas,
                    count(c.idcita) filter (where c.codigoestado = 'ATENDIDA') as atendidas,
                    count(c.idcita) filter (where c.codigoestado = 'INASISTENCIA') as inasistencias
             from paciente pa
             left join cita c on c.idpaciente = pa.idpaciente and c.fecha between cast(:desde as date) and cast(:hasta as date)
             $estado group by pa.idpaciente order by pa.apellidos, pa.nombres",
            $p
        );
    }

    /** Agenda diaria de cada médico. */
    public static function agendaDiaria(string $fecha, ?int $idmedico): array
    {
        $agenda = [];
        foreach (Cita::agenda($fecha, $idmedico) as $c) {
            $agenda[$c['medico']][] = $c;
        }
        return $agenda;
    }

    /** Citas por día del período (para el gráfico de tendencia). */
    public static function porDia(array $f): array
    {
        return Database::filas(
            "select d::date as dia, count(c.idcita) as total
             from generate_series(cast(:desde as date), cast(:hasta as date), interval '1 day') d
             left join cita c on c.fecha = d::date and c.codigoestado <> 'CANCELADA'
             group by d order by d",
            ['desde' => $f['desde'], 'hasta' => $f['hasta']]
        );
    }

    private static function agrupado(string $grupo, string $join, array $f): array
    {
        [$where, $p] = self::rango($f, 'where');
        return Database::filas(
            "select $grupo as grupo, count(*) as total,
                    count(*) filter (where c.codigoestado in ('PROGRAMADA', 'CONFIRMADA')) as pendientes,
                    count(*) filter (where c.codigoestado = 'ATENDIDA') as atendidas,
                    count(*) filter (where c.codigoestado = 'CANCELADA') as canceladas,
                    count(*) filter (where c.codigoestado = 'INASISTENCIA') as inasistencias
             from cita c $join $where group by 1 order by total desc",
            $p
        );
    }

    /** Condición del rango de fechas (con médico / especialidad opcionales). */
    private static function rango(array $f, string $prefijo = 'and'): array
    {
        $w = ['c.fecha between cast(:desde as date) and cast(:hasta as date)'];
        $p = ['desde' => $f['desde'], 'hasta' => $f['hasta']];
        if (!empty($f['medico'])) {
            $w[] = 'c.idmedico = :medico';
            $p['medico'] = (int) $f['medico'];
        }
        if (!empty($f['especialidad'])) {
            $w[] = 'c.idespecialidad = :especialidad';
            $p['especialidad'] = (int) $f['especialidad'];
        }
        $cond = implode(' and ', $w);
        // En porEstado la condición va en el LEFT JOIN para conservar estados con 0 citas
        return [$prefijo === 'where' ? "where $cond" : "and $cond", $p];
    }
}
