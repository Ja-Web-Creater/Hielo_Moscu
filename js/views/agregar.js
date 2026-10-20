(function () {
  'use strict';
  const HM = window.HM;

  HM.views.agregar = {
    mount(root, ctx) {
      ctx.setTitle('Agregar cliente', 'Alta de cliente, enfriador y ruta');
      root.innerHTML = `<div class="page narrow">
        <div class="page-head"><div><h2>Nuevo cliente</h2><p class="muted">Los campos con <i class="req">*</i> son obligatorios.</p></div></div>
        <div class="card" id="host"></div></div>`;
      const form = HM.CustomerForm.mount(root.querySelector('#host'), {
        onSaved: () => { location.hash = '#/clientes'; },
        onCancel: () => { location.hash = '#/clientes'; },
      });
      return () => form.destroy();
    },
  };
})();