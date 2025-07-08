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
    case 'crear_usuario':
        crearUsuario($conexion);
        break;
    case 'actualizar_usuario':
        actualizarUsuario($conexion);
        break;
    case 'eliminar_usuario':
        eliminarUsuario($conexion);
        break;
    case 'obtener_usuario':
        obtenerUsuario($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function crearUsuario($conexion) {
    try {
        $documento = $_POST['documento'] ?? '';
        $nombre_completo = $_POST['nombre_completo'] ?? '';
        $email = $_POST['email'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $password = $_POST['password'] ?? '';
        $id_rol = $_POST['id_rol'] ?? '';
        $id_estado_usuario = $_POST['id_estado_usuario'] ?? '';

        if (empty($documento) || empty($nombre_completo) || empty($email) || empty($password) || empty($id_rol)) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
            return;
        }

        // Verificar si el usuario ya existe
        $stmt = $conexion->prepare("SELECT documento FROM usuarios WHERE documento = ?");
        $stmt->execute([$documento]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'El documento ya está registrado']);
            return;
        }

        // Verificar si el email ya existe
        $stmt = $conexion->prepare("SELECT email FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'El email ya está registrado']);
            return;
        }

        // Hash de la contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Insertar usuario
        $stmt = $conexion->prepare("
            INSERT INTO usuarios (documento, nombre_completo, email, password, telefono, id_rol, id_estado_usuario, joined_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $documento,
            $nombre_completo,
            $email,
            $password_hash,
            $telefono,
            $id_rol,
            $id_estado_usuario ?: 1
        ]);

        echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al crear usuario: ' . $e->getMessage()]);
    }
}

function actualizarUsuario($conexion) {
    try {
        $documento_original = $_POST['documento_original'] ?? '';
        $documento = $_POST['documento'] ?? '';
        $nombre_completo = $_POST['nombre_completo'] ?? '';
        $email = $_POST['email'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $password = $_POST['password'] ?? '';
        $id_rol = $_POST['id_rol'] ?? '';
        $id_estado_usuario = $_POST['id_estado_usuario'] ?? '';

        if (empty($documento_original) || empty($documento) || empty($nombre_completo) || empty($email) || empty($id_rol)) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
            return;
        }

        // Verificar si el nuevo documento ya existe (si cambió)
        if ($documento !== $documento_original) {
            $stmt = $conexion->prepare("SELECT documento FROM usuarios WHERE documento = ? AND documento != ?");
            $stmt->execute([$documento, $documento_original]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'El documento ya está registrado']);
                return;
            }
        }

        // Verificar si el nuevo email ya existe (si cambió)
        $stmt = $conexion->prepare("SELECT email FROM usuarios WHERE email = ? AND documento != ?");
        $stmt->execute([$email, $documento_original]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'El email ya está registrado']);
            return;
        }

        // Construir query de actualización
        $sql = "UPDATE usuarios SET documento = ?, nombre_completo = ?, email = ?, telefono = ?, id_rol = ?, id_estado_usuario = ?";
        $params = [$documento, $nombre_completo, $email, $telefono, $id_rol, $id_estado_usuario ?: 1];

        // Si se proporcionó una nueva contraseña, incluirla en la actualización
        if (!empty($password)) {
            $sql .= ", password = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE documento = ?";
        $params[] = $documento_original;

        $stmt = $conexion->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar usuario: ' . $e->getMessage()]);
    }
}

function eliminarUsuario($conexion) {
    try {
        $documento = $_POST['documento'] ?? '';

        if (empty($documento)) {
            echo json_encode(['success' => false, 'message' => 'Documento requerido']);
            return;
        }

        // Verificar que no sea superadmin
        $stmt = $conexion->prepare("SELECT id_rol FROM usuarios WHERE documento = ?");
        $stmt->execute([$documento]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
            return;
        }

        if ($usuario['id_rol'] == 3) {
            echo json_encode(['success' => false, 'message' => 'No se puede eliminar un superadmin']);
            return;
        }

        // Eliminar usuario
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE documento = ?");
        $stmt->execute([$documento]);

        echo json_encode(['success' => true, 'message' => 'Usuario eliminado exitosamente']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar usuario: ' . $e->getMessage()]);
    }
}

function obtenerUsuario($conexion) {
    try {
        $documento = $_POST['documento'] ?? '';

        if (empty($documento)) {
            echo json_encode(['success' => false, 'message' => 'Documento requerido']);
            return;
        }

        $stmt = $conexion->prepare("
            SELECT documento, nombre_completo, email, telefono, id_rol, id_estado_usuario 
            FROM usuarios 
            WHERE documento = ?
        ");
        $stmt->execute([$documento]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
            return;
        }

        echo json_encode(['success' => true, 'usuario' => $usuario]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener usuario: ' . $e->getMessage()]);
    }
}
?> 