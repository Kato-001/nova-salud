<?php
/**
 * Portal · CU19 Consultar historial médico y resultados de estudios
 * @var array $p @var array $consultas @var array $estudios
 */
$titulo = 'Historial médico';
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><?= icono('historial', 14) ?> Mi salud</div>
        <h1>Mi historial médico</h1>
        <p>Consultas atendidas, diagnósticos, tratamientos y resultados de sus estudios.</p>
    </div>
    <button type="button" class="btn btn-outline" onclick="window.print()"><?= icono('imprimir') ?> Imprimir</button>
</div>

<div class="ficha">
    <div class="stack">
        <div class="card anim">
            <div class="card-head"><h2>Resumen</h2></div>
            <div class="kv-list">
                <div><span><?= icono('gota', 16) ?> Sangre</span><span><?= e($p['tiposangre'] ?: '—') ?></span></div>
                <div><span><?= icono('alerta', 16) ?> Alergias</span><span><?= e($p['alergias'] ?: '—') ?></span></div>
                <div><span>Crónicas</span><span><?= e($p['enfermedadescronicas'] ?: '—') ?></span></div>
                <div><span>Medicación</span><span><?= e($p['medicacionhabitual'] ?: '—') ?></span></div>
                <div><span>Consultas</span><span><?= count($consultas) ?></span></div>
                <div><span>Estudios</span><span><?= count($estudios) ?></span></div>
            </div>
        </div>
        <div class="card anim">
            <div class="card-head"><h2>Resultados de estudios</h2></div>
            <?php if (!$estudios): ?><div class="empty" style="padding:30px">No tiene estudios solicitados.</div><?php endif; ?>
            <?php foreach ($estudios as $es): [$nE, $cE] = Estudio::ESTADOS[$es['estado']]; ?>
                <div class="notif">
                    <div class="feed-ico" style="color:var(--primary);background:var(--primary-soft)"><?= icono('portapapeles', 17) ?></div>
                    <div style="min-width:0">
                        <p><strong><?= e($es['estudio']) ?></strong> <span class="badge <?= $cE ?>" style="height:22px"><?= e($nE) ?></span></p>
                        <?php if ($es['resultado']): ?><p class="soft" style="font-size:13.5px"><?= e($es['resultado']) ?></p><?php endif; ?>
                        <small>Solicitado el <?= e(fecha($es['fechasolicitud'])) ?> por Dr(a). <?= e($es['medico']) ?><?= $es['fecharesultado'] ? ' · resultado del ' . e(fecha($es['fecharesultado'])) : '' ?></small>
                        <?php if ($es['archivoresultado']): ?><br><a href="<?= e($es['archivoresultado']) ?>" target="_blank" rel="noopener" style="font-size:13px">Ver archivo</a><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card anim">
        <div class="card-head"><h2>Consultas</h2></div>
        <?= View::parcial('consultas', ['consultas' => $consultas]) ?>
    </div>
</div>
