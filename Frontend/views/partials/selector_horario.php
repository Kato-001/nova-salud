<?php
/**
 * Selector de fecha y hora disponible (lo controla assets/js/citas.js).
 * Sin JavaScript, la fecha y la hora se escriben a mano y el servidor las valida.
 */
?>
<div class="paso" data-requiere="1,2">
    <div class="section-title mt"><?= icono('calendario', 14) ?> Fecha</div>
    <div class="dias" id="dias"></div>
    <div class="row mt" style="gap:8px">
        <span class="faint" style="font-size:13px">¿Otra fecha?</span>
        <input class="input" type="date" id="fecha-manual" style="width:auto;height:38px" aria-label="Elegir otra fecha">
    </div>
    <input type="hidden" name="fecha" value="">
</div>
<div class="paso" data-requiere="1,2,3">
    <div class="section-title mt"><?= icono('reloj', 14) ?> Hora disponible <span class="faint" data-res="libres" style="text-transform:none;letter-spacing:0;font-weight:600"></span></div>
    <div class="slots" id="slots"><div class="slots-msg">Elija una fecha para ver los horarios disponibles.</div></div>
    <div class="leyenda-slots"><span><i></i>Disponible</span><span><i style="background:var(--bg-sunken);opacity:.5"></i>Ocupado o pasado</span></div>
    <input type="hidden" name="hora" value="">
</div>
<noscript>
    <div class="grid-2 mt">
        <label class="field"><span>Fecha (AAAA-MM-DD)</span><input class="input" type="date" name="fecha"></label>
        <label class="field"><span>Hora (HH:MM)</span><input class="input" type="time" name="hora"></label>
    </div>
</noscript>
