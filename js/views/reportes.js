(function () {
  'use strict';
  const HM = window.HM, S = HM.Store, esc = HM.esc;

  function bars(title, rows, color) {
    const max = Math.max(1, ...rows.map((r) => r[1]));
    return `<div class="card"><h3>${esc(title)}</h3>${rows.map(([l, n, href]) =>
      `<a class="bar-row" ${href ? `href="${href}"` : ''}><span class="bar-l">${esc(l)}</span><span class="bar"><i style="width:${(n / max) * 100}%;background:${color}"></i></span><b>${n}</b></a>`).join('') || '<p class="muted">Sin datos.</p>'}</div>`;
  }

  HM.views.reportes = {
    mount(root, ctx) {
      ctx.setTitle('Reportes', 'Resumen de clientes, enfriadores y rutas');
      const customers = S.peekAll('customers').filter((c) => c.active !== false);
      const withC = customers.filter((c) => c.cooler && c.cooler.has);
      const count = (arr, fn) => { const m = new Map(); arr.forEach((x) => { const k = fn(x); m.set(k, (m.get(k) || 0) + 1); }); return m; };

      const byStatus = count(withC, (c) => c.cooler.status);
      const byOwner = count(withC, (c) => c.cooler.owner);
      const perDay = HM.DAYS.map((d) => [d.long, S.plan(d.id).reduce((a, l) => a + l.items.length, 0)]);
      const perRoute = S.peekAll('routes').map((r) => [r.name, S.routeDayCounts(r.id).reduce((a, b) => a + b, 0)]);
      const noRoute = customers.filter((c) => !S.stopsOfCustomer(c.id).length).length;
      const attention = withC.filter((c) => c.cooler.status === 'Dañado' || c.cooler.status === 'En reparación').slice(0, 8);

      root.innerHTML = `<div class="page">
        <div class="page-head"><div><h2>Reportes</h2><p class="muted">Datos al momento. Solo clientes activos.</p></div></div>
        <div class="kpis">
          <div class="kpi"><b>${customers.length}</b><span>Clientes activos</span></div>
          <div class="kpi"><b>${withC.length}</b><span>Con enfriador</span></div>
          <div class="kpi"><b>${customers.length - withC.length}</b><span>Sin enfriador</span></div>
          <div class="kpi ${noRoute ? 'warn' : ''}"><b>${noRoute}</b><span>Sin ruta asignada</span></div>
        </div>
        <div class="grid2">
          ${bars('Enfriadores por estado', HM.COOLER_STATUS.map((s) => [s, byStatus.get(s) || 0, `#/clientes?cooler=with&status=${encodeURIComponent(s)}`]), '#0b5ed7')}
          ${bars('Enfriadores por propiedad', HM.COOLER_OWNER.map((s) => [s, byOwner.get(s) || 0]), '#1a9a3c')}
          ${bars('Paradas por día', perDay, '#f08a00')}
          ${bars('Paradas por ruta (semana)', perRoute, '#8a3ffc')}
        </div>
        <div class="card"><h3>Enfriadores que requieren atención</h3>
          ${attention.length ? attention.map((c) => `<div class="att"><a href="#/clientes?edit=${c.id}"><b>${esc(c.name)}</b></a><span>${HM.coolerBadge(c)}</span><small class="muted">${esc(c.address)}</small></div>`).join('') : '<p class="muted">Sin pendientes. 🎉</p>'}
        </div>
        <div class="card"><h3>Descargas</h3>
          <div class="download-row">
            <button class="btn btn-ghost" id="dlCustomers">⬇ Todos los clientes (CSV)</button>
            <label class="inline-field">Hoja de ruta de
              <select id="dayPick">${HM.DAYS.map((d) => `<option value="${d.id}">${d.long}</option>`).join('')}</select></label>
            <button class="btn btn-ghost" id="dlRoute">⬇ Descargar (CSV)</button>
          </div></div></div>`;

      root.querySelector('#dlCustomers').onclick = () => {
        const rows = [['Nombre', 'Teléfono', 'Dirección', 'Enfriador', 'Marca', 'Serie', 'Estado', 'Propiedad']];
        S.query({}).forEach((c) => { const k = c.cooler || {}; rows.push([c.name, c.phone, c.address, k.has ? 'Sí' : 'No', k.brand, k.serial, k.status, k.owner]); });
        HM.download('clientes.csv', HM.toCsv(rows));
      };
      root.querySelector('#dlRoute').onclick = () => {
        const day = +root.querySelector('#dayPick').value;
        const rows = [['Ruta', 'Día', 'Orden', 'Cliente', 'Teléfono', 'Dirección', 'Latitud', 'Longitud', 'Enfriador', 'Estado']];
        S.plan(day).forEach((l) => l.items.forEach((it, i) => { const c = it.customer, k = c.cooler || {};
          rows.push([l.route.name, HM.dayName(day), i + 1, c.name, c.phone, c.address, c.lat, c.lng, k.has ? k.brand + ' ' + k.serial : 'No', k.has ? k.status : '']); }));
        if (rows.length === 1) return HM.toast('No hay rutas programadas ese día.', 'error');
        HM.download(`hoja-de-ruta-${HM.dayName(day).toLowerCase()}.csv`, HM.toCsv(rows));
      };
      return null;
    },
  };
})();