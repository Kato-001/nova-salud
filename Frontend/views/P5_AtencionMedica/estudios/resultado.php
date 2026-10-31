<?php
/**
 * CU18 · Registrar resultado de un estudio (registrarResultado / adjuntarArchivo)
 * @var array $e
 */
$titulo = 'Estudio · ' . $e['estudio'];
$cerrado = in_array($e['estado'], ['COMPLETADO', 'CANCELADO'], true);
[$nEstado, $cEstado] = Estudio::ESTADOS[$e['estado']];
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><a href="/estudios"><?= icono('izquierda', 14) ?> Estudios médicos</a></div>
        <h1 class="row"><?= e($e['estudio']) ?> <span class="badge <?= $cEstado ?>"><span class="dot"></span><?= e($nEstado) ?></span></h1>
        <p><?= e($e['paciente']) ?> · CI <?= e($e['ci']) ?> · solicitado el <?= e(fecha_hora($e['fechasolicitud'])) ?></p>
    </div>
</div>

<div class="cita-layout">
    <?php if ($cerrado): ?>
        <div class="card card-pad anim">
            <div class="section-title" style="margin-top:0">Resultado</div>
            <p style="white-space:pre-line;margin-top:0"><?= e($e['resultado'] ?: 'Sin resultado registrado.') ?></p>
            <?php if ($e['archivoresultado']): ?><a class="btn btn-outline" href="<?= e($e['archivoresultado']) ?>" target="_blank" rel="noopener"><?= icono('descargar') ?> Ver archivo adjunto</a><?php endif; ?>
            <p class="faint" style="font-size:13px">Registrado el <?= e(fecha_hora($e['fecharesultado'])) ?></p>
        </div>
    <?php else: ?>
        <form method="post" action="/estudios/resultado" class="card card-pad anim" data-validar>
            <?= csrf_field() ?><input type="hidden" name="idestudio" value="<?= $e['idestudio'] ?>">
            <div class="stack">
                <label class="field"><span>Estado *</span>
                    <select class="select<?= clase_error('estado') ?>" name="estado" required>
                        <?php foreach (['EN_PROCESO' => 'En proceso (muestra tomada)', 'COMPLETADO' => 'Con resultado', 'CANCELADO' => 'Cancelado'] as $k => $l): ?>
                            <option value="<?= $k ?>" <?= old('estado', 'COMPLETADO') === $k ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select><?= error_campo('estado') ?></label>
                <label class="field"><span>Resultado</span>
                    <textarea class="textarea<?= clase_error('resultado') ?>" name="resultado" rows="6" maxlength="4000" placeholder="Valores obtenidos, interpretación…"><?= e(old('resultado', $e['resultado'] ?? '')) ?></textarea>
                    <?= error_campo('resultado') ?: '<small>Obligatorio si el estado es "Con resultado".</small>' ?></label>
                <label class="field"><span>Enlace al archivo del resultado (opcional)</span>
                    <input class="input<?= clase_error('archivoresultado') ?>" type="url" name="archivoresultado" value="<?= e(old('archivoresultado', $e['archivoresultado'] ?? '')) ?>" maxlength="500" placeholder="https://…">
                    <?= error_campo('archivoresultado') ?></label>
                <div class="row" style="justify-content:flex-end">
                    <a href="/estudios" class="btn btn-ghost">Volver</a>
                    <button class="btn btn-primary"><?= icono('check') ?> Guardar</button>
                </div>
            </div>
        </form>
    <?php endif; ?>
    <aside class="card anim">
        <div class="card-head"><h2>Solicitud</h2></div>
        <div class="kv-list">
            <div><span>Estudio</span><span><?= e($e['estudio']) ?></span></div>
            <div><span>Categoría</span><span><?= e($e['categoria'] ?: '—') ?></span></div>
            <div><span>Indicaciones</span><span><?= e($e['indicaciones'] ?: '—') ?></span></div>
            <div><span>Médico</span><span>Dr(a). <?= e($e['medico']) ?></span></div>
            <div><span>Cita</span><span class="mono"><a href="<?= e(url('/citas/ver', ['id' => $e['idcita']])) ?>"><?= e($e['codigo_cita']) ?></a></span></div>
            <div><span>Diagnóstico</span><span><?= e($e['diagnostico']) ?></span></div>
        </div>
    </aside>
</div>
