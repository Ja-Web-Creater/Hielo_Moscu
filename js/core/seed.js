/* Datos iniciales de DEMOSTRACIÓN (usuarios de prueba y clientes inventados). */
(function () {
  'use strict';
  const HM = window.HM;

  const CORRIDORS = {
    oeste: [[19.655, -99.075], [19.625, -99.07], [19.595, -99.06], [19.565, -99.045], [19.545, -99.01], [19.52, -98.985], [19.51, -99.0], [19.53, -99.03], [19.56, -99.05]],
    este: [[19.66, -98.9], [19.64, -98.87], [19.62, -98.865], [19.60, -98.9], [19.58, -98.915], [19.55, -98.89], [19.52, -98.88], [19.50, -98.86], [19.575, -98.88]],
    norte: [[19.70, -99.02], [19.68, -98.99], [19.665, -98.95], [19.65, -98.92], [19.635, -98.96], [19.64, -99.0], [19.66, -99.03]],
    sur: [[19.50, -99.04], [19.48, -99.0], [19.47, -98.96], [19.485, -98.92], [19.50, -98.9], [19.515, -98.94], [19.52, -98.99]],
  };
  /* día -> [índice de ruta, corredor, cantidad de clientes] */
  const SCHEDULE = {
    1: [[0, 'oeste', 36], [1, 'este', 27]],
    2: [[0, 'norte', 24], [1, 'sur', 20]],
    3: [[0, 'oeste', 30], [1, 'este', 22], [2, 'norte', 18]],
    4: [[0, 'sur', 26], [1, 'este', 28], [2, 'norte', 15]],
    5: [[0, 'oeste', 32], [1, 'sur', 21], [2, 'este', 25]],
    6: [[0, 'norte', 22], [1, 'oeste', 28], [2, 'sur', 19]],
    7: [[1, 'este', 14]],
  };
  const NAMES = ['Abarrotes vinos y licores San Marcos', 'Abarrotes vinos y licores casa rojas', 'Abarrotes Dan Don',
    'Abarrotes vinos y licores Yeyo', 'Abarrotes y cremería Itzel', 'Mini super el ruso', 'Ultramarinos San Ángel', 'Monky',
    'Benavira 1', 'Abarrotes y Cremería "Santiaguito"', 'Super Kapa', 'Tienda La Esperanza', 'Miscelánea Don Chuy',
    'Abarrotes Lupita', 'Cremería El Establo', 'Vinos y licores La Bodeguita', 'Abarrotes Los Pinos', 'Super Mercado Nuevo',
    'Tienda Doña Mary', 'Abarrotes El Güero', 'Miscelánea Fer', 'Paletería La Michoacana', 'Abarrotes Santa Cruz',
    'Depósito Las Torres', 'Tienda El Rincón', 'Abarrotes Don Beto', 'Mini Super Karla', 'Abarrotes y Semillas Juárez',
    'Vinos y licores El Ángel', 'Abarrotes La Palma', 'Tienda Los Primos', 'Cremería San José', 'Abarrotes Vero',
    'Super Tienda Mendoza', 'Miscelánea La Joya', 'Abarrotes El Sol', 'Depósito Hermanos Cruz', 'Tienda Sandra'];
  const COLONIAS = ['Centro', 'San Agustín', 'Jardines de Morelos', 'Santa Clara', 'Las Américas', 'Granjas Independencia', 'La Esmeralda', 'Ojo de Agua', 'San Cristóbal', 'Los Reyes'];
  const BRANDS = ['Imbera', 'Metalfrio', 'Torrey', 'Fricon', 'Vitrofrigo'];
  const STATUS_W = ['Bueno', 'Bueno', 'Bueno', 'Regular', 'Regular', 'Dañado', 'En reparación'];

  const rng = (seed) => { let s = seed; return () => (s = (s * 16807) % 2147483647) / 2147483647; };

  function pointsAlong(path, n, rand) {
    const segs = [];
    const total = path.slice(1).reduce((acc, p, i) => { const d = Math.hypot(p[0] - path[i][0], p[1] - path[i][1]); segs.push(d); return acc + d; }, 0);
    const out = [];
    for (let k = 0; k < n; k++) {
      let dist = (total * k) / (n - 1 || 1), i = 0;
      while (i < segs.length - 1 && dist > segs[i]) { dist -= segs[i]; i++; }
      const t = Math.min(dist / segs[i], 1);
      out.push([
        +(path[i][0] + (path[i + 1][0] - path[i][0]) * t + (rand() - 0.5) * 0.006).toFixed(6),
        +(path[i][1] + (path[i + 1][1] - path[i][1]) * t + (rand() - 0.5) * 0.006).toFixed(6),
      ]);
    }
    return out;
  }

  async function seedUsers() {
    const A = HM.Auth;
    await A.createUser({ username: 'admin', name: 'Administrador', role: 'admin', password: 'admin123' });
    await A.createUser({ username: 'supervisor', name: 'Supervisor de Rutas', role: 'supervisor', password: 'super123' });
    await A.createUser({ username: 'reparto1', name: 'Repartidor 1', role: 'repartidor', password: 'reparto123' });
    await A.createUser({ username: 'reparto2', name: 'Repartidor 2', role: 'repartidor', password: 'reparto123' });
  }

  async function seedBusiness() {
    const S = HM.Store;
    const drivers = S.peekAll('users').filter((u) => u.role === 'repartidor');
    const routes = await S.insertMany('routes', [0, 1, 2].map((i) => ({
      name: 'Ruta ' + (i + 1), color: HM.ROUTE_COLORS[i], driverId: (drivers[i] || drivers[0] || {}).id || '', active: true,
    })));
    const customers = [], stopsSpec = [];
    let seq = 0;
    for (let day = 1; day <= 7; day++) {
      SCHEDULE[day].forEach(([ri, corridor, count], k) => {
        const rand = rng(day * 100 + k * 7 + 3);
        pointsAlong(CORRIDORS[corridor], count, rand).forEach((p, i) => {
          const hasCooler = rand() < 0.6;
          const name = NAMES[(i + k * 11 + day * 5) % NAMES.length];
          const cid = 'c' + (++seq);
          customers.push({
            id: cid, name, contact: '', phone: '55' + String(10000000 + Math.floor(rand() * 89999999)),
            address: `Calle ${1 + Math.floor(rand() * 60)} #${1 + Math.floor(rand() * 400)}, Col. ${COLONIAS[Math.floor(rand() * COLONIAS.length)]}`,
            lat: p[0], lng: p[1], notes: '', active: true,
            cooler: hasCooler ? {
              has: true, brand: BRANDS[Math.floor(rand() * BRANDS.length)], model: 'MX-' + (100 + Math.floor(rand() * 400)),
              capacity: [10, 12, 16, 20][Math.floor(rand() * 4)], unit: 'pies³', serial: 'SN' + (100000 + Math.floor(rand() * 899999)),
              status: STATUS_W[Math.floor(rand() * STATUS_W.length)], owner: HM.COOLER_OWNER[Math.floor(rand() * 3)], deliveredAt: '', notes: '',
            } : { has: false },
          });
          stopsSpec.push({ routeId: routes[ri].id, day, customerId: cid, order: i + 1 });
        });
      });
    }
    await S.insertMany('customers', customers);
    await S.insertMany('stops', stopsSpec);
  }

  HM.Seed = {
    async run() {
      if (!HM.Store.peekAll('users').length) await seedUsers();
      if (!localStorage.getItem('hm.v1.seeded')) {
        await seedBusiness();
        try { localStorage.setItem('hm.v1.seeded', '1'); } catch (_) { /* nada */ }
      }
    },
    async resetDemo() {
      await HM.Store.clearBusinessData();
      await seedBusiness();
      HM.Store.flush();
    },
  };
})();