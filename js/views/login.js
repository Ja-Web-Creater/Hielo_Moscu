(function () {
  'use strict';
  const HM = window.HM;

  HM.views.login = {
    mount(root, { onSuccess }) {
      document.title = 'Iniciar sesión · Hielo Moscú';
      root.innerHTML = `
        <div class="login-wrap">
          <form class="login-card" id="loginForm" novalidate>
            <div class="login-brand"><span class="brand-snow big" aria-hidden="true">❄</span>
              <div class="brand-text"><small>HIELO</small><strong>MOSCÚ</strong></div></div>
            <h1>Iniciar sesión</h1>
            <p class="muted">Sistema de rutas y clientes</p>
            <label class="field"><span>Usuario</span>
              <input name="u" autocomplete="username" autocapitalize="none" spellcheck="false" required></label>
            <label class="field"><span>Contraseña</span>
              <div class="pass-row"><input name="p" type="password" autocomplete="current-password" required>
              <button type="button" class="icon-btn sm" id="togglePass" aria-label="Mostrar contraseña">👁</button></div></label>
            <label class="check"><input type="checkbox" name="r"> Mantener sesión en este equipo</label>
            <div class="form-error" id="loginErr" role="alert"></div>
            <button class="btn btn-primary block" id="loginBtn">Entrar</button>
            <details class="demo-hint"><summary>Cuentas de prueba (solo prototipo)</summary>
              <table><tr><td><b>admin</b></td><td>admin123</td><td>Administrador</td></tr>
              <tr><td><b>supervisor</b></td><td>super123</td><td>Supervisor</td></tr>
              <tr><td><b>reparto1</b></td><td>reparto123</td><td>Repartidor</td></tr></table>
            </details>
          </form>
        </div>`;
      const f = root.querySelector('#loginForm'), err = root.querySelector('#loginErr'), btn = root.querySelector('#loginBtn');
      f.u.focus();
      root.querySelector('#togglePass').onclick = () => { f.p.type = f.p.type === 'password' ? 'text' : 'password'; };
      f.onsubmit = async (e) => {
        e.preventDefault();
        err.textContent = '';
        if (!f.u.value.trim() || !f.p.value) { err.textContent = 'Escribe tu usuario y contraseña.'; return; }
        btn.disabled = true; btn.textContent = 'Entrando…';
        const r = await HM.Auth.login(f.u.value, f.p.value, f.r.checked);
        if (r.ok) { onSuccess(); return; }
        err.textContent = r.error; btn.disabled = false; btn.textContent = 'Entrar'; f.p.value = ''; f.p.focus();
      };
    },
  };
})();