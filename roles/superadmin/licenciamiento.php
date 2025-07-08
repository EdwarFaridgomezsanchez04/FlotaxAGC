<?php
session_start();

// Verificar autenticación de superadmin
if (!isset($_SESSION['documento']) || $_SESSION['tipo'] != 3) {
    header('Location: ../../login/login.php');
    exit;
}

$nombre_superadmin = $_SESSION['documento'] ?? 'Superadmin';
$documento_superadmin = $_SESSION['documento'] ?? '';

require_once '../../includes/validarsession.php';
require_once '../../conecct/conex.php';
// Remover esta línea: require_once 'auth_superadmin.php';

try {
    $database = new Database();
    $conexion = $database->conectar();
    
    // Verificar y crear tablas si no existen
    $stmt = $conexion->prepare("SHOW TABLES LIKE 'empresas'");
    $stmt->execute();
    $tabla_empresas_existe = $stmt->rowCount() > 0;
    
    if (!$tabla_empresas_existe) {
        $sql_empresas = "
            CREATE TABLE IF NOT EXISTS empresas (
                id_empresa INT AUTO_INCREMENT PRIMARY KEY,
                nombre_empresa VARCHAR(255) NOT NULL,
                nit VARCHAR(20) UNIQUE NOT NULL,
                direccion VARCHAR(255),
                telefono VARCHAR(20),
                email VARCHAR(100),
                fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                estado ENUM('activa', 'inactiva') DEFAULT 'activa'
            )
        ";
        $conexion->exec($sql_empresas);
        
        // Insertar empresa por defecto
        $stmt = $conexion->prepare("
            INSERT INTO empresas (nombre_empresa, nit, direccion, telefono, email) 
            VALUES ('FlotaX AGC', '900123456-1', 'Calle Principal 123', '3001234567', 'admin@flotaxagc.com')
        ");
        $stmt->execute();
    }
    
    $stmt = $conexion->prepare("SHOW TABLES LIKE 'sistema_licencias'");
    $stmt->execute();
    $tabla_licencias_existe = $stmt->rowCount() > 0;
    
    if (!$tabla_licencias_existe) {
        // La tabla ya existe en la base de datos, no necesitamos crearla
        // Solo insertar licencia por defecto si no hay ninguna
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM sistema_licencias");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            // Insertar licencia por defecto
            $stmt = $conexion->prepare("
                INSERT INTO sistema_licencias (nombre_empresa, tipo_licencia, fecha_inicio, fecha_vencimiento, max_usuarios, max_vehiculos, clave_licencia) 
                VALUES ('FlotaX AGC', 'empresarial', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 100, 500, ?)
            ");
            $clave_default = 'FLOTAX-' . strtoupper(bin2hex(random_bytes(8)));
            $stmt->execute([$clave_default]);
        }
    }
    
    // Obtener licencias
    $stmt = $conexion->prepare("
        SELECT * FROM sistema_licencias ORDER BY fecha_creacion DESC
    ");
    $stmt->execute();
    $licencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener la licencia actual (la más reciente activa)
    $licencia_actual = null;
    foreach ($licencias as $licencia) {
        if ($licencia['estado'] === 'activa') {
            $licencia_actual = $licencia;
            break;
        }
    }

    // Obtener empresas
    $stmt = $conexion->prepare("SELECT * FROM empresas WHERE estado = 'activa' ORDER BY nombre_empresa");
    $stmt->execute();
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estadísticas de uso
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM usuarios");
    $stmt->execute();
    $usuarios_actuales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM vehiculos");
    $stmt->execute();
    $vehiculos_actuales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch (Exception $e) {
    error_log("Error en licenciamiento: " . $e->getMessage());
    $licencias = [];
    $empresas = [];
    $usuarios_actuales = 0;
    $vehiculos_actuales = 0;
    $licencia_actual = null;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Licenciamiento - Sistema de Gestión de Flota</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --dark-color: #34495e;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
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
        
        .license-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            margin-bottom: 20px;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .status-activa { background: linear-gradient(135deg, #11998e, #38ef7d); color: white; }
        .status-vencida { background: linear-gradient(135deg, #ff416c, #ff4b2b); color: white; }
        .status-suspendida { background: linear-gradient(135deg, #f093fb, #f5576c); color: white; }
        
        .progress-custom {
            height: 10px;
            border-radius: 10px;
            background: rgba(0,0,0,0.1);
        }
        
        .progress-bar-custom {
            border-radius: 10px;
            transition: width 0.6s ease;
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
        
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
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
                    <a class="nav-link" href="usuarios.php">
                        <i class="fas fa-users me-2"></i> Gestión de Usuarios
                    </a>
                    <a class="nav-link" href="vehiculos.php">
                        <i class="fas fa-truck me-2"></i> Gestión de Vehículos
                    </a>
                    <a class="nav-link active" href="licenciamiento.php">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0 text-white"><i class="fas fa-certificate me-3"></i>Gestión de Licenciamiento</h1>
                    <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#modalNuevaLicencia">
                        <i class="fas fa-plus me-2"></i>Nueva Licencia
                    </button>
                </div>
                
                <?php if ($licencia_actual): ?>
                <!-- Información de Licencia Actual -->
                <div class="license-card">
                    <div class="row">
                        <div class="col-md-8">
                            <h4 class="mb-3"><i class="fas fa-building me-2 text-primary"></i><?php echo htmlspecialchars($licencia_actual['nombre_empresa']); ?></h4>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Tipo de Licencia:</strong></p>
                                    <span class="badge bg-primary fs-6"><?php echo ucfirst($licencia_actual['tipo_licencia']); ?></span>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Estado:</strong></p>
                                    <span class="status-badge status-<?php echo $licencia_actual['estado']; ?>">
                                        <?php echo ucfirst($licencia_actual['estado']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Fecha de Inicio:</strong></p>
                                    <p class="text-muted"><?php echo date('d/m/Y', strtotime($licencia_actual['fecha_inicio'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Fecha de Vencimiento:</strong></p>
                                    <p class="text-muted"><?php echo date('d/m/Y', strtotime($licencia_actual['fecha_vencimiento'])); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="mb-3">
                                <i class="fas fa-certificate fa-4x text-warning"></i>
                            </div>
                            <p class="mb-1"><strong>Clave de Licencia:</strong></p>
                            <code class="fs-6"><?php echo htmlspecialchars($licencia_actual['clave_licencia']); ?></code>
                        </div>
                    </div>
                </div>
                
                <!-- Estadísticas de Uso -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="license-card">
                            <h5 class="mb-3"><i class="fas fa-users me-2 text-success"></i>Uso de Usuarios</h5>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Usuarios Actuales: <strong><?php echo $usuarios_actuales; ?></strong></span>
                                <span>Límite: <strong><?php echo $licencia_actual['max_usuarios']; ?></strong></span>
                            </div>
                            <div class="progress progress-custom">
                                <?php 
                                $porcentaje_usuarios = ($usuarios_actuales / $licencia_actual['max_usuarios']) * 100;
                                $color_usuarios = $porcentaje_usuarios > 80 ? 'bg-danger' : ($porcentaje_usuarios > 60 ? 'bg-warning' : 'bg-success');
                                ?>
                                <div class="progress-bar progress-bar-custom <?php echo $color_usuarios; ?>" 
                                     style="width: <?php echo min($porcentaje_usuarios, 100); ?>%"></div>
                            </div>
                            <small class="text-muted"><?php echo number_format($porcentaje_usuarios, 1); ?>% utilizado</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="license-card">
                            <h5 class="mb-3"><i class="fas fa-car me-2 text-info"></i>Uso de Vehículos</h5>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Vehículos Actuales: <strong><?php echo $vehiculos_actuales; ?></strong></span>
                                <span>Límite: <strong><?php echo $licencia_actual['max_vehiculos']; ?></strong></span>
                            </div>
                            <div class="progress progress-custom">
                                <?php 
                                $porcentaje_vehiculos = ($vehiculos_actuales / $licencia_actual['max_vehiculos']) * 100;
                                $color_vehiculos = $porcentaje_vehiculos > 80 ? 'bg-danger' : ($porcentaje_vehiculos > 60 ? 'bg-warning' : 'bg-success');
                                ?>
                                <div class="progress-bar progress-bar-custom <?php echo $color_vehiculos; ?>" 
                                     style="width: <?php echo min($porcentaje_vehiculos, 100); ?>%"></div>
                            </div>
                            <small class="text-muted"><?php echo number_format($porcentaje_vehiculos, 1); ?>% utilizado</small>
                        </div>
                    </div>
                </div>
                
                <!-- Acciones Rápidas -->
                <div class="license-card">
                    <h5 class="mb-3"><i class="fas fa-tools me-2 text-primary"></i>Acciones Rápidas</h5>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-outline-primary w-100" onclick="renovarLicencia()">
                                <i class="fas fa-sync-alt me-2"></i>Renovar Licencia
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-outline-success w-100" onclick="ampliarLimites()">
                                <i class="fas fa-expand-arrows-alt me-2"></i>Ampliar Límites
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-outline-warning w-100" onclick="exportarLicencia()">
                                <i class="fas fa-download me-2"></i>Exportar Info
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-outline-danger w-100" onclick="suspenderLicencia()">
                                <i class="fas fa-pause me-2"></i>Suspender
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-outline-info w-100" onclick="verificarLicencias()">
                                <i class="fas fa-search me-2"></i>Verificar Licencias
                            </button>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="license-card text-center">
                    <i class="fas fa-exclamation-triangle fa-4x text-warning mb-3"></i>
                    <h4>No hay licencia configurada</h4>
                    <p class="text-muted mb-4">Configure una licencia para comenzar a usar el sistema</p>
                    <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#modalNuevaLicencia">
                        <i class="fas fa-plus me-2"></i>Crear Primera Licencia
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal Nueva Licencia -->
    <div class="modal fade" id="modalNuevaLicencia" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-certificate me-2"></i>Nueva Licencia</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formNuevaLicencia">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre de la Empresa</label>
                                <input type="text" class="form-control" name="nombre_empresa" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Licencia</label>
                                <select class="form-select" name="tipo_licencia" required>
                                    <option value="basica">Básica (10 usuarios, 50 vehículos)</option>
                                    <option value="profesional">Profesional (50 usuarios, 200 vehículos)</option>
                                    <option value="empresarial">Empresarial (100 usuarios, 500 vehículos)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha de Inicio</label>
                                <input type="date" class="form-control" name="fecha_inicio" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha de Vencimiento</label>
                                <input type="date" class="form-control" name="fecha_vencimiento" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Licencia</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Manejar formulario de nueva licencia
        document.getElementById('formNuevaLicencia').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'crear_licencia');
            
            // Debug: mostrar datos que se envían
            console.log('Enviando datos:', {
                nombre_empresa: formData.get('nombre_empresa'),
                tipo_licencia: formData.get('tipo_licencia'),
                fecha_inicio: formData.get('fecha_inicio'),
                fecha_vencimiento: formData.get('fecha_vencimiento'),
                action: formData.get('action')
            });
            
            try {
                const response = await fetch('licenciamiento_backend.php', {
                    method: 'POST',
                    body: formData
                });
                
                console.log('Respuesta del servidor:', response);
                
                const data = await response.json();
                console.log('Datos recibidos:', data);
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Licencia Creada',
                        text: data.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            } catch (error) {
                console.error('Error en la petición:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al procesar la solicitud: ' + error.message
                });
            }
        });
        
        // Funciones de acciones rápidas
        function renovarLicencia() {
            Swal.fire({
                title: 'Renovar Licencia',
                html: `
                    <div class="mb-3">
                        <label class="form-label">Nueva fecha de vencimiento:</label>
                        <input type="date" id="nueva_fecha_vencimiento" class="form-control" required>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Renovar',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    const fecha = document.getElementById('nueva_fecha_vencimiento').value;
                    if (!fecha) {
                        Swal.showValidationMessage('Por favor ingrese una fecha');
                        return false;
                    }
                    return fecha;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'renovar_licencia');
                    formData.append('nueva_fecha_vencimiento', result.value);
                    
                    // Obtener el ID de la licencia actual desde el PHP
                    <?php if (isset($licencia_actual) && $licencia_actual): ?>
                    formData.append('id_licencia', '<?php echo $licencia_actual['id_licencia']; ?>');
                    <?php else: ?>
                    // Si no hay licencia actual, usar 1 como fallback
                    formData.append('id_licencia', '1');
                    <?php endif; ?>
                    
                    fetch('licenciamiento_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Licencia Renovada',
                                text: data.message,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al renovar licencia: ' + error.message
                        });
                    });
                }
            });
        }
        
        function ampliarLimites() {
            Swal.fire({
                title: 'Ampliar Límites',
                html: `
                    <div class="mb-3">
                        <label class="form-label">Nuevos límites de usuarios:</label>
                        <input type="number" id="nuevos_usuarios" class="form-control" min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nuevos límites de vehículos:</label>
                        <input type="number" id="nuevos_vehiculos" class="form-control" min="1">
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Aplicar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire('Actualizado', 'Los límites han sido actualizados', 'success');
                }
            });
        }
        
        function exportarLicencia() {
            // Implementar exportación
            Swal.fire('Exportando', 'Generando archivo de información...', 'info');
        }
        
        function suspenderLicencia() {
            Swal.fire({
                title: '¿Suspender Licencia?',
                text: 'Esto desactivará temporalmente el sistema y bloqueará el acceso de todos los usuarios',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, suspender',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'suspender_licencia');
                    
                    // Obtener el ID de la licencia actual desde el PHP
                    <?php if (isset($licencia_actual) && $licencia_actual): ?>
                    formData.append('id_licencia', '<?php echo $licencia_actual['id_licencia']; ?>');
                    <?php else: ?>
                    // Si no hay licencia actual, usar 1 como fallback
                    formData.append('id_licencia', '1');
                    <?php endif; ?>
                    
                    // Debug: mostrar datos que se envían
                    console.log('Suspender - Datos a enviar:', {
                        action: formData.get('action'),
                        id_licencia: formData.get('id_licencia')
                    });
                    
                    fetch('licenciamiento_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        console.log('Suspender - Response status:', response.status);
                        console.log('Suspender - Response headers:', response.headers);
                        
                        // Verificar si la respuesta es JSON
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            throw new Error('Respuesta no es JSON. Content-Type: ' + contentType);
                        }
                        
                        return response.text().then(text => {
                            console.log('Suspender - Response text:', text);
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                throw new Error('Error parseando JSON: ' + text);
                            }
                        });
                    })
                    .then(data => {
                        console.log('Suspender - Data recibida:', data);
                        
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Licencia Suspendida',
                                text: data.message,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Suspender - Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al suspender licencia: ' + error.message
                        });
                    });
                }
            });
        }
        
        function verificarLicencias() {
            Swal.fire({
                title: 'Verificando licencias...',
                text: 'Procesando verificación automática',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            const formData = new FormData();
            formData.append('action', 'verificar_licencias');
            
            fetch('licenciamiento_backend.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let mensaje = `Verificación completada\n\n`;
                    mensaje += `Licencias vencidas: ${data.vencidas}\n`;
                    mensaje += `Licencias próximas a vencer: ${data.proximas}`;
                    
                    Swal.fire({
                        icon: 'info',
                        title: 'Verificación Completada',
                        text: mensaje,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al verificar licencias: ' + error.message
                });
            });
        }
    </script>
</body>
</html>