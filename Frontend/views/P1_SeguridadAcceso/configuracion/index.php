<?php
/**
 * Configuración del sistema
 * @var array $parametros
 */
$titulo = 'Configuración';
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><?= icono('ajustes', 14) ?> Administración</div>
        <h1>Configuración del sistema</h1>
        <p>Parámetros que usan las reglas de programación de citas.</p>
    </div>
</div>

<form method="post" action="/configuracion" class="card anim" style="max-width:880px" data-validar>
    <?= csrf_field() ?>
    <?php foreach ($parametros as $p):
        $regla = Configuracion::REGLAS[$p['clave']] ?? null; ?>
        <div class="cfg-row">
            <div>
                <strong><?= e($p['descripcion'] ?: $p['clave']) ?></strong>
                <span class="mono faint" style="font-size:12px"><?= e($p['clave']) ?></span>
            </div>
            <div class="field">
                <input class="input<?= clase_error($p['clave']) ?>" name="valores[<?= e($p['clave']) ?>]" value="<?= e(old($p['clave'], $p['valor'])) ?>" required maxlength="255"
                       <?= $regla ? 'type="number" min="' . $regla[0] . '" max="' . $regla[1] . '" step="1"' : '' ?> aria-label="<?= e($p['descripcion'] ?: $p['clave']) ?>">
                <?= error_campo($p['clave']) ?>
            </div>
        </div>
    <?php endforeach; ?>
    <div class="card-pad row-between" style="border-top:1px solid var(--border)">
        <span class="faint" style="font-size:13px">Cambiar la duración de las citas no modifica las citas ya programadas.</span>
        <button class="btn btn-primary"><?= icono('check') ?> Guardar cambios</button>
    </div>
</form>
