/* =====================================================================
   NovaSol Salud · Asistente para programar / reprogramar / reservar citas
   ---------------------------------------------------------------------
   Paso a paso: paciente → especialidad → médico → fecha → hora.
   Solo se muestran los horarios que el servidor devuelve como libres
   (/api/disponibilidad); al guardar, PHP vuelve a verificarlos (RN13).
   ===================================================================== */
(function () {
  'use strict';

  const form = document.getElementById('form-cita');
  if (!form) return;

  const $ = (s) => form.querySelector(s);
  const modo = form.dataset.modo;                       // nueva | reprogramar | portal
  const maxDias = parseInt(form.dataset.maxDias || '60', 10);
  const DIAS = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
  const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

  const campo = {
    paciente: $('[name="idpaciente"]'),
    especialidad: $('[name="idespecialidad"]'),
    medico: $('[name="idmedico"]'),
    fecha: $('[name="fecha"]'),
    hora: $('[name="hora"]'),
  };
  const dias = $('#dias');
  const slots = $('#slots');
  const fechaManual = $('#fecha-manual');
  const btn = $('#btn-confirmar');
  let diasMedico = (form.dataset.dias || '').split(',').filter(Boolean).map(Number);
  let peticion = 0;

  const json = async (url) => {
    const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } });
    if (r.status === 401) { location.href = '/login'; throw new Error('sesión'); }
    return r.json();
  };
  const isoDia = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  const fechaTexto = (iso) => { const d = new Date(iso + 'T12:00:00'); return DIAS[d.getDay()] + ' ' + d.getDate() + ' ' + MESES[d.getMonth()] + ' ' + d.getFullYear(); };
  const resumen = (k, v) => { const el = form.querySelector('[data-res="' + k + '"]'); if (el) el.textContent = v || '—'; };

  /* -------------------------------------------- Estado de los pasos */
  function actualizarPasos() {
    const listo = {
      1: modo !== 'nueva' || !!campo.paciente.value,
      2: !!(campo.medico && campo.medico.value),
      3: !!campo.fecha.value,
      4: !!campo.hora.value,
    };
    form.querySelectorAll('.step').forEach((s) => {
      const n = +s.dataset.step;
      s.classList.toggle('done', listo[n]);
      s.classList.toggle('on', !listo[n] && Object.keys(listo).filter((k) => k < n).every((k) => listo[k]));
    });
    form.querySelectorAll('.paso[data-requiere]').forEach((p) => {
      p.classList.toggle('bloqueado', !p.dataset.requiere.split(',').every((k) => listo[k]));
    });
    if (btn) btn.disabled = !(listo[1] && listo[2] && listo[3] && listo[4]);
  }

  /* ------------------------------------------ 1. Paciente (autocompletar) */
  const buscador = $('#buscar-paciente');
  if (buscador) {
    const lista = $('#ac-lista');
    const elegido = $('#paciente-elegido');
    let temporizador;
    let activo = -1;
    const elegir = (p) => {
      campo.paciente.value = p.idpaciente;
      elegido.querySelector('[data-nombre]').textContent = p.nombre_completo;
      elegido.querySelector('[data-detalle]').textContent = 'CI ' + p.ci + ' · ' + p.codigo + (p.telefono ? ' · ' + p.telefono : '');
      elegido.hidden = false;
      buscador.closest('.autocomplete').hidden = true;
      lista.classList.remove('open');
      resumen('paciente', p.nombre_completo);
      actualizarPasos();
      if (campo.especialidad && !campo.especialidad.value) campo.especialidad.focus();
    };
    $('#cambiar-paciente').addEventListener('click', () => {
      campo.paciente.value = '';
      elegido.hidden = true;
      buscador.closest('.autocomplete').hidden = false;
      buscador.value = '';
      buscador.focus();
      resumen('paciente', '');
      actualizarPasos();
    });
    buscador.addEventListener('input', () => {
      clearTimeout(temporizador);
      const q = buscador.value.trim();
      if (q.length < 2) { lista.classList.remove('open'); return; }
      temporizador = setTimeout(async () => {
        const r = await json('/api/pacientes?q=' + encodeURIComponent(q));
        activo = -1;
        lista.innerHTML = '';
        if (!r.pacientes.length) {
          lista.innerHTML = '<div class="ac-empty">No se encontraron pacientes activos. <a href="/pacientes/nuevo">Registrar nuevo paciente</a></div>';
        }
        r.pacientes.forEach((p) => {
          const it = document.createElement('div');
          it.className = 'ac-item';
          it.innerHTML = '<span class="avatar" style="width:34px;height:34px;font-size:12px"></span><div><strong></strong><small></small></div>';
          it.querySelector('.avatar').textContent = p.nombre_completo.split(' ').slice(0, 2).map((x) => x[0]).join('');
          it.querySelector('strong').textContent = p.nombre_completo;
          it.querySelector('small').textContent = 'CI ' + p.ci + ' · ' + p.codigo;
          it.addEventListener('mousedown', (e) => { e.preventDefault(); elegir(p); });
          lista.appendChild(it);
        });
        lista.classList.add('open');
      }, 250);
    });
    buscador.addEventListener('keydown', (e) => {
      const items = Array.from(lista.querySelectorAll('.ac-item'));
      if (!items.length) return;
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        activo = (activo + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
        items.forEach((it, i) => it.classList.toggle('on', i === activo));
      } else if (e.key === 'Enter' && activo >= 0) {
        e.preventDefault();
        items[activo].dispatchEvent(new MouseEvent('mousedown'));
      }
    });
    buscador.addEventListener('blur', () => setTimeout(() => lista.classList.remove('open'), 150));
    if (campo.paciente.value) { resumen('paciente', elegido.querySelector('[data-nombre]').textContent); }
  }

  /* ------------------------------------------ 2. Especialidad y médico */
  async function cargarMedicos(preseleccion) {
    const esp = campo.especialidad.value;
    resumen('especialidad', esp ? campo.especialidad.selectedOptions[0].textContent : '');
    campo.medico.innerHTML = '<option value="">' + (esp ? 'Cargando…' : 'Primero elija la especialidad') + '</option>';
    campo.medico.disabled = true;
    limpiarFecha();
    if (!esp) return actualizarPasos();
    const r = await json('/api/medicos?especialidad=' + esp);
    campo.medico.innerHTML = '<option value="">' + (r.medicos.length ? 'Seleccione un médico…' : 'Sin médicos activos para esta especialidad') + '</option>';
    r.medicos.forEach((m) => {
      const o = new Option('Dr(a). ' + m.nombre_completo + ' · ' + m.codigo, m.idmedico);
      o.dataset.dias = m.dias;
      campo.medico.add(o);
    });
    campo.medico.disabled = !r.medicos.length;
    if (preseleccion && campo.medico.querySelector('option[value="' + preseleccion + '"]')) {
      campo.medico.value = preseleccion;
      elegirMedico();
    } else if (r.medicos.length === 1) {
      campo.medico.value = r.medicos[0].idmedico;
      elegirMedico();
    }
    actualizarPasos();
  }
  function elegirMedico() {
    const o = campo.medico.selectedOptions[0];
    diasMedico = o && o.dataset.dias ? o.dataset.dias.split(',').map(Number) : [];
    resumen('medico', campo.medico.value ? o.textContent.split(' · ')[0] : '');
    limpiarFecha();
    pintarDias();
    actualizarPasos();
  }
  if (campo.especialidad && campo.especialidad.tagName === 'SELECT') {
    campo.especialidad.addEventListener('change', () => cargarMedicos());
    campo.medico.addEventListener('change', elegirMedico);
  }

  /* ------------------------------------------------------- 3. Fecha */
  function limpiarFecha() {
    campo.fecha.value = '';
    campo.hora.value = '';
    resumen('fecha', '');
    resumen('hora', '');
    if (slots) slots.innerHTML = '<div class="slots-msg">Elija una fecha para ver los horarios disponibles.</div>';
    if (dias) dias.innerHTML = '';
  }
  function pintarDias() {
    if (!dias) return;
    dias.innerHTML = '';
    if (!campo.medico || !campo.medico.value) return;
    if (!diasMedico.length) {
      dias.innerHTML = '<div class="slots-msg" style="grid-column:1/-1">El médico aún no tiene horarios de atención.</div>';
      return;
    }
    const hoy = new Date();
    let mostrados = 0;
    for (let i = 0; i <= maxDias && mostrados < 14; i++) {
      const d = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() + i);
      const iso = d.getDay() === 0 ? 7 : d.getDay();
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'dia';
      b.style.setProperty('--i', mostrados);
      b.dataset.fecha = isoDia(d);
      b.innerHTML = '<small>' + (i === 0 ? 'Hoy' : DIAS[d.getDay()]) + '</small><b>' + d.getDate() + '</b><small>' + MESES[d.getMonth()] + '</small>';
      b.disabled = !diasMedico.includes(iso);
      b.title = b.disabled ? 'El médico no atiende este día' : fechaTexto(b.dataset.fecha);
      b.addEventListener('click', () => elegirFecha(b.dataset.fecha));
      dias.appendChild(b);
      mostrados++;
    }
  }
  if (fechaManual) {
    const hoy = new Date();
    fechaManual.min = isoDia(hoy);
    fechaManual.max = isoDia(new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() + maxDias));
    fechaManual.addEventListener('change', () => { if (fechaManual.value) elegirFecha(fechaManual.value); });
  }

  /* ------------------------------------------------------- 4. Hora */
  async function elegirFecha(iso) {
    campo.fecha.value = iso;
    campo.hora.value = '';
    resumen('fecha', fechaTexto(iso));
    resumen('hora', '');
    if (fechaManual) fechaManual.value = iso;
    dias && dias.querySelectorAll('.dia').forEach((d) => d.classList.toggle('on', d.dataset.fecha === iso));
    actualizarPasos();
    slots.innerHTML = '<div class="slots-msg">Buscando horarios disponibles…</div>';
    const id = ++peticion;
    const excluir = form.dataset.excluir ? '&excluir=' + form.dataset.excluir : '';
    const r = await json('/api/disponibilidad?medico=' + campo.medico.value + '&fecha=' + iso + excluir);
    if (id !== peticion) return;   // el usuario ya eligió otra fecha
    slots.innerHTML = '';
    if (!r.ok) { slots.innerHTML = '<div class="slots-msg"></div>'; slots.firstChild.textContent = r.motivo; return; }
    const libres = r.bloques.filter((b) => b.libre).length;
    if (!libres) { slots.innerHTML = '<div class="slots-msg">No quedan horarios libres este día. Pruebe con otra fecha.</div>'; return; }
    r.bloques.forEach((b, i) => {
      const s = document.createElement('button');
      s.type = 'button';
      s.className = 'slot';
      s.style.setProperty('--i', i);
      s.textContent = b.inicio;
      s.disabled = !b.libre;
      s.title = b.libre ? b.inicio + ' – ' + b.fin : 'Ocupado';
      s.addEventListener('click', () => {
        slots.querySelectorAll('.slot').forEach((x) => x.classList.remove('on'));
        s.classList.add('on');
        campo.hora.value = b.inicio;
        resumen('hora', b.inicio + ' – ' + b.fin + ' (' + r.duracion + ' min)');
        actualizarPasos();
        btn && btn.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      });
      slots.appendChild(s);
    });
    const nota = form.querySelector('[data-res="libres"]');
    if (nota) nota.textContent = libres + ' de ' + r.bloques.length + ' horarios libres';
  }

  /* ---------------------------------------------------------- Inicio */
  if (modo === 'reprogramar') {
    pintarDias();
  } else if (campo.especialidad && campo.especialidad.value) {
    cargarMedicos(form.dataset.preMedico);
  }
  form.addEventListener('submit', (e) => {
    if (btn && btn.disabled) { e.preventDefault(); }
  });
  actualizarPasos();
})();
