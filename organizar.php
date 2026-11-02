<?php
// 1. Crear las carpetas si no existen
$carpetas = ['api', 'css', 'js', 'assets'];
foreach ($carpetas as $c) {
    if (!is_dir($c)) {
        mkdir($c, 0777, true);
        echo "Carpeta creada: $c<br>";
    }
}

// 2. Archivos de backend que se moverán a la carpeta api/
$archivos = [
    'conexion.php',
    'guardar_cliente.php',
    'eliminar_cliente.php',
    'actualizar_ubicacion.php',
    'api_actualizar_ubicacion.php',
    'api_enfriadores.php',
    'api_rutas.php',
    'obtener_clientes.php',
    'obtener_ubicaciones.php'
];

echo "<h3>Movienodo archivos a /api:</h3>";
foreach ($archivos as $archivo) {
    if (file_exists($archivo)) {
        if (rename($archivo, 'api/' . $archivo)) {
            echo "✅ Movido con éxito: <b>$archivo</b> ➔ <code>api/$archivo</code><br>";
        } else {
            echo "❌ No se pudo mover: $archivo<br>";
        }
    } else {
        echo "⚠️ El archivo no existe o ya fue movido: $archivo<br>";
    }
}

echo "<h2 style='color: green;'>¡Listo! Proyecto organizado correctamente. Ya puedes borrar este archivo (organizar.php).</h2>";
?>