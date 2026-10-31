<?php
/**
 * CU5 · Listado de médicos (búsqueda por nombre o especialidad)
 * @var array $medicos @var array $especialidades @var array $f @var Paginador $pag
 */
$titulo = 'Médicos';
$gestiona = Auth::puede('medicos.gestionar');
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><?= icono('estetoscopio', 14) ?> Gestión médica</div>
        <h1>Médicos</h1>
        <p>Profesionales de la clínica, sus especialidades y su estado.</p>
    </div>
    <?php if ($gestiona): ?><a href="/medicos/nuevo" class="btn btn-primary"><?= icono('mas') ?> Registrar médico</a><?php endif; ?>
</div>

<div class="card anim">
    <form class="toolbar" method="get">
        <div class="input-icon"><?= icono('buscar') ?><input class="input" type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Buscar por nombre o código" aria-label="Buscar"></div>
        <select class="select" name="especialidad" data-autoenviar aria-label="Especialidad">
            <option value="">Todas las especialidades</option>
            <?php foreach ($especialidades as $esp): ?><option value="<?= $esp['idespecialidad'] ?>" <?= (string) $f['especialidad'] === (string) $esp['idespecialidad'] ? 'selected' : '' ?>><?= e($esp['nombre']) ?></option><?php endforeach; ?>
        </select>
        <select class="select" name="estado" data-autoenviar aria-label="Estado">
            <option value="">Activos e inactivos</option>
            <option value="ACTIVO" <?= $f['estado'] === 'ACTIVO' ? 'selected' : '' ?>>Activos</option>
            <option value="INACTIVO" <?= $f['estado'] === 'INACTIVO' ? 'selected' : '' ?>>Inactivos</option>
        </select>
        <button class="btn btn-outline" data-sin-carga="1">Buscar</button>
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Médico</th><th>Código</th><th>Especialidades</th><th>Contacto</th><th>Estado</th><th style="text-align:right">Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($medicos as $m): $activo = $m['estado'] === 'ACTIVO'; ?>
                <tr>
                    <td>
                        <a href="<?= e(url('/medicos/ver', ['id' => $m['idmedico']])) ?>" class="user-cell" style="color:inherit">
                            <div class="avatar" <?= !$activo ? 'style="filter:grayscale(1);opacity:.55"' : '' ?>><?= e(iniciales($m['nombre_completo'])) ?></div>
                            <div><strong>Dr(a). <?= e($m['nombre_completo']) ?></strong><small>Reg. <?= e($m['registroprofesional']) ?></small></div>
                        </a>
                    </td>
                    <td class="mono"><?= e($m['codigo']) ?></td>
                    <td><?php foreach (array_filter(explode(', ', $m['especialidades'])) as $esp): ?><span class="badge badge-primary" style="margin:2px"><?= e($esp) ?></span><?php endforeach; ?></td>
                    <td style="font-size:13px"><?= e($m['telefono'] ?: '—') ?><div class="faint"><?= e($m['email']) ?></div></td>
                    <td><?= badge_estado($m['estado']) ?></td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-ghost btn-icon btn-sm" href="<?= e(url('/medicos/ver', ['id' => $m['idmedico']])) ?>" title="Ver ficha y agenda"><?= icono('ver', 16) ?></a>
                            <?php if ($gestiona): ?>
                                <a class="btn btn-ghost btn-icon btn-sm" href="<?= e(url('/medicos/editar', ['id' => $m['idmedico']])) ?>" title="Editar"><?= icono('editar', 16) ?></a>
                                <?php if (Auth::puede('horarios.gestionar')): ?>
                                    <a class="btn btn-ghost btn-icon btn-sm" href="<?= e(url('/horarios', ['medico' => $m['idmedico']])) ?>" title="Horarios"><?= icono('reloj', 16) ?></a>
                                <?php endif; ?>
                                <form method="post" action="/medicos/estado" class="inline" data-confirm="<?= $activo ? 'Desactivar médico' : 'Activar médico' ?>"
                                      data-confirm-texto="<?= e('Dr(a). ' . $m['nombre_completo'] . ($activo ? ' no podrá recibir nuevas citas ni iniciar sesión. Su historial se conserva.' : ' volverá a recibir citas.')) ?>"
                                      data-confirm-ok="<?= $activo ? 'Desactivar' : 'Activar' ?>" <?= $activo ? 'data-peligro' : '' ?>>
                                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $m['idmedico'] ?>">
                                    <button class="btn btn-ghost btn-icon btn-sm <?= $activo ? 'btn-danger-ghost' : 'btn-ok-ghost' ?>" title="<?= $activo ? 'Desactivar' : 'Activar' ?>" data-sin-carga="1"><?= icono('power', 16) ?></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$medicos): ?><div class="empty"><?= icono('estetoscopio', 36) ?><div><strong>No se encontraron médicos</strong></div></div><?php endif; ?>
    </div>
    <?= View::parcial('paginacion', ['pag' => $pag]) ?>
</div>
