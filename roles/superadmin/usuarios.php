<?php
session_start();

// Verificar autenticación de superadmin
if (!isset($_SESSION['documento']) || $_SESSION['tipo'] != 3) {
    header('Location: ../../login/login.php');
    exit;
}

$nombre_superadmin = $_SESSION['documento'] ?? 'Superadmin';

require_once '../../conecct/conex.php';
$database = new Database();
$conexion = $database->conectar();

// Obtener usuarios
try {
    $stmt = $conexion->prepare("
        SELECT u.*, eu.tipo_stade as estado_usuario, r.tipo_rol as rol_nombre
        FROM usuarios u
        LEFT JOIN estado_usuario eu ON u.id_estado_usuario = eu.id_estado
        LEFT JOIN roles r ON u.id_rol = r.id_rol
        ORDER BY u.nombre_completo
    ");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $usuarios = [];
}

// Obtener roles para el formulario
try {
    $stmt_roles = $conexion->prepare("SELECT * FROM roles ORDER BY tip_rol");
    $stmt_roles->execute();
    $roles = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $roles = [];
}

// Obtener estados para el formulario
try {
    $stmt_estados = $conexion->prepare("SELECT * FROM estado_usuario ORDER BY tipo_stade");
    $stmt_estados->execute();
    $estados = $stmt_estados->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $estados = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Superadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .superadmin-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: none;
        }
        .card-header {
            background: linear-gradient(135deg, #d32f2f, #b71c1c);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #d32f2f, #b71c1c);
            border: none;
            color: white;
            border-radius: 10px;
        }
        .btn-gradient:hover {
            background: linear-gradient(135deg, #b71c1c, #8e0000);
            color: white;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-activo {
            background-color: #d4edda;
            color: #155724;
        }
        .status-inactivo {
            background-color: #f8d7da;
            color: #721c24;
        }
        .role-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        .role-admin {
            background-color: #fff3cd;
            color: #856404;
        }
        .role-usuario {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .role-superadmin {
            background-color: #d4edda;
            color: #155724;
        }
        
        /* Estilos del sidebar */
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --dark-color: #34495e;
            --light-color: #ecf0f1;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .main-content {
            padding: 20px;
        }
        
        .user-info {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 15px;
            margin: 20px 10px;
            text-align: center;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--secondary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 1.5rem;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h6 class="text-white mb-1"><?php echo htmlspecialchars($nombre_superadmin); ?></h6>
                    <small class="text-white-50">Superadministrador</small>
                </div>
                
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a class="nav-link active" href="usuarios.php">
                        <i class="fas fa-users me-2"></i> Gestión de Usuarios
                    </a>
                    <a class="nav-link" href="vehiculos.php">
                        <i class="fas fa-truck me-2"></i> Gestión de Vehículos
                    </a>
                    <a class="nav-link" href="licenciamiento.php">
                        <i class="fas fa-certificate me-2"></i> Licenciamiento
                    </a>
                    <hr class="text-white-50">
                    <a class="nav-link text-danger" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">Gestión de Usuarios</h1>
                    <button class="btn btn-primary" onclick="abrirModalNuevoUsuario()">
                        <i class="bi bi-plus-circle"></i> Nuevo Usuario
                    </button>
                </div>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h4 class="text-primary"><?php echo count($usuarios); ?></h4>
                            <p class="text-muted">Total Usuarios</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h4 class="text-success"><?php echo count(array_filter($usuarios, function($u) { return $u['id_estado_usuario'] == 1; })); ?></h4>
                            <p class="text-muted">Usuarios Activos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h4 class="text-warning"><?php echo count(array_filter($usuarios, function($u) { return $u['id_rol'] == 1; })); ?></h4>
                            <p class="text-muted">Administradores</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h4 class="text-info"><?php echo count(array_filter($usuarios, function($u) { return $u['id_rol'] == 2; })); ?></h4>
                            <p class="text-muted">Usuarios Normales</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Usuarios -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Documento</th>
                                            <th>Nombre Completo</th>
                                            <th>Email</th>
                                            <th>Teléfono</th>
                                            <th>Rol</th>
                                            <th>Estado</th>
                                            <th>Fecha Registro</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $usuario): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($usuario['documento']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($usuario['nombre_completo']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['telefono']); ?></td>
                                            <td>
                                                <span class="role-badge role-<?php echo strtolower($usuario['rol_nombre'] ?? 'usuario'); ?>">
                                                    <?php echo htmlspecialchars($usuario['rol_nombre'] ?? 'Usuario'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $usuario['id_estado_usuario'] == 1 ? 'activo' : 'inactivo'; ?>">
                                                    <?php echo htmlspecialchars($usuario['estado_usuario'] ?? 'Desconocido'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($usuario['joined_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editarUsuario('<?php echo $usuario['documento']; ?>')">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarUsuario('<?php echo $usuario['documento']; ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo/Editar Usuario -->
    <div class="modal fade" id="modalUsuario" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Nuevo Usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formUsuario">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Documento</label>
                                <input type="text" class="form-control" name="documento" id="documento" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" name="nombre_completo" id="nombre_completo" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" name="telefono" id="telefono" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contraseña</label>
                                <input type="password" class="form-control" name="password" id="password">
                                <small class="text-muted">Dejar vacío para mantener la actual (en edición)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rol</label>
                                <select class="form-select" name="id_rol" id="id_rol" required>
                                    <option value="">Seleccionar Rol</option>
                                    <?php foreach ($roles as $rol): ?>
                                    <option value="<?php echo $rol['id_rol']; ?>">
                                        <?php echo htmlspecialchars($rol['tip_rol']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="id_estado_usuario" id="id_estado_usuario" required>
                                    <option value="">Seleccionar Estado</option>
                                    <?php foreach ($estados as $estado): ?>
                                    <option value="<?php echo $estado['id_estado']; ?>">
                                        <?php echo htmlspecialchars($estado['tipo_stade']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let modalUsuario;
        let modoEdicion = false;
        let documentoActual = '';

        document.addEventListener('DOMContentLoaded', function() {
            modalUsuario = new bootstrap.Modal(document.getElementById('modalUsuario'));
            
            document.getElementById('formUsuario').addEventListener('submit', function(e) {
                e.preventDefault();
                guardarUsuario();
            });
        });

        function abrirModalNuevoUsuario() {
            modoEdicion = false;
            documentoActual = '';
            document.getElementById('modalTitle').textContent = 'Nuevo Usuario';
            document.getElementById('formUsuario').reset();
            document.getElementById('password').required = true;
            modalUsuario.show();
        }

        function editarUsuario(documento) {
            modoEdicion = true;
            documentoActual = documento;
            document.getElementById('modalTitle').textContent = 'Editar Usuario';
            document.getElementById('password').required = false;
            
            // Cargar datos del usuario
            fetch('usuarios_backend.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=obtener_usuario&documento=' + documento
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const usuario = data.usuario;
                    document.getElementById('documento').value = usuario.documento;
                    document.getElementById('nombre_completo').value = usuario.nombre_completo;
                    document.getElementById('email').value = usuario.email;
                    document.getElementById('telefono').value = usuario.telefono;
                    document.getElementById('id_rol').value = usuario.id_rol;
                    document.getElementById('id_estado_usuario').value = usuario.id_estado_usuario;
                    modalUsuario.show();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Error al cargar datos del usuario', 'error');
            });
        }

        function guardarUsuario() {
            const formData = new FormData(document.getElementById('formUsuario'));
            formData.append('action', modoEdicion ? 'actualizar_usuario' : 'crear_usuario');
            
            if (modoEdicion) {
                formData.append('documento_original', documentoActual);
            }

            fetch('usuarios_backend.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: data.message
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Error al procesar la solicitud', 'error');
            });
        }

        function eliminarUsuario(documento) {
            Swal.fire({
                title: '¿Eliminar Usuario?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'eliminar_usuario');
                    formData.append('documento', documento);

                    fetch('usuarios_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Eliminado', data.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error', 'Error al eliminar usuario', 'error');
                    });
                }
            });
        }
    </script>
</body>
</html> 