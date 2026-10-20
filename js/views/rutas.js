(function () {
  'use strict';
  const HM = window.HM, S = HM.Store, esc = HM.esc;

  HM.views.rutas = {
    mount(root, ctx) {
      ctx.setTitle('Rutas', 'Recorridos por día y orden de visita');
      const canW = HM.Auth.can('routes.write');
      root.innerHTML = `<div class="page">
        <div class="page-head"><div><h2>Rutas</h2><p class="muted">Cada ruta tiene un recorrido distinto por día de la semana.</p></div>
        ${canW ? '<button class="btn btn-primary" id="newRoute">+ Nueva ruta</button>' : ''}</div>
        <div id="grid" class="route-grid"></div></div>`;
      const grid = root.querySelector('#grid');

      function render() {
        const routes = S.peekAll('routes');
        if (!routes.length) { grid.innerHTML = '<div class="card empty-card">Todavía no hay rutas.' + (canW ? ' Crea la primera con el botón de arriba.' : '') + '</div>'; return; }
        grid.innerHTML = routes.map((r) => {
          const counts = S.routeDayCounts(r.id), total = counts.reduce((a, b) => a + b, 0);
          const drv = S.peek('users', r.driverId);
          return `<article class="card route-card ${r.active === false ? 'inactive' : ''}" data-id="${r.id}" style="--c:${r.color}">
            <header><span class="dot" style="background:${r.color}"></span><div><h3>${esc(r.name)}${r.active === false ? ' <em>(inactiva)</em>' : ''}</h3>
              <small class="muted">${drv ? '🚚 ' + esc(drv.name) : 'Sin repartidor asignado'} · ${total} paradas</small></div></header>
            <div class="chips">${HM.DAYS.map((d, i) => `<button class="chip ${counts[i] ? '' : 'off'}" data-act="map" data-day="${d.id}" ${counts[i] ? '' : 'disabled'} title="Ver ${d.long} en el mapa">${d.short} <b>${counts[i]}</b></button>`).join('')}</div>
            <footer>
              <button class="btn btn-ghost sm" data-act="stops">${canW ? '↕ Editar recorrido' : '👁 Ver recorrido'}</button>
              ${canW ? '<button class="btn btn-ghost sm" data-act="edit">✏️ Datos</button><button class="btn btn-ghost sm danger" data-act="del">🗑</button>' : ''}
            </footer></article>`;
        }).join('');
      }

      /* ----- Datos de la ruta ----- */
      function routeForm(route) {
        const drivers = S.peekAll('users').filter((u) => u.role === 'repartidor' && u.active !== false);
        const node = document.createElement('div');
        node.innerHTML = `<form class="cform" novalidate>
          <label class="field"><span>Nombre de la ruta <i class="req">*</i></span><input name="name" maxlength="40" value="${esc(route ? route.name : '')}"><small class="err"></small></label>
          <div class="field"><span>Color en el mapa</span><div class="swatches">${HM.ROUTE_COLORS.map((c, i) =>
            `<label class="swatch" style="background:${c}"><input type="radio" name="color" value="${c}" ${(route ? route.color : HM.ROUTE_COLORS[S.peekAll('routes').length % 8]) === c ? 'checked' : ''}><i>✓</i></label>`).join('')}</div></div>
          <label class="field"><span>Repartidor</span><select name="driver"><option value="">Sin asignar</option>
            ${drivers.map((u) => `<option value="${u.id}" ${route && route.driverId === u.id ? 'selected' : ''}>${esc(u.name)}</option>`).join('')}</select></label>
          ${route ? `<label class="check"><input type="checkbox" name="active" ${route.active !== false ? 'checked' : ''}> Ruta activa</label>` : ''}
          <div class="form-actions"><button type="button" class="btn btn-ghost" data-no>Cancelar</button><button class="btn btn-primary">Guardar</button></div></form>`;
        const m = HM.openModal(route ? 'Editar ruta' : 'Nueva ruta', node);
        const f = node.querySelector('form');
        node.querySelector('[data-no]').onclick = m.close;
        f.onsubmit = async (ev) => {
          ev.preventDefault();
          const name = f.name.value.trim();
          if (name.length < 2) { f.querySelector('.err').textContent = 'Escribe el nombre de la ruta.'; return; }
          if (S.peekAll('routes').some((r) => r.name.toLowerCase() === name.toLowerCase() && (!route || r.id !== route.id))) { f.querySelector('.err').textContent = 'Ya existe una ruta con ese nombre.'; return; }
          const d = { name, color: f.color.value, driverId: f.driver.value };
          if (route) { d.active = f.active.checked; await S.update('routes', route.id, d); } else await S.insert('routes', Object.assign(d, { active: true }));
          HM.toast('Ruta guardada.'); m.close(); render();
        };
      }

      /* ----- Editor de recorrido (orden de visita) ----- */
      function stopsEditor(route, startDay) {
        const lists = {}, changed = new Set();
        let day = startDay || 1;
        const load = (d) => lists[d] || (lists[d] = S.stopsOf(route.id, d).map((s) => s.customerId));
        const node = document.createElement('div');
        node.innerHTML = `
          <div class="day-tabs" id="dt"></div>
          ${canW ? `<div class="stops-tools"><div class="search inline"><span>🔍</span><input id="addQ" placeholder="Agregar cliente (nombre, teléfono, dirección)…" autocomplete="off"><ul id="addRes" class="search-results" hidden></ul></div>
            <button class="btn btn-ghost sm" id="nn" title="Reordena para minimizar el recorrido (aproximado)">🧭 Ordenar por cercanía</button></div>` : ''}
          <ol id="sl" class="stop-list"></ol>
          <div class="form-actions"><span class="muted" id="sum"></span><button class="btn btn-ghost" data-no>${canW ? 'Cancelar' : 'Cerrar'}</button>${canW ? '<button class="btn btn-primary" id="save">Guardar recorrido</button>' : ''}</div>`;
        const m = HM.openModal(`${route.name} · recorrido`, node, { wide: true });
        const $ = (s) => node.querySelector(s);
        node.querySelector('[data-no]').onclick = m.close;

        function km(list) {
          let t = 0;
          for (let i = 1; i < list.length; i++) { const a = S.peek('customers', list[i - 1]), b = S.peek('customers', list[i]); if (a && b) t += HM.haversine(a, b); }
          return t;
        }
        function renderTabs() {
          $('#dt').innerHTML = HM.DAYS.map((d) => `<button class="day-tab ${d.id === day ? 'active' : ''}" data-d="${d.id}">${d.short} <b>${load(d.id).length}</b></button>`).join('');
        }
        function renderList() {
          const list = load(day);
          $('#sl').innerHTML = list.map((id, i) => {
            const c = S.peek('customers', id); if (!c) return '';
            return `<li draggable="${canW}" data-i="${i}"><span class="num" style="background:${route.color}">${i + 1}</span>
              <span class="name"><b>${esc(c.name)}</b><small>${esc(c.address)}</small></span>
              ${canW ? `<button class="icon-btn sm" data-a="up" ${i === 0 ? 'disabled' : ''} aria-label="Subir">↑</button><button class="icon-btn sm" data-a="down" ${i === list.length - 1 ? 'disabled' : ''} aria-label="Bajar">↓</button><button class="icon-btn sm danger" data-a="rm" aria-label="Quitar">✕</button>` : ''}</li>`;
          }).join('') || '<li class="empty">Sin clientes este día.</li>';
          $('#sum').textContent = `${list.length} paradas · ~${km(list).toFixed(1)} km en línea recta`;
          renderTabs();
        }
        const mark = () => changed.add(day);
        $('#dt').onclick = (e) => { const b = e.target.closest('[data-d]'); if (b) { day = +b.dataset.d; renderList(); } };
        const ol = $('#sl');
        ol.onclick = (e) => {
          const btn = e.target.closest('[data-a]'); if (!btn) return;
          const i = +btn.closest('li').dataset.i, list = load(day);
          if (btn.dataset.a === 'rm') list.splice(i, 1);
          else { const j = btn.dataset.a === 'up' ? i - 1 : i + 1; [list[i], list[j]] = [list[j], list[i]]; }
          mark(); renderList();
        };
        let dragFrom = null;
        ol.ondragstart = (e) => { const li = e.target.closest('li'); if (li) { dragFrom = +li.dataset.i; e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', String(dragFrom)); } catch (_) { /* nada */ } } };
        ol.ondragover = (e) => { if (dragFrom != null) e.preventDefault(); };
        ol.ondrop = (e) => {
          const li = e.target.closest('li'); if (!li || dragFrom == null) return;
          e.preventDefault();
          const to = +li.dataset.i, list = load(day);
          list.splice(to, 0, list.splice(dragFrom, 1)[0]); dragFrom = null; mark(); renderList();
        };
        if (canW) {
          const q = $('#addQ'), res = $('#addRes');
          q.addEventListener('input', HM.debounce(() => {
            const t = q.value.trim(); if (t.length < 2) { res.hidden = true; return; }
            const inList = new Set(load(day));
            const hits = S.query({ q: t }).filter((c) => !inList.has(c.id)).slice(0, 8);
            res.innerHTML = hits.length ? hits.map((c) => `<li data-id="${c.id}"><span>${esc(c.name)}</span><small>${esc(c.address)}</small></li>`).join('') : '<li><small>Sin resultados</small></li>';
            res.hidden = false;
          }, 150));
          res.onclick = (e) => { const li = e.target.closest('li[data-id]'); if (!li) return; load(day).push(li.dataset.id); mark(); q.value = ''; res.hidden = true; renderList(); };
          $('#nn').onclick = () => {
            const list = load(day), pts = list.map((id) => S.peek('customers', id)).filter(Boolean);
            if (pts.length < 3) return HM.toast('Se necesitan al menos 3 paradas.', 'error');
            const out = [pts[0]], rest = pts.slice(1);
            while (rest.length) {
              const last = out[out.length - 1]; let bi = 0, bd = Infinity;
              rest.forEach((p, i) => { const d = HM.haversine(last, p); if (d < bd) { bd = d; bi = i; } });
              out.push(rest.splice(bi, 1)[0]);
            }
            lists[day] = out.map((c) => c.id); mark(); renderList(); HM.toast('Orden actualizado. Recuerda guardar.');
          };
          $('#save').onclick = async () => {
            for (const d of changed) await S.setRouteDay(route.id, d, lists[d]);
            HM.toast('Recorrido guardado.'); m.close(); render();
          };
        }
        renderList();
      }

      /* ----- Eventos ----- */
      const nr = root.querySelector('#newRoute'); if (nr) nr.onclick = () => routeForm(null);
      grid.onclick = async (e) => {
        const btn = e.target.closest('[data-act]'); if (!btn) return;
        const id = btn.closest('.route-card').dataset.id, r = S.peek('routes', id); if (!r) return;
        if (btn.dataset.act === 'map') location.hash = `#/mapa?day=${btn.dataset.day}`;
        else if (btn.dataset.act === 'stops') {
          const counts = S.routeDayCounts(id); const first = counts.findIndex((n) => n > 0) + 1;
          stopsEditor(r, first || 1);
        } else if (btn.dataset.act === 'edit') routeForm(r);
        else if (btn.dataset.act === 'del') {
          if (await HM.confirm(`¿Eliminar "${r.name}"? Se quitarán sus paradas, pero los clientes NO se borran.`, { okText: 'Eliminar', danger: true })) {
            await S.removeRoute(id); HM.toast('Ruta eliminada.'); render();
          }
        }
      };
      render();
      return null;
    },
  };
})();