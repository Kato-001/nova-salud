<?php
/**
 * CU10 · Reservar ficha (RF06): paciente → especialidad → médico → fecha → hora
 * @var ?array $paciente @var array $especialidades @var ?int $preEspecialidad @var ?int $preMedico @var int $duracion @var int $maxDias
 */
$titulo = 'Programar cita';
$scripts = ['citas.js'];
$espSel = old('idespecialidad', (string) ($preEspecialidad ?? ''));
// Si se llega desde la ficha de un médico, se preselecciona su primera especialidad
if (!$espSel && $preMedico) {
    $espSel = (string) (Database::valor('select min(idespecialidad) from medicoespecialidad where idmedico = :m', ['m' => $preMedico]) ?? '');
}
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><a href="/citas"><?= icono('izquierda', 14) ?> Citas</a></div>
        <h1>Programar cita</h1>
        <p>Solo se muestran los horarios libres del médico. Al guardar, el sistema vuelve a verificar la disponibilidad.</p>
    </div>
</div>

<form method="post" action="/citas/guardar" id="form-cita" data-modo="nueva" data-max-dias="<?= $maxDias ?>" data-pre-medico="<?= e(old('idmedico', (string) ($preMedico ?? ''))) ?>">
    <?= csrf_field() ?>
    <div class="steps anim">
        <div class="step" data-step="1"><b>1</b> Paciente</div>
        <div class="step" data-step="2"><b>2</b> Especialidad y médico</div>
        <div class="step" data-step="3"><b>3</b> Fecha</div>
        <div class="step" data-step="4"><b>4</b> Hora</div>
    </div>

    <div class="cita-layout">
        <div class="card card-pad anim">
            <div class="section-title" style="margin-top:0"><?= icono('usuario', 14) ?> Paciente</div>
            <input type="hidden" name="idpaciente" value="<?= e(old('idpaciente', (string) ($paciente['idpaciente'] ?? ''))) ?>">
            <div class="autocomplete" <?= $paciente ? 'hidden' : '' ?>>
                <div class="input-icon"><?= icono('buscar') ?>
                    <input class="input" id="buscar-paciente" placeholder="Escriba el CI, nombre o apellido del paciente…" autocomplete="off" <?= $paciente ? '' : 'autofocus' ?>>
                </div>
                <div class="ac-list" id="ac-lista" role="listbox"></div>
            </div>
            <div class="picked" id="paciente-elegido" <?= $paciente ? '' : 'hidden' ?>>
                <div class="avatar"><?= icono('usuario', 18) ?></div>
                <div><strong data-nombre><?= e($paciente['nombre_completo'] ?? '') ?></strong>
                    <small data-detalle><?= $paciente ? e('CI ' . $paciente['ci'] . ' · ' . $paciente['codigo']) : '' ?></small></div>
                <button type="button" class="btn btn-ghost btn-sm" id="cambiar-paciente">Cambiar</button>
            </div>
            <p class="faint" style="font-size:13px;margin:10px 0 0">¿Paciente nuevo? <a href="/pacientes/nuevo">Regístrelo primero</a>.</p>

            <div class="paso" data-requiere="1">
                <div class="section-title mt"><?= icono('estetoscopio', 14) ?> Especialidad y médico</div>
                <div class="grid-2">
                    <label class="field"><span>Especialidad *</span>
                        <select class="select" name="idespecialidad" required>
                            <option value="">Seleccione…</option>
                            <?php foreach ($especialidades as $esp): ?><option value="<?= $esp['idespecialidad'] ?>" <?= $espSel === (string) $esp['idespecialidad'] ? 'selected' : '' ?>><?= e($esp['nombre']) ?></option><?php endforeach; ?>
                        </select></label>
                    <label class="field"><span>Médico *</span>
                        <select class="select" name="idmedico" required disabled><option value="">Primero elija la especialidad</option></select></label>
                </div>
            </div>

            <?= View::parcial('selector_horario') ?>

            <div class="paso" data-requiere="1,2,3,4">
                <div class="section-title mt"><?= icono('documento', 14) ?> Detalles</div>
                <div class="grid-2">
                    <label class="field"><span>Modalidad</span>
                        <select class="select" name="modalidad">
                            <option value="PRESENCIAL">Presencial</option>
                            <option value="VIRTUAL" <?= old('modalidad') === 'VIRTUAL' ? 'selected' : '' ?>>Virtual</option>
                        </select></label>
                    <label class="field"><span>Motivo de la consulta (opcional)</span>
                        <input class="input" name="motivo" value="<?= e(old('motivo')) ?>" maxlength="255" placeholder="Ej. Control general"></label>
                </div>
            </div>
        </div>

        <aside class="card resumen-cita anim">
            <div class="ticket-head"><small>Nueva cita</small><h3>CM-····</h3></div>
            <div class="card-pad">
                <dl>
                    <dt>Paciente</dt><dd data-res="paciente">—</dd>
                    <dt>Especialidad</dt><dd data-res="especialidad">—</dd>
                    <dt>Médico</dt><dd data-res="medico">—</dd>
                    <dt>Fecha</dt><dd data-res="fecha">—</dd>
                    <dt>Horario</dt><dd data-res="hora">—</dd>
                </dl>
                <button type="submit" class="btn btn-primary btn-block mt" id="btn-confirmar" disabled style="height:48px"><?= icono('calendario-ok') ?> Confirmar cita</button>
                <p class="faint" style="font-size:12.5px;margin:12px 0 0;text-align:center">Duración de cada cita: <?= $duracion ?> minutos</p>
            </div>
        </aside>
    </div>
</form>
