<?php
session_start();
require_once('../../conecct/conex.php');
include '../../includes/validarsession.php';

header('Content-Type: application/json');

$db = new Database();
$con = $db->conectar();

// Validación de sesión
$documento = $_SESSION['documento'] ?? null;
if (!$documento) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

// Manejar solicitudes POST para acciones específicas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    switch ($accion) {
  case 'obtener_estados_vehiculo':
    $sql = "SELECT DISTINCT id_estado, estado FROM estado_vehiculo ORDER BY estado ASC";
    $stmt = $con->prepare($sql);
    $stmt->execute();
    $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($estados);
    exit;
    }
}

// Obtener parámetros para reportes GET
$tipo = $_GET['tipo'] ?? '';
$filtros = json_decode($_GET['filtros'] ?? '{}', true);

if (empty($tipo)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de reporte no especificado']);
    exit;
}

try {
    $datos = generarReporte($con, $tipo, $filtros);
    echo json_encode([
        'success' => true,
        'datos' => $datos,
        'total' => count($datos),
        'filtros_aplicados' => $filtros
    ]);
} catch (Exception $e) {
    error_log("Error en reporte AJAX: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al generar el reporte: ' . $e->getMessage()
    ]);
}

function generarReporte($con, $tipo, $filtros = []) {
    $datos = [];
    try {
        switch ($tipo) {
           case 'vehiculos':
     $sql = "SELECT v.placa, 
                   m.nombre_marca as marca, 
                   v.modelo, 
                   v.kilometraje_actual, 
                   e.estado as estado, 
                   v.fecha_registro, 
                   u.nombre_completo as usuario_responsable,
                   tv.vehiculo as tipo_vehiculo
            FROM vehiculos v 
            LEFT JOIN usuarios u ON v.Documento = u.documento 
            LEFT JOIN marca m ON v.id_marca = m.id_marca
            LEFT JOIN estado_vehiculo e ON v.id_estado = e.id_estado
            LEFT JOIN tipo_vehiculo tv ON v.tipo_vehiculo = tv.id_tipo_vehiculo
            WHERE 1=1";
    $params = [];
    if (!empty($filtros['estado'])) {
        $sql .= " AND v.id_estado = :estado";
        $params[':estado'] = $filtros['estado'];
    }
    if (!empty($filtros['placa'])) {
        $sql .= " AND v.placa LIKE :placa";
        $params[':placa'] = '%' . $filtros['placa'] . '%';
    }
    if (!empty($filtros['fecha_desde'])) {
        $sql .= " AND v.fecha_registro >= :fecha_desde";
        $params[':fecha_desde'] = $filtros['fecha_desde'];
    }
    if (!empty($filtros['fecha_hasta'])) {
        $sql .= " AND v.fecha_registro <= :fecha_hasta";
        $params[':fecha_hasta'] = $filtros['fecha_hasta'];
    }
    $sql .= " ORDER BY v.fecha_registro DESC LIMIT 1000";
    break;

            case 'mantenimientos':
                $sql = "SELECT m.id_mantenimiento, m.placa, m.fecha_programada, m.fecha_realizada,
                               tm.descripcion as tipo_mantenimiento, m.kilometraje_actual,
                               m.observaciones, m.proximo_cambio_km, m.proximo_cambio_fecha,
                               u.nombre_completo as usuario_responsable,
                               CASE WHEN m.fecha_realizada IS NOT NULL THEN 'Realizado' ELSE 'Pendiente' END as estado
                        FROM mantenimiento m
                        LEFT JOIN tipo_mantenimiento tm ON m.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                        LEFT JOIN vehiculos v ON m.placa = v.placa
                        LEFT JOIN usuarios u ON v.Documento = u.documento
                        WHERE 1=1";
                $params = [];
                if (!empty($filtros['placa'])) {
                    $sql .= " AND m.placa LIKE :placa";
                    $params[':placa'] = '%' . $filtros['placa'] . '%';
                }
                if (!empty($filtros['tipo'])) {
                    $sql .= " AND m.id_tipo_mantenimiento = :tipo";
                    $params[':tipo'] = $filtros['tipo'];
                }
                if (!empty($filtros['estado'])) {
                    if ($filtros['estado'] == 'realizado') {
                        $sql .= " AND m.fecha_realizada IS NOT NULL";
                    } else {
                        $sql .= " AND m.fecha_realizada IS NULL";
                    }
                }
                if (!empty($filtros['fecha_desde'])) {
                    $sql .= " AND m.fecha_programada >= :fecha_desde";
                    $params[':fecha_desde'] = $filtros['fecha_desde'];
                }
                if (!empty($filtros['fecha_hasta'])) {
                    $sql .= " AND m.fecha_programada <= :fecha_hasta";
                    $params[':fecha_hasta'] = $filtros['fecha_hasta'];
                }
                $sql .= " ORDER BY m.fecha_programada DESC LIMIT 1000";
                break;

            case 'llantas':
                $sql = "SELECT l.id_llanta, l.placa, l.ultimo_cambio, l.estado,
                               l.presion_llantas, l.kilometraje_actual, l.proximo_cambio_fecha,
                               l.notas, u.nombre_completo as usuario_responsable
                        FROM llantas l
                        LEFT JOIN vehiculos v ON l.placa = v.placa
                        LEFT JOIN usuarios u ON v.Documento = u.documento
                        WHERE 1=1";
                $params = [];
                if (!empty($filtros['placa'])) {
                    $sql .= " AND l.placa LIKE :placa";
                    $params[':placa'] = '%' . $filtros['placa'] . '%';
                }
                if (!empty($filtros['estado'])) {
                    $sql .= " AND l.estado = :estado";
                    $params[':estado'] = $filtros['estado'];
                }
                if (!empty($filtros['fecha_desde'])) {
                    $sql .= " AND l.ultimo_cambio >= :fecha_desde";
                    $params[':fecha_desde'] = $filtros['fecha_desde'];
                }
                if (!empty($filtros['fecha_hasta'])) {
                    $sql .= " AND l.ultimo_cambio <= :fecha_hasta";
                    $params[':fecha_hasta'] = $filtros['fecha_hasta'];
                }
                $sql .= " ORDER BY l.ultimo_cambio DESC LIMIT 1000";
                break;

            case 'soat':
                $sql = "SELECT s.id_soat, s.id_placa, s.fecha_expedicion, s.fecha_vencimiento,
                               a.nombre as aseguradora, s.id_estado,
                               CASE 
                                   WHEN s.fecha_vencimiento > CURDATE() THEN 'Vigente' 
                                   ELSE 'Vencido' 
                               END as estado,
                               DATEDIFF(s.fecha_vencimiento, CURDATE()) as dias_restantes,
                               u.nombre_completo as usuario_responsable
                        FROM soat s
                        LEFT JOIN aseguradoras_soat a ON s.id_aseguradora = a.id_asegura
                        LEFT JOIN vehiculos v ON s.id_placa = v.placa
                        LEFT JOIN usuarios u ON v.Documento = u.documento
                        WHERE 1=1";
                $params = [];
                if (!empty($filtros['placa'])) {
                    $sql .= " AND s.id_placa LIKE :placa";
                    $params[':placa'] = '%' . $filtros['placa'] . '%';
                }
                if (!empty($filtros['estado'])) {
                    if ($filtros['estado'] == 'vigente') {
                        $sql .= " AND s.fecha_vencimiento > CURDATE()";
                    } elseif ($filtros['estado'] == 'vencido') {
                        $sql .= " AND s.fecha_vencimiento <= CURDATE()";
                    } elseif ($filtros['estado'] == 'proximo_vencer') {
                        $sql .= " AND s.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
                    }
                }
                if (!empty($filtros['fecha_desde'])) {
                    $sql .= " AND s.fecha_expedicion >= :fecha_desde";
                    $params[':fecha_desde'] = $filtros['fecha_desde'];
                }
                if (!empty($filtros['fecha_hasta'])) {
                    $sql .= " AND s.fecha_expedicion <= :fecha_hasta";
                    $params[':fecha_hasta'] = $filtros['fecha_hasta'];
                }
                $sql .= " ORDER BY s.fecha_vencimiento ASC LIMIT 1000";
                break;

            case 'tecnomecanica':
                $sql = "SELECT t.id_rtm, t.id_placa, t.fecha_expedicion, t.fecha_vencimiento,
                               c.centro_revision,
                               CASE 
                                   WHEN t.fecha_vencimiento > CURDATE() THEN 'Vigente' 
                                   ELSE 'Vencido' 
                               END as estado,
                               DATEDIFF(t.fecha_vencimiento, CURDATE()) as dias_restantes,
                               u.nombre_completo as usuario_responsable
                        FROM tecnomecanica t
                        LEFT JOIN centro_rtm c ON t.id_centro_revision = c.id_centro
                        LEFT JOIN vehiculos v ON t.id_placa = v.placa
                        LEFT JOIN usuarios u ON v.Documento = u.documento
                        WHERE 1=1";
                $params = [];
                if (!empty($filtros['placa'])) {
                    $sql .= " AND t.id_placa LIKE :placa";
                    $params[':placa'] = '%' . $filtros['placa'] . '%';
                }
                if (!empty($filtros['estado'])) {
                    if ($filtros['estado'] == 'vigente') {
                        $sql .= " AND t.fecha_vencimiento > CURDATE()";
                    } elseif ($filtros['estado'] == 'vencido') {
                        $sql .= " AND t.fecha_vencimiento <= CURDATE()";
                    } elseif ($filtros['estado'] == 'proximo_vencer') {
                        $sql .= " AND t.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
                    }
                }
                if (!empty($filtros['fecha_desde'])) {
                    $sql .= " AND t.fecha_expedicion >= :fecha_desde";
                    $params[':fecha_desde'] = $filtros['fecha_desde'];
                }
                if (!empty($filtros['fecha_hasta'])) {
                    $sql .= " AND t.fecha_expedicion <= :fecha_hasta";
                    $params[':fecha_hasta'] = $filtros['fecha_hasta'];
                }
                $sql .= " ORDER BY t.fecha_vencimiento ASC LIMIT 1000";
                break;

            case 'licencias':
                $sql = "SELECT l.id_licencia, l.id_documento, l.fecha_expedicion, l.fecha_vencimiento,
                               c.nombre_categoria as categoria, s.nombre_servicios as servicio,
                               CASE 
                                   WHEN l.fecha_vencimiento > CURDATE() THEN 'Vigente' 
                                   ELSE 'Vencido' 
                               END as estado,
                               DATEDIFF(l.fecha_vencimiento, CURDATE()) as dias_restantes,
                               u.nombre_completo as usuario_responsable, l.observaciones
                        FROM licencias l
                        LEFT JOIN categoria_licencia c ON l.id_categoria = c.id_categoria
                        LEFT JOIN servicios_licencias s ON l.id_servicio = s.id_servicio
                        LEFT JOIN usuarios u ON l.id_documento = u.documento
                        WHERE 1=1";
                $params = [];
                if (!empty($filtros['documento'])) {
                    $sql .= " AND l.id_documento LIKE :documento";
                    $params[':documento'] = '%' . $filtros['documento'] . '%';
                }
                if (!empty($filtros['estado'])) {
                    if ($filtros['estado'] == 'vigente') {
                        $sql .= " AND l.fecha_vencimiento > CURDATE()";
                    } elseif ($filtros['estado'] == 'vencido') {
                        $sql .= " AND l.fecha_vencimiento <= CURDATE()";
                    } elseif ($filtros['estado'] == 'proximo_vencer') {
                        $sql .= " AND l.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
                    }
                }
                if (!empty($filtros['fecha_desde'])) {
                    $sql .= " AND l.fecha_expedicion >= :fecha_desde";
                    $params[':fecha_desde'] = $filtros['fecha_desde'];
                }
                if (!empty($filtros['fecha_hasta'])) {
                    $sql .= " AND l.fecha_expedicion <= :fecha_hasta";
                    $params[':fecha_hasta'] = $filtros['fecha_hasta'];
                }
                $sql .= " ORDER BY l.fecha_vencimiento ASC LIMIT 1000";
                break;
                case 'alertas':
    $sql = "SELECT 'SOAT' as tipo_alerta,
                   v.placa,
                   s.fecha_vencimiento,
                   DATEDIFF(s.fecha_vencimiento, CURDATE()) as dias_restantes,
                   'SOAT próximo a vencer' as descripcion
            FROM soat s
            JOIN vehiculos v ON s.id_placa = v.placa
            WHERE s.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            UNION ALL
            SELECT 'Tecnomecánica' as tipo_alerta,
                   v.placa,
                   t.fecha_vencimiento,
                   DATEDIFF(t.fecha_vencimiento, CURDATE()) as dias_restantes,
                   'Tecnomecánica próxima a vencer' as descripcion
            FROM tecnomecanica t
            JOIN vehiculos v ON t.id_placa = v.placa
            WHERE t.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            UNION ALL
            SELECT 'Licencia' as tipo_alerta,
                   'N/A' as placa,
                   l.fecha_vencimiento,
                   DATEDIFF(l.fecha_vencimiento, CURDATE()) as dias_restantes,
                   CONCAT('Licencia de ', u.nombre_completo, ' próxima a vencer') as descripcion
            FROM licencias l
            JOIN usuarios u ON l.id_documento = u.documento
            WHERE l.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
    $params = [];
    if (!empty($filtros['tipo'])) {
        $sql = "SELECT * FROM (" . $sql . ") as alertas WHERE tipo_alerta LIKE :tipo";
        $params[':tipo'] = '%' . $filtros['tipo'] . '%';
    }
    if (!empty($filtros['fecha_desde'])) {
        $whereClause = empty($filtros['tipo']) ? " WHERE " : " AND ";
        $sql .= $whereClause . "fecha_vencimiento >= :fecha_desde";
        $params[':fecha_desde'] = $filtros['fecha_desde'];
    }
    if (!empty($filtros['fecha_hasta'])) {
        $whereClause = (empty($filtros['tipo']) && empty($filtros['fecha_desde'])) ? " WHERE " : " AND ";
        $sql .= $whereClause . "fecha_vencimiento <= :fecha_hasta";
        $params[':fecha_hasta'] = $filtros['fecha_hasta'];
    }
    $sql .= " ORDER BY dias_restantes ASC LIMIT 1000";
    break;

               case 'actividad':
                // Simulación de log de actividades (ajustar según tu estructura de BD)
                $sql = "SELECT 'Vehículo' as tipo_actividad, 
                               CONCAT('Registro de vehículo: ', v.placa) as descripcion,
                               v.fecha_registro as fecha,
                               u.nombre_completo as usuario_responsable,
                               v.placa as referencia
                        FROM vehiculos v
                        LEFT JOIN usuarios u ON v.Documento = u.documento
                        UNION ALL
                        SELECT 'Mantenimiento' as tipo_actividad,
                               CONCAT('Mantenimiento programado: ', m.placa) as descripcion,
                               m.fecha_programada as fecha,
                               resp.nombre_completo as usuario_responsable,
                               m.placa as referencia
                        FROM mantenimiento m
                        LEFT JOIN vehiculos v_m ON m.placa = v_m.placa
                        LEFT JOIN usuarios resp ON v_m.Documento = resp.documento
                        UNION ALL
                        SELECT 'SOAT' as tipo_actividad,
                               CONCAT('SOAT registrado: ', s.id_placa) as descripcion,
                               s.fecha_expedicion as fecha,
                               resp_s.nombre_completo as usuario_responsable,
                               s.id_placa as referencia
                        FROM soat s
                        LEFT JOIN vehiculos v_s ON s.id_placa = v_s.placa
                        LEFT JOIN usuarios resp_s ON v_s.Documento = resp_s.documento";
                $params = [];
                if (!empty($filtros['tipo'])) {
                    $sql = "SELECT * FROM (" . $sql . ") as actividades WHERE tipo_actividad LIKE :tipo";
                    $params[':tipo'] = '%' . $filtros['tipo'] . '%';
                }
                $whereClause = !empty($filtros['tipo']) ? " AND " : " WHERE ";
                if (!empty($filtros['fecha_desde'])) {
                    if (empty($filtros['tipo'])) {
                        $sql = "SELECT * FROM (" . $sql . ") as actividades WHERE fecha >= :fecha_desde";
                    } else {
                        $sql .= " AND fecha >= :fecha_desde";
                    }
                    $params[':fecha_desde'] = $filtros['fecha_desde'];
                }
                if (!empty($filtros['fecha_hasta'])) {
                    $connector = (!empty($filtros['tipo']) || !empty($filtros['fecha_desde'])) ? " AND " : " WHERE ";
                    if (empty($filtros['tipo']) && empty($filtros['fecha_desde'])) {
                        $sql = "SELECT * FROM (" . $sql . ") as actividades WHERE fecha <= :fecha_hasta";
                    } else {
                        $sql .= " AND fecha <= :fecha_hasta";
                    }
                    $params[':fecha_hasta'] = $filtros['fecha_hasta'];
                }
                $sql .= " ORDER BY fecha DESC LIMIT 1000";
                break;

            default:
                throw new Exception("Tipo de reporte no válido: " . $tipo);
        }

        $stmt = $con->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error en consulta SQL para reporte $tipo: " . $e->getMessage());
        throw new Exception("Error en la consulta de base de datos");
    }
    return $datos;
}
?>
