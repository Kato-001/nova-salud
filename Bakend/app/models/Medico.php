<?php
/**
 * MEDICO y MEDICOESPECIALIDAD · Médicos de la clínica (RF03).
 */

declare(strict_types=1);

final class Medico
{
    private const SELECT = "select m.*, m.nombres || ' ' || m.apellidos as nombre_completo, u.username, u.estado as estado_cuenta,
            coalesce((select string_agg(e.nombre, ', ' order by e.nombre) from medicoespecialidad me
                      join especialidad e on e.idespecialidad = me.idespecialidad where me.idmedico = m.idmedico), '') as especialidades
        from medico m join usuario u on u.idusuario = m.idusuario";

    public static function buscar(int $id): ?array
    {
        $m = Database::fila(self::SELECT . ' where m.idmedico = :id', ['id' => $id]);
        if ($m) {
            $m['ids_especialidad'] = array_map('intval', array_column(
                Database::filas('select idespecialidad from medicoespecialidad where idmedico = :id', ['id' => $id]), 'idespecialidad'));
        }
        return $m;
    }

    public static function listar(array $f, int $limite = 50, int $offset = 0): array
    {
        [$where, $p] = self::filtros($f);
        return Database::filas(self::SELECT . " $where order by m.apellidos, m.nombres limit $limite offset $offset", $p);
    }

    public static function contar(array $f): int
    {
        [$where, $p] = self::filtros($f);
        return (int) Database::valor("select count(*) from medico m $where", $p);
    }

    /** Búsqueda por nombre o especialidad (RF03). */
    private static function filtros(array $f): array
    {
        $w = [];
        $p = [];
        if (!empty($f['q'])) {
            $w[] = "(m.nombres || ' ' || m.apellidos ilike :q or m.codigo ilike :q)";
            $p['q'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['especialidad'])) {
            $w[] = 'exists (select 1 from medicoespecialidad me where me.idmedico = m.idmedico and me.idespecialidad = :esp)';
            $p['esp'] = (int) $f['especialidad'];
        }
        if (!empty($f['estado'])) {
            $w[] = 'm.estado = :estado';
            $p['estado'] = $f['estado'];
        }
        return [$w ? 'where ' . implode(' and ', $w) : '', $p];
    }

    /** Médicos activos que atienden una especialidad (para programar citas). */
    public static function porEspecialidad(int $idespecialidad): array
    {
        return Database::filas(
            "select m.idmedico, m.codigo, m.nombres || ' ' || m.apellidos as nombre_completo,
                    coalesce((select string_agg(distinct h.diasemana::text, ',') from horario h
                              where h.idmedico = m.idmedico and h.estado = 'ACTIVO'), '') as dias
             from medico m join medicoespecialidad me on me.idmedico = m.idmedico
             where me.idespecialidad = :e and m.estado = 'ACTIVO' order by m.apellidos",
            ['e' => $idespecialidad]
        );
    }

    /** Días de la semana (1-7) en que el médico atiende. */
    public static function diasAtencion(int $idmedico): array
    {
        return array_map('intval', array_column(Database::filas(
            "select distinct diasemana from horario where idmedico = :m and estado = 'ACTIVO' order by 1", ['m' => $idmedico]), 'diasemana'));
    }

    public static function activos(): array
    {
        return Database::filas("select idmedico, codigo, nombres || ' ' || apellidos as nombre_completo from medico where estado = 'ACTIVO' order by apellidos");
    }

    public static function todos(): array
    {
        return Database::filas("select idmedico, codigo, estado, nombres || ' ' || apellidos as nombre_completo from medico order by apellidos");
    }

    /** Registra el médico junto con su cuenta de usuario (rol MEDICO). */
    public static function crear(array $d, array $especialidades): int
    {
        return Database::transaccion(function () use ($d, $especialidades) {
            $idusuario = Usuario::crear([
                'nombre' => 'Dr(a). ' . $d['nombres'] . ' ' . $d['apellidos'], 'username' => $d['username'],
                'email' => $d['email'], 'password' => $d['password'], 'idrol' => Usuario::idRol('MEDICO'),
            ]);
            $id = (int) Database::valor(
                'insert into medico (codigo, registroprofesional, nombres, apellidos, telefono, email, idusuario)
                 values (:codigo, :registro, :nombres, :apellidos, :telefono, lower(:email), :u) returning idmedico',
                self::params($d) + ['u' => $idusuario]
            );
            self::asignarEspecialidades($id, $especialidades);
            return $id;
        });
    }

    public static function actualizar(int $id, array $d, array $especialidades): void
    {
        Database::transaccion(function () use ($id, $d, $especialidades) {
            Database::query(
                'update medico set codigo = :codigo, registroprofesional = :registro, nombres = :nombres,
                        apellidos = :apellidos, telefono = :telefono, email = lower(:email) where idmedico = :id',
                self::params($d) + ['id' => $id]
            );
            Database::query("update usuario set nombre = :n, email = lower(:e) where idusuario = (select idusuario from medico where idmedico = :id)",
                ['n' => 'Dr(a). ' . $d['nombres'] . ' ' . $d['apellidos'], 'e' => $d['email'], 'id' => $id]);
            self::asignarEspecialidades($id, $especialidades);
        });
    }

    private static function params(array $d): array
    {
        return [
            'codigo' => mb_strtoupper($d['codigo']), 'registro' => mb_strtoupper($d['registroprofesional']),
            'nombres' => $d['nombres'], 'apellidos' => $d['apellidos'],
            'telefono' => $d['telefono'] ?: null, 'email' => $d['email'],
        ];
    }

    private static function asignarEspecialidades(int $id, array $especialidades): void
    {
        // Se quitan las especialidades desmarcadas, salvo las que aún tienen citas pendientes
        Database::query(
            "delete from medicoespecialidad me where me.idmedico = :id and not (me.idespecialidad = any(cast(:ids as int[])))
               and not exists (select 1 from cita c where c.idmedico = me.idmedico and c.idespecialidad = me.idespecialidad
                               and c.codigoestado in ('PROGRAMADA', 'CONFIRMADA'))",
            ['id' => $id, 'ids' => '{' . implode(',', array_map('intval', $especialidades)) . '}']
        );
        foreach ($especialidades as $e) {
            Database::query('insert into medicoespecialidad (idmedico, idespecialidad) values (:m, :e) on conflict do nothing',
                ['m' => $id, 'e' => (int) $e]);
        }
    }

    /** RN11: no se elimina, se activa o desactiva (también su cuenta). */
    public static function cambiarEstado(int $id, string $estado): void
    {
        Database::transaccion(function () use ($id, $estado) {
            Database::query('update medico set estado = :e where idmedico = :id', ['e' => $estado, 'id' => $id]);
            $idusuario = (int) Database::valor('select idusuario from medico where idmedico = :id', ['id' => $id]);
            Usuario::cambiarEstado($idusuario, $estado);
        });
    }

    public static function siguienteCodigo(): string
    {
        $n = (int) Database::valor("select coalesce(max(substring(codigo from '\\d+$')::int), 0) + 1 from medico where codigo ~ '^MED-\\d+$'");
        return sprintf('MED-%03d', $n);
    }

    public static function existe(string $campo, string $valor, ?int $excepto = null): bool
    {
        $columna = $campo === 'codigo' ? 'codigo' : 'registroprofesional';
        return (bool) Database::valor("select 1 from medico where upper($columna) = upper(:v) and idmedico <> :id",
            ['v' => $valor, 'id' => $excepto ?? 0]);
    }
}
