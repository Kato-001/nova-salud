<?php
/**
 * Consulta de la bitácora de auditoría.
 */

declare(strict_types=1);

final class BitacoraController extends Controlador
{
    public function index(): void
    {
        Auth::requerir('bitacora.ver');
        $f = ['q' => entrada('q'), 'accion' => entrada('accion'), 'desde' => entrada('desde'), 'hasta' => entrada('hasta')];
        $pag = new Paginador(Bitacora::contar($f), 20);
        $this->vista('P1_SeguridadAcceso/bitacora/index', [
            'filas'    => Bitacora::listar($f, $pag->porPagina, $pag->offset),
            'acciones' => Bitacora::acciones(),
            'f'        => $f,
            'pag'      => $pag,
        ]);
    }
}
