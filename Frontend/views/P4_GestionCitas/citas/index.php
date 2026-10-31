<?php
/**
 * Consulta de citas con filtros por fecha, médico, especialidad y estado
 * @var array $citas @var array $conteo @var array $medicos @var array $especialidades @var array $f @var Paginador $pag
 */
$titulo = 'Citas';
$base = array_diff_key($f, ['estado' => 1]);
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><?= icono('calendario', 14) ?> Gestión de citas</div>
        <h1>Citas médicas</h1>
        <p>Consulte, filtre y gestione las citas programadas.</p>
    </div>
    <?php if (Auth::puede('citas.gestionar')): ?><a href="/citas/nueva" class="btn btn-primary"><?= icono('calendario-mas') ?> Programar cita</a><?php endif; ?>
</div>

<div class="card anim">
    <form class="toolbar" method="get">
        <div class="input-icon"><?= icono('buscar') ?><input class="input" type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Código, CI o paciente" aria-label="Buscar"></div>
        <input class="input" type="date" name="fecha" value="<?= e($f['fecha']) ?>" style="width:auto" title="Fecha exacta" aria-label="Fecha">
        <input class="input" type="date" name="desde" value="<?= e($f['desde']) ?>" style="width:auto" title="Desde" aria-label="Desde">
        <input class="input" type="date" name="hasta" value="<?= e($f['hasta']) ?>" style="width:auto" title="Hasta" aria-label="Hasta">
        <select class="select" name="medico" data-autoenviar aria-label="Médico">
            <option value="">Todos los médicos</option>
            <?php foreach ($medicos as $m): ?><option value="<?= $m['idmedico'] ?>" <?= (string) $f['medico'] === (string) $m['idmedico'] ? 'selected' : '' ?>><?= e($m['nombre_completo']) ?></option><?php endforeach; ?>
        </select>
        <select class="select" name="especialidad" data-autoenviar aria-label="Especialidad">
            <option value="">Todas las especialidades</option>
            <?php foreach ($especialidades as $esp): ?><option value="<?= $esp['idespecialidad'] ?>" <?= (string) $f['especialidad'] === (string) $esp['idespecialidad'] ? 'selected' : '' ?>><?= e($esp['nombre']) ?></option><?php endforeach; ?>
        </select>
        <input type="hidden" name="estado" value="<?= e($f['estado']) ?>">
        <button class="btn btn-outline" data-sin-carga="1"><?= icono('filtro', 16) ?> Filtrar</button>
        <a href="/citas" class="btn btn-ghost">Limpiar</a>
    </form>
    <div style="padding:14px 20px;border-bottom:1px solid var(--border);overflow-x:auto">
        <div class="pill-tabs">
            <a href="<?= e(url('/citas', $base)) ?>" class="<?= $f['estado'] === '' ? 'on' : '' ?>">Todas <span class="n"><?= array_sum($conteo) ?></span></a>
            <?php foreach (ESTADOS_CITA as $cod => [$nombre]): ?>
                <a href="<?= e(url('/citas', $base + ['estado' => $cod])) ?>" class="<?= $f['estado'] === $cod ? 'on' : '' ?>"><?= e($nombre) ?> <span class="n"><?= $conteo[$cod] ?></span></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Código</th><th>Fecha y hora</th><th>Paciente</th><th>Médico / Especialidad</th><th>Estado</th><th style="text-align:right"></th></tr></thead>
            <tbody>
            <?php foreach ($citas as $c): ?>
                <tr>
                    <td class="mono"><a href="<?= e(url('/citas/ver', ['id' => $c['idcita']])) ?>"><strong><?= e($c['codigo']) ?></strong></a></td>
                    <td><strong><?= e(fecha($c['fecha'])) ?></strong><div class="mono faint"><?= hora($c['horainicio']) ?>–<?= hora($c['horafin']) ?></div></td>
                    <td><?= e($c['paciente']) ?><div class="faint" style="font-size:12.5px">CI <?= e($c['ci']) ?></div></td>
                    <td><?= e($c['medico']) ?><div class="faint" style="font-size:12.5px"><?= e($c['especialidad']) ?></div></td>
                    <td><?= badge_cita($c['codigoestado']) ?></td>
                    <td><div class="actions"><a class="btn btn-ghost btn-sm" href="<?= e(url('/citas/ver', ['id' => $c['idcita']])) ?>">Ver <?= icono('derecha', 14) ?></a></div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$citas): ?><div class="empty"><?= icono('calendario', 36) ?><div><strong>No hay citas con esos filtros</strong></div></div><?php endif; ?>
    </div>
    <?= View::parcial('paginacion', ['pag' => $pag]) ?>
</div>
