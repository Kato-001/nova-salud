<?php
/**
 * Panel principal: indicadores según el rol del usuario.
 */

declare(strict_types=1);

final class PanelController extends Controlador
{
    public function index(): void
    {
        $u = Auth::requerir('panel.ver');

        // El médico ve su propio día; el personal administrativo, el resumen de la clínica
        if (Auth::puede('agenda.propia') && $u['idmedico']) {
            $hoy = Cita::agenda(hoy(), (int) $u['idmedico']);
            $this->vista('P1_SeguridadAcceso/panel_medico', [
                'hoy'      => $hoy,
                'proximas' => Cita::proximas(['medico' => $u['idmedico']], 6),
                'conteo'   => Cita::conteoPorEstado(['medico' => $u['idmedico'], 'desde' => date('Y-m-01'), 'hasta' => date('Y-m-t')]),
            ]);
            return;
        }

        $stats = Database::fila(
            "select (select count(*) from paciente where estado = 'ACTIVO') as pacientes,
                    (select count(*) from medico where estado = 'ACTIVO') as medicos,
                    (select count(*) from cita where fecha = current_date and codigoestado <> 'CANCELADA') as citas_hoy,
                    (select count(*) from cita where fecha > current_date and codigoestado in ('PROGRAMADA', 'CONFIRMADA')) as pendientes,
                    (select count(*) from especialidad where estado = 'ACTIVO') as especialidades,
                    (select count(*) from usuario where estado = 'ACTIVO') as usuarios"
        );
        $desde = date('Y-m-d', strtotime('-6 days'));
        $this->vista('P1_SeguridadAcceso/panel', [
            'stats'     => $stats,
            'semana'    => Reporte::porDia(['desde' => $desde, 'hasta' => date('Y-m-d', strtotime('+7 days'))]),
            'estados'   => Cita::conteoPorEstado(['desde' => date('Y-m-01'), 'hasta' => date('Y-m-t')]),
            'hoy'       => Cita::agenda(hoy()),
            'actividad' => Auth::puede('bitacora.ver') ? Bitacora::recientes(6) : [],
            'porEsp'    => Reporte::porEspecialidad(['desde' => date('Y-m-01'), 'hasta' => date('Y-m-t')]),
        ]);
    }
}
