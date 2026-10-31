<?php
/**
 * P4 · Gestión de citas:
 * CU9 consultar disponibilidad · CU10 reservar ficha · CU11 reprogramar ·
 * CU12 cancelar · CU13 registrar asistencia.
 */

declare(strict_types=1);

final class CitaController extends Controlador
{
    public function index(): void
    {
        Auth::requerir('citas.ver');
        $f = [
            'q' => entrada('q'), 'fecha' => entrada('fecha'), 'desde' => entrada('desde'), 'hasta' => entrada('hasta'),
            'medico' => entrada('medico'), 'especialidad' => entrada('especialidad'), 'estado' => entrada('estado'),
        ];
        $pag = new Paginador(Cita::contar($f), 15);
        $this->vista('P4_GestionCitas/citas/index', [
            'citas'          => Cita::listar($f, $pag->porPagina, $pag->offset),
            'conteo'         => Cita::conteoPorEstado(array_diff_key($f, ['estado' => 1])),
            'medicos'        => Medico::todos(),
            'especialidades' => Especialidad::todas(),
            'f'              => $f,
            'pag'            => $pag,
        ]);
    }

    public function nueva(): void
    {
        Auth::requerir('citas.gestionar');
        // Se puede llegar con el paciente ya elegido (desde su ficha o desde una cita cancelada)
        $paciente = ($id = entrada_int('paciente')) ? Paciente::buscar($id) : null;
        $this->vista('P4_GestionCitas/citas/nueva', [
            'paciente'       => $paciente && $paciente['estado'] === 'ACTIVO' ? $paciente : null,
            'especialidades' => Especialidad::todas(true),
            'preEspecialidad'=> entrada_int('especialidad'),
            'preMedico'      => entrada_int('medico'),
            'duracion'       => (int) config('DURACION_CITA_MINUTOS', '30'),
            'maxDias'        => (int) config('DIAS_MAX_RESERVA', '60'),
        ]);
    }

    public function guardar(): void
    {
        Auth::requerir('citas.gestionar');
        $d = [
            'idpaciente' => entrada('idpaciente'), 'idespecialidad' => entrada('idespecialidad'), 'idmedico' => entrada('idmedico'),
            'fecha' => entrada('fecha'), 'hora' => entrada('hora'), 'modalidad' => entrada('modalidad', 'PRESENCIAL'), 'motivo' => entrada('motivo'),
        ];
        $cita = null;
        if (!$this->intentar(function () use ($d, &$cita) {
            $cita = CitaService::programar($d);
        }, $d)) {
            redirect(url('/citas/nueva', ['paciente' => $d['idpaciente'], 'especialidad' => $d['idespecialidad'], 'medico' => $d['idmedico']]));
        }
        flash('ok', "Cita {$cita['codigo']} programada para el " . fecha($d['fecha']) . " a las {$d['hora']}.");
        redirect(url('/citas/ver', ['id' => $cita['idcita']]));
    }

    public function ver(): void
    {
        $u = Auth::requerir('citas.ver', 'agenda.propia');
        $c = Cita::buscar($this->id()) ?? $this->noEncontrado();
        $this->autorizarCita($u, $c);
        $this->vista('P4_GestionCitas/citas/ver', [
            'c'         => $c,
            'historial' => Cita::historial((int) $c['idcita']),
            'acciones'  => CitaService::accionesPermitidas($c),
            'consulta'  => $c['idconsulta'] ? Consulta::deCita((int) $c['idcita']) : null,
        ]);
    }

    public function reprogramar(): void
    {
        Auth::requerir('citas.gestionar');
        $c = Cita::buscar($this->id()) ?? $this->noEncontrado();
        if (!in_array($c['codigoestado'], ['PROGRAMADA', 'CONFIRMADA'], true)) {
            flash('error', 'Solo se pueden reprogramar citas programadas o confirmadas.');
            redirect(url('/citas/ver', ['id' => $c['idcita']]));
        }
        $this->vista('P4_GestionCitas/citas/reprogramar', ['c' => $c, 'maxDias' => (int) config('DIAS_MAX_RESERVA', '60')]);
    }

    public function guardarReprogramacion(): void
    {
        Auth::requerir('citas.gestionar');
        $id = $this->id('idcita');
        $d = ['fecha' => entrada('fecha'), 'hora' => entrada('hora'), 'motivo' => entrada('motivo')];
        if (!$this->intentar(fn() => CitaService::reprogramar($id, $d['fecha'], $d['hora'], $d['motivo']), $d)) {
            redirect(url('/citas/reprogramar', ['id' => $id]));
        }
        flash('ok', 'Cita reprogramada para el ' . fecha($d['fecha']) . " a las {$d['hora']}.");
        redirect(url('/citas/ver', ['id' => $id]));
    }

    public function cancelar(): void
    {
        Auth::requerir('citas.gestionar');
        $id = $this->id('idcita');
        if ($this->intentar(fn() => CitaService::cancelar($id, entrada('motivo')))) {
            flash('ok', 'Cita cancelada. El horario quedó libre y el registro se conserva en el historial.');
        }
        redirect(url('/citas/ver', ['id' => $id]));
    }

    /** Confirmar · registrar asistencia (atendida) · registrar inasistencia. */
    public function estado(): void
    {
        $u = Auth::requerir('citas.asistencia', 'consultas.registrar');
        $id = $this->id('idcita');
        $nuevo = entrada('estado');
        $c = Cita::buscar($id) ?? $this->noEncontrado();
        $this->autorizarCita($u, $c);
        // El médico solo puede marcar la atención de sus propias citas
        if (!Auth::puede('citas.asistencia') && $nuevo === 'CONFIRMADA') {
            flash('error', 'No tiene permiso para confirmar citas.');
            volver('/agenda');
        }
        $mensajes = ['CONFIRMADA' => 'Cita confirmada.', 'ATENDIDA' => 'Asistencia registrada: cita atendida.', 'INASISTENCIA' => 'Se registró la inasistencia del paciente.'];
        if ($this->intentar(fn() => CitaService::cambiarEstado($id, $nuevo, entrada('observacion') ?: null))) {
            flash('ok', $mensajes[$nuevo] ?? 'Estado actualizado.');
        }
        volver(url('/citas/ver', ['id' => $id]));
    }

    /** Un médico (sin permiso general de citas) solo ve las suyas. */
    private function autorizarCita(array $u, array $c): void
    {
        if (Auth::puede('citas.ver', 'citas.asistencia')) {
            return;
        }
        if (Auth::puede('agenda.propia') && (int) $u['idmedico'] === (int) $c['idmedico']) {
            return;
        }
        http_response_code(403);
        View::render('errors/403');
        exit;
    }
}
