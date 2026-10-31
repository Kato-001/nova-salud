<?php
/**
 * CU5 · Registrar / editar médico
 * @var ?array $m @var array $especialidades @var string $codigo
 */
$editando = $m !== null;
$titulo = $editando ? 'Editar médico' : 'Registrar médico';
$elegidas = array_map('intval', (array) ($GLOBALS['_old']['especialidades'] ?? ($m['ids_especialidad'] ?? [])));
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><a href="/medicos"><?= icono('izquierda', 14) ?> Médicos</a></div>
        <h1><?= $editando ? 'Editar médico' : 'Registrar médico' ?></h1>
        <p><?= $editando ? 'Dr(a). ' . e($m['nombre_completo']) : 'Se creará también su cuenta para que consulte su agenda.' ?></p>
    </div>
</div>

<form method="post" action="/medicos/guardar" class="card card-pad anim" style="max-width:920px" data-validar>
    <?= csrf_field() ?>
    <?php if ($editando): ?><input type="hidden" name="idmedico" value="<?= $m['idmedico'] ?>"><?php endif; ?>

    <div class="section-title">Datos profesionales</div>
    <div class="grid-3">
        <label class="field"><span>Código *</span>
            <input class="input mono<?= clase_error('codigo') ?>" name="codigo" value="<?= e(old('codigo', $codigo)) ?>" required minlength="3" maxlength="20" pattern="[A-Za-z0-9\-]+" data-msg="Solo letras, números y guiones.">
            <?= error_campo('codigo') ?></label>
        <label class="field"><span>Registro profesional *</span>
            <input class="input mono<?= clase_error('registroprofesional') ?>" name="registroprofesional" value="<?= e(old('registroprofesional', $m['registroprofesional'] ?? '')) ?>" required minlength="3" maxlength="50" pattern="[A-Za-z0-9\-]+" data-msg="Solo letras, números y guiones." placeholder="Ej. RP-12345">
            <?= error_campo('registroprofesional') ?></label>
        <span></span>
        <label class="field"><span>Nombres *</span>
            <input class="input<?= clase_error('nombres') ?>" name="nombres" value="<?= e(old('nombres', $m['nombres'] ?? '')) ?>" required minlength="2" maxlength="100" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü .'\-]+" data-msg="Solo letras.">
            <?= error_campo('nombres') ?></label>
        <label class="field"><span>Apellidos *</span>
            <input class="input<?= clase_error('apellidos') ?>" name="apellidos" value="<?= e(old('apellidos', $m['apellidos'] ?? '')) ?>" required minlength="2" maxlength="100" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü .'\-]+" data-msg="Solo letras.">
            <?= error_campo('apellidos') ?></label>
        <label class="field"><span>Teléfono</span>
            <input class="input<?= clase_error('telefono') ?>" name="telefono" value="<?= e(old('telefono', $m['telefono'] ?? '')) ?>" maxlength="20" pattern="[0-9+\-\s]{7,20}" data-msg="7 a 20 dígitos." inputmode="tel">
            <?= error_campo('telefono') ?></label>
        <label class="field span-2"><span>Correo electrónico *</span>
            <input class="input<?= clase_error('email') ?>" type="email" name="email" value="<?= e(old('email', $m['email'] ?? '')) ?>" required maxlength="150">
            <?= error_campo('email') ?></label>
    </div>

    <div class="section-title mt">Especialidades *</div>
    <div class="field">
        <div class="chips">
            <?php foreach ($especialidades as $esp): ?>
                <label class="chip-check">
                    <input type="checkbox" name="especialidades[]" value="<?= $esp['idespecialidad'] ?>" <?= in_array((int) $esp['idespecialidad'], $elegidas, true) ? 'checked' : '' ?>>
                    <span><?= icono('check', 14) ?><?= e($esp['nombre']) ?><?= $esp['estado'] !== 'ACTIVO' ? ' (inactiva)' : '' ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?= error_campo('especialidades') ?>
    </div>

    <?php if (!$editando): ?>
        <div class="section-title mt">Cuenta de acceso del médico</div>
        <div class="grid-3">
            <label class="field"><span>Nombre de usuario *</span>
                <input class="input mono<?= clase_error('username') ?>" name="username" value="<?= e(old('username')) ?>" required minlength="3" maxlength="50" pattern="[A-Za-z0-9_.\-]+" data-msg="Solo letras, números, guiones y puntos." autocomplete="off">
                <?= error_campo('username') ?></label>
            <label class="field"><span>Contraseña *</span>
                <input class="input<?= clase_error('password') ?>" type="password" name="password" required minlength="8" maxlength="100" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" data-msg="Mínimo 8 caracteres con letras y números." autocomplete="new-password" data-fortaleza="fuerza">
                <div class="strength" id="fuerza" hidden><i></i><i></i><i></i><i></i></div>
                <?= error_campo('password') ?></label>
            <label class="field"><span>Confirmar contraseña *</span>
                <input class="input<?= clase_error('password_confirmacion') ?>" type="password" name="password_confirmacion" required maxlength="100" data-coincide="password" autocomplete="new-password">
                <?= error_campo('password_confirmacion') ?></label>
        </div>
    <?php else: ?>
        <p class="faint mt" style="font-size:13px">La cuenta de acceso (usuario <span class="mono"><?= e($m['username']) ?></span>) se administra desde <a href="/usuarios">Usuarios</a>.</p>
    <?php endif; ?>

    <div class="row mt" style="justify-content:flex-end">
        <a href="<?= $editando ? e(url('/medicos/ver', ['id' => $m['idmedico']])) : '/medicos' ?>" class="btn btn-ghost">Cancelar</a>
        <button class="btn btn-primary"><?= icono('check') ?> <?= $editando ? 'Guardar cambios' : 'Registrar médico' ?></button>
    </div>
</form>
