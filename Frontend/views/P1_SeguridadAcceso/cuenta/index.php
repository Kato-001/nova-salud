<?php
/**
 * Mi cuenta (personal de la clínica)
 * @var array $u
 */
$titulo = 'Mi cuenta';
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><?= icono('cuenta', 14) ?> Perfil</div>
        <h1>Mi cuenta</h1>
        <p>Sus datos de acceso al sistema.</p>
    </div>
</div>

<div class="split" data-escalonar>
    <div class="card">
        <div class="ficha-head">
            <div class="avatar lg"><?= e(iniciales($u['nombre'])) ?></div>
            <h2><?= e($u['nombre']) ?></h2>
            <p><?= badge_rol($u['rol']) ?> <?= badge_estado($u['estado']) ?></p>
        </div>
        <div class="kv-list">
            <div><span><?= icono('usuario', 16) ?> Usuario</span><span class="mono"><?= e($u['username']) ?></span></div>
            <div><span><?= icono('correo', 16) ?> Correo</span><span><?= e($u['email']) ?></span></div>
            <div><span><?= icono('reloj', 16) ?> Último acceso</span><span><?= e(fecha_hora($u['ultimoacceso'])) ?></span></div>
            <div><span><?= icono('escudo', 16) ?> Permisos</span><span><?= count($u['permisos']) ?></span></div>
        </div>
        <div class="card-pad"><p class="faint m0" style="font-size:13px">Para cambiar su nombre, usuario o correo, solicítelo al administrador.</p></div>
    </div>

    <form method="post" action="/cuenta/password" class="card card-pad" data-validar>
        <?= csrf_field() ?>
        <h2 style="font-size:17px;margin-bottom:18px" class="row"><?= icono('llave') ?> Cambiar contraseña</h2>
        <div class="stack">
            <label class="field"><span>Contraseña actual</span>
                <input class="input<?= clase_error('actual') ?>" type="password" name="actual" required maxlength="100" autocomplete="current-password"><?= error_campo('actual') ?></label>
            <label class="field"><span>Nueva contraseña</span>
                <input class="input<?= clase_error('password') ?>" type="password" name="password" required minlength="8" maxlength="100" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}"
                       data-msg="Mínimo 8 caracteres con letras y números." autocomplete="new-password" data-fortaleza="fuerza">
                <div class="strength" id="fuerza" hidden><i></i><i></i><i></i><i></i></div>
                <?= error_campo('password') ?: '<small>Mínimo 8 caracteres con letras y números.</small>' ?></label>
            <label class="field"><span>Confirmar nueva contraseña</span>
                <input class="input<?= clase_error('password_confirmacion') ?>" type="password" name="password_confirmacion" required maxlength="100" data-coincide="password" autocomplete="new-password">
                <?= error_campo('password_confirmacion') ?></label>
            <button class="btn btn-primary">Actualizar contraseña</button>
        </div>
    </form>
</div>
