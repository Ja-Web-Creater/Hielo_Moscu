<?php
$conn = new mysqli("localhost", "root", "", "hielo_moscu");
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $conn->real_escape_string($_POST['nombre_local']);
    $telefono = $conn->real_escape_string($_POST['telefono']);
    $direccion = $conn->real_escape_string($_POST['direccion']);
    
    if ($conn->query("INSERT INTO clientes (nombre_local, telefono, direccion) VALUES ('$nombre', '$telefono', '$direccion')")) {
        $mensaje = "✅ ¡Cliente registrado con éxito en el sistema!";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hielo Moscú - Agregar cliente</title>
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
                        <p class="text-[11px] font-bold text-sky-600">Agregar cliente</p>
                    </div>
                </div>
                <span class="text-[10px] text-slate-400 font-bold uppercase">Confianza en tu ruta</span>
            </div>

            <?php if(!empty($mensaje)): ?>
                <div class="bg-emerald-100 text-emerald-800 p-3 rounded-xl text-xs font-bold"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <!-- Formulario Principal -->
            <form method="POST" class="space-y-4">
                <!-- Datos Generales -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-3">
                    <h2 class="text-xs font-black text-slate-800 uppercase tracking-wider"><i class="fa-solid fa-user text-blue-600 mr-1.5"></i> Datos del cliente</h2>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Nombre del negocio</label>
                        <input type="text" name="nombre_local" required placeholder="Ej. Abarrotes La Esperanza" class="w-full border border-slate-300 rounded-xl p-2.5 text-xs outline-none focus:border-blue-500 bg-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Teléfono de contacto</label>
                        <input type="text" name="telefono" placeholder="Ej. 5512345678" class="w-full border border-slate-300 rounded-xl p-2.5 text-xs outline-none focus:border-blue-500 bg-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Ubicación / Dirección</label>
                        <input type="text" name="direccion" required placeholder="Av. Bordo de Xochiaca, Nezahualcóyotl" class="w-full border border-slate-300 rounded-xl p-2.5 text-xs outline-none focus:border-blue-500 bg-white">
                    </div>
                </div>

                <!-- Detalles de Enfriador -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-3">
                    <h2 class="text-xs font-black text-slate-800 uppercase tracking-wider"><i class="fa-solid fa-snowflake text-sky-500 mr-1.5"></i> Enfriador</h2>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400">No. Serie</label>
                            <input type="text" placeholder="SN-4587-20" class="w-full border border-slate-200 rounded-xl p-2 text-xs bg-white">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400">Capacidad</label>
                            <input type="text" placeholder="5,000 L" class="w-full border border-slate-200 rounded-xl p-2 text-xs bg-white">
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex gap-2 pt-1">
                    <a href="index.php" class="flex-1 bg-slate-100 text-slate-600 text-center py-3 rounded-xl text-xs font-bold hover:bg-slate-200 transition">Cancelar</a>
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl text-xs shadow-md shadow-blue-500/20 transition">Guardar cliente</button>
                </div>
            </form>
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
</body>
</html>