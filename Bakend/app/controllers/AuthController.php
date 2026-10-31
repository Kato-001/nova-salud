<?php
/**
 * CU1 · Iniciar sesión / cerrar sesión · CU8 · Registrarse como paciente.
 */

declare(strict_types=1);

final class AuthController extends Controlador
{
    public function formulario(): void
    {
        if (Auth::usuario()) {
            redirect(Auth::inicio());
        }
        $this->vista('P1_SeguridadAcceso/login', [], 'auth');
    }

    public function ingresar(): void
    {
        $login = entrada('login');
        $password = $_POST['password'] ?? '';

        $v = new Validator(['login' => $login, 'password' => $password]);
        $v->requerido('login', 'El usuario o correo')->max('login', 150)->requerido('password', 'La contraseña')->max('password', 100);
        if ($v->falla()) {
            con_errores($v->errores(), ['login' => $login]);
            redirect('/login');
        }

        try {
            Auth::intentar($login, (string) $password);
        } catch (AppException $e) {
            con_errores([], ['login' => $login]);
            flash('error', $e->getMessage());
            redirect('/login');
        }
        redirect(Auth::destino());
    }

    public function salir(): void
    {
        if (Auth::usuario()) {
            Bitacora::registrar('CERRAR_SESION', 'SEGURIDAD', 'usuario', Auth::id(), 'Cierre de sesión');
        }
        Auth::cerrarSesion();
        flash('ok', 'Sesión cerrada correctamente.');
        redirect('/login');
    }

    // ----------------------------------------------------- CU8: registro
    public function registro(): void
    {
        if (Auth::usuario()) {
            redirect(Auth::inicio());
        }
        $this->vista('P3_GestionPacientes/registro', [], 'auth');
    }

    public function registrar(): void
    {
        $d = $_POST;
        $v = PacienteController::validador($d, null);
        $v->requerido('username', 'El usuario')->regex('username', '/^[A-Za-z0-9_.-]{3,50}$/', 'Solo letras, números, guiones y puntos (3 a 50).')
          ->requerido('email', 'El correo')
          ->password('password')->coincide('password_confirmacion', 'password', 'Las contraseñas no coinciden.')
          ->si(Usuario::existeUsername(entrada('username')), 'username', 'Ese nombre de usuario ya está en uso.')
          ->si(entrada('email') !== '' && Usuario::existeEmail(entrada('email')), 'email', 'Ese correo ya está registrado.')
          ->si(empty($d['acepta']), 'acepta', 'Debe aceptar el uso de sus datos para la atención médica.');
        if ($v->falla()) {
            con_errores($v->errores(), $d);
            flash('error', $v->primerError());
            redirect('/registro');
        }

        $ok = $this->intentar(function () use ($d) {
            $id = Paciente::crear(array_map(fn($x) => is_string($x) ? trim($x) : $x, $d), true);
            Bitacora::registrar('REGISTRO', 'PACIENTES', 'paciente', $id, 'Autoregistro de paciente CI ' . trim($d['ci']), null);
        }, $d);
        if (!$ok) {
            redirect('/registro');
        }
        Auth::intentar(trim($d['username']), $d['password']);
        flash('ok', '¡Bienvenido! Su cuenta fue creada. Ya puede reservar su primera cita.');
        redirect(Auth::destino());
    }
}
