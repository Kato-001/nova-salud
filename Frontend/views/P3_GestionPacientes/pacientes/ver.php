<?php
/**
 * CU7 · Ficha del paciente · RF12 · Historial de citas
 * @var array $p @var array $citas @var array $conteo @var array $contactos @var array $consultas
 */
$titulo = $p['nombre_completo'];
$tab = in_array($_GET['tab'] ?? '', ['citas', 'clinica', 'contactos'], true) ? $_GET['tab'] : 'citas';
$gestiona = Auth::puede('pacientes.gestionar');
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><a href="/pacientes"><?= icono('izquierda', 14) ?> Pacientes</a></div>
        <h1><?= e($p['nombre_completo']) ?></h1>
        <p><span class="mono"><?= e($p['codigo']) ?></span> · CI <?= e($p['ci']) ?> · Historia <?= e($p['numero_historia']) ?></p>
    </div>
    <div class="row">
        <?php if ($gestiona): ?><a href="<?= e(url('/pacientes/editar', ['id' => $p['idpaciente']])) ?>" class="btn btn-outline"><?= icono('editar') ?> Editar</a><?php endif; ?>
        <?php if ($p['estado'] === 'ACTIVO' && Auth::puede('citas.gestionar')): ?>
            <a href="<?= e(url('/citas/nueva', ['paciente' => $p['idpaciente']])) ?>" class="btn btn-primary"><?= icono('calendario-mas') ?> Programar cita</a>
        <?php endif; ?>
    </div>
</div>

<div class="ficha">
    <div class="card anim">
        <div class="ficha-head">
            <div class="avatar lg"><?= e(iniciales($p['nombre_completo'])) ?></div>
            <h2><?= e($p['nombre_completo']) ?></h2>
            <p><?= e(Paciente::SEXOS[$p['sexo']] ?? '') ?> · <?= e(edad($p['fechanacimiento'])) ?></p>
            <?= badge_estado($p['estado']) ?>
            <?php if ($p['username']): ?><span class="badge badge-info"><?= icono('usuario', 12) ?> Con cuenta</span><?php endif; ?>
        </div>
        <div class="kv-list">
            <div><span><?= icono('calendario', 16) ?> Nacimiento</span><span><?= e(fecha($p['fechanacimiento'])) ?></span></div>
            <div><span><?= icono('telefono', 16) ?> Teléfono</span><span><?= e($p['telefono']) ?></span></div>
            <div><span><?= icono('correo', 16) ?> Correo</span><span><?= e($p['email'] ?: '—') ?></span></div>
            <div><span><?= icono('inicio', 16) ?> Dirección</span><span><?= e($p['direccion'] ?: '—') ?></span></div>
            <div><span><?= icono('gota', 16) ?> Tipo de sangre</span><span><?= e($p['tiposangre'] ?: '—') ?></span></div>
            <div><span><?= icono('alerta', 16) ?> Alergias</span><span><?= e($p['alergias'] ?: '—') ?></span></div>
            <div><span><?= icono('campana', 16) ?> Recordatorios</span><span><?= $p['aceptanotificaciones'] ? 'Sí' : 'No' ?></span></div>
        </div>
    </div>

    <div>
        <div class="card card-pad anim mb">
            <div class="mini-stats">
                <div><b data-contar="<?= count($citas) ?>">0</b><small>Citas en total</small></div>
                <div><b data-contar="<?= ($conteo['ATENDIDA'] ?? 0) ?>">0</b><small>Atendidas</small></div>
                <div><b data-contar="<?= ($conteo['CANCELADA'] ?? 0) ?>">0</b><small>Canceladas</small></div>
                <div><b data-contar="<?= ($conteo['INASISTENCIA'] ?? 0) ?>">0</b><small>Inasistencias</small></div>
            </div>
        </div>

        <nav class="tabs">
            <a href="<?= e(url('/pacientes/ver', ['id' => $p['idpaciente'], 'tab' => 'citas'])) ?>" class="<?= $tab === 'citas' ? 'on' : '' ?>"><?= icono('calendario', 16) ?> Historial de citas</a>
            <a href="<?= e(url('/pacientes/ver', ['id' => $p['idpaciente'], 'tab' => 'clinica'])) ?>" class="<?= $tab === 'clinica' ? 'on' : '' ?>"><?= icono('portapapeles', 16) ?> Historia clínica</a>
            <a href="<?= e(url('/pacientes/ver', ['id' => $p['idpaciente'], 'tab' => 'contactos'])) ?>" id="contactos" class="<?= $tab === 'contactos' ? 'on' : '' ?>"><?= icono('telefono', 16) ?> Contactos de emergencia</a>
        </nav>

        <?php if ($tab === 'citas'): ?>
            <div class="card anim">
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Código</th><th>Fecha y hora</th><th>Médico</th><th>Especialidad</th><th>Estado</th><th>Registrada</th></tr></thead>
                        <tbody>
                        <?php foreach ($citas as $c): ?>
                            <tr>
                                <td class="mono"><a href="<?= e(url('/citas/ver', ['id' => $c['idcita']])) ?>"><?= e($c['codigo']) ?></a></td>
                                <td><strong><?= e(fecha($c['fecha'])) ?></strong><div class="mono faint"><?= hora($c['horainicio']) ?>–<?= hora($c['horafin']) ?></div></td>
                                <td><?= e($c['medico']) ?></td>
                                <td><?= e($c['especialidad']) ?></td>
                                <td><?= badge_cita($c['codigoestado']) ?></td>
                                <td class="faint" style="font-size:13px"><?= e(fecha_hora($c['fecharegistro'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (!$citas): ?><div class="empty">El paciente aún no tiene citas.</div><?php endif; ?>
                </div>
            </div>

        <?php elseif ($tab === 'clinica'): ?>
            <form method="post" action="/pacientes/historia" class="card card-pad anim mb" data-validar>
                <?= csrf_field() ?><input type="hidden" name="idpaciente" value="<?= $p['idpaciente'] ?>">
                <div class="row-between mb"><h2 style="font-size:16px">Antecedentes · <?= e($p['numero_historia']) ?></h2></div>
                <div class="grid-2">
                    <?php foreach (['alergias' => 'Alergias', 'enfermedadescronicas' => 'Enfermedades crónicas', 'medicacionhabitual' => 'Medicación habitual',
                                    'antecedentespersonales' => 'Antecedentes personales', 'antecedentesfamiliares' => 'Antecedentes familiares'] as $k => $l): ?>
                        <label class="field"><span><?= $l ?></span><textarea class="textarea" name="<?= $k ?>" rows="2" maxlength="2000" <?= $gestiona ? '' : 'readonly' ?>><?= e($p[$k]) ?></textarea></label>
                    <?php endforeach; ?>
                </div>
                <?php if ($gestiona): ?><div class="row mt" style="justify-content:flex-end"><button class="btn btn-primary">Guardar antecedentes</button></div><?php endif; ?>
            </form>
            <div class="card anim">
                <div class="card-head"><h2>Consultas registradas</h2><span class="faint" style="font-size:13px"><?= count($consultas) ?></span></div>
                <?= View::parcial('consultas', ['consultas' => $consultas]) ?>
            </div>

        <?php else: ?>
            <div class="card anim">
                <?php foreach ($contactos as $ct): ?>
                    <div class="role-card">
                        <div class="avatar"><?= e(iniciales($ct['nombre'])) ?></div>
                        <div class="grow"><h3><?= e($ct['nombre']) ?> <span class="badge badge-off"><?= e($ct['parentesco']) ?></span></h3><p><?= icono('telefono', 14) ?> <?= e($ct['telefono']) ?></p></div>
                        <?php if ($gestiona): ?>
                            <form method="post" action="/pacientes/contacto/eliminar" class="inline" data-confirm="Quitar contacto" data-confirm-ok="Quitar" data-peligro>
                                <?= csrf_field() ?><input type="hidden" name="idpaciente" value="<?= $p['idpaciente'] ?>"><input type="hidden" name="idcontacto" value="<?= $ct['idcontacto'] ?>">
                                <button class="btn btn-ghost btn-icon btn-sm btn-danger-ghost" title="Quitar" data-sin-carga="1"><?= icono('basura', 16) ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (!$contactos): ?><div class="empty" style="padding:30px">Sin contactos de emergencia registrados.</div><?php endif; ?>
                <?php if ($gestiona): ?>
                    <form method="post" action="/pacientes/contacto" class="card-pad" style="border-top:1px solid var(--border)" data-validar>
                        <?= csrf_field() ?><input type="hidden" name="idpaciente" value="<?= $p['idpaciente'] ?>">
                        <div class="grid-3">
                            <label class="field"><span>Nombre *</span><input class="input" name="nombre" required minlength="3" maxlength="150"></label>
                            <label class="field"><span>Parentesco *</span><input class="input" name="parentesco" required minlength="3" maxlength="50" placeholder="Ej. Madre"></label>
                            <label class="field"><span>Teléfono *</span><input class="input" name="telefono" required pattern="[0-9+\-\s]{7,20}" data-msg="7 a 20 dígitos." maxlength="20"></label>
                        </div>
                        <div class="row mt" style="justify-content:flex-end"><button class="btn btn-outline"><?= icono('mas', 16) ?> Agregar contacto</button></div>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
