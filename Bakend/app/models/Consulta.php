<?php
/**
 * CONSULTA, TRATAMIENTO y MEDICAMENTO · Atención médica de una cita.
 */

declare(strict_types=1);

final class Consulta
{
    public static function deCita(int $idcita): ?array
    {
        $c = Database::fila('select * from consulta where idcita = :id', ['id' => $idcita]);
        if ($c) {
            $c['tratamientos'] = self::tratamientos((int) $c['idconsulta']);
            $c['estudios'] = Estudio::deConsulta((int) $c['idconsulta']);
        }
        return $c;
    }

    public static function tratamientos(int $idconsulta): array
    {
        return Database::filas(
            'select t.*, m.nombre as medicamento, m.presentacion from tratamiento t
             join medicamento m on m.idmedicamento = t.idmedicamento where t.idconsulta = :id order by t.idtratamiento',
            ['id' => $idconsulta]
        );
    }

    public static function medicamentos(): array
    {
        return Database::filas("select * from medicamento where estado = 'ACTIVO' order by nombre");
    }

    /** Guarda la consulta, sus tratamientos y los estudios solicitados (dentro de la transacción del servicio). */
    public static function crear(int $idcita, array $d, array $tratamientos, array $estudios = []): int
    {
        $num = fn(string $k) => ($d[$k] ?? '') !== '' ? str_replace(',', '.', $d[$k]) : null;
        $id = (int) Database::valor(
            'insert into consulta (idcita, motivo, peso, talla, presionarterial, temperatura, frecuenciacardiaca,
                                   examenfisico, diagnostico, observaciones)
             values (:cita, :motivo, :peso, :talla, :pa, :temp, :fc, :ex, :dx, :obs) returning idconsulta',
            [
                'cita' => $idcita, 'motivo' => $d['motivo'] ?: null, 'peso' => $num('peso'), 'talla' => $num('talla'),
                'pa' => $d['presionarterial'] ?: null, 'temp' => $num('temperatura'),
                'fc' => ($d['frecuenciacardiaca'] ?? '') !== '' ? (int) $d['frecuenciacardiaca'] : null,
                'ex' => $d['examenfisico'] ?: null, 'dx' => $d['diagnostico'], 'obs' => $d['observaciones'] ?: null,
            ]
        );
        foreach ($tratamientos as $t) {
            Database::query(
                'insert into tratamiento (idconsulta, idmedicamento, dosis, frecuencia, duracion, indicaciones)
                 values (:c, :m, :d, :f, :du, :i)',
                ['c' => $id, 'm' => (int) $t['idmedicamento'], 'd' => $t['dosis'], 'f' => $t['frecuencia'],
                 'du' => $t['duracion'], 'i' => ($t['indicaciones'] ?? '') ?: null]
            );
        }
        foreach ($estudios as $e) {
            Estudio::solicitar($id, (int) $e['idcatalogo'], $e['indicaciones'] ?? null);   // CU18 «extend» CU15
        }
        return $id;
    }
}
