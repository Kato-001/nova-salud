<?php
/**
 * Portal del paciente (perfil 3.4 del enunciado): reservar y seguir sus citas,
 * editar su perfil, ver su historial médico y sus recordatorios.
 */

declare(strict_types=1);

final class PortalController extends Controlador
{
    /** Paciente vinculado a la sesión (o error 403 si la cuenta no tiene ficha). */
    private function paciente(): array
    {
        $u = Auth::requerir('portal.paciente');
        $p = $u['idpaciente'] ? Paciente::buscar((int) $u['idpaciente']) : null;
        if (!$p) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
        return $p;
    }

    public function inicio(): void
    {
        $p = $this->paciente();
        $this->vista('Portal/inicio', [
            'p'              => $p,
            'proximas'       => Cita::proximas(['paciente' => $p['idpaciente']], 5),
            'notificaciones' => Notificacion::delPaciente((int) $p['idpaciente'], 6),
            'noLeidas'       => Notificacion::noLeidas((int) $p['idpaciente']),
            'conteo'         => Cita::conteoPorEstado(['paciente' => $p['idpaciente']]),
        ]);
    }

    public function reservar(): void
    {
        $p = $this->paciente();
        $this->vista('Portal/reservar', [
            'p'              => $p,
            'especialidades' => Especialidad::todas(true),
            // Desde la página pública se llega con la especialidad y el médico ya elegidos
            'preEspecialidad' => entrada_int('especialidad'),
            'preMedico'       => entrada_int('medico'),
            'duracion'       => (int) config('DURACION_CITA_MINUTOS', '30'),
            'maxDias'        => (int) config('DIAS_MAX_RESERVA', '60'),
        ]);
    }

    public function guardarReserva(): void
    {
        $p = $this->paciente();
        $d = [
            'idpaciente' => (string) $p['idpaciente'],   // siempre el paciente de la sesión, nunca el del formulario
            'idespecialidad' => entrada('idespecialidad'), 'idmedico' => entrada('idmedico'),
            'fecha' => entrada('fecha'), 'hora' => entrada('hora'), 'modalidad' => entrada('modalidad', 'PRESENCIAL'), 'motivo' => entrada('motivo'),
        ];
        $cita = null;
        if (!$this->intentar(function () use ($d, &$cita) {
            $cita = CitaService::programar($d);
        }, $d)) {
            redirect('/portal/reservar');
        }
        flash('ok', "¡Listo! Su cita {$cita['codigo']} quedó reservada para el " . fecha($d['fecha']) . " a las {$d['hora']}.");
        redirect('/portal/citas');
    }

    public function citas(): void
    {
        $p = $this->paciente();
        $todas = Cita::delPaciente((int) $p['idpaciente']);
        $filtro = entrada('ver', 'proximas');
        $citas = array_values(array_filter($todas, fn($c) => match ($filtro) {
            'proximas'  => in_array($c['codigoestado'], ['PROGRAMADA', 'CONFIRMADA'], true) && $c['fecha'] >= hoy(),
            'atendidas' => $c['codigoestado'] === 'ATENDIDA',
            'canceladas'=> in_array($c['codigoestado'], ['CANCELADA', 'INASISTENCIA'], true),
            default     => true,
        }));
        if ($filtro === 'proximas') {
            usort($citas, fn($a, $b) => [$a['fecha'], $a['horainicio']] <=> [$b['fecha'], $b['horainicio']]);
        }
        $this->vista('Portal/citas', [
            'p' => $p, 'citas' => $citas, 'filtro' => $filtro,
            'horasCancelacion' => (int) config('HORAS_MIN_CANCELACION', '2'),
        ]);
    }

    public function cancelar(): void
    {
        $p = $this->paciente();
        $id = $this->id('idcita');
        $c = Cita::buscar($id);
        if (!$c || (int) $c['idpaciente'] !== (int) $p['idpaciente']) {
            $this->noEncontrado();   // no revela citas de otros pacientes
        }
        if ($this->intentar(fn() => CitaService::cancelar($id, entrada('motivo'), true))) {
            flash('ok', "Cita {$c['codigo']} cancelada.");
        }
        redirect('/portal/citas');
    }

    public function perfil(): void
    {
        $p = $this->paciente();
        $this->vista('Portal/perfil', [
            'p' => $p, 'contactos' => Paciente::contactos((int) $p['idpaciente']), 'seccion' => entrada('seccion', 'personal'),
        ]);
    }

    /** Guarda una sección del perfil: personal, médica, contacto o cuenta. */
    public function guardarPerfil(): void
    {
        $p = $this->paciente();
        $id = (int) $p['idpaciente'];
        $seccion = entrada('seccion');
        $d = array_map(fn($x) => is_string($x) ? trim($x) : $x, $_POST);

        switch ($seccion) {
            case 'personal':
                // El CI identifica al paciente: no lo cambia él mismo
                $d['ci'] = $p['ci'];
                $d['tiposangre'] = $d['tiposangre'] ?? $p['tiposangre'];
                $d['aceptanotificaciones'] = !empty($d['aceptanotificaciones']);
                $v = PacienteController::validador($d, $id);
                if ($v->falla()) {
                    con_errores($v->errores(), $d);
                    flash('error', $v->primerError());
                    break;
                }
                Paciente::actualizar($id, $d);
                Bitacora::registrar('EDITAR', 'PORTAL', 'paciente', $id, 'El paciente actualizó sus datos personales');
                flash('ok', 'Sus datos fueron actualizados.');
                break;

            case 'medica':
                $v = (new Validator($d))->en('tiposangre', Paciente::TIPOS_SANGRE, 'Tipo de sangre no válido.')
                    ->max('alergias', 2000)->max('enfermedadescronicas', 2000)->max('medicacionhabitual', 2000)
                    ->max('antecedentespersonales', 2000)->max('antecedentesfamiliares', 2000);
                if ($v->falla()) {
                    flash('error', $v->primerError());
                    break;
                }
                Database::query('update paciente set tiposangre = :t where idpaciente = :id', ['t' => $d['tiposangre'] ?: null, 'id' => $id]);
                Paciente::actualizarHistoria($id, $d);
                Bitacora::registrar('EDITAR', 'PORTAL', 'historiaclinica', $id, 'El paciente actualizó su información médica');
                flash('ok', 'Información médica guardada.');
                break;

            case 'contacto':
                $v = PacienteController::validadorContacto($d);
                if ($v->falla()) {
                    flash('error', $v->primerError());
                    break;
                }
                Paciente::agregarContacto($id, $d);
                flash('ok', 'Contacto de emergencia agregado.');
                break;

            case 'quitar_contacto':
                Paciente::eliminarContacto($id, (int) ($d['idcontacto'] ?? 0));
                flash('ok', 'Contacto eliminado.');
                $seccion = 'contacto';
                break;

            default:
                $this->noEncontrado();
        }
        redirect(url('/portal/perfil', ['seccion' => $seccion]));
    }

    public function historial(): void
    {
        $p = $this->paciente();
        $this->vista('Portal/historial', [
            'p' => $p,
            'consultas' => Paciente::consultas((int) $p['idpaciente']),
            'estudios' => Estudio::delPaciente((int) $p['idpaciente']),   // CU19
        ]);
    }

    public function leerNotificaciones(): void
    {
        $p = $this->paciente();
        Notificacion::marcarLeidas((int) $p['idpaciente']);
        if (es_ajax()) {
            json_response(['ok' => true]);
        }
        redirect('/portal');
    }
}
