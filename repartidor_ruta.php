<?php
$conn = new mysqli("localhost", "root", "", "hielo_moscu");
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$repartidores = $conn->query("SELECT * FROM repartidores ORDER BY nombre ASC");
$id_rep_sel = $_GET['id_repartidor'] ?? '';
$dia_sel = $_GET['dia'] ?? 'Lunes';
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_detalle'])) {
    $id_det = intval($_POST['id_detalle']);
    $conn->query("UPDATE detalles_ruta SET estatus = 'Entregado' WHERE id_detalle = $id_det");
    $mensaje = "✅ ¡Entrega completada!";
}

$entregas = null;
if (!empty($id_rep_sel)) {
    $entregas = $conn->query("SELECT dr.id_detalle, dr.orden_entrega, dr.estatus, c.nombre_local, c.telefono, c.direccion, r.nombre_ruta 
        FROM detalles_ruta dr
        INNER JOIN rutas r ON dr.id_ruta = r.id_ruta
        INNER JOIN clientes c ON dr.id_cliente = c.id_cliente
        WHERE r.id_repartidor = $id_rep_sel AND r.dia_semana = '$dia_sel'
        ORDER BY dr.orden_entrega ASC");
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
<body class="bg-slate-100 font-sans p-4 max-w-md mx-auto">
    <div class="space-y-4">
        <div class="bg-blue-600 text-white p-5 rounded-2xl shadow-sm flex justify-between items-center">
            <div>
                <h1 class="text-base font-bold"><i class="fa-solid fa-truck-fast mr-2"></i>Vista Repartidor</h1>
                <p class="text-[11px] text-blue-100">Selecciona tu ruta diaria</p>
            </div>
            <a href="admin_rutas.php" class="bg-slate-700 hover:bg-slate-600 text-white px-3 py-1.5 rounded-xl text-xs font-semibold"><i class="fa-solid fa-lock"></i> Oficina</a>
        </div>

        <form method="GET" class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 space-y-3">
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Repartidor</label>
                <select name="id_repartidor" required class="w-full border border-slate-300 rounded-xl p-2.5 text-sm bg-white">
                    <option value="">-- Selecciona tu nombre --</option>
                    <?php while($r = $repartidores->fetch_assoc()): ?>
                        <option value="<?php echo $r['id_repartidor']; ?>" <?php echo ($id_rep_sel == $r['id_repartidor']) ? 'selected' : ''; ?>><?php echo $r['nombre']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Día de la Semana</label>
                <select name="dia" required class="w-full border border-slate-300 rounded-xl p-2.5 text-sm bg-white">
                    <?php foreach(['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'] as $d): ?>
                        <option value="<?php echo $d; ?>" <?php echo ($dia_sel == $d) ? 'selected' : ''; ?>><?php echo $d; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-xl text-sm transition">Cargar Mis Entregas</button>
        </form>

        <?php if (!empty($mensaje)): ?>
            <div class="bg-emerald-100 border border-emerald-300 text-emerald-800 p-3 rounded-xl text-xs font-semibold"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <?php if (!empty($id_rep_sel)): ?>
            <div class="space-y-3">
                <h2 class="text-xs font-bold text-slate-700">Entregas para el día: <span class="text-blue-600"><?php echo $dia_sel; ?></span></h2>
                <?php if ($entregas && $entregas->num_rows > 0): ?>
                    <?php while($row = $entregas->fetch_assoc()): 
                        $completado = ($row['estatus'] == 'Entregado');
                        $maps_url = "https://www.google.com/maps/search/?api=1&query=" . urlencode($row['direccion']);
                    ?>
                        <div class="bg-white p-4 rounded-2xl shadow-sm border <?php echo $completado ? 'border-emerald-300 bg-emerald-50/40' : 'border-slate-200'; ?>">
                            <div class="flex justify-between items-start mb-1">
                                <span class="bg-blue-100 text-blue-700 text-[10px] font-bold px-2 py-0.5 rounded-md">#<?php echo $row['orden_entrega']; ?></span>
                                <span class="text-[11px] font-semibold <?php echo $completado ? 'text-emerald-600' : 'text-amber-600'; ?>"><?php echo $row['estatus']; ?></span>
                            </div>
                            <h3 class="font-bold text-slate-800 text-sm"><?php echo $row['nombre_local']; ?></h3>
                            <p class="text-xs text-slate-600 mt-1"><i class="fa-solid fa-location-dot text-red-500 mr-1"></i> <?php echo $row['direccion']; ?></p>
                            <?php if (!empty($row['telefono'])): ?>
                                <p class="text-xs text-slate-600 mt-1"><i class="fa-solid fa-phone text-emerald-600 mr-1"></i> <a href="tel:<?php echo $row['telefono']; ?>" class="text-blue-600 font-semibold underline"><?php echo $row['telefono']; ?></a></p>
                            <?php endif; ?>
                            <div class="mt-3 pt-3 border-t border-slate-100 flex gap-2">
                                <a href="<?php echo $maps_url; ?>" target="_blank" class="flex-1 bg-amber-500 hover:bg-amber-600 text-white text-center py-2 rounded-xl text-xs font-bold flex items-center justify-center gap-1"><i class="fa-solid fa-map-location-dot"></i> Maps</a>
                                <?php if (!$completado): ?>
                                    <form method="POST" class="flex-1">
                                        <input type="hidden" name="id_detalle" value="<?php echo $row['id_detalle']; ?>">
                                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-2 rounded-xl text-xs font-bold"><i class="fa-solid fa-check"></i> Entregado</button>
                                    </form>
                                <?php else: ?>
                                    <div class="flex-1 bg-slate-200 text-slate-500 text-center py-2 rounded-xl text-xs font-bold flex items-center justify-center gap-1"><i class="fa-solid fa-check-double"></i> Listo</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="bg-white p-6 rounded-2xl shadow-sm text-center text-slate-400 text-xs">No hay entregas registradas para este día.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>