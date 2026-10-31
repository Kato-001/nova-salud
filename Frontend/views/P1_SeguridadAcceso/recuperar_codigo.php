<?php
/**
 * CU1 · Recuperar contraseña · Paso 2: código y nueva contraseña
 * @var string $correo
 */
$titulo = 'Código de verificación';
// Se muestra el correo parcialmente oculto: ju****@gmail.com
[$local, $dominio] = array_pad(explode('@', $correo, 2), 2, '');
$oculto = mb_substr($local, 0, 2) . str_repeat('•', max(2, mb_strlen($local) - 2)) . '@' . $dominio;
?>
<div class="auth-centro">
    <div class="card card-pad auth-tarjeta anim">
        <a href="/login" class="brand" style="padding:0 0 22px">
            <span class="brand-logo"><?= icono('cruz', 22) ?></span>
            <span><strong>NovaSol Salud</strong><small><?= e(config('NOMBRE_CLINICA', 'Clínica Vida Salud')) ?></small></span>
        </a>
        <div class="pasos-rec"><span class="ok"><?= icono('check', 14) ?></span><i class="on"></i><span class="on">2</span></div>
        <h1 style="font-size:26px;font-weight:800">Revisa tu correo</h1>
        <p class="soft" style="margin:8px 0 22px">Escribe el código de 6 dígitos que enviamos a <strong><?= e($oculto) ?></strong> y elige tu nueva contraseña. El código vence en 15 minutos.</p>
        <form method="post" action="/recuperar/codigo" class="stack" data-validar>
            <?= csrf_field() ?>
            <label class="field"><span>Código de verificación</span>
                <input class="input codigo-otp<?= clase_error('codigo') ?>" name="codigo" required inputmode="numeric" autocomplete="one-time-code"
                       pattern="\d{6}" maxlength="6" data-msg="El código tiene 6 dígitos." placeholder="••••••" autofocus>
                <?= error_campo('codigo') ?></label>
            <label class="field"><span>Nueva contraseña</span>
                <input class="input<?= clase_error('password') ?>" type="password" name="password" required minlength="8" maxlength="100"
                       pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" data-msg="Mínimo 8 caracteres con letras y números." autocomplete="new-password" data-fortaleza="fuerza">
                <div class="strength" id="fuerza" hidden><i></i><i></i><i></i><i></i></div>
                <?= error_campo('password') ?: '<small>Mínimo 8 caracteres con letras y números.</small>' ?></label>
            <label class="field"><span>Confirmar contraseña</span>
                <input class="input<?= clase_error('password_confirmacion') ?>" type="password" name="password_confirmacion" required maxlength="100" data-coincide="password" autocomplete="new-password">
                <?= error_campo('password_confirmacion') ?></label>
            <button class="btn btn-primary btn-block" style="height:48px"><?= icono('llave', 17) ?> Restablecer contraseña</button>
        </form>
        <form method="post" action="/recuperar" style="margin-top:16px;text-align:center">
            <?= csrf_field() ?><input type="hidden" name="email" value="<?= e($correo) ?>">
            <span class="faint" style="font-size:13px">¿No llegó el código?</span>
            <button class="btn btn-ghost btn-sm" data-sin-carga="1">Reenviar código</button>
        </form>
        <p class="faint" style="font-size:13px;margin:8px 0 0;text-align:center"><a href="/recuperar">Usar otro correo</a> · <a href="/login">Volver al inicio de sesión</a></p>
    </div>
</div>
