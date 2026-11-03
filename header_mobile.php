<?php
// Componente de diseño adaptable (Mobile Shell para PC/Tablet/Celular)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hielo Moscú</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="flex items-center justify-center p-0 sm:p-4 font-sans">
    <!-- Contenedor adaptativo: Ocupa toda la pantalla en celular, y formato tarjeta móvil elegante en Tablet y PC -->
    <div class="w-full sm:max-w-md min-h-screen sm:min-h-[85vh] sm:rounded-[3rem] bg-slate-50 shadow-2xl flex flex-col justify-between relative overflow-hidden border-0 sm:border-8 sm:border-slate-800">
        
        <!-- Contenido dinámico de cada vista -->
        <div class="flex-1 overflow-y-auto pb-28">