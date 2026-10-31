<?php
/** Página de error 500 */
$titulo = 'Algo salió mal';
?>
<div class="error-page">
    <div>
        <div class="code">500</div>
        <h1>Algo salió mal</h1>
        <p>Ocurrió un error inesperado. Ya quedó registrado; intente nuevamente en unos minutos.</p>
        <?php if (!empty($detalle)): ?><p class="mono faint" style="font-size:12px"><?= e($detalle) ?></p><?php endif; ?>
        <div class="row" style="justify-content:center">
            <a href="javascript:history.back()" class="btn btn-outline">Volver</a>
            <a href="/" class="btn btn-primary">Ir al inicio</a>
        </div>
    </div>
</div>
