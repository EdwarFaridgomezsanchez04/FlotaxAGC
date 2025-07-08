<?php
// Iniciar sesión para validar el usuario autenticado
session_start();

// Incluir archivo de conexión a la base de datos
require_once('../../../conecct/conex.php');

// Configuración de manejo de errores
ini_set('display_errors', 0);      // No mostrar errores en pantalla
ini_set('log_errors', 1);          // Habilitar registro de errores
ini_set('error_log', 'php_errors.log'); // Archivo de log de errores

// Crear instancia de conexión a la base de datos
$db = new Database();
$con = $db->conectar();

// Establecer tipo de contenido como JSON para la respuesta
header('Content-Type: application/json');

// Verificar si la conexión a la base de datos fue exitosa
if (!$con) {
    error_log("Failed to connect to database");
    echo json_encode(['error' => 'No se pudo conectar a la base de datos']);
    exit;
}

try {
    // Obtener la placa del vehículo desde los datos POST
    $placa = $_POST['placa'] ?? '';
    
    // Validar que se haya proporcionado una placa
    if (empty($placa)) {
        error_log("No placa provided in delete_vehicle.php");
        echo json_encode(['error' => 'Placa no proporcionada']);
        exit;
    }

    // Definir tablas relacionadas que deben ser eliminadas primero
    // para mantener la integridad referencial
    $tablasRelacionadas = [
        'correos_enviados_pico_placa',  // Correos de pico y placa
        'mantenimiento',                // Registros de mantenimiento
        'llantas',                      // Información de llantas
        'multas',                       // Multas del vehículo
        'soat',                         // Seguro obligatorio
        'tecnomecanica'                 // Revisión tecnomecánica
    ];

    // Eliminar registros relacionados en cascada
    foreach ($tablasRelacionadas as $tabla) {
        // Determinar el campo de referencia según la tabla
        // SOAT y tecnomecánica usan 'id_placa', las demás usan 'placa'
        $campo = ($tabla == 'soat' || $tabla == 'tecnomecanica') ? 'id_placa' : 'placa';
        
        // Preparar consulta de eliminación
        $sqlDelete = "DELETE FROM $tabla WHERE $campo = :placa";
        $stmtDelete = $con->prepare($sqlDelete);
        $stmtDelete->bindParam(':placa', $placa, PDO::PARAM_STR);
        $stmtDelete->execute();
    }

    // Verificar dependencias restantes con consultas explícitas
    $hasDependencies = false;
    $checkQueries = [
        "SELECT COUNT(*) FROM mantenimiento WHERE placa = :placa",
        "SELECT COUNT(*) FROM llantas WHERE placa = :placa",
        "SELECT COUNT(*) FROM multas WHERE placa = :placa",
        "SELECT COUNT(*) FROM soat WHERE id_placa = :placa",
        "SELECT COUNT(*) FROM tecnomecanica WHERE id_placa = :placa"
    ];

    // Ejecutar verificaciones de dependencias
    foreach ($checkQueries as $queryStr) {
        // Extraer nombre de tabla para logging
        $table = explode(' FROM ', $queryStr)[1];
        $table = strtok($table, ' ');
        
        error_log("Executing query: $queryStr for placa: $placa");
        
        // Preparar y ejecutar consulta de verificación
        $query = $con->prepare($queryStr);
        $query->bindParam(':placa', $placa, PDO::PARAM_STR);
        $query->execute();
        $count = $query->fetchColumn();
        
        error_log("Result for $table: $count records");
        
        // Si existen dependencias, no permitir eliminación
        if ($count > 0) {
            error_log("Cannot delete vehicle with placa $placa due to dependencies in $table");
            echo json_encode(['error' => "No se puede eliminar el vehículo porque tiene registros asociados en $table"]);
            $hasDependencies = true;
            break;
        }
    }

    // Si hay dependencias, terminar ejecución
    if ($hasDependencies) {
        exit;
    }

    // Obtener y eliminar imagen del vehículo
    $image_query = $con->prepare("SELECT foto_vehiculo FROM vehiculos WHERE placa = :placa");
    $image_query->bindParam(':placa', $placa, PDO::PARAM_STR);
    $image_query->execute();
    $image = $image_query->fetchColumn();
    
    // Eliminar archivo de imagen si existe
    if ($image && file_exists('../' . $image)) {
        if (!unlink('../' . $image)) {
            error_log("Failed to delete image for placa $placa: ../$image");
        }
    }

    // Eliminar registro del vehículo de la tabla principal
    $query = $con->prepare("DELETE FROM vehiculos WHERE placa = :placa");
    $query->bindParam(':placa', $placa, PDO::PARAM_STR);

    // Ejecutar eliminación y enviar respuesta
    if ($query->execute()) {
        echo json_encode(['success' => true]);
    } else {
        error_log("Failed to delete vehicle with placa: $placa");
        echo json_encode(['error' => 'Error al eliminar el vehículo']);
    }
    
} catch (PDOException $e) {
    // Manejo de errores de base de datos
    error_log("Database error in delete_vehicle.php: Query failed - " . $e->getMessage());
    echo json_encode(['error' => 'Error en la consulta: ' . $e->getMessage()]);
}
?>