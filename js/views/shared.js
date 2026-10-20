/* Piezas reutilizables entre pantallas: fila de cliente y modal de ficha */
(function () {
  'use strict';
  const HM = window.HM, S = HM.Store;

  const STATUS_CLASS = { 'Bueno': 'ok', 'Regular': 'warn', 'Dañado': 'bad', 'En reparación': 'bad', 'Retirado': 'none' };

  HM.coolerBadge = (c) => {
    const k = c.cooler;
    if (!k || !k.has) return '<span class="badge none">Sin enfriador</span>';
    return `<span class="badge ${STATUS_CLASS[k.status] || 'none'}" title="Serie ${HM.esc(k.serial)}">❄ ${HM.esc(k.brand)} · ${HM.esc(k.status)}</span>`;
  };

  HM.assignChips = (c) => {
    const st = S.stopsOfCustomer(c.id).sort((a, b) => a.day - b.day);
    if (!st.length) return '<span class="muted sm">Sin ruta</span>';
    return st.map((s) => {
      const r = S.peek('routes', s.routeId);
      return `<span class="chip sm" style="--c:${r ? r.color : '#888'}">${HM.esc(r ? r.name : '?')} · ${HM.dayShort(s.day)} · #${s.order}</span>`;
    }).join('');
  };

  /* opts: { edit, del } */
  HM.customerRow = (c, opts = {}) => `
    <div class="lrow ${c.active === false ? 'inactive' : ''}" data-id="${c.id}">
      <div class="lcell main"><b>${HM.esc(c.name)}${c.active === false ? ' <em>(inactivo)</em>' : ''}</b><small>${HM.esc(c.address)}</small></div>
      <div class="lcell"><a href="tel:${HM.digits(c.phone)}">${HM.esc(HM.fmtPhone(c.phone))}</a></div>
      <div class="lcell">${HM.coolerBadge(c)}</div>
      <div class="lcell chips">${HM.assignChips(c)}</div>
      <div class="lcell acts">
        <button class="icon-btn sm" data-act="map" title="Ver en el mapa" aria-label="Ver en el mapa">📍</button>
        <a class="icon-btn sm" href="${HM.gmapsGo(c)}" target="_blank" rel="noopener" title="Cómo llegar" aria-label="Cómo llegar">➤</a>
        <button class="icon-btn sm" data-act="edit" title="${opts.edit ? 'Editar' : 'Ver ficha'}" aria-label="Ver ficha">${opts.edit ? '✏️' : '📄'}</button>
        ${opts.del ? '<button class="icon-btn sm danger" data-act="del" title="Eliminar" aria-label="Eliminar">🗑</button>' : ''}
      </div>
    </div>`;

  HM.customerHeader = '<div class="lhead"><div>Cliente</div><div>Teléfono</div><div>Enfriador</div><div>Ruta · día · orden</div><div></div></div>';

  /* Abre la ficha del cliente (editable si el usuario tiene permiso) */
  HM.openCustomer = (customer, onChange) => {
    const node = document.createElement('div');
    let modal;
    const form = HM.CustomerForm.mount(node, {
      customer,
      onSaved: () => { modal.close(); if (onChange) onChange(); },
      onCancel: () => modal.close(),
    });
    modal = HM.openModal(customer.name, node, { wide: true, onClose: () => form.destroy() });
    return modal;
  };
})();