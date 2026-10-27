<?php
/**
 * Archivo de Conexión a Base de Datos - Hielo Moscú
 * Utiliza PDO con MySQL 8, charset utf8mb4 y manejo estricto de errores.
 */

$host = 'localhost';
$dbname = 'hielo_moscu';
$username = 'root'; // Cambia por tu usuario de MySQL
$password = ''; // Cambia por tu contraseña de MySQL

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza excepciones si hay errores SQL
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve arrays asociativos por defecto
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa preparaciones nativas de MySQL para mayor seguridad
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
} catch (PDOException $e) {
    // Si es una petición AJAX/API, devolvemos JSON; si no, un mensaje limpio
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' || strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["error" => "Error de conexión a la base de datos."], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        echo "Error crítico: No se pudo establecer la conexión con la base de datos.";
    }
    exit;
}
?>