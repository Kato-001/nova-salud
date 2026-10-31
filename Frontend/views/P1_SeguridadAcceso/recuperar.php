<?php
/** CU1 · Recuperar contraseña · Paso 1: correo */
$titulo = 'Recuperar contraseña';
?>
<div class="auth-centro">
    <div class="card card-pad auth-tarjeta anim">
        <a href="/login" class="brand" style="padding:0 0 22px">
            <span class="brand-logo"><?= icono('cruz', 22) ?></span>
            <span><strong>NovaSol Salud</strong><small><?= e(config('NOMBRE_CLINICA', 'Clínica Vida Salud')) ?></small></span>
        </a>
        <div class="pasos-rec"><span class="on">1</span><i></i><span>2</span></div>
        <h1 style="font-size:26px;font-weight:800">¿Olvidaste tu contraseña?</h1>
        <p class="soft" style="margin:8px 0 22px">Escribe el correo electrónico de tu cuenta y te enviaremos un <strong>código de verificación de 6 dígitos</strong>.</p>
        <form method="post" action="/recuperar" class="stack" data-validar>
            <?= csrf_field() ?>
            <label class="field"><span>Correo electrónico</span>
                <span class="input-icon"><?= icono('correo') ?>
                    <input class="input<?= clase_error('email') ?>" type="email" name="email" value="<?= e(old('email')) ?>" required maxlength="150" autocomplete="email" autofocus placeholder="nombre@gmail.com">
                </span><?= error_campo('email') ?></label>
            <button class="btn btn-primary btn-block" style="height:48px"><?= icono('correo', 17) ?> Enviar código</button>
        </form>
        <p class="faint" style="font-size:13px;margin:20px 0 0;text-align:center"><a href="/login"><?= icono('izquierda', 14) ?> Volver al inicio de sesión</a></p>
    </div>
</div>
