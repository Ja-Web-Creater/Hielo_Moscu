<?php
$conn = new mysqli("localhost", "root", "", "hielo_moscu");
if ($conn->connect_error) { die("Error de conexión: " . $conn->connect_error); }
$rutas = $conn->query("SELECT * FROM rutas");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hielo Moscú - Rutas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 font-sans pb-24">
    <div class="max-w-md mx-auto min-h-screen bg-white shadow-xl flex flex-col justify-between">
        <div class="p-4 space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-md font-bold text-lg">🧊</div>
                    <div>
                        <h1 class="text-base font-black text-blue-950">HIELO MOSCÚ</h1>
                        <p class="text-[11px] font-bold text-sky-600">Gestión de Rutas</p>
                    </div>
                </div>
                <a href="admin_rutas.php" class="bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold px-3 py-2 rounded-xl shadow-sm transition flex items-center gap-1.5">
                    <i class="fa-solid fa-lock text-[10px]"></i> Oficina
                </a>
            </div>

            <!-- Accesos Rápidos Visibles -->
            <div class="grid grid-cols-2 gap-2">
                <a href="admin_rutas.php" class="bg-blue-50 hover:bg-blue-100 border border-blue-200 p-3 rounded-2xl text-center transition flex flex-col items-center justify-center">
                    <i class="fa-solid fa-plus-circle text-blue-600 text-base mb-1"></i>
                    <span class="text-xs font-bold text-blue-950">Asignar Rutas</span>
                </a>
                <a href="repartidor_ruta.php" class="bg-amber-50 hover:bg-amber-100 border border-amber-200 p-3 rounded-2xl text-center transition flex flex-col items-center justify-center">
                    <i class="fa-solid fa-truck-fast text-amber-600 text-base mb-1"></i>
                    <span class="text-xs font-bold text-amber-900">Vista Repartidor</span>
                </a>
            </div>

            <!-- Resumen superior -->
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-slate-50 border border-slate-200 p-3 rounded-2xl text-center">
                    <span class="text-lg font-black text-blue-950"><i class="fa-solid fa-route text-blue-600 mr-1"></i> 18</span>
                    <p class="text-[10px] text-slate-400 font-bold uppercase">Rutas activas</p>
                </div>
                <div class="bg-slate-50 border border-slate-200 p-3 rounded-2xl text-center">
                    <span class="text-lg font-black text-blue-950"><i class="fa-solid fa-location-dot text-red-500 mr-1"></i> 6</span>
                    <p class="text-[10px] text-slate-400 font-bold uppercase">Zonas</p>
                </div>
            </div>

            <h2 class="text-xs font-black text-slate-800 uppercase tracking-wider">Lista de rutas principales</h2>

            <!-- Listado de Rutas -->
            <div class="space-y-3">
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-3 shadow-sm">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-amber-500 rounded-full"></div>
                            <h3 class="text-xs font-bold text-blue-950">RUTA 1 - CENTRO</h3>
                        </div>
                        <span class="text-[10px] font-bold bg-amber-100 text-amber-700 px-2 py-0.5 rounded-lg">Martes</span>
                    </div>
                    <div class="flex gap-2">
                        <a href="index.php" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-2 rounded-xl text-xs font-bold"><i class="fa-solid fa-map"></i> Ver en mapa</a>
                        <a href="admin_rutas.php" class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-700 text-center py-2 rounded-xl text-xs font-bold"><i class="fa-solid fa-pen"></i> Administrar</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menú de Navegación Inferior -->
        <nav class="fixed bottom-0 left-0 right-0 max-w-md mx-auto bg-white/95 backdrop-blur-md border-t border-slate-200 py-2 px-3 z-30 shadow-lg">
            <div class="flex justify-around items-center text-center">
                <a href="index.php" class="flex flex-col items-center text-slate-500 hover:text-blue-600 transition">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-slate-100 text-slate-700 shadow-xs">
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
                <a href="rutas.php" class="flex flex-col items-center text-blue-600 font-bold transition">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-blue-50 text-blue-600 shadow-xs">
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
</body>
</html>