<?php
/**
 * Portal · Seguimiento de mis citas
 * @var array $p @var array $citas @var string $filtro @var int $horasCancelacion
 */
$titulo = 'Mis citas';
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><?= icono('calendario-ok', 14) ?> Mi salud</div>
        <h1>Mis citas</h1>
        <p>Puede cancelar una cita hasta <?= $horasCancelacion ?> hora(s) antes. Para reprogramar, cancele y reserve otra, o comuníquese con la clínica.</p>
    </div>
    <a href="/portal/reservar" class="btn btn-primary"><?= icono('calendario-mas') ?> Reservar cita</a>
</div>

<div class="pill-tabs mb anim">
    <?php foreach (['proximas' => 'Próximas', 'atendidas' => 'Atendidas', 'canceladas' => 'Canceladas / no asistí', 'todas' => 'Todas'] as $k => $l): ?>
        <a href="<?= e(url('/portal/citas', ['ver' => $k])) ?>" class="<?= $filtro === $k ? 'on' : '' ?>"><?= $l ?></a>
    <?php endforeach; ?>
</div>

<div class="card anim">
    <?php if (!$citas): ?><div class="empty"><?= icono('calendario', 36) ?><div><strong>No hay citas en esta sección</strong></div></div><?php endif; ?>
    <?php foreach ($citas as $c):
        $t = strtotime($c['fecha']);
        $cancelable = in_array($c['codigoestado'], ['PROGRAMADA', 'CONFIRMADA'], true) && strtotime($c['fecha'] . ' ' . $c['horainicio']) - time() >= $horasCancelacion * 3600; ?>
        <div class="cita-card">
            <div class="fecha-box"><b><?= date('j', $t) ?></b><small><?= MESES[(int) date('n', $t) - 1] ?> <?= date('y', $t) ?></small></div>
            <div style="min-width:0">
                <strong><?= e($c['especialidad']) ?> · <?= hora($c['horainicio']) ?>–<?= hora($c['horafin']) ?></strong>
                <div class="faint" style="font-size:13px">Dr(a). <?= e($c['medico']) ?> · <?= $c['modalidad'] === 'VIRTUAL' ? 'Virtual' : 'Presencial' ?> · <span class="mono"><?= e($c['codigo']) ?></span></div>
                <?php if ($c['motivo']): ?><div class="soft" style="font-size:13px"><?= e($c['motivo']) ?></div><?php endif; ?>
            </div>
            <div class="row">
                <?= badge_cita($c['codigoestado']) ?>
                <?php if ($cancelable): ?>
                    <form method="post" action="/portal/citas/cancelar" class="inline" data-confirm="Cancelar mi cita <?= e($c['codigo']) ?>"
                          data-confirm-texto="<?= e(fecha_larga($c['fecha']) . ' a las ' . hora($c['horainicio'])) ?>. El horario quedará libre para otro paciente." data-confirm-ok="Cancelar cita" data-motivo="¿Por qué cancela? *" data-peligro>
                        <?= csrf_field() ?><input type="hidden" name="idcita" value="<?= $c['idcita'] ?>">
                        <button class="btn btn-ghost btn-sm btn-danger-ghost">Cancelar</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
