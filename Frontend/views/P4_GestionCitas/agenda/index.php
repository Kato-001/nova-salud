<?php
/**
 * CU14 · Agenda médica por fecha, médico y estado (RF11)
 * @var string $fecha @var ?int $idmedico @var ?string $estado @var bool $todas @var array $medicos
 * @var array $porMedico @var array $conteo @var ?array $bloques @var ?array $medico
 */
$titulo = $todas ? 'Agenda médica' : 'Mi agenda';
$params = fn(array $extra) => url('/agenda', array_merge(['fecha' => $fecha, 'medico' => $idmedico, 'estado' => $estado], $extra));
$verMotivo = Auth::puede('citas.ver') || Auth::puede('consultas.registrar');

// Para un médico concreto se intercalan los bloques libres con sus citas
$lineas = [];
if ($idmedico && $bloques && $bloques['ok'] && !$estado) {
    $ocupadas = [];
    foreach ($porMedico[$idmedico]['citas'] ?? [] as $c) {
        $ocupadas[hora($c['horainicio'])][] = $c;
    }
    foreach ($bloques['bloques'] as $b) {
        if (isset($ocupadas[$b['inicio']])) {
            foreach ($ocupadas[$b['inicio']] as $c) $lineas[] = ['cita' => $c];
            unset($ocupadas[$b['inicio']]);
        } else {
            $lineas[] = ['libre' => $b];
        }
    }
    foreach ($ocupadas as $lista) foreach ($lista as $c) $lineas[] = ['cita' => $c];   // citas fuera del horario actual
    usort($lineas, fn($a, $b) => (isset($a['cita']) ? hora($a['cita']['horainicio']) : $a['libre']['inicio']) <=> (isset($b['cita']) ? hora($b['cita']['horainicio']) : $b['libre']['inicio']));
}
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><?= icono('agenda', 14) ?> Agenda</div>
        <h1><?= e(fecha_larga($fecha)) ?></h1>
        <p><?= $medico ? 'Dr(a). ' . e($medico['nombre_completo']) . ' · ' . e($medico['especialidades']) : 'Todos los médicos' ?></p>
    </div>
    <div class="date-nav no-print">
        <a class="btn btn-outline btn-icon" href="<?= e($params(['fecha' => date('Y-m-d', strtotime("$fecha -1 day"))])) ?>" aria-label="Día anterior"><?= icono('izquierda') ?></a>
        <a class="btn btn-outline" href="<?= e($params(['fecha' => hoy()])) ?>">Hoy</a>
        <a class="btn btn-outline btn-icon" href="<?= e($params(['fecha' => date('Y-m-d', strtotime("$fecha +1 day"))])) ?>" aria-label="Día siguiente"><?= icono('derecha') ?></a>
        <button type="button" class="btn btn-outline btn-icon" onclick="window.print()" title="Imprimir agenda"><?= icono('imprimir') ?></button>
    </div>
</div>

<form class="card toolbar anim mb" method="get" style="border-radius:var(--radius-lg)">
    <label class="field" style="flex:0 0 auto"><span>Fecha</span><input class="input" type="date" name="fecha" value="<?= e($fecha) ?>" data-autoenviar></label>
    <?php if ($todas): ?>
        <label class="field" style="flex:1 1 240px"><span>Médico</span>
            <select class="select" name="medico" data-autoenviar style="width:100%">
                <option value="">Todos los médicos</option>
                <?php foreach ($medicos as $m): ?><option value="<?= $m['idmedico'] ?>" <?= (int) $idmedico === (int) $m['idmedico'] ? 'selected' : '' ?>><?= e($m['nombre_completo']) ?></option><?php endforeach; ?>
            </select></label>
    <?php endif; ?>
    <label class="field" style="flex:0 1 200px"><span>Estado</span>
        <select class="select" name="estado" data-autoenviar style="width:100%">
            <option value="">Todos</option>
            <?php foreach (ESTADOS_CITA as $cod => [$n]): ?><option value="<?= $cod ?>" <?= $estado === $cod ? 'selected' : '' ?>><?= e($n) ?> (<?= $conteo[$cod] ?? 0 ?>)</option><?php endforeach; ?>
        </select></label>
</form>

<?php if ($lineas): ?>
    <div class="card anim">
        <div class="card-head"><h2>Agenda completa</h2><span class="faint" style="font-size:13px"><?= count(array_filter($lineas, fn($l) => isset($l['libre']) && $l['libre']['libre'])) ?> bloques libres</span></div>
        <div class="card-pad agenda-col" data-escalonar>
            <?php foreach ($lineas as $l): ?>
                <?php if (isset($l['libre'])): $b = $l['libre']; ?>
                    <div class="agenda-item libre">
                        <div class="h"><?= e($b['inicio']) ?><small><?= e($b['fin']) ?></small></div>
                        <div><?= $b['libre'] ? 'Disponible' : 'No disponible' ?></div>
                        <?php if ($b['libre'] && Auth::puede('citas.gestionar')): ?>
                            <a class="btn btn-ghost btn-sm" href="<?= e(url('/citas/nueva', ['medico' => $idmedico])) ?>"><?= icono('mas', 15) ?> Programar</a>
                        <?php else: ?><span></span><?php endif; ?>
                    </div>
                <?php else: $c = $l['cita']; ?>
                    <?= View::parcial('agenda_item', ['c' => $c, 'verMotivo' => $verMotivo]) ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php elseif ($idmedico && $bloques && !$bloques['ok'] && !$porMedico): ?>
    <div class="card empty anim"><?= icono('calendario', 36) ?><div><strong><?= e($bloques['motivo']) ?></strong></div></div>
<?php elseif (!$porMedico): ?>
    <div class="card empty anim"><?= icono('calendario', 36) ?><div><strong>No hay citas para esta fecha<?= $estado ? ' con ese estado' : '' ?></strong></div></div>
<?php else: ?>
    <div class="stack">
        <?php foreach ($porMedico as $id => $grupo): ?>
            <div class="card anim">
                <div class="card-head">
                    <h2>Dr(a). <?= e($grupo['nombre']) ?></h2>
                    <div class="row"><span class="faint" style="font-size:13px"><?= count($grupo['citas']) ?> cita(s)</span>
                        <?php if ($todas): ?><a class="btn btn-ghost btn-sm no-print" href="<?= e($params(['medico' => $id])) ?>">Ver agenda completa</a><?php endif; ?></div>
                </div>
                <div class="card-pad agenda-col" data-escalonar>
                    <?php foreach ($grupo['citas'] as $c): ?><?= View::parcial('agenda_item', ['c' => $c, 'verMotivo' => $verMotivo]) ?><?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
