<?php
/**
 * Panel del médico: su agenda de hoy y sus próximas citas.
 * @var array $hoy @var array $proximas @var array $conteo
 */
$titulo = 'Mi panel';
$u = Auth::usuario();
$pendHoy = count(array_filter($hoy, fn($c) => in_array($c['codigoestado'], ['PROGRAMADA', 'CONFIRMADA'], true)));
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><?= icono('estetoscopio', 14) ?> Panel médico</div>
        <h1>Hola, <?= e($u['nombre']) ?></h1>
        <p>Tiene <strong><?= $pendHoy ?></strong> paciente(s) por atender hoy.</p>
    </div>
    <a href="/agenda" class="btn btn-primary"><?= icono('agenda') ?> Ver mi agenda</a>
</div>

<div class="stats" data-escalonar>
    <?php foreach ([['Citas hoy', count($hoy), 'calendario', '#0d9488'], ['Pendientes hoy', $pendHoy, 'reloj', '#4f46e5'],
                    ['Atendidas este mes', $conteo['ATENDIDA'], 'ok-circulo', '#059669'], ['Inasistencias del mes', $conteo['INASISTENCIA'], 'prohibido', '#d97706']] as [$l, $v, $ic, $c]): ?>
        <div class="card stat lift">
            <div class="glow" style="background: <?= $c ?>"></div>
            <div class="ico" style="background: <?= $c ?>1a; color: <?= $c ?>"><?= icono($ic, 22) ?></div>
            <div class="val" data-contar="<?= (int) $v ?>">0</div>
            <div class="lbl"><?= e($l) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="split">
    <div class="card anim">
        <div class="card-head"><h2>Hoy, <?= e(fecha_larga(hoy())) ?></h2></div>
        <div class="card-pad agenda-col">
            <?php if (!$hoy): ?><div class="empty">No tiene citas para hoy.</div><?php endif; ?>
            <?php foreach ($hoy as $c): ?>
                <a href="<?= e(url('/citas/ver', ['id' => $c['idcita']])) ?>" class="agenda-item <?= e($c['codigoestado']) ?>">
                    <div class="h"><?= hora($c['horainicio']) ?><small><?= hora($c['horafin']) ?></small></div>
                    <div style="min-width:0"><strong><?= e($c['paciente']) ?></strong><div class="sub"><?= e($c['especialidad']) ?> · <?= e($c['motivo'] ?: 'Sin motivo indicado') ?></div></div>
                    <?= badge_cita($c['codigoestado']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card anim">
        <div class="card-head"><h2>Próximas citas</h2></div>
        <?php if (!$proximas): ?><div class="empty">No hay citas próximas.</div><?php endif; ?>
        <?php foreach ($proximas as $c): $t = strtotime($c['fecha']); ?>
            <div class="cita-card">
                <div class="fecha-box"><b><?= date('j', $t) ?></b><small><?= MESES[(int) date('n', $t) - 1] ?></small></div>
                <div style="min-width:0"><strong><?= e($c['paciente']) ?></strong><div class="faint" style="font-size:13px"><?= hora($c['horainicio']) ?> · <?= e($c['especialidad']) ?></div></div>
                <?= badge_cita($c['codigoestado']) ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
