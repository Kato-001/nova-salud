<?php
/**
 * ESTUDIO y CATALOGOESTUDIO · CU18 Gestionar estudios médicos y
 * CU19 Consultar historial de resultados.
 */

declare(strict_types=1);

final class Estudio
{
    public const ESTADOS = [
        'SOLICITADO' => ['Solicitado', 'badge-info'],
        'EN_PROCESO' => ['En proceso', 'badge-accent'],
        'COMPLETADO' => ['Con resultado', 'badge-ok'],
        'CANCELADO'  => ['Cancelado', 'badge-off'],
    ];

    private const SELECT = "select e.*, ce.nombre as estudio, ce.categoria, co.idcita, co.diagnostico, c.codigo as codigo_cita,
            c.idpaciente, p.nombres || ' ' || p.apellidos as paciente, p.ci,
            c.idmedico, m.nombres || ' ' || m.apellidos as medico
        from estudio e
        join catalogoestudio ce on ce.idcatalogo = e.idcatalogo
        join consulta co on co.idconsulta = e.idconsulta
        join cita c on c.idcita = co.idcita
        join paciente p on p.idpaciente = c.idpaciente
        join medico m on m.idmedico = c.idmedico";

    // ------------------------------------------------------------ Catálogo
    public static function catalogo(bool $soloActivos = true): array
    {
        return Database::filas(
            'select ce.*, (select count(*) from estudio e where e.idcatalogo = ce.idcatalogo) as usos
             from catalogoestudio ce ' . ($soloActivos ? "where ce.estado = 'ACTIVO' " : '') . 'order by ce.categoria, ce.nombre'
        );
    }

    public static function buscarCatalogo(int $id): ?array
    {
        return Database::fila('select * from catalogoestudio where idcatalogo = :id', ['id' => $id]);
    }

    public static function existeEnCatalogo(string $nombre, ?int $excepto = null): bool
    {
        return (bool) Database::valor('select 1 from catalogoestudio where normalizar(nombre) = normalizar(:n) and idcatalogo <> :id',
            ['n' => $nombre, 'id' => $excepto ?? 0]);
    }

    public static function guardarCatalogo(?int $id, array $d): int
    {
        $p = ['n' => $d['nombre'], 'c' => $d['categoria'] ?: null, 'd' => $d['descripcion'] ?: null];
        if ($id) {
            Database::query('update catalogoestudio set nombre = :n, categoria = :c, descripcion = :d where idcatalogo = :id', $p + ['id' => $id]);
            return $id;
        }
        return (int) Database::valor('insert into catalogoestudio (nombre, categoria, descripcion) values (:n, :c, :d) returning idcatalogo', $p);
    }

    public static function estadoCatalogo(int $id, string $estado): void
    {
        Database::query('update catalogoestudio set estado = :e where idcatalogo = :id', ['e' => $estado, 'id' => $id]);
    }

    // ----------------------------------------------------------- Estudios
    public static function buscar(int $id): ?array
    {
        return Database::fila(self::SELECT . ' where e.idestudio = :id', ['id' => $id]);
    }

    public static function listar(array $f, int $limite, int $offset): array
    {
        [$where, $p] = self::filtros($f);
        return Database::filas(self::SELECT . " $where order by (e.estado in ('SOLICITADO','EN_PROCESO')) desc, e.fechasolicitud desc limit $limite offset $offset", $p);
    }

    public static function contar(array $f): int
    {
        [$where, $p] = self::filtros($f);
        return (int) Database::valor("select count(*) from estudio e
            join catalogoestudio ce on ce.idcatalogo = e.idcatalogo join consulta co on co.idconsulta = e.idconsulta
            join cita c on c.idcita = co.idcita join paciente p on p.idpaciente = c.idpaciente
            join medico m on m.idmedico = c.idmedico $where", $p);
    }

    public static function conteoEstados(array $f): array
    {
        [$where, $p] = self::filtros(array_diff_key($f, ['estado' => 1]));
        $filas = Database::filas("select e.estado, count(*) as total from estudio e
            join catalogoestudio ce on ce.idcatalogo = e.idcatalogo join consulta co on co.idconsulta = e.idconsulta
            join cita c on c.idcita = co.idcita join paciente p on p.idpaciente = c.idpaciente
            join medico m on m.idmedico = c.idmedico $where group by e.estado", $p);
        $r = array_fill_keys(array_keys(self::ESTADOS), 0);
        foreach ($filas as $fila) {
            $r[$fila['estado']] = (int) $fila['total'];
        }
        return $r;
    }

    private static function filtros(array $f): array
    {
        $w = [];
        $p = [];
        if (!empty($f['q'])) {
            $w[] = "(p.ci ilike :q or p.nombres || ' ' || p.apellidos ilike :q or ce.nombre ilike :q)";
            $p['q'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['estado'])) {
            $w[] = 'e.estado = :estado';
            $p['estado'] = $f['estado'];
        }
        if (!empty($f['medico'])) {
            $w[] = 'c.idmedico = :medico';
            $p['medico'] = (int) $f['medico'];
        }
        if (!empty($f['paciente'])) {
            $w[] = 'c.idpaciente = :paciente';
            $p['paciente'] = (int) $f['paciente'];
        }
        return [$w ? 'where ' . implode(' and ', $w) : '', $p];
    }

    public static function deConsulta(int $idconsulta): array
    {
        return Database::filas(
            'select e.*, ce.nombre as estudio, ce.categoria from estudio e
             join catalogoestudio ce on ce.idcatalogo = e.idcatalogo where e.idconsulta = :id order by e.idestudio',
            ['id' => $idconsulta]
        );
    }

    /** CU19: estudios del paciente con su resultado. */
    public static function delPaciente(int $idpaciente): array
    {
        return self::listar(['paciente' => $idpaciente], 200, 0);
    }

    /** solicitar(): el médico lo indica durante la consulta. */
    public static function solicitar(int $idconsulta, int $idcatalogo, ?string $indicaciones): int
    {
        return (int) Database::valor(
            'insert into estudio (idconsulta, idcatalogo, indicaciones) values (:c, :e, :i) returning idestudio',
            ['c' => $idconsulta, 'e' => $idcatalogo, 'i' => $indicaciones ?: null]
        );
    }

    /** registrarResultado() y adjuntarArchivo(): completa el estudio y avisa al paciente. */
    public static function registrarResultado(array $e, string $estado, ?string $resultado, ?string $archivo): void
    {
        Database::transaccion(function () use ($e, $estado, $resultado, $archivo) {
            Database::query(
                "update estudio set estado = :s, resultado = :r, archivoresultado = :a,
                        fecharesultado = case when :s2 = 'COMPLETADO' then now() else fecharesultado end
                 where idestudio = :id",
                ['s' => $estado, 's2' => $estado, 'r' => $resultado ?: null, 'a' => $archivo ?: null, 'id' => (int) $e['idestudio']]
            );
            if ($estado === 'COMPLETADO') {
                Database::query(
                    "insert into notificacion (tipo, canal, mensaje, programadapara, enviadaen, idcita, idestudio)
                     values ('RESULTADO', 'APP', :m, now(), now(), :c, :e)",
                    ['m' => 'Ya está disponible el resultado de su estudio: ' . $e['estudio'], 'c' => (int) $e['idcita'], 'e' => (int) $e['idestudio']]
                );
            }
        });
    }
}
