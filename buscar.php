<?php
require_once 'seguridad.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "hielo_moscu");
if ($conn->connect_error) { 
    die("Error de conexión: " . $conn->connect_error); 
}
$conn->set_charset("utf8mb4");

$busqueda = trim($_GET['q'] ?? '');
$clientes = [];

if (!empty($busqueda)) {
    $busqueda_sql = "%" . $busqueda . "%";
    $stmt = $conn->prepare("SELECT * FROM clientes WHERE nombre_local LIKE ? OR direccion LIKE ? OR telefono LIKE ?");
    $stmt->bind_param("sss", $busqueda_sql, $busqueda_sql, $busqueda_sql);
    $stmt->execute();
    $resultado = $stmt->get_result();
    while ($row = $resultado->fetch_assoc()) {
        $clientes[] = $row;
    }
    $stmt->close();
} else {
    // Si no hay búsqueda, mostramos los últimos clientes registrados de forma segura
    $resultado = $conn->query("SELECT * FROM clientes ORDER BY id_cliente DESC LIMIT 20");
    if ($resultado) {
        while ($row = $resultado->fetch_assoc()) {
            $clientes[] = $row;
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hielo Moscú - Buscar Clientes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 font-sans pb-28">
    <div class="w-full max-w-[1000px] mx-auto p-4 sm:p-6 space-y-4">
        
        <!-- Header -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-md font-bold text-xl">🔍</div>
                <div>
                    <h1 class="text-base sm:text-lg font-black text-blue-950">HIELO MOSCÚ</h1>
                    <p class="text-xs font-bold text-sky-600">Buscador de Clientes</p>
                </div>
            </div>
            <a href="index.php" class="bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold px-3 py-2 rounded-xl shadow-sm transition">Volver al Mapa</a>
        </div>

        <!-- Formulario de Búsqueda -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200 shadow-sm">
            <form method="GET" action="buscar.php" class="flex gap-2">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" name="q" value="<?php echo e($busqueda); ?>" placeholder="Buscar por nombre, dirección o teléfono..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-3 text-xs font-bold text-slate-800 focus:outline-none focus:border-blue-600 transition">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl text-xs font-bold transition shadow-sm">Buscar</button>
            </form>
        </div>

        <!-- Resultados -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200 shadow-sm space-y-3">
            <h2 class="text-xs font-black text-slate-700 uppercase tracking-wider">
                <i class="fa-solid fa-list text-sky-500 mr-1.5"></i> Resultados (<?php echo count($clientes); ?>)
            </h2>
            
            <div class="space-y-2.5">
                <?php if (count($clientes) === 0): ?>
                    <div class="text-center py-12 text-slate-400 text-xs">No se encontraron clientes que coincidan con la búsqueda.</div>
                <?php else: ?>
                    <?php foreach ($clientes as $c): ?>
                        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 hover:bg-slate-100/70 transition">
                            <div>
                                <h4 class="text-xs font-black text-slate-800">
                                    <?php echo e($c['nombre_local'] ?? $c['nombre'] ?? 'Sin nombre'); ?>
                                    <span class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded-md ml-2 font-bold"><?php echo e($c['delivery_day'] ?? 'Sin día'); ?></span>
                                </h4>
                                <p class="text-[11px] text-slate-500 mt-0.5"><i class="fa-solid fa-location-dot text-red-500 mr-1"></i><?php echo e($c['direccion'] ?? 'Sin dirección'); ?></p>
                                <?php if (!empty($c['telefono'])): ?>
                                    <p class="text-[10px] text-slate-400 mt-0.5"><i class="fa-solid fa-phone text-emerald-500 mr-1"></i><?php echo e($c['telefono']); ?></p>
                                <?php endif; ?>
                            </div>
                            <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($c['direccion'] ?? ''); ?>" target="_blank" rel="noopener noreferrer" class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-2 rounded-lg text-[10px] font-bold transition shadow-xs flex items-center gap-1 shrink-0">
                                <i class="fa-solid fa-map-location-dot"></i> Maps
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Menú de Navegación Inferior Actualizado -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-slate-200 py-2 px-2 z-30 shadow-lg">
        <div class="max-w-md mx-auto flex justify-around items-center text-center">
            <a href="index.php" class="flex flex-col items-center text-slate-500 hover:text-blue-600 transition">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-slate-100 text-slate-700 shadow-xs">
                    <i class="fa-solid fa-house text-sm"></i>
                </div>
                <span class="text-[9px] font-bold mt-0.5">Inicio</span>
            </a>
            <a href="clientes.php" class="flex flex-col items-center text-slate-500 hover:text-blue-600 transition">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-slate-100 text-slate-700 shadow-xs">
                    <i class="fa-solid fa-users text-sm"></i>
                </div>
                <span class="text-[9px] font-bold mt-0.5">Clientes</span>
            </a>
            <a href="rutas.php" class="flex flex-col items-center text-slate-500 hover:text-blue-600 transition">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-slate-100 text-slate-700 shadow-xs">
                    <i class="fa-solid fa-route text-sm"></i>
                </div>
                <span class="text-[9px] font-bold mt-0.5">Rutas</span>
            </a>
            <a href="agregar.php" class="flex flex-col items-center transition -mt-3">
                <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center shadow-md shadow-blue-500/40 border-2 border-white">
                    <i class="fa-solid fa-plus text-sm"></i>
                </div>
                <span class="text-[9px] font-bold mt-0.5 text-blue-600">Agregar</span>
            </a>
            <a href="buscar.php" class="flex flex-col items-center text-blue-600 font-bold transition">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-blue-50 text-blue-600 shadow-xs">
                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                </div>
                <span class="text-[9px] font-bold mt-0.5">Buscar</span>
            </a>
            <a href="index.php" class="flex flex-col items-center text-slate-500 hover:text-blue-600 transition">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-slate-100 text-slate-700 shadow-xs">
                    <i class="fa-solid fa-map text-sm"></i>
                </div>
                <span class="text-[9px] font-bold mt-0.5">Mapa</span>
            </a>
        </div>
    </nav>
    <?php include 'footer_mobile.php'; ?>
</body>
</html>