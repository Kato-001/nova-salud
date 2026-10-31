<?php
/**
 * Datos de la página pública: especialidades, médicos con sus horarios y cifras generales.
 * Solo lectura y solo información que se puede mostrar sin iniciar sesión (nada de pacientes).
 */

declare(strict_types=1);

final class Publico
{
    private const DIAS = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];

    /** Especialidades activas con la cantidad de médicos activos que las atienden. */
    public static function especialidades(): array
    {
        return Database::filas(
            "select e.idespecialidad, e.nombre, e.descripcion,
                    count(m.idmedico) filter (where m.estado = 'ACTIVO') as medicos
             from especialidad e
             left join medicoespecialidad me on me.idespecialidad = e.idespecialidad
             left join medico m on m.idmedico = me.idmedico
             where e.estado = 'ACTIVO'
             group by e.idespecialidad, e.nombre, e.descripcion
             order by e.nombre"
        );
    }

    /** Médicos activos con sus especialidades y su horario semanal resumido. */
    public static function medicos(): array
    {
        $medicos = Database::filas(
            "select m.idmedico, m.nombres, m.apellidos, u.nombre as nombre_usuario,
                    string_agg(e.nombre, ', ' order by e.nombre) as especialidades,
                    min(e.idespecialidad) as idespecialidad
             from medico m
             join usuario u on u.idusuario = m.idusuario
             join medicoespecialidad me on me.idmedico = m.idmedico
             join especialidad e on e.idespecialidad = me.idespecialidad and e.estado = 'ACTIVO'
             where m.estado = 'ACTIVO'
             group by m.idmedico, m.nombres, m.apellidos, u.nombre
             order by m.apellidos, m.nombres"
        );
        $horarios = [];
        foreach (Database::filas("select idmedico, diasemana, to_char(horainicio, 'HH24:MI') as ini, to_char(horafin, 'HH24:MI') as fin
                                  from horario where estado = 'ACTIVO' order by idmedico, diasemana, horainicio") as $h) {
            $horarios[$h['idmedico']][] = $h;
        }
        foreach ($medicos as &$m) {
            // "Dr." o "Dra." se toma del nombre de la cuenta del médico
            $m['titulo'] = preg_match('/^(Dra?\.)/u', (string) $m['nombre_usuario'], $t) ? $t[1] : 'Dr(a).';
            $m['horario'] = self::resumirHorario($horarios[$m['idmedico']] ?? []);
        }
        return $medicos;
    }

    /** Cifras generales que se muestran en la portada. */
    public static function cifras(): array
    {
        return Database::fila(
            "select (select count(*) from especialidad where estado = 'ACTIVO') as especialidades,
                    (select count(*) from medico where estado = 'ACTIVO') as medicos,
                    (select count(*) from cita where codigoestado = 'ATENDIDA') as atendidas"
        ) ?? ['especialidades' => 0, 'medicos' => 0, 'atendidas' => 0];
    }

    /**
     * Agrupa los días que tienen el mismo horario: "Lun – Vie · 08:00 a 12:00".
     * @param array<int, array{diasemana: int|string, ini: string, fin: string}> $horas
     * @return string[]
     */
    private static function resumirHorario(array $horas): array
    {
        $porRango = [];
        foreach ($horas as $h) {
            $porRango["{$h['ini']} a {$h['fin']}"][] = (int) $h['diasemana'];
        }
        $lineas = [];
        foreach ($porRango as $rango => $dias) {
            sort($dias);
            // Une los días consecutivos en tramos (1,2,3,5 → Lun – Mié, Vie)
            $tramos = [];
            foreach ($dias as $d) {
                if ($tramos && end($tramos)[1] === $d - 1) {
                    $tramos[array_key_last($tramos)][1] = $d;
                } else {
                    $tramos[] = [$d, $d];
                }
            }
            $texto = implode(', ', array_map(fn($t) => $t[0] === $t[1] ? self::DIAS[$t[0]]
                : self::DIAS[$t[0]] . ($t[1] - $t[0] > 1 ? ' – ' : ', ') . self::DIAS[$t[1]], $tramos));
            $lineas[] = "$texto · $rango";
        }
        return $lineas;
    }
}
