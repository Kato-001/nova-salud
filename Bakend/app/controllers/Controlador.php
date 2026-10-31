<?php
/**
 * Clase base de los controladores: utilidades comunes.
 */

declare(strict_types=1);

abstract class Controlador
{
    protected function vista(string $vista, array $datos = [], string $layout = 'app'): void
    {
        View::render($vista, $datos, $layout);
    }

    /** Id numérico obligatorio de la URL o del formulario; si falta → 404. */
    protected function id(string $clave = 'id'): int
    {
        $id = entrada_int($clave);
        if (!$id || $id < 1) {
            $this->noEncontrado();
        }
        return $id;
    }

    protected function noEncontrado(): never
    {
        http_response_code(404);
        View::render('errors/404');
        exit;
    }

    /**
     * Ejecuta una operación de escritura y traduce los errores conocidos a
     * mensajes para el usuario; los inesperados se registran y se ocultan.
     * @return bool true si la operación terminó bien
     */
    protected function intentar(callable $operacion, array $datosFormulario = []): bool
    {
        try {
            $operacion();
            return true;
        } catch (ValidacionException $e) {
            con_errores($e->errores, $datosFormulario);
            flash('error', $e->getMessage());
        } catch (AppException $e) {
            con_errores([], $datosFormulario);
            flash('error', $e->getMessage());
        } catch (PDOException $e) {
            con_errores([], $datosFormulario);
            flash('error', AppException::desdePdo($e)->getMessage());
        }
        return false;
    }

    /** Estado ACTIVO/INACTIVO recibido de un formulario (valor seguro). */
    protected function estadoContrario(string $actual): string
    {
        return $actual === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
    }
}
