<?php
/**
 * PACIENTE, HISTORIACLINICA y CONTACTOEMERGENCIA · Gestión de pacientes (RF02).
 */

declare(strict_types=1);

final class Paciente
{
    public const SEXOS = ['M' => 'Masculino', 'F' => 'Femenino', 'O' => 'Otro'];
    public const TIPOS_SANGRE = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public static function buscar(int $id): ?array
    {
        return Database::fila(
            "select p.*, p.nombres || ' ' || p.apellidos as nombre_completo, u.username, u.estado as estado_cuenta,
                    h.idhistoria, h.numero as numero_historia, h.antecedentespersonales, h.antecedentesfamiliares,
                    h.alergias, h.enfermedadescronicas, h.medicacionhabitual
             from paciente p
             left join usuario u on u.idusuario = p.idusuario
             left join historiaclinica h on h.idpaciente = p.idpaciente
             where p.idpaciente = :id",
            ['id' => $id]
        );
    }

    public static function porUsuario(int $idusuario): ?array
    {
        $id = Database::valor('select idpaciente from paciente where idusuario = :u', ['u' => $idusuario]);
        return $id ? self::buscar((int) $id) : null;
    }

    public static function listar(array $f, int $limite, int $offset): array
    {
        [$where, $p] = self::filtros($f);
        return Database::filas(
            "select p.*, p.nombres || ' ' || p.apellidos as nombre_completo,
                    (select count(*) from cita c where c.idpaciente = p.idpaciente) as total_citas,
                    (select max(c.fecha) from cita c where c.idpaciente = p.idpaciente and c.codigoestado = 'ATENDIDA') as ultima_atencion
             from paciente p $where order by p.apellidos, p.nombres limit $limite offset $offset",
            $p
        );
    }

    public static function contar(array $f): int
    {
        [$where, $p] = self::filtros($f);
        return (int) Database::valor("select count(*) from paciente p $where", $p);
    }

    /** Búsqueda por CI, nombre o apellido (RF02). */
    private static function filtros(array $f): array
    {
        $w = [];
        $p = [];
        if (!empty($f['q'])) {
            $w[] = "(p.ci ilike :q or p.codigo ilike :q or p.nombres || ' ' || p.apellidos ilike :q or p.apellidos || ' ' || p.nombres ilike :q)";
            $p['q'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['estado'])) {
            $w[] = 'p.estado = :estado';
            $p['estado'] = $f['estado'];
        }
        return [$w ? 'where ' . implode(' and ', $w) : '', $p];
    }

    /** Autocompletado al programar una cita: solo pacientes activos. */
    public static function buscarRapido(string $q): array
    {
        return Database::filas(
            "select idpaciente, codigo, ci, nombres || ' ' || apellidos as nombre_completo, telefono, fechanacimiento
             from paciente where estado = 'ACTIVO'
               and (ci ilike :q or codigo ilike :q or nombres || ' ' || apellidos ilike :q or apellidos || ' ' || nombres ilike :q)
             order by apellidos, nombres limit 8",
            ['q' => '%' . $q . '%']
        );
    }

    public static function existeCi(string $ci, ?int $excepto = null): bool
    {
        return (bool) Database::valor('select 1 from paciente where ci = :ci and idpaciente <> :id', ['ci' => $ci, 'id' => $excepto ?? 0]);
    }

    /**
     * Registra al paciente y su historia clínica. Si trae usuario y contraseña
     * también se le crea la cuenta del portal (rol PACIENTE).
     */
    public static function crear(array $d, bool $conCuenta = false): int
    {
        return Database::transaccion(function () use ($d, $conCuenta) {
            $idusuario = null;
            if ($conCuenta) {
                $idusuario = Usuario::crear([
                    'nombre' => $d['nombres'] . ' ' . $d['apellidos'], 'username' => $d['username'],
                    'email' => $d['email'], 'password' => $d['password'], 'idrol' => Usuario::idRol('PACIENTE'),
                ]);
            }
            $siguiente = (int) Database::valor("select coalesce(max(substring(codigo from '\\d+$')::int), 0) + 1 from paciente where codigo ~ '^PAC-\\d+$'");
            $codigo = sprintf('PAC-%04d', $siguiente);
            $id = (int) Database::valor(
                'insert into paciente (codigo, ci, nombres, apellidos, fechanacimiento, sexo, telefono, email, direccion,
                                       tiposangre, aceptanotificaciones, idusuario)
                 values (:codigo, :ci, :nombres, :apellidos, :nac, :sexo, :tel, :email, :dir, :sangre, :notif, :u) returning idpaciente',
                self::params($d) + ['codigo' => $codigo, 'u' => $idusuario]
            );
            Database::query('insert into historiaclinica (numero, idpaciente) values (:n, :p)', ['n' => 'HC-' . substr($codigo, 4), 'p' => $id]);
            return $id;
        });
    }

    public static function actualizar(int $id, array $d): void
    {
        Database::transaccion(function () use ($id, $d) {
            Database::query(
                'update paciente set ci = :ci, nombres = :nombres, apellidos = :apellidos, fechanacimiento = :nac, sexo = :sexo,
                        telefono = :tel, email = :email, direccion = :dir, tiposangre = :sangre, aceptanotificaciones = :notif
                 where idpaciente = :id',
                self::params($d) + ['id' => $id]
            );
            Database::query("update usuario set nombre = :n where idusuario = (select idusuario from paciente where idpaciente = :id)",
                ['n' => $d['nombres'] . ' ' . $d['apellidos'], 'id' => $id]);
        });
    }

    private static function params(array $d): array
    {
        return [
            'ci' => $d['ci'], 'nombres' => $d['nombres'], 'apellidos' => $d['apellidos'], 'nac' => $d['fechanacimiento'],
            'sexo' => $d['sexo'], 'tel' => $d['telefono'], 'email' => ($d['email'] ?? '') !== '' ? mb_strtolower($d['email']) : null,
            'dir' => ($d['direccion'] ?? '') ?: null, 'sangre' => ($d['tiposangre'] ?? '') ?: null,
            'notif' => !empty($d['aceptanotificaciones']),
        ];
    }

    /** RN11: el paciente se desactiva; su historial de citas se conserva. */
    public static function cambiarEstado(int $id, string $estado): void
    {
        Database::transaccion(function () use ($id, $estado) {
            Database::query('update paciente set estado = :e where idpaciente = :id', ['e' => $estado, 'id' => $id]);
            $idusuario = Database::valor('select idusuario from paciente where idpaciente = :id', ['id' => $id]);
            if ($idusuario) {
                Usuario::cambiarEstado((int) $idusuario, $estado);
            }
        });
    }

    /** Citas vigentes a futuro (se avisan al desactivar). */
    public static function citasPendientes(int $id): int
    {
        return (int) Database::valor("select count(*) from cita where idpaciente = :id and codigoestado in ('PROGRAMADA', 'CONFIRMADA') and fecha >= current_date", ['id' => $id]);
    }

    public static function actualizarHistoria(int $id, array $d): void
    {
        Database::query(
            'update historiaclinica set antecedentespersonales = :ap, antecedentesfamiliares = :af, alergias = :al,
                    enfermedadescronicas = :ec, medicacionhabitual = :mh where idpaciente = :id',
            ['ap' => $d['antecedentespersonales'] ?: null, 'af' => $d['antecedentesfamiliares'] ?: null, 'al' => $d['alergias'] ?: null,
             'ec' => $d['enfermedadescronicas'] ?: null, 'mh' => $d['medicacionhabitual'] ?: null, 'id' => $id]
        );
    }

    // ------------------------------------------------ Contactos de emergencia
    public static function contactos(int $id): array
    {
        return Database::filas('select * from contactoemergencia where idpaciente = :id order by idcontacto', ['id' => $id]);
    }

    public static function agregarContacto(int $id, array $d): void
    {
        Database::query('insert into contactoemergencia (nombre, parentesco, telefono, idpaciente) values (:n, :p, :t, :id)',
            ['n' => $d['nombre'], 'p' => $d['parentesco'], 't' => $d['telefono'], 'id' => $id]);
    }

    public static function eliminarContacto(int $idpaciente, int $idcontacto): void
    {
        Database::query('delete from contactoemergencia where idcontacto = :c and idpaciente = :p', ['c' => $idcontacto, 'p' => $idpaciente]);
    }

    // ---------------------------------------------- Historial clínico (consultas)
    public static function consultas(int $id): array
    {
        $consultas = Database::filas(
            "select co.*, c.codigo, c.fecha, c.horainicio, e.nombre as especialidad, m.nombres || ' ' || m.apellidos as medico
             from consulta co join cita c on c.idcita = co.idcita
             join especialidad e on e.idespecialidad = c.idespecialidad join medico m on m.idmedico = c.idmedico
             where c.idpaciente = :id order by co.fechahora desc",
            ['id' => $id]
        );
        foreach ($consultas as &$c) {
            $c['tratamientos'] = Consulta::tratamientos((int) $c['idconsulta']);
            $c['estudios'] = Estudio::deConsulta((int) $c['idconsulta']);
        }
        return $consultas;
    }
}
