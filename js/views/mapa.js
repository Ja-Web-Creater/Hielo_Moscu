(function () {
  'use strict';
  const HM = window.HM, S = HM.Store, esc = HM.esc;
  const DENSE = 300;      // a partir de aquí se dibujan puntos ligeros (canvas) en lugar de pines numerados
  const PAGE = 100;       // clientes que se pintan por bloque en la lista

  HM.views.mapa = {
    mount(root, ctx) {
      ctx.setTitle('Mapa de rutas', 'Planeación y visualización de recorridos');
      root.innerHTML = `
      <div class="map-page">
        <div class="days" id="days" role="tablist"></div>
        <div class="content">
          <aside class="panel" id="panel">
            <div class="sheet-handle" id="handle" aria-hidden="true"></div>
            <section class="card-block">
              <h2 class="only-web"><span aria-hidden="true">📅</span> Resumen del día</h2>
              <div class="stats">
                <div class="stat"><span class="stat-ico">🔗</span><b id="statRoutes">0</b><small>rutas</small></div>
                <div class="stat"><span class="stat-ico">👥</span><b id="statClients">0</b><small>clientes</small></div>
                <div class="stat"><span class="stat-ico">👁</span><b id="statVisible">0</b><small>visibles</small></div>
              </div>
            </section>
            <section class="card-block"><h2>Rutas del día</h2><div id="routeList" class="route-list"></div></section>
            <section class="card-block options only-web">
              <button class="options-toggle" id="optToggle" aria-expanded="true">Opciones de visualización <span id="optArrow">⌃</span></button>
              <div id="optBody">
                <label class="switch-row"><span>Mostrar puntos</span><input type="checkbox" id="optPoints" checked><i></i></label>
                <label class="switch-row"><span>Mostrar líneas de recorrido</span><input type="checkbox" id="optLines" checked><i></i></label>
                <label class="switch-row"><span>Mostrar numeración</span><input type="checkbox" id="optNumbers" checked><i></i></label>
                <label class="switch-row"><span>Mostrar nombres de clientes</span><input type="checkbox" id="optNames"><i></i></label>
                <button class="btn btn-primary block" id="btnFit">⤢ Ajustar mapa a rutas visibles</button>
              </div>
            </section>
            <section class="card-block clients">
              <div class="clients-head"><h2 id="clientsTitle">Clientes</h2><small id="clientsCount"></small></div>
              <input id="clientFilter" class="client-filter" type="search" placeholder="Buscar cliente en esta ruta...">
              <ul id="clientList" class="client-list"></ul>
            </section>
          </aside>
          <section class="map-wrap">
            <div id="map" aria-label="Mapa de rutas"></div>
            <div class="map-type only-web"><button class="active" data-layer="map">Mapa</button><button data-layer="sat">Satélite</button></div>
            <div class="legend" id="legend"></div>
            <div class="map-ctrl">
              <button id="btnLocate" title="Mi ubicación" aria-label="Mi ubicación">◎</button>
              <button id="btnZoomIn" aria-label="Acercar">+</button>
              <button id="btnZoomOut" aria-label="Alejar">−</button>
            </div>
          </section>
        </div>
      </div>`;

      const $ = (id) => root.querySelector('#' + id);
      const st = { day: 1, plan: [], byKey: new Map(), visible: new Set(), selected: null, active: null, limit: PAGE,
        opts: { points: true, lines: true, numbers: true, names: false } };
      const markers = new Map();
      const isMobile = () => window.matchMedia('(max-width: 900px)').matches;

      /* ----- Mapa ----- */
      if (!window.L) {
        $('map').innerHTML = '<p class="muted pad">No se pudo cargar el mapa (sin conexión). Revisa tu internet y recarga.</p>';
        return null;
      }
      const map = L.map($('map'), { zoomControl: false, center: [19.58, -98.97], zoom: 11 });
      const tiles = {
        map: L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap &copy; CARTO' }),
        sat: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19, attribution: 'Tiles &copy; Esri' }),
      };
      tiles.map.addTo(map);
      const canvas = L.canvas({ padding: 0.5 });
      const draw = L.layerGroup().addTo(map);

      /* ----- Datos ----- */
      const label = (l) => (st.day === 0 ? `${l.route.name} · ${HM.dayShort(l.day)}` : l.route.name);
      function loadPlan(day) {
        st.day = day; st.plan = S.plan(day);
        st.byKey = new Map(st.plan.map((l) => [l.key, l]));
        st.visible = new Set(st.plan.map((l) => l.key));
        st.selected = st.plan.length ? st.plan[0].key : null;
        st.active = null; st.limit = PAGE;
      }
      const visiblePlan = () => st.plan.filter((l) => st.visible.has(l.key));

      /* ----- Render ----- */
      function renderDays() {
        $('days').innerHTML = HM.DAYS.map((d) => {
          const n = S.dayRouteCount(d.id);
          return `<button class="day ${st.day === d.id ? 'active' : ''}" role="tab" data-day="${d.id}"><b><span class="long">${d.long}</span><span class="short">${d.short}</span></b><small>${n} ${n === 1 ? 'ruta' : 'rutas'}</small></button>`;
        }).join('') + `<button class="day all ${st.day === 0 ? 'active' : ''}" data-day="0"><span class="long">Seleccionar todo</span><span class="short">Todo</span></button>`;
      }
      function renderStats() {
        $('statRoutes').textContent = st.plan.length;
        $('statClients').textContent = st.plan.reduce((a, l) => a + l.items.length, 0);
        $('statVisible').textContent = st.visible.size;
      }
      function renderRoutes() {
        if (!st.plan.length) {
          $('routeList').innerHTML = S.peekAll('customers').length
            ? '<p class="empty">No hay rutas programadas este día.</p>'
            : '<p class="empty">Aún no hay clientes. <a href="#/agregar">Agrega el primero</a>.</p>';
          return;
        }
        $('routeList').innerHTML = st.plan.map((l) => {
          const on = st.visible.has(l.key);
          return `<div class="route ${st.selected === l.key ? 'selected' : ''}" data-key="${l.key}">
            <span class="dot" style="background:${l.route.color}"></span>
            <span class="info"><b>${esc(label(l))}</b><small>${l.items.length} clientes</small></span>
            <button class="mini-btn ${on ? '' : 'off'}" data-act="eye" title="Mostrar / ocultar" aria-label="Mostrar u ocultar ruta">${on ? '👁' : '🚫'}</button>
            <button class="mini-btn" data-act="fit" title="Centrar ruta" aria-label="Centrar ruta">◎</button>
            <span class="chev">›</span></div>`;
        }).join('');
      }
      function renderClients() {
        const l = st.byKey.get(st.selected), ul = $('clientList');
        if (!l) { $('clientsTitle').textContent = 'Clientes'; $('clientsCount').textContent = ''; ul.innerHTML = ''; return; }
        $('clientsTitle').textContent = `Clientes de ${label(l)}`;
        $('clientsCount').textContent = `${l.items.length} clientes`;
        const q = HM.norm($('clientFilter').value.trim());
        const all = l.items.map((it, i) => ({ it, n: i + 1 })).filter((x) => !q || HM.norm(x.it.customer.name).includes(q));
        const shown = all.slice(0, st.limit);
        ul.innerHTML = shown.map(({ it, n }) => `<li data-id="${it.customer.id}" class="${st.active === it.customer.id ? 'active' : ''}">
          <span class="num" style="background:${l.route.color}">${n}</span>
          <span class="name" title="${esc(it.customer.name)}">${esc(it.customer.name)}</span>
          <button class="loc" data-act="loc" title="Ver en el mapa" aria-label="Ver en el mapa">📍</button>
          <a href="tel:${HM.digits(it.customer.phone)}" title="Llamar" aria-label="Llamar">📞</a></li>`).join('')
          + (all.length > shown.length ? `<li class="more"><button class="btn btn-ghost sm" data-act="more">Mostrar más (${all.length - shown.length})</button></li>` : '')
          + (!all.length ? '<li class="empty">Sin resultados.</li>' : '');
      }
      function renderLegend() {
        $('legend').innerHTML = visiblePlan().map((l) => `<div><i style="background:${l.route.color}"></i>${esc(label(l))} (${l.items.length})</div>`).join('');
      }
      function popupHtml(l, it, n) {
        const c = it.customer;
        return `<div class="pop-head"><span class="num" style="background:${l.route.color}">${n}</span>
          <div><b>${esc(c.name)}</b><small>${esc(l.route.name)} · ${HM.dayName(l.day)}</small></div></div>
          <div class="pop-meta">Orden de visita: ${n}<br>${esc(c.address)}</div>
          <div>${HM.coolerBadge(c)}</div>
          <a class="pop-phone" href="tel:${HM.digits(c.phone)}">📞 ${esc(HM.fmtPhone(c.phone))}</a>
          <div class="pop-actions">
            <a class="go" href="${HM.gmapsGo(c)}" target="_blank" rel="noopener">➤ Cómo llegar</a>
            <a class="open" href="${HM.gmapsView(c)}" target="_blank" rel="noopener">↗ Abrir en Maps</a>
          </div>
          <a class="pop-link" href="#/clientes?edit=${c.id}">Ver ficha del cliente</a>`;
      }
      function drawMap() {
        draw.clearLayers(); markers.clear();
        const vis = visiblePlan();
        const total = vis.reduce((a, l) => a + l.items.length, 0);
        const dense = total > DENSE;
        vis.forEach((l) => {
          if (st.opts.lines) L.polyline(l.items.map((i) => [i.customer.lat, i.customer.lng]), { color: l.route.color, weight: 3, opacity: 0.85, renderer: canvas, interactive: false }).addTo(draw);
          if (!st.opts.points) return;
          l.items.forEach((it, i) => {
            const c = it.customer, n = i + 1;
            let m;
            if (dense) {
              m = L.circleMarker([c.lat, c.lng], { renderer: canvas, radius: 6, color: '#fff', weight: 1.5, fillColor: l.route.color, fillOpacity: 1 });
              if (st.opts.names) m.bindTooltip(esc(c.name));
            } else {
              const size = st.opts.numbers ? 24 : 14;
              const tag = st.opts.names ? `<span class="pin-name">${esc(c.name)}</span>` : '';
              m = L.marker([c.lat, c.lng], { icon: L.divIcon({ className: '', iconSize: [size, size], iconAnchor: [size / 2, size / 2],
                html: `<div class="pin" style="background:${l.route.color}">${st.opts.numbers ? n : ''}</div>${tag}` }) });
            }
            m.addTo(draw);
            m.bindPopup(() => popupHtml(l, it, n), { maxWidth: 300 });
            m.on('click', () => { st.selected = l.key; st.active = c.id; renderRoutes(); renderClients(); const a = $('clientList').querySelector('li.active'); if (a && a.scrollIntoView) a.scrollIntoView({ block: 'nearest' }); });
            markers.set(l.key + '|' + c.id, m);
          });
        });
      }
      function fit(list) {
        const pts = list.flatMap((l) => l.items.map((i) => [i.customer.lat, i.customer.lng]));
        if (pts.length) map.fitBounds(pts, { padding: [40, 40], maxZoom: 14 });
      }
      function focusClient(l, c) {
        st.selected = l.key; st.active = c.id;
        if (!st.visible.has(l.key)) { st.visible.add(l.key); renderStats(); renderLegend(); drawMap(); }
        renderRoutes(); renderClients();
        map.setView([c.lat, c.lng], Math.max(map.getZoom(), 15));
        const m = markers.get(l.key + '|' + c.id); if (m) m.openPopup();
        if (isMobile()) $('panel').classList.remove('expanded');
      }
      function renderAll(doFit) {
        renderDays(); renderStats(); renderRoutes(); renderClients(); renderLegend(); drawMap();
        if (doFit) fit(visiblePlan());
      }

      /* ----- Eventos ----- */
      $('days').onclick = (e) => { const b = e.target.closest('[data-day]'); if (b) { loadPlan(+b.dataset.day); renderAll(true); } };
      $('routeList').onclick = (e) => {
        const row = e.target.closest('.route'); if (!row) return;
        const l = st.byKey.get(row.dataset.key); if (!l) return;
        const act = e.target.closest('[data-act]');
        if (act && act.dataset.act === 'eye') {
          if (st.visible.has(l.key)) st.visible.delete(l.key); else st.visible.add(l.key);
          renderStats(); renderRoutes(); renderLegend(); drawMap();
        } else if (act && act.dataset.act === 'fit') {
          st.visible.add(l.key); st.selected = l.key; renderAll(false); fit([l]);
        } else { st.selected = l.key; st.active = null; st.limit = PAGE; renderRoutes(); renderClients(); }
      };
      $('clientList').onclick = (e) => {
        if (e.target.closest('a')) return;
        if (e.target.closest('[data-act="more"]')) { st.limit += PAGE; renderClients(); return; }
        const li = e.target.closest('li[data-id]'); if (!li) return;
        const l = st.byKey.get(st.selected); if (!l) return;
        const it = l.items.find((x) => x.customer.id === li.dataset.id);
        if (it) focusClient(l, it.customer);
      };
      root.querySelectorAll('.switch-row input').forEach((i) => i.addEventListener('change', () => {
        st.opts = { points: $('optPoints').checked, lines: $('optLines').checked, numbers: $('optNumbers').checked, names: $('optNames').checked };
        drawMap();
      }));
      $('btnFit').onclick = () => fit(visiblePlan());
      $('optToggle').onclick = () => {
        const hide = ($('optBody').hidden = !$('optBody').hidden);
        $('optArrow').textContent = hide ? '⌄' : '⌃';
        $('optToggle').setAttribute('aria-expanded', String(!hide));
      };
      $('clientFilter').addEventListener('input', HM.debounce(() => { st.limit = PAGE; renderClients(); }, 120));
      $('btnZoomIn').onclick = () => map.zoomIn();
      $('btnZoomOut').onclick = () => map.zoomOut();
      $('btnLocate').onclick = () => {
        if (!navigator.geolocation) return HM.toast('Tu navegador no permite la geolocalización.', 'error');
        navigator.geolocation.getCurrentPosition((p) => {
          const ll = [p.coords.latitude, p.coords.longitude];
          L.circleMarker(ll, { radius: 8, color: '#fff', weight: 3, fillColor: '#0b5ed7', fillOpacity: 1 }).addTo(map);
          map.setView(ll, 14);
        }, () => HM.toast('No se pudo obtener tu ubicación.', 'error'));
      };
      root.querySelectorAll('.map-type button').forEach((b) => { b.onclick = () => {
        root.querySelectorAll('.map-type button').forEach((x) => x.classList.toggle('active', x === b));
        if (b.dataset.layer === 'sat') { map.removeLayer(tiles.map); tiles.sat.addTo(map); } else { map.removeLayer(tiles.sat); tiles.map.addTo(map); }
      }; });
      $('handle').onclick = () => { $('panel').classList.toggle('expanded'); setTimeout(() => map.invalidateSize(), 300); };
      const onResize = () => map.invalidateSize();
      window.addEventListener('resize', onResize);

      /* ----- Inicio: día actual, o el que pida la URL (?day=) / el cliente (?c=) ----- */
      const jsDay = new Date().getDay();
      let day = +ctx.params.get('day') || (jsDay === 0 ? 7 : jsDay);
      const cid = ctx.params.get('c');
      const target = cid ? S.peek('customers', cid) : null;
      if (target) {
        const own = S.stopsOfCustomer(cid);
        if (own.length && !own.some((s) => s.day === day)) day = own.sort((a, b) => a.day - b.day)[0].day;
      }
      loadPlan(day);
      renderAll(!target);
      setTimeout(() => map.invalidateSize(), 50);
      if (target) {
        const l = st.plan.find((x) => x.items.some((i) => i.customer.id === cid));
        if (l) focusClient(l, target);
        else {
          map.setView([target.lat, target.lng], 16);
          L.marker([target.lat, target.lng]).addTo(map).bindPopup(`<b>${esc(target.name)}</b><br>${esc(target.address)}<br><small>Este cliente aún no está en ninguna ruta.</small>`).openPopup();
        }
      }

      return () => { window.removeEventListener('resize', onResize); map.remove(); };
    },
  };
})();