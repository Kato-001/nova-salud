<?php
/**
 * Configuración del sistema (duración de citas, días de reserva, etc.).
 */

declare(strict_types=1);

final class ConfiguracionController extends Controlador
{
    public function index(): void
    {
        Auth::requerir('configuracion.gestionar');
        $this->vista('P1_SeguridadAcceso/configuracion/index', ['parametros' => Configuracion::listar()]);
    }

    public function guardar(): void
    {
        Auth::requerir('configuracion.gestionar');
        $valores = (array) ($_POST['valores'] ?? []);
        $actuales = Configuracion::todas();
        $v = new Validator(array_map(fn($x) => is_string($x) ? $x : '', $valores));

        foreach ($actuales as $clave => $_) {
            $v->requerido($clave, 'El valor')->max($clave, 255);
            if (isset(Configuracion::REGLAS[$clave])) {
                [$min, $max] = Configuracion::REGLAS[$clave];
                $v->entero($clave, $min, $max);
            }
        }
        // La duración debe dividir la hora en bloques exactos
        $dur = (int) ($valores['DURACION_CITA_MINUTOS'] ?? 30);
        $v->si($dur > 0 && 60 % $dur !== 0 && $dur % 60 !== 0, 'DURACION_CITA_MINUTOS', 'Use una duración que divida la hora (10, 15, 20, 30 o 60 minutos).');

        if ($v->falla()) {
            con_errores($v->errores(), $valores);
            flash('error', 'Revise los valores marcados.');
            redirect('/configuracion');
        }

        $cambios = 0;
        foreach ($actuales as $clave => $anterior) {
            $nuevo = trim((string) $valores[$clave]);
            if ($nuevo !== $anterior) {
                Configuracion::actualizar($clave, $nuevo);
                Bitacora::registrar('EDITAR', 'SEGURIDAD', 'configuracion', null, "$clave: $anterior → $nuevo");
                $cambios++;
            }
        }
        flash('ok', $cambios ? "Configuración guardada ($cambios cambio(s))." : 'No había cambios que guardar.');
        redirect('/configuracion');
    }
}
