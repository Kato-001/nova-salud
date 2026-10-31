<?php
/**
 * CU5 · Ficha del médico con su agenda (RF03: consultar su agenda)
 * @var array $m @var array $horarios @var array $bloqueos @var string $fecha @var array $agenda @var array $conteo
 */
$titulo = 'Dr(a). ' . $m['nombre_completo'];
$porDia = [];
foreach ($horarios as $h) {
    $porDia[(int) $h['diasemana']][] = $h;
}
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><a href="/medicos"><?= icono('izquierda', 14) ?> Médicos</a></div>
        <h1>Dr(a). <?= e($m['nombre_completo']) ?></h1>
        <p><?= e($m['especialidades']) ?></p>
    </div>
    <div class="row">
        <?php if (Auth::puede('medicos.gestionar')): ?><a href="<?= e(url('/medicos/editar', ['id' => $m['idmedico']])) ?>" class="btn btn-outline"><?= icono('editar') ?> Editar</a><?php endif; ?>
        <?php if (Auth::puede('citas.gestionar') && $m['estado'] === 'ACTIVO'): ?><a href="<?= e(url('/citas/nueva', ['medico' => $m['idmedico']])) ?>" class="btn btn-primary"><?= icono('calendario-mas') ?> Programar cita</a><?php endif; ?>
    </div>
</div>

<div class="ficha">
    <div class="stack" data-escalonar>
        <div class="card">
            <div class="ficha-head">
                <div class="avatar lg"><?= e(iniciales($m['nombre_completo'])) ?></div>
                <h2><?= e($m['nombre_completo']) ?></h2>
                <p class="mono"><?= e($m['codigo']) ?> · Reg. <?= e($m['registroprofesional']) ?></p>
                <?= badge_estado($m['estado']) ?>
            </div>
            <div class="kv-list">
                <div><span><?= icono('telefono', 16) ?> Teléfono</span><span><?= e($m['telefono'] ?: '—') ?></span></div>
                <div><span><?= icono('correo', 16) ?> Correo</span><span><?= e($m['email']) ?></span></div>
                <div><span><?= icono('usuario', 16) ?> Usuario</span><span class="mono"><?= e($m['username']) ?></span></div>
            </div>
        </div>
        <div class="card">
            <div class="card-head"><h2>Horario de atención</h2>
                <?php if (Auth::puede('horarios.gestionar')): ?><a href="<?= e(url('/horarios', ['medico' => $m['idmedico']])) ?>" class="btn btn-ghost btn-sm">Editar</a><?php endif; ?></div>
            <div class="kv-list">
                <?php foreach (DIAS_SEMANA as $n => $dia): if (empty($porDia[$n])) continue; ?>
                    <div><span><?= e($dia) ?></span><span>
                        <?php foreach ($porDia[$n] as $h): ?>
                            <span class="mono" style="<?= $h['estado'] !== 'ACTIVO' ? 'text-decoration:line-through;opacity:.5' : '' ?>"><?= hora($h['horainicio']) ?>–<?= hora($h['horafin']) ?></span><br>
                        <?php endforeach; ?>
                    </span></div>
                <?php endforeach; ?>
                <?php if (!$horarios): ?><div><span>Sin horarios definidos</span><span></span></div><?php endif; ?>
            </div>
            <?php if ($bloqueos): ?>
                <div class="card-pad" style="border-top:1px solid var(--border)">
                    <div class="section-title" style="margin-top:0">Próximos bloqueos</div>
                    <?php foreach ($bloqueos as $b): ?>
                        <div style="font-size:13.5px;margin-bottom:6px"><span class="badge badge-warn"><?= e($b['tipo']) ?></span> <?= e(fecha($b['fecha'])) ?> <?= $b['horainicio'] ? hora($b['horainicio']) . '–' . hora($b['horafin']) : '(todo el día)' ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="stack">
        <div class="card card-pad anim">
            <div class="mini-stats">
                <?php foreach (['PROGRAMADA' => 'Programadas', 'ATENDIDA' => 'Atendidas', 'CANCELADA' => 'Canceladas', 'INASISTENCIA' => 'Inasistencias'] as $k => $l): ?>
                    <div><b data-contar="<?= $conteo[$k] + ($k === 'PROGRAMADA' ? $conteo['CONFIRMADA'] : 0) ?>">0</b><small><?= $l ?></small></div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card anim">
            <form class="card-head" method="get">
                <input type="hidden" name="id" value="<?= $m['idmedico'] ?>">
                <h2>Agenda del <?= e(fecha_larga($fecha)) ?></h2>
                <div class="date-nav">
                    <a class="btn btn-ghost btn-icon btn-sm" href="<?= e(url('/medicos/ver', ['id' => $m['idmedico'], 'fecha' => date('Y-m-d', strtotime("$fecha -1 day"))])) ?>" aria-label="Día anterior"><?= icono('izquierda', 16) ?></a>
                    <input class="input" type="date" name="fecha" value="<?= e($fecha) ?>" data-autoenviar style="height:36px" aria-label="Fecha">
                    <a class="btn btn-ghost btn-icon btn-sm" href="<?= e(url('/medicos/ver', ['id' => $m['idmedico'], 'fecha' => date('Y-m-d', strtotime("$fecha +1 day"))])) ?>" aria-label="Día siguiente"><?= icono('derecha', 16) ?></a>
                </div>
            </form>
            <div class="card-pad agenda-col">
                <?php if (!$agenda): ?><div class="empty" style="padding:30px">Sin citas para este día.</div><?php endif; ?>
                <?php foreach ($agenda as $c): ?>
                    <a href="<?= e(url('/citas/ver', ['id' => $c['idcita']])) ?>" class="agenda-item <?= e($c['codigoestado']) ?>">
                        <div class="h"><?= hora($c['horainicio']) ?><small><?= hora($c['horafin']) ?></small></div>
                        <div style="min-width:0"><strong><?= e($c['paciente']) ?></strong><div class="sub"><span class="mono"><?= e($c['codigo']) ?></span> · <?= e($c['especialidad']) ?></div></div>
                        <?= badge_cita($c['codigoestado']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
