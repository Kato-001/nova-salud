<?php
/**
 * Detalle de una cita con sus acciones según el estado (RF10) y su historial
 * @var array $c @var array $historial @var array $acciones @var ?array $consulta
 */
$titulo = 'Cita ' . $c['codigo'];
$gestiona = Auth::puede('citas.gestionar');
$asistencia = Auth::puede('citas.asistencia');
$u = Auth::usuario();
$esSuMedico = Auth::puede('consultas.registrar') && (int) $u['idmedico'] === (int) $c['idmedico'];
$ACCIONES = ['PROGRAMAR' => 'Cita programada', 'CONFIRMAR' => 'Asistencia confirmada', 'REPROGRAMAR' => 'Cita reprogramada',
             'CANCELAR' => 'Cita cancelada', 'ATENDER' => 'Paciente atendido', 'INASISTENCIA' => 'El paciente no asistió'];
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><a href="<?= Auth::puede('citas.ver') ? '/citas' : '/agenda' ?>"><?= icono('izquierda', 14) ?> <?= Auth::puede('citas.ver') ? 'Citas' : 'Mi agenda' ?></a></div>
        <h1 class="row">Cita <span class="mono"><?= e($c['codigo']) ?></span> <?= badge_cita($c['codigoestado']) ?></h1>
        <p><?= e(fecha_larga($c['fecha'])) ?> · <?= hora($c['horainicio']) ?>–<?= hora($c['horafin']) ?></p>
    </div>
    <div class="row no-print">
        <?php if ($esSuMedico && $acciones['asistencia']): ?>
            <a href="<?= e(url('/atencion', ['cita' => $c['idcita']])) ?>" class="btn btn-primary"><?= icono('estetoscopio') ?> Atender y registrar consulta</a>
        <?php endif; ?>
        <button type="button" class="btn btn-outline" onclick="window.print()"><?= icono('imprimir') ?> Imprimir</button>
    </div>
</div>

<div class="cita-layout">
    <div class="stack">
        <div class="card anim">
            <div class="ticket-head" style="border-radius:var(--radius-lg) var(--radius-lg) 0 0">
                <small>Cita médica</small>
                <h3><?= e($c['codigo']) ?></h3>
            </div>
            <div class="kv-list">
                <div><span><?= icono('usuario', 16) ?> Paciente</span><span>
                    <?php if (Auth::puede('pacientes.ver')): ?><a href="<?= e(url('/pacientes/ver', ['id' => $c['idpaciente']])) ?>"><strong><?= e($c['paciente']) ?></strong></a>
                    <?php else: ?><strong><?= e($c['paciente']) ?></strong><?php endif; ?>
                    <div class="faint" style="font-size:12.5px">CI <?= e($c['ci']) ?> · <?= e(edad($c['fechanacimiento'])) ?> · <?= e($c['telefono_paciente']) ?></div></span></div>
                <div><span><?= icono('cruz', 16) ?> Especialidad</span><span><?= e($c['especialidad']) ?></span></div>
                <div><span><?= icono('estetoscopio', 16) ?> Médico</span><span>Dr(a). <?= e($c['medico']) ?></span></div>
                <div><span><?= icono('calendario', 16) ?> Fecha</span><span><?= e(fecha($c['fecha'])) ?></span></div>
                <div><span><?= icono('reloj', 16) ?> Horario</span><span class="mono"><?= hora($c['horainicio']) ?>–<?= hora($c['horafin']) ?></span></div>
                <div><span><?= icono('info', 16) ?> Modalidad</span><span><?= $c['modalidad'] === 'VIRTUAL' ? 'Virtual' : 'Presencial' ?></span></div>
                <div><span><?= icono('documento', 16) ?> Motivo</span><span><?= e($c['motivo'] ?: '—') ?></span></div>
                <div><span><?= icono('historial', 16) ?> Registrada</span><span><?= e(fecha_hora($c['fecharegistro'])) ?><?= $c['registrado_por'] ? '<div class="faint" style="font-size:12.5px">por ' . e($c['registrado_por']) . '</div>' : '' ?></span></div>
            </div>
        </div>

        <?php if ($consulta): ?>
            <div class="card anim">
                <div class="card-head"><h2><?= icono('estetoscopio', 18) ?> Consulta registrada</h2></div>
                <?= View::parcial('consultas', ['consultas' => [$consulta + ['codigo' => $c['codigo'], 'medico' => $c['medico'], 'especialidad' => $c['especialidad'], 'idcita' => $c['idcita']]]]) ?>
                <?php if ($consulta['examenfisico']): ?><div class="card-pad" style="padding-top:0"><p class="soft m0" style="font-size:13.5px"><strong>Examen físico:</strong> <?= e($consulta['examenfisico']) ?></p></div><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="stack">
        <?php if ($acciones['reprogramar'] || $acciones['confirmar'] || $acciones['asistencia']): ?>
        <div class="card card-pad anim no-print">
            <h2 style="font-size:16px;margin-bottom:14px">Acciones</h2>
            <div class="stack" style="gap:10px">
                <?php if ($acciones['confirmar'] && $asistencia): ?>
                    <form method="post" action="/citas/estado"><?= csrf_field() ?><input type="hidden" name="idcita" value="<?= $c['idcita'] ?>"><input type="hidden" name="estado" value="CONFIRMADA">
                        <button class="btn btn-outline btn-block"><?= icono('calendario-ok') ?> Confirmar asistencia</button></form>
                <?php endif; ?>
                <?php if ($acciones['reprogramar'] && $gestiona): ?>
                    <a href="<?= e(url('/citas/reprogramar', ['id' => $c['idcita']])) ?>" class="btn btn-outline btn-block"><?= icono('reiniciar') ?> Reprogramar</a>
                <?php endif; ?>
                <?php if ($acciones['asistencia'] && ($asistencia || $esSuMedico)): ?>
                    <form method="post" action="/citas/estado" data-confirm="Registrar asistencia" data-confirm-texto="La cita quedará como ATENDIDA." data-confirm-ok="Registrar">
                        <?= csrf_field() ?><input type="hidden" name="idcita" value="<?= $c['idcita'] ?>"><input type="hidden" name="estado" value="ATENDIDA">
                        <button class="btn btn-outline btn-block btn-ok-ghost"><?= icono('ok-circulo') ?> Marcar como atendida</button></form>
                    <form method="post" action="/citas/estado" data-confirm="Registrar inasistencia" data-confirm-texto="Se registrará que el paciente no se presentó a su cita." data-confirm-ok="Registrar inasistencia" data-peligro>
                        <?= csrf_field() ?><input type="hidden" name="idcita" value="<?= $c['idcita'] ?>"><input type="hidden" name="estado" value="INASISTENCIA">
                        <button class="btn btn-outline btn-block" style="color:var(--warn)"><?= icono('prohibido') ?> El paciente no asistió</button></form>
                <?php endif; ?>
                <?php if ($acciones['cancelar'] && $gestiona): ?>
                    <form method="post" action="/citas/cancelar" data-confirm="Cancelar la cita <?= e($c['codigo']) ?>" data-confirm-texto="El horario quedará libre. La cita no se borra: queda en el historial." data-confirm-ok="Cancelar cita" data-motivo="Motivo de la cancelación *" data-peligro>
                        <?= csrf_field() ?><input type="hidden" name="idcita" value="<?= $c['idcita'] ?>">
                        <button class="btn btn-ghost btn-block btn-danger-ghost"><?= icono('x-circulo') ?> Cancelar cita</button></form>
                <?php endif; ?>
            </div>
            <?php if (!$acciones['asistencia'] && in_array($c['codigoestado'], ['PROGRAMADA', 'CONFIRMADA'], true)): ?>
                <p class="faint" style="font-size:12.5px;margin:12px 0 0">La asistencia se registra el día de la cita.</p>
            <?php endif; ?>
        </div>
        <?php elseif ($gestiona && in_array($c['codigoestado'], ['CANCELADA', 'INASISTENCIA'], true)): ?>
            <div class="card card-pad anim no-print">
                <p class="soft m0" style="font-size:14px">Una cita <?= e(mb_strtolower(ESTADOS_CITA[$c['codigoestado']][0])) ?> no se puede reprogramar ni marcar como atendida. Si el paciente necesita atención, registre una cita nueva.</p>
                <a class="btn btn-primary btn-block mt" href="<?= e(url('/citas/nueva', ['paciente' => $c['idpaciente'], 'especialidad' => $c['idespecialidad'], 'medico' => $c['idmedico']])) ?>"><?= icono('calendario-mas') ?> Nueva cita para este paciente</a>
            </div>
        <?php endif; ?>

        <div class="card anim">
            <div class="card-head"><h2>Historial de la cita</h2></div>
            <div class="card-pad">
                <div class="timeline">
                    <?php foreach ($historial as $h): ?>
                        <div class="tl-item <?= e($h['codigoestado']) ?>">
                            <strong><?= e($ACCIONES[$h['accion']] ?? $h['accion']) ?></strong>
                            <?php if ($h['accion'] === 'REPROGRAMAR'): ?>
                                <p><?= e(fecha($h['fechaanterior'])) ?> <?= hora($h['horaanterior']) ?> → <?= e(fecha($h['fechanueva'])) ?> <?= hora($h['horanueva']) ?></p>
                            <?php endif; ?>
                            <?php if ($h['motivo']): ?><p>«<?= e($h['motivo']) ?>»</p><?php endif; ?>
                            <small><?= e(fecha_hora($h['fecha'])) ?><?= $h['usuario'] ? ' · ' . e($h['usuario']) : '' ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
