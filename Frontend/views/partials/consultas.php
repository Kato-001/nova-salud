<?php
/**
 * Lista de consultas con signos vitales y tratamientos.
 * @var array $consultas
 */
?>
<?php if (!$consultas): ?><div class="empty" style="padding:30px">Aún no hay consultas registradas.</div><?php endif; ?>
<?php foreach ($consultas as $co): ?>
    <div class="consulta-card">
        <div class="row-between">
            <div><strong><?= e($co['diagnostico']) ?></strong>
                <div class="faint" style="font-size:13px"><?= e(fecha_hora($co['fechahora'])) ?> · Dr(a). <?= e($co['medico']) ?> · <?= e($co['especialidad']) ?></div></div>
            <a class="mono" href="<?= e(url('/citas/ver', ['id' => $co['idcita']])) ?>" style="font-size:13px"><?= e($co['codigo']) ?></a>
        </div>
        <div class="signos">
            <?php if ($co['presionarterial']): ?><span>PA <?= e($co['presionarterial']) ?></span><?php endif; ?>
            <?php if ($co['temperatura']): ?><span>T° <?= e($co['temperatura']) ?> °C</span><?php endif; ?>
            <?php if ($co['frecuenciacardiaca']): ?><span>FC <?= (int) $co['frecuenciacardiaca'] ?> lpm</span><?php endif; ?>
            <?php if ($co['peso']): ?><span>Peso <?= e((float) $co['peso']) ?> kg</span><?php endif; ?>
            <?php if ($co['talla']): ?><span>Talla <?= e((float) $co['talla']) ?> cm</span><?php endif; ?>
        </div>
        <?php if ($co['observaciones']): ?><p class="soft m0" style="font-size:13.5px"><?= e($co['observaciones']) ?></p><?php endif; ?>
        <?php foreach ($co['tratamientos'] as $t): ?>
            <div class="trat"><?= icono('pildora', 16) ?><div><strong><?= e($t['medicamento']) ?></strong> <span class="faint"><?= e($t['presentacion']) ?></span><br>
                <?= e($t['dosis']) ?> · <?= e($t['frecuencia']) ?> · <?= e($t['duracion']) ?><?= $t['indicaciones'] ? ' · ' . e($t['indicaciones']) : '' ?></div></div>
        <?php endforeach; ?>
        <?php foreach ($co['estudios'] ?? [] as $es): [$nE, $cE] = Estudio::ESTADOS[$es['estado']]; ?>
            <div class="trat"><?= icono('portapapeles', 16) ?><div><strong><?= e($es['estudio']) ?></strong> <span class="badge <?= $cE ?>" style="height:22px"><?= e($nE) ?></span>
                <?php if ($es['resultado']): ?><br><span class="soft"><?= e($es['resultado']) ?></span><?php endif; ?>
                <?php if ($es['archivoresultado']): ?><br><a href="<?= e($es['archivoresultado']) ?>" target="_blank" rel="noopener">Ver archivo</a><?php endif; ?></div></div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
