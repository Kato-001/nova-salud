<?php
/**
 * CU3 · Gestionar usuarios (registrar, modificar, activar/desactivar, asignar rol).
 */

declare(strict_types=1);

final class UsuarioController extends Controlador
{
    public function index(): void
    {
        Auth::requerir('usuarios.gestionar');
        $f = ['q' => entrada('q'), 'rol' => entrada('rol'), 'estado' => entrada('estado')];
        $pag = new Paginador(Usuario::contar($f), 10);
        $this->vista('P1_SeguridadAcceso/usuarios/index', [
            'usuarios' => Usuario::listar($f, $pag->porPagina, $pag->offset),
            'roles'    => Rol::todos(),
            'conteo'   => Usuario::conteoEstados(),
            'f'        => $f,
            'pag'      => $pag,
        ]);
    }

    public function crear(): void
    {
        Auth::requerir('usuarios.gestionar');
        $this->vista('P1_SeguridadAcceso/usuarios/form', ['usuario' => null, 'roles' => $this->rolesAsignables()]);
    }

    public function editar(): void
    {
        Auth::requerir('usuarios.gestionar');
        $usuario = Usuario::buscar($this->id()) ?? $this->noEncontrado();
        $this->vista('P1_SeguridadAcceso/usuarios/form', ['usuario' => $usuario, 'roles' => $this->rolesAsignables($usuario)]);
    }

    public function guardar(): void
    {
        Auth::requerir('usuarios.gestionar');
        $id = entrada_int('idusuario');
        $actual = $id ? (Usuario::buscar($id) ?? $this->noEncontrado()) : null;
        $d = [
            'nombre' => entrada('nombre'), 'username' => entrada('username'), 'email' => entrada('email'),
            'idrol' => entrada('idrol'), 'password' => $_POST['password'] ?? '', 'password_confirmacion' => $_POST['password_confirmacion'] ?? '',
        ];
        $idsRoles = array_column($this->rolesAsignables($actual), 'idrol');

        $v = new Validator($d);
        $v->requerido('nombre', 'El nombre')->longitud('nombre', 3, 150)
          ->requerido('username', 'El usuario')->regex('username', '/^[A-Za-z0-9_.-]{3,50}$/', 'Solo letras, números, guiones y puntos (3 a 50).')
          ->requerido('email', 'El correo')->email('email')->max('email', 150)
          ->requerido('idrol', 'El rol')->si($d['idrol'] !== '' && !in_array((int) $d['idrol'], array_map('intval', $idsRoles), true), 'idrol', 'Rol no válido.')
          ->password('password', $actual === null)
          ->coincide('password_confirmacion', 'password', 'Las contraseñas no coinciden.')
          ->si(Usuario::existeUsername($d['username'], $id), 'username', 'Ese nombre de usuario ya está en uso.')
          ->si(Usuario::existeEmail($d['email'], $id), 'email', 'Ese correo ya está registrado.')
          ->si($actual && $id === Auth::id() && (int) $d['idrol'] !== (int) $actual['idrol'], 'idrol', 'No puede cambiar su propio rol.');

        if ($v->falla()) {
            con_errores($v->errores(), $d);
            flash('error', $v->primerError());
            redirect($id ? url('/usuarios/editar', ['id' => $id]) : '/usuarios/nuevo');
        }

        $ok = $this->intentar(function () use ($id, $d) {
            if ($id) {
                Usuario::actualizar($id, $d);
                Bitacora::registrar('EDITAR', 'SEGURIDAD', 'usuario', $id, "Usuario {$d['username']} actualizado" . ($d['password'] ? ' (con cambio de contraseña)' : ''));
            } else {
                $nuevo = Usuario::crear($d);
                Bitacora::registrar('CREAR', 'SEGURIDAD', 'usuario', $nuevo, "Usuario {$d['username']} creado");
            }
        }, $d);
        if (!$ok) {
            redirect($id ? url('/usuarios/editar', ['id' => $id]) : '/usuarios/nuevo');
        }
        flash('ok', $id ? 'Usuario actualizado.' : 'Usuario creado correctamente.');
        redirect('/usuarios');
    }

    public function estado(): void
    {
        Auth::requerir('usuarios.gestionar');
        $u = Usuario::buscar($this->id()) ?? $this->noEncontrado();
        if ((int) $u['idusuario'] === Auth::id()) {
            flash('error', 'No puede desactivar su propia cuenta.');
            volver('/usuarios');
        }
        $nuevo = $this->estadoContrario($u['estado']);
        Usuario::cambiarEstado((int) $u['idusuario'], $nuevo);
        Bitacora::registrar($nuevo === 'ACTIVO' ? 'ACTIVAR' : 'DESACTIVAR', 'SEGURIDAD', 'usuario', (int) $u['idusuario'], "Usuario {$u['username']}");
        flash('ok', ($nuevo === 'ACTIVO' ? 'Cuenta activada: ' : 'Cuenta desactivada: ') . $u['nombre']);
        volver('/usuarios');
    }

    /**
     * Desde aquí se crean cuentas del personal. Las de médicos y pacientes se
     * crean en sus módulos para que queden vinculadas a su ficha, y su rol no cambia.
     */
    private function rolesAsignables(?array $usuario = null): array
    {
        $vinculado = $usuario && in_array($usuario['rol'], ['MEDICO', 'PACIENTE'], true);
        return array_values(array_filter(Rol::todos(), fn($r) => $vinculado
            ? $r['nombre'] === $usuario['rol']
            : !in_array($r['nombre'], ['MEDICO', 'PACIENTE'], true)));
    }
}
