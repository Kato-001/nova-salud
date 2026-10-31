<?php
/**
 * Error de negocio con un mensaje apto para mostrar al usuario.
 */

declare(strict_types=1);

final class AppException extends RuntimeException
{
    /** Convierte un error de PostgreSQL en un mensaje claro, sin detalles internos. */
    public static function desdePdo(PDOException $e): self
    {
        $estado = (string) ($e->errorInfo[0] ?? $e->getCode());
        $texto = (string) ($e->errorInfo[2] ?? $e->getMessage());

        // RAISE EXCEPTION de los triggers: el mensaje ya está redactado para el usuario
        if ($estado === 'P0001') {
            return new self((string) preg_replace('/^ERROR:\s*/', '', strtok($texto, "\n")));
        }

        $mensaje = match ($estado) {
            '23P01' => match (true) {
                str_contains($texto, 'cita_sin_cruce_paciente') => 'El paciente ya tiene otra cita en ese horario.',
                str_contains($texto, 'horario_sin_cruce')       => 'El horario se cruza con otro horario activo del médico ese día.',
                default                                         => 'El médico ya tiene una cita en ese horario. Elija otro.',
            },
            '23505' => self::duplicado($texto),
            '23503' => 'No se puede completar: el registro está relacionado con otros datos.',
            '23514' => 'Algún dato no cumple las reglas permitidas. Revise el formulario.',
            '22P02', '22007', '22008', '22003' => 'Algún dato tiene un formato inválido.',
            default => null,
        };

        if ($mensaje === null) {
            error_log('[NovaSol Salud] PDO ' . $estado . ': ' . $texto);
            $mensaje = 'No se pudo completar la operación. Intente nuevamente.';
        }
        return new self($mensaje);
    }

    private static function duplicado(string $texto): string
    {
        return match (true) {
            str_contains($texto, 'paciente_ci')         => 'Ya existe un paciente con ese CI.',
            str_contains($texto, 'usuario_username')    => 'Ese nombre de usuario ya está en uso.',
            str_contains($texto, 'usuario_email')       => 'Ese correo ya está registrado.',
            str_contains($texto, 'especialidad_nombre') => 'Ya existe una especialidad con ese nombre.',
            str_contains($texto, 'medico_codigo')       => 'Ya existe un médico con ese código.',
            str_contains($texto, 'medico_registro')     => 'Ese registro profesional ya está registrado.',
            str_contains($texto, 'rol_nombre')          => 'Ya existe un rol con ese nombre.',
            default                                     => 'Ya existe un registro con esos datos.',
        };
    }
}
