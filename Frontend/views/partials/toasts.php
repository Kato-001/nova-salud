<?php
/**
 * Mensajes flash: se envían como JSON y app.js los muestra como toasts.
 * Sin JavaScript se ven como alertas normales.
 */
$mensajes = tomar_flash();
?>
<div class="toasts" aria-live="polite"></div>
<?php if ($mensajes): ?>
<script type="application/json" id="flash-data"><?= json_encode($mensajes, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<noscript>
    <div style="position:fixed;bottom:20px;right:20px;display:grid;gap:8px;z-index:100">
        <?php foreach ($mensajes as $m): ?>
            <div class="alert alert-<?= $m['tipo'] === 'error' ? 'error' : ($m['tipo'] === 'info' ? 'info' : 'ok') ?>"><?= e($m['mensaje']) ?></div>
        <?php endforeach; ?>
    </div>
</noscript>
<?php endif; ?>
