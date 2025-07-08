<?php
/**
 * ENDPOINT PARA OBTENER DATOS DE MANTENIMIENTO
 * 
 * Este archivo es un endpoint AJAX que proporciona información detallada
 * de un registro de mantenimiento específico mediante su ID.
 * 
 * Funcionalidades principales:
 * - Validación de sesión de usuario activo
 * - Obtención de datos completos de mantenimiento
 * - Respuesta en formato JSON para consumo AJAX
 * - Manejo robusto de errores y excepciones
 * - Validación de parámetros de entrada
 * 
 * Método HTTP: GET
 * Parámetro requerido: id (ID del mantenimiento)
 * Respuesta: JSON con datos del mantenimiento o mensaje de error
 */

// Inicialización de sesión para validación de usuario autenticado
session_start();

// Inclusión de archivos de configuración y validación
require_once('../../../conecct/conex.php');  // Conexión a base de datos
include '../../../includes/validarsession.php';  // Validador de sesión

// Configuración de cabecera para respuesta JSON
header('Content-Type: application/json');

// Instanciación de la clase de base de datos
$db = new Database();
$con = $db->conectar();

/**
 * VALIDACIÓN DE SESIÓN ACTIVA
 * 
 * Verifica que el usuario tenga una sesión válida antes de procesar la solicitud.
 * Si no hay sesión activa, retorna error y termina la ejecución.
 */
if (!isset($_SESSION['documento'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

/**
 * VALIDACIÓN DEL MÉTODO HTTP
 * 
 * Este endpoint solo acepta peticiones GET.
 * Cualquier otro método HTTP será rechazado.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

/**
 * OBTENCIÓN Y VALIDACIÓN DE PARÁMETROS
 * 
 * Extrae el ID del mantenimiento desde los parámetros GET.
 * Utiliza el operador null coalescing (??) para manejar valores no definidos.
 */
$id = $_GET['id'] ?? '';

// Validación de que el ID no esté vacío
if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'ID de mantenimiento requerido']);
    exit;
}

/**
 * PROCESAMIENTO DE LA CONSULTA DE MANTENIMIENTO
 * 
 * Bloque try-catch para manejo seguro de errores de base de datos
 * y otras excepciones que puedan ocurrir durante la ejecución.
 */
try {
    /**
     * CONSULTA SQL PARA OBTENER DATOS COMPLETOS DEL MANTENIMIENTO
     * 
     * La consulta incluye:
     * - Datos principales del mantenimiento (tabla mantenimiento)
     * - Descripción del tipo de mantenimiento (tabla tipo_mantenimiento)
     * - LEFT JOIN para obtener información relacionada
     * - Filtrado por ID específico del mantenimiento
     */
    $query = $con->prepare("
        SELECT 
            m.id_mantenimiento,           -- ID único del registro de mantenimiento
            m.placa,                      -- Placa del vehículo asociado
            m.id_tipo_mantenimiento,      -- ID del tipo de mantenimiento
            m.fecha_programada,           -- Fecha programada para el mantenimiento
            m.fecha_realizada,            -- Fecha en que se realizó el mantenimiento
            m.observaciones,              -- Observaciones y notas del mantenimiento
            m.kilometraje_actual,         -- Kilometraje del vehículo al momento del mantenimiento
            m.proximo_cambio_km,          -- Kilometraje para el próximo mantenimiento
            m.proximo_cambio_fecha,       -- Fecha estimada para el próximo mantenimiento
            tm.descripcion as tipo_descripcion  -- Descripción del tipo de mantenimiento
        FROM mantenimiento m
        LEFT JOIN tipo_mantenimiento tm ON m.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
        WHERE m.id_mantenimiento = :id
    ");
    
    // Vinculación segura del parámetro ID para prevenir inyección SQL
    $query->bindParam(':id', $id);
    
    // Ejecución de la consulta preparada
    $query->execute();
    
    /**
     * OBTENCIÓN Y PROCESAMIENTO DE RESULTADOS
     * 
     * fetch(PDO::FETCH_ASSOC) retorna un array asociativo con los datos
     * o false si no se encuentra ningún registro.
     */
    $mantenimiento = $query->fetch(PDO::FETCH_ASSOC);
    
    /**
     * GENERACIÓN DE RESPUESTA JSON
     * 
     * Estructura de respuesta exitosa:
     * {
     *   "success": true,
     *   "mantenimiento": {
     *     "id_mantenimiento": "123",
     *     "placa": "ABC123",
     *     "tipo_descripcion": "Cambio de aceite",
     *     // ... otros campos
     *   }
     * }
     */
    if ($mantenimiento) {
        echo json_encode([
            'success' => true, 
            'mantenimiento' => $mantenimiento
        ]);
    } else {
        // Respuesta cuando no se encuentra el mantenimiento solicitado
        echo json_encode(['success' => false, 'message' => 'Mantenimiento no encontrado']);
    }
    
} catch (Exception $e) {
    /**
     * MANEJO DE EXCEPCIONES
     * 
     * Captura cualquier error que pueda ocurrir durante:
     * - La preparación de la consulta
     * - La ejecución de la consulta
     * - El procesamiento de datos
     * 
     * Retorna un mensaje de error genérico con detalles de la excepción
     * para facilitar la depuración en desarrollo.
     */
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>