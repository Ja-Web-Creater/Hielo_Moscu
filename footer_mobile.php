</div>
        <!-- Menú de Navegación Inferior fijo -->
        <nav class="absolute bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-slate-200 py-2.5 px-4 z-40 shadow-lg">
            <div class="flex justify-around items-center text-center">
                <a href="index.php" class="flex flex-col items-center <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-blue-600 font-bold' : 'text-slate-400 hover:text-blue-600'; ?>">
                    <i class="fa-solid fa-map text-lg"></i>
                    <span class="text-[10px] font-medium mt-0.5">Mapa</span>
                </a>
                <a href="clientes.php" class="flex flex-col items-center <?php echo basename($_SERVER['PHP_SELF']) == 'clientes.php' ? 'text-blue-600 font-bold' : 'text-slate-400 hover:text-blue-600'; ?>">
                    <i class="fa-solid fa-users text-lg"></i>
                    <span class="text-[10px] font-medium mt-0.5">Clientes</span>
                </a>
                <a href="rutas.php" class="flex flex-col items-center <?php echo basename($_SERVER['PHP_SELF']) == 'rutas.php' ? 'text-blue-600 font-bold' : 'text-slate-400 hover:text-blue-600'; ?>">
                    <i class="fa-solid fa-route text-lg"></i>
                    <span class="text-[10px] font-medium mt-0.5">Rutas</span>
                </a>
                <a href="agregar.php" class="flex flex-col items-center <?php echo basename($_SERVER['PHP_SELF']) == 'agregar.php' ? 'text-blue-600 font-bold' : 'text-slate-400 hover:text-blue-600'; ?> -mt-2">
                    <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center shadow-md shadow-blue-500/30 border-2 border-white">
                        <i class="fa-solid fa-plus text-sm"></i>
                    </div>
                    <span class="text-[10px] font-medium mt-0.5">Agregar</span>
                </a>
                <a href="admin_rutas.php" class="flex flex-col items-center <?php echo basename($_SERVER['PHP_SELF']) == 'admin_rutas.php' ? 'text-blue-600 font-bold' : 'text-slate-400 hover:text-blue-600'; ?>">
                    <i class="fa-solid fa-magnifying-glass text-lg"></i>
                    <span class="text-[10px] font-medium mt-0.5">Buscar</span>
                </a>
            </div>
        </nav>
    </div>
</body>
</html>