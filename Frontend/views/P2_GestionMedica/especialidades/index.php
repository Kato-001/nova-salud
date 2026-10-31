<?php
/**
 * CU4 · Gestionar especialidades
 * @var array $especialidades @var ?array $edicion
 */
$titulo = 'Especialidades';
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><?= icono('cruz', 14) ?> Gestión médica</div>
        <h1>Especialidades</h1>
        <p>Servicios que ofrece la clínica. No se permiten nombres repetidos (se comparan sin tildes ni mayúsculas).</p>
    </div>
</div>

<div class="roles-layout">
    <form method="post" action="/especialidades/guardar" class="card card-pad anim" data-validar>
        <?= csrf_field() ?>
        <?php if ($edicion): ?><input type="hidden" name="idespecialidad" value="<?= $edicion['idespecialidad'] ?>"><?php endif; ?>
        <h2 style="font-size:17px;margin-bottom:16px"><?= $edicion ? 'Editar especialidad' : 'Nueva especialidad' ?></h2>
        <div class="stack">
            <label class="field">
                <span>Nombre *</span>
                <input class="input<?= clase_error('nombre') ?>" name="nombre" value="<?= e(old('nombre', $edicion['nombre'] ?? '')) ?>" required minlength="3" maxlength="100"
                       pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü ]+" data-msg="Solo letras y espacios." placeholder="Ej. Odontología" <?= $edicion ? 'autofocus' : '' ?>>
                <?= error_campo('nombre') ?>
            </label>
            <label class="field">
                <span>Descripción</span>
                <textarea class="textarea" name="descripcion" rows="3" maxlength="255"><?= e(old('descripcion', $edicion['descripcion'] ?? '')) ?></textarea>
            </label>
            <div class="row">
                <button class="btn btn-primary" style="flex:1"><?= icono($edicion ? 'check' : 'mas') ?> <?= $edicion ? 'Guardar cambios' : 'Registrar' ?></button>
                <?php if ($edicion): ?><a href="/especialidades" class="btn btn-ghost">Cancelar</a><?php endif; ?>
            </div>
        </div>
    </form>

    <div class="card anim">
        <div class="card-head"><h2>Especialidades registradas</h2><span class="faint" style="font-size:13px"><?= count($especialidades) ?> en total</span></div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Especialidad</th><th>Médicos activos</th><th>Citas</th><th>Estado</th><th style="text-align:right">Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($especialidades as $esp): $activo = $esp['estado'] === 'ACTIVO'; ?>
                    <tr>
                        <td><strong><?= e($esp['nombre']) ?></strong><div class="faint" style="font-size:12.5px"><?= e($esp['descripcion'] ?: 'Sin descripción') ?></div></td>
                        <td><a href="<?= e(url('/medicos', ['especialidad' => $esp['idespecialidad']])) ?>"><?= (int) $esp['medicos'] ?> médico(s)</a></td>
                        <td><?= (int) $esp['citas'] ?></td>
                        <td><?= badge_estado($esp['estado']) ?></td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-ghost btn-icon btn-sm" href="<?= e(url('/especialidades', ['editar' => $esp['idespecialidad']])) ?>" title="Editar"><?= icono('editar', 16) ?></a>
                                <form method="post" action="/especialidades/estado" class="inline" data-confirm="<?= $activo ? 'Desactivar' : 'Activar' ?> <?= e($esp['nombre']) ?>"
                                      data-confirm-texto="<?= $activo ? 'Dejará de aparecer al programar nuevas citas. Las citas existentes se conservan.' : 'Volverá a estar disponible para programar citas.' ?>"
                                      data-confirm-ok="<?= $activo ? 'Desactivar' : 'Activar' ?>" <?= $activo ? 'data-peligro' : '' ?>>
                                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $esp['idespecialidad'] ?>">
                                    <button class="btn btn-ghost btn-icon btn-sm <?= $activo ? 'btn-danger-ghost' : 'btn-ok-ghost' ?>" title="<?= $activo ? 'Desactivar' : 'Activar' ?>" data-sin-carga="1"><?= icono('power', 16) ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
