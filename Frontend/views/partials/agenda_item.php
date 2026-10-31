<?php
/**
 * Fila de la agenda: hora, código, paciente, especialidad, estado y motivo (si hay permiso).
 * @var array $c @var bool $verMotivo
 */
?>
<a href="<?= e(url('/citas/ver', ['id' => $c['idcita']])) ?>" class="agenda-item <?= e($c['codigoestado']) ?>">
    <div class="h"><?= hora($c['horainicio']) ?><small><?= hora($c['horafin']) ?></small></div>
    <div style="min-width:0">
        <strong><?= e($c['paciente']) ?></strong>
        <div class="sub"><span class="mono"><?= e($c['codigo']) ?></span> · <?= e($c['especialidad']) ?><?= $verMotivo && $c['motivo'] ? ' · ' . e($c['motivo']) : '' ?></div>
    </div>
    <?= badge_cita($c['codigoestado']) ?>
</a>
