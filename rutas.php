<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "hielo_moscu");
if ($conn->connect_error) { 
    die("Error de conexión a MySQL: " . $conn->connect_error); 
}
$conn->set_charset("utf8");

// Consultamos todos los clientes de forma segura
$sql = "SELECT * FROM clientes";
$resultado = $conn->query($sql);

$total_clientes_global = 0;
$conteo_dias = [];

if ($resultado) {
    while ($row = $resultado->fetch_assoc()) {
        $total_clientes_global++;
        
        // Detectar automáticamente qué columna guarda el día
        $dia = '';
        foreach (['delivery_day', 'dia_entrega', 'dia', 'dias'] as $col) {
            if (!empty($row[$col])) {
                $dia = trim($row[$col]);
                break;
            }
        }

        if (!empty($dia)) {
            $dia = ucfirst(strtolower($dia));
            if (!isset($conteo_dias[$dia])) {
                $conteo_dias[$dia] = 0;
            }
            $conteo_dias[$dia]++;
        }
    }
}

$rutas_por_dia = [];
foreach ($conteo_dias as $dia => $total) {
    $rutas_por_dia[] = [
        'delivery_day' => $dia,
        'total_clientes' => $total
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hielo Moscú - Gestión de Rutas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 font-sans pb-28">
    <div class="w-full max-w-[1400px] mx-auto p-4 sm:p-6 space-y-4">
        
        <!-- Header -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-md font-bold text-xl">🛣️</div>
                <div>
                    <h1 class="text-base sm:text-lg font-black text-blue-950">HIELO MOSCÚ</h1>
                    <p class="text-xs font-bold text-sky-600">Panel de Rutas Activas</p>
                </div>
            </div>
            <a href="index.php" class="bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold px-3 py-2 rounded-xl shadow-sm transition">Volver al Mapa</a>
        </div>

        <!-- Estadísticas dinámicas -->
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm text-center">
                <p class="text-xs font-bold text-slate-400 uppercase">Rutas Programadas</p>
                <h3 class="text-2xl font-black text-blue-950 mt-1"><?php echo count($rutas_por_dia); ?></h3>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm text-center">
                <p class="text-xs font-bold text-slate-400 uppercase">Clientes Totales</p>
                <h3 class="text-2xl font-black text-sky-600 mt-1"><?php echo $total_clientes_global; ?></h3>
            </div>
        </div>

        <!-- Lista de Rutas Dinámicas -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <h2 class="text-xs font-black text-slate-700 uppercase tracking-wider"><i class="fa-solid fa-route text-blue-600 mr-1.5"></i> Rutas según Base de Datos</h2>
            
            <div class="space-y-3">
                <?php if (count($rutas_por_dia) === 0): ?>
                    <div class="text-center py-12 text-slate-400 text-xs">No hay rutas o clientes con días asignados en la base de datos.</div>
                <?php else: ?>
                    <?php foreach ($rutas_por_dia as $ruta): ?>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-black text-slate-800 flex items-center gap-2">
                                    <i class="fa-solid fa-calendar-day text-blue-600"></i> Día: <?php echo htmlspecialchars($ruta['delivery_day']); ?>
                                </h4>
                                <p class="text-xs font-bold text-sky-600 mt-1"><?php echo $ruta['total_clientes']; ?> cliente(s) programado(s)</p>
                            </div>
                            <a href="index.php" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2 rounded-xl transition shadow-xs">Ver en Mapa</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Menú de Navegación Inferior (Igual al de index.php) -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-slate-200 py-2.5 px-3 z-30 shadow-lg">
        <div class="max-w-md mx-auto flex justify-around items-center text-center">
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
    <?php include 'footer_mobile.php'; ?>
</body>
</html>