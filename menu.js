document.addEventListener("DOMContentLoaded", function() {
    // Eliminar cualquier barra de navegación vieja que estorbe en la página actual
    const barrasViejas = document.querySelectorAll("nav.fixed, .nav-inferior");
    barrasViejas.forEach(b => b.remove());

    // Crear la barra unificada definitiva con los 7 botones exactos
    const nav = document.createElement("nav");
    nav.className = "fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-slate-200 py-2 px-1 z-50 shadow-lg";
    
    nav.innerHTML = `
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
            <a href="agregar.php" class="flex flex-col items-center text-slate-500 hover:text-blue-600 transition">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-blue-600 text-white shadow-xs">
                    <i class="fa-solid fa-plus text-xs"></i>
                </div>
                <span class="text-[8px] font-bold mt-0.5 text-blue-600">Agregar</span>
            </a>
            <a href="repartidor_ruta.php" class="flex flex-col items-center text-slate-500 hover:text-amber-600 transition">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-amber-500 text-white shadow-xs">
                    <i class="fa-solid fa-truck text-xs"></i>
                </div>
                <span class="text-[8px] font-bold mt-0.5 text-amber-600">Repartidor</span>
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
        </div>
    `;

    document.body.appendChild(nav);
    document.body.style.paddingBottom = "5rem"; // Evita que la barra tape contenido
});