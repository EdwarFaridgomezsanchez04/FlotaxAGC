<?php
session_start();

// Verificar autenticación de superadmin
if (!isset($_SESSION['documento']) || $_SESSION['tipo'] != 3) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once '../../conecct/conex.php';
$database = new Database();
$conexion = $database->conectar();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'crear_vehiculo':
        crearVehiculo($conexion);
        break;
    case 'actualizar_vehiculo':
        actualizarVehiculo($conexion);
        break;
    case 'eliminar_vehiculo':
        eliminarVehiculo($conexion);
        break;
    case 'obtener_vehiculo':
        obtenerVehiculo($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function crearVehiculo($conexion) {
    try {
        $placa = $_POST['placa'] ?? '';
        $Documento = $_POST['Documento'] ?? '';
        $id_marca = $_POST['id_marca'] ?? '';
        $modelo = $_POST['modelo'] ?? '';
        $año = $_POST['año'] ?? '';
        $id_tipo = $_POST['id_tipo'] ?? '';
        $color = $_POST['color'] ?? '';
        $id_estado = $_POST['id_estado'] ?? '';

        if (empty($placa) || empty($Documento) || empty($id_marca) || empty($modelo) || empty($año) || empty($id_tipo) || empty($color)) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
            return;
        }

        // Verificar si el vehículo ya existe
        $stmt = $conexion->prepare("SELECT placa FROM vehiculos WHERE placa = ?");
        $stmt->execute([$placa]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'La placa ya está registrada']);
            return;
        }

        // Verificar que el propietario existe
        $stmt = $conexion->prepare("SELECT documento FROM usuarios WHERE documento = ?");
        $stmt->execute([$Documento]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'El propietario no existe']);
            return;
        }

        // Insertar vehículo
        $stmt = $conexion->prepare("
            INSERT INTO vehiculos (placa, Documento, id_marca, modelo, año, id_tipo, color, id_estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $placa,
            $Documento,
            $id_marca,
            $modelo,
            $año,
            $id_tipo,
            $color,
            $id_estado ?: '1'
        ]);

        echo json_encode(['success' => true, 'message' => 'Vehículo creado exitosamente']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al crear vehículo: ' . $e->getMessage()]);
    }
}

function actualizarVehiculo($conexion) {
    try {
        $placa_original = $_POST['placa_original'] ?? '';
        $placa = $_POST['placa'] ?? '';
        $Documento = $_POST['Documento'] ?? '';
        $id_marca = $_POST['id_marca'] ?? '';
        $modelo = $_POST['modelo'] ?? '';
        $año = $_POST['año'] ?? '';
        $id_tipo = $_POST['id_tipo'] ?? '';
        $color = $_POST['color'] ?? '';
        $id_estado = $_POST['id_estado'] ?? '';

        if (empty($placa_original) || empty($placa) || empty($Documento) || empty($id_marca) || empty($modelo) || empty($año) || empty($id_tipo) || empty($color)) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
            return;
        }

        // Verificar si la nueva placa ya existe (si cambió)
        if ($placa !== $placa_original) {
            $stmt = $conexion->prepare("SELECT placa FROM vehiculos WHERE placa = ? AND placa != ?");
            $stmt->execute([$placa, $placa_original]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'La placa ya está registrada']);
                return;
            }
        }

        // Verificar que el propietario existe
        $stmt = $conexion->prepare("SELECT documento FROM usuarios WHERE documento = ?");
        $stmt->execute([$Documento]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'El propietario no existe']);
            return;
        }

        // Actualizar vehículo
        $stmt = $conexion->prepare("
            UPDATE vehiculos 
            SET placa = ?, Documento = ?, id_marca = ?, modelo = ?, año = ?, id_tipo = ?, color = ?, id_estado = ?
            WHERE placa = ?
        ");

        $stmt->execute([
            $placa,
            $Documento,
            $id_marca,
            $modelo,
            $año,
            $id_tipo,
            $color,
            $id_estado ?: '1',
            $placa_original
        ]);

        echo json_encode(['success' => true, 'message' => 'Vehículo actualizado exitosamente']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar vehículo: ' . $e->getMessage()]);
    }
}

function eliminarVehiculo($conexion) {
    try {
        $placa = $_POST['placa'] ?? '';

        if (empty($placa)) {
            echo json_encode(['success' => false, 'message' => 'Placa requerida']);
            return;
        }

        // Verificar que el vehículo existe
        $stmt = $conexion->prepare("SELECT placa FROM vehiculos WHERE placa = ?");
        $stmt->execute([$placa]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Vehículo no encontrado']);
            return;
        }

        // Eliminar vehículo
        $stmt = $conexion->prepare("DELETE FROM vehiculos WHERE placa = ?");
        $stmt->execute([$placa]);

        echo json_encode(['success' => true, 'message' => 'Vehículo eliminado exitosamente']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar vehículo: ' . $e->getMessage()]);
    }
}

function obtenerVehiculo($conexion) {
    try {
        $placa = $_POST['placa'] ?? '';

        if (empty($placa)) {
            echo json_encode(['success' => false, 'message' => 'Placa requerida']);
            return;
        }

        $stmt = $conexion->prepare("
            SELECT placa, Documento, id_marca, modelo, año, id_tipo, color, id_estado
            FROM vehiculos 
            WHERE placa = ?
        ");
        $stmt->execute([$placa]);
        $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$vehiculo) {
            echo json_encode(['success' => false, 'message' => 'Vehículo no encontrado']);
            return;
        }

        echo json_encode(['success' => true, 'vehiculo' => $vehiculo]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener vehículo: ' . $e->getMessage()]);
    }
}
?> 