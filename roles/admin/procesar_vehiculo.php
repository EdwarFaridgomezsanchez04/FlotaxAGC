<?php
session_start();
require_once('../../conecct/conex.php');
include '../../includes/validarsession.php';

// Establecer el tipo de contenido como JSON
header('Content-Type: application/json');

$db = new Database();
$con = $db->conectar();

// Validar que existe una sesión activa
if (!isset($_SESSION['documento'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

// Validar que el método de la petición sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        case 'agregar':
            // Validar campos requeridos
            $campos_requeridos = ['tipo_vehiculo', 'id_marca', 'placa', 'modelo', 'kilometraje_actual', 'id_estado', 'documento'];
            foreach ($campos_requeridos as $campo) {
                if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
                    echo json_encode(['success' => false, 'message' => "El campo $campo es requerido"]);
                    exit;
                }
            }
            
            // Verificar que la placa no exista
            $check_placa = $con->prepare("SELECT placa FROM vehiculos WHERE placa = :placa");
            $check_placa->bindParam(':placa', $_POST['placa']);
            $check_placa->execute();
            
            if ($check_placa->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'La placa ya existe en el sistema']);
                exit;
            }
            
            // Procesar imagen si se subió
            $foto_vehiculo = null;
            if (isset($_FILES['foto_vehiculo']) && $_FILES['foto_vehiculo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../roles/usuario/vehiculos/listar/guardar_foto_vehiculo/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['foto_vehiculo']['name'], PATHINFO_EXTENSION);
                $foto_vehiculo = $_POST['placa'] . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $foto_vehiculo;
                
                if (!move_uploaded_file($_FILES['foto_vehiculo']['tmp_name'], $upload_path)) {
                    echo json_encode(['success' => false, 'message' => 'Error al subir la imagen']);
                    exit;
                }
            }
            
            // Insertar vehículo en la base de datos
            $sql = "INSERT INTO vehiculos (tipo_vehiculo, id_marca, placa, modelo, kilometraje_actual, id_estado, Documento, foto_vehiculo, fecha_registro) 
                    VALUES (:tipo_vehiculo, :id_marca, :placa, :modelo, :kilometraje, :estado, :documento, :foto_vehiculo, NOW())";
            
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':tipo_vehiculo', $_POST['tipo_vehiculo']);
            $stmt->bindParam(':id_marca', $_POST['id_marca']);
            $stmt->bindParam(':placa', $_POST['placa']);
            $stmt->bindParam(':modelo', $_POST['modelo']);
            $stmt->bindParam(':kilometraje', $_POST['kilometraje_actual']);
            $stmt->bindParam(':estado', $_POST['id_estado']);
            $stmt->bindParam(':documento', $_POST['documento']);
            $stmt->bindParam(':foto_vehiculo', $foto_vehiculo);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Vehículo agregado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al guardar el vehículo']);
            }
            break;
            
        case 'editar':
            // Código para editar vehículo (mantener el existente)
            break;
            
        case 'eliminar':
            // Código para eliminar vehículo (mantener el existente)
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>