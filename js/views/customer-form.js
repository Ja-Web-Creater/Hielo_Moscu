/* Formulario único de cliente (alta y edición): datos, ubicación, enfriador y asignación a rutas */
(function () {
  'use strict';
  const HM = window.HM, S = HM.Store;
  const e = HM.esc;

  function parseMapsLink(s) {
    s = String(s || '').trim();
    const pats = [
      /@(-?\d{1,3}\.\d+),(-?\d{1,3}\.\d+)/,
      /!3d(-?\d{1,3}\.\d+)!4d(-?\d{1,3}\.\d+)/,
      /[?&](?:q|query|ll|destination)=(-?\d{1,3}\.\d+)(?:,|%2C)(-?\d{1,3}\.\d+)/,
      /^\s*(-?\d{1,3}\.\d+)\s*,\s*(-?\d{1,3}\.\d+)\s*$/,
    ];
    for (const p of pats) { const m = s.match(p); if (m) return { lat: +m[1], lng: +m[2] }; }
    return null;
  }

  function field(label, name, o = {}) {
    const req = o.req ? ' <i class="req" title="Obligatorio">*</i>' : '';
    let ctl;
    if (o.type === 'textarea') ctl = `<textarea name="${name}" rows="2" maxlength="300"></textarea>`;
    else if (o.type === 'select') ctl = `<select name="${name}"><option value="">Selecciona…</option>${o.options.map((x) => `<option>${e(x)}</option>`).join('')}</select>`;
    else ctl = `<input name="${name}" type="${o.type || 'text'}" ${o.attrs || ''} maxlength="${o.max || 120}">`;
    return `<label class="field ${o.span ? 'span2' : ''}"><span>${label}${req}</span>${ctl}<small class="err" data-for="${name}"></small></label>`;
  }

  HM.CustomerForm = {
    mount(root, opts) {
      const customer = opts.customer || null, editing = !!customer;
      const canWrite = HM.Auth.can('customers.write');
      const ro = !canWrite;
      const c = customer || { cooler: { has: false }, active: true };
      const k = c.cooler || { has: false };
      const routes = S.peekAll('routes').filter((r) => r.active !== false);
      const cur = {};
      if (editing) S.stopsOfCustomer(c.id).forEach((s) => { if (!cur[s.day]) cur[s.day] = s; });
      let map = null, marker = null, destroyed = false;

      root.innerHTML = `
      <form class="cform" novalidate autocomplete="off">
        <fieldset><legend>Datos del cliente</legend><div class="form-grid">
          ${field('Nombre del negocio', 'name', { req: true, span: true })}
          ${field('Contacto (persona)', 'contact')}
          ${field('Teléfono (10 dígitos)', 'phone', { req: true, type: 'tel', attrs: 'inputmode="numeric"', max: 20 })}
          ${field('Dirección', 'address', { req: true, span: true, max: 200 })}
          ${field('Notas', 'notes', { type: 'textarea', span: true })}
        </div></fieldset>

        <fieldset><legend>Ubicación en el mapa</legend>
          <div class="form-grid">
            ${field('Latitud', 'lat', { req: true, attrs: 'inputmode="decimal"', max: 20 })}
            ${field('Longitud', 'lng', { req: true, attrs: 'inputmode="decimal"', max: 20 })}
            <label class="field span2"><span>Pegar enlace de Google Maps o coordenadas</span>
              <div class="pass-row"><input name="link" placeholder="https://www.google.com/maps/@19.58,-98.97 …  o  19.58, -98.97">
              <button type="button" class="btn btn-ghost sm" data-act="geo">📍 Mi ubicación</button></div>
              <small class="err" data-for="link"></small></label>
          </div>
          <div class="mini-map" id="cmap"></div>
          <p class="hint">${ro ? '' : 'Haz clic en el mapa o arrastra el marcador para ajustar el punto.'}</p>
        </fieldset>

        <fieldset><legend>Enfriador</legend>
          <label class="check"><input type="checkbox" name="has"> El cliente tiene enfriador</label>
          <div class="form-grid" id="coolerBox" hidden>
            ${field('Marca', 'brand', { req: true })}
            ${field('Modelo', 'model')}
            <label class="field"><span>Capacidad <i class="req">*</i></span>
              <div class="pass-row"><input name="capacity" inputmode="decimal" maxlength="6" style="flex:1">
              <select name="unit"><option>pies³</option><option>litros</option></select></div>
              <small class="err" data-for="capacity"></small></label>
            ${field('Número de serie', 'serial', { req: true })}
            ${field('Estado', 'status', { req: true, type: 'select', options: HM.COOLER_STATUS })}
            ${field('Propiedad', 'owner', { req: true, type: 'select', options: HM.COOLER_OWNER })}
            ${field('Fecha de entrega', 'deliveredAt', { type: 'date' })}
            ${field('Notas del enfriador', 'coolerNotes', { type: 'textarea', span: true })}
          </div>
        </fieldset>

        <fieldset><legend>Ruta, día y orden de visita</legend>
          ${routes.length ? '' : '<p class="muted">Primero crea una ruta en la pantalla Rutas.</p>'}
          <div class="assign-grid">
            <div class="ah">Día</div><div class="ah">Ruta</div><div class="ah">Orden</div>
            ${HM.DAYS.map((d) => `
              <div class="al">${d.long}</div>
              <select name="route_${d.id}"><option value="">— sin ruta —</option>${routes.map((r) => `<option value="${r.id}">${e(r.name)}</option>`).join('')}</select>
              <input name="pos_${d.id}" type="number" min="1" max="999" placeholder="Al final" aria-label="Orden de visita ${d.long}">`).join('')}
          </div>
        </fieldset>

        ${editing ? '<label class="check"><input type="checkbox" name="active"> Cliente activo</label>' : ''}
        <div class="form-error" data-for="form" role="alert"></div>
        <div class="form-actions">
          <button type="button" class="btn btn-ghost" data-act="cancel">${ro ? 'Cerrar' : 'Cancelar'}</button>
          ${ro ? '' : `${editing ? '' : '<button type="button" class="btn btn-ghost" data-act="again">Guardar y agregar otro</button>'}
          <button type="submit" class="btn btn-primary">${editing ? 'Guardar cambios' : 'Guardar cliente'}</button>`}
        </div>
      </form>`;

      const f = root.querySelector('form');
      const val = (n) => (f.elements[n] ? f.elements[n].value.trim() : '');
      const box = root.querySelector('#coolerBox');

      /* Valores iniciales */
      const set = (n, v) => { if (f.elements[n] && v != null) f.elements[n].value = v; };
      set('name', c.name); set('contact', c.contact); set('phone', c.phone); set('address', c.address); set('notes', c.notes);
      set('lat', c.lat); set('lng', c.lng);
      f.elements.has.checked = !!k.has; box.hidden = !k.has;
      if (k.has) {
        set('brand', k.brand); set('model', k.model); set('capacity', k.capacity); set('unit', k.unit || 'pies³');
        set('serial', k.serial); set('status', k.status); set('owner', k.owner); set('deliveredAt', k.deliveredAt); set('coolerNotes', k.notes);
      }
      if (f.elements.active) f.elements.active.checked = c.active !== false;
      HM.DAYS.forEach((d) => { const s = cur[d.id]; if (s) { set('route_' + d.id, s.routeId); set('pos_' + d.id, s.order); } });
      if (ro) Array.from(f.elements).forEach((el) => { if (el.tagName !== 'BUTTON') el.disabled = true; });

      f.elements.has.addEventListener('change', () => { box.hidden = !f.elements.has.checked; });
      HM.DAYS.forEach((d) => f.elements['route_' + d.id].addEventListener('change', () => {
        const prev = cur[d.id];
        if (!prev || prev.routeId !== f.elements['route_' + d.id].value) f.elements['pos_' + d.id].value = '';
      }));

      /* Mini mapa de ubicación */
      function placeMarker(lat, lng, pan) {
        if (!map) return;
        if (!marker) {
          marker = L.marker([lat, lng], { draggable: !ro }).addTo(map);
          marker.on('dragend', () => { const p = marker.getLatLng(); setCoords(p.lat, p.lng, false); });
        } else marker.setLatLng([lat, lng]);
        if (pan) map.setView([lat, lng], Math.max(map.getZoom(), 15));
      }
      function setCoords(lat, lng, pan = true) {
        f.elements.lat.value = (+lat).toFixed(6); f.elements.lng.value = (+lng).toFixed(6);
        placeMarker(lat, lng, pan);
      }
      const coordsOk = (lat, lng) => lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180 && !(lat === 0 && lng === 0);
      function syncFromInputs() {
        const lat = parseFloat(val('lat')), lng = parseFloat(val('lng'));
        if (isFinite(lat) && isFinite(lng) && coordsOk(lat, lng)) placeMarker(lat, lng, true);
      }
      ['lat', 'lng'].forEach((n) => f.elements[n].addEventListener('change', syncFromInputs));
      f.elements.link.addEventListener('input', () => {
        const p = parseMapsLink(f.elements.link.value);
        if (p && coordsOk(p.lat, p.lng)) { setCoords(p.lat, p.lng); showErr('link', ''); }
      });
      f.elements.link.addEventListener('change', () => {
        if (f.elements.link.value.trim() && !parseMapsLink(f.elements.link.value)) showErr('link', 'No se encontraron coordenadas en ese texto. Los enlaces cortos (goo.gl) no funcionan: abre el enlace y copia la dirección completa.');
      });

      setTimeout(() => {
        if (destroyed) return;
        const el = root.querySelector('#cmap');
        if (!window.L) { el.innerHTML = '<p class="muted pad">El mapa no está disponible sin conexión. Escribe latitud y longitud a mano.</p>'; return; }
        const lat = parseFloat(c.lat), lng = parseFloat(c.lng), has = isFinite(lat) && isFinite(lng);
        map = L.map(el, { zoomControl: true }).setView(has ? [lat, lng] : [19.58, -98.97], has ? 15 : 11);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap &copy; CARTO' }).addTo(map);
        if (has) placeMarker(lat, lng, false);
        if (!ro) map.on('click', (ev) => setCoords(ev.latlng.lat, ev.latlng.lng, false));
        map.invalidateSize();
      }, 80);

      f.querySelector('[data-act="geo"]').onclick = () => {
        if (!navigator.geolocation) return HM.toast('Tu navegador no permite la geolocalización.', 'error');
        navigator.geolocation.getCurrentPosition((p) => setCoords(p.coords.latitude, p.coords.longitude), () => HM.toast('No se pudo obtener tu ubicación.', 'error'));
      };

      /* Validación */
      function showErr(name, msg) {
        const el = f.querySelector(`.err[data-for="${name}"]`);
        if (el) el.textContent = msg;
        const inp = f.elements[name];
        if (inp && inp.classList) inp.classList.toggle('invalid', !!msg);
      }
      function validate() {
        const err = {};
        const d = {};
        d.name = val('name'); if (d.name.length < 3) err.name = 'Escribe el nombre del negocio (mínimo 3 letras).';
        d.contact = val('contact');
        d.phone = HM.digits(val('phone')); if (d.phone.length !== 10) err.phone = 'El teléfono debe tener 10 dígitos.';
        d.address = val('address'); if (d.address.length < 5) err.address = 'Escribe la dirección.';
        d.notes = val('notes');
        const lat = parseFloat(val('lat')), lng = parseFloat(val('lng'));
        if (!isFinite(lat) || lat < -90 || lat > 90) err.lat = 'Latitud inválida.';
        if (!isFinite(lng) || lng < -180 || lng > 180) err.lng = 'Longitud inválida.';
        if (!err.lat && !err.lng && lat === 0 && lng === 0) err.lat = 'Falta ubicar al cliente en el mapa.';
        d.lat = lat; d.lng = lng;
        if (f.elements.has.checked) {
          const q = {
            has: true, brand: val('brand'), model: val('model'), capacity: parseFloat(val('capacity')), unit: val('unit'),
            serial: val('serial'), status: val('status'), owner: val('owner'), deliveredAt: val('deliveredAt'), notes: val('coolerNotes'),
          };
          if (!q.brand) err.brand = 'La marca es obligatoria.';
          if (!(q.capacity > 0)) err.capacity = 'Escribe la capacidad (número mayor a 0).';
          if (!q.serial) err.serial = 'El número de serie es obligatorio.';
          if (!q.status) err.status = 'Selecciona el estado.';
          if (!q.owner) err.owner = 'Selecciona la propiedad.';
          d.cooler = q;
        } else d.cooler = { has: false };
        if (f.elements.active) d.active = f.elements.active.checked;
        return { err, d };
      }
      const FIELDS = ['name', 'phone', 'address', 'lat', 'lng', 'brand', 'capacity', 'serial', 'status', 'owner'];

      async function save(again) {
        FIELDS.forEach((n) => showErr(n, ''));
        showErr('form', '');
        const { err, d } = validate();
        const keys = Object.keys(err);
        if (keys.length) {
          keys.forEach((n) => showErr(n, err[n]));
          const first = f.elements[keys[0]]; if (first) first.focus();
          return;
        }
        const others = S.peekAll('customers').filter((x) => !customer || x.id !== customer.id);
        if (d.cooler.has) {
          const dupS = others.find((x) => x.cooler && x.cooler.has && x.cooler.serial === d.cooler.serial);
          if (dupS) { showErr('serial', `Ese número de serie ya está registrado en "${dupS.name}".`); f.elements.serial.focus(); return; }
        }
        const dupP = others.find((x) => x.phone === d.phone);
        if (dupP && !(await HM.confirm(`Ya existe un cliente con ese teléfono: "${dupP.name}". ¿Guardar de todos modos?`, { okText: 'Guardar' }))) return;

        const me = HM.Auth.current();
        const desired = [];
        HM.DAYS.forEach((day) => {
          const rid = val('route_' + day.id);
          if (rid) { const p = parseInt(val('pos_' + day.id), 10); desired.push({ routeId: rid, day: day.id, position: p > 0 ? p : null }); }
        });
        const btns = f.querySelectorAll('button[type=submit],[data-act=again]');
        btns.forEach((b) => { b.disabled = true; });
        try {
          const saved = editing
            ? await S.update('customers', customer.id, Object.assign(d, { updatedBy: me.uid }))
            : await S.insert('customers', Object.assign(d, { active: true, createdBy: me.uid }));
          await S.syncAssignments(saved.id, desired);
          HM.toast(editing ? 'Cliente actualizado.' : 'Cliente guardado.');
          if (again) { api.destroy(); HM.CustomerForm.mount(root, opts); const n = root.querySelector('[name=name]'); if (n) n.focus(); }
          else if (opts.onSaved) opts.onSaved(saved);
        } catch (ex) {
          showErr('form', ex.message || 'No se pudo guardar.');
          btns.forEach((b) => { b.disabled = false; });
        }
      }

      f.addEventListener('submit', (ev) => { ev.preventDefault(); if (!ro) save(false); });
      const again = f.querySelector('[data-act="again"]'); if (again) again.onclick = () => save(true);
      f.querySelector('[data-act="cancel"]').onclick = () => { if (opts.onCancel) opts.onCancel(); };

      const api = { destroy() { destroyed = true; if (map) { map.remove(); map = null; marker = null; } } };
      return api;
    },
  };
})();