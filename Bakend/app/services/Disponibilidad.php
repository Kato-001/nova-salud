<?php
/**
 * RF05 / RF06 · Cálculo de los horarios disponibles de un médico en una fecha.
 *
 * Divide cada horario de atención del día en bloques de DURACION_CITA_MINUTOS
 * y descarta los bloques pasados, los ocupados por citas vigentes (las
 * canceladas no ocupan horario, RN09) y los bloqueados en la agenda.
 */

declare(strict_types=1);

final class Disponibilidad
{
    /**
     * @return array{ok: bool, motivo: ?string, duracion: int, bloques: list<array{inicio: string, fin: string, libre: bool}>}
     */
    public static function bloques(int $idmedico, string $fecha, ?int $excluirCita = null): array
    {
        $duracion = max(5, (int) config('DURACION_CITA_MINUTOS', '30'));
        $res = fn(?string $motivo, array $bloques = []) => ['ok' => $motivo === null, 'motivo' => $motivo, 'duracion' => $duracion, 'bloques' => $bloques];

        $d = DateTime::createFromFormat('!Y-m-d', $fecha);
        if (!$d || $d->format('Y-m-d') !== $fecha) {
            return $res('La fecha no es válida.');
        }
        if ($fecha < hoy()) {
            return $res('No se pueden reservar citas en fechas pasadas.');                 // RN06
        }
        $maxDias = (int) config('DIAS_MAX_RESERVA', '60');
        if ($fecha > date('Y-m-d', strtotime("+$maxDias days"))) {
            return $res("Solo se puede reservar con hasta $maxDias días de anticipación.");
        }

        $medico = Database::fila('select estado from medico where idmedico = :id', ['id' => $idmedico]);
        if (!$medico) {
            return $res('El médico no existe.');
        }
        if ($medico['estado'] !== 'ACTIVO') {
            return $res('El médico está inactivo y no recibe nuevas citas.');               // RN04
        }

        $horarios = Database::filas(
            "select horainicio, horafin from horario where idmedico = :m and diasemana = :d and estado = 'ACTIVO' order by horainicio",
            ['m' => $idmedico, 'd' => (int) $d->format('N')]
        );
        if (!$horarios) {
            return $res('El médico no atiende los ' . mb_strtolower(DIAS_SEMANA[(int) $d->format('N')]) . '.');
        }

        $bloqueos = Database::filas('select horainicio, horafin, motivo, tipo from bloqueoagenda where idmedico = :m and fecha = :f',
            ['m' => $idmedico, 'f' => $fecha]);
        foreach ($bloqueos as $b) {
            if ($b['horainicio'] === null) {
                return $res('El médico no atiende ese día (' . mb_strtolower($b['tipo']) . ($b['motivo'] ? ': ' . $b['motivo'] : '') . ').');
            }
        }

        // Citas que ocupan horario (todas menos las canceladas)
        $ocupadas = Database::filas(
            "select c.horainicio, c.horafin from cita c join estadocita e on e.codigo = c.codigoestado
             where c.idmedico = :m and c.fecha = :f and e.ocupahorario and c.idcita <> :ex",
            ['m' => $idmedico, 'f' => $fecha, 'ex' => $excluirCita ?? 0]
        );

        $ahora = date('H:i:s');
        $bloques = [];
        foreach ($horarios as $h) {
            $inicio = strtotime("$fecha {$h['horainicio']}");
            $limite = strtotime("$fecha {$h['horafin']}");
            // RN08: bloques exactos de la duración configurada, sin pasar la hora final (RF07)
            for ($t = $inicio; $t + $duracion * 60 <= $limite; $t += $duracion * 60) {
                $ini = date('H:i:s', $t);
                $fin = date('H:i:s', $t + $duracion * 60);
                $libre = !($fecha === hoy() && $ini <= $ahora)
                    && !self::seCruza($ini, $fin, $ocupadas)
                    && !self::seCruza($ini, $fin, $bloqueos);
                $bloques[] = ['inicio' => substr($ini, 0, 5), 'fin' => substr($fin, 0, 5), 'libre' => $libre];
            }
        }
        return $res(null, $bloques);
    }

    /** RN13: verificación en el servidor de que un bloque concreto está libre. */
    public static function estaLibre(int $idmedico, string $fecha, string $hora, ?int $excluirCita = null): bool
    {
        $r = self::bloques($idmedico, $fecha, $excluirCita);
        foreach ($r['bloques'] as $b) {
            if ($b['inicio'] === substr($hora, 0, 5)) {
                return $b['libre'];
            }
        }
        return false;   // la hora no corresponde a ningún bloque del horario (RN05)
    }

    private static function seCruza(string $ini, string $fin, array $rangos): bool
    {
        foreach ($rangos as $r) {
            if ($r['horainicio'] !== null && $ini < $r['horafin'] && $fin > $r['horainicio']) {
                return true;
            }
        }
        return false;
    }
}
