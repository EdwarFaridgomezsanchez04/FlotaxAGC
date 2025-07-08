<?php
session_start();
require_once('../../conecct/conex.php');
include '../../includes/validarsession.php';

$db = new Database();
$con = $db->conectar();
$code = $_SESSION['documento'];

// Consulta mejorada para obtener documentos


$documento = $_SESSION['documento'] ?? null;
if (!$documento) {
    header('Location: ../../login.php');
    exit;
}

$nombre_completo = $_SESSION['nombre_completo'] ?? null;
$foto_perfil = $_SESSION['foto_perfil'] ?? null;
if (!$nombre_completo || !$foto_perfil) {
    $user_query = $con->prepare("SELECT nombre_completo, foto_perfil FROM usuarios WHERE documento = :documento");
    $user_query->bindParam(':documento', $documento, PDO::PARAM_STR);
    $user_query->execute();
    $user = $user_query->fetch(PDO::FETCH_ASSOC);
    $nombre_completo = $user['nombre_completo'] ?? 'Usuario';
    $foto_perfil = $user['foto_perfil'] ?: 'roles/user/css/img/perfil.jpg';
    $_SESSION['nombre_completo'] = $nombre_completo;
    $_SESSION['foto_perfil'] = $foto_perfil;
}

// Función para determinar el estado de un documento
function getDocumentStatus($fecha_vencimiento) {
    if (!$fecha_vencimiento) return 'no-disponible';
    
    $fecha_actual = new DateTime();
    $fecha_venc = new DateTime($fecha_vencimiento);
    $diferencia = $fecha_actual->diff($fecha_venc);
    
    if ($fecha_venc < $fecha_actual) {
        return 'vencido';
    } elseif ($diferencia->days <= 30) {
        return 'proximo';
    } else {
        return 'vigente';
    }
}

// Datos de documentos desde la base de datos
$documentos = [];

$query = $con->prepare("
    SELECT 
        v.placa,
        s.fecha_vencimiento AS soat_vence,
        t.fecha_vencimiento AS tecnomecanica_vence,
        l.fecha_vencimiento AS licencia_vence,
        u.nombre_completo AS propietario
    FROM vehiculos v
    LEFT JOIN soat s ON v.placa = s.id_placa
    LEFT JOIN tecnomecanica t ON v.placa = t.id_placa
    LEFT JOIN licencias l ON v.documento = l.id_documento
    LEFT JOIN usuarios u ON v.documento = u.documento
");

$query->execute();
$resultados = $query->fetchAll(PDO::FETCH_ASSOC);

foreach ($resultados as $row) {
    $estado_licencia = getDocumentStatus($row['licencia_vence']);

    $documentos[] = [
        'placa' => $row['placa'],
        'soat_vence' => $row['soat_vence'],
        'tecnomecanica_vence' => $row['tecnomecanica_vence'],
        'licencia_estado' => $estado_licencia,
        'propietario' => $row['propietario'] ?? 'Desconocido'
    ];
}
?>
 
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Visualizacion de Documentos - Flotax AGC</title>
  <link rel="shortcut icon" href="../../css/img/logo_sinfondo.png">
  <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/documentos.css">

</head>
<body>

  <?php include 'menu.php'; ?>

  <div class="content">
    <div class="container-fluid">
      <!-- Header de la página -->
      <div class="page-header">
        <div>
          <h1 class="page-title">
            <i class="bi bi-folder-check"></i>
            Control de Documentos
          </h1>
          <p class="page-subtitle">Gestión y seguimiento de documentos vehiculares</p>
        </div>
      </div>

      <!-- Estadísticas rápidas -->
      <div class="stats-cards">
        <div class="stat-card success">
          <i class="bi bi-check-circle stat-icon"></i>
          <div class="stat-number">15</div>
          <div class="stat-label">Documentos al día</div>
        </div>
        <div class="stat-card warning">
          <i class="bi bi-exclamation-triangle stat-icon"></i>
          <div class="stat-number">3</div>
          <div class="stat-label">Por vencer (30 días)</div>
        </div>
        <div class="stat-card danger">
          <i class="bi bi-x-circle stat-icon"></i>
          <div class="stat-number">2</div>
          <div class="stat-label">Vencidos</div>
        </div>
        <div class="stat-card">
          <i class="bi bi-file-earmark stat-icon"></i>
          <div class="stat-number">20</div>
          <div class="stat-label">Total documentos</div>
        </div>
      </div>

      <!-- Controles superiores -->
      <div class="controls-section">
        <div class="buscador">
          <input type="text" id="buscar" placeholder="Buscar por placa, documento o estado..." onkeyup="filtrarTabla()">
        </div>
      </div>

      <!-- Tabla de documentos -->
      <div class="table-container">
        <div class="table-responsive">
          <table class="table" id="tablaDocumentos">
            <thead>
              <tr>
                <th><i class="bi bi-car-front"></i> Placa</th>
                <th><i class="bi bi-shield-check"></i> SOAT</th>
                <th><i class="bi bi-gear"></i> TecnoMecánica</th>
                <th><i class="bi bi-person-badge"></i> Licencia</th>
                                <th><i class="bi bi-file-earmark-text"></i>Propietario</th>
                <th><i class="bi bi-gear-fill"></i> Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($documentos as $doc): ?>
                <tr>
                  <td>
                    <span class="placa-badge"><?= htmlspecialchars($doc['placa']) ?></span>
                  </td>
                  <td>
                    <?php 
                    $soat_status = getDocumentStatus($doc['soat_vence']);
                    $soat_fecha = new DateTime($doc['soat_vence']);
                    ?>
                   <center> <span class="status-<?= $soat_status ?> fecha-tooltip" 
                          data-tooltip="Vence: <?= $soat_fecha->format('d/m/Y') ?>">
                      <?php if ($soat_status === 'vigente'): ?>
                        Vigente
                      <?php elseif ($soat_status === 'proximo'): ?>
                        Por vencer
                      <?php else: ?>
                        Vencido
                      <?php endif; ?>
                    </span></center>
                  </td>
                  <td>
                    <?php 
                    $tecno_status = getDocumentStatus($doc['tecnomecanica_vence']);
                    $tecno_fecha = new DateTime($doc['tecnomecanica_vence']);
                    ?>
                    <span class="status-<?= $tecno_status ?> fecha-tooltip" 
                          data-tooltip="Vence: <?= $tecno_fecha->format('d/m/Y') ?>">
                      <?php if ($tecno_status === 'vigente'): ?>
                        Vigente
                      <?php elseif ($tecno_status === 'proximo'): ?>
                        Por vencer
                      <?php else: ?>
                        Vencido
                      <?php endif; ?>
                    </span>
                  </td>
                  <td>
                    <span class="status-<?= $doc['licencia_estado'] ?>">
                      <?= ucfirst($doc['licencia_estado']) ?>
                    </span>
                  </td>
                                       <td>
                    <?= htmlspecialchars($doc['propietario']) ?>
                  </td>
                  <td>
                    <button class="btn btn-primary btn-sm btn-gradient" onclick="verDetallesDocumento('<?= htmlspecialchars(json_encode($doc), ENT_QUOTES, 'UTF-8') ?>')">
                      <i class="bi bi-eye"></i> Ver Detalles
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Paginación -->
      <div class="pagination-container">
        <ul class="pagination" id="paginacion"></ul>
      </div>
    </div>
  </div>
<script>
  
    // Función de filtrado mejorada
    function filtrarTabla() {
      const input = document.getElementById('buscar').value.toLowerCase();
      const rows = document.querySelectorAll("#tablaDocumentos tbody tr");
      let visibleRows = 0;
      
      rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        const isVisible = text.includes(input);
        row.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleRows++;
      });
      
      // Reconfigurar paginación después del filtrado
      configurarPaginacion();
    }

    // Paginación mejorada
    const filasPorPagina = 5;
    function configurarPaginacion() {
      const filas = Array.from(document.querySelectorAll('#tablaDocumentos tbody tr'))
                         .filter(row => row.style.display !== 'none');
      const totalPaginas = Math.ceil(filas.length / filasPorPagina);
      const paginacion = document.getElementById('paginacion');

      function mostrarPagina(pagina) {
        // Ocultar todas las filas
        document.querySelectorAll('#tablaDocumentos tbody tr').forEach(row => {
          row.style.display = 'none';
        });
        
        // Mostrar solo las filas de la página actual
        const inicio = (pagina - 1) * filasPorPagina;
        const fin = inicio + filasPorPagina;
        filas.slice(inicio, fin).forEach(row => {
          row.style.display = '';
        });
        
        // Actualizar botones de paginación
        document.querySelectorAll('#paginacion .page-item').forEach(btn => {
          btn.classList.remove('active');
        });
        document.querySelector(`#paginacion .page-item:nth-child(${pagina})`)?.classList.add('active');
      }

      // Crear botones de paginación
      paginacion.innerHTML = '';
      for (let i = 1; i <= totalPaginas; i++) {
        const li = document.createElement('li');
        li.className = 'page-item' + (i === 1 ? ' active' : '');
        li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
        li.querySelector('a').addEventListener('click', e => {
          e.preventDefault();
          mostrarPagina(i);
        });
        paginacion.appendChild(li);
      }

      if (totalPaginas > 0) {
        mostrarPagina(1);
      }
    }

  

    function editarDocumento(placa) {
      // Implementar edición de documento
      window.open(`editar_documento.php?placa=${placa}`, '', 'width=800, height=600, toolbar=NO');
    }

    function eliminarDocumento(placa) {
      if (confirm(`¿Está seguro de eliminar los documentos del vehículo ${placa}?`)) {
        // Implementar eliminación
        console.log('Eliminar documentos de:', placa);
      }
    }

    function verDocumento(tipo, placa) {
      // Implementar visualización de documento
      window.open(`ver_documento.php?tipo=${tipo}&placa=${placa}`, '_blank');
    }

    // Inicializar cuando el DOM esté listo
    window.addEventListener('DOMContentLoaded', () => {
      configurarPaginacion();
      
      // Agregar animación a las filas
      const rows = document.querySelectorAll('#tablaDocumentos tbody tr');
      rows.forEach((row, index) => {
        row.style.animationDelay = `${index * 0.1}s`;
      });
    });
  </script>

<!-- Modal para ver detalles del documento -->
<div class="modal fade" id="modalVerDocumento" tabindex="-1" aria-labelledby="modalVerDocumentoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalVerDocumentoLabel">Detalles del Documento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Contenido del modal se cargará aquí -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function verDetallesDocumento(docData) {
        const doc = JSON.parse(docData);
        const modalBody = document.querySelector('#modalVerDocumento .modal-body');

        const getStatusClass = (status) => {
            if (status === 'vencido') return 'text-danger';
            if (status === 'proximo') return 'text-warning';
            return 'text-success';
        };

        const formatDate = (dateString) => {
            if (!dateString) return 'No disponible';
            const date = new Date(dateString);
            return date.toLocaleDateString('es-CO', { year: 'numeric', month: 'long', day: 'numeric' });
        };

        const soatStatus = getDocumentStatus(doc.soat_vence);
        const tecnoStatus = getDocumentStatus(doc.tecnomecanica_vence);

        modalBody.innerHTML = `
            <div class="info-card">
                <div class="info-header">
                    <h4>Placa: <span class="placa-badge">${doc.placa}</span></h4>
                    <p><strong>Propietario:</strong> ${doc.propietario}</p>
                </div>
                <hr>
                <div class="document-details">
                    <div class="document-card">
                        <h5><i class="bi bi-shield-check"></i> SOAT</h5>
                        <p><strong>Vencimiento:</strong> ${formatDate(doc.soat_vence)}</p>
                        <p><strong>Estado:</strong> <span class="${getStatusClass(soatStatus)}">${soatStatus.charAt(0).toUpperCase() + soatStatus.slice(1)}</span></p>
                        <a href="#" class="btn btn-outline-primary btn-sm mt-2"><i class="bi bi-download"></i> Descargar SOAT</a>
                    </div>
                    <div class="document-card">
                        <h5><i class="bi bi-gear"></i> TecnoMecánica</h5>
                        <p><strong>Vencimiento:</strong> ${formatDate(doc.tecnomecanica_vence)}</p>
                        <p><strong>Estado:</strong> <span class="${getStatusClass(tecnoStatus)}">${tecnoStatus.charAt(0).toUpperCase() + tecnoStatus.slice(1)}</span></p>
                        <a href="#" class="btn btn-outline-primary btn-sm mt-2"><i class="bi bi-download"></i> Descargar TecnoMec.</a>
                    </div>
                    <div class="document-card">
                        <h5><i class="bi bi-person-badge"></i> Licencia de Conducción</h5>
                        <p><strong>Estado:</strong> <span class="${getStatusClass(doc.licencia_estado)}">${doc.licencia_estado.charAt(0).toUpperCase() + doc.licencia_estado.slice(1)}</span></p>
                        <a href="#" class="btn btn-outline-primary btn-sm mt-2"><i class="bi bi-download"></i> Descargar Licencia</a>
                    </div>
                </div>
            </div>
        `;

        const modal = new bootstrap.Modal(document.getElementById('modalVerDocumento'));
        modal.show();
    }

    function getDocumentStatus(fechaVencimiento) {
        if (!fechaVencimiento) return 'no-disponible';
        const fechaActual = new Date();
        const fechaVenc = new Date(fechaVencimiento);
        const diffTime = fechaVenc - fechaActual;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays < 0) return 'vencido';
        if (diffDays <= 30) return 'proximo';
        return 'vigente';
    }
</script>
</body>
</html>
