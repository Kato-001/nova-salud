<?php
/**
 * CU7 · Registrar / modificar paciente
 * @var ?array $p
 */
$editando = $p !== null;
$titulo = $editando ? 'Editar paciente' : 'Registrar paciente';
$conCuenta = !empty($GLOBALS['_old']['crear_cuenta']);
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><a href="/pacientes"><?= icono('izquierda', 14) ?> Pacientes</a></div>
        <h1><?= $editando ? 'Editar paciente' : 'Registrar paciente' ?></h1>
        <p><?= $editando ? e($p['codigo'] . ' · ' . $p['nombre_completo']) : 'El código del paciente y su historia clínica se generan automáticamente.' ?></p>
    </div>
</div>

<form method="post" action="/pacientes/guardar" class="card card-pad anim" style="max-width:980px" data-validar>
    <?= csrf_field() ?>
    <?php if ($editando): ?><input type="hidden" name="idpaciente" value="<?= $p['idpaciente'] ?>"><?php endif; ?>
    <?= View::parcial('campos_paciente', ['p' => $p]) ?>

    <?php if (!$editando): ?>
        <div class="section-title mt">Portal del paciente</div>
        <label class="check"><input type="checkbox" name="crear_cuenta" value="1" <?= $conCuenta ? 'checked' : '' ?>
               onchange="document.getElementById('cuenta').hidden = !this.checked; document.querySelectorAll('#cuenta input').forEach(i => i.disabled = !this.checked)">
            Crear cuenta para que el paciente reserve y consulte sus citas en línea</label>
        <div class="grid-3 mt" id="cuenta" <?= $conCuenta ? '' : 'hidden' ?>>
            <label class="field"><span>Nombre de usuario *</span>
                <input class="input mono<?= clase_error('username') ?>" name="username" value="<?= e(old('username')) ?>" required minlength="3" maxlength="50" pattern="[A-Za-z0-9_.\-]+" data-msg="Solo letras, números, guiones y puntos." autocomplete="off" <?= $conCuenta ? '' : 'disabled' ?>>
                <?= error_campo('username') ?></label>
            <label class="field"><span>Contraseña *</span>
                <input class="input<?= clase_error('password') ?>" type="password" name="password" required minlength="8" maxlength="100" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" data-msg="Mínimo 8 caracteres con letras y números." autocomplete="new-password" <?= $conCuenta ? '' : 'disabled' ?>>
                <?= error_campo('password') ?></label>
            <label class="field"><span>Confirmar *</span>
                <input class="input<?= clase_error('password_confirmacion') ?>" type="password" name="password_confirmacion" required maxlength="100" data-coincide="password" autocomplete="new-password" <?= $conCuenta ? '' : 'disabled' ?>>
                <?= error_campo('password_confirmacion') ?></label>
            <small class="faint span-all">Para la cuenta, el correo electrónico es obligatorio.</small>
        </div>
    <?php endif; ?>

    <div class="row mt" style="justify-content:flex-end">
        <a href="<?= $editando ? e(url('/pacientes/ver', ['id' => $p['idpaciente']])) : '/pacientes' ?>" class="btn btn-ghost">Cancelar</a>
        <button class="btn btn-primary"><?= icono('check') ?> <?= $editando ? 'Guardar cambios' : 'Registrar paciente' ?></button>
    </div>
</form>
