<?php
// Limpiar cualquier búfer previo para evitar salidas de texto no deseadas
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');

if (file_exists('conexion.php')) {
    require_once 'conexion.php';
} else {
    require_once 'bd/conexion.php';
}

try {
    $sql = "SELECT r.id AS route_id, r.name AS route_name, r.color, rs.weekday, 
                   c.id AS customer_id, c.name AS customer_name, c.address, c.phone, c.lat, c.lng
            FROM routes r
            LEFT JOIN route_stops rs ON r.id = rs.route_id
            LEFT JOIN customers c ON rs.customer_id = c.id
            ORDER BY rs.weekday, r.id, rs.visit_order";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();

    echo json_encode(["success" => true, "data" => $rows], JSON_UNESCAPED_UNICODE);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Error al obtener rutas: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
