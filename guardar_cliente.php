<?php
/**
 * Script para procesar y guardar clientes en MySQL - Hielo Moscú
 */
require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Recoger datos enviados desde el formulario
        $nombre = $_POST['nombre'] ?? '';
        $direccion = $_POST['ubicacionTexto'] ?? '';
        $ruta = $_POST['ruta'] ?? 'Ruta 1';
        $dia = $_POST['dia'] ?? 'Lunes';
        $serie = $_POST['serie'] ?? '';
        $capacidad = $_POST['capacidad'] ?? '';
        $notas = $_POST['notas'] ?? '';
        $lat = $_POST['latitud'] ?? 19.412530;
        $lng = $_POST['longitud'] ?? -99.018240;

        if (empty($nombre) || empty($direccion)) {
            echo json_encode(["success" => false, "error" => "El nombre y la ubicación son obligatorios."], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Insertar en la tabla de clientes (ajusta los nombres de columnas según tu DBeaver si es necesario)
        $sql = "INSERT INTO customers (name, address, route, delivery_day, notes, latitude, longitude, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $direccion, $ruta, $dia, $notas, $lat, $lng]);
        
        $customerId = $pdo->lastInsertId();

        // Si tiene enfriador registrado, lo guardamos en su respectiva tabla
        if (!empty($serie) || !empty($capacidad)) {
            $sqlCooler = "INSERT INTO coolers (customer_id, serial_number, capacity, status) VALUES (?, ?, ?, 'Activo')";
            $stmtCooler = $pdo->prepare($sqlCooler);
            $stmtCooler->execute([$customerId, $serie, $capacidad]);
        }

        echo json_encode(["success" => true, "message" => "Cliente guardado exitosamente en MySQL."], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Error en BD: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Método no permitido."], JSON_UNESCAPED_UNICODE);
}
?>