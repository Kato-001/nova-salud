<?php
/**
 * Renderizado de vistas PHP dentro de un layout.
 * Separa la presentación (Frontend/views) de la lógica (Bakend/app).
 * Una vista puede definir $titulo y $scripts para que los use el layout.
 */

declare(strict_types=1);

final class View
{
    /** Variables que la vista comparte con el layout. */
    private const COMPARTIDAS = ['titulo', 'scripts'];

    public static function render(string $vista, array $datos = [], string $layout = 'app'): void
    {
        // Errores y datos del formulario anterior (patrón Post/Redirect/Get)
        $GLOBALS['_errores'] = $_SESSION['_errores'] ?? [];
        $GLOBALS['_old'] = $_SESSION['_old'] ?? [];
        unset($_SESSION['_errores'], $_SESSION['_old']);

        [$contenido, $compartidas] = self::capturar($vista, $datos);
        if ($layout === '') {
            echo $contenido;
            return;
        }
        [$html] = self::capturar("layouts/$layout", $compartidas + $datos + ['contenido' => $contenido]);
        echo $html;
    }

    /** Devuelve el HTML de una vista parcial. */
    public static function parcial(string $vista, array $datos = []): string
    {
        return self::capturar("partials/$vista", $datos)[0];
    }

    /** @return array{0: string, 1: array} HTML y variables compartidas */
    private static function capturar(string $__vista, array $__datos): array
    {
        $__archivo = VIEWS_PATH . "/$__vista.php";
        if (!is_file($__archivo)) {
            throw new RuntimeException("Vista no encontrada: $__vista");
        }
        extract($__datos, EXTR_SKIP);
        ob_start();
        try {
            require $__archivo;
            $html = (string) ob_get_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return [$html, array_intersect_key(get_defined_vars(), array_flip(self::COMPARTIDAS))];
    }
}
