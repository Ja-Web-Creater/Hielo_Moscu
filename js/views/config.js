(function () {
  'use strict';
  const HM = window.HM, S = HM.Store, esc = HM.esc;

  HM.views.config = {
    mount(root, ctx) {
      ctx.setTitle('Configuración', 'Tu cuenta, usuarios y respaldos');
      const isAdmin = HM.Auth.can('users.manage');
      const me = ctx.user;

      root.innerHTML = `<div class="page narrow">
        <div class="page-head"><div><h2>Configuración</h2></div></div>
        <section class="card"><h3>Mi cuenta</h3>
          <p class="muted">${esc(me.name)} · ${esc(HM.ROLES[me.role])} · usuario <b>${esc(me.username)}</b></p>
          <form id="pw" class="cform inline-form" novalidate>
            <label class="field"><span>Contraseña actual</span><input type="password" name="o" autocomplete="current-password"></label>
            <label class="field"><span>Nueva contraseña (mín. 6)</span><input type="password" name="n" autocomplete="new-password"></label>
            <button class="btn btn-primary">Cambiar contraseña</button>
            <div class="form-error" id="pwErr" role="alert"></div></form></section>
        ${isAdmin ? `
        <section class="card"><div class="card-head"><h3>Usuarios</h3><button class="btn btn-primary sm" id="newUser">+ Nuevo usuario</button></div>
          <div id="users" class="user-table"></div></section>
        <section class="card"><h3>Respaldo de datos</h3>
          <p class="muted">Hoy los datos viven en este navegador. Descarga un respaldo con frecuencia.</p>
          <div class="download-row"><button class="btn btn-ghost" id="exp">⬇ Descargar respaldo (JSON)</button>
            <label class="btn btn-ghost" for="imp">⬆ Restaurar respaldo</label><input type="file" id="imp" accept="application/json,.json" hidden></div></section>
        <section class="card danger-zone"><h3>Zona de datos de prueba</h3>
          <p class="muted">Borra clientes, rutas y paradas y vuelve a cargar los datos de demostración. Los usuarios no se tocan.</p>
          <button class="btn btn-danger" id="reset">Restablecer datos de demostración</button>
          <button class="btn btn-ghost" id="wipe">Vaciar clientes, rutas y paradas</button></section>` : ''}
      </div>`;
      const $ = (s) => root.querySelector(s);

      $('#pw').onsubmit = async (e) => {
        e.preventDefault();
        const f = e.target, err = $('#pwErr'); err.textContent = '';
        try { await HM.Auth.changeOwnPassword(f.o.value, f.n.value); f.reset(); HM.toast('Contraseña actualizada.'); }
        catch (ex) { err.textContent = ex.message; }
      };
      if (!isAdmin) return null;

      function renderUsers() {
        $('#users').innerHTML = S.peekAll('users').map((u) => `<div class="urow ${u.active === false ? 'inactive' : ''}" data-id="${u.id}">
          <div><b>${esc(u.name)}</b><small>${esc(u.username)}</small></div><span class="badge none">${esc(HM.ROLES[u.role])}</span>
          <span>${u.active === false ? 'Inactivo' : 'Activo'}</span>
          <div class="acts"><button class="btn btn-ghost sm" data-act="edit">Editar</button><button class="btn btn-ghost sm" data-act="pass">Contraseña</button></div></div>`).join('');
      }

      function userForm(u) {
        const node = document.createElement('div');
        node.innerHTML = `<form class="cform" novalidate>
          <label class="field"><span>Nombre completo <i class="req">*</i></span><input name="name" value="${esc(u ? u.name : '')}" maxlength="60"></label>
          <label class="field"><span>Usuario <i class="req">*</i></span><input name="username" value="${esc(u ? u.username : '')}" ${u ? 'disabled' : ''} maxlength="30" autocapitalize="none"></label>
          <label class="field"><span>Rol</span><select name="role">${Object.entries(HM.ROLES).map(([k, v]) => `<option value="${k}" ${u && u.role === k ? 'selected' : ''}>${v}</option>`).join('')}</select></label>
          ${u ? `<label class="check"><input type="checkbox" name="active" ${u.active !== false ? 'checked' : ''} ${u.id === me.uid ? 'disabled' : ''}> Usuario activo</label>`
            : '<label class="field"><span>Contraseña inicial (mín. 6) <i class="req">*</i></span><input name="password" type="text" autocomplete="off"></label>'}
          <p class="hint">Administrador: todo · Supervisor: clientes, rutas y reportes · Repartidor: solo consulta.</p>
          <div class="form-error" role="alert"></div>
          <div class="form-actions"><button type="button" class="btn btn-ghost" data-no>Cancelar</button><button class="btn btn-primary">Guardar</button></div></form>`;
        const m = HM.openModal(u ? 'Editar usuario' : 'Nuevo usuario', node);
        const f = node.querySelector('form'), err = node.querySelector('.form-error');
        node.querySelector('[data-no]').onclick = m.close;
        f.onsubmit = async (e) => {
          e.preventDefault(); err.textContent = '';
          try {
            if (u) {
              if (u.id === me.uid && f.role.value !== 'admin') throw new Error('No puedes quitarte el rol de administrador a ti mismo.');
              if (!f.name.value.trim()) throw new Error('El nombre es obligatorio.');
              const patch = { name: f.name.value.trim(), role: f.role.value };
              if (u.id !== me.uid) patch.active = f.active.checked;
              const admins = S.peekAll('users').filter((x) => x.role === 'admin' && x.active !== false && x.id !== u.id).length;
              if (!admins && (patch.role !== 'admin' || patch.active === false)) throw new Error('Debe quedar al menos un administrador activo.');
              await S.update('users', u.id, patch);
            } else {
              await HM.Auth.createUser({ username: f.username.value, name: f.name.value, role: f.role.value, password: f.password.value });
            }
            HM.toast('Usuario guardado.'); m.close(); renderUsers();
          } catch (ex) { err.textContent = ex.message; }
        };
      }

      function passForm(u) {
        const node = document.createElement('div');
        node.innerHTML = `<form class="cform" novalidate><p class="muted">Nueva contraseña para <b>${esc(u.name)}</b>.</p>
          <label class="field"><span>Nueva contraseña (mín. 6)</span><input name="p" type="text" autocomplete="off"></label>
          <div class="form-error" role="alert"></div>
          <div class="form-actions"><button type="button" class="btn btn-ghost" data-no>Cancelar</button><button class="btn btn-primary">Guardar</button></div></form>`;
        const m = HM.openModal('Restablecer contraseña', node);
        node.querySelector('[data-no]').onclick = m.close;
        node.querySelector('form').onsubmit = async (e) => {
          e.preventDefault();
          try { await HM.Auth.setPassword(u.id, e.target.p.value); HM.toast('Contraseña restablecida.'); m.close(); }
          catch (ex) { node.querySelector('.form-error').textContent = ex.message; }
        };
      }

      $('#newUser').onclick = () => userForm(null);
      $('#users').onclick = (e) => {
        const b = e.target.closest('[data-act]'); if (!b) return;
        const u = S.peek('users', b.closest('.urow').dataset.id); if (!u) return;
        if (b.dataset.act === 'edit') userForm(u); else passForm(u);
      };
      $('#exp').onclick = () => {
        const o = S.exportAll();
        HM.download(`respaldo-hielo-moscu-${new Date().toISOString().slice(0, 10)}.json`, JSON.stringify(o), 'application/json');
      };
      $('#imp').onchange = async (e) => {
        const file = e.target.files[0]; if (!file) return;
        try {
          const obj = JSON.parse(await file.text());
          if (!(await HM.confirm('Se reemplazarán TODOS los datos actuales por los del respaldo. ¿Continuar?', { okText: 'Restaurar', danger: true }))) return;
          await S.importAll(obj); S.flush(); HM.toast('Respaldo restaurado.'); location.reload();
        } catch (ex) { HM.toast(ex.message || 'No se pudo leer el archivo.', 'error'); }
        e.target.value = '';
      };
      $('#reset').onclick = async () => {
        if (await HM.confirm('Se borrarán los clientes y rutas actuales y se cargarán los datos de demostración.', { okText: 'Restablecer', danger: true })) {
          await HM.Seed.resetDemo(); HM.toast('Datos de demostración cargados.');
        }
      };
      $('#wipe').onclick = async () => {
        if (await HM.confirm('Se borrarán TODOS los clientes, rutas y paradas. Úsalo antes de capturar los datos reales.', { okText: 'Vaciar todo', danger: true })) {
          await S.clearBusinessData(); S.flush(); HM.toast('Datos vaciados.');
        }
      };
      renderUsers();
      return null;
    },
  };
})();