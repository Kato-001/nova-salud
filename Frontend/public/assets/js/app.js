/* =====================================================================
   NovaSol Salud · Interacción del lado del cliente
   ---------------------------------------------------------------------
   - Tema claro/oscuro, menú móvil, toasts, modal de confirmación.
   - Validación de formularios (las mismas reglas se repiten en PHP).
   - Animaciones: entrada escalonada, contadores, efecto ripple.
   La aplicación funciona sin JavaScript; esto solo mejora la experiencia.
   ===================================================================== */
(function () {
  'use strict';

  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

  /* ------------------------------------------------------------- Tema */
  const raiz = document.documentElement;
  $$('[data-tema]').forEach((btn) => btn.addEventListener('click', () => {
    const nuevo = raiz.dataset.theme === 'dark' ? 'light' : 'dark';
    raiz.dataset.theme = nuevo;
    try { localStorage.setItem('hs-tema', nuevo); } catch (e) { /* sin almacenamiento */ }
  }));

  /* ------------------------------------------------------- Menú móvil */
  const sidebar = $('.sidebar');
  const fondo = $('.backdrop');
  const menu = (abrir) => {
    if (!sidebar) return;
    sidebar.classList.toggle('open', abrir);
    if (fondo) fondo.hidden = !abrir;
  };
  $$('[data-menu]').forEach((b) => b.addEventListener('click', () => menu(true)));
  if (fondo) fondo.addEventListener('click', () => menu(false));

  /* ----------------------------------------------------------- Toasts */
  const ICONOS = {
    ok: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>',
    error: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>',
    info: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
  };
  const zonaToasts = $('.toasts');
  function toast(tipo, mensaje) {
    if (!zonaToasts) return;
    const t = document.createElement('div');
    t.className = 'toast ' + tipo;
    t.setAttribute('role', tipo === 'error' ? 'alert' : 'status');
    t.innerHTML = '<span class="t-ico">' + (ICONOS[tipo] || ICONOS.info) + '</span><div style="flex:1"></div>'
      + '<button type="button" class="btn btn-ghost btn-icon btn-sm" aria-label="Cerrar" style="width:28px;height:28px">×</button><span class="bar-time"></span>';
    t.children[1].textContent = mensaje;   // texto plano: evita inyección de HTML
    const cerrar = () => { t.classList.add('out'); setTimeout(() => t.remove(), 300); };
    t.querySelector('button').addEventListener('click', cerrar);
    zonaToasts.appendChild(t);
    setTimeout(cerrar, tipo === 'error' ? 7000 : 5000);
  }
  window.hsToast = toast;
  const flash = $('#flash-data');
  if (flash) {
    try { JSON.parse(flash.textContent).forEach((f, i) => setTimeout(() => toast(f.tipo, f.mensaje), 150 + i * 150)); } catch (e) { /* nada */ }
  }

  /* ------------------------------------------------ Modal de confirmación */
  const modal = $('#modal-confirmar');
  let formPendiente = null;
  function abrirModal(form) {
    const d = form.dataset;
    formPendiente = form;
    $('#mc-titulo', modal).textContent = d.confirm || '¿Confirmar la acción?';
    $('#mc-texto', modal).textContent = d.confirmTexto || '';
    const ok = $('#mc-ok', modal);
    ok.textContent = d.confirmOk || 'Confirmar';
    ok.className = 'btn ' + (d.peligro !== undefined ? 'btn-danger' : 'btn-primary');
    $('#mc-ico', modal).style.cssText = d.peligro !== undefined
      ? 'background:var(--danger-soft);color:var(--danger)' : 'background:var(--warn-soft);color:var(--warn)';
    const motivo = $('#mc-motivo-campo', modal);
    motivo.hidden = d.motivo === undefined;
    $('#mc-motivo', modal).value = '';
    $('#mc-motivo-error', modal).textContent = '';
    $('#mc-motivo-label', modal).textContent = d.motivo || 'Motivo';
    modal.classList.remove('closing');
    modal.classList.add('open');
    setTimeout(() => (d.motivo !== undefined ? $('#mc-motivo', modal) : ok).focus(), 50);
  }
  function cerrarModal() {
    modal.classList.add('closing');
    setTimeout(() => modal.classList.remove('open', 'closing'), 200);
    formPendiente = null;
  }
  if (modal) {
    $$('[data-cerrar-modal]', modal).forEach((b) => b.addEventListener('click', cerrarModal));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal.classList.contains('open')) cerrarModal(); });
    $('#mc-ok', modal).addEventListener('click', () => {
      if (!formPendiente) return;
      const f = formPendiente;
      if (f.dataset.motivo !== undefined) {
        const texto = $('#mc-motivo', modal).value.trim();
        if (texto.length < 3) {
          $('#mc-motivo-error', modal).textContent = 'Escriba el motivo (mínimo 3 caracteres).';
          $('#mc-motivo', modal).focus();
          return;
        }
        let campo = f.querySelector('input[name="motivo"]');
        if (!campo) { campo = document.createElement('input'); campo.type = 'hidden'; campo.name = 'motivo'; f.appendChild(campo); }
        campo.value = texto;
      }
      f.dataset.confirmado = '1';
      cerrarModal();
      f.requestSubmit ? f.requestSubmit() : f.submit();
    });
  }
  document.addEventListener('submit', (e) => {
    const f = e.target;
    if (f.dataset.confirm !== undefined && !f.dataset.confirmado && modal) {
      e.preventDefault();
      abrirModal(f);
    }
  }, true);

  /* --------------------------------------------- Validación de formularios */
  const MENSAJES = {
    valueMissing: 'Este campo es obligatorio.',
    typeMismatch: 'El formato no es válido.',
    tooShort: (el) => 'Mínimo ' + el.minLength + ' caracteres.',
    tooLong: (el) => 'Máximo ' + el.maxLength + ' caracteres.',
    rangeUnderflow: (el) => 'El valor mínimo es ' + el.min + '.',
    rangeOverflow: (el) => 'El valor máximo es ' + el.max + '.',
    stepMismatch: 'Valor no permitido.',
    badInput: 'Valor no válido.',
  };
  function mensajeDe(el) {
    if (el.dataset.coincide) {
      const otro = el.form.elements[el.dataset.coincide];
      if (otro && el.value !== otro.value) return 'Las contraseñas no coinciden.';
    }
    const v = el.validity;
    if (v.valid) return '';
    if (v.patternMismatch) return el.dataset.msg || 'El formato no es válido.';
    for (const k in MENSAJES) {
      if (v[k]) return typeof MENSAJES[k] === 'function' ? MENSAJES[k](el) : MENSAJES[k];
    }
    return el.validationMessage;
  }
  function marcar(el) {
    const msg = mensajeDe(el);
    const campo = el.closest('.field') || el.parentElement;
    let span = campo.querySelector('.error[data-js]');
    if (msg) {
      if (!span) {
        // Si PHP ya mostró un error en este campo, se reemplaza por el de JS
        $$('.error', campo).forEach((s) => s.remove());
        span = document.createElement('span');
        span.className = 'error';
        span.dataset.js = '1';
        campo.appendChild(span);
      }
      span.textContent = msg;
      el.classList.add('invalid');
    } else {
      if (span) span.remove();
      el.classList.remove('invalid');
    }
    return !msg;
  }
  $$('form[data-validar]').forEach((form) => {
    form.noValidate = true;   // se usan mensajes propios en español
    const campos = () => $$('input, select, textarea', form).filter((el) => !el.disabled && el.type !== 'hidden' && el.willValidate);
    campos().forEach((el) => {
      el.addEventListener('blur', () => { el.dataset.tocado = '1'; marcar(el); });
      el.addEventListener('input', () => { if (el.dataset.tocado) marcar(el); });
    });
    form.addEventListener('submit', (e) => {
      const invalidos = campos().filter((el) => !marcar(el));
      if (invalidos.length) {
        e.preventDefault();
        e.stopImmediatePropagation();
        invalidos[0].focus();
        invalidos[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        toast('error', 'Revise los campos marcados en rojo.');
      }
    });
  });

  /* Evita doble envío y muestra un indicador de carga */
  document.addEventListener('submit', (e) => {
    if (e.defaultPrevented) return;
    const btn = e.target.querySelector('button[type="submit"], button:not([type])');
    if (btn && !btn.dataset.sinCarga) {
      setTimeout(() => {
        btn.disabled = true;
        btn.dataset.texto = btn.innerHTML;
        btn.innerHTML = '<svg class="spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-6.22-8.56"/></svg> Procesando…';
      }, 0);
    }
  });
  // Al volver con el botón "atrás" los botones deben quedar habilitados
  window.addEventListener('pageshow', () => $$('button[data-texto]').forEach((b) => { b.disabled = false; b.innerHTML = b.dataset.texto; }));

  /* Fortaleza de contraseña */
  $$('[data-fortaleza]').forEach((input) => {
    const barra = document.getElementById(input.dataset.fortaleza);
    if (!barra) return;
    const colores = ['var(--danger)', 'var(--danger)', 'var(--warn)', 'var(--ok)', 'var(--ok)'];
    input.addEventListener('input', () => {
      const p = input.value;
      let n = 0;
      if (p.length >= 8) n++;
      if (/[A-Za-z]/.test(p) && /\d/.test(p)) n++;
      if (/[A-Z]/.test(p) && /[a-z]/.test(p)) n++;
      if (/[^A-Za-z0-9]/.test(p) || p.length >= 12) n++;
      barra.hidden = !p;
      $$('i', barra).forEach((i, k) => { i.style.background = k < n ? colores[n] : ''; });
    });
  });

  /* Mostrar / ocultar contraseña */
  $$('[data-ver-clave]').forEach((b) => b.addEventListener('click', () => {
    const input = document.getElementById(b.dataset.verClave);
    input.type = input.type === 'password' ? 'text' : 'password';
    b.setAttribute('aria-label', input.type === 'password' ? 'Mostrar contraseña' : 'Ocultar contraseña');
    b.classList.toggle('on', input.type === 'text');
  }));

  /* Filtros que se aplican al cambiar */
  $$('[data-autoenviar]').forEach((el) => el.addEventListener('change', () => el.form.requestSubmit ? el.form.requestSubmit() : el.form.submit()));

  /* ------------------------------------------------------- Animaciones */
  // Escalonado: cada grupo [data-escalonar] numera sus hijos
  $$('[data-escalonar]').forEach((g) => Array.from(g.children).forEach((c, i) => { c.classList.add('anim'); c.style.setProperty('--i', i); }));
  $$('.table tbody').forEach((tb) => Array.from(tb.rows).forEach((r, i) => { if (i < 30) { r.classList.add('anim'); r.style.setProperty('--i', i); } }));

  // Contadores que suben desde 0
  const contar = (el) => {
    const fin = parseFloat(el.dataset.contar) || 0;
    const dec = (el.dataset.contar.split('.')[1] || '').length;
    const t0 = performance.now();
    const paso = (t) => {
      const p = Math.min(1, (t - t0) / 1100);
      const e = 1 - Math.pow(1 - p, 4);
      el.textContent = (fin * e).toLocaleString('es-BO', { minimumFractionDigits: dec, maximumFractionDigits: dec });
      if (p < 1) requestAnimationFrame(paso);
    };
    requestAnimationFrame(paso);
  };
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((ents) => ents.forEach((en) => { if (en.isIntersecting) { contar(en.target); io.unobserve(en.target); } }));
    $$('[data-contar]').forEach((el) => io.observe(el));
  } else {
    $$('[data-contar]').forEach(contar);
  }

  // Efecto ripple en los botones principales
  document.addEventListener('pointerdown', (e) => {
    const b = e.target.closest('.btn-primary, .btn-danger');
    if (!b) return;
    const r = b.getBoundingClientRect();
    const s = document.createElement('span');
    const d = Math.max(r.width, r.height);
    s.className = 'ripple';
    s.style.cssText = 'width:' + d + 'px;height:' + d + 'px;left:' + (e.clientX - r.left - d / 2) + 'px;top:' + (e.clientY - r.top - d / 2) + 'px';
    b.appendChild(s);
    setTimeout(() => s.remove(), 600);
  });

  /* ------------------- Filas dinámicas (tratamientos y estudios de la consulta)
     <button data-agregar="idLista" data-plantilla="idTemplate"> agrega una fila */
  $$('[data-agregar]').forEach((btn) => {
    const lista = document.getElementById(btn.dataset.agregar);
    const plantilla = document.getElementById(btn.dataset.plantilla);
    if (!lista || !plantilla) return;
    let n = lista.children.length + 100;   // índices únicos para los nombres del formulario
    btn.addEventListener('click', () => {
      lista.insertAdjacentHTML('beforeend', plantilla.innerHTML.replace(/__i__/g, n++));
      lista.lastElementChild.querySelector('select, input').focus();
    });
    lista.addEventListener('click', (e) => {
      const b = e.target.closest('[data-quitar-fila]');
      if (b) b.closest('.trat-row').remove();
    });
  });

  /* --------------------------------- Portal: marcar notificaciones leídas */
  const btnLeer = $('#marcar-leidas');
  if (btnLeer) {
    btnLeer.addEventListener('click', async () => {
      const r = await fetch('/portal/notificaciones/leer', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': btnLeer.dataset.csrf, 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '_csrf=' + encodeURIComponent(btnLeer.dataset.csrf),
      });
      if (r.ok) {
        $$('.notif.nueva').forEach((n) => n.classList.remove('nueva'));
        $$('[data-no-leidas]').forEach((n) => n.remove());
        btnLeer.remove();
      }
    });
  }
})();
