<?php
/**
 * Bitácora de auditoría
 * @var array $filas @var array $acciones @var array $f @var Paginador $pag
 */
$titulo = 'Bitácora';
$colores = ['CREAR' => 'badge-ok', 'ACTIVAR' => 'badge-ok', 'EDITAR' => 'badge-accent', 'PERMISOS' => 'badge-accent', 'ELIMINAR' => 'badge-danger',
            'DESACTIVAR' => 'badge-warn', 'CANCELAR' => 'badge-warn', 'LOGIN_FALLIDO' => 'badge-danger', 'INICIAR_SESION' => 'badge-info',
            'CERRAR_SESION' => 'badge-off', 'REPROGRAMAR' => 'badge-primary', 'ATENDER' => 'badge-ok', 'CONFIRMAR' => 'badge-accent'];
?>
<div class="page-head">
    <div>
        <div class="eyebrow"><?= icono('bitacora', 14) ?> Auditoría</div>
        <h1>Bitácora</h1>
        <p>Registro de inicios de sesión y de cada operación realizada en el sistema, con usuario, fecha e IP.</p>
    </div>
</div>

<div class="card anim">
    <form class="toolbar" method="get">
        <div class="input-icon"><?= icono('buscar') ?><input class="input" type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Buscar por descripción o usuario" aria-label="Buscar"></div>
        <select class="select" name="accion" data-autoenviar aria-label="Acción">
            <option value="">Todas las acciones</option>
            <?php foreach ($acciones as $a): ?><option value="<?= e($a) ?>" <?= $f['accion'] === $a ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $a)) ?></option><?php endforeach; ?>
        </select>
        <input class="input" type="date" name="desde" value="<?= e($f['desde']) ?>" style="width:auto" aria-label="Desde">
        <input class="input" type="date" name="hasta" value="<?= e($f['hasta']) ?>" style="width:auto" aria-label="Hasta">
        <button class="btn btn-outline" data-sin-carga="1"><?= icono('filtro', 16) ?> Filtrar</button>
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Módulo</th><th>Descripción</th><th>IP</th></tr></thead>
            <tbody>
            <?php foreach ($filas as $b): ?>
                <tr>
                    <td class="soft" style="white-space:nowrap"><?= e(fecha_hora($b['fecha'])) ?></td>
                    <td><?php if ($b['usuario']): ?><strong style="font-weight:600"><?= e($b['usuario']) ?></strong><div class="mono faint"><?= e($b['username']) ?></div><?php else: ?><span class="faint">—</span><?php endif; ?></td>
                    <td><span class="badge <?= $colores[$b['accion']] ?? 'badge-off' ?>"><?= e(str_replace('_', ' ', $b['accion'])) ?></span></td>
                    <td class="faint" style="font-size:12.5px"><?= e($b['modulo']) ?><?= $b['tabla'] ? '<div class="mono">' . e($b['tabla']) . ($b['idregistro'] ? ' #' . (int) $b['idregistro'] : '') . '</div>' : '' ?></td>
                    <td><?= e($b['descripcion']) ?></td>
                    <td class="mono faint"><?= e($b['ip'] ?: '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$filas): ?><div class="empty">No hay registros con esos filtros.</div><?php endif; ?>
    </div>
    <?= View::parcial('paginacion', ['pag' => $pag]) ?>
</div>
