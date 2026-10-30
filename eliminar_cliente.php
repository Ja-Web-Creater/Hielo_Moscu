<?php
/**
 * Script para eliminar un cliente de MySQL - Hielo Moscú
 */
require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = $_POST['id'] ?? null;

        if (!$id) {
            echo json_encode(["success" => false, "error" => "ID de cliente no proporcionado."], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Eliminar registros dependientes primero (enfriadores) si aplica
        $stmtCooler = $pdo->prepare("DELETE FROM coolers WHERE customer_id = ?");
        $stmtCooler->execute([$id]);

        // Eliminar el cliente
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(["success" => true, "message" => "Cliente eliminado correctamente."], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Error al eliminar: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Método no permitido."], JSON_UNESCAPED_UNICODE);
}
?>