<?php
/**
 * Script para guardar clientes en MySQL - Hielo Moscú
 */
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Capturar y limpiar datos del formulario con valores por defecto para campos obligatorios
        $nombre = trim($_POST['nombre'] ?? '');
        $ubicacionTexto = trim($_POST['ubicacionTexto'] ?? '');
        $ruta = trim($_POST['ruta'] ?? 'Ruta 1');
        $lat = $_POST['latitud'] ?? '19.412530';
        $lng = $_POST['longitud'] ?? '-99.018240';
        $notas = trim($_POST['notas'] ?? '');
        
        // Campos requeridos por tu BD que no están en el formulario básico
        $phone = trim($_POST['phone'] ?? '0000000000'); // Teléfono por defecto si no se llena
        $contact = trim($_POST['contact'] ?? '');

        if (empty($nombre)) {
            throw new Exception("El nombre del negocio es obligatorio.");
        }

        // Insertar usando los nombres exactos de columnas de tu tabla
        $sql = "INSERT INTO customers (name, contact, phone, address, lat, lng, notes, active, route, created_at, updated_at) 
                VALUES (:name, :contact, :phone, :address, :lat, :lng, :notes, 1, :route, NOW(), NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name' => $nombre,
            ':contact' => $contact,
            ':phone' => $phone,
            ':address' => $ubicacionTexto,
            ':lat' => $lat,
            ':lng' => $lng,
            ':notes' => $notas,
            ':route' => $ruta
        ]);

        echo json_encode(["success" => true, "message" => "Cliente guardado exitosamente"]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Método no permitido"]);
}
?>