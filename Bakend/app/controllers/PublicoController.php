<?php
/**
 * Página pública de la clínica: lo primero que ve cualquier visitante.
 * Muestra especialidades, médicos y cómo reservar; para reservar se pide iniciar sesión.
 */

declare(strict_types=1);

final class PublicoController extends Controlador
{
    public function inicio(): void
    {
        $this->vista('Publico/inicio', [
            'especialidades' => Publico::especialidades(),
            'medicos'        => Publico::medicos(),
            'cifras'         => Publico::cifras(),
        ], 'publico');
    }

    /**
     * Enlace para reservar según quién mira la página: el paciente (o un visitante,
     * que primero inicia sesión) va al portal; la recepción, a programar cita.
     */
    public static function enlaceReserva(?int $especialidad = null, ?int $medico = null): string
    {
        $params = ['especialidad' => $especialidad, 'medico' => $medico];
        if (Auth::usuario() && Auth::inicio() !== '/portal') {
            return Auth::puede('citas.gestionar') ? url('/citas/nueva', $params) : Auth::inicio();
        }
        return url('/portal/reservar', $params);
    }
}
