<?php
/**
 * CU15 · Registrar consulta médica · CU17 · Registrar tratamiento · CU18 · Solicitar estudios.
 * Al guardar la consulta la cita queda ATENDIDA.
 */

declare(strict_types=1);

final class AtencionController extends Controlador
{
    public function formulario(): void
    {
        $u = Auth::requerir('consultas.registrar');
        $c = $this->citaDelMedico($u, $this->id('cita'));
        $this->vista('P5_AtencionMedica/consulta', [
            'c'            => $c,
            'paciente'     => Paciente::buscar((int) $c['idpaciente']),
            'anteriores'   => array_slice(Paciente::consultas((int) $c['idpaciente']), 0, 5),
            'medicamentos' => Consulta::medicamentos(),
            'catalogo'     => Estudio::catalogo(),
        ]);
    }

    public function guardar(): void
    {
        $u = Auth::requerir('consultas.registrar');
        $idcita = $this->id('idcita');
        $c = $this->citaDelMedico($u, $idcita);
        $d = array_map(fn($x) => is_string($x) ? trim($x) : $x, $_POST);

        $v = new Validator($d);
        $v->requerido('diagnostico', 'El diagnóstico')->longitud('diagnostico', 3, 2000)
          ->max('motivo', 255)->max('examenfisico', 2000)->max('observaciones', 2000)
          ->decimal('peso', 0.5, 400)->decimal('talla', 20, 250)->decimal('temperatura', 30, 45)
          ->entero('frecuenciacardiaca', 20, 250)
          ->regex('presionarterial', '/^\d{2,3}\/\d{2,3}$/', 'Use el formato 120/80.');

        // Tratamientos: filas completas del formulario dinámico
        $tratamientos = [];
        $ids = array_map('intval', array_column(Consulta::medicamentos(), 'idmedicamento'));
        foreach ((array) ($d['tratamientos'] ?? []) as $i => $t) {
            $t = array_map(fn($x) => is_string($x) ? trim($x) : '', (array) $t);
            if (($t['idmedicamento'] ?? '') === '' && ($t['dosis'] ?? '') === '') {
                continue;   // fila vacía
            }
            $faltan = !in_array((int) ($t['idmedicamento'] ?? 0), $ids, true) || ($t['dosis'] ?? '') === ''
                || ($t['frecuencia'] ?? '') === '' || ($t['duracion'] ?? '') === '';
            $v->si($faltan, 'tratamientos', 'Complete medicamento, dosis, frecuencia y duración en cada tratamiento (fila ' . ($i + 1) . ').');
            $tratamientos[] = $t;
        }

        // CU18: estudios solicitados (opcionales)
        $estudios = [];
        $idsCatalogo = array_map('intval', array_column(Estudio::catalogo(), 'idcatalogo'));
        foreach ((array) ($d['estudios'] ?? []) as $e) {
            $e = array_map(fn($x) => is_string($x) ? trim($x) : '', (array) $e);
            if (($e['idcatalogo'] ?? '') === '') {
                continue;
            }
            $v->si(!in_array((int) $e['idcatalogo'], $idsCatalogo, true), 'estudios', 'Estudio no válido.')
              ->si(mb_strlen($e['indicaciones'] ?? '') > 500, 'estudios', 'Las indicaciones del estudio admiten hasta 500 caracteres.');
            $estudios[] = $e;
        }

        if ($v->falla()) {
            con_errores($v->errores(), $d);
            flash('error', $v->primerError());
            redirect(url('/atencion', ['cita' => $idcita]));
        }
        if (!$this->intentar(fn() => CitaService::atender($idcita, $d, $tratamientos, $estudios), $d)) {
            redirect(url('/atencion', ['cita' => $idcita]));
        }
        flash('ok', "Consulta registrada. La cita {$c['codigo']} quedó atendida.");
        redirect(url('/citas/ver', ['id' => $idcita]));
    }

    /** La cita debe ser del médico en sesión y estar pendiente de atención. */
    private function citaDelMedico(array $u, int $idcita): array
    {
        $c = Cita::buscar($idcita) ?? $this->noEncontrado();
        if ((int) $c['idmedico'] !== (int) $u['idmedico']) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
        if (!in_array($c['codigoestado'], ['PROGRAMADA', 'CONFIRMADA'], true) || $c['fecha'] > hoy()) {
            flash('error', $c['fecha'] > hoy() ? 'Solo puede atender la cita el día programado.' : 'Esta cita ya no está pendiente de atención.');
            redirect(url('/citas/ver', ['id' => $idcita]));
        }
        return $c;
    }
}
