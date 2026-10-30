<?php
/**
 * Script para obtener todos los clientes y enfriadores desde MySQL - Hielo Moscú
 */
require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Consulta para obtener los clientes unidos con sus enfriadores si existen
    $sql = "SELECT c.*, cl.serial_number, cl.capacity 
            FROM customers c 
            LEFT JOIN coolers cl ON c.id = cl.customer_id 
            ORDER BY c.id DESC";
            
    $stmt = $pdo->query($sql);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($clientes, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al obtener clientes: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>