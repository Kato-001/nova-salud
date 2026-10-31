<?php
/** Página de error 404 */
$titulo = 'Página no encontrada';
?>
<div class="error-page">
    <div>
        <div class="code">404</div>
        <h1>Página no encontrada</h1>
        <p>La página que busca no existe o fue movida.</p>
        <div class="row" style="justify-content:center">
            <a href="javascript:history.back()" class="btn btn-outline">Volver</a>
            <a href="<?= Auth::usuario() ? Auth::inicio() : '/' ?>" class="btn btn-primary">Ir al inicio</a>
        </div>
    </div>
</div>
