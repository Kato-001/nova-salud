<?php
/**
 * CU14 · Consultar agenda médica (RF11): por médico, fecha y estado.
 */

declare(strict_types=1);

final class AgendaController extends Controlador
{
    public function index(): void
    {
        $u = Auth::requerir('agenda.ver', 'agenda.propia');
        $fecha = entrada('fecha') ?: hoy();
        if (!DateTime::createFromFormat('!Y-m-d', $fecha)) {
            $fecha = hoy();
        }

        // Con permiso general se elige el médico; el médico solo ve su agenda
        $todas = Auth::puede('agenda.ver');
        $idmedico = $todas ? entrada_int('medico') : (int) $u['idmedico'];
        $estado = entrada('estado') ?: null;

        $citas = Cita::agenda($fecha, $idmedico ?: null, $estado);
        $porMedico = [];
        foreach ($citas as $c) {
            $porMedico[$c['idmedico']]['nombre'] = $c['medico'];
            $porMedico[$c['idmedico']]['citas'][] = $c;
        }
        // Bloques libres del médico elegido (vista de "agenda completa")
        $bloques = $idmedico ? Disponibilidad::bloques($idmedico, $fecha) : null;

        $this->vista('P4_GestionCitas/agenda/index', [
            'fecha'     => $fecha,
            'idmedico'  => $idmedico,
            'estado'    => $estado,
            'todas'     => $todas,
            'medicos'   => $todas ? Medico::todos() : [],
            'porMedico' => $porMedico,
            'conteo'    => array_count_values(array_column($citas, 'codigoestado')),
            'bloques'   => $bloques,
            'medico'    => $idmedico ? Medico::buscar($idmedico) : null,
        ]);
    }
}
