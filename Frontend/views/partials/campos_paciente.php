<?php
/**
 * Campos de datos personales del paciente (formulario interno, autoregistro y portal).
 * @var ?array $p datos actuales · @var bool $ciBloqueado
 */
$p ??= [];
$ciBloqueado ??= false;
$v = fn(string $c, string $def = '') => old($c, $p[$c] ?? $def);
?>
<div class="section-title">Datos personales</div>
<div class="grid-3">
    <label class="field"><span>CI (documento de identidad) *</span>
        <input class="input mono<?= clase_error('ci') ?>" name="ci" value="<?= e($v('ci')) ?>" required maxlength="16" pattern="[0-9]{5,12}(-[0-9A-Za-z]{1,3})?"
               data-msg="5 a 12 dígitos (complemento opcional, ej. 1234567-1B)." inputmode="numeric" <?= $ciBloqueado ? 'readonly title="El CI no se puede modificar"' : '' ?>>
        <?= error_campo('ci') ?></label>
    <label class="field"><span>Nombres *</span>
        <input class="input<?= clase_error('nombres') ?>" name="nombres" value="<?= e($v('nombres')) ?>" required minlength="2" maxlength="100" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü .'\-]+" data-msg="Solo letras.">
        <?= error_campo('nombres') ?></label>
    <label class="field"><span>Apellidos *</span>
        <input class="input<?= clase_error('apellidos') ?>" name="apellidos" value="<?= e($v('apellidos')) ?>" required minlength="2" maxlength="100" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü .'\-]+" data-msg="Solo letras.">
        <?= error_campo('apellidos') ?></label>
    <label class="field"><span>Fecha de nacimiento *</span>
        <input class="input<?= clase_error('fechanacimiento') ?>" type="date" name="fechanacimiento" value="<?= e($v('fechanacimiento')) ?>" required min="1900-01-01" max="<?= hoy() ?>">
        <?= error_campo('fechanacimiento') ?></label>
    <label class="field"><span>Sexo *</span>
        <select class="select<?= clase_error('sexo') ?>" name="sexo" required>
            <option value="">Seleccione…</option>
            <?php foreach (Paciente::SEXOS as $k => $l): ?><option value="<?= $k ?>" <?= $v('sexo') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
        </select><?= error_campo('sexo') ?></label>
    <label class="field"><span>Tipo de sangre</span>
        <select class="select<?= clase_error('tiposangre') ?>" name="tiposangre">
            <option value="">No sabe / no indica</option>
            <?php foreach (Paciente::TIPOS_SANGRE as $t): ?><option <?= $v('tiposangre') === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
        </select><?= error_campo('tiposangre') ?></label>
</div>

<div class="section-title mt">Contacto</div>
<div class="grid-3">
    <label class="field"><span>Teléfono *</span>
        <input class="input<?= clase_error('telefono') ?>" name="telefono" value="<?= e($v('telefono')) ?>" required maxlength="20" pattern="[0-9+\-\s]{7,20}" data-msg="7 a 20 dígitos." inputmode="tel">
        <?= error_campo('telefono') ?></label>
    <label class="field"><span>Correo electrónico<?= !empty($correoObligatorio) ? ' *' : ' (opcional)' ?></span>
        <input class="input<?= clase_error('email') ?>" type="email" name="email" value="<?= e($v('email')) ?>" maxlength="150" <?= !empty($correoObligatorio) ? 'required' : '' ?>>
        <?= error_campo('email') ?></label>
    <label class="field"><span>Dirección (opcional)</span>
        <input class="input<?= clase_error('direccion') ?>" name="direccion" value="<?= e($v('direccion')) ?>" maxlength="255">
        <?= error_campo('direccion') ?></label>
    <label class="check span-all">
        <input type="checkbox" name="aceptanotificaciones" value="1" <?= ($GLOBALS['_old'] ? !empty($GLOBALS['_old']['aceptanotificaciones']) : ($p['aceptanotificaciones'] ?? true)) ? 'checked' : '' ?>>
        Acepta recibir recordatorios de sus citas
    </label>
</div>
