<?php
session_start();
require_once('../../conecct/conex.php');
include '../../includes/validarsession.php';
$db = new Database();
$con = $db->conectar();

// Check for documento in session
$documento = $_SESSION['documento'] ?? null;
if (!$documento) {
    header('Location: ../../login.php');
    exit;
}

// Fetch nombre_completo and foto_perfil if not in session
$nombre_completo = $_SESSION['nombre_completo'] ?? null;
$foto_perfil = $_SESSION['foto_perfil'] ?? null;
if (!$nombre_completo || !$foto_perfil) {
    $user_query = $con->prepare("SELECT * FROM usuarios WHERE documento = :documento");
    $user_query->bindParam(':documento', $documento, PDO::PARAM_STR);
    $user_query->execute();
    $user = $user_query->fetch(PDO::FETCH_ASSOC);
    $nombre_completo = $user['nombre_completo'] ?? 'Usuario';
    $foto_perfil = $user['foto_perfil'] ?: 'css/img/perfil.jpg';
    $_SESSION['nombre_completo'] = $nombre_completo;
    $_SESSION['foto_perfil'] = $foto_perfil;
}

$estado = $con->prepare("SELECT DISTINCT estado FROM estado_vehiculo ORDER BY estado DESC");
$estado->execute();
$estado = $estado->fetchAll(PDO::FETCH_COLUMN);

// Obtener estadísticas
$stats_query = $con->prepare("SELECT COUNT(*) AS total_vehiculos,
        SUM(CASE WHEN vehiculos.id_estado = '10' THEN 1 ELSE 0 END) AS vehiculos_activos,
        SUM(CASE WHEN vehiculos.id_estado = '2' THEN 1 ELSE 0 END) AS vehiculos_inactivos,
        SUM(CASE WHEN vehiculos.id_estado = '3' THEN 1 ELSE 0 END) AS vehiculos_mantenimiento
    FROM vehiculos 
    INNER JOIN estado_vehiculo ON vehiculos.id_estado = estado_vehiculo.id_estado");
$stats_query->execute();
$stats = $stats_query->fetch(PDO::FETCH_ASSOC);

// Función para determinar la clase CSS del estado
function getEstadoClass($estado) {
    switch (strtolower($estado)) {
        case 'activo':
            return 'estado-activo';
        case 'en mantenimiento':
            return 'estado-mantenimiento';
        case 'inactivo':
            return 'estado-inactivo';
        default:
            return 'estado-activo';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Vehículos - Flotax AGC</title>
    <link rel="shortcut icon" href="../../css/img/logo_sinfondo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/vehiculos.css">
    <link rel="stylesheet" href="css/vehiculos-modal.css">
</head>
<body>
<?php include 'menu.php'; ?>

<div class="content">
    <!-- Header de la página -->
    <div class="page-header">
        <div>
            <h1 class="page-title">
                <i class="bi bi-truck"></i>
                Gestión de Vehículos
            </h1>
            <p class="page-subtitle">Administración y control de la flota vehicular</p>
        </div>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="stats-overview">
        <div class="stat-card vehicles">
            <i class="bi bi-truck stat-icon"></i>
            <div class="stat-number"><?= $stats['total_vehiculos'] ?></div>
            <div class="stat-label">Total Vehículos</div>
        </div>
        <div class="stat-card active">
            <i class="bi bi-check-circle stat-icon"></i>
            <div class="stat-number"><?= $stats['vehiculos_activos'] ?></div>
            <div class="stat-label">Vehículos en Uso</div>
        </div>
        <div class="stat-card maintenance">
            <i class="bi bi-tools stat-icon"></i>
            <div class="stat-number"><?= $stats['vehiculos_mantenimiento'] ?></div>
            <div class="stat-label">En Mantenimiento</div>
        </div>
        <div class="stat-card inactive">
            <i class="bi bi-x-circle stat-icon"></i>
            <div class="stat-number"><?= $stats['vehiculos_inactivos'] ?></div>
            <div class="stat-label">Inactivos</div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filters-section">
        <div class="filter-group">
            <label class="filter-label">Estado</label>
            <select class="filter-select" id="filtroEstado" onchange="aplicarFiltros()">
                <option value="">Todos los Estados</option>
                <?php foreach ($estado as $estadoItem): ?>
                    <option value="<?= htmlspecialchars($estadoItem) ?>">
                        <?= htmlspecialchars($estadoItem) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Marca</label>
            <select class="filter-select" id="filtroMarca" onchange="aplicarFiltros()">
                <option value="">Todas las marcas</option>
                <!-- Opciones dinámicas desde la base de datos -->
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Año</label>
            <select class="filter-select" id="filtroAno" onchange="aplicarFiltros()">
                <option value="">Todos los años</option>
                <!-- Opciones dinámicas -->
            </select>
        </div>
    </div>

    <!-- Controles superiores -->
    <div class="controls-section">
        <div class="buscador">
            <input type="text" id="buscar" class="form-control" placeholder="Buscar por placa, propietario, documento..." onkeyup="filtrarTabla()">
        </div>
    </div>

    <!-- Tabla de vehículos -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="table" id="tablaUsuarios">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><i class="bi bi-car-front"></i> Placa</th>
                        <th><i class="bi bi-person-badge"></i> Documento</th>
                        <th><i class="bi bi-person"></i> Propietario</th>
                        <th><i class="bi bi-tags"></i> Marca</th>
                        <th><i class="bi bi-calendar"></i> Modelo</th>
                        <th><i class="bi bi-info-circle"></i> Estado</th>
                        <th><i class="bi bi-speedometer2"></i> Kilometraje</th>
                        <th><i class="bi bi-calendar-date"></i> Registro</th>
                        <th><i class="bi bi-image"></i> Imagen</th>
                        <th><i class="bi bi-tools"></i> Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = $con->prepare("SELECT *
                                        FROM vehiculos
                                        INNER JOIN usuarios ON vehiculos.Documento = usuarios.documento 
                                        INNER JOIN marca ON vehiculos.id_marca = marca.id_marca
                                        INNER JOIN estado_vehiculo ON vehiculos.id_estado = estado_vehiculo.id_estado
                                        ORDER BY vehiculos.fecha_registro DESC");
                    $sql->execute();
                    $vehiculos = $sql->fetchAll(PDO::FETCH_ASSOC);
                    $count = 1;
                    
                    if (count($vehiculos) > 0):
                        foreach ($vehiculos as $resu):
                            error_log("Fetched vehicle: placa={$resu['placa']}, documento={$resu['Documento']}, id_marca={$resu['id_marca']}, modelo={$resu['modelo']}, id_estado={$resu['id_estado']}, kilometraje={$resu['kilometraje_actual']}");
$image_path = '../../usuario/' . htmlspecialchars($resu['foto_vehiculo']);
                    ?>
                    <tr>    
                        <td><span class="numero-fila"><?php echo $count++; ?></span></td>
                        <td><span class="placa-cell"><?php echo htmlspecialchars($resu['placa']); ?></span></td>
                        <td><span class="documento-cell"><?php echo htmlspecialchars($resu['Documento']); ?></span></td>
                        <td><span class="propietario-cell"><?php echo htmlspecialchars($resu['nombre_completo']); ?></span></td>
                        <td><span class="marca-cell"><?php echo htmlspecialchars($resu['nombre_marca']); ?></span></td>
                        <td><span class="modelo-cell"><?php echo htmlspecialchars($resu['modelo']); ?></span></td>
                        <td>
                            <span class="estado-cell <?php echo getEstadoClass($resu['estado']); ?>">
                                <?php echo htmlspecialchars($resu['estado']); ?>
                            </span>
                        </td>
                        <td><span class="kilometraje-cell"><?php echo number_format($resu['kilometraje_actual']); ?></span></td>
                        <td><span class="fecha-cell"><?php echo date('d/m/Y', strtotime($resu['fecha_registro'])); ?></span></td>
                                      <td>
                                        <?php
                                        $nombreImagen = htmlspecialchars($resu['foto_vehiculo']);
                                        $rutaImagen = "../../roles/usuario/vehiculos/listar/guardar_foto_vehiculo/" . $nombreImagen;
                                        ?>
                                        <img src="<?php echo $rutaImagen; ?>"
                                             alt="Vehículo <?php echo htmlspecialchars($resu['placa']); ?>" 
                                             class="vehicle-image">
                                        </td>

                        <td>
                            <div class="action-buttons">
                                <a class="action-icon edit" data-id="<?php echo htmlspecialchars($resu['placa']); ?>" title="Editar vehículo">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <a class="action-icon delete" data-id="<?php echo htmlspecialchars($resu['placa']); ?>" title="Eliminar vehículo">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        endforeach;
                    else:
                    ?>
                    <tr>
                        <td colspan="11" class="no-data">
                            <i class="bi bi-truck"></i>
                            <h3>No hay vehículos registrados</h3>
                            <p>Comienza agregando tu primer vehículo a la flota</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    <div class="pagination-container">
        <nav>
            <ul class="pagination" id="paginacion"></ul>
        </nav>
    </div>

    <!-- Botón agregar -->
    <div class="boton-agregar">
        <button type="button" class="boton" id="btnAgregarVehiculo">
            <i class="bi bi-plus-circle"></i>
            <i class="bi bi-truck"></i>
            Agregar Vehículo
        </button>
    </div>
</div> <!-- cierre de .content -->

<?php include 'modals_vehiculos/vehiculo_modals.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="modals_vehiculos/vehiculos-scripts.js"></script>
<script>
    // Función de filtrado mejorada
    function filtrarTabla() {
        const input = document.getElementById('buscar').value.toLowerCase();
        const rows = document.querySelectorAll("#tablaUsuarios tbody tr");
        let visibleRows = 0;
        
        rows.forEach(row => {
            if (row.querySelector('.no-data')) return; // Skip no-data row
            
            const text = row.innerText.toLowerCase();
            const isVisible = text.includes(input);
            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleRows++;
        });
        
        configurarPaginacion();
    }

    // Aplicar filtros combinados
    function aplicarFiltros() {
        const filtroEstado = document.getElementById('filtroEstado').value.toLowerCase();
        const filtroMarca = document.getElementById('filtroMarca').value.toLowerCase();
        const filtroAno = document.getElementById('filtroAno').value;
        const rows = document.querySelectorAll("#tablaUsuarios tbody tr");
        
        rows.forEach(row => {
            if (row.querySelector('.no-data')) return;
            
            const estado = row.querySelector('.estado-cell')?.textContent.toLowerCase() || '';
            const marca = row.querySelector('.marca-cell')?.textContent.toLowerCase() || '';
            const modelo = row.querySelector('.modelo-cell')?.textContent || '';
            
            let mostrar = true;
            
            if (filtroEstado && !estado.includes(filtroEstado)) mostrar = false;
            if (filtroMarca && !marca.includes(filtroMarca)) mostrar = false;
            if (filtroAno && modelo !== filtroAno) mostrar = false;
            
            row.style.display = mostrar ? '' : 'none';
        });
        
        configurarPaginacion();
    }

    // Paginación mejorada
    const filasPorPagina = 5;
    function configurarPaginacion() {
        const filas = Array.from(document.querySelectorAll('#tablaUsuarios tbody tr'))
                           .filter(row => row.style.display !== 'none' && !row.querySelector('.no-data'));
        const totalPaginas = Math.ceil(filas.length / filasPorPagina);
        const paginacion = document.getElementById('paginacion');

        function mostrarPagina(pagina) {
            document.querySelectorAll('#tablaUsuarios tbody tr').forEach(row => {
                if (!row.querySelector('.no-data')) {
                    row.style.display = 'none';
                }
            });
            
            const inicio = (pagina - 1) * filasPorPagina;
            const fin = inicio + filasPorPagina;
            filas.slice(inicio, fin).forEach(row => {
                row.style.display = '';
            });
            
            document.querySelectorAll('#paginacion .page-item').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`#paginacion .page-item:nth-child(${pagina})`)?.classList.add('active');
        }

        paginacion.innerHTML = '';
        for (let i = 1; i <= totalPaginas; i++) {
            const li = document.createElement('li');
            li.className = 'page-item' + (i === 1 ? ' active' : '');
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = i;
            a.addEventListener('click', function (e) {
                e.preventDefault();
                mostrarPagina(i);
            });
            li.appendChild(a);
            paginacion.appendChild(li);
        }

        if (totalPaginas > 0) {
            mostrarPagina(1);
        }
    }

    // Inicializar cuando el DOM esté listo
    window.addEventListener('DOMContentLoaded', () => {
        configurarPaginacion();
        cargarOpcionesFiltros();
        // Agregar animación a las filas
        const rows = document.querySelectorAll('#tablaUsuarios tbody tr');
        rows.forEach((row, index) => {
            if (!row.querySelector('.no-data')) {
                row.style.animationDelay = `${index * 0.1}s`;
            }
        });
    });

    function cargarOpcionesFiltros() {
        // Cargar marcas únicas
        const marcas = [...new Set(Array.from(document.querySelectorAll('.marca-cell')).map(el => el.textContent))];
        const selectMarca = document.getElementById('filtroMarca');
        marcas.forEach(marca => {
            const option = document.createElement('option');
            option.value = marca.toLowerCase();
            option.textContent = marca;
            selectMarca.appendChild(option);
        });

        // Cargar años únicos
        const anos = [...new Set(Array.from(document.querySelectorAll('.modelo-cell')).map(el => el.textContent))];
        const selectAno = document.getElementById('filtroAno');
        anos.sort((a, b) => b - a).forEach(ano => {
            const option = document.createElement('option');
            option.value = ano;
            option.textContent = ano;
            selectAno.appendChild(option);
        });
    }
</script>
</body>
</html>