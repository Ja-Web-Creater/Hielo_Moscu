<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "hielo_moscu");
if ($conn->connect_error) { 
    die("Error de conexión: " . $conn->connect_error); 
}
$conn->set_charset("utf8");

// Asegurar automáticamente que la columna existe en la tabla clientes
$conn->query("ALTER TABLE clientes ADD COLUMN IF NOT EXISTS delivery_day VARCHAR(50) DEFAULT 'Lunes'");
$conn->query("ALTER TABLE clientes ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,8) DEFAULT 19.412530");
$conn->query("ALTER TABLE clientes ADD COLUMN IF NOT EXISTS longitude DECIMAL(11,8) DEFAULT -99.018240");

$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre    = $conn->real_escape_string($_POST['nombre_local'] ?? '');
    $telefono  = $conn->real_escape_string($_POST['telefono'] ?? '');
    $direccion = $conn->real_escape_string($_POST['direccion'] ?? '');
    
    // Capturamos el día de la sugerencia de ruta (por defecto Martes)
    $delivery_day = $conn->real_escape_string($_POST['delivery_day'] ?? 'Martes');
    
    // Coordenadas base para Nezahualcóyotl
    $latitude  = 19.412530;
    $longitude = -99.018240;

    if (!empty($nombre) && !empty($direccion)) {
        $sql = "INSERT INTO clientes (nombre_local, telefono, direccion, delivery_day, latitude, longitude) 
                VALUES ('$nombre', '$telefono', '$direccion', '$delivery_day', '$latitude', '$longitude')";
        
        if ($conn->query($sql)) {
            $mensaje = "✅ ¡Cliente registrado con éxito y asignado a ruta!";
        } else {
            // Fallback defensivo si ocurriera otro problema con columnas
            $sql_fallback = "INSERT INTO clientes (nombre_local, telefono, direccion) VALUES ('$nombre', '$telefono', '$direccion')";
            if ($conn->query($sql_fallback)) {
                $mensaje = "✅ ¡Cliente registrado con éxito!";
            } else {
                $mensaje = "❌ Error al registrar: " . $conn->error;
            }
        }
    } else {
        $mensaje = "⚠️ El nombre del negocio y la dirección son obligatorios.";
    }
}

// Header dinámico
if (file_exists('header_mobile.php')) {
    include 'header_mobile.php';
} else {
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></head><body class="bg-slate-100 font-sans pb-28">';
}
?>
<div class="p-4 space-y-4 max-w-[800px] mx-auto">
    <!-- Header con frase institucional -->
    <div class="flex items-center justify-between pt-2 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-tr from-blue-600 to-sky-400 rounded-2xl flex items-center justify-center text-white shadow-md">🧊</div>
            <div>
                <h1 class="text-base font-black text-blue-950">HIELO MOSCÚ</h1>
                <p class="text-[11px] font-bold text-sky-600">Agregar cliente</p>
            </div>
        </div>
        <span class="text-[9px] font-black text-slate-400 text-right uppercase tracking-wider">Más que hielo,<br>confianza en tu ruta</span>
    </div>

    <?php if(!empty($mensaje)): ?>
        <div class="bg-emerald-100 text-emerald-800 p-3 rounded-xl text-xs font-bold flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i> <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <!-- Datos del Cliente -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-3">
            <h2 class="text-xs font-black text-blue-950 uppercase tracking-wider flex items-center gap-1.5"><i class="fa-solid fa-user text-blue-600"></i> Datos del cliente</h2>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-1">Nombre del negocio</label>
                <input type="text" name="nombre_local" required placeholder="Ej. Abarrotes La Esperanza" class="w-full border border-slate-200 rounded-xl p-2.5 text-xs outline-none focus:border-blue-500 bg-slate-50 font-bold text-slate-800">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-1">Teléfono</label>
                <input type="text" name="telefono" placeholder="Ej. 5512345678" class="w-full border border-slate-200 rounded-xl p-2.5 text-xs outline-none focus:border-blue-500 bg-slate-50 font-bold text-slate-800">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-1">Ubicación / Dirección</label>
                <input type="text" name="direccion" required placeholder="Av. Bordo de Xochiaca, Nezahualcóyotl" class="w-full border border-slate-200 rounded-xl p-2.5 text-xs outline-none focus:border-blue-500 bg-slate-50 font-bold text-slate-800">
            </div>
        </div>

        <!-- Sugerencia de Ruta Activa -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-2">
            <h2 class="text-xs font-black text-blue-950 uppercase tracking-wider flex items-center gap-1.5"><i class="fa-solid fa-route text-amber-500"></i> Sugerencia de ruta</h2>
            
            <!-- Campo oculto que inyecta el día seleccionado en la ruta sugerida -->
            <input type="hidden" name="delivery_day" value="Martes">

            <div class="bg-amber-50/80 border border-amber-200 p-3 rounded-xl flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-extrabold text-amber-800 bg-amber-200/70 px-2 py-0.5 rounded-md">Recomendada</span>
                    <h3 class="text-xs font-bold text-slate-900 mt-1">Ruta sugerida: NEZA-03</h3>
                    <p class="text-[10px] text-slate-600 font-medium">Martes • Nezahualcóyotl • A 320 m de la ruta</p>
                </div>
                <div class="w-7 h-7 bg-amber-500 text-white rounded-full flex items-center justify-center text-xs shadow-xs">✓</div>
            </div>
        </div>

        <!-- Enfriador (Visual) -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-3">
            <div class="flex justify-between items-center">
                <h2 class="text-xs font-black text-blue-950 uppercase tracking-wider flex items-center gap-1.5"><i class="fa-solid fa-snowflake text-sky-500"></i> Enfriador</h2>
                <div class="flex bg-slate-100 p-0.5 rounded-xl text-[10px] font-bold">
                    <span class="bg-blue-600 text-white px-3 py-1 rounded-lg shadow-xs">Sí</span>
                    <span class="text-slate-500 px-3 py-1">No</span>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 mb-1">No. Serie</label>
                    <input type="text" placeholder="SN-4587-20" class="w-full border border-slate-200 rounded-xl p-2 text-xs bg-slate-50">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 mb-1">Capacidad</label>
                    <input type="text" placeholder="5,000 L" class="w-full border border-slate-200 rounded-xl p-2 text-xs bg-slate-50">
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex gap-2 pt-2">
            <a href="index.php" class="flex-1 bg-slate-200 text-slate-700 text-center py-3 rounded-xl text-xs font-bold hover:bg-slate-300 transition">Cancelar</a>
            <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl text-xs shadow-md shadow-blue-500/30 transition">Guardar cliente</button>
        </div>
    </form>
</div>

<?php 
if (file_exists('footer_mobile.php')) {
    include 'footer_mobile.php';
} else {
    echo '</body></html>';
}
?>