<?php
// Habilitar reporte de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

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
    case 'crear_licencia':
        crearLicencia($conexion);
        break;
    case 'actualizar_licencia':
        actualizarLicencia($conexion);
        break;
    case 'suspender_licencia':
        suspenderLicencia($conexion);
        break;
    case 'renovar_licencia':
        renovarLicencia($conexion);
        break;
    case 'obtener_licencia':
        obtenerLicencia($conexion);
        break;
    case 'verificar_licencias':
        verificarLicencias($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function crearLicencia($conexion) {
    try {
        $nombre_empresa = $_POST['nombre_empresa'] ?? '';
        $tipo_licencia = $_POST['tipo_licencia'] ?? '';
        $fecha_inicio = $_POST['fecha_inicio'] ?? '';
        $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? '';
        $max_usuarios = $_POST['max_usuarios'] ?? '';
        $max_vehiculos = $_POST['max_vehiculos'] ?? '';
        $clave_licencia = $_POST['clave_licencia'] ?? '';

        if (empty($nombre_empresa) || empty($tipo_licencia) || empty($fecha_inicio) || empty($fecha_vencimiento) || empty($max_usuarios) || empty($max_vehiculos)) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
            return;
        }

        // Generar clave de licencia si no se proporciona
        if (empty($clave_licencia)) {
            $clave_licencia = 'FLOTAX-' . strtoupper(bin2hex(random_bytes(8)));
        }

        // Desactivar licencias anteriores
        $stmt = $conexion->prepare("UPDATE sistema_licencias SET estado = 'inactiva' WHERE estado = 'activa'");
        $stmt->execute();

        // Insertar nueva licencia
        $stmt = $conexion->prepare("
            INSERT INTO sistema_licencias (nombre_empresa, tipo_licencia, fecha_inicio, fecha_vencimiento, max_usuarios, max_vehiculos, clave_licencia, estado, fecha_creacion) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'activa', NOW())
        ");

        $stmt->execute([
            $nombre_empresa,
            $tipo_licencia,
            $fecha_inicio,
            $fecha_vencimiento,
            $max_usuarios,
            $max_vehiculos,
            $clave_licencia
        ]);

        echo json_encode(['success' => true, 'message' => 'Licencia creada exitosamente']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al crear licencia: ' . $e->getMessage()]);
    }
}

function actualizarLicencia($conexion) {
    try {
        $id_licencia = $_POST['id_licencia'] ?? '';
        $nombre_empresa = $_POST['nombre_empresa'] ?? '';
        $tipo_licencia = $_POST['tipo_licencia'] ?? '';
        $fecha_inicio = $_POST['fecha_inicio'] ?? '';
        $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? '';
        $max_usuarios = $_POST['max_usuarios'] ?? '';
        $max_vehiculos = $_POST['max_vehiculos'] ?? '';

        if (empty($id_licencia) || empty($nombre_empresa) || empty($tipo_licencia) || empty($fecha_inicio) || empty($fecha_vencimiento) || empty($max_usuarios) || empty($max_vehiculos)) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
            return;
        }

        // Actualizar licencia
        $stmt = $conexion->prepare("
            UPDATE sistema_licencias 
            SET nombre_empresa = ?, tipo_licencia = ?, fecha_inicio = ?, fecha_vencimiento = ?, max_usuarios = ?, max_vehiculos = ?
            WHERE id_licencia = ?
        ");

        $stmt->execute([
            $nombre_empresa,
            $tipo_licencia,
            $fecha_inicio,
            $fecha_vencimiento,
            $max_usuarios,
            $max_vehiculos,
            $id_licencia
        ]);

        echo json_encode(['success' => true, 'message' => 'Licencia actualizada exitosamente']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar licencia: ' . $e->getMessage()]);
    }
}

function suspenderLicencia($conexion) {
    try {
        $id_licencia = $_POST['id_licencia'] ?? '';

        if (empty($id_licencia)) {
            echo json_encode(['success' => false, 'message' => 'ID de licencia requerido']);
            return;
        }

        // Suspender licencia
        $stmt = $conexion->prepare("UPDATE sistema_licencias SET estado = 'suspendida' WHERE id_licencia = ?");
        $stmt->execute([$id_licencia]);

        // Desactivar usuarios
        $stmt = $conexion->prepare("UPDATE usuarios SET id_estado_usuario = 2 WHERE id_estado_usuario = 1");
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Licencia suspendida y usuarios desactivados']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al suspender licencia: ' . $e->getMessage()]);
    }
}

function renovarLicencia($conexion) {
    try {
        $id_licencia = $_POST['id_licencia'] ?? '';
        $nueva_fecha_vencimiento = $_POST['nueva_fecha_vencimiento'] ?? '';

        if (empty($id_licencia) || empty($nueva_fecha_vencimiento)) {
            echo json_encode(['success' => false, 'message' => 'ID de licencia y nueva fecha requeridos']);
            return;
        }

        // Renovar licencia
        $stmt = $conexion->prepare("UPDATE sistema_licencias SET fecha_vencimiento = ?, estado = 'activa' WHERE id_licencia = ?");
        $stmt->execute([$nueva_fecha_vencimiento, $id_licencia]);

        // Reactivar usuarios
        $stmt = $conexion->prepare("UPDATE usuarios SET id_estado_usuario = 1 WHERE id_estado_usuario = 2");
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Licencia renovada y usuarios reactivados']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al renovar licencia: ' . $e->getMessage()]);
    }
}

function obtenerLicencia($conexion) {
    try {
        $id_licencia = $_POST['id_licencia'] ?? '';

        if (empty($id_licencia)) {
            echo json_encode(['success' => false, 'message' => 'ID de licencia requerido']);
            return;
        }

        $stmt = $conexion->prepare("SELECT * FROM sistema_licencias WHERE id_licencia = ?");
        $stmt->execute([$id_licencia]);
        $licencia = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$licencia) {
            echo json_encode(['success' => false, 'message' => 'Licencia no encontrada']);
            return;
        }

        echo json_encode(['success' => true, 'licencia' => $licencia]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener licencia: ' . $e->getMessage()]);
    }
}

function verificarLicencias($conexion) {
    try {
        // Verificar licencias vencidas
        $stmt = $conexion->prepare("
            SELECT COUNT(*) as vencidas 
            FROM sistema_licencias 
            WHERE fecha_vencimiento < CURDATE() AND estado = 'activa'
        ");
        $stmt->execute();
        $licencias_vencidas = $stmt->fetch(PDO::FETCH_ASSOC)['vencidas'];

        // Verificar licencias próximas a vencer (30 días)
        $stmt = $conexion->prepare("
            SELECT COUNT(*) as proximas 
            FROM sistema_licencias 
            WHERE fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
            AND estado = 'activa'
        ");
        $stmt->execute();
        $licencias_proximas = $stmt->fetch(PDO::FETCH_ASSOC)['proximas'];

        // Actualizar estado de licencias vencidas
        if ($licencias_vencidas > 0) {
            $stmt = $conexion->prepare("
                UPDATE sistema_licencias 
                SET estado = 'vencida' 
                WHERE fecha_vencimiento < CURDATE() AND estado = 'activa'
            ");
            $stmt->execute();

            // Desactivar usuarios si hay licencias vencidas
            $stmt = $conexion->prepare("UPDATE usuarios SET id_estado_usuario = 2 WHERE id_estado_usuario = 1");
            $stmt->execute();
        }

        echo json_encode([
            'success' => true,
            'vencidas' => $licencias_vencidas,
            'proximas' => $licencias_proximas,
            'message' => 'Verificación completada'
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al verificar licencias: ' . $e->getMessage()]);
    }
}
?>