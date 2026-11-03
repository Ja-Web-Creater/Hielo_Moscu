<?php
$conn = new mysqli("localhost", "root", "", "hielo_moscu");
if ($conn->connect_error) { die("Error de conexión: " . $conn->connect_error); }
include 'header_mobile.php';
?>
<div class="p-4 space-y-4">
    <!-- Header -->
    <div class="flex items-center space-x-3 pt-2">
        <div class="w-10 h-10 bg-gradient-to-tr from-blue-600 to-sky-400 rounded-2xl flex items-center justify-center text-white shadow-md">🧊</div>
        <div>
            <h1 class="text-base font-black text-blue-950">HIELO MOSCÚ</h1>
            <p class="text-[11px] font-bold text-sky-600">Rutas</p>
        </div>
    </div>

    <!-- Buscador -->
    <div class="relative">
        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400 text-sm"></i>
        <input type="text" placeholder="Buscar ruta, zona o día..." class="w-full bg-white border border-slate-200 rounded-2xl pl-10 pr-4 py-3 text-xs outline-none focus:border-blue-500 shadow-xs">
    </div>

    <!-- Resumen superior (Estilo Imagen 3) -->
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-white border border-slate-200 p-3.5 rounded-2xl text-center shadow-xs">
            <div class="flex items-center justify-center text-blue-600 text-base mb-0.5"><i class="fa-solid fa-route"></i></div>
            <span class="text-lg font-black text-blue-950">18</span>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">rutas</p>
        </div>
        <div class="bg-white border border-slate-200 p-3.5 rounded-2xl text-center shadow-xs">
            <div class="flex items-center justify-center text-red-500 text-base mb-0.5"><i class="fa-solid fa-location-dot"></i></div>
            <span class="text-lg font-black text-blue-950">6</span>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">zonas</p>
        </div>
    </div>

    <div class="flex justify-between items-center pt-1">
        <h2 class="text-xs font-black text-slate-800 uppercase tracking-wider">Lista de rutas</h2>
        <span class="text-[10px] font-bold text-slate-400">Ordenar por Día ▾</span>
    </div>

    <!-- Listado de Rutas (Estilo Tarjetas Imagen 3) -->
    <div class="space-y-3">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/85 space-y-3 shadow-xs">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-2.5">
                    <div class="w-3.5 h-3.5 bg-amber-500 rounded-full shadow-xs"></div>
                    <h3 class="text-xs font-black text-blue-950">RUTA 1</h3>
                </div>
                <i class="fa-solid fa-chevron-right text-slate-300 text-xs"></i>
            </div>
            <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-500 border-t border-slate-100 pt-2.5">
                <div><i class="fa-solid fa-calendar text-blue-500 mr-1"></i> Martes</div>
                <div><i class="fa-solid fa-users text-blue-500 mr-1"></i> 18 clientes</div>
                <div class="col-span-2"><i class="fa-solid fa-location-dot text-red-500 mr-1"></i> Zona: Nezahualcóyotl</div>
            </div>
            <div class="flex gap-2 pt-1">
                <a href="index.php" class="flex-1 bg-blue-50 hover:bg-blue-100 text-blue-700 text-center py-2 rounded-xl text-xs font-bold transition"><i class="fa-solid fa-map mr-1"></i> Ver mapa</a>
                <a href="admin_rutas.php" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-center py-2 rounded-xl text-xs font-bold transition"><i class="fa-solid fa-pen mr-1"></i> Editar</a>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/85 space-y-3 shadow-xs">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-2.5">
                    <div class="w-3.5 h-3.5 bg-purple-500 rounded-full shadow-xs"></div>
                    <h3 class="text-xs font-black text-blue-950">RUTA 2</h3>
                </div>
                <i class="fa-solid fa-chevron-right text-slate-300 text-xs"></i>
            </div>
            <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-500 border-t border-slate-100 pt-2.5">
                <div><i class="fa-solid fa-calendar text-blue-500 mr-1"></i> Jueves</div>
                <div><i class="fa-solid fa-users text-blue-500 mr-1"></i> 14 clientes</div>
                <div class="col-span-2"><i class="fa-solid fa-location-dot text-red-500 mr-1"></i> Zona: Chimalhuacán</div>
            </div>
            <div class="flex gap-2 pt-1">
                <a href="index.php" class="flex-1 bg-blue-50 hover:bg-blue-100 text-blue-700 text-center py-2 rounded-xl text-xs font-bold transition"><i class="fa-solid fa-map mr-1"></i> Ver mapa</a>
                <a href="admin_rutas.php" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-center py-2 rounded-xl text-xs font-bold transition"><i class="fa-solid fa-pen mr-1"></i> Editar</a>
            </div>
        </div>
    </div>
</div>
<?php include 'footer_mobile.php'; ?>