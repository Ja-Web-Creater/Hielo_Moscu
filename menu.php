<?php
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<!-- Menú de Navegación Inferior Unificado -->
<nav class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-slate-200 py-2 px-1 z-50 shadow-lg">
    <div class="max-w-xl mx-auto flex justify-around items-center text-center gap-0.5">
        
        <!-- 1. Inicio -->
        <a href="index.php" class="flex flex-col items-center transition <?php echo ($pagina_actual == 'index.php') ? 'text-blue-600' : 'text-slate-500 hover:text-blue-600'; ?>">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center shadow-xs <?php echo ($pagina_actual == 'index.php') ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'; ?>">
                <i class="fa-solid fa-house text-xs"></i>
            </div>
            <span class="text-[8px] font-bold mt-0.5">Inicio</span>
        </a>

        <!-- 2. Clientes -->
        <a href="clientes.php" class="flex flex-col items-center transition <?php echo ($pagina_actual == 'clientes.php') ? 'text-blue-600' : 'text-slate-500 hover:text-blue-600'; ?>">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center shadow-xs <?php echo ($pagina_actual == 'clientes.php') ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'; ?>">
                <i class="fa-solid fa-users text-xs"></i>
            </div>
            <span class="text-[8px] font-bold mt-0.5">Clientes</span>
        </a>

        <!-- 3. Rutas -->
        <a href="rutas.php" class="flex flex-col items-center transition <?php echo ($pagina_actual == 'rutas.php') ? 'text-blue-600' : 'text-slate-500 hover:text-blue-600'; ?>">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center shadow-xs <?php echo ($pagina_actual == 'rutas.php') ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'; ?>">
                <i class="fa-solid fa-route text-xs"></i>
            </div>
            <span class="text-[8px] font-bold mt-0.5">Rutas</span>
        </a>

        <!-- 4. Agregar -->
        <a href="agregar.php" class="flex flex-col items-center text-blue-600 hover:opacity-90 transition">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-blue-600 text-white shadow-xs">
                <i class="fa-solid fa-plus text-xs"></i>
            </div>
            <span class="text-[8px] font-bold mt-0.5">Agregar</span>
        </a>

        <!-- 5. Repartidor -->
        <a href="repartidor_ruta.php" class="flex flex-col items-center text-amber-600 hover:opacity-90 transition">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-amber-500 text-white shadow-xs">
                <i class="fa-solid fa-truck text-xs"></i>
            </div>
            <span class="text-[8px] font-bold mt-0.5">Repartidor</span>
        </a>

        <!-- 6. Oficina -->
        <a href="admin_rutas.php" class="flex flex-col items-center text-slate-800 hover:opacity-90 transition">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-slate-800 text-white shadow-xs">
                <i class="fa-solid fa-lock text-xs"></i>
            </div>
            <span class="text-[8px] font-bold mt-0.5">Oficina</span>
        </a>

        <!-- 7. Buscar -->
        <a href="buscar.php" class="flex flex-col items-center transition <?php echo ($pagina_actual == 'buscar.php') ? 'text-blue-600' : 'text-slate-500 hover:text-blue-600'; ?>">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center shadow-xs <?php echo ($pagina_actual == 'buscar.php') ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'; ?>">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </div>
            <span class="text-[8px] font-bold mt-0.5">Buscar</span>
        </a>

    </div>
</nav>