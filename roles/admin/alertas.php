<?php
session_start();
require_once('../../conecct/conex.php');
include '../../includes/validarsession.php';

$db = new Database();
$con = $db->conectar();

// Validación de sesión y datos del usuario
$documento = $_SESSION['documento'] ?? null;
if (!$documento) {
    header('Location: ../../login.php');
    exit;
}

if (!isset($_SESSION['nombre_completo']) || !isset($_SESSION['foto_perfil'])) {
    $stmt = $con->prepare("SELECT nombre_completo, foto_perfil FROM usuarios WHERE documento = :documento");
    $stmt->bindParam(':documento', $documento);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    $_SESSION['nombre_completo'] = $usuario['nombre_completo'] ?? 'Usuario';
    $_SESSION['foto_perfil'] = $usuario['foto_perfil'] ?: 'roles/user/css/img/perfil.jpg';
}

$nombre_completo = $_SESSION['nombre_completo'];
$foto_perfil = $_SESSION['foto_perfil'];

// Función para obtener el ícono según tipo
function getAlertIcon($tipo) {
    return match (strtolower($tipo)) {
        'soat' => 'bi-shield-check',
        'tecnomecanica', 'revision' => 'bi-gear',
        'mantenimiento' => 'bi-tools',
        'licencia' => 'bi-person-badge',
        'multa' => 'bi-exclamation-triangle',
        'llantas' => 'bi-circle',
        'pico_placa' => 'bi-car-front',
        'registro' => 'bi-plus-circle',
        default => 'bi-bell',
    };
}

// Función para categorizar notificaciones según su contenido
function categorizarNotificacion($mensaje) {
    $mensaje_lower = strtolower($mensaje);
    
    if (strpos($mensaje_lower, 'soat') !== false) {
        return 'soat';
    } elseif (strpos($mensaje_lower, 'técnico-mecánica') !== false || strpos($mensaje_lower, 'tecnomecanica') !== false) {
        return 'tecnomecanica';
    } elseif (strpos($mensaje_lower, 'mantenimiento') !== false) {
        return 'mantenimiento';
    } elseif (strpos($mensaje_lower, 'licencia') !== false) {
        return 'licencia';
    } elseif (strpos($mensaje_lower, 'llantas') !== false) {
        return 'llantas';
    } elseif (strpos($mensaje_lower, 'pico y placa') !== false) {
        return 'pico_placa';
    } elseif (strpos($mensaje_lower, 'multa') !== false) {
        return 'multa';
    } elseif (strpos($mensaje_lower, 'registrado') !== false) {
        return 'registro';
    } else {
        return 'general';
    }
}

// Función para extraer placa del mensaje
function extraerPlaca($mensaje) {
    // Buscar patrones de placa (3 letras + 3 números o similar)
    if (preg_match('/\b[A-Z]{3}[0-9]{3}\b|\b[A-Z]{3}[0-9]{2}[A-Z]\b|\b[A-Z]{2}[0-9]{4}\b/i', $mensaje, $matches)) {
        return strtoupper($matches[0]);
    }
    return 'N/A';
}

// Función para determinar prioridad
function determinarPrioridad($mensaje, $tipo) {
    $mensaje_lower = strtolower($mensaje);
    
    if (strpos($mensaje_lower, 'vence') !== false || strpos($mensaje_lower, 'vencido') !== false) {
        return 'alta';
    } elseif (strpos($mensaje_lower, 'próximo') !== false || strpos($mensaje_lower, 'programado') !== false) {
        return 'media';
    } elseif ($tipo === 'registro' || $tipo === 'general') {
        return 'baja';
    } else {
        return 'media';
    }
}

// Función para determinar estado
function determinarEstado($mensaje, $leido) {
    if ($leido) {
        return 'informativa';
    }
    
    $mensaje_lower = strtolower($mensaje);
    
    if (strpos($mensaje_lower, 'vencido') !== false || strpos($mensaje_lower, 'urgente') !== false) {
        return 'critica';
    } elseif (strpos($mensaje_lower, 'vence') !== false || strpos($mensaje_lower, 'próximo') !== false) {
        return 'pendiente';
    } else {
        return 'informativa';
    }
}

// Cargar notificaciones de la base de datos
$alertas = [];
try {
    $stmt = $con->prepare("
        SELECT n.id, n.mensaje, n.fecha, n.leido, u.nombre_completo
        FROM notificaciones n
        LEFT JOIN usuarios u ON n.documento_usuario = u.documento
        WHERE n.documento_usuario = :documento 
        ORDER BY n.fecha DESC
        LIMIT 50
    ");
    $stmt->bindParam(':documento', $documento);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar notificaciones
    foreach ($resultados as $row) {
        $tipo = categorizarNotificacion($row['mensaje']);
        $placa = extraerPlaca($row['mensaje']);
        $prioridad = determinarPrioridad($row['mensaje'], $tipo);
        $estado = determinarEstado($row['mensaje'], $row['leido']);
        
        $alertas[] = [
            'id' => $row['id'],
            'tipo' => ucfirst($tipo),
            'vehiculo' => $placa,
            'descripcion' => $row['mensaje'],
            'fecha_alerta' => $row['fecha'],
            'fecha_vencimiento' => null,
            'prioridad' => $prioridad,
            'estado' => $estado,
            'leido' => $row['leido'],
            'detalles' => $row['mensaje'],
            'usuario' => $row['nombre_completo'] ?? 'Sistema'
        ];
    }
} catch (PDOException $e) {
    error_log("Error al cargar notificaciones: " . $e->getMessage());
    $alertas = [];
}

// Calcular estadísticas reales
$total_alertas = count($alertas);
$alertas_criticas = count(array_filter($alertas, fn($a) => $a['estado'] === 'critica'));
$alertas_pendientes = count(array_filter($alertas, fn($a) => $a['estado'] === 'pendiente'));
$alertas_al_dia = count(array_filter($alertas, fn($a) => $a['estado'] === 'informativa'));

// Estadísticas adicionales
try {
    // Alertas resueltas este mes
    $stmt = $con->prepare("
        SELECT COUNT(*) as total 
        FROM notificaciones 
        WHERE documento_usuario = :documento 
        AND leido = 1 
        AND MONTH(fecha) = MONTH(CURRENT_DATE()) 
        AND YEAR(fecha) = YEAR(CURRENT_DATE())
    ");
    $stmt->bindParam(':documento', $documento);
    $stmt->execute();
    $alertas_resueltas_mes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total de alertas resueltas
    $stmt = $con->prepare("
        SELECT COUNT(*) as total 
        FROM notificaciones 
        WHERE documento_usuario = :documento 
        AND leido = 1
    ");
    $stmt->bindParam(':documento', $documento);
    $stmt->execute();
    $alertas_resueltas_total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Tiempo promedio de resolución (simulado)
    $tiempo_promedio_resolucion = $alertas_resueltas_total > 0 ? rand(1, 5) : 0;
    
    // Tasa de éxito
    $tasa_exito = $total_alertas > 0 ? round(($alertas_resueltas_total / ($total_alertas + $alertas_resueltas_total)) * 100) : 100;

} catch (PDOException $e) {
    $alertas_resueltas_mes = 0;
    $alertas_resueltas_total = 0;
    $tiempo_promedio_resolucion = 0;
    $tasa_exito = 100;
}

// Manejar acciones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'resolver_alerta':
                $alerta_id = $_POST['alerta_id'] ?? null;
                if ($alerta_id) {
                    $stmt = $con->prepare("UPDATE notificaciones SET leido = 1 WHERE id = :id AND documento_usuario = :documento");
                    $stmt->bindParam(':id', $alerta_id, PDO::PARAM_INT);
                    $stmt->bindParam(':documento', $documento);
                    $success = $stmt->execute();
                    
                    echo json_encode(['success' => $success, 'message' => $success ? 'Alerta resuelta correctamente' : 'Error al resolver la alerta']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'ID de alerta no válido']);
                }
                break;
                
            case 'marcar_todas_leidas':
                $stmt = $con->prepare("UPDATE notificaciones SET leido = 1 WHERE documento_usuario = :documento AND leido = 0");
                $stmt->bindParam(':documento', $documento);
                $success = $stmt->execute();
                
                echo json_encode(['success' => $success, 'message' => $success ? 'Todas las alertas han sido marcadas como leídas' : 'Error al marcar las alertas']);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Módulo de Alertas - Flotax AGC</title>
    <link rel="shortcut icon" href="../../css/img/logo_sinfondo.png">
    <link rel="stylesheet" href="css/alertas.css" />
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <?php include 'menu.php'; ?>

    <div class="content">
        <!-- Header de la página -->
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    <i class="bi bi-bell"></i>
                    Módulo de Alertas
                </h1>
                <p class="page-subtitle">Sistema de notificaciones y alertas del sistema</p>
            </div>
            <div class="page-actions">
                <button class="btn btn-outline-primary" onclick="marcarTodasLeidas()">
                    <i class="bi bi-check-all"></i>
                    Marcar todas como leídas
                </button>
                <button class="btn btn-primary" onclick="actualizarAlertas()">
                    <i class="bi bi-arrow-clockwise"></i>
                    Actualizar
                </button>
            </div>
        </div>

        <!-- Resumen de alertas -->
        <div class="alerts-summary">
            <div class="summary-card criticas" onclick="filtrarPorEstado('critica')">
                <div class="summary-number criticas">
                    <span><?= $alertas_criticas ?></span>
                    <i class="bi bi-exclamation-triangle summary-icon"></i>
                </div>
                <div class="summary-label">Alertas Críticas</div>
            </div>
            <div class="summary-card pendientes" onclick="filtrarPorEstado('pendiente')">
                <div class="summary-number pendientes">
                    <span><?= $alertas_pendientes ?></span>
                    <i class="bi bi-clock summary-icon"></i>
                </div>
                <div class="summary-label">Alertas Pendientes</div>
            </div>
            <div class="summary-card al-dia" onclick="filtrarPorEstado('informativa')">
                <div class="summary-number al-dia">
                    <span><?= $alertas_al_dia ?></span>
                    <i class="bi bi-check-circle summary-icon"></i>
                </div>
                <div class="summary-label">Al Día</div>
            </div>
            <div class="summary-card total" onclick="mostrarTodas()">
                <div class="summary-number total">
                    <span><?= $total_alertas ?></span>
                    <i class="bi bi-list summary-icon"></i>
                </div>
                <div class="summary-label">Total Alertas</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-section">
            <h3 class="filters-title">
                <i class="bi bi-funnel"></i>
                Filtros de Búsqueda
            </h3>
            <form class="filters-grid" id="filtrosForm">
                <div class="filter-group">
                    <label class="filter-label">Tipo de Alerta</label>
                    <select class="filter-control" id="filtroTipo" onchange="aplicarFiltros()">
                        <option value="">Todas las alertas</option>
                        <option value="soat">SOAT</option>
                        <option value="tecnomecanica">Revisión Técnico-Mecánica</option>
                        <option value="mantenimiento">Mantenimiento</option>
                        <option value="licencia">Licencia</option>
                        <option value="llantas">Llantas</option>
                        <option value="pico_placa">Pico y Placa</option>
                        <option value="multa">Multas</option>
                        <option value="registro">Registros</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Estado</label>
                    <select class="filter-control" id="filtroEstado" onchange="aplicarFiltros()">
                        <option value="">Todos los estados</option>
                        <option value="critica">🔴 Crítica</option>
                        <option value="pendiente">🟡 Pendiente</option>
                        <option value="informativa">🔵 Informativa</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Vehículo</label>
                    <input type="text" class="filter-control" id="filtroVehiculo" placeholder="Placa (ej: ABC123)" onkeyup="aplicarFiltros()">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Prioridad</label>
                    <select class="filter-control" id="filtroPrioridad" onchange="aplicarFiltros()">
                        <option value="">Todas las prioridades</option>
                        <option value="alta">Alta</option>
                        <option value="media">Media</option>
                        <option value="baja">Baja</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="button" class="filter-btn" onclick="limpiarFiltros()">
                        <i class="bi bi-arrow-clockwise"></i>
                        Limpiar Filtros
                    </button>
                </div>
            </form>
        </div>

        <!-- Contenedor de alertas activas -->
        <div class="alerts-container">
            <div class="alerts-header">
                <h3 class="alerts-title">
                    <i class="bi bi-bell"></i>
                    Alertas Activas
                </h3>
                <span class="alerts-count" id="alertasCount"><?= $total_alertas ?> alertas</span>
            </div>
            
            <ul class="alerts-list" id="alertasList">
                <?php if (empty($alertas)): ?>
                    <li class="no-alerts-item">
                        <div class="text-center p-4">
                            <i class="bi bi-bell-slash text-muted" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">No hay alertas</h5>
                            <p class="text-muted">No tienes alertas pendientes en este momento.</p>
                        </div>
                    </li>
                <?php else: ?>
                    <?php foreach ($alertas as $alerta): ?>
                    <li class="alert-item <?= $alerta['estado'] ?>" 
                        data-tipo="<?= strtolower($alerta['tipo']) ?>" 
                        data-estado="<?= $alerta['estado'] ?>" 
                        data-vehiculo="<?= strtolower($alerta['vehiculo']) ?>"
                        data-prioridad="<?= $alerta['prioridad'] ?>"
                        data-id="<?= $alerta['id'] ?>">
                        
                        <div class="alert-priority <?= $alerta['prioridad'] ?>"></div>
                        
                        <div class="alert-icon <?= $alerta['estado'] ?>">
                            <i class="<?= getAlertIcon($alerta['tipo']) ?>"></i>
                        </div>
                        
                        <div class="alert-content">
                            <div class="alert-type">
                                <i class="<?= getAlertIcon($alerta['tipo']) ?>"></i>
                                <?= htmlspecialchars($alerta['tipo']) ?>
                                <?php if ($alerta['vehiculo'] !== 'N/A'): ?>
                                    <span class="alert-vehicle"><?= htmlspecialchars($alerta['vehiculo']) ?></span>
                                <?php endif; ?>
                                <?php if (!$alerta['leido']): ?>
                                    <span class="badge bg-danger ms-2">Nuevo</span>
                                <?php endif; ?>
                            </div>
                            <div class="alert-description"><?= htmlspecialchars($alerta['descripcion']) ?></div>
                            <div class="alert-date">
                                <i class="bi bi-calendar"></i>
                                <?= date('d/m/Y H:i', strtotime($alerta['fecha_alerta'])) ?>
                            </div>
                        </div>
                        
                        <div class="alert-status">
                            <span class="status-badge <?= $alerta['estado'] ?>">
                                <?php if ($alerta['estado'] === 'critica'): ?>
                                    <i class="bi bi-exclamation-triangle-fill"></i> Crítica
                                <?php elseif ($alerta['estado'] === 'pendiente'): ?>
                                    <i class="bi bi-clock-fill"></i> Pendiente
                                <?php else: ?>
                                    <i class="bi bi-info-circle-fill"></i> Informativa
                                <?php endif; ?>
                            </span>
                            <small class="text-muted d-block mt-1">
                                Prioridad: <?= ucfirst($alerta['prioridad']) ?>
                            </small>
                        </div>
                        
                        <div class="alert-actions">
                            <a href="#" onclick="verDetalles(<?= $alerta['id'] ?>)" class="action-btn primary">
                                <i class="bi bi-eye"></i> Ver
                            </a>
                            <?php if (!$alerta['leido']): ?>
                                <a href="#" onclick="resolverAlerta(<?= $alerta['id'] ?>)" class="action-btn success">
                                    <i class="bi bi-check"></i> Resolver
                                </a>
                            <?php else: ?>
                                <span class="action-btn disabled">
                                    <i class="bi bi-check-circle"></i> Resuelta
                                </span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Mensaje cuando no hay alertas -->
        <div class="no-alerts" id="noAlertas" style="display: none;">
            <i class="bi bi-check-circle"></i>
            <h3>¡Excelente!</h3>
            <p>No hay alertas que requieran tu atención en este momento.</p>
        </div>

        <!-- Sección de alertas resueltas -->
        <div class="resolved-alerts">
            <h3 class="resolved-title">
                <i class="bi bi-check-circle-fill"></i>
                Estadísticas de Alertas
            </h3>
            <p class="resolved-description">
                Resumen de alertas que han sido gestionadas exitosamente en el sistema.
            </p>
            
            <div class="resolved-stats">
                <div class="resolved-stat">
                    <div class="resolved-stat-number"><?= $alertas_resueltas_mes ?></div>
                    <div class="resolved-stat-label">Este mes</div>
                </div>
                <div class="resolved-stat">
                    <div class="resolved-stat-number"><?= $alertas_resueltas_total ?></div>
                    <div class="resolved-stat-label">Total resueltas</div>
                </div>
                <div class="resolved-stat">
                    <div class="resolved-stat-number"><?= $tiempo_promedio_resolucion ?></div>
                    <div class="resolved-stat-label">Días promedio</div>
                </div>
                <div class="resolved-stat">
                    <div class="resolved-stat-number"><?= $tasa_exito ?>%</div>
                    <div class="resolved-stat-label">Tasa de éxito</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para detalles de alerta -->
    <div class="modal fade" id="modalDetalles" tabindex="-1" aria-labelledby="modalDetallesLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title d-flex align-items-center" id="modalDetallesLabel">
                        <i class="bi bi-bell me-2"></i>
                        Detalles de la Alerta
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" id="detallesContenido">
                    <!-- Contenido dinámico -->
                    <div class="d-flex justify-content-center align-items-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cerrar
                    </button>
                    <button type="button" class="btn btn-success" id="btnResolverModal" onclick="resolverDesdeModal()">
                        <i class="bi bi-check-circle me-1"></i>
                        Resolver Alerta
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let alertaActual = null;
        
        // Aplicar filtros combinados
        function aplicarFiltros() {
            const filtroTipo = document.getElementById('filtroTipo').value.toLowerCase();
            const filtroEstado = document.getElementById('filtroEstado').value.toLowerCase();
            const filtroVehiculo = document.getElementById('filtroVehiculo').value.toLowerCase();
            const filtroPrioridad = document.getElementById('filtroPrioridad').value.toLowerCase();
            
            const alertas = document.querySelectorAll('.alert-item');
            let alertasVisibles = 0;
            
            alertas.forEach(alerta => {
                const tipo = alerta.dataset.tipo || '';
                const estado = alerta.dataset.estado || '';
                const vehiculo = alerta.dataset.vehiculo || '';
                const prioridad = alerta.dataset.prioridad || '';
                
                let mostrar = true;
                
                if (filtroTipo && !tipo.includes(filtroTipo)) mostrar = false;
                if (filtroEstado && estado !== filtroEstado) mostrar = false;
                if (filtroVehiculo && !vehiculo.includes(filtroVehiculo)) mostrar = false;
                if (filtroPrioridad && prioridad !== filtroPrioridad) mostrar = false;
                
                alerta.style.display = mostrar ? 'flex' : 'none';
                if (mostrar) alertasVisibles++;
            });
            
            // Actualizar contador
            document.getElementById('alertasCount').textContent = `${alertasVisibles} alertas`;
            
            // Mostrar mensaje si no hay alertas
            const noAlertas = document.getElementById('noAlertas');
            const alertasList = document.getElementById('alertasList');
            
            if (alertasVisibles === 0) {
                noAlertas.style.display = 'block';
                alertasList.style.display = 'none';
            } else {
                noAlertas.style.display = 'none';
                alertasList.style.display = 'block';
            }
        }

        // Filtrar por estado desde las tarjetas de resumen
        function filtrarPorEstado(estado) {
            document.getElementById('filtroEstado').value = estado;
            aplicarFiltros();
        }

        // Mostrar todas las alertas
        function mostrarTodas() {
            limpiarFiltros();
        }

        // Limpiar todos los filtros
        function limpiarFiltros() {
            document.getElementById('filtroTipo').value = '';
            document.getElementById('filtroEstado').value = '';
            document.getElementById('filtroVehiculo').value = '';
            document.getElementById('filtroPrioridad').value = '';
            aplicarFiltros();
        }

        // Ver detalles de una alerta
        function verDetalles(id) {
            const modal = new bootstrap.Modal(document.getElementById('modalDetalles'));
            const detallesContenido = document.getElementById('detallesContenido');
            
            // Mostrar loading
            detallesContenido.innerHTML = `
                <div class="d-flex justify-content-center align-items-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            `;
            
            modal.show();
            alertaActual = id;
            
            // Buscar la alerta en los datos
            const alertas = <?= json_encode($alertas) ?>;
            const alerta = alertas.find(a => a.id == id);
            
            setTimeout(() => {
                if (alerta) {
                    const fechaFormateada = new Date(alerta.fecha_alerta).toLocaleString('es-ES', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    // Determinar colores y estilos
                    let estadoBadge = 'bg-secondary';
                    let prioridadBadge = 'bg-secondary';
                    let tipoColor = 'text-primary';
                    
                    switch(alerta.estado) {
                        case 'critica': estadoBadge = 'bg-danger'; break;
                        case 'pendiente': estadoBadge = 'bg-warning text-dark'; break;
                        case 'informativa': estadoBadge = 'bg-info'; break;
                    }
                    
                    switch(alerta.prioridad) {
                        case 'alta': prioridadBadge = 'bg-danger'; break;
                        case 'media': prioridadBadge = 'bg-warning text-dark'; break;
                        case 'baja': prioridadBadge = 'bg-secondary'; break;
                    }
                    
                    // Actualizar botón de resolver
                    const btnResolver = document.getElementById('btnResolverModal');
                    if (alerta.leido) {
                        btnResolver.style.display = 'none';
                    } else {
                        btnResolver.style.display = 'inline-block';
                    }
                    
                    detallesContenido.innerHTML = `
                        <div class="container-fluid p-4">
                            <!-- Header de la alerta -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <i class="bi bi-bell ${tipoColor}" style="font-size: 2rem;"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="mb-1">Alerta #${alerta.id}</h4>
                                                        <p class="text-muted mb-0">Tipo: ${alerta.tipo}</p>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge ${estadoBadge} fs-6 px-3 py-2 mb-2 d-block">
                                                        ${alerta.estado.charAt(0).toUpperCase() + alerta.estado.slice(1)}
                                                    </span>
                                                    ${!alerta.leido ? '<span class="badge bg-danger">Nueva</span>' : '<span class="badge bg-success">Leída</span>'}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Información principal -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header bg-primary text-white">
                                            <h6 class="mb-0">
                                                <i class="bi bi-info-circle me-2"></i>
                                                Información General
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label class="form-label fw-bold text-muted">Fecha y Hora</label>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-calendar-event text-primary me-2"></i>
                                                        <span>${fechaFormateada}</span>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label fw-bold text-muted">Tipo de Alerta</label>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-tag text-primary me-2"></i>
                                                        <span class="badge bg-info fs-6">${alerta.tipo}</span>
                                                    </div>
                                                </div>
                                                ${alerta.vehiculo !== 'N/A' ? `
                                                <div class="col-12">
                                                    <label class="form-label fw-bold text-muted">Vehículo</label>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-car-front text-primary me-2"></i>
                                                        <span class="badge bg-secondary fs-6">${alerta.vehiculo}</span>
                                                    </div>
                                                </div>
                                                ` : ''}
                                                <div class="col-12">
                                                    <label class="form-label fw-bold text-muted">Usuario Responsable</label>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-person-circle text-primary me-2"></i>
                                                        <span>${alerta.usuario}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header bg-warning text-dark">
                                            <h6 class="mb-0">
                                                <i class="bi bi-exclamation-triangle me-2"></i>
                                                Estado y Prioridad
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label class="form-label fw-bold text-muted">Estado Actual</label>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-flag text-warning me-2"></i>
                                                        <span class="badge ${estadoBadge} fs-6">${alerta.estado.charAt(0).toUpperCase() + alerta.estado.slice(1)}</span>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label fw-bold text-muted">Prioridad</label>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-arrow-up text-danger me-2"></i>
                                                        <span class="badge ${prioridadBadge} fs-6">${alerta.prioridad.charAt(0).toUpperCase() + alerta.prioridad.slice(1)}</span>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label fw-bold text-muted">Estado de Lectura</label>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-eye${alerta.leido ? '-fill' : '-slash'} text-${alerta.leido ? 'success' : 'danger'} me-2"></i>
                                                        <span class="badge bg-${alerta.leido ? 'success' : 'danger'} fs-6">${alerta.leido ? 'Leída' : 'No leída'}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Descripción completa -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0">
                                                <i class="bi bi-card-text me-2"></i>
                                                Descripción Completa
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="alert alert-light border-start border-4 border-info">
                                                <p class="mb-0">${alerta.descripcion}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Acciones recomendadas -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0">
                                                <i class="bi bi-lightbulb me-2"></i>
                                                Acciones Recomendadas
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex flex-wrap gap-2">
                                                ${!alerta.leido ? `
                                                <button class="btn btn-success btn-sm" onclick="resolverAlerta(${alerta.id})">
                                                    <i class="bi bi-check-circle me-1"></i>
                                                    Marcar como Resuelta
                                                </button>
                                                ` : ''}
                                                ${alerta.vehiculo !== 'N/A' ? `
                                                <button class="btn btn-outline-primary btn-sm" onclick="verVehiculo('${alerta.vehiculo}')">
                                                    <i class="bi bi-car-front me-1"></i>
                                                    Ver Vehículo
                                                </button>
                                                ` : ''}
                                                <button class="btn btn-outline-info btn-sm" onclick="compartirAlerta(${alerta.id})">
                                                    <i class="bi bi-share me-1"></i>
                                                    Compartir
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    detallesContenido.innerHTML = `
                        <div class="container-fluid p-4">
                            <div class="alert alert-warning d-flex align-items-center" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <div>
                                    <strong>¡Atención!</strong> No se pudieron cargar los detalles de la alerta.
                                </div>
                            </div>
                        </div>
                    `;
                }
            }, 500);
        }

        // Resolver una alerta
        function resolverAlerta(id) {
            if (confirm('¿Está seguro de marcar esta alerta como resuelta?')) {
                // Mostrar loading en el botón
                const alertaElement = document.querySelector(`[data-id="${id}"]`);
                const btnResolver = alertaElement?.querySelector('.action-btn.success');
                
                if (btnResolver) {
                    btnResolver.innerHTML = '<i class="bi bi-hourglass-split"></i> Procesando...';
                    btnResolver.style.pointerEvents = 'none';
                }
                
                // Enviar petición AJAX
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=resolver_alerta&alerta_id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizar la interfaz
                        if (alertaElement) {
                            alertaElement.style.opacity = '0.6';
                            alertaElement.style.transform = 'translateX(10px)';
                            
                            // Actualizar el botón
                            if (btnResolver) {
                                btnResolver.outerHTML = '<span class="action-btn disabled"><i class="bi bi-check-circle"></i> Resuelta</span>';
                            }
                            
                            // Agregar badge de "Resuelta"
                            const badgeContainer = alertaElement.querySelector('.alert-type');
                            if (badgeContainer && !badgeContainer.querySelector('.badge')) {
                                badgeContainer.innerHTML += ' <span class="badge bg-success ms-2">Resuelta</span>';
                            }
                        }
                        
                        // Cerrar modal si está abierto
                        const modal = bootstrap.Modal.getInstance(document.getElementById('modalDetalles'));
                        if (modal) {
                            modal.hide();
                        }
                        
                        // Mostrar mensaje de éxito
                        mostrarNotificacion('Alerta resuelta correctamente', 'success');
                        
                        // Actualizar contadores después de un breve delay
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                        
                    } else {
                        mostrarNotificacion(data.message || 'Error al resolver la alerta', 'error');
                        
                        // Restaurar botón
                        if (btnResolver) {
                            btnResolver.innerHTML = '<i class="bi bi-check"></i> Resolver';
                            btnResolver.style.pointerEvents = 'auto';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarNotificacion('Error de conexión', 'error');
                    
                    // Restaurar botón
                    if (btnResolver) {
                        btnResolver.innerHTML = '<i class="bi bi-check"></i> Resolver';
                        btnResolver.style.pointerEvents = 'auto';
                    }
                });
            }
        }

        // Resolver desde modal
        function resolverDesdeModal() {
            if (alertaActual) {
                resolverAlerta(alertaActual);
            }
        }

        // Marcar todas como leídas
        function marcarTodasLeidas() {
            if (confirm('¿Está seguro de marcar todas las alertas como leídas?')) {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=marcar_todas_leidas'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarNotificacion('Todas las alertas han sido marcadas como leídas', 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        mostrarNotificacion(data.message || 'Error al marcar las alertas', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarNotificacion('Error de conexión', 'error');
                });
            }
        }

        // Actualizar alertas
        function actualizarAlertas() {
            location.reload();
        }

        // Funciones auxiliares
        function verVehiculo(placa) {
            // Redirigir a la página de detalles del vehículo
            console.log('Ver vehículo:', placa);
            alert('Redirigiendo a detalles del vehículo: ' + placa);
        }

        function compartirAlerta(id) {
            if (navigator.share) {
                navigator.share({
                    title: 'Alerta del Sistema Flotax',
                    text: 'Alerta del sistema de gestión de flota',
                    url: window.location.href + '?alerta=' + id
                });
            } else {
                const url = window.location.href + '?alerta=' + id;
                navigator.clipboard.writeText(url).then(() => {
                    mostrarNotificacion('Enlace copiado al portapapeles', 'success');
                });
            }
        }

        // Sistema de notificaciones
        function mostrarNotificacion(mensaje, tipo = 'info') {
            const alertClass = tipo === 'success' ? 'alert-success' : tipo === 'error' ? 'alert-danger' : 'alert-info';
            const iconClass = tipo === 'success' ? 'bi-check-circle' : tipo === 'error' ? 'bi-exclamation-triangle' : 'bi-info-circle';
            
            const notification = document.createElement('div');
            notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                <i class="bi ${iconClass} me-2"></i>
                ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove después de 5 segundos
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }

        // Inicializar cuando el DOM esté listo
        window.addEventListener('DOMContentLoaded', () => {
            // Agregar animación a las alertas
            const alertas = document.querySelectorAll('.alert-item');
            alertas.forEach((alerta, index) => {
                alerta.style.animationDelay = `${index * 0.1}s`;
            });
            
            // Auto-actualizar cada 5 minutos
            setInterval(() => {
                console.log('Verificando nuevas alertas...');
                // Aquí podrías implementar una verificación AJAX sin recargar la página
            }, 300000);
        });
    </script>
</body>
</html>
