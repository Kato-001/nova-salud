<?php
/**
 * Portal · Reservar cita (búsqueda de médico por especialidad y disponibilidad)
 * @var array $p @var array $especialidades @var ?int $preEspecialidad @var ?int $preMedico @var int $duracion @var int $maxDias
 */
$titulo = 'Reservar cita';
$scripts = ['citas.js'];
$espSel = old('idespecialidad', (string) ($preEspecialidad ?? ''));
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><?= icono('calendario-mas', 14) ?> Mi salud</div>
        <h1>Reservar una cita</h1>
        <p>Elija la especialidad, el médico y un horario libre. Recibirá un recordatorio antes de su cita.</p>
    </div>
</div>

<form method="post" action="/portal/reservar" id="form-cita" data-modo="portal" data-max-dias="<?= $maxDias ?>" data-pre-medico="<?= e(old('idmedico', (string) ($preMedico ?? ''))) ?>">
    <?= csrf_field() ?>
    <div class="steps anim">
        <div class="step done" data-step="1"><b>1</b> Sus datos</div>
        <div class="step" data-step="2"><b>2</b> Especialidad y médico</div>
        <div class="step" data-step="3"><b>3</b> Fecha</div>
        <div class="step" data-step="4"><b>4</b> Hora</div>
    </div>
    <div class="cita-layout">
        <div class="card card-pad anim">
            <div class="section-title" style="margin-top:0"><?= icono('estetoscopio', 14) ?> Especialidad y médico</div>
            <div class="grid-2">
                <label class="field"><span>Especialidad *</span>
                    <select class="select" name="idespecialidad" required autofocus>
                        <option value="">¿Qué especialidad necesita?</option>
                        <?php foreach ($especialidades as $esp): ?><option value="<?= $esp['idespecialidad'] ?>" <?= $espSel === (string) $esp['idespecialidad'] ? 'selected' : '' ?>><?= e($esp['nombre']) ?></option><?php endforeach; ?>
                    </select></label>
                <label class="field"><span>Médico *</span>
                    <select class="select" name="idmedico" required disabled><option value="">Primero elija la especialidad</option></select></label>
            </div>
            <?= View::parcial('selector_horario') ?>
            <div class="paso" data-requiere="2,3,4">
                <div class="grid-2 mt">
                    <label class="field"><span>Modalidad</span>
                        <select class="select" name="modalidad"><option value="PRESENCIAL">Presencial</option><option value="VIRTUAL">Virtual</option></select></label>
                    <label class="field"><span>Motivo (opcional)</span><input class="input" name="motivo" maxlength="255" value="<?= e(old('motivo')) ?>" placeholder="Cuéntenos brevemente el motivo"></label>
                </div>
            </div>
        </div>
        <aside class="card resumen-cita anim">
            <div class="ticket-head"><small>Mi reserva</small><h3><?= e($p['nombres']) ?></h3></div>
            <div class="card-pad">
                <dl>
                    <dt>Especialidad</dt><dd data-res="especialidad">—</dd>
                    <dt>Médico</dt><dd data-res="medico">—</dd>
                    <dt>Fecha</dt><dd data-res="fecha">—</dd>
                    <dt>Horario</dt><dd data-res="hora">—</dd>
                </dl>
                <button type="submit" class="btn btn-primary btn-block mt" id="btn-confirmar" disabled style="height:48px"><?= icono('calendario-ok') ?> Confirmar reserva</button>
                <p class="faint" style="font-size:12.5px;margin:12px 0 0;text-align:center">Cada cita dura <?= $duracion ?> minutos.</p>
            </div>
        </aside>
    </div>
</form>
