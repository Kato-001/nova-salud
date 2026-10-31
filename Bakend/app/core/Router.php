<?php
/**
 * Enrutador mínimo: asocia método + ruta con un controlador.
 * Todas las peticiones POST verifican el token CSRF antes de ejecutarse.
 */

declare(strict_types=1);

final class Router
{
    private array $rutas = [];

    public function get(string $ruta, array $accion): self
    {
        $this->rutas['GET'][$ruta] = $accion;
        return $this;
    }

    public function post(string $ruta, array $accion): self
    {
        $this->rutas['POST'][$ruta] = $accion;
        return $this;
    }

    public function despachar(string $metodo, string $uri): void
    {
        $ruta = '/' . trim((string) parse_url($uri, PHP_URL_PATH), '/');
        $accion = $this->rutas[$metodo][$ruta] ?? null;

        if ($accion === null) {
            // Existe con otro método → 405; si no existe → 404
            $otro = $metodo === 'GET' ? 'POST' : 'GET';
            http_response_code(isset($this->rutas[$otro][$ruta]) ? 405 : 404);
            View::render('errors/404', [], Auth::usuario() ? 'app' : 'auth');
            return;
        }

        if ($metodo === 'POST') {
            Csrf::verificar();
        }

        [$clase, $metodoControlador] = $accion;
        (new $clase())->$metodoControlador();
    }
}
