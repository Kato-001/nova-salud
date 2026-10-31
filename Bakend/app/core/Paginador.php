<?php
/**
 * Paginación de listados (LIMIT / OFFSET).
 */

declare(strict_types=1);

final class Paginador
{
    public readonly int $pagina;
    public readonly int $paginas;
    public readonly int $offset;

    public function __construct(public readonly int $total, public readonly int $porPagina = 15)
    {
        $this->paginas = max(1, (int) ceil($total / $porPagina));
        $this->pagina = min($this->paginas, max(1, (int) ($_GET['pagina'] ?? 1)));
        $this->offset = ($this->pagina - 1) * $porPagina;
    }

    /** URL de otra página conservando los filtros actuales. */
    public function url(int $pagina): string
    {
        $q = $_GET;
        $q['pagina'] = $pagina;
        return '?' . http_build_query($q);
    }

    public function desde(): int
    {
        return $this->total ? $this->offset + 1 : 0;
    }

    public function hasta(): int
    {
        return min($this->total, $this->offset + $this->porPagina);
    }
}
