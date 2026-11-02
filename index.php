<?php
$conn = new mysqli("localhost", "root", "", "hielo_moscu");
if ($conn->connect_error) { 
    die("Error de conexión: " . $conn->connect_error); 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hielo Moscú - Mapa Principal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-slate-100 font-sans pb-24">
    <div class="max-w-md mx-auto min-h-screen bg-white shadow-xl flex flex-col justify-between relative">
        <div class="p-4 space-y-3">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-10 h-10 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-md font-bold text-lg">🧊</div>
                    <div>
                        <h1 class="text-base font-black text-blue-950">HIELO MOSCÚ</h1>
                        <p id="info-ruta" class="text-[11px] font-bold text-sky-600">Mapa General</p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5">
                    <span id="badge-total" class="text-[11px] font-bold bg-sky-50 text-sky-600 px-3 py-1 rounded-xl border border-sky-100">0 Entregas</span>
                    <a href="admin_rutas.php" class="bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold px-2.5 py-1.5 rounded-xl shadow-sm transition flex items-center gap-1" title="Panel de Oficina">
                        <i class="fa-solid fa-lock text-[10px]"></i>
                    </a>
                </div>
            </div>

            <!-- Filtros de días -->
            <div class="flex space-x-1.5 overflow-x-auto pb-1 no-scrollbar">
                <button onclick="filtrarDia('todos')" class="filter-btn px-3 py-1 rounded-xl text-xs font-bold bg-sky-600 text-white shrink-0">Todos</button>
                <button onclick="filtrarDia('Lunes')" class="filter-btn px-3 py-1 rounded-xl text-xs font-bold bg-white text-slate-600 border border-slate-200 shrink-0">Lunes</button>
                <button onclick="filtrarDia('Martes')" class="filter-btn px-3 py-1 rounded-xl text-xs font-bold bg-white text-slate-600 border border-slate-200 shrink-0">Martes</button>
                <button onclick="filtrarDia('Miércoles')" class="filter-btn px-3 py-1 rounded-xl text-xs font-bold bg-white text-slate-600 border border-slate-200 shrink-0">Miércoles</button>
                <button onclick="filtrarDia('Jueves')" class="filter-btn px-3 py-1 rounded-xl text-xs font-bold bg-white text-slate-600 border border-slate-200 shrink-0">Jueves</button>
                <button onclick="filtrarDia('Viernes')" class="filter-btn px-3 py-1 rounded-xl text-xs font-bold bg-white text-slate-600 border border-slate-200 shrink-0">Viernes</button>
                <button onclick="filtrarDia('Sábado')" class="filter-btn px-3 py-1 rounded-xl text-xs font-bold bg-white text-slate-600 border border-slate-200 shrink-0">Sábado</button>
                <button onclick="filtrarDia('Domingo')" class="filter-btn px-3 py-1 rounded-xl text-xs font-bold bg-white text-slate-600 border border-slate-200 shrink-0">Domingo</button>
            </div>

            <!-- Mapa -->
            <div class="w-full h-80 rounded-2xl overflow-hidden border border-slate-200 shadow-sm relative">
                <div id="map" class="w-full h-full"></div>
            </div>

            <!-- Lista de Paradas -->
            <div id="lista-paradas" class="space-y-2 max-h-60 overflow-y-auto pr-1">
                <div class="text-center py-6 text-slate-400 text-xs">Cargando puntos desde MySQL...</div>
            </div>
        </div>

        <!-- Menú de Navegación Inferior -->
        <nav class="fixed bottom-0 left-0 right-0 max-w-md mx-auto bg-white/95 backdrop-blur-md border-t border-slate-200 py-2 px-3 z-30 shadow-lg">
            <div class="flex justify-around items-center text-center">
                <a href="index.php" class="flex flex-col items-center text-blue-600 font-bold transition">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-blue-50 text-blue-600 shadow-xs">
                        <i class="fa-solid fa-house text-sm"></i>
                    </div>
                    <span class="text-[10px] font-bold mt-0.5">Inicio</span>
                </a>
                <a href="clientes.php" class="flex flex-col items-center text-slate-500 hover:text-blue-600 transition">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-slate-100 text-slate-700 shadow-xs">
                        <i class="fa-solid fa-users text-sm"></i>
                    </div>
                    <span class="text-[10px] font-bold mt-0.5">Clientes</span>
                </a>
                <a href="rutas.php" class="flex flex-col items-center text-slate-500 hover:text-blue-600 transition">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-slate-100 text-slate-700 shadow-xs">
                        <i class="fa-solid fa-route text-sm"></i>
                    </div>
                    <span class="text-[10px] font-bold mt-0.5">Rutas</span>
                </a>
                <a href="agregar.php" class="flex flex-col items-center transition -mt-3">
                    <div class="w-11 h-11 bg-blue-600 text-white rounded-full flex items-center justify-center shadow-md shadow-blue-500/40 border-2 border-white">
                        <i class="fa-solid fa-plus text-base"></i>
                    </div>
                    <span class="text-[10px] font-bold mt-0.5 text-blue-600">Agregar</span>
                </a>
                <a href="index.php" class="flex flex-col items-center text-slate-500 hover:text-blue-600 transition">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-slate-100 text-slate-700 shadow-xs">
                        <i class="fa-solid fa-map text-sm"></i>
                    </div>
                    <span class="text-[10px] font-bold mt-0.5">Mapa</span>
                </a>
            </div>
        </nav>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script>
        const map = L.map('map').setView([19.412530, -99.018240], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
        let markersGroup = L.layerGroup().addTo(map);
        let clientesGlobales = [];

        fetch('obtener_clientes.php')
            .then(res => res.json())
            .then(data => { 
                if(Array.isArray(data)) {
                    clientesGlobales = data; 
                    renderizarMapa('todos');
                } else {
                    clientesGlobales = [];
                    renderizarMapa('todos');
                }
            })
            .catch(err => {
                document.getElementById('lista-paradas').innerHTML = '<div class="text-center py-6 text-slate-400 text-xs">Sin conexión con la base de datos.</div>';
            });

        function renderizarMapa(dia) {
            markersGroup.clearLayers();
            const filtrados = dia === 'todos' ? clientesGlobales : clientesGlobales.filter(c => (c.delivery_day || '').toLowerCase() === dia.toLowerCase());
            document.getElementById('badge-total').innerText = filtrados.length + ' Entregas';
            document.getElementById('info-ruta').innerText = dia === 'todos' ? 'Todas las rutas' : 'Día: ' + dia;
            const contenedor = document.getElementById('lista-paradas');
            contenedor.innerHTML = '';

            if (filtrados.length === 0) {
                contenedor.innerHTML = `<div class="text-center py-6 text-slate-400 text-xs">No hay entregas registradas para este filtro.</div>`;
                return;
            }

            filtrados.forEach((c, idx) => {
                const lat = parseFloat(c.latitude) || 19.412530;
                const lng = parseFloat(c.longitude) || -99.018240;
                L.marker([lat, lng]).addTo(markersGroup);
                
                contenedor.innerHTML += `
                    <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100 flex items-center justify-between">
                        <div>
                            <h4 class="text-xs font-bold text-slate-800">${idx + 1}. ${c.name || c.nombre_local}</h4>
                            <p class="text-[10px] text-slate-400">${c.address || c.direccion || 'Sin dirección'}</p>
                        </div>
                        <a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(c.address || c.direccion)}" target="_blank" class="bg-amber-500 text-white px-2.5 py-1 rounded-xl text-[10px] font-bold"><i class="fa-solid fa-map-location-dot"></i> Maps</a>
                    </div>`;
            });
        }

        function filtrarDia(dia) {
            document.querySelectorAll('.filter-btn').forEach(b => {
                b.classList.remove('bg-sky-600', 'text-white');
                b.classList.add('bg-white', 'text-slate-600', 'border', 'border-slate-200');
            });
            event.currentTarget.classList.remove('bg-white', 'text-slate-600', 'border');
            event.currentTarget.classList.add('bg-sky-600', 'text-white');
            renderizarMapa(dia);
        }
    </script>
</body>
</html>