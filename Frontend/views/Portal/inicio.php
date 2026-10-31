<?php
/**
 * Portal del paciente · Inicio
 * @var array $p @var array $proximas @var array $notificaciones @var int $noLeidas @var array $conteo
 */
$titulo = 'Inicio';
?>
<div class="hero-portal anim">
    <div class="blob" style="width:320px;height:320px;background:#0d9488;top:-140px;left:-80px"></div>
    <div class="blob" style="width:280px;height:280px;background:#4f46e5;bottom:-160px;right:-40px;animation-delay:-5s"></div>
    <div class="eyebrow" style="color:#5eead4"><?= icono('pulso', 14) ?> Mi salud</div>
    <h1>Hola, <?= e($p['nombres']) ?></h1>
    <p><?= $proximas ? 'Su próxima cita es el ' . e(fecha_larga($proximas[0]['fecha'])) . ' a las ' . hora($proximas[0]['horainicio']) . ' con Dr(a). ' . e($proximas[0]['medico']) . '.' : 'No tiene citas próximas. Reserve una en pocos pasos.' ?></p>
    <div class="row">
        <a href="/portal/reservar" class="btn btn-primary"><?= icono('calendario-mas') ?> Reservar cita</a>
        <a href="/portal/citas" class="btn btn-outline">Ver mis citas</a>
    </div>
</div>

<div class="stats" data-escalonar>
    <?php foreach ([['Próximas', $conteo['PROGRAMADA'] + $conteo['CONFIRMADA'], 'calendario', '#0d9488'], ['Atendidas', $conteo['ATENDIDA'], 'ok-circulo', '#059669'],
                    ['Canceladas', $conteo['CANCELADA'], 'x-circulo', '#64748b'], ['Inasistencias', $conteo['INASISTENCIA'], 'prohibido', '#d97706']] as [$l, $v, $ic, $c]): ?>
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
        <div class="card-head"><h2>Próximas citas</h2><a href="/portal/citas" class="btn btn-ghost btn-sm">Ver todas <?= icono('flecha', 15) ?></a></div>
        <?php if (!$proximas): ?>
            <div class="empty"><?= icono('calendario', 36) ?><div><strong>Sin citas próximas</strong></div><a href="/portal/reservar" class="btn btn-outline btn-sm mt">Reservar ahora</a></div>
        <?php endif; ?>
        <?php foreach ($proximas as $c): $t = strtotime($c['fecha']); ?>
            <div class="cita-card">
                <div class="fecha-box"><b><?= date('j', $t) ?></b><small><?= MESES[(int) date('n', $t) - 1] ?></small></div>
                <div style="min-width:0"><strong><?= e($c['especialidad']) ?> · <?= hora($c['horainicio']) ?></strong><div class="faint" style="font-size:13px">Dr(a). <?= e($c['medico']) ?> · <span class="mono"><?= e($c['codigo']) ?></span></div></div>
                <?= badge_cita($c['codigoestado']) ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card anim">
        <div class="card-head">
            <h2 class="row"><?= icono('campana', 18) ?> Recordatorios y avisos <?php if ($noLeidas): ?><span class="badge badge-danger" data-no-leidas><?= $noLeidas ?> nuevo(s)</span><?php endif; ?></h2>
            <?php if ($noLeidas): ?><button type="button" class="btn btn-ghost btn-sm" id="marcar-leidas" data-csrf="<?= e(Csrf::token()) ?>">Marcar leídos</button><?php endif; ?>
        </div>
        <?php if (!$notificaciones): ?><div class="empty" style="padding:30px">No tiene avisos.</div><?php endif; ?>
        <?php foreach ($notificaciones as $n): ?>
            <div class="notif <?= $n['leidaen'] ? '' : 'nueva' ?>">
                <div class="feed-ico" style="color:var(--primary);background:var(--primary-soft)"><?= icono(['RECORDATORIO' => 'reloj', 'CANCELACION' => 'x-circulo', 'RESULTADO' => 'portapapeles'][$n['tipo']] ?? 'campana', 17) ?></div>
                <div><p><?= e($n['mensaje']) ?></p><small><?= e(hace_cuanto($n['programadapara'] ?? $n['enviadaen'])) ?></small></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
