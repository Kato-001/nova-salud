<?php
/**
 * Portal · Perfil del paciente (datos personales, información médica,
 * contactos de emergencia y cuenta)
 * @var array $p @var array $contactos @var string $seccion
 */
$titulo = 'Mi perfil';
$secciones = ['personal' => ['Datos personales', 'usuario'], 'medica' => ['Información médica', 'pulso'], 'contacto' => ['Contactos de emergencia', 'telefono'], 'cuenta' => ['Cuenta', 'llave']];
$seccion = isset($secciones[$seccion]) ? $seccion : 'personal';
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><?= icono('cuenta', 14) ?> Mi salud</div>
        <h1>Mi perfil</h1>
        <p><span class="mono"><?= e($p['codigo']) ?></span> · Historia clínica <?= e($p['numero_historia']) ?></p>
    </div>
</div>

<nav class="tabs">
    <?php foreach ($secciones as $k => [$l, $ic]): ?>
        <a href="<?= e(url('/portal/perfil', ['seccion' => $k])) ?>" class="<?= $seccion === $k ? 'on' : '' ?>"><?= icono($ic, 16) ?> <?= $l ?></a>
    <?php endforeach; ?>
</nav>

<?php if ($seccion === 'personal'): ?>
    <form method="post" action="/portal/perfil" class="card card-pad anim" style="max-width:980px" data-validar>
        <?= csrf_field() ?><input type="hidden" name="seccion" value="personal">
        <?= View::parcial('campos_paciente', ['p' => $p, 'ciBloqueado' => true]) ?>
        <div class="row mt" style="justify-content:flex-end"><button class="btn btn-primary"><?= icono('check') ?> Guardar mis datos</button></div>
    </form>

<?php elseif ($seccion === 'medica'): ?>
    <form method="post" action="/portal/perfil" class="card card-pad anim" style="max-width:980px" data-validar>
        <?= csrf_field() ?><input type="hidden" name="seccion" value="medica">
        <p class="soft" style="margin-top:0;font-size:14px">Esta información ayuda a su médico. Solo la ven usted y el personal autorizado de la clínica.</p>
        <div class="grid-2">
            <label class="field"><span>Tipo de sangre</span>
                <select class="select" name="tiposangre"><option value="">No sé</option>
                    <?php foreach (Paciente::TIPOS_SANGRE as $t): ?><option <?= $p['tiposangre'] === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select></label>
            <?php foreach (['alergias' => 'Alergias', 'enfermedadescronicas' => 'Enfermedades crónicas', 'medicacionhabitual' => 'Medicamentos que toma',
                            'antecedentespersonales' => 'Antecedentes personales', 'antecedentesfamiliares' => 'Antecedentes familiares'] as $k => $l): ?>
                <label class="field"><span><?= $l ?></span><textarea class="textarea" name="<?= $k ?>" rows="2" maxlength="2000"><?= e($p[$k]) ?></textarea></label>
            <?php endforeach; ?>
        </div>
        <div class="row mt" style="justify-content:flex-end"><button class="btn btn-primary"><?= icono('check') ?> Guardar información médica</button></div>
    </form>

<?php elseif ($seccion === 'contacto'): ?>
    <div class="card anim" style="max-width:980px">
        <?php foreach ($contactos as $ct): ?>
            <div class="role-card">
                <div class="avatar"><?= e(iniciales($ct['nombre'])) ?></div>
                <div class="grow"><h3><?= e($ct['nombre']) ?> <span class="badge badge-off"><?= e($ct['parentesco']) ?></span></h3><p><?= e($ct['telefono']) ?></p></div>
                <form method="post" action="/portal/perfil" class="inline" data-confirm="Quitar a <?= e($ct['nombre']) ?>" data-confirm-ok="Quitar" data-peligro>
                    <?= csrf_field() ?><input type="hidden" name="seccion" value="quitar_contacto"><input type="hidden" name="idcontacto" value="<?= $ct['idcontacto'] ?>">
                    <button class="btn btn-ghost btn-icon btn-sm btn-danger-ghost" title="Quitar" data-sin-carga="1"><?= icono('basura', 16) ?></button>
                </form>
            </div>
        <?php endforeach; ?>
        <?php if (!$contactos): ?><div class="empty" style="padding:30px">Agregue al menos un contacto para emergencias.</div><?php endif; ?>
        <form method="post" action="/portal/perfil" class="card-pad" style="border-top:1px solid var(--border)" data-validar>
            <?= csrf_field() ?><input type="hidden" name="seccion" value="contacto">
            <div class="grid-3">
                <label class="field"><span>Nombre *</span><input class="input" name="nombre" required minlength="3" maxlength="150"></label>
                <label class="field"><span>Parentesco *</span><input class="input" name="parentesco" required minlength="3" maxlength="50" placeholder="Ej. Hermano"></label>
                <label class="field"><span>Teléfono *</span><input class="input" name="telefono" required pattern="[0-9+\-\s]{7,20}" data-msg="7 a 20 dígitos." maxlength="20"></label>
            </div>
            <div class="row mt" style="justify-content:flex-end"><button class="btn btn-outline"><?= icono('mas', 16) ?> Agregar contacto</button></div>
        </form>
    </div>

<?php else: ?>
    <div class="split">
        <form method="post" action="/cuenta/password" class="card card-pad anim" data-validar>
            <?= csrf_field() ?>
            <h2 style="font-size:17px;margin-bottom:16px" class="row"><?= icono('llave') ?> Cambiar contraseña</h2>
            <div class="stack">
                <label class="field"><span>Contraseña actual</span><input class="input<?= clase_error('actual') ?>" type="password" name="actual" required autocomplete="current-password"><?= error_campo('actual') ?></label>
                <label class="field"><span>Nueva contraseña</span><input class="input<?= clase_error('password') ?>" type="password" name="password" required minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" data-msg="Mínimo 8 caracteres con letras y números." autocomplete="new-password" data-fortaleza="fuerza">
                    <div class="strength" id="fuerza" hidden><i></i><i></i><i></i><i></i></div><?= error_campo('password') ?></label>
                <label class="field"><span>Confirmar</span><input class="input<?= clase_error('password_confirmacion') ?>" type="password" name="password_confirmacion" required data-coincide="password" autocomplete="new-password"><?= error_campo('password_confirmacion') ?></label>
                <button class="btn btn-primary">Actualizar contraseña</button>
            </div>
        </form>
        <div class="card card-pad anim">
            <h2 style="font-size:17px;margin-bottom:16px" class="row"><?= icono('escudo') ?> Privacidad y notificaciones</h2>
            <div class="kv-list" style="margin:0 -24px">
                <div><span>Usuario</span><span class="mono"><?= e($p['username']) ?></span></div>
                <div><span>Recordatorios de citas</span><span><?= $p['aceptanotificaciones'] ? 'Activados' : 'Desactivados' ?></span></div>
            </div>
            <p class="soft" style="font-size:13.5px">Puede activar o desactivar los recordatorios en <a href="<?= e(url('/portal/perfil', ['seccion' => 'personal'])) ?>">Datos personales</a>.
                Su contraseña se guarda cifrada y su información médica solo la ve el personal autorizado. Cada acceso queda registrado en la bitácora.</p>
        </div>
    </div>
<?php endif; ?>
