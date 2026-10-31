<?php
/**
 * CU6 · Gestionar horarios de atención y bloqueos de agenda (RF05).
 */

declare(strict_types=1);

final class HorarioController extends Controlador
{
    public function index(): void
    {
        Auth::requerir('horarios.gestionar');
        $medicos = Medico::todos();
        $idmedico = entrada_int('medico') ?? (int) ($medicos[0]['idmedico'] ?? 0);
        $medico = $idmedico ? Medico::buscar($idmedico) : null;
        $this->vista('P2_GestionMedica/horarios/index', [
            'medicos'  => $medicos,
            'medico'   => $medico,
            'horarios' => $medico ? Horario::delMedico($idmedico) : [],
            'bloqueos' => $medico ? Horario::bloqueos($idmedico) : [],
            'duracion' => (int) config('DURACION_CITA_MINUTOS', '30'),
        ]);
    }

    public function guardar(): void
    {
        Auth::requerir('horarios.gestionar');
        $idmedico = $this->id('idmedico');
        Medico::buscar($idmedico) ?? $this->noEncontrado();
        $d = ['diasemana' => entrada('diasemana'), 'horainicio' => entrada('horainicio'), 'horafin' => entrada('horafin')];
        $duracion = (int) config('DURACION_CITA_MINUTOS', '30');

        $v = new Validator($d);
        $v->requerido('diasemana', 'El día')->entero('diasemana', 1, 7)
          ->requerido('horainicio', 'La hora inicial')->hora('horainicio')
          ->requerido('horafin', 'La hora final')->hora('horafin')
          ->si($d['horafin'] !== '' && $d['horafin'] <= $d['horainicio'], 'horafin', 'La hora final debe ser posterior a la inicial.')
          ->si($d['horafin'] > $d['horainicio'] && (strtotime($d['horafin']) - strtotime($d['horainicio'])) / 60 < $duracion, 'horafin', "El horario debe cubrir al menos una cita ($duracion min).");
        if (!$v->falla() && Horario::seCruza($idmedico, (int) $d['diasemana'], $d['horainicio'], $d['horafin'])) {
            $v->si(true, 'horainicio', 'Se cruza con otro horario activo del médico ese día.');
        }
        if ($v->falla()) {
            con_errores($v->errores(), $d);
            flash('error', $v->primerError());
            redirect(url('/horarios', ['medico' => $idmedico]));
        }
        if ($this->intentar(function () use ($idmedico, $d) {
            $id = Horario::crear($idmedico, (int) $d['diasemana'], $d['horainicio'], $d['horafin']);
            Bitacora::registrar('CREAR', 'GESTION_MEDICA', 'horario', $id, DIAS_SEMANA[(int) $d['diasemana']] . " {$d['horainicio']}–{$d['horafin']} (médico #$idmedico)");
        }, $d)) {
            flash('ok', 'Horario agregado: ' . DIAS_SEMANA[(int) $d['diasemana']] . " de {$d['horainicio']} a {$d['horafin']}.");
        }
        redirect(url('/horarios', ['medico' => $idmedico]));
    }

    public function estado(): void
    {
        Auth::requerir('horarios.gestionar');
        $h = Horario::buscar($this->id()) ?? $this->noEncontrado();
        $nuevo = $this->estadoContrario($h['estado']);
        if ($nuevo === 'ACTIVO' && Horario::seCruza((int) $h['idmedico'], (int) $h['diasemana'], $h['horainicio'], $h['horafin'], (int) $h['idhorario'])) {
            flash('error', 'No se puede activar: se cruza con otro horario activo ese día.');
            redirect(url('/horarios', ['medico' => $h['idmedico']]));
        }
        $afectadas = $nuevo === 'INACTIVO' ? Horario::citasAfectadas($h) : 0;
        Horario::cambiarEstado((int) $h['idhorario'], $nuevo);
        Bitacora::registrar($nuevo === 'ACTIVO' ? 'ACTIVAR' : 'DESACTIVAR', 'GESTION_MEDICA', 'horario', (int) $h['idhorario'], DIAS_SEMANA[(int) $h['diasemana']] . ' ' . hora($h['horainicio']) . '–' . hora($h['horafin']));
        flash($afectadas ? 'info' : 'ok', 'Horario ' . ($nuevo === 'ACTIVO' ? 'activado.' : 'desactivado.') . ($afectadas ? " Hay $afectadas cita(s) ya programadas en ese horario: se conservan, revíselas." : ''));
        redirect(url('/horarios', ['medico' => $h['idmedico']]));
    }

    public function eliminar(): void
    {
        Auth::requerir('horarios.gestionar');
        $h = Horario::buscar($this->id()) ?? $this->noEncontrado();
        if (Horario::citasAfectadas($h)) {
            flash('error', 'Este horario tiene citas pendientes. Desactívelo o reprograme las citas antes de eliminarlo.');
            redirect(url('/horarios', ['medico' => $h['idmedico']]));
        }
        Horario::eliminar((int) $h['idhorario']);
        Bitacora::registrar('ELIMINAR', 'GESTION_MEDICA', 'horario', (int) $h['idhorario'], DIAS_SEMANA[(int) $h['diasemana']] . ' ' . hora($h['horainicio']) . '–' . hora($h['horafin']));
        flash('ok', 'Horario eliminado.');
        redirect(url('/horarios', ['medico' => $h['idmedico']]));
    }

    // ------------------------------------------------------------ Bloqueos
    public function bloquear(): void
    {
        Auth::requerir('horarios.gestionar');
        $idmedico = $this->id('idmedico');
        $d = [
            'idmedico' => $idmedico, 'fecha' => entrada('fecha'), 'tipo' => entrada('tipo'), 'motivo' => entrada('motivo'),
            'horainicio' => entrada('dia_completo') ? '' : entrada('horainicio'), 'horafin' => entrada('dia_completo') ? '' : entrada('horafin'),
        ];
        $v = new Validator($d);
        $v->requerido('fecha', 'La fecha')->fecha('fecha', hoy())
          ->requerido('tipo', 'El tipo')->en('tipo', ['FERIADO', 'VACACION', 'PERMISO', 'OTRO'])
          ->max('motivo', 255)->hora('horainicio')->hora('horafin')
          ->si(($d['horainicio'] === '') !== ($d['horafin'] === ''), 'horafin', 'Indique ambas horas o marque "Todo el día".')
          ->si($d['horainicio'] !== '' && $d['horafin'] <= $d['horainicio'], 'horafin', 'La hora final debe ser posterior a la inicial.');
        if ($v->falla()) {
            con_errores($v->errores(), $d);
            flash('error', $v->primerError());
            redirect(url('/horarios', ['medico' => $idmedico]));
        }
        $citas = Horario::citasEnBloqueo($idmedico, $d['fecha'], $d['horainicio'], $d['horafin']);
        $id = Horario::crearBloqueo($d);
        Bitacora::registrar('BLOQUEAR', 'GESTION_MEDICA', 'bloqueoagenda', $id, "Bloqueo {$d['tipo']} el {$d['fecha']} (médico #$idmedico)");
        flash($citas ? 'info' : 'ok', 'Agenda bloqueada el ' . fecha($d['fecha']) . '.' . ($citas ? " Atención: hay $citas cita(s) en ese rango que debe reprogramar." : ''));
        redirect(url('/horarios', ['medico' => $idmedico]));
    }

    public function desbloquear(): void
    {
        Auth::requerir('horarios.gestionar');
        $b = Horario::eliminarBloqueo($this->id()) ?? $this->noEncontrado();
        Bitacora::registrar('LIBERAR', 'GESTION_MEDICA', 'bloqueoagenda', (int) $b['idbloqueo'], "Bloqueo del {$b['fecha']} liberado");
        flash('ok', 'Bloqueo eliminado: la agenda vuelve a estar disponible.');
        redirect(url('/horarios', ['medico' => $b['idmedico']]));
    }
}
