<?php
/** Página de error 403 */
$titulo = 'Acceso denegado';
?>
<div class="error-page">
    <div>
        <div class="code">403</div>
        <h1>Acceso denegado</h1>
        <p>No tiene permiso para ver esta página. Si cree que es un error, consulte al administrador.</p>
        <div class="row" style="justify-content:center">
            <a href="javascript:history.back()" class="btn btn-outline">Volver</a>
            <a href="<?= Auth::usuario() ? Auth::inicio() : '/' ?>" class="btn btn-primary">Ir al inicio</a>
        </div>
    </div>
</div>
