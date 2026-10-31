<?php
/**
 * Gráfico de anillo con los estados de las citas (conic-gradient en CSS).
 * @var array $conteo codigo_estado => cantidad
 */
$colores = ['PROGRAMADA' => 'var(--info)', 'CONFIRMADA' => 'var(--accent)', 'ATENDIDA' => 'var(--ok)', 'CANCELADA' => 'var(--text-faint)', 'INASISTENCIA' => 'var(--warn)'];
$total = array_sum($conteo);
$partes = [];
$acum = 0;
foreach ($conteo as $estado => $n) {
    if (!$n) continue;
    $desde = $acum / max(1, $total) * 360;
    $acum += $n;
    $partes[] = ($colores[$estado] ?? 'gray') . ' ' . round($desde, 2) . 'deg ' . round($acum / $total * 360, 2) . 'deg';
}
$fondo = $partes ? 'conic-gradient(' . implode(', ', $partes) . ')' : 'var(--bg-sunken)';
?>
<div class="donut-wrap">
    <div class="donut" style="background: <?= $fondo ?>">
        <div class="centro"><b data-contar="<?= $total ?>">0</b><small>citas</small></div>
    </div>
    <div class="leyenda" style="flex:1;min-width:170px">
        <?php foreach ($conteo as $estado => $n): ?>
            <span><i style="background: <?= $colores[$estado] ?? 'gray' ?>"></i><?= e(ESTADOS_CITA[$estado][0] ?? $estado) ?><b><?= $n ?></b></span>
        <?php endforeach; ?>
    </div>
</div>
