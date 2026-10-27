<?php
// Limpiar cualquier búfer previo para evitar salidas de texto o espacios no deseados
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');

// Como ya está en la raíz, llamamos directamente a conexion.php
require_once 'conexion.php';

// Validar que la petición sea estrictamente POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Método no permitido."], JSON_UNESCAPED_UNICODE);
    exit;
}

// Leer el cuerpo JSON enviado desde el frontend
$input = json_decode(file_get_contents('php://input'), true);

$customer_id = isset($input['customer_id']) ? intval($input['customer_id']) : 0;
$lat         = isset($input['lat']) ? floatval($input['lat']) : 0.0;
$lng         = isset($input['lng']) ? floatval($input['lng']) : 0.0;

if ($customer_id <= 0) {
    echo json_encode(["success" => false, "error" => "ID de cliente inválido."], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Consulta SQL con signos de interrogación (?) para total compatibilidad en PDO
    $sql = "UPDATE customers SET lat = ?, lng = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$lat, $lng, $customer_id]);

    echo json_encode(["success" => true, "message" => "Ubicación actualizada correctamente."], JSON_UNESCAPED_UNICODE);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Error de base de datos: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
?>