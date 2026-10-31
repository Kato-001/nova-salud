<?php
/**
 * CU18 · Gestionar estudios médicos: bandeja de estudios solicitados,
 * registro de resultados y catálogo de estudios.
 */

declare(strict_types=1);

final class EstudioController extends Controlador
{
    /** El médico (sin permiso de agenda general) solo ve los estudios de sus pacientes. */
    private function soloPropios(array $u): ?int
    {
        return Auth::puede('agenda.propia') && !Auth::puede('agenda.ver') && $u['idmedico'] ? (int) $u['idmedico'] : null;
    }

    public function index(): void
    {
        $u = Auth::requerir('estudios.gestionar');
        $f = ['q' => entrada('q'), 'estado' => entrada('estado'), 'medico' => $this->soloPropios($u)];
        $pag = new Paginador(Estudio::contar($f), 15);
        $this->vista('P5_AtencionMedica/estudios/index', [
            'estudios' => Estudio::listar($f, $pag->porPagina, $pag->offset),
            'conteo'   => Estudio::conteoEstados($f),
            'f'        => $f,
            'pag'      => $pag,
        ]);
    }

    public function resultado(): void
    {
        $u = Auth::requerir('estudios.gestionar');
        $e = $this->estudioPermitido($u, $this->id());
        $this->vista('P5_AtencionMedica/estudios/resultado', ['e' => $e]);
    }

    /** registrarResultado() / adjuntarArchivo() */
    public function guardarResultado(): void
    {
        $u = Auth::requerir('estudios.gestionar');
        $e = $this->estudioPermitido($u, $this->id('idestudio'));
        $d = ['estado' => entrada('estado'), 'resultado' => entrada('resultado'), 'archivoresultado' => entrada('archivoresultado')];

        $v = new Validator($d);
        $v->requerido('estado', 'El estado')->en('estado', array_keys(Estudio::ESTADOS))
          ->si($d['estado'] === 'COMPLETADO' && mb_strlen($d['resultado']) < 3, 'resultado', 'Escriba el resultado del estudio.')
          ->max('resultado', 4000)->max('archivoresultado', 500)
          ->regex('archivoresultado', '#^https?://\S+$#', 'El enlace al archivo debe comenzar con http:// o https://')
          ->si(in_array($e['estado'], ['COMPLETADO', 'CANCELADO'], true), 'estado', 'Este estudio ya está cerrado.');
        if ($v->falla()) {
            con_errores($v->errores(), $d);
            flash('error', $v->primerError());
            redirect(url('/estudios/resultado', ['id' => $e['idestudio']]));
        }
        if ($this->intentar(fn() => Estudio::registrarResultado($e, $d['estado'], $d['resultado'], $d['archivoresultado']), $d)) {
            Bitacora::registrar('RESULTADO', 'ATENCION', 'estudio', (int) $e['idestudio'], "Estudio {$e['estudio']} de {$e['paciente']}: {$d['estado']}");
            flash('ok', $d['estado'] === 'COMPLETADO' ? 'Resultado registrado. Se avisó al paciente.' : 'Estudio actualizado.');
        }
        redirect('/estudios');
    }

    private function estudioPermitido(array $u, int $id): array
    {
        $e = Estudio::buscar($id) ?? $this->noEncontrado();
        $propio = $this->soloPropios($u);
        if ($propio !== null && (int) $e['idmedico'] !== $propio) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
        return $e;
    }

    // ------------------------------------------------------------ Catálogo
    public function catalogo(): void
    {
        Auth::requerir('estudios.catalogo');
        $editar = entrada_int('editar');
        $this->vista('P5_AtencionMedica/estudios/catalogo', [
            'catalogo' => Estudio::catalogo(false),
            'edicion'  => $editar ? Estudio::buscarCatalogo($editar) : null,
        ]);
    }

    public function guardarCatalogo(): void
    {
        Auth::requerir('estudios.catalogo');
        $id = entrada_int('idcatalogo');
        $d = ['nombre' => preg_replace('/\s+/', ' ', entrada('nombre')), 'categoria' => entrada('categoria'), 'descripcion' => entrada('descripcion')];
        $v = new Validator($d);
        $v->requerido('nombre', 'El nombre')->longitud('nombre', 3, 150)->max('categoria', 100)->max('descripcion', 255)
          ->si(Estudio::existeEnCatalogo($d['nombre'], $id), 'nombre', 'Ese estudio ya está en el catálogo.');
        if ($v->falla()) {
            con_errores($v->errores(), $d);
            flash('error', $v->primerError());
            redirect(url('/estudios/catalogo', ['editar' => $id]));
        }
        if ($this->intentar(function () use ($id, $d) {
            $nuevo = Estudio::guardarCatalogo($id, $d);
            Bitacora::registrar($id ? 'EDITAR' : 'CREAR', 'ATENCION', 'catalogoestudio', $nuevo, "Estudio {$d['nombre']}");
        }, $d)) {
            flash('ok', $id ? 'Estudio actualizado.' : "Estudio {$d['nombre']} agregado al catálogo.");
        }
        redirect('/estudios/catalogo');
    }

    public function estadoCatalogo(): void
    {
        Auth::requerir('estudios.catalogo');
        $c = Estudio::buscarCatalogo($this->id()) ?? $this->noEncontrado();
        $nuevo = $this->estadoContrario($c['estado']);
        Estudio::estadoCatalogo((int) $c['idcatalogo'], $nuevo);
        Bitacora::registrar($nuevo === 'ACTIVO' ? 'ACTIVAR' : 'DESACTIVAR', 'ATENCION', 'catalogoestudio', (int) $c['idcatalogo'], "Estudio {$c['nombre']}");
        flash('ok', "{$c['nombre']} " . ($nuevo === 'ACTIVO' ? 'activado.' : 'desactivado: ya no se ofrece al solicitar estudios.'));
        redirect('/estudios/catalogo');
    }
}
