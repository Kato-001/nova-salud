<?php
/**
 * CU3 · Registrar / editar usuario
 * @var ?array $usuario @var array $roles
 */
$editando = $usuario !== null;
$titulo = $editando ? 'Editar usuario' : 'Nuevo usuario';
$esYo = $editando && (int) $usuario['idusuario'] === Auth::id();
$vinculado = $editando && in_array($usuario['rol'], ['MEDICO', 'PACIENTE'], true);
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><a href="/usuarios"><?= icono('izquierda', 14) ?> Usuarios</a></div>
        <h1><?= $editando ? 'Editar usuario' : 'Registrar usuario' ?></h1>
        <p><?= $editando ? e($usuario['email']) : 'Los campos con * son obligatorios.' ?></p>
    </div>
</div>

<form method="post" action="/usuarios/guardar" class="card card-pad anim" style="max-width:820px" data-validar>
    <?= csrf_field() ?>
    <?php if ($editando): ?><input type="hidden" name="idusuario" value="<?= $usuario['idusuario'] ?>"><?php endif; ?>

    <div class="section-title">Datos de la persona</div>
    <div class="grid-2">
        <label class="field">
            <span>Nombre completo *</span>
            <input class="input<?= clase_error('nombre') ?>" name="nombre" value="<?= e(old('nombre', $usuario['nombre'] ?? '')) ?>" required minlength="3" maxlength="150" autofocus>
            <?= error_campo('nombre') ?>
        </label>
        <label class="field">
            <span>Rol *</span>
            <select class="select<?= clase_error('idrol') ?>" name="idrol" required <?= $esYo || $vinculado ? 'disabled' : '' ?>>
                <option value="">Seleccione…</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r['idrol'] ?>" <?= (string) old('idrol', $usuario['idrol'] ?? '') === (string) $r['idrol'] ? 'selected' : '' ?>><?= e($r['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($esYo || $vinculado): ?>
                <input type="hidden" name="idrol" value="<?= $usuario['idrol'] ?>">
                <small><?= $esYo ? 'No puede cambiar su propio rol.' : 'El rol de médicos y pacientes no se cambia desde aquí.' ?></small>
            <?php endif; ?>
            <?= error_campo('idrol') ?>
        </label>
    </div>

    <div class="section-title mt">Acceso</div>
    <div class="grid-2">
        <label class="field">
            <span>Nombre de usuario *</span>
            <input class="input mono<?= clase_error('username') ?>" name="username" value="<?= e(old('username', $usuario['username'] ?? '')) ?>" required minlength="3" maxlength="50"
                   pattern="[A-Za-z0-9_.\-]+" data-msg="Solo letras, números, guiones y puntos." autocomplete="off">
            <?= error_campo('username') ?>
        </label>
        <label class="field">
            <span>Correo electrónico *</span>
            <input class="input<?= clase_error('email') ?>" type="email" name="email" value="<?= e(old('email', $usuario['email'] ?? '')) ?>" required maxlength="150" autocomplete="off">
            <?= error_campo('email') ?>
        </label>
        <label class="field">
            <span>Contraseña <?= $editando ? '(vacío = no cambiar)' : '*' ?></span>
            <input class="input<?= clase_error('password') ?>" type="password" name="password" <?= $editando ? '' : 'required' ?> minlength="8" maxlength="100"
                   pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" data-msg="Mínimo 8 caracteres con letras y números." autocomplete="new-password" data-fortaleza="fuerza">
            <div class="strength" id="fuerza" hidden><i></i><i></i><i></i><i></i></div>
            <?= error_campo('password') ?: '<small>Mínimo 8 caracteres con letras y números. Se guarda cifrada con password_hash().</small>' ?>
        </label>
        <label class="field">
            <span>Confirmar contraseña</span>
            <input class="input<?= clase_error('password_confirmacion') ?>" type="password" name="password_confirmacion" maxlength="100" data-coincide="password" autocomplete="new-password">
            <?= error_campo('password_confirmacion') ?>
        </label>
    </div>

    <div class="row mt" style="justify-content:flex-end">
        <a href="/usuarios" class="btn btn-ghost">Cancelar</a>
        <button type="submit" class="btn btn-primary"><?= icono('check') ?> <?= $editando ? 'Guardar cambios' : 'Crear usuario' ?></button>
    </div>
</form>
