<?php
// Componente adaptativo universal: Ancho completo en PC/Tablet, compacto en móvil
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
            background-color: #f1f5f9;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body class="font-sans flex flex-col min-h-screen">
    <!-- Contenedor principal que se expande por completo en PC y tablets -->
    <div class="w-full flex-1 flex flex-col justify-between relative pb-28">
        <div class="w-full flex-1">