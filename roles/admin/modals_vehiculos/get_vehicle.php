<?php
session_start();
require_once('../../../conecct/conex.php');
include '../../../includes/validarsession.php';

// Establecer el tipo de contenido como JSON
header('Content-Type: application/json');

$db = new Database();
$con = $db->conectar();

// Validar que existe una sesión activa
if (!isset($_SESSION['documento'])) {
    echo json_encode(['success' => false, 'redirect' => '../../login.php']);
    exit;
}

if (!$con) {
    echo json_encode(['success' => false, 'message' => 'No se pudo conectar a la base de datos']);
    exit;
}

$placa = $_GET['placa'] ?? $_GET['id'] ?? ''; // Aceptar ambos parámetros
if (!$placa) {
    echo json_encode(['success' => false, 'message' => 'Placa no proporcionada']);
    exit;
}

try {
    $query = $con->prepare("SELECT v.*, m.nombre_marca, e.estado 
                           FROM vehiculos v 
                           LEFT JOIN marca m ON v.id_marca = m.id_marca 
                           LEFT JOIN estado_vehiculo e ON v.id_estado = e.id_estado 
                           WHERE v.placa = :placa");
    $query->bindParam(':placa', $placa, PDO::PARAM_STR);
    $query->execute();
    $vehicle = $query->fetch(PDO::FETCH_ASSOC);

    if ($vehicle) {
        echo json_encode(['success' => true, 'vehicle' => $vehicle]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Vehículo no encontrado']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la consulta: ' . $e->getMessage()]);
}
?>