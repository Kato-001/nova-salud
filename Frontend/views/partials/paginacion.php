<?php
/** @var Paginador $pag */
if ($pag->total === 0) return;
?>
<div class="table-foot">
    <span>Mostrando <?= $pag->desde() ?>–<?= $pag->hasta() ?> de <?= $pag->total ?></span>
    <?php if ($pag->paginas > 1): ?>
    <nav class="pager" aria-label="Paginación">
        <?php if ($pag->pagina > 1): ?>
            <a class="btn btn-outline btn-icon btn-sm" href="<?= e($pag->url($pag->pagina - 1)) ?>" aria-label="Página anterior"><?= icono('izquierda', 16) ?></a>
        <?php endif; ?>
        <span style="align-self:center;padding:0 8px"><?= $pag->pagina ?> / <?= $pag->paginas ?></span>
        <?php if ($pag->pagina < $pag->paginas): ?>
            <a class="btn btn-outline btn-icon btn-sm" href="<?= e($pag->url($pag->pagina + 1)) ?>" aria-label="Página siguiente"><?= icono('derecha', 16) ?></a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
</div>
