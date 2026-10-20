/* Autenticación y permisos (PROTOTIPO LOCAL).
   Las contraseñas se guardan con PBKDF2 + sal, pero como todo vive en el navegador,
   esto NO es seguridad real. Al conectar MySQL, el login y los permisos deben
   validarse en el servidor (sesión/JWT) y esta capa solo llamará a la API. */
(function () {
  'use strict';
  const HM = window.HM;
  const SKEY = 'hm.v1.session', AKEY = 'hm.v1.attempts';
  const TTL = 8 * 3600 * 1000, MAX_TRIES = 5, LOCK_MS = 60 * 1000;
  const enc = new TextEncoder();
  const hex = (b) => Array.from(new Uint8Array(b)).map((x) => x.toString(16).padStart(2, '0')).join('');

  const PERMS = {
    admin: ['*'],
    supervisor: ['customers.write', 'routes.write', 'reports.view'],
    repartidor: [],
  };

  async function hash(pass, salt) {
    if (window.crypto && crypto.subtle) {
      const key = await crypto.subtle.importKey('raw', enc.encode(pass), 'PBKDF2', false, ['deriveBits']);
      const bits = await crypto.subtle.deriveBits({ name: 'PBKDF2', salt: enc.encode(salt), iterations: 100000, hash: 'SHA-256' }, key, 256);
      return 'p1$' + hex(bits);
    }
    let h = 5381; const s = salt + pass;
    for (let i = 0; i < s.length; i++) h = ((h << 5) + h + s.charCodeAt(i)) | 0;
    return 'w1$' + (h >>> 0).toString(16);
  }

  function readAttempts() {
    try { return JSON.parse(localStorage.getItem(AKEY)) || {}; } catch (_) { return {}; }
  }
  function writeAttempts(o) { try { localStorage.setItem(AKEY, JSON.stringify(o)); } catch (_) { /* nada */ } }

  function readSession() {
    for (const st of [sessionStorage, localStorage]) {
      try {
        const s = JSON.parse(st.getItem(SKEY));
        if (s && s.exp > Date.now()) return s;
      } catch (_) { /* nada */ }
    }
    return null;
  }

  const Auth = {
    hash,
    async createUser({ username, name, role, password }) {
      username = String(username || '').trim().toLowerCase();
      if (!/^[a-z0-9._-]{3,30}$/.test(username)) throw new Error('El usuario debe tener 3 a 30 caracteres (letras, números, punto, guion).');
      if (!name || !String(name).trim()) throw new Error('El nombre es obligatorio.');
      if (!HM.ROLES[role]) throw new Error('Rol inválido.');
      if (!password || password.length < 6) throw new Error('La contraseña debe tener al menos 6 caracteres.');
      if (HM.Store.peekAll('users').some((u) => u.username === username)) throw new Error('Ese usuario ya existe.');
      const salt = HM.uid() + HM.uid();
      return HM.Store.insert('users', { username, name: String(name).trim(), role, salt, passHash: await hash(password, salt), active: true });
    },

    async setPassword(userId, password) {
      if (!password || password.length < 6) throw new Error('La contraseña debe tener al menos 6 caracteres.');
      const salt = HM.uid() + HM.uid();
      await HM.Store.update('users', userId, { salt, passHash: await hash(password, salt) });
    },

    async changeOwnPassword(oldPass, newPass) {
      const s = Auth.current(); if (!s) throw new Error('Sesión expirada.');
      const u = HM.Store.peek('users', s.uid);
      if (!u || (await hash(oldPass, u.salt)) !== u.passHash) throw new Error('La contraseña actual no es correcta.');
      await Auth.setPassword(u.id, newPass);
    },

    async login(username, password, remember) {
      username = String(username || '').trim().toLowerCase();
      const att = readAttempts(), rec = att[username] || { n: 0, until: 0 };
      if (rec.until > Date.now()) {
        return { ok: false, error: `Demasiados intentos. Espera ${Math.ceil((rec.until - Date.now()) / 1000)} s.` };
      }
      const u = HM.Store.peekAll('users').find((x) => x.username === username);
      const good = u && u.active !== false && (await hash(password, u.salt)) === u.passHash;
      if (!good) {
        rec.n += 1;
        if (rec.n >= MAX_TRIES) { rec.n = 0; rec.until = Date.now() + LOCK_MS; }
        att[username] = rec; writeAttempts(att);
        return { ok: false, error: 'Usuario o contraseña incorrectos.' };
      }
      delete att[username]; writeAttempts(att);
      const s = { uid: u.id, username: u.username, name: u.name, role: u.role, exp: Date.now() + TTL };
      const [on, off] = remember ? [localStorage, sessionStorage] : [sessionStorage, localStorage];
      try { off.removeItem(SKEY); on.setItem(SKEY, JSON.stringify(s)); } catch (_) { /* nada */ }
      return { ok: true, session: s };
    },

    logout() {
      try { sessionStorage.removeItem(SKEY); localStorage.removeItem(SKEY); } catch (_) { /* nada */ }
    },

    /* Sesión vigente o null. Revisa que el usuario siga existiendo y activo. */
    current() {
      const s = readSession(); if (!s) return null;
      const u = HM.Store.peek('users', s.uid);
      if (!u || u.active === false) { Auth.logout(); return null; }
      s.role = u.role; s.name = u.name;
      return s;
    },

    can(perm) {
      const s = Auth.current(); if (!s) return false;
      const p = PERMS[s.role] || [];
      return p.includes('*') || p.includes(perm);
    },
  };

  HM.Auth = Auth;
})();