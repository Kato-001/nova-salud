<?php
/**
 * Mi cuenta: datos de acceso y cambio de contraseña (cambiarPassword()).
 */

declare(strict_types=1);

final class CuentaController extends Controlador
{
    public function index(): void
    {
        $u = Auth::requerir();
        $this->vista('P1_SeguridadAcceso/cuenta/index', ['u' => $u]);
    }

    public function password(): void
    {
        $u = Auth::requerir();
        $d = ['actual' => $_POST['actual'] ?? '', 'password' => $_POST['password'] ?? '', 'password_confirmacion' => $_POST['password_confirmacion'] ?? ''];
        $v = new Validator($d);
        $v->requerido('actual', 'La contraseña actual')
          ->password('password')
          ->coincide('password_confirmacion', 'password', 'Las contraseñas no coinciden.')
          ->si($d['actual'] !== '' && !Usuario::verificarPassword((int) $u['idusuario'], $d['actual']), 'actual', 'La contraseña actual es incorrecta.')
          ->si($d['actual'] !== '' && $d['actual'] === $d['password'], 'password', 'La nueva contraseña debe ser distinta de la actual.');
        if ($v->falla()) {
            con_errores($v->errores());
            flash('error', $v->primerError());
            volver('/cuenta');
        }
        Usuario::cambiarPassword((int) $u['idusuario'], $d['password']);
        Bitacora::registrar('CAMBIAR_PASSWORD', 'SEGURIDAD', 'usuario', (int) $u['idusuario'], 'Cambio de contraseña');
        flash('ok', 'Contraseña actualizada. Úsela en su próximo inicio de sesión.');
        volver('/cuenta');
    }
}
