import http from 'k6/http';
import { check, sleep, group } from 'k6';

// Configuración de la prueba de carga:
// Simulamos un incremento gradual de usuarios (repartidores y oficina navegando al mismo tiempo)
export const options = {
    stages: [
        { duration: '10s', target: 10 }, // Sube a 10 usuarios virtuales en 10 segundos (Calentamiento)
        { duration: '20s', target: 25 }, // Se mantiene en 25 usuarios concurrentes durante 20 segundos (Tráfico normal)
        { duration: '10s', target: 0 },  // Desciende a 0 usuarios (Cierre)
    ],
    thresholds: {
        http_req_duration: ['p(95)<500'], // El 95% de las peticiones deben responder en menos de 500ms
        http_req_failed: ['rate<0.01'],    // Menos del 1% de errores permitidos
    },
};

export default function () {
    // 1. Probar la API que alimenta el mapa (index.php)
    group('API Obtener Clientes', function () {
        let res = http.get('http://localhost/hielo_moscu/api/obtener_clientes.php');
        check(res, {
            'status es 200': (r) => r.status === 200,
            'respuesta rápida (<300ms)': (r) => r.timings.duration < 300,
        });
    });

    sleep(1);

    // 2. Probar el buscador de clientes
    group('Buscador de Clientes', function () {
        let res = http.get('http://localhost/hielo_moscu/buscar.php?q=Abarrotes');
        check(res, {
            'buscador responde 200': (r) => r.status === 200,
        });
    });

    sleep(1);

    // 3. Probar la vista del Repartidor (Operador 1)
    group('Vista Repartidor', function () {
        let res = http.get('http://localhost/hielo_moscu/repartidor_ruta.php?id_repartidor=1&dia=Martes');
        check(res, {
            'repartidor responde 200': (r) => r.status === 200,
        });
    });

    sleep(2); // Pausa antes de la siguiente iteración del usuario simulado
}