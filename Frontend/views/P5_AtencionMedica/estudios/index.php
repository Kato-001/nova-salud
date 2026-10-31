<?php
/**
 * CU18 · Bandeja de estudios médicos solicitados
 * @var array $estudios @var array $conteo @var array $f @var Paginador $pag
 */
$titulo = 'Estudios médicos';
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><?= icono('portapapeles', 14) ?> Atención médica</div>
        <h1>Estudios médicos</h1>
        <p>Estudios que los médicos solicitan en la consulta. Al registrar el resultado, el paciente recibe un aviso en su portal.</p>
    </div>
    <?php if (Auth::puede('estudios.catalogo')): ?><a href="/estudios/catalogo" class="btn btn-outline"><?= icono('documento') ?> Catálogo de estudios</a><?php endif; ?>
</div>

<div class="card anim">
    <form class="toolbar" method="get">
        <div class="input-icon"><?= icono('buscar') ?><input class="input" type="search" name="q" value="<?= e($f['q']) ?>" placeholder="CI, paciente o nombre del estudio" aria-label="Buscar"></div>
        <input type="hidden" name="estado" value="<?= e($f['estado']) ?>">
        <button class="btn btn-outline" data-sin-carga="1">Buscar</button>
    </form>
    <div style="padding:14px 20px;border-bottom:1px solid var(--border);overflow-x:auto">
        <div class="pill-tabs">
            <a href="<?= e(url('/estudios', ['q' => $f['q']])) ?>" class="<?= $f['estado'] === '' ? 'on' : '' ?>">Todos <span class="n"><?= array_sum($conteo) ?></span></a>
            <?php foreach (Estudio::ESTADOS as $cod => [$nombre]): ?>
                <a href="<?= e(url('/estudios', ['q' => $f['q'], 'estado' => $cod])) ?>" class="<?= $f['estado'] === $cod ? 'on' : '' ?>"><?= e($nombre) ?> <span class="n"><?= $conteo[$cod] ?></span></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Estudio</th><th>Paciente</th><th>Solicitado por</th><th>Solicitud</th><th>Estado</th><th style="text-align:right"></th></tr></thead>
            <tbody>
            <?php foreach ($estudios as $es): [$nEstado, $cEstado] = Estudio::ESTADOS[$es['estado']]; $abierto = in_array($es['estado'], ['SOLICITADO', 'EN_PROCESO'], true); ?>
                <tr>
                    <td><strong><?= e($es['estudio']) ?></strong><div class="faint" style="font-size:12.5px"><?= e($es['categoria']) ?><?= $es['indicaciones'] ? ' · ' . e($es['indicaciones']) : '' ?></div></td>
                    <td><?= e($es['paciente']) ?><div class="faint" style="font-size:12.5px">CI <?= e($es['ci']) ?></div></td>
                    <td>Dr(a). <?= e($es['medico']) ?><div class="mono faint" style="font-size:12px"><a href="<?= e(url('/citas/ver', ['id' => $es['idcita']])) ?>"><?= e($es['codigo_cita']) ?></a></div></td>
                    <td class="soft" style="white-space:nowrap"><?= e(fecha($es['fechasolicitud'])) ?></td>
                    <td><span class="badge <?= $cEstado ?>"><span class="dot"></span><?= e($nEstado) ?></span></td>
                    <td><div class="actions">
                        <a class="btn btn-<?= $abierto ? 'primary' : 'ghost' ?> btn-sm" href="<?= e(url('/estudios/resultado', ['id' => $es['idestudio']])) ?>"><?= $abierto ? icono('editar', 15) . ' Registrar resultado' : icono('ver', 15) . ' Ver' ?></a>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$estudios): ?><div class="empty"><?= icono('portapapeles', 36) ?><div><strong>No hay estudios con esos filtros</strong></div></div><?php endif; ?>
    </div>
    <?= View::parcial('paginacion', ['pag' => $pag]) ?>
</div>
