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
<body class="bg-slate-100 font-sans pb-28">
    <!-- Contenedor General de Ancho Completo para PC y Adaptable -->
    <div class="w-full max-w-[1400px] mx-auto p-4 sm:p-6 space-y-4">
        
        <!-- Header -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-md font-bold text-xl">🧊</div>
                <div>
                    <h1 class="text-base sm:text-lg font-black text-blue-950">HIELO MOSCÚ</h1>
                    <p id="info-ruta" class="text-xs font-bold text-sky-600">Mapa General</p>
                </div>
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-end">
                <span id="badge-total" class="text-xs font-bold bg-sky-50 text-sky-600 px-3 py-2 rounded-xl border border-sky-100">0 Entregas</span>
                <a href="admin_rutas.php" class="bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold px-3 py-2 rounded-xl shadow-sm transition flex items-center gap-1.5" title="Panel de Oficina">
                    <i class="fa-solid fa-lock text-xs"></i> Panel de Oficina
                </a>
            </div>
        </div>

        <!-- Filtros de días -->
        <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm flex space-x-2 overflow-x-auto no-scrollbar">
            <button onclick="filtrarDia('todos')" class="filter-btn px-4 py-2 rounded-xl text-xs font-bold bg-sky-600 text-white shrink-0 shadow-sm transition">Todos</button>
            <button onclick="filtrarDia('Lunes')" class="filter-btn px-4 py-2 rounded-xl text-xs font-bold bg-slate-50 text-slate-600 border border-slate-200 shrink-0 hover:bg-slate-100 transition">Lunes</button>
            <button onclick="filtrarDia('Martes')" class="filter-btn px-4 py-2 rounded-xl text-xs font-bold bg-slate-50 text-slate-600 border border-slate-200 shrink-0 hover:bg-slate-100 transition">Martes</button>
            <button onclick="filtrarDia('Miércoles')" class="filter-btn px-4 py-2 rounded-xl text-xs font-bold bg-slate-50 text-slate-600 border border-slate-200 shrink-0 hover:bg-slate-100 transition">Miércoles</button>
            <button onclick="filtrarDia('Jueves')" class="filter-btn px-4 py-2 rounded-xl text-xs font-bold bg-slate-50 text-slate-600 border border-slate-200 shrink-0 hover:bg-slate-100 transition">Jueves</button>
            <button onclick="filtrarDia('Viernes')" class="filter-btn px-4 py-2 rounded-xl text-xs font-bold bg-slate-50 text-slate-600 border border-slate-200 shrink-0 hover:bg-slate-100 transition">Viernes</button>
            <button onclick="filtrarDia('Sábado')" class="filter-btn px-4 py-2 rounded-xl text-xs font-bold bg-slate-50 text-slate-600 border border-slate-200 shrink-0 hover:bg-slate-100 transition">Sábado</button>
            <button onclick="filtrarDia('Domingo')" class="filter-btn px-4 py-2 rounded-xl text-xs font-bold bg-slate-50 text-slate-600 border border-slate-200 shrink-0 hover:bg-slate-100 transition">Domingo</button>
        </div>

        <!-- Grid de PC: Mapa a la izquierda (2 columnas), Lista de Paradas a la derecha (1 columna) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Mapa -->
            <div class="lg:col-span-2 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm space-y-2">
                <h2 class="text-xs font-black text-slate-700 uppercase tracking-wider"><i class="fa-solid fa-map-location-dot text-blue-600 mr-1.5"></i> Vista de Mapa</h2>
                <div class="w-full h-[480px] rounded-xl overflow-hidden border border-slate-200 relative">
                    <div id="map" class="w-full h-full"></div>
                </div>
            </div>

            <!-- Lista de Paradas -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm space-y-3 flex flex-col">
                <h2 class="text-xs font-black text-slate-700 uppercase tracking-wider"><i class="fa-solid fa-list-check text-sky-500 mr-1.5"></i> Paradas Programadas</h2>
                <div id="lista-paradas" class="space-y-2.5 max-h-[440px] overflow-y-auto pr-1 flex-1">
                    <div class="text-center py-16 text-slate-400 text-xs">Cargando puntos desde MySQL...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Menú de Navegación Inferior -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-slate-200 py-2.5 px-3 z-30 shadow-lg">
        <div class="max-w-md mx-auto flex justify-around items-center text-center">
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script>
        const map = L.map('map').setView([19.412530, -99.018240], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
        let markersGroup = L.layerGroup().addTo(map);
        let clientesGlobales = [];

        // Petición apuntando correctamente a la API con control anti-caché
        fetch('api/obtener_clientes.php?t=' + new Date().getTime(), { cache: 'no-store' })
            .then(res => {
                if (!res.ok) {
                    throw new Error('Error HTTP código ' + res.status);
                }
                return res.json();
            })
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
                console.error("Detalle del error:", err);
                document.getElementById('lista-paradas').innerHTML = '<div class="text-center py-6 text-red-500 text-xs font-bold">Error técnico: ' + err.message + '</div>';
            });

        function renderizarMapa(dia) {
            markersGroup.clearLayers();
            
            // Filtrado ultra flexible: limpia acentos, espacios y mayúsculas/minúsculas
            const filtrados = dia === 'todos' ? clientesGlobales : clientesGlobales.filter(c => {
                const diaCliente = (c.delivery_day || '').trim().toLowerCase()
                    .normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                const diaFiltro = dia.trim().toLowerCase()
                    .normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                return diaCliente === diaFiltro;
            });

            document.getElementById('badge-total').innerText = filtrados.length + ' Entregas';
            document.getElementById('info-ruta').innerText = dia === 'todos' ? 'Todas las rutas' : 'Día: ' + dia;
            const contenedor = document.getElementById('lista-paradas');
            contenedor.innerHTML = '';

            if (filtrados.length === 0) {
                contenedor.innerHTML = `<div class="text-center py-12 text-slate-400 text-xs">No hay entregas registradas para este filtro (${dia}).</div>`;
                return;
            }

            filtrados.forEach((c, idx) => {
                const lat = parseFloat(c.latitude) || 19.412530;
                const lng = parseFloat(c.longitude) || -99.018240;
                L.marker([lat, lng]).addTo(markersGroup);
                
                contenedor.innerHTML += `
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/80 flex items-center justify-between hover:bg-slate-100/70 transition">
                        <div>
                            <h4 class="text-xs font-bold text-slate-800">${idx + 1}. ${c.name || c.nombre_local} <span class="text-[10px] bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded-md ml-1">${c.delivery_day || 'Sin día'}</span></h4>
                            <p class="text-[10px] text-slate-400 mt-0.5">${c.address || c.direccion || 'Sin dirección'}</p>
                        </div>
                        <a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(c.address || c.direccion)}" target="_blank" class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-lg text-[10px] font-bold transition shadow-xs flex items-center gap-1"><i class="fa-solid fa-map-location-dot"></i> Maps</a>
                    </div>`;
            });
        }

        function filtrarDia(dia) {
            document.querySelectorAll('.filter-btn').forEach(b => {
                b.classList.remove('bg-sky-600', 'text-white', 'shadow-sm');
                b.classList.add('bg-slate-50', 'text-slate-600', 'border', 'border-slate-200');
            });
            event.currentTarget.classList.remove('bg-slate-50', 'text-slate-600', 'border');
            event.currentTarget.classList.add('bg-sky-600', 'text-white', 'shadow-sm');
            renderizarMapa(dia);
        }
    </script>
    <?php include 'footer_mobile.php'; ?>
</body>
</html>