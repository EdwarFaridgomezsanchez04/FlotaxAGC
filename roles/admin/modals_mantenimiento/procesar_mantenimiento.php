<?php
// Inicializar sesión para validar el usuario autenticado
session_start();

// Incluir archivos necesarios para la conexión a la base de datos y validación de sesión
require_once('../../../conecct/conex.php');
include '../../../includes/validarsession.php';

// Establecer el tipo de contenido como JSON para las respuestas
header('Content-Type: application/json');

// Crear instancia de la base de datos y obtener la conexión
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

// Obtener la acción a realizar desde los datos POST
$accion = $_POST['accion'] ?? '';

try {
    // Switch para manejar las diferentes acciones (agregar, editar, eliminar)
    switch ($accion) {
        case 'agregar':
            // Obtener y sanitizar los datos del formulario para agregar mantenimiento
            $placa = $_POST['placa'] ?? '';
            $id_tipo_mantenimiento = $_POST['id_tipo_mantenimiento'] ?? '';
            $fecha_programada = $_POST['fecha_programada'] ?? '';
            // Si fecha_realizada está vacía, asignar null
            $fecha_realizada = !empty($_POST['fecha_realizada']) ? $_POST['fecha_realizada'] : null;
            $observaciones = $_POST['observaciones'] ?? null;
            // Convertir a entero si no está vacío, sino asignar null
            $kilometraje_actual = !empty($_POST['kilometraje_actual']) ? (int)$_POST['kilometraje_actual'] : null;
            $proximo_cambio_km = !empty($_POST['proximo_cambio_km']) ? (int)$_POST['proximo_cambio_km'] : null;
            $proximo_cambio_fecha = !empty($_POST['proximo_cambio_fecha']) ? $_POST['proximo_cambio_fecha'] : null;
            
            // Validar que los campos obligatorios no estén vacíos
            if (empty($placa) || empty($id_tipo_mantenimiento) || empty($fecha_programada)) {
                echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios']);
                exit;
            }
            
            // Verificar que la placa del vehículo existe en la base de datos
            $check_placa = $con->prepare("SELECT placa FROM vehiculos WHERE placa = :placa");
            $check_placa->bindParam(':placa', $placa);
            $check_placa->execute();
            
            if ($check_placa->rowCount() === 0) {
                echo json_encode(['success' => false, 'message' => 'La placa del vehículo no existe']);
                exit;
            }
            
            // Preparar consulta para insertar el nuevo mantenimiento
            $query = $con->prepare("
                INSERT INTO mantenimiento 
                (placa, id_tipo_mantenimiento, fecha_programada, fecha_realizada, observaciones, 
                 kilometraje_actual, proximo_cambio_km, proximo_cambio_fecha) 
                VALUES 
                (:placa, :id_tipo_mantenimiento, :fecha_programada, :fecha_realizada, :observaciones, 
                 :kilometraje_actual, :proximo_cambio_km, :proximo_cambio_fecha)
            ");
            
            // Vincular parámetros para prevenir inyección SQL
            $query->bindParam(':placa', $placa);
            $query->bindParam(':id_tipo_mantenimiento', $id_tipo_mantenimiento);
            $query->bindParam(':fecha_programada', $fecha_programada);
            $query->bindParam(':fecha_realizada', $fecha_realizada);
            $query->bindParam(':observaciones', $observaciones);
            $query->bindParam(':kilometraje_actual', $kilometraje_actual);
            $query->bindParam(':proximo_cambio_km', $proximo_cambio_km);
            $query->bindParam(':proximo_cambio_fecha', $proximo_cambio_fecha);
            
            // Ejecutar la consulta y enviar respuesta JSON
            if ($query->execute()) {
                echo json_encode(['success' => true, 'message' => 'Mantenimiento agregado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al agregar el mantenimiento']);
            }
            break;
            
        case 'editar':
            // Obtener y sanitizar los datos del formulario para editar mantenimiento
            $id_mantenimiento = $_POST['id_mantenimiento'] ?? '';
            $placa = $_POST['placa'] ?? '';
            $id_tipo_mantenimiento = $_POST['id_tipo_mantenimiento'] ?? '';
            $fecha_programada = $_POST['fecha_programada'] ?? '';
            $fecha_realizada = !empty($_POST['fecha_realizada']) ? $_POST['fecha_realizada'] : null;
            $observaciones = $_POST['observaciones'] ?? null;
            $kilometraje_actual = !empty($_POST['kilometraje_actual']) ? (int)$_POST['kilometraje_actual'] : null;
            $proximo_cambio_km = !empty($_POST['proximo_cambio_km']) ? (int)$_POST['proximo_cambio_km'] : null;
            $proximo_cambio_fecha = !empty($_POST['proximo_cambio_fecha']) ? $_POST['proximo_cambio_fecha'] : null;
            
            // Validar que los campos obligatorios no estén vacíos (incluyendo ID para edición)
            if (empty($id_mantenimiento) || empty($placa) || empty($id_tipo_mantenimiento) || empty($fecha_programada)) {
                echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios']);
                exit;
            }
            
            // Verificar que el mantenimiento a editar existe en la base de datos
            $check_mant = $con->prepare("SELECT id_mantenimiento FROM mantenimiento WHERE id_mantenimiento = :id");
            $check_mant->bindParam(':id', $id_mantenimiento);
            $check_mant->execute();
            
            if ($check_mant->rowCount() === 0) {
                echo json_encode(['success' => false, 'message' => 'El mantenimiento no existe']);
                exit;
            }
            
            // Preparar consulta para actualizar el mantenimiento existente
            $query = $con->prepare("
                UPDATE mantenimiento SET 
                placa = :placa, 
                id_tipo_mantenimiento = :id_tipo_mantenimiento, 
                fecha_programada = :fecha_programada, 
                fecha_realizada = :fecha_realizada, 
                observaciones = :observaciones, 
                kilometraje_actual = :kilometraje_actual, 
                proximo_cambio_km = :proximo_cambio_km, 
                proximo_cambio_fecha = :proximo_cambio_fecha 
                WHERE id_mantenimiento = :id_mantenimiento
            ");
            
            // Vincular todos los parámetros incluyendo el ID para la condición WHERE
            $query->bindParam(':id_mantenimiento', $id_mantenimiento);
            $query->bindParam(':placa', $placa);
            $query->bindParam(':id_tipo_mantenimiento', $id_tipo_mantenimiento);
            $query->bindParam(':fecha_programada', $fecha_programada);
            $query->bindParam(':fecha_realizada', $fecha_realizada);
            $query->bindParam(':observaciones', $observaciones);
            $query->bindParam(':kilometraje_actual', $kilometraje_actual);
            $query->bindParam(':proximo_cambio_km', $proximo_cambio_km);
            $query->bindParam(':proximo_cambio_fecha', $proximo_cambio_fecha);
            
            // Ejecutar la actualización y enviar respuesta JSON
            if ($query->execute()) {
                echo json_encode(['success' => true, 'message' => 'Mantenimiento actualizado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el mantenimiento']);
            }
            break;
            
        case 'eliminar':
            // Obtener el ID del mantenimiento a eliminar
            $id_mantenimiento = $_POST['id_mantenimiento'] ?? '';
            
            // Validar que se proporcionó el ID del mantenimiento
            if (empty($id_mantenimiento)) {
                echo json_encode(['success' => false, 'message' => 'ID de mantenimiento requerido']);
                exit;
            }
            
            // Verificar que el mantenimiento a eliminar existe en la base de datos
            $check_mant = $con->prepare("SELECT id_mantenimiento FROM mantenimiento WHERE id_mantenimiento = :id");
            $check_mant->bindParam(':id', $id_mantenimiento);
            $check_mant->execute();
            
            if ($check_mant->rowCount() === 0) {
                echo json_encode(['success' => false, 'message' => 'El mantenimiento no existe']);
                exit;
            }
            
            // Preparar y ejecutar consulta para eliminar el mantenimiento
            $query = $con->prepare("DELETE FROM mantenimiento WHERE id_mantenimiento = :id_mantenimiento");
            $query->bindParam(':id_mantenimiento', $id_mantenimiento);
            
            // Ejecutar la eliminación y enviar respuesta JSON
            if ($query->execute()) {
                echo json_encode(['success' => true, 'message' => 'Mantenimiento eliminado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar el mantenimiento']);
            }
            break;
            
        default:
            // Manejar acciones no válidas o no reconocidas
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
    
} catch (Exception $e) {
    // Capturar y manejar cualquier excepción que pueda ocurrir
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>