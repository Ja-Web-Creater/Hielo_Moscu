/* Arranque, estructura de la página (menú, encabezado) y enrutador por hash (#/mapa, #/clientes...) */
(function () {
  'use strict';
  const HM = window.HM;
  const app = document.getElementById('app');

  const NAV = [
    { id: 'mapa', label: 'Mapa', ico: '🗺️', tab: true },
    { id: 'rutas', label: 'Rutas', ico: '🚚', tab: true },
    { id: 'clientes', label: 'Clientes', ico: '👥', tab: true },
    { id: 'agregar', label: 'Agregar', ico: '➕', tab: true, perm: 'customers.write' },
    { id: 'buscar', label: 'Buscar', ico: '🔍', tab: true },
    { id: 'reportes', label: 'Reportes', ico: '📈', perm: 'reports.view' },
    { id: 'config', label: 'Config.', ico: '⚙️' },
  ];
  const allowed = () => NAV.filter((n) => !n.perm || HM.Auth.can(n.perm));

  let cleanup = null, shellUid = null, token = 0;

  function parseHash() {
    const h = location.hash.replace(/^#\/?/, '');
    const [path, q] = h.split('?');
    return { name: path || 'mapa', params: new URLSearchParams(q || '') };
  }

  function buildShell(user) {
    const items = allowed();
    app.innerHTML = `
      <div class="app">
        <nav class="nav" aria-label="Principal">
          <div class="nav-logo" aria-hidden="true">❄</div>
          ${items.map((n) => `<a class="nav-item ${n.id === 'config' ? 'nav-bottom' : ''}" data-id="${n.id}" href="#/${n.id}"><span class="ico">${n.ico}</span>${n.label}</a>`).join('')}
        </nav>
        <div class="main">
          <header class="topbar">
            <div class="brand"><span class="brand-snow" aria-hidden="true">❄</span><div class="brand-text"><small>HIELO</small><strong>MOSCÚ</strong></div></div>
            <div class="title"><h1 id="pageTitle"></h1><p id="pageSub"></p></div>
            <div class="search" id="gSearchBox">
              <span aria-hidden="true">🔍</span>
              <input id="gSearch" type="search" placeholder="Buscar cliente, teléfono o dirección..." autocomplete="off" aria-label="Buscar">
              <ul id="gResults" class="search-results" hidden></ul>
            </div>
            <button class="icon-btn" id="btnFullscreen" title="Pantalla completa" aria-label="Pantalla completa">⛶</button>
            <div class="user-wrap">
              <button class="user-btn" id="userBtn" aria-haspopup="true" aria-expanded="false" aria-label="Menú de usuario">
                <span class="avatar">${HM.esc(user.name.trim().charAt(0).toUpperCase())}</span>
                <span class="user-meta"><b>${HM.esc(user.name)}</b><small>${HM.esc(HM.ROLES[user.role])}</small></span>
              </button>
              <div class="user-menu" id="userMenu" hidden>
                ${items.filter((n) => !n.tab).map((n) => `<a href="#/${n.id}">${n.ico} ${n.id === 'config' ? 'Configuración' : n.label}</a>`).join('')}
                <button id="btnLogout">⎋ Cerrar sesión</button>
              </div>
            </div>
          </header>
          <main id="view" tabindex="-1"></main>
          <nav class="tabbar" aria-label="Principal móvil">
            ${items.filter((n) => n.tab).map((n) => `<a data-id="${n.id}" href="#/${n.id}"><span class="ico">${n.ico}</span>${n.label}</a>`).join('')}
          </nav>
        </div>
      </div>`;

    const $ = (id) => document.getElementById(id);
    const menu = $('userMenu'), btn = $('userBtn');
    btn.onclick = (e) => { e.stopPropagation(); menu.hidden = !menu.hidden; btn.setAttribute('aria-expanded', String(!menu.hidden)); };
    menu.onclick = () => { menu.hidden = true; };
    $('btnLogout').onclick = () => { HM.Auth.logout(); shellUid = null; location.hash = '#/mapa'; render(); };
    $('btnFullscreen').onclick = () => {
      if (document.fullscreenElement) document.exitFullscreen();
      else if (document.documentElement.requestFullscreen) document.documentElement.requestFullscreen();
    };

    /* Búsqueda global rápida */
    const inp = $('gSearch'), box = $('gResults');
    const run = HM.debounce(() => {
      const q = inp.value.trim();
      if (q.length < 2) { box.hidden = true; return; }
      const hits = HM.Store.query({ q }).slice(0, 8);
      box.innerHTML = hits.length
        ? hits.map((c) => `<li data-id="${c.id}" tabindex="0"><span>${HM.esc(c.name)}</span><small>${HM.esc(HM.fmtPhone(c.phone))}</small></li>`).join('')
          + `<li data-all="1"><small>Ver todos los resultados →</small></li>`
        : '<li><small>Sin resultados</small></li>';
      box.hidden = false;
    }, 150);
    inp.addEventListener('input', run);
    inp.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && inp.value.trim()) { location.hash = '#/buscar?q=' + encodeURIComponent(inp.value.trim()); box.hidden = true; inp.blur(); }
      if (e.key === 'Escape') box.hidden = true;
    });
    box.addEventListener('click', (e) => {
      const li = e.target.closest('li'); if (!li) return;
      if (li.dataset.all) location.hash = '#/buscar?q=' + encodeURIComponent(inp.value.trim());
      else if (li.dataset.id) location.hash = '#/mapa?c=' + li.dataset.id;
      box.hidden = true; inp.value = '';
    });
  }

  /* Cierra menús al hacer clic fuera (un solo listener global) */
  document.addEventListener('click', (e) => {
    const m = document.getElementById('userMenu'), b = document.getElementById('gResults');
    if (m && !e.target.closest('.user-wrap')) m.hidden = true;
    if (b && !e.target.closest('.search')) b.hidden = true;
  });

  function setActive(name) {
    document.querySelectorAll('.nav-item, .tabbar a').forEach((a) => a.classList.toggle('active', a.dataset.id === name));
  }

  async function render() {
    const my = ++token;
    if (cleanup) { try { cleanup(); } catch (e) { console.error(e); } cleanup = null; }
    const user = HM.Auth.current();
    if (!user) {
      shellUid = null;
      HM.views.login.mount(app, { onSuccess: () => render() });
      return;
    }
    if (shellUid !== user.uid) { buildShell(user); shellUid = user.uid; }

    const { name, params } = parseHash();
    const def = HM.views[name], item = NAV.find((n) => n.id === name);
    if (!def || !item || (item.perm && !HM.Auth.can(item.perm))) { location.hash = '#/mapa'; return; }

    setActive(name);
    const view = document.getElementById('view');
    view.innerHTML = '';
    view.scrollTop = 0;
    const ctx = {
      params,
      user,
      setTitle(t, s) {
        document.getElementById('pageTitle').textContent = t;
        document.getElementById('pageSub').textContent = s || '';
        document.title = t + ' · Hielo Moscú';
      },
    };
    try {
      const c = await def.mount(view, ctx);
      if (my !== token) { if (c) c(); return; }
      cleanup = c || null;
    } catch (e) {
      console.error(e);
      view.innerHTML = '<div class="page"><div class="card"><b>Ocurrió un error al abrir esta pantalla.</b><p class="muted">Recarga la página. Si sigue pasando, avisa al administrador.</p></div></div>';
    }
  }

  async function boot() {
    app.innerHTML = '<div class="splash">❄ Cargando…</div>';
    try { await HM.Seed.run(); } catch (e) { console.error(e); }
    window.addEventListener('hashchange', render);
    /* Si la sesión expira mientras la pantalla está abierta, vuelve al login */
    setInterval(() => { if (shellUid && !HM.Auth.current()) render(); }, 60000);
    render();
  }
  boot();
})();