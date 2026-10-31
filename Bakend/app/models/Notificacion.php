<?php
/**
 * NOTIFICACION · Avisos y recordatorios automáticos para el paciente.
 */

declare(strict_types=1);

final class Notificacion
{
    /** Crea el recordatorio de una cita (se muestra X horas antes, según CONFIGURACION). */
    public static function recordatorio(array $cita): void
    {
        $horas = (int) config('HORAS_RECORDATORIO', '24');
        $inicio = new DateTime($cita['fecha'] . ' ' . $cita['horainicio']);
        $programada = (clone $inicio)->modify("-$horas hours");
        Database::query(
            "insert into notificacion (tipo, canal, mensaje, programadapara, idcita) values ('RECORDATORIO', 'APP', :m, :p, :c)",
            [
                'm' => 'Recuerde su cita ' . $cita['codigo'] . ' del ' . date('d/m/Y', $inicio->getTimestamp()) . ' a las ' . $inicio->format('H:i'),
                'p' => $programada->format('Y-m-d H:i:sP'), 'c' => (int) $cita['idcita'],
            ]
        );
    }

    /** Aviso inmediato (confirmación, reprogramación, cancelación). */
    public static function aviso(int $idcita, string $tipo, string $mensaje): void
    {
        Database::query(
            "insert into notificacion (tipo, canal, mensaje, programadapara, enviadaen, idcita) values (:t, 'APP', :m, now(), now(), :c)",
            ['t' => $tipo, 'm' => $mensaje, 'c' => $idcita]
        );
    }

    /** Los recordatorios pendientes de una cita reprogramada o cancelada se descartan. */
    public static function descartarRecordatorios(int $idcita): void
    {
        Database::query("delete from notificacion where idcita = :c and tipo = 'RECORDATORIO' and leidaen is null", ['c' => $idcita]);
    }

    /** Notificaciones visibles para el paciente (ya llegó su hora programada). */
    public static function delPaciente(int $idpaciente, int $n = 10): array
    {
        return Database::filas(
            "select n.*, c.codigo, c.codigoestado from notificacion n join cita c on c.idcita = n.idcita
             where c.idpaciente = :p and (n.programadapara is null or n.programadapara <= now())
             order by coalesce(n.programadapara, n.enviadaen) desc limit $n",
            ['p' => $idpaciente]
        );
    }

    public static function noLeidas(int $idpaciente): int
    {
        return (int) Database::valor(
            "select count(*) from notificacion n join cita c on c.idcita = n.idcita
             where c.idpaciente = :p and n.leidaen is null and (n.programadapara is null or n.programadapara <= now())",
            ['p' => $idpaciente]
        );
    }

    public static function marcarLeidas(int $idpaciente): void
    {
        Database::query(
            "update notificacion n set leidaen = now(), enviadaen = coalesce(enviadaen, now()) from cita c
             where c.idcita = n.idcita and c.idpaciente = :p and n.leidaen is null and (n.programadapara is null or n.programadapara <= now())",
            ['p' => $idpaciente]
        );
    }
}
