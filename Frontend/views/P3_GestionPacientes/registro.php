<?php
/** CU8 · Registrarse como paciente (portal) */
$titulo = 'Crear cuenta de paciente';
?>
<div style="min-height:100%;padding:32px 16px;display:grid;place-items:start center">
    <div style="width:100%;max-width:980px">
        <div class="row-between mb anim">
            <a href="/" class="brand" style="padding:0" title="Volver al inicio">
                <span class="brand-logo"><?= icono('cruz', 22) ?></span>
                <span><strong>Nova Salud</strong><small><?= e(config('NOMBRE_CLINICA', 'Clínica Vida Salud')) ?></small></span>
            </a>
            <a href="/login" class="btn btn-ghost"><?= icono('izquierda', 16) ?> Ya tengo cuenta</a>
        </div>
        <div class="hero-portal anim" style="--i:1">
            <div class="blob" style="width:300px;height:300px;background:#0d9488;top:-120px;left:-60px"></div>
            <div class="blob" style="width:260px;height:260px;background:#4f46e5;bottom:-140px;right:-40px;animation-delay:-4s"></div>
            <h1>Cree su cuenta de paciente</h1>
            <p>Reserve sus citas en línea, reciba recordatorios y consulte su historial médico desde cualquier lugar.</p>
        </div>

        <form method="post" action="/registro" class="card card-pad anim" style="--i:2" data-validar>
            <?= csrf_field() ?>
            <?= View::parcial('campos_paciente', ['correoObligatorio' => true]) ?>

            <div class="section-title mt">Cuenta</div>
            <div class="grid-3">
                <label class="field"><span>Nombre de usuario *</span>
                    <input class="input mono<?= clase_error('username') ?>" name="username" value="<?= e(old('username')) ?>" required minlength="3" maxlength="50" pattern="[A-Za-z0-9_.\-]+" data-msg="Solo letras, números, guiones y puntos." autocomplete="username">
                    <?= error_campo('username') ?></label>
                <label class="field"><span>Contraseña *</span>
                    <input class="input<?= clase_error('password') ?>" type="password" name="password" required minlength="8" maxlength="100" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" data-msg="Mínimo 8 caracteres con letras y números." autocomplete="new-password" data-fortaleza="fuerza">
                    <div class="strength" id="fuerza" hidden><i></i><i></i><i></i><i></i></div>
                    <?= error_campo('password') ?: '<small>Mínimo 8 caracteres con letras y números.</small>' ?></label>
                <label class="field"><span>Confirmar contraseña *</span>
                    <input class="input<?= clase_error('password_confirmacion') ?>" type="password" name="password_confirmacion" required maxlength="100" data-coincide="password" autocomplete="new-password">
                    <?= error_campo('password_confirmacion') ?></label>
            </div>
            <div class="field mt">
                <label class="check"><input type="checkbox" name="acepta" value="1" required <?= old('acepta') ? 'checked' : '' ?>>
                    Autorizo el uso de mis datos personales y médicos solo para mi atención en la clínica.</label>
                <?= error_campo('acepta') ?>
            </div>
            <div class="row mt" style="justify-content:flex-end">
                <a href="/login" class="btn btn-ghost">Cancelar</a>
                <button class="btn btn-primary"><?= icono('usuario-ok') ?> Crear mi cuenta</button>
            </div>
        </form>
    </div>
</div>
