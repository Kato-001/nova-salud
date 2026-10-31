<?php
/**
 * Modal de confirmación reutilizable. Se activa con data-confirm en un <form>:
 *   data-confirm="Título" data-confirm-texto="Detalle" data-confirm-ok="Botón"
 *   data-peligro (botón rojo) · data-motivo="Etiqueta" (pide un motivo obligatorio)
 */
?>
<div class="modal-layer" id="modal-confirmar" role="dialog" aria-modal="true" aria-labelledby="mc-titulo">
    <div class="modal-backdrop" data-cerrar-modal></div>
    <div class="modal sm">
        <div class="modal-head">
            <div class="modal-ico" id="mc-ico"><?= icono('alerta', 22) ?></div>
            <div>
                <h2 id="mc-titulo">¿Confirmar?</h2>
                <p id="mc-texto"></p>
            </div>
            <button type="button" class="btn btn-ghost btn-icon" data-cerrar-modal aria-label="Cerrar"><?= icono('cerrar') ?></button>
        </div>
        <div class="modal-body" id="mc-motivo-campo" hidden>
            <label class="field">
                <span id="mc-motivo-label">Motivo</span>
                <textarea class="textarea" id="mc-motivo" rows="3" maxlength="255"></textarea>
                <span class="error" id="mc-motivo-error"></span>
            </label>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-ghost" data-cerrar-modal>Cancelar</button>
            <button type="button" class="btn btn-primary" id="mc-ok">Confirmar</button>
        </div>
    </div>
</div>
