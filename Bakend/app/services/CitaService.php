<?php
/**
 * Lógica de negocio de las citas: programar (RF06), evitar cruces (RF07),
 * reprogramar (RF08), cancelar (RF09), asistencia (RF10) y atención.
 *
 * Cada operación:
 *   1. valida los datos en el servidor (RN13),
 *   2. se ejecuta en una transacción que bloquea al médico (SELECT … FOR UPDATE)
 *      para que dos usuarios no tomen el mismo horario a la vez,
 *   3. vuelve a comprobar la disponibilidad dentro de la transacción,
 *   4. deja rastro en HISTORIALCITA y BITACORA.
 * Además, la base de datos tiene restricciones EXCLUDE y triggers como respaldo.
 */

declare(strict_types=1);

final class CitaService
{
    /** RF10: transiciones de estado permitidas (igual que en el trigger validar_cita). */
    public const TRANSICIONES = [
        'PROGRAMADA' => ['CONFIRMADA', 'CANCELADA', 'ATENDIDA', 'INASISTENCIA'],
        'CONFIRMADA' => ['CANCELADA', 'ATENDIDA', 'INASISTENCIA'],
    ];

    public const ACCION_ESTADO = [
        'CONFIRMADA'   => 'CONFIRMAR',
        'ATENDIDA'     => 'ATENDER',
        'INASISTENCIA' => 'INASISTENCIA',
        'CANCELADA'    => 'CANCELAR',
    ];

    /**
     * Programa una cita nueva.
     * @param array $d idpaciente, idespecialidad, idmedico, fecha, hora, modalidad, motivo
     * @return array la cita registrada
     */
    public static function programar(array $d): array
    {
        $v = new Validator($d);
        $v->requerido('idpaciente', 'El paciente')->entero('idpaciente', 1)
          ->requerido('idespecialidad', 'La especialidad')->entero('idespecialidad', 1)
          ->requerido('idmedico', 'El médico')->entero('idmedico', 1)
          ->requerido('fecha', 'La fecha')->fecha('fecha', hoy())
          ->requerido('hora', 'La hora')->hora('hora')
          ->en('modalidad', ['PRESENCIAL', 'VIRTUAL'])
          ->max('motivo', 255);
        if ($v->falla()) {
            throw new ValidacionException($v->errores());
        }

        $idmedico = (int) $d['idmedico'];
        try {
            $cita = Database::transaccion(function () use ($d, $idmedico) {
                self::bloquearMedico($idmedico);

                if (!Database::valor("select 1 from paciente where idpaciente = :id and estado = 'ACTIVO'", ['id' => (int) $d['idpaciente']])) {
                    throw new AppException('El paciente no existe o está inactivo.');
                }
                if (!Database::valor('select 1 from medicoespecialidad where idmedico = :m and idespecialidad = :e',
                    ['m' => $idmedico, 'e' => (int) $d['idespecialidad']])) {
                    throw new AppException('El médico no atiende la especialidad seleccionada.');
                }
                self::exigirDisponible($idmedico, $d['fecha'], $d['hora']);

                $duracion = (int) config('DURACION_CITA_MINUTOS', '30');
                $fin = date('H:i', strtotime($d['hora']) + $duracion * 60);
                $cita = Database::fila(
                    'insert into cita (fecha, horainicio, horafin, modalidad, motivo, idpaciente, idmedico, idespecialidad, idusuarioregistro)
                     values (:f, :hi, :hf, :mo, :mot, :p, :m, :e, :u) returning idcita, codigo, fecha, horainicio',
                    [
                        'f' => $d['fecha'], 'hi' => $d['hora'], 'hf' => $fin, 'mo' => $d['modalidad'] ?: 'PRESENCIAL',
                        'mot' => ($d['motivo'] ?? '') ?: null, 'p' => (int) $d['idpaciente'], 'm' => $idmedico,
                        'e' => (int) $d['idespecialidad'], 'u' => Auth::id(),
                    ]
                );
                self::historial((int) $cita['idcita'], 'PROGRAMAR', 'PROGRAMADA', null, null, $d['fecha'], $d['hora'], null);
                Notificacion::aviso((int) $cita['idcita'], 'CONFIRMACION', "Su cita {$cita['codigo']} quedó programada para el " . date('d/m/Y', strtotime($d['fecha'])) . " a las {$d['hora']}.");
                Notificacion::recordatorio($cita);
                return $cita;
            });
        } catch (PDOException $e) {
            throw AppException::desdePdo($e);   // p. ej. cruce detectado por la restricción EXCLUDE
        }

        Bitacora::registrar('CREAR', 'CITAS', 'cita', (int) $cita['idcita'], "Cita {$cita['codigo']} programada para el {$d['fecha']} {$d['hora']}");
        return $cita;
    }

    /** RF08: cambia la fecha y hora de una cita programada o confirmada. */
    public static function reprogramar(int $idcita, string $fecha, string $hora, string $motivo): array
    {
        $v = new Validator(['fecha' => $fecha, 'hora' => $hora, 'motivo' => $motivo]);
        $v->requerido('fecha', 'La nueva fecha')->fecha('fecha', hoy())->requerido('hora', 'La nueva hora')->hora('hora')
          ->requerido('motivo', 'El motivo')->longitud('motivo', 3, 255);
        if ($v->falla()) {
            throw new ValidacionException($v->errores());
        }

        try {
            $cita = Database::transaccion(function () use ($idcita, $fecha, $hora, $motivo) {
                $c = self::citaParaActualizar($idcita);
                if (!in_array($c['codigoestado'], ['PROGRAMADA', 'CONFIRMADA'], true)) {
                    throw new AppException('Solo se pueden reprogramar citas programadas o confirmadas. Para una cita '
                        . mb_strtolower(ESTADOS_CITA[$c['codigoestado']][0]) . ' registre una cita nueva.');
                }
                if ($c['fecha'] === $fecha && substr($c['horainicio'], 0, 5) === substr($hora, 0, 5)) {
                    throw new AppException('Elija una fecha u hora distinta a la actual.');
                }
                self::bloquearMedico((int) $c['idmedico']);
                self::exigirDisponible((int) $c['idmedico'], $fecha, $hora, $idcita);

                $duracion = (int) config('DURACION_CITA_MINUTOS', '30');
                Database::query(
                    "update cita set fecha = :f, horainicio = :hi, horafin = :hf, codigoestado = 'PROGRAMADA' where idcita = :id",
                    ['f' => $fecha, 'hi' => $hora, 'hf' => date('H:i', strtotime($hora) + $duracion * 60), 'id' => $idcita]
                );
                self::historial($idcita, 'REPROGRAMAR', 'PROGRAMADA', $c['fecha'], $c['horainicio'], $fecha, $hora, $motivo);
                Notificacion::descartarRecordatorios($idcita);
                Notificacion::aviso($idcita, 'REPROGRAMACION', "Su cita {$c['codigo']} fue reprogramada para el " . date('d/m/Y', strtotime($fecha)) . " a las $hora.");
                Notificacion::recordatorio(['idcita' => $idcita, 'codigo' => $c['codigo'], 'fecha' => $fecha, 'horainicio' => $hora]);
                return $c;
            });
        } catch (PDOException $e) {
            throw AppException::desdePdo($e);
        }

        Bitacora::registrar('REPROGRAMAR', 'CITAS', 'cita', $idcita, "Cita {$cita['codigo']}: {$cita['fecha']} " . hora($cita['horainicio']) . " → $fecha $hora");
        return $cita;
    }

    /**
     * RF09: cancela una cita sin borrarla; deja de ocupar el horario (RN09).
     * @param bool $porPaciente aplica la anticipación mínima configurada
     */
    public static function cancelar(int $idcita, string $motivo, bool $porPaciente = false): array
    {
        if (mb_strlen(trim($motivo)) < 3) {
            throw new ValidacionException(['motivo' => 'Indique el motivo de la cancelación.']);
        }
        try {
            $cita = Database::transaccion(function () use ($idcita, $motivo, $porPaciente) {
                $c = self::citaParaActualizar($idcita);
                if (!in_array($c['codigoestado'], ['PROGRAMADA', 'CONFIRMADA'], true)) {
                    throw new AppException('Una cita ' . mb_strtolower(ESTADOS_CITA[$c['codigoestado']][0]) . ' no se puede cancelar.');
                }
                if ($porPaciente) {
                    $horas = (int) config('HORAS_MIN_CANCELACION', '2');
                    if (strtotime("{$c['fecha']} {$c['horainicio']}") - time() < $horas * 3600) {
                        throw new AppException("Solo puede cancelar con al menos $horas horas de anticipación. Comuníquese con la clínica.");
                    }
                }
                Database::query("update cita set codigoestado = 'CANCELADA' where idcita = :id", ['id' => $idcita]);
                self::historial($idcita, 'CANCELAR', 'CANCELADA', null, null, null, null, trim($motivo));
                Notificacion::descartarRecordatorios($idcita);
                Notificacion::aviso($idcita, 'CANCELACION', "Su cita {$c['codigo']} del " . date('d/m/Y', strtotime($c['fecha'])) . ' fue cancelada.');
                return $c;
            });
        } catch (PDOException $e) {
            throw AppException::desdePdo($e);
        }
        Bitacora::registrar('CANCELAR', 'CITAS', 'cita', $idcita, "Cita {$cita['codigo']} cancelada: " . trim($motivo));
        return $cita;
    }

    /** RF10: confirmar, registrar asistencia (atendida) o inasistencia. */
    public static function cambiarEstado(int $idcita, string $nuevo, ?string $observacion = null): array
    {
        if (!in_array($nuevo, ['CONFIRMADA', 'ATENDIDA', 'INASISTENCIA'], true)) {
            throw new AppException('Estado no válido.');
        }
        try {
            $cita = Database::transaccion(function () use ($idcita, $nuevo, $observacion) {
                $c = self::citaParaActualizar($idcita);
                self::validarTransicion($c, $nuevo);
                Database::query('update cita set codigoestado = :e where idcita = :id', ['e' => $nuevo, 'id' => $idcita]);
                self::historial($idcita, self::ACCION_ESTADO[$nuevo], $nuevo, null, null, null, null, $observacion);
                if ($nuevo !== 'CONFIRMADA') {
                    Notificacion::descartarRecordatorios($idcita);
                }
                return $c;
            });
        } catch (PDOException $e) {
            throw AppException::desdePdo($e);
        }
        Bitacora::registrar(self::ACCION_ESTADO[$nuevo], 'CITAS', 'cita', $idcita, "Cita {$cita['codigo']}: {$cita['codigoestado']} → $nuevo");
        return $cita;
    }

    /** El médico registra la consulta; la cita pasa a ATENDIDA en la misma transacción. */
    public static function atender(int $idcita, array $consulta, array $tratamientos, array $estudios = []): void
    {
        try {
            $cita = Database::transaccion(function () use ($idcita, $consulta, $tratamientos, $estudios) {
                $c = self::citaParaActualizar($idcita);
                self::validarTransicion($c, 'ATENDIDA');
                Consulta::crear($idcita, $consulta, $tratamientos, $estudios);
                Database::query("update cita set codigoestado = 'ATENDIDA' where idcita = :id", ['id' => $idcita]);
                self::historial($idcita, 'ATENDER', 'ATENDIDA', null, null, null, null, 'Consulta registrada');
                Notificacion::descartarRecordatorios($idcita);
                return $c;
            });
        } catch (PDOException $e) {
            throw AppException::desdePdo($e);
        }
        Bitacora::registrar('ATENDER', 'ATENCION', 'consulta', $idcita, "Consulta registrada para la cita {$cita['codigo']}");
    }

    /** ¿Qué acciones admite la cita en su estado actual? (para mostrar botones) */
    public static function accionesPermitidas(array $c): array
    {
        $vigente = in_array($c['codigoestado'], ['PROGRAMADA', 'CONFIRMADA'], true);
        $llego = $c['fecha'] <= hoy();
        return [
            'confirmar'    => $c['codigoestado'] === 'PROGRAMADA',
            'reprogramar'  => $vigente,
            'cancelar'     => $vigente,
            'asistencia'   => $vigente && $llego,
            'inasistencia' => $vigente && $llego,
        ];
    }

    // ------------------------------------------------------------- Internos
    private static function validarTransicion(array $c, string $nuevo): void
    {
        if (!in_array($nuevo, self::TRANSICIONES[$c['codigoestado']] ?? [], true)) {
            throw new AppException('No se puede pasar una cita ' . mb_strtolower(ESTADOS_CITA[$c['codigoestado']][0])
                . ' a ' . mb_strtolower(ESTADOS_CITA[$nuevo][0]) . '.');
        }
        if (in_array($nuevo, ['ATENDIDA', 'INASISTENCIA'], true) && $c['fecha'] > hoy()) {
            throw new AppException('La asistencia solo se registra el día de la cita o después.');
        }
    }

    /** Lee la cita bloqueando su fila hasta terminar la transacción. */
    private static function citaParaActualizar(int $idcita): array
    {
        $c = Database::fila('select * from cita where idcita = :id for update', ['id' => $idcita]);
        if (!$c) {
            throw new AppException('La cita no existe.');
        }
        return $c;
    }

    /** Serializa las reservas del mismo médico (evita que dos usuarios tomen el mismo bloque). */
    private static function bloquearMedico(int $idmedico): void
    {
        if (!Database::valor('select idmedico from medico where idmedico = :id for update', ['id' => $idmedico])) {
            throw new AppException('El médico no existe.');
        }
    }

    private static function exigirDisponible(int $idmedico, string $fecha, string $hora, ?int $excluir = null): void
    {
        $r = Disponibilidad::bloques($idmedico, $fecha, $excluir);
        if (!$r['ok']) {
            throw new AppException($r['motivo']);
        }
        if (!Disponibilidad::estaLibre($idmedico, $fecha, $hora, $excluir)) {
            $enHorario = in_array(substr($hora, 0, 5), array_column($r['bloques'], 'inicio'), true);
            throw new AppException($enHorario
                ? 'El horario de las ' . substr($hora, 0, 5) . ' ya no está disponible: el médico tiene otra cita. Elija otro.'
                : 'La hora elegida está fuera del horario de atención del médico.');
        }
    }

    private static function historial(int $idcita, string $accion, string $estado, ?string $fAnt, ?string $hAnt, ?string $fNueva, ?string $hNueva, ?string $motivo): void
    {
        Database::query(
            'insert into historialcita (idcita, accion, codigoestado, fechaanterior, horaanterior, fechanueva, horanueva, motivo, idusuario)
             values (:c, :a, :e, :fa, :ha, :fn, :hn, :m, :u)',
            ['c' => $idcita, 'a' => $accion, 'e' => $estado, 'fa' => $fAnt, 'ha' => $hAnt, 'fn' => $fNueva, 'hn' => $hNueva,
             'm' => $motivo ? mb_substr($motivo, 0, 255) : null, 'u' => Auth::id()]
        );
    }
}
