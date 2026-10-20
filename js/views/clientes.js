(function () {
  'use strict';
  const HM = window.HM, S = HM.Store;
  const SIZE = 25;

  /* Filtros reutilizados por Clientes y Buscar */
  HM.filtersHtml = (f) => `
    <select name="cooler" aria-label="Enfriador"><option value="">Enfriador: todos</option><option value="with" ${f.cooler === 'with' ? 'selected' : ''}>Con enfriador</option><option value="without" ${f.cooler === 'without' ? 'selected' : ''}>Sin enfriador</option></select>
    <select name="status" aria-label="Estado del enfriador"><option value="">Estado: todos</option>${HM.COOLER_STATUS.map((s) => `<option ${f.status === s ? 'selected' : ''}>${s}</option>`).join('')}</select>
    <select name="routeId" aria-label="Ruta"><option value="">Ruta: todas</option>${S.peekAll('routes').map((r) => `<option value="${r.id}" ${f.routeId === r.id ? 'selected' : ''}>${HM.esc(r.name)}</option>`).join('')}</select>
    <select name="day" aria-label="Día"><option value="">Día: todos</option>${HM.DAYS.map((d) => `<option value="${d.id}" ${+f.day === d.id ? 'selected' : ''}>${d.long}</option>`).join('')}</select>`;

  HM.views.clientes = {
    mount(root, ctx) {
      ctx.setTitle('Clientes', 'Directorio, enfriadores y rutas asignadas');
      const canW = HM.Auth.can('customers.write'), canR = HM.Auth.can('reports.view');
      const p = ctx.params;
      const f = { q: p.get('q') || '', cooler: p.get('cooler') || '', status: p.get('status') || '', routeId: p.get('routeId') || '', day: p.get('day') || '', active: 'yes', page: 1 };

      root.innerHTML = `<div class="page">
        <div class="page-head"><div><h2>Clientes</h2><p class="muted" id="count"></p></div>
          <div class="head-actions">${canR ? '<button class="btn btn-ghost" id="csv">⬇ Exportar CSV</button>' : ''}${canW ? '<a class="btn btn-primary" href="#/agregar">+ Nuevo cliente</a>' : ''}</div></div>
        <div class="filters card">
          <input id="q" type="search" placeholder="Buscar por nombre, teléfono, dirección o serie…" value="${HM.esc(f.q)}" aria-label="Buscar">
          <div class="filter-row" id="fr">${HM.filtersHtml(f)}
            <select name="active" aria-label="Activos"><option value="yes">Solo activos</option><option value="">Todos</option><option value="no">Solo inactivos</option></select></div>
        </div>
        <div class="card list">${HM.customerHeader}<div id="rows"></div></div>
        <div id="pager"></div></div>`;
      const $ = (s) => root.querySelector(s);
      let result = [];

      function render() {
        result = S.query(f);
        const pg = HM.paginate(result, f.page, SIZE); f.page = pg.page;
        $('#count').textContent = `${result.length} ${result.length === 1 ? 'cliente' : 'clientes'}`;
        $('#rows').innerHTML = pg.items.map((c) => HM.customerRow(c, { edit: canW, del: canW })).join('')
          || '<div class="empty-card">No hay clientes con esos filtros.</div>';
        $('#pager').innerHTML = HM.pagerHtml(pg);
      }
      $('#q').addEventListener('input', HM.debounce((e) => { f.q = e.target.value; f.page = 1; render(); }, 180));
      $('#fr').addEventListener('change', (e) => { f[e.target.name] = e.target.value; f.page = 1; render(); });
      $('#pager').onclick = (e) => { const b = e.target.closest('[data-page]'); if (b) { f.page = +b.dataset.page; render(); root.scrollTop = 0; } };
      $('#rows').onclick = async (e) => {
        const btn = e.target.closest('[data-act]'); if (!btn) return;
        const id = btn.closest('.lrow').dataset.id, c = S.peek('customers', id); if (!c) return;
        if (btn.dataset.act === 'map') location.hash = '#/mapa?c=' + id;
        else if (btn.dataset.act === 'edit') HM.openCustomer(c, render);
        else if (btn.dataset.act === 'del' && await HM.confirm(`¿Eliminar a "${c.name}"? Se quitará también de sus rutas. Esta acción no se puede deshacer.`, { okText: 'Eliminar', danger: true })) {
          await S.removeCustomer(id); HM.toast('Cliente eliminado.'); render();
        }
      };
      const csv = $('#csv');
      if (csv) csv.onclick = () => {
        const rows = [['Nombre', 'Contacto', 'Teléfono', 'Dirección', 'Latitud', 'Longitud', 'Activo', 'Enfriador', 'Marca', 'Modelo', 'Capacidad', 'Serie', 'Estado', 'Propiedad', 'Rutas']];
        result.forEach((c) => { const k = c.cooler || {}; rows.push([c.name, c.contact, c.phone, c.address, c.lat, c.lng, c.active === false ? 'No' : 'Sí',
          k.has ? 'Sí' : 'No', k.brand, k.model, k.has ? k.capacity + ' ' + k.unit : '', k.serial, k.status, k.owner,
          S.stopsOfCustomer(c.id).map((s) => { const r = S.peek('routes', s.routeId); return (r ? r.name : '?') + ' ' + HM.dayShort(s.day) + ' #' + s.order; }).join(' | ')]); });
        HM.download('clientes.csv', HM.toCsv(rows)); HM.toast('Archivo descargado.');
      };
      render();

      /* ?edit=ID abre la ficha (viene del mapa) */
      const eid = p.get('edit');
      if (eid && S.peek('customers', eid)) {
        history.replaceState(null, '', '#/clientes');
        HM.openCustomer(S.peek('customers', eid), render);
      }
      return null;
    },
  };
})();