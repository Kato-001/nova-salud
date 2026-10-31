<?php
/**
 * CU3 · Roles y permisos (asignarPermisos / quitarPermisos del diagrama).
 */

declare(strict_types=1);

final class RolController extends Controlador
{
    public function index(): void
    {
        Auth::requerir('roles.gestionar');
        $permisos = [];
        foreach (Rol::permisos() as $p) {
            $permisos[$p['modulo']][] = $p;
        }
        $this->vista('P1_SeguridadAcceso/roles/index', [
            'roles'    => Rol::todos(),
            'permisos' => $permisos,
            'matriz'   => Rol::matrizPermisos(),
        ]);
    }

    public function guardar(): void
    {
        Auth::requerir('roles.gestionar');
        $id = entrada_int('idrol');
        $nombre = mb_strtoupper(preg_replace('/\s+/', '_', entrada('nombre')));
        $descripcion = entrada('descripcion');

        if ($id) {
            $rol = Rol::buscar($id) ?? $this->noEncontrado();
            if (mb_strlen($descripcion) > 255) {
                flash('error', 'La descripción admite hasta 255 caracteres.');
                redirect('/roles');
            }
            Rol::actualizarDescripcion($id, $descripcion ?: null);
            Bitacora::registrar('EDITAR', 'SEGURIDAD', 'rol', $id, "Rol {$rol['nombre']}: descripción actualizada");
            flash('ok', 'Descripción del rol actualizada.');
            redirect('/roles');
        }

        $v = new Validator(['nombre' => $nombre, 'descripcion' => $descripcion]);
        $v->requerido('nombre', 'El nombre')->regex('nombre', '/^[A-ZÁÉÍÓÚÑ_]{3,50}$/u', 'Solo letras y guiones bajos (3 a 50).')->max('descripcion', 255);
        if ($v->falla()) {
            con_errores($v->errores(), $_POST);
            redirect('/roles');
        }
        if ($this->intentar(function () use ($nombre, $descripcion) {
            $id = Rol::crear($nombre, $descripcion ?: null);
            Rol::asignarPermisos($id, [(int) Database::valor("select idpermiso from permiso where codigo = 'panel.ver'")]);
            Bitacora::registrar('CREAR', 'SEGURIDAD', 'rol', $id, "Rol $nombre creado");
        }, $_POST)) {
            flash('ok', "Rol $nombre creado. Asígnele sus permisos en la tabla.");
        }
        redirect('/roles');
    }

    public function permisos(): void
    {
        Auth::requerir('roles.gestionar');
        $rol = Rol::buscar($this->id('idrol')) ?? $this->noEncontrado();
        $ids = array_map('intval', (array) ($_POST['permisos'] ?? []));

        // El administrador conserva siempre la gestión de usuarios y roles
        if ($rol['nombre'] === 'ADMINISTRADOR') {
            $fijos = array_column(Database::filas(
                'select idpermiso from permiso where codigo = any(cast(:c as text[]))',
                ['c' => '{' . implode(',', Rol::PERMISOS_ADMIN_FIJOS) . '}']
            ), 'idpermiso');
            $ids = array_merge($ids, array_map('intval', $fijos));
        }
        Rol::asignarPermisos((int) $rol['idrol'], $ids);
        Bitacora::registrar('PERMISOS', 'SEGURIDAD', 'rol', (int) $rol['idrol'], "Permisos del rol {$rol['nombre']} actualizados (" . count(array_unique($ids)) . ')');
        flash('ok', "Permisos del rol {$rol['nombre']} guardados.");
        redirect(url('/roles', ['rol' => $rol['idrol']]));
    }

    public function eliminar(): void
    {
        Auth::requerir('roles.gestionar');
        $rol = Rol::buscar($this->id()) ?? $this->noEncontrado();
        if (in_array($rol['nombre'], Rol::DEL_SISTEMA, true)) {
            flash('error', 'Los roles del sistema no se pueden eliminar.');
            redirect('/roles');
        }
        if ($this->intentar(fn() => Rol::eliminar((int) $rol['idrol']))) {
            Bitacora::registrar('ELIMINAR', 'SEGURIDAD', 'rol', (int) $rol['idrol'], "Rol {$rol['nombre']} eliminado");
            flash('ok', "Rol {$rol['nombre']} eliminado.");
        }
        redirect('/roles');
    }
}
