/* Utilidades de interfaz compartidas. Todo cuelga del espacio de nombres HM. */
(function () {
  'use strict';
  const HM = (window.HM = window.HM || {});

  HM.DAYS = [
    { id: 1, short: 'Lun', long: 'Lunes' }, { id: 2, short: 'Mar', long: 'Martes' },
    { id: 3, short: 'Mié', long: 'Miércoles' }, { id: 4, short: 'Jue', long: 'Jueves' },
    { id: 5, short: 'Vie', long: 'Viernes' }, { id: 6, short: 'Sáb', long: 'Sábado' },
    { id: 7, short: 'Dom', long: 'Domingo' },
  ];
  HM.ROLES = { admin: 'Administrador', supervisor: 'Supervisor', repartidor: 'Repartidor' };
  HM.COOLER_STATUS = ['Bueno', 'Regular', 'Dañado', 'En reparación', 'Retirado'];
  HM.COOLER_OWNER = ['Empresa', 'Cliente', 'Comodato'];
  HM.ROUTE_COLORS = ['#0b5ed7', '#1a9a3c', '#f08a00', '#8a3ffc', '#d6336c', '#0ca5a5', '#7a5c00', '#495057'];

  HM.esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  HM.uid = () => Date.now().toString(36) + Math.random().toString(36).slice(2, 9);
  HM.norm = (s) => String(s == null ? '' : s).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  HM.digits = (s) => String(s == null ? '' : s).replace(/\D/g, '');
  HM.phoneOk = (s) => HM.digits(s).length === 10;
  HM.fmtPhone = (s) => { const d = HM.digits(s); return d.length === 10 ? `${d.slice(0, 2)} ${d.slice(2, 6)} ${d.slice(6)}` : s || ''; };
  HM.dayName = (id) => (HM.DAYS[id - 1] || {}).long || '';
  HM.dayShort = (id) => (HM.DAYS[id - 1] || {}).short || '';
  HM.gmapsGo = (c) => `https://www.google.com/maps/dir/?api=1&destination=${c.lat},${c.lng}`;
  HM.gmapsView = (c) => `https://www.google.com/maps/search/?api=1&query=${c.lat},${c.lng}`;

  HM.debounce = (fn, ms = 200) => {
    let t;
    return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
  };

  /* Distancia en km entre dos coordenadas */
  HM.haversine = (a, b) => {
    const R = 6371, r = Math.PI / 180;
    const dLat = (b.lat - a.lat) * r, dLng = (b.lng - a.lng) * r;
    const h = Math.sin(dLat / 2) ** 2 + Math.cos(a.lat * r) * Math.cos(b.lat * r) * Math.sin(dLng / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(h));
  };

  /* Avisos temporales */
  HM.toast = (msg, type = 'ok') => {
    let box = document.getElementById('toasts');
    if (!box) { box = document.createElement('div'); box.id = 'toasts'; document.body.appendChild(box); }
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.setAttribute('role', 'status');
    t.textContent = msg;
    box.appendChild(t);
    setTimeout(() => { t.classList.add('out'); setTimeout(() => t.remove(), 300); }, 3200);
  };

  /* Ventana modal. "content" puede ser texto HTML o un nodo. Devuelve { close, el } */
  HM.openModal = (title, content, opts = {}) => {
    const prevFocus = document.activeElement;
    const ov = document.createElement('div');
    ov.className = 'modal-ov';
    ov.innerHTML = `<div class="modal ${opts.wide ? 'wide' : ''}" role="dialog" aria-modal="true" aria-label="${HM.esc(title)}">
      <div class="modal-head"><h3>${HM.esc(title)}</h3><button class="icon-btn sm" data-close aria-label="Cerrar">✕</button></div>
      <div class="modal-body"></div></div>`;
    const body = ov.querySelector('.modal-body');
    if (typeof content === 'string') body.innerHTML = content; else body.appendChild(content);
    document.body.appendChild(ov);
    let closed = false;
    const close = () => {
      if (closed) return; closed = true;
      document.removeEventListener('keydown', onKey);
      ov.remove();
      if (opts.onClose) opts.onClose();
      if (prevFocus && prevFocus.focus) prevFocus.focus();
    };
    const onKey = (e) => { if (e.key === 'Escape') close(); };
    document.addEventListener('keydown', onKey);
    ov.addEventListener('mousedown', (e) => { if (e.target === ov) close(); });
    ov.querySelector('[data-close]').onclick = close;
    setTimeout(() => { const f = body.querySelector('input,select,textarea,button'); if (f) f.focus(); }, 30);
    return { close, el: ov, body };
  };

  HM.confirm = (msg, { okText = 'Aceptar', danger = false } = {}) =>
    new Promise((resolve) => {
      const node = document.createElement('div');
      node.innerHTML = `<p class="confirm-msg">${HM.esc(msg)}</p>
        <div class="form-actions"><button class="btn btn-ghost" data-no>Cancelar</button>
        <button class="btn ${danger ? 'btn-danger' : 'btn-primary'}" data-yes>${HM.esc(okText)}</button></div>`;
      let answer = false;
      const m = HM.openModal('Confirmar', node, { onClose: () => resolve(answer) });
      node.querySelector('[data-no]').onclick = () => m.close();
      node.querySelector('[data-yes]').onclick = () => { answer = true; m.close(); };
    });

  /* CSV compatible con Excel (BOM + comillas) */
  HM.toCsv = (rows) => '\ufeff' + rows.map((r) => r.map((v) => {
    const s = v == null ? '' : String(v);
    return /[",\n;]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
  }).join(',')).join('\r\n');

  HM.download = (filename, text, mime = 'text/csv;charset=utf-8') => {
    const url = URL.createObjectURL(new Blob([text], { type: mime }));
    const a = document.createElement('a');
    a.href = url; a.download = filename; document.body.appendChild(a); a.click(); a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  };

  /* Pagina un arreglo */
  HM.paginate = (arr, page, size) => {
    const pages = Math.max(1, Math.ceil(arr.length / size));
    const p = Math.min(Math.max(1, page), pages);
    return { items: arr.slice((p - 1) * size, p * size), page: p, pages, total: arr.length };
  };

  HM.pagerHtml = (p) => p.pages <= 1 ? '' :
    `<div class="pager"><button class="btn btn-ghost sm" data-page="${p.page - 1}" ${p.page <= 1 ? 'disabled' : ''}>‹ Anterior</button>
     <span>Página ${p.page} de ${p.pages} · ${p.total} resultados</span>
     <button class="btn btn-ghost sm" data-page="${p.page + 1}" ${p.page >= p.pages ? 'disabled' : ''}>Siguiente ›</button></div>`;

  HM.views = HM.views || {};
})();