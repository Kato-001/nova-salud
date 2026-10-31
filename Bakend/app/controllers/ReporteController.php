<?php
/**
 * CU20 · Generar reportes (RF13), con exportación a CSV e impresión a PDF.
 */

declare(strict_types=1);

final class ReporteController extends Controlador
{
    public const TIPOS = [
        'resumen'      => 'Resumen del período',
        'fecha'        => 'Citas por fecha',
        'medico'       => 'Citas por médico',
        'especialidad' => 'Citas por especialidad',
        'estado'       => 'Citas por estado',
        'pacientes'    => 'Pacientes registrados',
        'agenda'       => 'Agenda diaria por médico',
    ];

    public function index(): void
    {
        Auth::requerir('reportes.ver');
        $tipo = array_key_exists(entrada('tipo'), self::TIPOS) ? entrada('tipo') : 'resumen';
        $f = $this->filtros();

        $datos = match ($tipo) {
            'resumen'      => ['resumen' => Reporte::resumen($f), 'porEstado' => Reporte::porEstado($f), 'porDia' => Reporte::porDia($f), 'porEsp' => Reporte::porEspecialidad($f)],
            'fecha'        => ['filas' => Reporte::citasPorFecha($f + ['estado' => entrada('estado')])],
            'medico'       => ['filas' => Reporte::porMedico($f)],
            'especialidad' => ['filas' => Reporte::porEspecialidad($f)],
            'estado'       => ['filas' => Reporte::porEstado($f)],
            'pacientes'    => ['filas' => Reporte::pacientes($f + ['estado_paciente' => entrada('estado_paciente')])],
            'agenda'       => ['agenda' => Reporte::agendaDiaria($f['dia'], $f['medico'] ? (int) $f['medico'] : null)],
        };

        if (entrada('formato') === 'csv') {
            Bitacora::registrar('EXPORTAR', 'REPORTES', null, null, 'Reporte "' . self::TIPOS[$tipo] . '" exportado a CSV');
            $this->csv($tipo, $f, $datos);
        }

        $this->vista('P6_Reportes/index', $datos + [
            'tipo' => $tipo, 'f' => $f, 'tipos' => self::TIPOS,
            'medicos' => Medico::todos(), 'especialidades' => Especialidad::todas(),
        ]);
    }

    private function filtros(): array
    {
        $valida = fn(string $v) => DateTime::createFromFormat('!Y-m-d', $v) ? $v : null;
        $desde = $valida(entrada('desde')) ?? date('Y-m-01');
        $hasta = $valida(entrada('hasta')) ?? date('Y-m-t');
        if ($hasta < $desde) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        // Se limita el rango a un año para no sobrecargar la base
        if ((strtotime($hasta) - strtotime($desde)) / 86400 > 366) {
            $hasta = date('Y-m-d', strtotime("$desde +366 days"));
        }
        return [
            'desde' => $desde, 'hasta' => $hasta, 'dia' => $valida(entrada('dia')) ?? hoy(),
            'medico' => entrada('medico'), 'especialidad' => entrada('especialidad'),
        ];
    }

    /** Exportación a CSV (se abre en Excel). */
    private function csv(string $tipo, array $f, array $datos): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_' . $tipo . '_' . $f['desde'] . '_' . $f['hasta'] . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");   // BOM para que Excel respete las tildes
        $fila = fn(array $c) => fputcsv($out, $c, ';');

        $fila([self::TIPOS[$tipo] . ' · ' . config('NOMBRE_CLINICA', 'NovaSol Salud')]);
        $fila($tipo === 'agenda' ? ['Fecha', $f['dia']] : ['Período', $f['desde'], $f['hasta']]);
        $fila([]);

        switch ($tipo) {
            case 'fecha':
                $fila(['Código', 'Fecha', 'Hora', 'Paciente', 'CI', 'Médico', 'Especialidad', 'Estado', 'Motivo', 'Registrada']);
                foreach ($datos['filas'] as $c) {
                    $fila([$c['codigo'], $c['fecha'], hora($c['horainicio']) . '-' . hora($c['horafin']), $c['paciente'], $c['ci'], $c['medico'], $c['especialidad'], $c['estado'], $c['motivo'], substr($c['fecharegistro'], 0, 16)]);
                }
                $fila(['Total', count($datos['filas'])]);
                break;
            case 'medico':
            case 'especialidad':
                $fila([$tipo === 'medico' ? 'Médico' : 'Especialidad', 'Total', 'Pendientes', 'Atendidas', 'Canceladas', 'Inasistencias']);
                foreach ($datos['filas'] as $r) {
                    $fila([$r['grupo'], $r['total'], $r['pendientes'], $r['atendidas'], $r['canceladas'], $r['inasistencias']]);
                }
                $fila(['Total', array_sum(array_column($datos['filas'], 'total'))]);
                break;
            case 'estado':
                $fila(['Estado', 'Cantidad']);
                foreach ($datos['filas'] as $r) {
                    $fila([$r['grupo'], $r['total']]);
                }
                $fila(['Total', array_sum(array_column($datos['filas'], 'total'))]);
                break;
            case 'pacientes':
                $fila(['Código', 'CI', 'Paciente', 'Teléfono', 'Estado', 'Citas', 'Atendidas', 'Inasistencias']);
                foreach ($datos['filas'] as $r) {
                    $fila([$r['codigo'], $r['ci'], $r['paciente'], $r['telefono'], $r['estado'], $r['citas'], $r['atendidas'], $r['inasistencias']]);
                }
                $fila(['Total pacientes', count($datos['filas'])]);
                break;
            case 'agenda':
                $fila(['Médico', 'Hora', 'Código', 'Paciente', 'Especialidad', 'Estado', 'Motivo']);
                foreach ($datos['agenda'] as $medico => $citas) {
                    foreach ($citas as $c) {
                        $fila([$medico, hora($c['horainicio']), $c['codigo'], $c['paciente'], $c['especialidad'], $c['estado'], $c['motivo']]);
                    }
                }
                break;
            default:
                $r = $datos['resumen'];
                foreach (['total' => 'Total de citas', 'atendidas' => 'Atendidas', 'canceladas' => 'Canceladas', 'inasistencias' => 'Inasistencias', 'pendientes' => 'Pendientes', 'pacientes' => 'Pacientes distintos'] as $k => $l) {
                    $fila([$l, $r[$k] ?? 0]);
                }
        }
        fclose($out);
        exit;
    }
}
