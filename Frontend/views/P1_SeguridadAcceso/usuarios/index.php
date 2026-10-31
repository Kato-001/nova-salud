<?php
/**
 * CU3 · Listado de usuarios
 * @var array $usuarios @var array $roles @var array $conteo @var array $f @var Paginador $pag
 */
$titulo = 'Usuarios';
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><?= icono('usuarios', 14) ?> Administración</div>
        <h1>Usuarios del sistema</h1>
        <p>Cuentas de acceso y rol asignado a cada persona.</p>
    </div>
    <a href="/usuarios/nuevo" class="btn btn-primary"><?= icono('mas') ?> Nuevo usuario</a>
</div>

<div class="card anim">
    <form class="toolbar" method="get">
        <div class="input-icon">
            <?= icono('buscar') ?>
            <input class="input" type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Buscar por nombre, usuario o correo" aria-label="Buscar">
        </div>
        <select class="select" name="rol" data-autoenviar aria-label="Filtrar por rol">
            <option value="">Todos los roles</option>
            <?php foreach ($roles as $r): ?>
                <option value="<?= $r['idrol'] ?>" <?= (string) $f['rol'] === (string) $r['idrol'] ? 'selected' : '' ?>><?= e($r['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="estado" value="<?= e($f['estado']) ?>">
        <div class="pill-tabs" role="tablist">
            <?php foreach (['' => 'Todos', 'ACTIVO' => 'Activos', 'INACTIVO' => 'Inactivos'] as $v => $l): ?>
                <a href="<?= e(url('/usuarios', ['q' => $f['q'], 'rol' => $f['rol'], 'estado' => $v])) ?>" class="<?= $f['estado'] === $v ? 'on' : '' ?>"><?= $l ?> <span class="n"><?= $conteo[$v] ?></span></a>
            <?php endforeach; ?>
        </div>
        <button class="btn btn-outline" data-sin-carga="1">Buscar</button>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Usuario</th><th>Nombre de usuario</th><th>Rol</th><th>Último acceso</th><th>Estado</th><th style="text-align:right">Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($usuarios as $usr): $esYo = (int) $usr['idusuario'] === Auth::id(); ?>
                <tr>
                    <td>
                        <div class="user-cell">
                            <div class="avatar" <?= $usr['estado'] !== 'ACTIVO' ? 'style="filter:grayscale(1);opacity:.55"' : '' ?>><?= e(iniciales($usr['nombre'])) ?></div>
                            <div><strong><?= e($usr['nombre']) ?><?= $esYo ? ' <span class="faint" style="font-weight:500">(usted)</span>' : '' ?></strong><small><?= e($usr['email']) ?></small></div>
                        </div>
                    </td>
                    <td class="mono"><?= e($usr['username']) ?></td>
                    <td><?= badge_rol($usr['rol']) ?></td>
                    <td class="soft" title="<?= e(fecha_hora($usr['ultimoacceso'])) ?>"><?= e(hace_cuanto($usr['ultimoacceso'])) ?></td>
                    <td><?= badge_estado($usr['estado']) ?></td>
                    <td>
                        <div class="actions">
                            <?php if ($usr['idmedico']): ?>
                                <a class="btn btn-ghost btn-icon btn-sm" href="<?= e(url('/medicos/ver', ['id' => $usr['idmedico']])) ?>" title="Ver ficha del médico"><?= icono('estetoscopio', 16) ?></a>
                            <?php elseif ($usr['idpaciente']): ?>
                                <a class="btn btn-ghost btn-icon btn-sm" href="<?= e(url('/pacientes/ver', ['id' => $usr['idpaciente']])) ?>" title="Ver ficha del paciente"><?= icono('pulso', 16) ?></a>
                            <?php endif; ?>
                            <a class="btn btn-ghost btn-icon btn-sm" href="<?= e(url('/usuarios/editar', ['id' => $usr['idusuario']])) ?>" title="Editar" aria-label="Editar a <?= e($usr['nombre']) ?>"><?= icono('editar', 16) ?></a>
                            <?php if (!$esYo): $activo = $usr['estado'] === 'ACTIVO'; ?>
                                <form method="post" action="/usuarios/estado" class="inline"
                                      data-confirm="<?= $activo ? 'Desactivar cuenta' : 'Activar cuenta' ?>"
                                      data-confirm-texto="<?= e($usr['nombre'] . ($activo ? ' no podrá iniciar sesión hasta que se reactive su cuenta.' : ' podrá volver a iniciar sesión.')) ?>"
                                      data-confirm-ok="<?= $activo ? 'Desactivar' : 'Activar' ?>" <?= $activo ? 'data-peligro' : '' ?>>
                                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $usr['idusuario'] ?>">
                                    <button class="btn btn-ghost btn-icon btn-sm <?= $activo ? 'btn-danger-ghost' : 'btn-ok-ghost' ?>" title="<?= $activo ? 'Desactivar' : 'Activar' ?>" data-sin-carga="1"><?= icono('power', 16) ?></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$usuarios): ?>
            <div class="empty"><?= icono('usuario-mas', 36) ?><div><strong>No se encontraron usuarios</strong></div><div class="faint">Pruebe con otro término o filtro.</div></div>
        <?php endif; ?>
    </div>
    <?= View::parcial('paginacion', ['pag' => $pag]) ?>
</div>
<p class="faint" style="font-size:13px;margin-top:14px">
    Las cuentas de médicos se crean desde <a href="/medicos/nuevo">Médicos</a> y las de pacientes al registrarlos. Una cuenta inactiva no puede iniciar sesión.
</p>
