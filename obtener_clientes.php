<?php
/**
 * Script seguro para obtener clientes desde MySQL - Hielo Moscú
 */
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Consulta directa a customers para evitar errores si la tabla coolers tiene otro nombre o estructura
    $stmt = $pdo->query("SELECT * FROM customers ORDER BY id DESC");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Asegurar formato de array
    if (!is_array($clientes)) {
        $clientes = [];
    }

    echo json_encode($clientes, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error SQL: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>