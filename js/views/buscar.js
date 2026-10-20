(function () {
  'use strict';
  const HM = window.HM, S = HM.Store;
  const SIZE = 20;

  HM.views.buscar = {
    mount(root, ctx) {
      ctx.setTitle('Buscar', 'Encuentra clientes, enfriadores y rutas');
      const canW = HM.Auth.can('customers.write');
      const f = { q: ctx.params.get('q') || '', cooler: '', status: '', routeId: '', day: '', active: 'yes', page: 1 };
      root.innerHTML = `<div class="page narrow">
        <div class="page-head"><div><h2>Buscar</h2><p class="muted">Por nombre, contacto, teléfono, dirección, marca o número de serie del enfriador.</p></div></div>
        <div class="card filters">
          <div class="big-search"><span aria-hidden="true">🔍</span><input id="q" type="search" placeholder="Escribe para buscar…" value="${HM.esc(f.q)}" autocomplete="off" aria-label="Buscar"></div>
          <div class="filter-row" id="fr">${HM.filtersHtml(f)}</div>
        </div>
        <p class="muted" id="count"></p>
        <div class="card list">${HM.customerHeader}<div id="rows"></div></div>
        <div id="pager"></div></div>`;
      const $ = (s) => root.querySelector(s);

      function render() {
        const hasFilter = f.q.trim() || f.cooler || f.status || f.routeId || f.day;
        if (!hasFilter) {
          $('#count').textContent = '';
          $('#rows').innerHTML = '<div class="empty-card">Escribe algo o elige un filtro para empezar.</div>';
          $('#pager').innerHTML = '';
          return;
        }
        const res = S.query(f), pg = HM.paginate(res, f.page, SIZE); f.page = pg.page;
        $('#count').textContent = `${res.length} ${res.length === 1 ? 'resultado' : 'resultados'}`;
        $('#rows').innerHTML = pg.items.map((c) => HM.customerRow(c, { edit: canW })).join('') || '<div class="empty-card">Sin resultados. Prueba con menos palabras.</div>';
        $('#pager').innerHTML = HM.pagerHtml(pg);
      }
      $('#q').addEventListener('input', HM.debounce((e) => { f.q = e.target.value; f.page = 1; render(); }, 160));
      $('#fr').addEventListener('change', (e) => { f[e.target.name] = e.target.value; f.page = 1; render(); });
      $('#pager').onclick = (e) => { const b = e.target.closest('[data-page]'); if (b) { f.page = +b.dataset.page; render(); root.scrollTop = 0; } };
      $('#rows').onclick = (e) => {
        const btn = e.target.closest('[data-act]'); if (!btn) return;
        const id = btn.closest('.lrow').dataset.id, c = S.peek('customers', id); if (!c) return;
        if (btn.dataset.act === 'map') location.hash = '#/mapa?c=' + id;
        else if (btn.dataset.act === 'edit') HM.openCustomer(c, render);
      };
      render();
      if (!f.q) $('#q').focus();
      return null;
    },
  };
})();