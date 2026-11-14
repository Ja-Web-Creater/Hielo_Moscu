<?php
require_once 'seguridad.php';

$conn = new mysqli("localhost", "root", "", "hielo_moscu");
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Tabla ligera para registrar qué chofer y camión usan el dispositivo hoy
$conn->query("CREATE TABLE IF NOT EXISTS bitacora_turnos (
    id_turno INT AUTO_INCREMENT PRIMARY KEY,
    id_repartidor INT NOT NULL,
    nombre_chofer VARCHAR(100) NOT NULL,
    camion_unidad VARCHAR(50) NOT NULL,
    fecha_turno DATE NOT NULL
)");

// Validar token CSRF en peticiones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
}

$dias_validos = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

// Cargar únicamente los 3 dispositivos principales (Operador 1, 2 y 3)
$repartidores = $conn->query("SELECT * FROM repartidores WHERE id_repartidor IN (1, 2, 3) ORDER BY id_repartidor ASC");

$id_rep_raw = $_GET['id_repartidor'] ?? '';
$id_rep_sel = in_array(intval($id_rep_raw), [1, 2, 3], true) ? intval($id_rep_raw) : '';
$dia_sel = in_array($_GET['dia'] ?? '', $dias_validos, true) ? $_GET['dia'] : 'Lunes';
$mensaje = '';

// Registrar turno del día si envían el formulario de chofer/camión
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar_turno'])) {
    $id_rep = intval($_POST['id_repartidor']);
    $chofer = trim($_POST['nombre_chofer'] ?? '');
    $camion = trim($_POST['camion_unidad'] ?? '');
    $dia_post = in_array($_POST['dia_actual'] ?? '', $dias_validos, true) ? $_POST['dia_actual'] : 'Lunes';
    $hoy = date('Y-m-d');

    if (in_array($id_rep, [1, 2, 3], true) && $chofer !== '' && $camion !== '') {
        // Guardar o actualizar el turno de hoy para este dispositivo
        $stmt_del = $conn->prepare("DELETE FROM bitacora_turnos WHERE id_repartidor = ? AND fecha_turno = ?");
        $stmt_del->bind_param("is", $id_rep, $hoy);
        $stmt_del->execute();
        $stmt_del->close();

        $stmt_ins = $conn->prepare("INSERT INTO bitacora_turnos (id_repartidor, nombre_chofer, camion_unidad, fecha_turno) VALUES (?, ?, ?, ?)");
        $stmt_ins->bind_param("isss", $id_rep, $chofer, $camion, $hoy);
        $stmt_ins->execute();
        $stmt_ins->close();
    }
    
    header("Location: repartidor_ruta.php?id_repartidor=" . $id_rep . "&dia=" . urlencode($dia_post));
    exit;
}

// Marcar entrega como completada
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_detalle'])) {
    $id_det = intval($_POST['id_detalle']);
    if ($id_det > 0) {
        $stmt_ent = $conn->prepare("UPDATE detalles_ruta SET estatus = 'Entregado' WHERE id_detalle = ?");
        $stmt_ent->bind_param("i", $id_det);
        if ($stmt_ent->execute()) {
            $mensaje = "✅ ¡Entrega completada!";
        }
        $stmt_ent->close();
    }
}

// Obtener turno activo de hoy si existe
$turno_actual = null;
if (!empty($id_rep_sel)) {
    $hoy = date('Y-m-d');
    $stmt_t = $conn->prepare("SELECT * FROM bitacora_turnos WHERE id_repartidor = ? AND fecha_turno = ? LIMIT 1");
    $stmt_t->bind_param("is", $id_rep_sel, $hoy);
    $stmt_t->execute();
    $res_t = $stmt_t->get_result();
    if ($res_t && $res_t->num_rows > 0) {
        $turno_actual = $res_t->fetch_assoc();
    }
    $stmt_t->close();
}

$entregas = null;
if (!empty($id_rep_sel)) {
    $stmt_e = $conn->prepare("SELECT dr.id_detalle, dr.orden_entrega, dr.estatus, c.nombre_local, c.telefono, c.direccion, r.nombre_ruta 
        FROM detalles_ruta dr
        INNER JOIN rutas r ON dr.id_ruta = r.id_ruta
        INNER JOIN clientes c ON dr.id_cliente = c.id_cliente
        WHERE r.id_repartidor = ? AND r.dia_semana = ?
        ORDER BY dr.orden_entrega ASC");
    $stmt_e->bind_param("is", $id_rep_sel, $dia_sel);
    $stmt_e->execute();
    $entregas = $stmt_e->get_result();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hielo Moscú - Repartidor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 font-sans p-4 max-w-md mx-auto pb-24">
    <div class="space-y-4">
        <div class="bg-blue-600 text-white p-5 rounded-2xl shadow-sm flex justify-between items-center">
            <div>
                <h1 class="text-base font-bold"><i class="fa-solid fa-truck-fast mr-2"></i>Vista Repartidor</h1>
                <p class="text-[11px] text-blue-100">Control por Dispositivo Fijo</p>
            </div>
            <a href="admin_rutas.php" class="bg-slate-700 hover:bg-slate-600 text-white px-3 py-1.5 rounded-xl text-xs font-semibold"><i class="fa-solid fa-lock"></i> Oficina</a>
        </div>

        <!-- Selector de Dispositivo y Día -->
        <form method="GET" class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 space-y-3">
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Dispositivo / Teléfono</label>
                <select name="id_repartidor" required onchange="this.form.submit()" class="w-full border border-slate-300 rounded-xl p-2.5 text-sm bg-white font-bold">
                    <option value="">-- Selecciona tu dispositivo --</option>
                    <?php if($repartidores) { while($r = $repartidores->fetch_assoc()): ?>
                        <option value="<?php echo intval($r['id_repartidor']); ?>" <?php echo ($id_rep_sel == $r['id_repartidor']) ? 'selected' : ''; ?>><?php echo e($r['nombre']); ?></option>
                    <?php endwhile; } ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Día de la Semana</label>
                <select name="dia" required class="w-full border border-slate-300 rounded-xl p-2.5 text-sm bg-white">
                    <?php foreach($dias_validos as $d): ?>
                        <option value="<?php echo e($d); ?>" <?php echo ($dia_sel == $d) ? 'selected' : ''; ?>><?php echo e($d); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-xl text-sm transition shadow-sm">Cargar Mis Entregas</button>
        </form>

        <?php if (!empty($id_rep_sel)): ?>
            <!-- Registro Dinámico de Chofer y Camión del Día -->
            <div class="bg-amber-50 border border-amber-200 p-4 rounded-2xl shadow-sm space-y-3">
                <div class="flex justify-between items-center">
                    <h2 class="text-xs font-bold text-amber-900"><i class="fa-solid fa-id-card mr-1"></i> Turno de Hoy</h2>
                    <?php if($turno_actual): ?>
                        <span class="bg-amber-200 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-md">Registrado ✅</span>
                    <?php endif; ?>
                </div>
                <form method="POST" class="space-y-2">
                    <?php echo campo_csrf(); ?>
                    <input type="hidden" name="registrar_turno" value="1">
                    <input type="hidden" name="id_repartidor" value="<?php echo intval($id_rep_sel); ?>">
                    <input type="hidden" name="dia_actual" value="<?php echo e($dia_sel); ?>">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-0.5">Nombre del Operador</label>
                            <input type="text" name="nombre_chofer" required placeholder="Ingresa tu nombre" value="<?php echo e($turno_actual['nombre_chofer'] ?? ''); ?>" class="w-full border border-amber-300 rounded-xl p-2 text-xs bg-white font-semibold">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-0.5">Camión / Unidad</label>
                            <input type="text" name="camion_unidad" required placeholder="Número de unidad" value="<?php echo e($turno_actual['camion_unidad'] ?? ''); ?>" class="w-full border border-amber-300 rounded-xl p-2 text-xs bg-white font-semibold">
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-amber-600 hover:bg-amber-700 text-white font-bold py-2 rounded-xl text-xs transition">Guardar Operador y Unidad de Hoy</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if (!empty($mensaje)): ?>
            <div class="bg-emerald-100 border border-emerald-300 text-emerald-800 p-3 rounded-xl text-xs font-semibold"><?php echo e($mensaje); ?></div>
        <?php endif; ?>

        <?php if (!empty($id_rep_sel)): ?>
            <div class="space-y-3">
                <h2 class="text-xs font-bold text-slate-700">Entregas para el día: <span class="text-blue-600"><?php echo e($dia_sel); ?></span></h2>
                <?php if ($entregas && $entregas->num_rows > 0): ?>
                    <?php while($row = $entregas->fetch_assoc()): 
                        $completado = ($row['estatus'] == 'Entregado');
                        $maps_url = "https://www.google.com/maps/search/?api=1&query=" . urlencode($row['direccion']);
                    ?>
                        <div class="bg-white p-4 rounded-2xl shadow-sm border <?php echo $completado ? 'border-emerald-300 bg-emerald-50/40' : 'border-slate-200'; ?>">
                            <div class="flex justify-between items-start mb-1">
                                <span class="bg-blue-100 text-blue-700 text-[10px] font-bold px-2 py-0.5 rounded-md">#<?php echo intval($row['orden_entrega']); ?> - Ruta: <?php echo e($row['nombre_ruta']); ?></span>
                                <span class="text-[11px] font-semibold <?php echo $completado ? 'text-emerald-600' : 'text-amber-600'; ?>"><?php echo e($row['estatus']); ?></span>
                            </div>
                            <h3 class="font-bold text-slate-800 text-sm"><?php echo e($row['nombre_local']); ?></h3>
                            <p class="text-xs text-slate-600 mt-1"><i class="fa-solid fa-location-dot text-red-500 mr-1"></i> <?php echo e($row['direccion']); ?></p>
                            <?php if (!empty($row['telefono'])): ?>
                                <p class="text-xs text-slate-600 mt-1"><i class="fa-solid fa-phone text-emerald-600 mr-1"></i> <a href="tel:<?php echo e($row['telefono']); ?>" class="text-blue-600 font-semibold underline"><?php echo e($row['telefono']); ?></a></p>
                            <?php endif; ?>
                            <div class="mt-3 pt-3 border-t border-slate-100 flex gap-2">
                                <a href="<?php echo e($maps_url); ?>" target="_blank" rel="noopener noreferrer" class="flex-1 bg-amber-500 hover:bg-amber-600 text-white text-center py-2 rounded-xl text-xs font-bold flex items-center justify-center gap-1"><i class="fa-solid fa-map-location-dot"></i> Maps</a>
                                <?php if (!$completado): ?>
                                    <form method="POST" class="flex-1">
                                        <?php echo campo_csrf(); ?>
                                        <input type="hidden" name="id_detalle" value="<?php echo intval($row['id_detalle']); ?>">
                                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-2 rounded-xl text-xs font-bold"><i class="fa-solid fa-check"></i> Entregado</button>
                                    </form>
                                <?php else: ?>
                                    <div class="flex-1 bg-slate-200 text-slate-500 text-center py-2 rounded-xl text-xs font-bold flex items-center justify-center gap-1"><i class="fa-solid fa-check-double"></i> Listo</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="bg-white p-6 rounded-2xl shadow-sm text-center text-slate-400 text-xs">No hay entregas registradas para este dispositivo en este día.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>