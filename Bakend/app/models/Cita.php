<?php
/**
 * CITA, ESTADOCITA e HISTORIALCITA · Consultas de citas médicas.
 * Las operaciones que cambian una cita están en services/CitaService.php.
 */

declare(strict_types=1);

final class Cita
{
    private const SELECT = "select c.*, ec.nombre as estado, e.nombre as especialidad,
            p.codigo as codigo_paciente, p.ci, p.nombres || ' ' || p.apellidos as paciente, p.telefono as telefono_paciente,
            p.fechanacimiento, p.sexo, m.codigo as codigo_medico, m.nombres || ' ' || m.apellidos as medico,
            ur.nombre as registrado_por, co.idconsulta
        from cita c
        join estadocita ec on ec.codigo = c.codigoestado
        join especialidad e on e.idespecialidad = c.idespecialidad
        join paciente p on p.idpaciente = c.idpaciente
        join medico m on m.idmedico = c.idmedico
        left join usuario ur on ur.idusuario = c.idusuarioregistro
        left join consulta co on co.idcita = c.idcita";

    public static function buscar(int $id): ?array
    {
        return Database::fila(self::SELECT . ' where c.idcita = :id', ['id' => $id]);
    }

    /** Listado con filtros por fecha o rango, médico, especialidad, estado y paciente. */
    public static function listar(array $f, int $limite = 500, int $offset = 0, string $orden = 'c.fecha desc, c.horainicio'): array
    {
        [$where, $p] = self::filtros($f);
        return Database::filas(self::SELECT . " $where order by $orden limit $limite offset $offset", $p);
    }

    public static function contar(array $f): int
    {
        [$where, $p] = self::filtros($f);
        return (int) Database::valor("select count(*) from cita c join paciente p on p.idpaciente = c.idpaciente
                                      join medico m on m.idmedico = c.idmedico $where", $p);
    }

    public static function filtros(array $f): array
    {
        $w = [];
        $p = [];
        if (!empty($f['fecha'])) {
            $w[] = 'c.fecha = cast(:fecha as date)';
            $p['fecha'] = $f['fecha'];
        }
        if (!empty($f['desde'])) {
            $w[] = 'c.fecha >= cast(:desde as date)';
            $p['desde'] = $f['desde'];
        }
        if (!empty($f['hasta'])) {
            $w[] = 'c.fecha <= cast(:hasta as date)';
            $p['hasta'] = $f['hasta'];
        }
        foreach (['idmedico' => 'medico', 'idespecialidad' => 'especialidad', 'idpaciente' => 'paciente'] as $col => $clave) {
            if (!empty($f[$clave])) {
                $w[] = "c.$col = :$clave";
                $p[$clave] = (int) $f[$clave];
            }
        }
        if (!empty($f['estado'])) {
            $w[] = 'c.codigoestado = :estado';
            $p['estado'] = $f['estado'];
        }
        if (!empty($f['q'])) {
            $w[] = "(c.codigo ilike :q or p.ci ilike :q or p.nombres || ' ' || p.apellidos ilike :q)";
            $p['q'] = '%' . $f['q'] . '%';
        }
        return [$w ? 'where ' . implode(' and ', $w) : '', $p];
    }

    /** RF12: historial de citas de un paciente (incluye pasadas y canceladas). */
    public static function delPaciente(int $idpaciente): array
    {
        return self::listar(['paciente' => $idpaciente], 500, 0, 'c.fecha desc, c.horainicio desc');
    }

    /** RF11: agenda de un médico (o de todos) para una fecha. */
    public static function agenda(string $fecha, ?int $idmedico = null, ?string $estado = null): array
    {
        return self::listar(['fecha' => $fecha, 'medico' => $idmedico, 'estado' => $estado], 500, 0, 'm.apellidos, c.horainicio');
    }

    public static function historial(int $idcita): array
    {
        return Database::filas(
            'select h.*, ec.nombre as estado, u.nombre as usuario from historialcita h
             join estadocita ec on ec.codigo = h.codigoestado left join usuario u on u.idusuario = h.idusuario
             where h.idcita = :id order by h.fecha, h.idhistorial',
            ['id' => $idcita]
        );
    }

    public static function estados(): array
    {
        return Database::filas('select * from estadocita order by array_position(array[\'PROGRAMADA\',\'CONFIRMADA\',\'ATENDIDA\',\'CANCELADA\',\'INASISTENCIA\'], codigo::text)');
    }

    /** Citas vigentes desde hoy (para paneles y portal). */
    public static function proximas(array $f, int $n = 8): array
    {
        $f['desde'] = hoy();
        [$where, $p] = self::filtros($f);
        $where .= ($where ? ' and ' : 'where ') . "c.codigoestado in ('PROGRAMADA', 'CONFIRMADA')";
        return Database::filas(self::SELECT . " $where order by c.fecha, c.horainicio limit $n", $p);
    }

    public static function conteoPorEstado(array $f): array
    {
        [$where, $p] = self::filtros($f);
        $filas = Database::filas("select c.codigoestado, count(*) as total from cita c
                                  join paciente p on p.idpaciente = c.idpaciente join medico m on m.idmedico = c.idmedico
                                  $where group by c.codigoestado", $p);
        $r = array_fill_keys(array_keys(ESTADOS_CITA), 0);
        foreach ($filas as $fila) {
            $r[$fila['codigoestado']] = (int) $fila['total'];
        }
        return $r;
    }
}
