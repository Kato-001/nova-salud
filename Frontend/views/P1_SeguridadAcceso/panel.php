<?php
/**
 * Panel del personal administrativo (administrador y recepción).
 * @var array $stats @var array $semana @var array $estados @var array $hoy @var array $actividad @var array $porEsp
 */
$titulo = 'Panel';
$u = Auth::usuario();
$hora = (int) date('G');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
$maxDia = max(1, ...array_map('intval', array_column($semana, 'total')));
$maxEsp = max(1, ...array_map('intval', array_column($porEsp, 'total') ?: [0]));
$tarjetas = [
    ['Citas para hoy', $stats['citas_hoy'], 'calendario', '#0d9488', '/agenda'],
    ['Citas pendientes', $stats['pendientes'], 'reloj', '#4f46e5', '/citas?estado=PROGRAMADA'],
    ['Pacientes activos', $stats['pacientes'], 'pulso', '#0ea5e9', '/pacientes'],
    ['Médicos activos', $stats['medicos'], 'estetoscopio', '#059669', '/medicos'],
];
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><?= icono('brillo', 14) ?> Panel de control</div>
        <h1><?= e($saludo) ?>, <?= e(explode(' ', $u['nombre'])[0]) ?></h1>
        <p>Resumen de la actividad de la clínica para hoy, <?= e(fecha_larga(hoy())) ?>.</p>
    </div>
    <?php if (Auth::puede('citas.gestionar')): ?>
        <a href="/citas/nueva" class="btn btn-primary"><?= icono('calendario-mas') ?> Programar cita</a>
    <?php endif; ?>
</div>

<div class="stats" data-escalonar>
    <?php foreach ($tarjetas as [$lbl, $val, $ico, $color, $link]): ?>
        <a href="<?= e($link) ?>" class="card stat lift" style="color:inherit">
            <div class="glow" style="background: <?= $color ?>"></div>
            <div class="ico" style="background: <?= $color ?>1a; color: <?= $color ?>"><?= icono($ico, 22) ?></div>
            <div class="val" data-contar="<?= (int) $val ?>">0</div>
            <div class="lbl"><?= e($lbl) ?></div>
        </a>
    <?php endforeach; ?>
</div>

<div class="split" data-escalonar>
    <div class="card">
        <div class="card-head">
            <h2>Citas por día</h2>
            <span class="faint" style="font-size:13px">Últimos 7 días y próximos 7</span>
        </div>
        <div class="card-pad">
            <div class="bars" style="height:200px">
                <?php foreach ($semana as $i => $d): $esHoy = $d['dia'] === hoy(); ?>
                    <div class="bar-col<?= $esHoy ? ' hoy' : '' ?>" title="<?= e(fecha($d['dia'])) ?>: <?= (int) $d['total'] ?> citas">
                        <b><?= (int) $d['total'] ?></b>
                        <div class="bar grow" style="--i:<?= $i ?>;height:<?= max(3, round($d['total'] / $maxDia * 100)) ?>%;<?= $d['dia'] > hoy() ? 'opacity:.55' : '' ?>"></div>
                        <small><?= $esHoy ? 'Hoy' : mb_substr(DIAS_SEMANA[(int) date('N', strtotime($d['dia']))], 0, 2) . ' ' . date('j', strtotime($d['dia'])) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-head"><h2>Estados del mes</h2><a href="/reportes?tipo=estado" class="btn btn-ghost btn-sm">Ver reporte</a></div>
        <div class="card-pad"><?= View::parcial('donut', ['conteo' => $estados]) ?></div>
    </div>
</div>

<div class="split mt" data-escalonar>
    <div class="card">
        <div class="card-head">
            <h2>Agenda de hoy</h2>
            <a href="/agenda" class="btn btn-ghost btn-sm">Ver agenda <?= icono('flecha', 15) ?></a>
        </div>
        <?php if (!$hoy): ?>
            <div class="empty"><?= icono('calendario', 36) ?><div><strong>No hay citas para hoy</strong></div></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Hora</th><th>Paciente</th><th>Médico</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($hoy, 0, 8) as $c): ?>
                        <tr>
                            <td class="mono"><a href="<?= e(url('/citas/ver', ['id' => $c['idcita']])) ?>"><?= hora($c['horainicio']) ?></a></td>
                            <td><strong><?= e($c['paciente']) ?></strong><div class="faint" style="font-size:12.5px"><?= e($c['especialidad']) ?></div></td>
                            <td><?= e($c['medico']) ?></td>
                            <td><?= badge_cita($c['codigoestado']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($hoy) > 8): ?><div class="table-foot"><span>y <?= count($hoy) - 8 ?> cita(s) más</span></div><?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-head"><h2>Citas por especialidad</h2><span class="faint" style="font-size:13px">Este mes</span></div>
        <div class="card-pad" style="padding-top:8px;padding-bottom:12px">
            <?php foreach ($porEsp as $i => $e): ?>
                <div class="hbar-row">
                    <span><?= e($e['grupo']) ?></span>
                    <div class="hbar-track"><div class="hbar grow-x" style="--i:<?= $i ?>;width:<?= round($e['total'] / $maxEsp * 100) ?>%"></div></div>
                    <b><?= (int) $e['total'] ?></b>
                </div>
            <?php endforeach; ?>
            <?php if (!$porEsp): ?><div class="empty">Sin citas este mes.</div><?php endif; ?>
        </div>
    </div>
</div>

<?php if ($actividad): ?>
<div class="card mt anim">
    <div class="card-head"><h2>Actividad reciente</h2><a href="/bitacora" class="btn btn-ghost btn-sm">Ver bitácora <?= icono('flecha', 15) ?></a></div>
    <?php foreach ($actividad as $a): ?>
        <div class="feed-item">
            <div class="feed-ico" style="color:var(--accent);background:var(--accent-soft)"><?= icono('actividad', 17) ?></div>
            <div style="flex:1;min-width:0">
                <p><strong><?= e($a['usuario'] ?? 'Sistema') ?></strong> · <?= e($a['descripcion']) ?></p>
                <small><?= e(str_replace('_', ' ', mb_strtolower($a['accion']))) ?> · <?= e(hace_cuanto($a['fecha'])) ?></small>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
