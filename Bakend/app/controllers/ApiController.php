<?php
/**
 * Endpoints JSON que usa JavaScript en los formularios de citas
 * (cargar médicos, horarios disponibles y buscar pacientes).
 * Requieren sesión y permiso igual que las páginas.
 */

declare(strict_types=1);

final class ApiController extends Controlador
{
    public function medicos(): void
    {
        Auth::requerir('citas.gestionar', 'portal.paciente');
        $esp = entrada_int('especialidad');
        json_response(['ok' => true, 'medicos' => $esp ? Medico::porEspecialidad($esp) : []]);
    }

    /** CU9: horarios disponibles de un médico en una fecha. */
    public function disponibilidad(): void
    {
        Auth::requerir('citas.gestionar', 'portal.paciente', 'citas.ver');
        $medico = entrada_int('medico');
        $fecha = entrada('fecha');
        if (!$medico || $fecha === '') {
            json_response(['ok' => false, 'motivo' => 'Seleccione el médico y la fecha.'], 422);
        }
        json_response(Disponibilidad::bloques($medico, $fecha, entrada_int('excluir')));
    }

    public function pacientes(): void
    {
        Auth::requerir('citas.gestionar');
        $q = entrada('q');
        json_response(['ok' => true, 'pacientes' => mb_strlen($q) >= 2 ? Paciente::buscarRapido($q) : []]);
    }
}
