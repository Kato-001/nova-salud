<?php
/**
 * CU15 · Registrar consulta médica · CU17 · Registrar tratamiento
 * @var array $c @var array $paciente @var array $anteriores @var array $medicamentos @var array $catalogo
 */
$titulo = 'Atender ' . $c['codigo'];
$old = $GLOBALS['_old'];
$tratamientos = $old['tratamientos'] ?? [[]];
$opciones = fn($sel) => implode('', array_map(fn($m) => '<option value="' . $m['idmedicamento'] . '"' . ((string) $sel === (string) $m['idmedicamento'] ? ' selected' : '') . '>' . e($m['nombre'] . ' · ' . $m['presentacion']) . '</option>', $medicamentos));
$fila = fn($i, $t) => '<div class="trat-row">
    <select class="select" name="tratamientos[' . $i . '][idmedicamento]" aria-label="Medicamento"><option value="">Medicamento…</option>' . $opciones($t['idmedicamento'] ?? '') . '</select>
    <input class="input" name="tratamientos[' . $i . '][dosis]" value="' . e($t['dosis'] ?? '') . '" maxlength="100" placeholder="Dosis (1 tableta)" aria-label="Dosis">
    <input class="input" name="tratamientos[' . $i . '][frecuencia]" value="' . e($t['frecuencia'] ?? '') . '" maxlength="100" placeholder="Cada 8 horas" aria-label="Frecuencia">
    <input class="input" name="tratamientos[' . $i . '][duracion]" value="' . e($t['duracion'] ?? '') . '" maxlength="100" placeholder="5 días" aria-label="Duración">
    <input class="input" name="tratamientos[' . $i . '][indicaciones]" value="' . e($t['indicaciones'] ?? '') . '" maxlength="500" placeholder="Indicaciones" aria-label="Indicaciones">
    <button type="button" class="btn btn-ghost btn-icon btn-danger-ghost" data-quitar-fila aria-label="Quitar">' . icono('cerrar', 16) . '</button>
</div>';
$estudiosSol = $old['estudios'] ?? [];
$filaEstudio = fn($i, $e) => '<div class="trat-row" style="grid-template-columns:1.5fr 2fr auto">
    <select class="select" name="estudios[' . $i . '][idcatalogo]" aria-label="Estudio"><option value="">Estudio…</option>'
    . implode('', array_map(fn($c) => '<option value="' . $c['idcatalogo'] . '"' . ((string) ($e['idcatalogo'] ?? '') === (string) $c['idcatalogo'] ? ' selected' : '') . '>' . e($c['nombre'] . ' · ' . $c['categoria']) . '</option>', $catalogo))
    . '</select>
    <input class="input" name="estudios[' . $i . '][indicaciones]" value="' . e($e['indicaciones'] ?? '') . '" maxlength="500" placeholder="Indicaciones (ej. en ayunas)" aria-label="Indicaciones">
    <button type="button" class="btn btn-ghost btn-icon btn-danger-ghost" data-quitar-fila aria-label="Quitar">' . icono('cerrar', 16) . '</button>
</div>';
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><a href="<?= e(url('/citas/ver', ['id' => $c['idcita']])) ?>"><?= icono('izquierda', 14) ?> Cita <?= e($c['codigo']) ?></a></div>
        <h1>Consulta de <?= e($c['paciente']) ?></h1>
        <p><?= e($c['especialidad']) ?> · <?= e(fecha($c['fecha'])) ?> <?= hora($c['horainicio']) ?> · Motivo: <?= e($c['motivo'] ?: 'no indicado') ?></p>
    </div>
</div>

<div class="cita-layout">
    <form method="post" action="/atencion/guardar" class="card card-pad anim" data-validar>
        <?= csrf_field() ?><input type="hidden" name="idcita" value="<?= $c['idcita'] ?>">
        <div class="section-title" style="margin-top:0"><?= icono('actividad', 14) ?> Signos vitales</div>
        <div class="grid-3">
            <label class="field"><span>Presión arterial</span><input class="input<?= clase_error('presionarterial') ?>" name="presionarterial" value="<?= e(old('presionarterial')) ?>" placeholder="120/80" pattern="\d{2,3}/\d{2,3}" data-msg="Formato 120/80." maxlength="7"><?= error_campo('presionarterial') ?></label>
            <label class="field"><span>Temperatura (°C)</span><input class="input<?= clase_error('temperatura') ?>" type="number" step="0.1" min="30" max="45" name="temperatura" value="<?= e(old('temperatura')) ?>"><?= error_campo('temperatura') ?></label>
            <label class="field"><span>Frecuencia cardiaca (lpm)</span><input class="input<?= clase_error('frecuenciacardiaca') ?>" type="number" min="20" max="250" name="frecuenciacardiaca" value="<?= e(old('frecuenciacardiaca')) ?>"><?= error_campo('frecuenciacardiaca') ?></label>
            <label class="field"><span>Peso (kg)</span><input class="input<?= clase_error('peso') ?>" type="number" step="0.1" min="0.5" max="400" name="peso" value="<?= e(old('peso')) ?>"><?= error_campo('peso') ?></label>
            <label class="field"><span>Talla (cm)</span><input class="input<?= clase_error('talla') ?>" type="number" step="0.1" min="20" max="250" name="talla" value="<?= e(old('talla')) ?>"><?= error_campo('talla') ?></label>
            <label class="field"><span>Motivo de consulta</span><input class="input" name="motivo" value="<?= e(old('motivo', $c['motivo'] ?? '')) ?>" maxlength="255"></label>
        </div>

        <div class="section-title mt"><?= icono('estetoscopio', 14) ?> Evaluación</div>
        <div class="stack">
            <label class="field"><span>Examen físico</span><textarea class="textarea" name="examenfisico" rows="3" maxlength="2000"><?= e(old('examenfisico')) ?></textarea></label>
            <label class="field"><span>Diagnóstico *</span><textarea class="textarea<?= clase_error('diagnostico') ?>" name="diagnostico" rows="2" required minlength="3" maxlength="2000"><?= e(old('diagnostico')) ?></textarea><?= error_campo('diagnostico') ?></label>
            <label class="field"><span>Observaciones / indicaciones generales</span><textarea class="textarea" name="observaciones" rows="2" maxlength="2000"><?= e(old('observaciones')) ?></textarea></label>
        </div>

        <div class="section-title mt"><?= icono('pildora', 14) ?> Tratamiento</div>
        <div id="tratamientos"><?php foreach ($tratamientos as $i => $t) echo $fila($i, (array) $t); ?></div>
        <?= error_campo('tratamientos') ?>
        <template id="tpl-tratamiento"><?= $fila('__i__', []) ?></template>
        <button type="button" class="btn btn-ghost btn-sm" data-agregar="tratamientos" data-plantilla="tpl-tratamiento"><?= icono('mas', 15) ?> Agregar medicamento</button>

        <div class="section-title mt"><?= icono('portapapeles', 14) ?> Estudios a solicitar</div>
        <div id="estudios"><?php foreach ($estudiosSol as $i => $es) echo $filaEstudio($i, (array) $es); ?></div>
        <?= error_campo('estudios') ?>
        <template id="tpl-estudio"><?= $filaEstudio('__i__', []) ?></template>
        <button type="button" class="btn btn-ghost btn-sm" data-agregar="estudios" data-plantilla="tpl-estudio"><?= icono('mas', 15) ?> Solicitar estudio</button>

        <div class="row mt" style="justify-content:flex-end">
            <a href="<?= e(url('/citas/ver', ['id' => $c['idcita']])) ?>" class="btn btn-ghost">Cancelar</a>
            <button class="btn btn-primary"><?= icono('ok-circulo') ?> Guardar consulta y marcar atendida</button>
        </div>
    </form>

    <aside class="stack">
        <div class="card anim">
            <div class="card-head"><h2>Paciente</h2></div>
            <div class="kv-list">
                <div><span>Edad</span><span><?= e(edad($paciente['fechanacimiento'])) ?></span></div>
                <div><span>Sexo</span><span><?= e(Paciente::SEXOS[$paciente['sexo']] ?? '') ?></span></div>
                <div><span>Sangre</span><span><?= e($paciente['tiposangre'] ?: '—') ?></span></div>
                <div><span style="color:var(--danger)"><?= icono('alerta', 15) ?> Alergias</span><span><?= e($paciente['alergias'] ?: '—') ?></span></div>
                <div><span>Crónicas</span><span><?= e($paciente['enfermedadescronicas'] ?: '—') ?></span></div>
                <div><span>Medicación</span><span><?= e($paciente['medicacionhabitual'] ?: '—') ?></span></div>
            </div>
        </div>
        <div class="card anim">
            <div class="card-head"><h2>Consultas anteriores</h2></div>
            <?= View::parcial('consultas', ['consultas' => $anteriores]) ?>
        </div>
    </aside>
</div>
