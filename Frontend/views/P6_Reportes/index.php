<?php
/**
 * CU20 · Reportes administrativos (RF13) con filtros, totales, CSV e impresión
 * @var string $tipo @var array $f @var array $tipos @var array $medicos @var array $especialidades
 */
$titulo = 'Reportes';
$csv = url('/reportes', array_merge($_GET, ['tipo' => $tipo, 'formato' => 'csv']));
$pct = fn($n, $t) => $t ? round($n / $t * 100, 1) . '%' : '0%';
$colEstados = ['PROGRAMADA' => 'var(--info)', 'CONFIRMADA' => 'var(--accent)', 'ATENDIDA' => 'var(--ok)', 'CANCELADA' => 'var(--text-faint)', 'INASISTENCIA' => 'var(--warn)'];
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><?= icono('reporte', 14) ?> Reportes</div>
        <h1><?= e($tipos[$tipo]) ?></h1>
        <p><?= $tipo === 'agenda' ? e(fecha_larga($f['dia'])) : 'Del ' . e(fecha($f['desde'])) . ' al ' . e(fecha($f['hasta'])) ?></p>
    </div>
    <div class="row no-print">
        <a href="<?= e($csv) ?>" class="btn btn-outline"><?= icono('descargar') ?> Exportar CSV</a>
        <button type="button" class="btn btn-primary" onclick="window.print()"><?= icono('imprimir') ?> Imprimir / PDF</button>
    </div>
</div>

<div class="report-head">
    <strong><?= e(config('NOMBRE_CLINICA', 'NovaSol Salud')) ?></strong> · <?= e($tipos[$tipo]) ?> · Generado el <?= date('d/m/Y H:i') ?> por <?= e(Auth::usuario()['nombre']) ?>
</div>

<nav class="tabs no-print">
    <?php foreach ($tipos as $k => $l): ?>
        <a href="<?= e(url('/reportes', ['tipo' => $k, 'desde' => $f['desde'], 'hasta' => $f['hasta']])) ?>" class="<?= $tipo === $k ? 'on' : '' ?>"><?= e($l) ?></a>
    <?php endforeach; ?>
</nav>

<div class="card anim">
    <form class="report-filters" method="get">
        <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
        <?php if ($tipo === 'agenda'): ?>
            <label class="field"><span>Fecha</span><input class="input" type="date" name="dia" value="<?= e($f['dia']) ?>"></label>
        <?php else: ?>
            <label class="field"><span>Desde</span><input class="input" type="date" name="desde" value="<?= e($f['desde']) ?>" required></label>
            <label class="field"><span>Hasta</span><input class="input" type="date" name="hasta" value="<?= e($f['hasta']) ?>" required></label>
        <?php endif; ?>
        <?php if (in_array($tipo, ['fecha', 'agenda', 'especialidad', 'estado', 'resumen'], true)): ?>
            <label class="field"><span>Médico</span><select class="select" name="medico"><option value="">Todos</option>
                <?php foreach ($medicos as $m): ?><option value="<?= $m['idmedico'] ?>" <?= (string) $f['medico'] === (string) $m['idmedico'] ? 'selected' : '' ?>><?= e($m['nombre_completo']) ?></option><?php endforeach; ?></select></label>
        <?php endif; ?>
        <?php if (in_array($tipo, ['fecha', 'medico', 'estado', 'resumen'], true)): ?>
            <label class="field"><span>Especialidad</span><select class="select" name="especialidad"><option value="">Todas</option>
                <?php foreach ($especialidades as $esp): ?><option value="<?= $esp['idespecialidad'] ?>" <?= (string) $f['especialidad'] === (string) $esp['idespecialidad'] ? 'selected' : '' ?>><?= e($esp['nombre']) ?></option><?php endforeach; ?></select></label>
        <?php endif; ?>
        <?php if ($tipo === 'fecha'): ?>
            <label class="field"><span>Estado</span><select class="select" name="estado"><option value="">Todos</option>
                <?php foreach (ESTADOS_CITA as $cod => [$n]): ?><option value="<?= $cod ?>" <?= entrada('estado') === $cod ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select></label>
        <?php endif; ?>
        <?php if ($tipo === 'pacientes'): ?>
            <label class="field"><span>Estado del paciente</span><select class="select" name="estado_paciente"><option value="">Todos</option>
                <option value="ACTIVO" <?= entrada('estado_paciente') === 'ACTIVO' ? 'selected' : '' ?>>Activos</option><option value="INACTIVO" <?= entrada('estado_paciente') === 'INACTIVO' ? 'selected' : '' ?>>Inactivos</option></select></label>
        <?php endif; ?>
        <?php if ($tipo !== 'agenda'): ?>
            <div class="row" style="gap:6px">
                <?php foreach (['Este mes' => [date('Y-m-01'), date('Y-m-t')], 'Mes pasado' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))], 'Este año' => [date('Y-01-01'), date('Y-12-31')]] as $l => [$d, $h]): ?>
                    <a class="btn btn-ghost btn-sm" href="<?= e(url('/reportes', ['tipo' => $tipo, 'desde' => $d, 'hasta' => $h])) ?>"><?= $l ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <button class="btn btn-primary" style="height:40px"><?= icono('filtro', 16) ?> Generar</button>
    </form>

    <?php if ($tipo === 'resumen'): $r = $resumen; ?>
        <div class="card-pad">
            <div class="kpis" data-escalonar>
                <div class="kpi"><b data-contar="<?= (int) $r['total'] ?>">0</b><small>Citas en el período</small><span class="pct"><?= (int) $r['pacientes'] ?> pacientes distintos</span></div>
                <div class="kpi" style="background:var(--ok-soft)"><b data-contar="<?= (int) $r['atendidas'] ?>">0</b><small>Atendidas</small><span class="pct"><?= $pct($r['atendidas'], $r['total']) ?></span></div>
                <div class="kpi"><b data-contar="<?= (int) $r['canceladas'] ?>">0</b><small>Canceladas</small><span class="pct"><?= $pct($r['canceladas'], $r['total']) ?></span></div>
                <div class="kpi" style="background:var(--warn-soft)"><b data-contar="<?= (int) $r['inasistencias'] ?>">0</b><small>Inasistencias</small><span class="pct"><?= $pct($r['inasistencias'], $r['total']) ?></span></div>
                <div class="kpi" style="background:var(--info-soft)"><b data-contar="<?= (int) $r['pendientes'] ?>">0</b><small>Pendientes</small><span class="pct"><?= $pct($r['pendientes'], $r['total']) ?></span></div>
            </div>
        </div>
        <div class="split card-pad" style="padding-top:0">
            <div>
                <div class="section-title">Citas por día</div>
                <?php $maxD = max(1, ...array_map('intval', array_column($porDia, 'total'))); $muchos = count($porDia) > 31; ?>
                <div class="bars" style="height:180px;gap:<?= $muchos ? 2 : 6 ?>px">
                    <?php foreach ($porDia as $i => $d): ?>
                        <div class="bar-col" title="<?= e(fecha($d['dia'])) ?>: <?= (int) $d['total'] ?>">
                            <?php if (!$muchos): ?><b><?= (int) $d['total'] ?: '' ?></b><?php endif; ?>
                            <div class="bar grow" style="--i:<?= min($i, 40) ?>;height:<?= max(2, round($d['total'] / $maxD * 100)) ?>%"></div>
                            <?php if (!$muchos): ?><small><?= date('j', strtotime($d['dia'])) ?></small><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <div class="section-title">Por estado</div>
                <?= View::parcial('donut', ['conteo' => array_column($porEstado, 'total', 'codigo')]) ?>
            </div>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Especialidad</th><th>Total</th><th>Pendientes</th><th>Atendidas</th><th>Canceladas</th><th>Inasistencias</th></tr></thead>
                <tbody>
                <?php foreach ($porEsp as $row): ?>
                    <tr><td><strong><?= e($row['grupo']) ?></strong></td><td><?= $row['total'] ?></td><td><?= $row['pendientes'] ?></td><td><?= $row['atendidas'] ?></td><td><?= $row['canceladas'] ?></td><td><?= $row['inasistencias'] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($tipo === 'fecha'): ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Código</th><th>Fecha</th><th>Hora</th><th>Paciente</th><th>Médico</th><th>Especialidad</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($filas as $c): ?>
                    <tr>
                        <td class="mono"><a href="<?= e(url('/citas/ver', ['id' => $c['idcita']])) ?>"><?= e($c['codigo']) ?></a></td>
                        <td><?= e(fecha($c['fecha'])) ?></td><td class="mono"><?= hora($c['horainicio']) ?>–<?= hora($c['horafin']) ?></td>
                        <td><?= e($c['paciente']) ?><div class="faint" style="font-size:12px">CI <?= e($c['ci']) ?></div></td>
                        <td><?= e($c['medico']) ?></td><td><?= e($c['especialidad']) ?></td><td><?= badge_cita($c['codigoestado']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row"><td colspan="6">Total de citas</td><td><?= count($filas) ?></td></tr>
                </tbody>
            </table>
            <?php if (!$filas): ?><div class="empty">Sin citas en el período.</div><?php endif; ?>
        </div>

    <?php elseif (in_array($tipo, ['medico', 'especialidad'], true)): $tot = array_sum(array_column($filas, 'total')); $max = max(1, ...array_map('intval', array_column($filas, 'total') ?: [0])); ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th><?= $tipo === 'medico' ? 'Médico' : 'Especialidad' ?></th><th style="width:30%"></th><th>Total</th><th>Pendientes</th><th>Atendidas</th><th>Canceladas</th><th>Inasistencias</th></tr></thead>
                <tbody>
                <?php foreach ($filas as $i => $row): ?>
                    <tr>
                        <td><strong><?= e($row['grupo']) ?></strong></td>
                        <td><div class="hbar-track"><div class="hbar grow-x" style="--i:<?= $i ?>;width:<?= round($row['total'] / $max * 100) ?>%"></div></div></td>
                        <td><strong><?= $row['total'] ?></strong> <span class="faint" style="font-size:12px"><?= $pct($row['total'], $tot) ?></span></td>
                        <td><?= $row['pendientes'] ?></td><td><?= $row['atendidas'] ?></td><td><?= $row['canceladas'] ?></td><td><?= $row['inasistencias'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row"><td colspan="2">Total</td><td><?= $tot ?></td>
                    <?php foreach (['pendientes', 'atendidas', 'canceladas', 'inasistencias'] as $k): ?><td><?= array_sum(array_column($filas, $k)) ?></td><?php endforeach; ?></tr>
                </tbody>
            </table>
            <?php if (!$filas): ?><div class="empty">Sin citas en el período.</div><?php endif; ?>
        </div>

    <?php elseif ($tipo === 'estado'): $tot = array_sum(array_column($filas, 'total')); ?>
        <div class="split card-pad">
            <?= View::parcial('donut', ['conteo' => array_column($filas, 'total', 'codigo')]) ?>
            <table class="table">
                <thead><tr><th>Estado</th><th>Cantidad</th><th>%</th></tr></thead>
                <tbody>
                <?php foreach ($filas as $row): ?><tr><td><?= badge_cita($row['codigo']) ?></td><td><strong><?= $row['total'] ?></strong></td><td><?= $pct($row['total'], $tot) ?></td></tr><?php endforeach; ?>
                <tr class="total-row"><td>Total</td><td><?= $tot ?></td><td>100%</td></tr>
                </tbody>
            </table>
        </div>

    <?php elseif ($tipo === 'pacientes'): ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Código</th><th>CI</th><th>Paciente</th><th>Teléfono</th><th>Estado</th><th>Citas en el período</th><th>Atendidas</th><th>Inasistencias</th></tr></thead>
                <tbody>
                <?php foreach ($filas as $row): ?>
                    <tr><td class="mono"><?= e($row['codigo']) ?></td><td class="mono"><?= e($row['ci']) ?></td><td><strong><?= e($row['paciente']) ?></strong></td><td><?= e($row['telefono']) ?></td>
                        <td><?= badge_estado($row['estado']) ?></td><td><?= $row['citas'] ?></td><td><?= $row['atendidas'] ?></td><td><?= $row['inasistencias'] ?></td></tr>
                <?php endforeach; ?>
                <tr class="total-row"><td colspan="5">Total de pacientes: <?= count($filas) ?></td><td><?= array_sum(array_column($filas, 'citas')) ?></td><td><?= array_sum(array_column($filas, 'atendidas')) ?></td><td><?= array_sum(array_column($filas, 'inasistencias')) ?></td></tr>
                </tbody>
            </table>
        </div>

    <?php else: /* agenda */ ?>
        <?php if (!$agenda): ?><div class="empty">No hay citas para esta fecha.</div><?php endif; ?>
        <?php foreach ($agenda as $medico => $citas): ?>
            <div class="card-pad" style="border-bottom:1px solid var(--border)">
                <div class="row-between mb"><h2 style="font-size:16px">Dr(a). <?= e($medico) ?></h2><span class="faint" style="font-size:13px"><?= count($citas) ?> cita(s)</span></div>
                <table class="table">
                    <thead><tr><th>Hora</th><th>Código</th><th>Paciente</th><th>Especialidad</th><th>Estado</th><th>Motivo</th></tr></thead>
                    <tbody>
                    <?php foreach ($citas as $c): ?>
                        <tr><td class="mono"><?= hora($c['horainicio']) ?>–<?= hora($c['horafin']) ?></td><td class="mono"><?= e($c['codigo']) ?></td><td><?= e($c['paciente']) ?></td>
                            <td><?= e($c['especialidad']) ?></td><td><?= badge_cita($c['codigoestado']) ?></td><td class="soft"><?= e($c['motivo'] ?: '—') ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
