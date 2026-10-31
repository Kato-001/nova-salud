<?php
/**
 * CU18 · Catálogo de estudios (CATALOGOESTUDIO)
 * @var array $catalogo @var ?array $edicion
 */
$titulo = 'Catálogo de estudios';
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><a href="/estudios"><?= icono('izquierda', 14) ?> Estudios médicos</a></div>
        <h1>Catálogo de estudios</h1>
        <p>Laboratorios e imágenes que el médico puede solicitar durante la consulta.</p>
    </div>
</div>

<div class="roles-layout">
    <form method="post" action="/estudios/catalogo" class="card card-pad anim" data-validar>
        <?= csrf_field() ?>
        <?php if ($edicion): ?><input type="hidden" name="idcatalogo" value="<?= $edicion['idcatalogo'] ?>"><?php endif; ?>
        <h2 style="font-size:17px;margin-bottom:16px"><?= $edicion ? 'Editar estudio' : 'Nuevo estudio' ?></h2>
        <div class="stack">
            <label class="field"><span>Nombre *</span>
                <input class="input<?= clase_error('nombre') ?>" name="nombre" value="<?= e(old('nombre', $edicion['nombre'] ?? '')) ?>" required minlength="3" maxlength="150" placeholder="Ej. Hemograma completo"><?= error_campo('nombre') ?></label>
            <label class="field"><span>Categoría</span>
                <input class="input" name="categoria" value="<?= e(old('categoria', $edicion['categoria'] ?? '')) ?>" maxlength="100" list="categorias" placeholder="Laboratorio, Imagen…">
                <datalist id="categorias"><option>Laboratorio</option><option>Imagen</option><option>Cardiología</option></datalist></label>
            <label class="field"><span>Descripción</span>
                <textarea class="textarea" name="descripcion" rows="2" maxlength="255"><?= e(old('descripcion', $edicion['descripcion'] ?? '')) ?></textarea></label>
            <div class="row">
                <button class="btn btn-primary" style="flex:1"><?= icono($edicion ? 'check' : 'mas') ?> <?= $edicion ? 'Guardar cambios' : 'Agregar' ?></button>
                <?php if ($edicion): ?><a href="/estudios/catalogo" class="btn btn-ghost">Cancelar</a><?php endif; ?>
            </div>
        </div>
    </form>
    <div class="card anim">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Estudio</th><th>Categoría</th><th>Solicitados</th><th>Estado</th><th style="text-align:right">Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($catalogo as $c): $activo = $c['estado'] === 'ACTIVO'; ?>
                    <tr>
                        <td><strong><?= e($c['nombre']) ?></strong><div class="faint" style="font-size:12.5px"><?= e($c['descripcion'] ?: '') ?></div></td>
                        <td><?= e($c['categoria'] ?: '—') ?></td>
                        <td><?= (int) $c['usos'] ?></td>
                        <td><?= badge_estado($c['estado']) ?></td>
                        <td><div class="actions">
                            <a class="btn btn-ghost btn-icon btn-sm" href="<?= e(url('/estudios/catalogo', ['editar' => $c['idcatalogo']])) ?>" title="Editar"><?= icono('editar', 16) ?></a>
                            <form method="post" action="/estudios/catalogo/estado" class="inline">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['idcatalogo'] ?>">
                                <button class="btn btn-ghost btn-icon btn-sm <?= $activo ? 'btn-danger-ghost' : 'btn-ok-ghost' ?>" title="<?= $activo ? 'Desactivar' : 'Activar' ?>" data-sin-carga="1"><?= icono('power', 16) ?></button>
                            </form>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
