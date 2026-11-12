<?php
session_start();
$conn = new mysqli("localhost", "root", "", "hielo_moscu");
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Auto-creación y blindaje de tablas con estructura fija por dispositivos
$conn->query("CREATE TABLE IF NOT EXISTS clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre_local VARCHAR(150) NOT NULL,
    telefono VARCHAR(50),
    direccion TEXT NOT NULL,
    delivery_day VARCHAR(50) DEFAULT 'Lunes',
    latitude DECIMAL(10,8) DEFAULT 19.412530,
    longitude DECIMAL(11,8) DEFAULT -99.018240
)");

$conn->query("CREATE TABLE IF NOT EXISTS repartidores (
    id_repartidor INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(50)
)");

// Insertar automáticamente los 4 dispositivos predeterminados si la tabla está vacía
$check_rep = $conn->query("SELECT COUNT(*) as total FROM repartidores");
if ($check_rep) {
    $row_rep = $check_rep->fetch_assoc();
    if ($row_rep['total'] == 0) {
        $conn->query("INSERT INTO repartidores (id_repartidor, nombre, telefono) VALUES 
            (1, 'Operador 1 (Teléfono 1)', 'Dispositivo 1'),
            (2, 'Operador 2 (Teléfono 2)', 'Dispositivo 2'),
            (3, 'Operador 3 (Teléfono 3)', 'Dispositivo 3'),
            (4, 'Operador X (Domingo)', 'Dispositivo Domingo')");
    }
}

$conn->query("CREATE TABLE IF NOT EXISTS rutas (
    id_ruta INT AUTO_INCREMENT PRIMARY KEY,
    id_repartidor INT,
    dia_semana VARCHAR(20),
    nombre_ruta VARCHAR(100)
)");

$conn->query("CREATE TABLE IF NOT EXISTS detalles_ruta (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_ruta INT,
    id_cliente INT,
    orden_entrega INT,
    estatus VARCHAR(50) DEFAULT 'Pendiente'
)");

$error_login = "";
$mensaje = "";

// Login de Oficina
if (isset($_POST['login_admin'])) {
    if (($_POST['password'] ?? '') === 'hielo123') {
        $_SESSION['admin_autenticado'] = true;
        header("Location: admin_rutas.php");
        exit;
    } else {
        $error_login = "❌ Contraseña incorrecta (hielo123).";
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_autenticado']);
    header("Location: admin_rutas.php");
    exit;
}

// Vista de Login
if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true):
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hielo Moscú - Login Oficina</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 font-sans flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200 max-w-sm w-full space-y-6 relative">
        <a href="index.php" class="absolute top-4 right-4 text-slate-400 hover:text-blue-600 transition" title="Volver al Inicio">
            <i class="fa-solid fa-house text-lg"></i>
        </a>
        <div class="text-center">
            <div class="bg-blue-600 text-white w-12 h-12 rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-sm text-lg font-bold">🧊</div>
            <h1 class="text-lg font-bold text-slate-800">Panel de Oficina</h1>
            <p class="text-xs text-slate-500 mt-1">Acceso restringido (Protegido)</p>
        </div>
        <?php if (!empty($error_login)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded-xl text-xs font-semibold text-center"><?php echo $error_login; ?></div>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Contraseña</label>
                <input type="password" name="password" required placeholder="Ej. hielo123" class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <button type="submit" name="login_admin" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl text-sm transition">Entrar al Panel</button>
        </form>
        <div class="flex justify-between items-center pt-2 border-t border-slate-100 text-xs">
            <a href="index.php" class="text-slate-600 font-semibold hover:underline"><i class="fa-solid fa-arrow-left mr-1"></i> Ir a Inicio</a>
            <a href="repartidor_ruta.php" class="text-blue-600 font-semibold hover:underline">Vista Repartidor ➔</a>
        </div>
    </div>
</body>
</html>
<?php exit; endif;

// Acciones del Panel
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] == 'editar_cliente') {
        $id = intval($_POST['id_cliente']);
        $nombre = $conn->real_escape_string($_POST['nombre_local']);
        $tel = $conn->real_escape_string($_POST['telefono']);
        $dir = $conn->real_escape_string($_POST['direccion']);
        $dia = $conn->real_escape_string($_POST['delivery_day'] ?? 'Lunes');
        
        if ($conn->query("UPDATE clientes SET nombre_local='$nombre', telefono='$tel', direccion='$dir', delivery_day='$dia' WHERE id_cliente=$id")) {
            $mensaje = "✅ ¡Cliente actualizado correctamente!";
        }
    } elseif ($_POST['accion'] == 'eliminar_cliente') {
        $id = intval($_POST['id_cliente']);
        $conn->query("DELETE FROM detalles_ruta WHERE id_cliente = $id");
        if ($conn->query("DELETE FROM clientes WHERE id_cliente = $id")) {
            $mensaje = "🗑️ ¡Cliente eliminado del sistema!";
        }
    } elseif ($_POST['accion'] == 'crear_repartidor') {
        $nombre = $conn->real_escape_string($_POST['nombre_repartidor']);
        $tel = $conn->real_escape_string($_POST['telefono_repartidor']);
        if ($conn->query("INSERT INTO repartidores (nombre, telefono) VALUES ('$nombre', '$tel')")) {
            $mensaje = "✅ ¡Dispositivo/Operador registrado con éxito!";
        }
    } elseif ($_POST['accion'] == 'asignar_ruta') {
        $id_rep = intval($_POST['id_repartidor']);
        $dia = $conn->real_escape_string($_POST['dia_semana']);
        $nom_ruta = $conn->real_escape_string($_POST['nombre_ruta']);
        $id_cli = intval($_POST['id_cliente']);
        $orden = intval($_POST['orden_entrega']);

        // Sincronizar también el día en la tabla clientes
        $conn->query("UPDATE clientes SET delivery_day = '$dia' WHERE id_cliente = $id_cli");

        // Buscar o crear la ruta para ese dispositivo y día
        $res = $conn->query("SELECT id_ruta FROM rutas WHERE id_repartidor = $id_rep AND dia_semana = '$dia' LIMIT 1");
        if ($res && $res->num_rows > 0) {
            $id_ruta = $res->fetch_assoc()['id_ruta'];
        } else {
            $conn->query("INSERT INTO rutas (id_repartidor, dia_semana, nombre_ruta) VALUES ($id_rep, '$dia', '$nom_ruta')");
            $id_ruta = $conn->insert_id;
        }

        // VALIDACIÓN ANTI-DUPLICADOS: Verificar si el cliente ya está asignado a esta ruta
        $checar_duplicado = $conn->query("SELECT id_detalle FROM detalles_ruta WHERE id_ruta = $id_ruta AND id_cliente = $id_cli");
        if ($checar_duplicado && $checar_duplicado->num_rows > 0) {
            $mensaje = "⚠️ Este cliente ya está asignado a esta ruta (evitado duplicado).";
        } else {
            if ($conn->query("INSERT INTO detalles_ruta (id_ruta, id_cliente, orden_entrega, estatus) VALUES ($id_ruta, $id_cli, $orden, 'Pendiente')")) {
                $mensaje = "✅ ¡Asignado al dispositivo/ruta correctamente!";
            }
        }
    }
}

// Obtener cliente a editar
$cliente_edit = null;
if (isset($_GET['editar'])) {
    $id_edit = intval($_GET['editar']);
    $res_edit = $conn->query("SELECT * FROM clientes WHERE id_cliente = $id_edit");
    if ($res_edit && $res_edit->num_rows > 0) {
        $cliente_edit = $res_edit->fetch_assoc();
    }
}

$clientes = $conn->query("SELECT * FROM clientes ORDER BY nombre_local ASC");
$repartidores = $conn->query("SELECT * FROM repartidores ORDER BY id_repartidor ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hielo Moscú - Oficina</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 font-sans p-4 md:p-6 pb-32">
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Header del Panel -->
        <div class="bg-blue-600 text-white p-6 rounded-2xl shadow-sm flex flex-col sm:flex-row justify-between items-center gap-4">
            <div>
                <h1 class="text-xl font-bold">🧊 Hielo Moscú - Panel de Oficina</h1>
                <p class="text-xs text-blue-100 mt-1">Gestión por Dispositivos Fijos (Operador 1, 2, 3 y X)</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="index.php" class="bg-white text-blue-600 hover:bg-blue-50 px-3 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-sm"><i class="fa-solid fa-house"></i> Inicio</a>
                <a href="agregar.php" class="bg-blue-500 hover:bg-blue-400 text-white px-3 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1"><i class="fa-solid fa-user-plus"></i> + Cliente</a>
                <a href="repartidor_ruta.php" class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1"><i class="fa-solid fa-truck"></i> Repartidor</a>
                <a href="admin_rutas.php?logout=true" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
            </div>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="bg-emerald-100 border border-emerald-300 text-emerald-800 p-4 rounded-xl text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i> <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <?php if($cliente_edit): ?>
        <!-- Formulario para Modificar Cliente -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-blue-200 space-y-4">
            <h2 class="text-base font-bold text-blue-950"><i class="fa-solid fa-pen text-blue-600 mr-2"></i> Modificar Datos del Cliente</h2>
            <form method="POST" class="space-y-3">
                <input type="hidden" name="accion" value="editar_cliente">
                <input type="hidden" name="id_cliente" value="<?php echo $cliente_edit['id_cliente']; ?>">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Nombre del Local</label>
                    <input type="text" name="nombre_local" required value="<?php echo htmlspecialchars($cliente_edit['nombre_local']); ?>" class="w-full border border-slate-300 rounded-xl p-3 text-sm bg-slate-50">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Teléfono</label>
                    <input type="text" name="telefono" value="<?php echo htmlspecialchars($cliente_edit['telefono'] ?? ''); ?>" class="w-full border border-slate-300 rounded-xl p-3 text-sm bg-slate-50">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Día de Entrega</label>
                    <select name="delivery_day" class="w-full border border-slate-300 rounded-xl p-3 text-sm bg-slate-50 font-bold">
                        <?php foreach(['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'] as $d): ?>
                            <option value="<?php echo $d; ?>" <?php echo (($cliente_edit['delivery_day'] ?? '') === $d) ? 'selected' : ''; ?>><?php echo $d; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Dirección</label>
                    <textarea name="direccion" required rows="2" class="w-full border border-slate-300 rounded-xl p-3 text-sm bg-slate-50"><?php echo htmlspecialchars($cliente_edit['direccion']); ?></textarea>
                </div>
                <div class="flex gap-2 pt-2">
                    <a href="admin_rutas.php" class="flex-1 bg-slate-200 text-slate-700 text-center py-3 rounded-xl text-sm font-bold hover:bg-slate-300 transition">Cancelar</a>
                    <button type="submit" class="flex-1 bg-blue-600 text-white font-bold py-3 rounded-xl text-sm hover:bg-blue-700 transition">Actualizar Cliente</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Registrar / Gestionar Dispositivos -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 space-y-4">
                <h2 class="text-base font-bold text-slate-800"><i class="fa-solid fa-mobile-screen text-blue-600 mr-2"></i> Registrar Dispositivo / Operador</h2>
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="accion" value="crear_repartidor">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Nombre del Dispositivo / Operador</label>
                        <input type="text" name="nombre_repartidor" required placeholder="Ej. Operador 4" class="w-full border border-slate-300 rounded-xl p-3 text-sm outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Descripción / Referencia</label>
                        <input type="text" name="telefono_repartidor" required placeholder="Ej. Teléfono Adicional" class="w-full border border-slate-300 rounded-xl p-3 text-sm outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50">
                    </div>
                    <button type="submit" class="w-full bg-amber-600 hover:bg-amber-700 text-white font-bold py-3 rounded-xl text-sm transition shadow-sm">Guardar Dispositivo</button>
                </form>
            </div>

            <!-- Asignar Cliente a Ruta por Dispositivo -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 space-y-4">
                <h2 class="text-base font-bold text-slate-800"><i class="fa-solid fa-route text-blue-600 mr-2"></i> Asignar Ruta a Dispositivo</h2>
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="accion" value="asignar_ruta">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Dispositivo Fijo</label>
                            <select name="id_repartidor" required class="w-full border border-slate-300 rounded-xl p-2.5 text-xs bg-white font-bold">
                                <option value="">-- Seleccionar --</option>
                                <?php if($repartidores) { $repartidores->data_seek(0); while($r = $repartidores->fetch_assoc()): ?>
                                    <option value="<?php echo $r['id_repartidor']; ?>"><?php echo $r['nombre']; ?></option>
                                <?php endwhile; } ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Día</label>
                            <select name="dia_semana" required class="w-full border border-slate-300 rounded-xl p-2.5 text-xs bg-white font-bold">
                                <?php foreach(['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'] as $d): ?>
                                    <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Cliente</label>
                            <select name="id_cliente" required class="w-full border border-slate-300 rounded-xl p-2.5 text-xs bg-white font-bold">
                                <option value="">-- Seleccionar --</option>
                                <?php if($clientes) { $clientes->data_seek(0); while($c = $clientes->fetch_assoc()): ?>
                                    <option value="<?php echo $c['id_cliente']; ?>"><?php echo $c['nombre_local']; ?></option>
                                <?php endwhile; } ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Orden #</label>
                            <input type="number" name="orden_entrega" value="1" min="1" required class="w-full border border-slate-300 rounded-xl p-2 text-xs bg-white font-bold">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Nombre de la Ruta</label>
                        <input type="text" name="nombre_ruta" placeholder="Ej. Ruta Operador 1" required class="w-full border border-slate-300 rounded-xl p-2.5 text-xs bg-slate-50 font-bold">
                    </div>
                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl text-sm transition shadow-sm">Asignar a Ruta</button>
                </form>
            </div>
        </div>

        <!-- Listado y Gestión de Clientes -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 space-y-4">
            <h2 class="text-base font-bold text-slate-800"><i class="fa-solid fa-address-book text-blue-600 mr-2"></i> Administración de Clientes</h2>
            <div class="overflow-x-auto max-h-80 overflow-y-auto border border-slate-100 rounded-xl">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-700 sticky top-0 border-b border-slate-200">
                        <tr>
                            <th class="p-3">Local</th>
                            <th class="p-3">Día</th>
                            <th class="p-3">Teléfono</th>
                            <th class="p-3">Dirección</th>
                            <th class="p-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php 
                        if($clientes && $clientes->num_rows > 0):
                            $clientes->data_seek(0);
                            while($c = $clientes->fetch_assoc()):
                        ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-3 font-bold text-blue-950"><?php echo htmlspecialchars($c['nombre_local']); ?></td>
                                <td class="p-3"><span class="bg-sky-100 text-sky-700 px-2 py-0.5 rounded-md font-bold text-[10px]"><?php echo htmlspecialchars($c['delivery_day'] ?? 'Sin día'); ?></span></td>
                                <td class="p-3 text-slate-600"><?php echo htmlspecialchars($c['telefono'] ?? '---'); ?></td>
                                <td class="p-3 text-slate-500 truncate max-w-xs"><?php echo htmlspecialchars($c['direccion']); ?></td>
                                <td class="p-3 text-center space-x-1">
                                    <a href="admin_rutas.php?editar=<?php echo $c['id_cliente']; ?>" class="bg-sky-100 hover:bg-sky-200 text-sky-700 px-2.5 py-1 rounded-lg font-bold transition inline-block" title="Modificar"><i class="fa-solid fa-pen"></i> Editar</a>
                                    <form method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de eliminar este cliente? Se borrará de sus rutas asignadas.');">
                                        <input type="hidden" name="accion" value="eliminar_cliente">
                                        <input type="hidden" name="id_cliente" value="<?php echo $c['id_cliente']; ?>">
                                        <button type="submit" class="bg-red-100 hover:bg-red-200 text-red-700 px-2.5 py-1 rounded-lg font-bold transition" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="5" class="p-4 text-center text-slate-400">No hay clientes registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Menú de Navegación Inferior Completo y Visible -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-slate-200 py-2 px-1 z-30 shadow-lg">
        <div class="max-w-xl mx-auto flex justify-around items-center text-center gap-0.5">
            <a href="index.php" class="flex flex-col items-center text-slate-500 hover:text-blue-600 transition">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-slate-100 text-slate-700 shadow-xs">
                    <i class="fa-solid fa-house text-xs"></i>
                </div>
                <span class="text-[8px] font-bold mt-0.5">Inicio</span>
            </a>
            <a href="clientes.php" class="flex flex-col items-center text-slate-500 hover:text-blue-600 transition">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-slate-100 text-slate-700 shadow-xs">
                    <i class="fa-solid fa-users text-xs"></i>
                </div>
                <span class="text-[8px] font-bold mt-0.5">Clientes</span>
            </a>
            <a href="rutas.php" class="flex flex-col items-center text-slate-500 hover:text-blue-600 transition">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-slate-100 text-slate-700 shadow-xs">
                    <i class="fa-solid fa-route text-xs"></i>
                </div>
                <span class="text-[8px] font-bold mt-0.5">Rutas</span>
            </a>
            <a href="agregar.php" class="flex flex-col items-center transition -mt-3">
                <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center shadow-md shadow-blue-500/40 border-2 border-white">
                    <i class="fa-solid fa-plus text-sm"></i>
                </div>
                <span class="text-[8px] font-bold mt-0.5 text-blue-600">Agregar</span>
            </a>
            <a href="admin_rutas.php" class="flex flex-col items-center text-slate-500 hover:text-blue-600 transition">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-slate-800 text-white shadow-xs">
                    <i class="fa-solid fa-lock text-xs"></i>
                </div>
                <span class="text-[8px] font-bold mt-0.5 text-slate-800">Oficina</span>
            </a>
            <a href="buscar.php" class="flex flex-col items-center text-slate-500 hover:text-blue-600 transition">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-slate-100 text-slate-700 shadow-xs">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </div>
                <span class="text-[8px] font-bold mt-0.5">Buscar</span>
            </a>
            <a href="index.php" class="flex flex-col items-center text-slate-500 hover:text-blue-600 transition">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-slate-100 text-slate-700 shadow-xs">
                    <i class="fa-solid fa-map text-xs"></i>
                </div>
                <span class="text-[8px] font-bold mt-0.5">Mapa</span>
            </a>
        </div>
    </nav>
<?php include 'footer_mobile.php'; ?>
</body>
</html>