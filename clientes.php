<?php
$conn = new mysqli("localhost", "root", "", "hielo_moscu");
if ($conn->connect_error) { 
    die("Error de conexión: " . $conn->connect_error); 
}
$clientes = $conn->query("SELECT * FROM clientes ORDER BY nombre_local ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hielo Moscú - Clientes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 font-sans pb-24">
    <div class="max-w-md mx-auto min-h-screen bg-white shadow-xl flex flex-col justify-between">
        <div class="p-4 space-y-4">
            <!-- Header -->
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-md font-bold text-lg">🧊</div>
                <div>
                    <h1 class="text-base font-black text-blue-950">HIELO MOSCÚ</h1>
                    <p class="text-[11px] font-bold text-sky-600">Clientes</p>
                </div>
            </div>

            <!-- Buscador -->
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400 text-sm"></i>
                <input type="text" id="buscador" placeholder="Buscar cliente, negocio o zona..." class="w-full bg-slate-50 border border-slate-200 rounded-2xl pl-10 pr-4 py-3 text-xs outline-none focus:border-blue-500">
            </div>

            <div class="flex justify-between items-center">
                <h2 class="text-xs font-black text-slate-800 uppercase tracking-wider">Lista de clientes</h2>
                <span class="text-[10px] font-bold text-slate-400">Ordenar por Nombre ▾</span>
            </div>

            <!-- Listado de Clientes -->
            <div id="lista-clientes" class="space-y-2.5">
                <?php if ($clientes && $clientes->num_rows > 0): ?>
                    <?php while($c = $clientes->fetch_assoc()): ?>
                        <div class="bg-slate-50 hover:bg-white p-3.5 rounded-2xl border border-slate-200 transition shadow-sm space-y-1 cliente-item" data-nombre="<?php echo strtolower($c['nombre_local'] ?? ''); ?>" data-zona="<?php echo strtolower($c['direccion'] ?? ''); ?>">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-xs font-bold text-blue-950"><?php echo htmlspecialchars($c['nombre_local'] ?? 'Sin nombre'); ?></h3>
                                    <p class="text-[11px] text-slate-400"><i class="fa-solid fa-location-dot text-red-500 mr-1"></i><?php echo htmlspecialchars($c['direccion'] ?? 'Sin dirección'); ?></p>
                                    <?php if(!empty($c['telefono'])): ?>
                                        <p class="text-[11px] text-slate-500 mt-0.5"><i class="fa-solid fa-phone text-emerald-600 mr-1"></i><?php echo htmlspecialchars($c['telefono']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center gap-1">
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($c['direccion'] ?? ''); ?>" target="_blank" class="w-7 h-7 bg-amber-100 text-amber-700 rounded-full flex items-center justify-center text-xs" title="Ver en Maps"><i class="fa-solid fa-map-location-dot"></i></a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-8 text-slate-400 text-xs">No hay clientes registrados en la base de datos.</div>
                <?php endif; ?>
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
                <a href="clientes.php" class="flex flex-col items-center text-blue-600 font-bold transition">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-blue-50 text-blue-600 shadow-xs">
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

    <script>
        document.getElementById('buscador').addEventListener('input', function(e) {
            const filtro = e.target.value.toLowerCase();
            document.querySelectorAll('.cliente-item').forEach(item => {
                const nombre = item.getAttribute('data-nombre');
                const zona = item.getAttribute('data-zona');
                if (nombre.includes(filtro) || zona.includes(filtro)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>