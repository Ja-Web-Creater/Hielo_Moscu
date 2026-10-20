/* Capa de datos.
   Hoy guarda en localStorage del navegador. Todas las funciones públicas son async
   a propósito: el día que exista la API (PHP/Node + MySQL) solo se reemplaza este
   archivo por uno que haga fetch(), y las pantallas no cambian.
   Para lecturas rápidas en pantalla hay accesos síncronos (peek / peekAll / query / plan). */
(function () {
  'use strict';
  const HM = window.HM;
  const PREFIX = 'hm.v1.';
  const ENT = ['users', 'customers', 'routes', 'stops'];
  const data = {}, idx = {}, dirty = new Set();
  let stopIdx = null, searchIdx = null, saveTimer = null;

  function load(e) {
    try { const v = JSON.parse(localStorage.getItem(PREFIX + e)); return Array.isArray(v) ? v : []; }
    catch (_) { return []; }
  }
  ENT.forEach((e) => { data[e] = load(e); });

  function flush() {
    clearTimeout(saveTimer);
    dirty.forEach((e) => {
      try { localStorage.setItem(PREFIX + e, JSON.stringify(data[e])); }
      catch (_) { HM.toast('No se pudo guardar: el almacenamiento del navegador está lleno.', 'error'); }
    });
    dirty.clear();
  }
  window.addEventListener('beforeunload', flush);

  function touch(e) {
    idx[e] = null;
    if (e === 'stops' || e === 'customers' || e === 'routes') stopIdx = null;
    if (e === 'customers') searchIdx = null;
    dirty.add(e);
    clearTimeout(saveTimer);
    saveTimer = setTimeout(flush, 120);
  }
  const byId = (e) => idx[e] || (idx[e] = new Map(data[e].map((x) => [x.id, x])));

  /* Índice de paradas: día -> ruta -> paradas ordenadas; cliente -> paradas */
  function stopIndex() {
    if (stopIdx) return stopIdx;
    const byDay = new Map(), byCustomer = new Map();
    for (const s of data.stops) {
      let d = byDay.get(s.day); if (!d) byDay.set(s.day, (d = new Map()));
      let r = d.get(s.routeId); if (!r) d.set(s.routeId, (r = []));
      r.push(s);
      let c = byCustomer.get(s.customerId); if (!c) byCustomer.set(s.customerId, (c = []));
      c.push(s);
    }
    byDay.forEach((d) => d.forEach((arr) => arr.sort((a, b) => a.order - b.order)));
    return (stopIdx = { byDay, byCustomer });
  }

  function renumber(routeId, day) {
    data.stops.filter((s) => s.routeId === routeId && s.day === day)
      .sort((a, b) => a.order - b.order).forEach((s, i) => { s.order = i + 1; });
  }

  function searchText(c) {
    const k = c.cooler && c.cooler.has ? [c.cooler.brand, c.cooler.model, c.cooler.serial].join(' ') : '';
    return HM.norm([c.name, c.contact, c.phone, c.address, k].join(' '));
  }
  function searchIndex() {
    if (searchIdx) return searchIdx;
    searchIdx = new Map();
    for (const c of data.customers) searchIdx.set(c.id, searchText(c));
    return searchIdx;
  }

  const Store = {
    /* ----- CRUD genérico (async) ----- */
    async all(e) { return data[e].slice(); },
    async get(e, id) { return byId(e).get(id) || null; },
    async insert(e, obj) {
      const now = Date.now();
      const row = Object.assign({ id: HM.uid(), createdAt: now, updatedAt: now }, obj);
      data[e].push(row); touch(e); return row;
    },
    async insertMany(e, list) {
      const now = Date.now();
      const rows = list.map((o) => Object.assign({ id: HM.uid(), createdAt: now, updatedAt: now }, o));
      for (const r of rows) data[e].push(r);
      touch(e); return rows;
    },
    async update(e, id, patch) {
      const row = byId(e).get(id);
      if (!row) throw new Error('El registro no existe.');
      Object.assign(row, patch, { updatedAt: Date.now() });
      touch(e); return row;
    },
    async remove(e, id) {
      const i = data[e].findIndex((x) => x.id === id);
      if (i >= 0) { data[e].splice(i, 1); touch(e); }
    },

    /* ----- Lecturas síncronas rápidas ----- */
    peek(e, id) { return byId(e).get(id) || null; },
    peekAll(e) { return data[e]; },

    /* ----- Dominio ----- */
    /* Rutas programadas de un día (0 = todos). Devuelve [{key, route, day, items:[{stop, customer}]}] */
    plan(day) {
      const days = day ? [day] : [1, 2, 3, 4, 5, 6, 7];
      const out = [], si = stopIndex(), cm = byId('customers');
      for (const d of days) {
        const m = si.byDay.get(d); if (!m) continue;
        for (const r of data.routes) {
          if (r.active === false) continue;
          const stops = m.get(r.id); if (!stops || !stops.length) continue;
          const items = [];
          for (const s of stops) { const c = cm.get(s.customerId); if (c && c.active !== false) items.push({ stop: s, customer: c }); }
          if (items.length) out.push({ key: r.id + ':' + d, route: r, day: d, items });
        }
      }
      return out;
    },
    dayRouteCount(day) {
      const m = stopIndex().byDay.get(day); if (!m) return 0;
      let n = 0; for (const r of data.routes) if (r.active !== false && m.has(r.id)) n++;
      return n;
    },
    routeDayCounts(routeId) {
      const out = [];
      for (let d = 1; d <= 7; d++) { const m = stopIndex().byDay.get(d); out.push(m && m.get(routeId) ? m.get(routeId).length : 0); }
      return out;
    },
    stopsOfCustomer(customerId) { return (stopIndex().byCustomer.get(customerId) || []).slice(); },
    stopsOf(routeId, day) { const m = stopIndex().byDay.get(day); return ((m && m.get(routeId)) || []).slice(); },

    /* Reemplaza la lista de clientes (en orden) de una ruta en un día */
    async setRouteDay(routeId, day, customerIds) {
      data.stops = data.stops.filter((s) => !(s.routeId === routeId && s.day === day));
      const now = Date.now();
      customerIds.forEach((cid, i) => data.stops.push({ id: HM.uid(), routeId, day, customerId: cid, order: i + 1, createdAt: now, updatedAt: now }));
      touch('stops');
    },

    /* Deja al cliente asignado exactamente a "desired" [{routeId, day, position|null}] */
    async syncAssignments(customerId, desired) {
      const keep = new Set(desired.map((d) => d.routeId + ':' + d.day));
      const touched = new Set();
      data.stops = data.stops.filter((s) => {
        if (s.customerId !== customerId) return true;
        const k = s.routeId + ':' + s.day;
        if (keep.has(k)) return true;
        touched.add(k); return false;
      });
      for (const d of desired) {
        const k = d.routeId + ':' + d.day;
        const list = data.stops.filter((s) => s.routeId === d.routeId && s.day === d.day).sort((a, b) => a.order - b.order);
        const at = list.findIndex((s) => s.customerId === customerId);
        if (at >= 0 && (d.position == null || d.position - 1 === at)) continue;
        let stop;
        if (at >= 0) stop = list.splice(at, 1)[0];
        else {
          const now = Date.now();
          stop = { id: HM.uid(), routeId: d.routeId, day: d.day, customerId, order: 0, createdAt: now, updatedAt: now };
          data.stops.push(stop);
        }
        const pos = d.position == null ? list.length : Math.max(0, Math.min(list.length, d.position - 1));
        list.splice(pos, 0, stop);
        list.forEach((s, i) => { s.order = i + 1; });
        touched.add(k);
      }
      touched.forEach((k) => { const [r, dd] = k.split(':'); renumber(r, +dd); });
      touch('stops');
    },

    async removeCustomer(id) {
      const hit = new Set(data.stops.filter((s) => s.customerId === id).map((s) => s.routeId + ':' + s.day));
      data.stops = data.stops.filter((s) => s.customerId !== id);
      hit.forEach((k) => { const [r, d] = k.split(':'); renumber(r, +d); });
      touch('stops');
      await Store.remove('customers', id);
    },
    async removeRoute(id) {
      data.stops = data.stops.filter((s) => s.routeId !== id);
      touch('stops');
      await Store.remove('routes', id);
    },

    /* Búsqueda de clientes con filtros. Rápida: texto normalizado precalculado. */
    query({ q = '', cooler = '', status = '', routeId = '', day = 0, active = '' } = {}) {
      const tokens = HM.norm(q).split(/\s+/).filter(Boolean);
      const si = searchIndex(), sti = stopIndex();
      const out = [];
      for (const c of data.customers) {
        if (active === 'yes' && c.active === false) continue;
        if (active === 'no' && c.active !== false) continue;
        const has = !!(c.cooler && c.cooler.has);
        if (cooler === 'with' && !has) continue;
        if (cooler === 'without' && has) continue;
        if (status && !(has && c.cooler.status === status)) continue;
        if (routeId || day) {
          const st = sti.byCustomer.get(c.id) || [];
          if (!st.some((s) => (!routeId || s.routeId === routeId) && (!day || s.day === +day))) continue;
        }
        if (tokens.length) { const t = si.get(c.id); if (!tokens.every((k) => t.includes(k))) continue; }
        out.push(c);
      }
      return out.sort((a, b) => a.name.localeCompare(b.name, 'es'));
    },

    /* ----- Respaldo ----- */
    exportAll() {
      const o = { app: 'hielo-moscu', version: 1, exportedAt: new Date().toISOString() };
      ENT.forEach((e) => { o[e] = data[e]; });
      return o;
    },
    async importAll(o) {
      if (!o || o.app !== 'hielo-moscu') throw new Error('El archivo no es un respaldo de Hielo Moscú.');
      for (const e of ENT) if (!Array.isArray(o[e])) throw new Error('El respaldo está incompleto (' + e + ').');
      if (!o.users.some((u) => u.role === 'admin' && u.active !== false)) throw new Error('El respaldo no tiene un administrador activo.');
      ENT.forEach((e) => { data[e] = o[e]; touch(e); });
    },
    async clearBusinessData() {
      ['customers', 'routes', 'stops'].forEach((e) => { data[e] = []; touch(e); });
    },
    flush,
  };

  HM.Store = Store;
})();