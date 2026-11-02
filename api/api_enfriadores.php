<?php
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
    $sql = "SELECT co.id, co.customer_id, c.name AS customer_name, co.brand, co.serial, co.ownership, co.status
            FROM coolers co
            LEFT JOIN customers c ON co.customer_id = c.id";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();

    echo json_encode(["success" => true, "data" => $rows], JSON_UNESCAPED_UNICODE);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Error al obtener enfriadores: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
