<?php
/**
 * CU11 · Reprogramar cita (RF08)
 * @var array $c @var int $maxDias
 */
$titulo = 'Reprogramar ' . $c['codigo'];
$scripts = ['citas.js'];
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><a href="<?= e(url('/citas/ver', ['id' => $c['idcita']])) ?>"><?= icono('izquierda', 14) ?> Cita <?= e($c['codigo']) ?></a></div>
        <h1>Reprogramar cita</h1>
        <p>Elija una nueva fecha y hora libres del mismo médico. La cita volverá a estado <strong>Programada</strong>.</p>
    </div>
</div>

<form method="post" action="/citas/reprogramar" id="form-cita" data-modo="reprogramar" data-max-dias="<?= $maxDias ?>"
      data-excluir="<?= $c['idcita'] ?>" data-dias="<?= e(implode(',', Medico::diasAtencion((int) $c['idmedico']))) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="idcita" value="<?= $c['idcita'] ?>">
    <input type="hidden" name="idmedico" value="<?= $c['idmedico'] ?>">
    <div class="steps anim">
        <div class="step done" data-step="1"><b>1</b> Cita actual</div>
        <div class="step done" data-step="2"><b>2</b> Médico</div>
        <div class="step" data-step="3"><b>3</b> Nueva fecha</div>
        <div class="step" data-step="4"><b>4</b> Nueva hora</div>
    </div>

    <div class="cita-layout">
        <div class="card card-pad anim">
            <div class="picked" style="border-color:var(--border);background:var(--bg-sunken)">
                <div class="avatar"><?= e(iniciales($c['paciente'])) ?></div>
                <div><strong><?= e($c['paciente']) ?></strong><small>Dr(a). <?= e($c['medico']) ?> · <?= e($c['especialidad']) ?></small></div>
            </div>
            <?= View::parcial('selector_horario') ?>
            <div class="paso" data-requiere="3,4">
                <label class="field mt"><span>Motivo de la reprogramación *</span>
                    <input class="input<?= clase_error('motivo') ?>" name="motivo" value="<?= e(old('motivo')) ?>" required minlength="3" maxlength="255" placeholder="Ej. El paciente pidió cambiar el día">
                    <?= error_campo('motivo') ?></label>
            </div>
        </div>
        <aside class="card resumen-cita anim">
            <div class="ticket-head"><small>Reprogramar</small><h3><?= e($c['codigo']) ?></h3></div>
            <div class="card-pad">
                <dl>
                    <dt>Actual</dt><dd style="text-decoration:line-through;opacity:.6"><?= e(fecha($c['fecha'])) ?> · <?= hora($c['horainicio']) ?></dd>
                    <dt>Nueva fecha</dt><dd data-res="fecha">—</dd>
                    <dt>Nueva hora</dt><dd data-res="hora">—</dd>
                </dl>
                <button type="submit" class="btn btn-primary btn-block mt" id="btn-confirmar" disabled style="height:48px"><?= icono('reiniciar') ?> Guardar nueva fecha</button>
            </div>
        </aside>
    </div>
</form>
