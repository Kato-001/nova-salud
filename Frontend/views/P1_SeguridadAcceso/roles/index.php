<?php
/**
 * CU3 · Roles y matriz de permisos
 * @var array $roles @var array $permisos (agrupados por módulo) @var array $matriz
 */
$titulo = 'Roles y permisos';
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><?= icono('escudo', 14) ?> Administración</div>
        <h1>Roles y permisos</h1>
        <p>Cada usuario tiene un rol; cada rol tiene permisos que definen el menú y las operaciones que puede realizar.</p>
    </div>
</div>

<div class="roles-layout">
    <div class="stack">
        <form method="post" action="/roles/guardar" class="card card-pad anim" data-validar>
            <?= csrf_field() ?>
            <h2 style="font-size:17px;margin-bottom:16px">Nuevo rol</h2>
            <div class="stack">
                <label class="field">
                    <span>Nombre *</span>
                    <input class="input<?= clase_error('nombre') ?>" name="nombre" value="<?= e(old('nombre')) ?>" required minlength="3" maxlength="50"
                           pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ_ ]+" data-msg="Solo letras." placeholder="Ej. ENFERMERIA">
                    <?= error_campo('nombre') ?: '<small>Se guarda en mayúsculas.</small>' ?>
                </label>
                <label class="field">
                    <span>Descripción</span>
                    <textarea class="textarea" name="descripcion" rows="2" maxlength="255"><?= e(old('descripcion')) ?></textarea>
                </label>
                <button class="btn btn-primary"><?= icono('mas') ?> Agregar rol</button>
            </div>
        </form>

        <div class="card anim">
            <div class="card-head"><h2>Roles</h2><span class="faint" style="font-size:13px"><?= count($roles) ?> roles</span></div>
            <?php foreach ($roles as $r): $sistema = in_array($r['nombre'], Rol::DEL_SISTEMA, true); ?>
                <div class="role-card">
                    <div class="grow">
                        <h3><?= badge_rol($r['nombre']) ?>
                            <?php if ($sistema): ?><span class="badge badge-lock"><?= icono('candado', 11) ?> Sistema</span><?php endif; ?>
                            <span class="faint" style="font-size:13px;font-weight:500"><?= (int) $r['usuarios'] ?> usuario(s)</span>
                        </h3>
                        <form method="post" action="/roles/guardar" class="inline-edit">
                            <?= csrf_field() ?><input type="hidden" name="idrol" value="<?= $r['idrol'] ?>">
                            <input class="input" name="descripcion" value="<?= e($r['descripcion']) ?>" maxlength="255" aria-label="Descripción de <?= e($r['nombre']) ?>" placeholder="Sin descripción">
                            <button class="btn btn-ghost btn-icon btn-sm" title="Guardar descripción" data-sin-carga="1"><?= icono('check', 16) ?></button>
                        </form>
                    </div>
                    <?php if (!$sistema && !(int) $r['usuarios']): ?>
                        <form method="post" action="/roles/eliminar" class="inline" data-confirm="Eliminar el rol <?= e($r['nombre']) ?>" data-confirm-texto="Esta acción no se puede deshacer." data-confirm-ok="Eliminar" data-peligro>
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['idrol'] ?>">
                            <button class="btn btn-ghost btn-icon btn-sm btn-danger-ghost" title="Eliminar" data-sin-carga="1"><?= icono('basura', 16) ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <div class="card-pad" style="padding-top:14px;border-top:1px solid var(--border)">
                <p class="faint m0" style="font-size:13px">Los roles del sistema no se pueden renombrar ni eliminar. Un rol solo se elimina si no tiene usuarios.</p>
            </div>
        </div>
    </div>

    <div class="card anim">
        <div class="card-head"><h2>Permisos por rol</h2><span class="faint" style="font-size:13px">Marque y guarde cada columna</span></div>
        <div class="table-wrap">
            <table class="table matrix">
                <thead>
                <tr>
                    <th>Permiso</th>
                    <?php foreach ($roles as $r): ?><th><?= e(['ADMINISTRADOR' => 'ADMIN', 'RECEPCIONISTA' => 'RECEPCIÓN', 'MEDICO' => 'MÉDICO'][$r['nombre']] ?? $r['nombre']) ?></th><?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($permisos as $modulo => $lista): ?>
                    <tr class="mod"><td colspan="<?= count($roles) + 1 ?>"><?= e($modulo) ?></td></tr>
                    <?php foreach ($lista as $p): ?>
                        <tr>
                            <td><?= e($p['nombre']) ?><small><?= e($p['codigo']) ?></small></td>
                            <?php foreach ($roles as $r):
                                $marcado = in_array((int) $p['idpermiso'], $matriz[(int) $r['idrol']] ?? [], true);
                                $fijo = $r['nombre'] === 'ADMINISTRADOR' && in_array($p['codigo'], Rol::PERMISOS_ADMIN_FIJOS, true); ?>
                                <td><input type="checkbox" form="permisos-<?= $r['idrol'] ?>" name="permisos[]" value="<?= $p['idpermiso'] ?>"
                                           <?= $marcado ? 'checked' : '' ?> <?= $fijo ? 'disabled title="El administrador siempre conserva este permiso"' : '' ?>
                                           aria-label="<?= e($p['nombre'] . ' para ' . $r['nombre']) ?>"></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                <tr>
                    <td></td>
                    <?php foreach ($roles as $r): ?>
                        <td>
                            <form method="post" action="/roles/permisos" id="permisos-<?= $r['idrol'] ?>">
                                <?= csrf_field() ?><input type="hidden" name="idrol" value="<?= $r['idrol'] ?>">
                                <button class="btn btn-outline btn-sm">Guardar</button>
                            </form>
                        </td>
                    <?php endforeach; ?>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
