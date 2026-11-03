<?php
$conn = new mysqli("localhost", "root", "", "hielo_moscu");
if ($conn->connect_error) { die("Error de conexión: " . $conn->connect_error); }
$clientes = $conn->query("SELECT * FROM clientes ORDER BY nombre_local ASC");
include 'header_mobile.php';
?>
<div class="p-4 space-y-4">
    <!-- Header Estilo Cliente -->
    <div class="flex items-center space-x-3 pt-2">
        <div class="w-10 h-10 bg-gradient-to-tr from-blue-600 to-sky-400 rounded-2xl flex items-center justify-center text-white shadow-md">🧊</div>
        <div>
            <h1 class="text-base font-black text-blue-950 tracking-wide">HIELO MOSCÚ</h1>
            <p class="text-[11px] font-bold text-sky-600">Clientes</p>
        </div>
    </div>

    <!-- Buscador -->
    <div class="relative">
        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400 text-sm"></i>
        <input type="text" id="buscador" placeholder="Buscar cliente, negocio o zona..." class="w-full bg-white border border-slate-200 rounded-2xl pl-10 pr-4 py-3 text-xs outline-none focus:border-blue-500 shadow-xs">
    </div>

    <div class="flex justify-between items-center pt-1">
        <h2 class="text-xs font-black text-slate-800 uppercase tracking-wider">Lista de clientes</h2>
        <span class="text-[10px] font-bold text-slate-400">Ordenar por Nombre ▾</span>
    </div>

    <!-- Listado de Clientes con Etiquetas de Rutas (Estilo Imagen 1) -->
    <div id="lista-clientes" class="space-y-2.5">
        <?php if ($clientes && $clientes->num_rows > 0): ?>
            <?php while($c = $clientes->fetch_assoc()): ?>
                <div class="bg-white hover:bg-slate-50 p-3.5 rounded-2xl border border-slate-200/85 transition shadow-xs space-y-2 cliente-item" data-nombre="<?php echo strtolower($c['nombre_local'] ?? ''); ?>" data-zona="<?php echo strtolower($c['direccion'] ?? ''); ?>">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-xs font-bold text-blue-950"><?php echo htmlspecialchars($c['nombre_local'] ?? 'Sin nombre'); ?></h3>
                            <p class="text-[11px] text-slate-400 mt-0.5"><i class="fa-solid fa-location-dot text-red-500 mr-1"></i><?php echo htmlspecialchars($c['direccion'] ?? 'Sin dirección'); ?></p>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <!-- Etiquetas simuladas de rutas asignadas estilo mockup -->
                            <span class="text-[9px] font-bold bg-sky-100 text-sky-700 px-2 py-1 rounded-lg">Ruta 1</span>
                            <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($c['direccion'] ?? ''); ?>" target="_blank" class="w-7 h-7 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-full flex items-center justify-center text-xs transition" title="Ver en Maps"><i class="fa-solid fa-eye"></i></a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-12 text-slate-400 text-xs">No hay clientes registrados.</div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.getElementById('buscador').addEventListener('input', function(e) {
        const filtro = e.target.value.toLowerCase();
        document.querySelectorAll('.cliente-item').forEach(item => {
            const nombre = item.getAttribute('data-nombre');
            const zona = item.getAttribute('data-zona');
            item.style.display = (nombre.includes(filtro) || zona.includes(filtro)) ? 'block' : 'none';
        });
    });
</script>
<?php include 'footer_mobile.php'; ?>