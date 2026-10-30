<?php
/**
 * Script para obtener todos los clientes desde MySQL - Hielo Moscú
 */
// Desactivar la visualización de errores directos para no contaminar el JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $sql = "SELECT c.*, cl.serial_number, cl.capacity 
            FROM customers c 
            LEFT JOIN coolers cl ON c.id = cl.customer_id 
            ORDER BY c.id DESC";
            
    $stmt = $pdo->query($sql);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Asegurar que siempre devuelva un array aunque esté vacío
    if (!is_array($clientes)) {
        $clientes = [];
    }

    echo json_encode($clientes, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al obtener clientes: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>