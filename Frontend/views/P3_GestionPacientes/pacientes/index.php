<?php
/**
 * CU7 · Listado y búsqueda de pacientes (CI, nombre o apellido)
 * @var array $pacientes @var array $f @var Paginador $pag
 */
$titulo = 'Pacientes';
$gestiona = Auth::puede('pacientes.gestionar');
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><?= icono('pulso', 14) ?> Gestión de pacientes</div>
        <h1>Pacientes</h1>
        <p>Busque por CI, nombre o apellido. Los pacientes no se eliminan: se desactivan conservando su historial.</p>
    </div>
    <?php if ($gestiona): ?><a href="/pacientes/nuevo" class="btn btn-primary"><?= icono('usuario-mas') ?> Registrar paciente</a><?php endif; ?>
</div>

<div class="card anim">
    <form class="toolbar" method="get">
        <div class="input-icon"><?= icono('buscar') ?><input class="input" type="search" name="q" value="<?= e($f['q']) ?>" placeholder="CI, nombre, apellido o código" aria-label="Buscar" autofocus></div>
        <select class="select" name="estado" data-autoenviar aria-label="Estado">
            <option value="">Activos e inactivos</option>
            <option value="ACTIVO" <?= $f['estado'] === 'ACTIVO' ? 'selected' : '' ?>>Activos</option>
            <option value="INACTIVO" <?= $f['estado'] === 'INACTIVO' ? 'selected' : '' ?>>Inactivos</option>
        </select>
        <button class="btn btn-outline" data-sin-carga="1">Buscar</button>
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Paciente</th><th>CI</th><th>Edad</th><th>Teléfono</th><th>Citas</th><th>Estado</th><th style="text-align:right">Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($pacientes as $p): $activo = $p['estado'] === 'ACTIVO'; ?>
                <tr>
                    <td>
                        <a href="<?= e(url('/pacientes/ver', ['id' => $p['idpaciente']])) ?>" class="user-cell" style="color:inherit">
                            <div class="avatar" <?= !$activo ? 'style="filter:grayscale(1);opacity:.55"' : '' ?>><?= e(iniciales($p['nombre_completo'])) ?></div>
                            <div><strong><?= e($p['apellidos'] . ', ' . $p['nombres']) ?></strong><small class="mono"><?= e($p['codigo']) ?></small></div>
                        </a>
                    </td>
                    <td class="mono"><?= e($p['ci']) ?></td>
                    <td><?= e(edad($p['fechanacimiento'])) ?></td>
                    <td><?= e($p['telefono']) ?></td>
                    <td><?= (int) $p['total_citas'] ?><?= $p['ultima_atencion'] ? '<div class="faint" style="font-size:12px">Última: ' . e(fecha($p['ultima_atencion'])) . '</div>' : '' ?></td>
                    <td><?= badge_estado($p['estado']) ?></td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-ghost btn-icon btn-sm" href="<?= e(url('/pacientes/ver', ['id' => $p['idpaciente']])) ?>" title="Ver ficha e historial"><?= icono('ver', 16) ?></a>
                            <?php if ($activo && Auth::puede('citas.gestionar')): ?>
                                <a class="btn btn-ghost btn-icon btn-sm" href="<?= e(url('/citas/nueva', ['paciente' => $p['idpaciente']])) ?>" title="Programar cita"><?= icono('calendario-mas', 16) ?></a>
                            <?php endif; ?>
                            <?php if ($gestiona): ?>
                                <a class="btn btn-ghost btn-icon btn-sm" href="<?= e(url('/pacientes/editar', ['id' => $p['idpaciente']])) ?>" title="Editar"><?= icono('editar', 16) ?></a>
                                <form method="post" action="/pacientes/estado" class="inline" data-confirm="<?= $activo ? 'Desactivar paciente' : 'Activar paciente' ?>"
                                      data-confirm-texto="<?= e($p['nombre_completo'] . ($activo ? ': no podrá recibir nuevas citas. Su historial se conserva.' : ': podrá volver a recibir citas.')) ?>"
                                      data-confirm-ok="<?= $activo ? 'Desactivar' : 'Activar' ?>" <?= $activo ? 'data-peligro' : '' ?>>
                                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['idpaciente'] ?>">
                                    <button class="btn btn-ghost btn-icon btn-sm <?= $activo ? 'btn-danger-ghost' : 'btn-ok-ghost' ?>" title="<?= $activo ? 'Desactivar' : 'Activar' ?>" data-sin-carga="1"><?= icono('power', 16) ?></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$pacientes): ?>
            <div class="empty"><?= icono('buscar', 36) ?><div><strong>No se encontraron pacientes</strong></div>
                <?php if ($gestiona): ?><a href="/pacientes/nuevo" class="btn btn-outline btn-sm mt">Registrar paciente nuevo</a><?php endif; ?></div>
        <?php endif; ?>
    </div>
    <?= View::parcial('paginacion', ['pag' => $pag]) ?>
</div>
