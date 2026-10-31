<?php
/**
 * CU1 · Flujo alternativo: recuperar la contraseña con un código enviado al correo.
 *
 *   1. /recuperar         el usuario escribe su correo → se envía un código de 6 dígitos
 *   2. /recuperar/codigo  escribe el código y la nueva contraseña → se restablece
 *
 * Seguridad: el código se guarda con password_hash(), vence en 15 minutos, admite
 * 5 intentos, se limita la cantidad de solicitudes y la respuesta es la misma exista
 * o no el correo (no se revela qué correos están registrados).
 */

declare(strict_types=1);

final class RecuperacionController extends Controlador
{
    private const AVISO = 'Si el correo está registrado, te enviamos un código de 6 dígitos. Revisa tu bandeja de entrada y la carpeta de spam.';

    public function formulario(): void
    {
        if (Auth::usuario()) {
            redirect(Auth::inicio());
        }
        $this->vista('P1_SeguridadAcceso/recuperar', [], 'auth');
    }

    public function enviar(): void
    {
        $correo = mb_strtolower(entrada('email'));
        $v = (new Validator(['email' => $correo]))->requerido('email', 'El correo')->email('email')->max('email', 150);
        if ($v->falla()) {
            con_errores($v->errores(), ['email' => $correo]);
            flash('error', $v->primerError());
            redirect('/recuperar');
        }
        if (Recuperacion::solicitudesPorIp(ip_cliente()) >= Recuperacion::MAX_POR_IP) {
            flash('error', 'Demasiadas solicitudes. Espere unos minutos e intente de nuevo.');
            redirect('/recuperar');
        }
        if (!Mailer::configurado()) {
            error_log('[NovaSol] Recuperación solicitada pero SMTP no está configurado');
            flash('error', 'El envío de correos no está configurado. Solicite al administrador que restablezca su contraseña.');
            redirect('/recuperar');
        }

        $u = Recuperacion::usuarioPorCorreo($correo);
        if ($u) {
            if (Recuperacion::solicitudesRecientes((int) $u['idusuario']) >= Recuperacion::MAX_SOLICITUDES) {
                $_SESSION['recuperar_email'] = $correo;
                flash('error', 'Ya se enviaron varios códigos a este correo. Espere 15 minutos para pedir otro.');
                redirect('/recuperar/codigo');
            }
            $codigo = Recuperacion::crear((int) $u['idusuario']);
            try {
                [$html, $texto] = Recuperacion::correo($u['nombre'], $codigo);
                Mailer::enviar($u['email'], 'Tu código de verificación: ' . $codigo, $html, $texto);
                Bitacora::registrar('RECUPERAR_SOLICITUD', 'SEGURIDAD', 'usuario', (int) $u['idusuario'], 'Código de recuperación enviado al correo', (int) $u['idusuario']);
            } catch (Throwable $e) {
                error_log('[NovaSol] No se pudo enviar el correo de recuperación: ' . $e->getMessage());
                if ($r = Recuperacion::vigente((int) $u['idusuario'])) {
                    Recuperacion::anular((int) $r['idrecuperacion']);
                }
                flash('error', 'No pudimos enviar el correo en este momento. Intente más tarde o contacte al administrador.');
                redirect('/recuperar');
            }
        }

        // La misma respuesta exista o no la cuenta
        $_SESSION['recuperar_email'] = $correo;
        flash('ok', self::AVISO);
        redirect('/recuperar/codigo');
    }

    public function codigo(): void
    {
        if (empty($_SESSION['recuperar_email'])) {
            redirect('/recuperar');
        }
        $this->vista('P1_SeguridadAcceso/recuperar_codigo', ['correo' => $_SESSION['recuperar_email']], 'auth');
    }

    public function restablecer(): void
    {
        $correo = (string) ($_SESSION['recuperar_email'] ?? '');
        if ($correo === '') {
            redirect('/recuperar');
        }
        $d = ['codigo' => preg_replace('/\D/', '', entrada('codigo')), 'password' => $_POST['password'] ?? '', 'password_confirmacion' => $_POST['password_confirmacion'] ?? ''];
        $v = new Validator($d);
        $v->requerido('codigo', 'El código')->regex('codigo', '/^\d{6}$/', 'El código tiene 6 dígitos.')
          ->password('password')->coincide('password_confirmacion', 'password', 'Las contraseñas no coinciden.');
        if ($v->falla()) {
            con_errores($v->errores());
            flash('error', $v->primerError());
            redirect('/recuperar/codigo');
        }

        $u = Recuperacion::usuarioPorCorreo($correo);
        $r = $u ? Recuperacion::vigente((int) $u['idusuario']) : null;
        if (!$r) {
            flash('error', 'El código no es válido o ya venció. Solicite uno nuevo.');
            redirect('/recuperar/codigo');
        }
        if ((int) $r['intentos'] >= Recuperacion::MAX_INTENTOS) {
            Recuperacion::anular((int) $r['idrecuperacion']);
            flash('error', 'Se superó el número de intentos. Solicite un código nuevo.');
            redirect('/recuperar');
        }
        if (!password_verify($d['codigo'], $r['codigohash'])) {
            Recuperacion::sumarIntento((int) $r['idrecuperacion']);
            $quedan = Recuperacion::MAX_INTENTOS - (int) $r['intentos'] - 1;
            Bitacora::registrar('RECUPERAR_FALLIDO', 'SEGURIDAD', 'usuario', (int) $u['idusuario'], 'Código de recuperación incorrecto', (int) $u['idusuario']);
            flash('error', $quedan > 0 ? "Código incorrecto. Le quedan $quedan intento(s)." : 'Código incorrecto. Solicite un código nuevo.');
            redirect($quedan > 0 ? '/recuperar/codigo' : '/recuperar');
        }

        Recuperacion::restablecer($r, $d['password']);
        Bitacora::registrar('RECUPERAR_CONTRASENA', 'SEGURIDAD', 'usuario', (int) $u['idusuario'], 'Contraseña restablecida con código enviado al correo', (int) $u['idusuario']);
        unset($_SESSION['recuperar_email']);
        flash('ok', 'Tu contraseña fue restablecida. Ya puedes ingresar con la nueva contraseña.');
        redirect('/login');
    }
}
