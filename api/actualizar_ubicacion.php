<?php
require_once 'conexion.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver = trim($_POST['driver_name'] ?? 'Repartidor 1');
    $lat = $_POST['lat'] ?? 0;
    $lng = $_POST['lng'] ?? 0;

    if ($lat && $lng) {
        // Guardar o actualizar la posición actual del repartidor
        $stmt = $pdo->prepare("INSERT INTO driver_locations (driver_name, lat, lng) VALUES (:driver, :lat, :lng) 
                               ON DUPLICATE KEY UPDATE lat = :lat, lng = :lng, updated_at = NOW()");
        // Nota: Para usar ON DUPLICATE asegúrate que driver_name sea UNIQUE, o haz un UPSERT simple por ID.
        // Alternativa simple por ID/Nombre:
        $check = $pdo->prepare("SELECT id FROM driver_locations WHERE driver_name = ?");
        $check->execute([$driver]);
        if ($row = $check->fetch()) {
            $upd = $pdo->prepare("UPDATE driver_locations SET lat = ?, lng = ?, updated_at = NOW() WHERE driver_name = ?");
            $upd->execute([$lat, $lng, $driver]);
        } else {
            $ins = $pdo->prepare("INSERT INTO driver_locations (driver_name, lat, lng) VALUES (?, ?, ?)");
            $ins->execute([$driver, $lat, $lng]);
        }
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Coordenadas inválidas"]);
    }
}
?>