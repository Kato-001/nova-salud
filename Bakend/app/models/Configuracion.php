<?php
/**
 * CONFIGURACION · Parámetros del sistema (clave / valor).
 */

declare(strict_types=1);

final class Configuracion
{
    /** @return array<string,string> */
    public static function todas(): array
    {
        return array_column(Database::filas('select clave, valor from configuracion'), 'valor', 'clave');
    }

    public static function listar(): array
    {
        return Database::filas('select * from configuracion order by clave');
    }

    public static function actualizar(string $clave, string $valor): void
    {
        Database::query('update configuracion set valor = :v where clave = :c', ['v' => $valor, 'c' => $clave]);
    }

    /** Reglas de validación de cada parámetro conocido. */
    public const REGLAS = [
        'DURACION_CITA_MINUTOS' => [10, 120],
        'DIAS_MAX_RESERVA'      => [1, 365],
        'HORAS_MIN_CANCELACION' => [0, 72],
        'HORAS_RECORDATORIO'    => [1, 168],
    ];
}
