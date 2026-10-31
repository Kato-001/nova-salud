<?php
/**
 * CU6 · Gestionar horarios de atención y bloqueos de agenda
 * @var array $medicos @var ?array $medico @var array $horarios @var array $bloqueos @var int $duracion
 */
$titulo = 'Horarios';
$porDia = [];
foreach ($horarios as $h) {
    $porDia[(int) $h['diasemana']][] = $h;
}
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><?= icono('reloj', 14) ?> Gestión médica</div>
        <h1>Horarios de atención</h1>
        <p>Las citas se ofrecen en bloques de <strong><?= $duracion ?> minutos</strong> dentro de estos horarios (configurable en <a href="/configuracion">Configuración</a>).</p>
    </div>
    <form method="get" class="field" style="min-width:280px">
        <span>Médico</span>
        <select class="select" name="medico" data-autoenviar>
            <?php foreach ($medicos as $op): ?>
                <option value="<?= $op['idmedico'] ?>" <?= $medico && (int) $medico['idmedico'] === (int) $op['idmedico'] ? 'selected' : '' ?>><?= e($op['nombre_completo']) ?> · <?= e($op['codigo']) ?><?= $op['estado'] !== 'ACTIVO' ? ' (inactivo)' : '' ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if (!$medico): ?>
    <div class="card empty">Primero registre un médico.</div>
<?php else: ?>
<div class="cita-layout">
    <div class="card anim">
        <div class="card-head"><h2>Semana de Dr(a). <?= e($medico['nombre_completo']) ?></h2><?= badge_estado($medico['estado']) ?></div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Día</th><th>Horario</th><th>Citas por día</th><th>Estado</th><th style="text-align:right">Acciones</th></tr></thead>
                <tbody>
                <?php foreach (DIAS_SEMANA as $n => $dia): ?>
                    <?php if (empty($porDia[$n])): ?>
                        <tr><td><strong><?= e($dia) ?></strong></td><td class="faint" colspan="4">No atiende</td></tr>
                    <?php else: foreach ($porDia[$n] as $i => $h):
                        $bloques = intdiv((int) ((strtotime($h['horafin']) - strtotime($h['horainicio'])) / 60), $duracion);
                        $activo = $h['estado'] === 'ACTIVO'; ?>
                        <tr>
                            <td><?= $i === 0 ? '<strong>' . e($dia) . '</strong>' : '' ?></td>
                            <td class="mono" style="font-weight:700;<?= !$activo ? 'opacity:.5;text-decoration:line-through' : '' ?>"><?= hora($h['horainicio']) ?> – <?= hora($h['horafin']) ?></td>
                            <td><?= $bloques ?> cita(s) de <?= $duracion ?> min</td>
                            <td><?= badge_estado($h['estado']) ?></td>
                            <td>
                                <div class="actions">
                                    <form method="post" action="/horarios/estado" class="inline">
                                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $h['idhorario'] ?>">
                                        <button class="btn btn-ghost btn-icon btn-sm <?= $activo ? 'btn-danger-ghost' : 'btn-ok-ghost' ?>" title="<?= $activo ? 'Desactivar' : 'Activar' ?>" data-sin-carga="1"><?= icono('power', 16) ?></button>
                                    </form>
                                    <form method="post" action="/horarios/eliminar" class="inline" data-confirm="Eliminar horario" data-confirm-texto="<?= e($dia . ' ' . hora($h['horainicio']) . '–' . hora($h['horafin'])) ?>. Si tiene citas pendientes no se podrá eliminar." data-confirm-ok="Eliminar" data-peligro>
                                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $h['idhorario'] ?>">
                                        <button class="btn btn-ghost btn-icon btn-sm btn-danger-ghost" title="Eliminar" data-sin-carga="1"><?= icono('basura', 16) ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="stack">
        <form method="post" action="/horarios/guardar" class="card card-pad anim" data-validar>
            <?= csrf_field() ?><input type="hidden" name="idmedico" value="<?= $medico['idmedico'] ?>">
            <h2 style="font-size:17px;margin-bottom:16px" class="row"><?= icono('mas') ?> Agregar horario</h2>
            <div class="stack">
                <label class="field"><span>Día *</span>
                    <select class="select<?= clase_error('diasemana') ?>" name="diasemana" required>
                        <?php foreach (DIAS_SEMANA as $n => $dia): ?><option value="<?= $n ?>" <?= old('diasemana') === (string) $n ? 'selected' : '' ?>><?= e($dia) ?></option><?php endforeach; ?>
                    </select><?= error_campo('diasemana') ?></label>
                <div class="grid-2" style="gap:12px">
                    <label class="field"><span>Hora inicial *</span>
                        <input class="input<?= clase_error('horainicio') ?>" type="time" name="horainicio" value="<?= e(old('horainicio', '08:00')) ?>" required step="<?= $duracion * 60 ?>"><?= error_campo('horainicio') ?></label>
                    <label class="field"><span>Hora final *</span>
                        <input class="input<?= clase_error('horafin') ?>" type="time" name="horafin" value="<?= e(old('horafin', '12:00')) ?>" required step="<?= $duracion * 60 ?>"><?= error_campo('horafin') ?></label>
                </div>
                <small class="faint">La hora final debe ser posterior a la inicial y no cruzarse con otro horario del mismo día.</small>
                <button class="btn btn-primary">Agregar horario</button>
            </div>
        </form>

        <div class="card anim">
            <div class="card-head"><h2>Bloqueos de agenda</h2></div>
            <form method="post" action="/bloqueos/guardar" class="card-pad stack" data-validar>
                <?= csrf_field() ?><input type="hidden" name="idmedico" value="<?= $medico['idmedico'] ?>">
                <div class="grid-2" style="gap:12px">
                    <label class="field"><span>Fecha *</span><input class="input" type="date" name="fecha" min="<?= hoy() ?>" required></label>
                    <label class="field"><span>Tipo *</span>
                        <select class="select" name="tipo" required>
                            <?php foreach (['FERIADO' => 'Feriado', 'VACACION' => 'Vacación', 'PERMISO' => 'Permiso', 'OTRO' => 'Otro'] as $v => $l): ?><option value="<?= $v ?>"><?= $l ?></option><?php endforeach; ?>
                        </select></label>
                </div>
                <label class="check"><input type="checkbox" name="dia_completo" value="1" checked onchange="this.closest('form').querySelector('.horas-bloqueo').hidden = this.checked"> Todo el día</label>
                <div class="grid-2 horas-bloqueo" style="gap:12px" hidden>
                    <label class="field"><span>Desde</span><input class="input" type="time" name="horainicio"></label>
                    <label class="field"><span>Hasta</span><input class="input" type="time" name="horafin"></label>
                </div>
                <label class="field"><span>Motivo</span><input class="input" name="motivo" maxlength="255" placeholder="Ej. Congreso médico"></label>
                <button class="btn btn-outline"><?= icono('prohibido', 16) ?> Bloquear agenda</button>
            </form>
            <?php foreach ($bloqueos as $b): ?>
                <div class="role-card">
                    <div class="grow">
                        <h3><span class="badge badge-warn"><?= e($b['tipo']) ?></span> <?= e(fecha($b['fecha'])) ?></h3>
                        <p><?= $b['horainicio'] ? hora($b['horainicio']) . ' – ' . hora($b['horafin']) : 'Todo el día' ?><?= $b['motivo'] ? ' · ' . e($b['motivo']) : '' ?></p>
                    </div>
                    <form method="post" action="/bloqueos/eliminar" class="inline">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $b['idbloqueo'] ?>">
                        <button class="btn btn-ghost btn-icon btn-sm btn-danger-ghost" title="Liberar" data-sin-carga="1"><?= icono('cerrar', 16) ?></button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>
