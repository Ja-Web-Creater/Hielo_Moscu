<?php
// Desactivar salida de errores en texto plano para proteger el formato JSON
@ini_set('display_errors', '0');
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$host = "localhost";
$user = "root";
$pass = "";
$db   = "hielo_moscu";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode([]);
    exit();
}

$conn->set_charset("utf8");

// Consulta general sin restricciones de columnas específicas para evitar errores 500
$sql = "SELECT * FROM clientes";
$resultado = $conn->query($sql);

$clientes = [];
if ($resultado) {
    while ($row = $resultado->fetch_assoc()) {
        $clientes[] = [
            "id_cliente"   => $row['id_cliente'] ?? '',
            "name"         => $row['nombre_local'] ?? ($row['nombre'] ?? 'Sin nombre'),
            "address"      => $row['direccion'] ?? 'Sin dirección',
            "telefono"     => $row['telefono'] ?? '',
            "latitude"     => !empty($row['latitude']) ? $row['latitude'] : 19.412530,
            "longitude"    => !empty($row['longitude']) ? $row['longitude'] : -99.018240,
            "delivery_day" => ucfirst(strtolower(trim($row['delivery_day'] ?? '')))
        ];
    }
}

echo json_encode($clientes);
$conn->close();
?>