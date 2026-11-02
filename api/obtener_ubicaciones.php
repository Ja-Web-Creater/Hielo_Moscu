<?php
require_once 'conexion.php';
header('Content-Type: application/json; charset=utf-8');

$stmt = $pdo->query("SELECT * FROM driver_locations");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
?>