<?php
/**
 * CU4 · Gestionar especialidades (RF04).
 */

declare(strict_types=1);

final class EspecialidadController extends Controlador
{
    public function index(): void
    {
        Auth::requerir('especialidades.gestionar');
        $editar = entrada_int('editar');
        $this->vista('P2_GestionMedica/especialidades/index', [
            'especialidades' => Especialidad::todas(),
            'edicion'        => $editar ? Especialidad::buscar($editar) : null,
        ]);
    }

    public function guardar(): void
    {
        Auth::requerir('especialidades.gestionar');
        $id = entrada_int('idespecialidad');
        $nombre = preg_replace('/\s+/', ' ', entrada('nombre'));
        $descripcion = entrada('descripcion');

        $v = new Validator(['nombre' => $nombre, 'descripcion' => $descripcion]);
        $v->requerido('nombre', 'El nombre')->longitud('nombre', 3, 100)
          ->regex('nombre', '/^[\p{L} ]+$/u', 'Solo letras y espacios.')
          ->max('descripcion', 255)
          ->si(Especialidad::existeNombre($nombre, $id), 'nombre', 'Ya existe una especialidad con ese nombre.');   // nombre normalizado
        if ($v->falla()) {
            con_errores($v->errores(), $_POST);
            flash('error', $v->primerError());
            redirect(url('/especialidades', ['editar' => $id]));
        }

        $nombre = mb_convert_case(mb_strtolower($nombre), MB_CASE_TITLE);
        if ($this->intentar(function () use ($id, $nombre, $descripcion) {
            $nuevo = Especialidad::guardar($id, $nombre, $descripcion ?: null);
            Bitacora::registrar($id ? 'EDITAR' : 'CREAR', 'GESTION_MEDICA', 'especialidad', $nuevo, "Especialidad $nombre");
        }, $_POST)) {
            flash('ok', $id ? 'Especialidad actualizada.' : "Especialidad $nombre registrada.");
        }
        redirect('/especialidades');
    }

    public function estado(): void
    {
        Auth::requerir('especialidades.gestionar');
        $e = Especialidad::buscar($this->id()) ?? $this->noEncontrado();
        $nuevo = $this->estadoContrario($e['estado']);
        Especialidad::cambiarEstado((int) $e['idespecialidad'], $nuevo);
        Bitacora::registrar($nuevo === 'ACTIVO' ? 'ACTIVAR' : 'DESACTIVAR', 'GESTION_MEDICA', 'especialidad', (int) $e['idespecialidad'], "Especialidad {$e['nombre']}");
        flash('ok', "Especialidad {$e['nombre']} " . ($nuevo === 'ACTIVO' ? 'activada.' : 'desactivada: ya no aparece al programar citas.'));
        redirect('/especialidades');
    }
}
