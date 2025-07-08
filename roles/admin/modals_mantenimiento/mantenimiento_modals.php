<?php
/**
 * MODALES PARA GESTIÓN DE MANTENIMIENTOS
 * 
 * Este archivo contiene todos los modales de Bootstrap necesarios para
 * la gestión completa del sistema de mantenimientos de vehículos.
 * 
 * Modales incluidos:
 * 1. Modal para agregar nuevo mantenimiento
 * 2. Modal para editar mantenimiento existente
 * 3. Modal para ver detalles de mantenimiento (solo lectura)
 * 4. Modal para confirmar eliminación de mantenimiento
 * 5. Modal de alertas generales del sistema
 * 
 * Características principales:
 * - Formularios responsivos con validación HTML5
 * - Carga dinámica de vehículos y tipos de mantenimiento
 * - Interfaz moderna con Bootstrap 5 e iconos
 * - Manejo de estados y confirmaciones de seguridad
 */

// Inclusión de la conexión a base de datos
require_once('../../conecct/conex.php');

// Instanciación de la clase de base de datos
$db = new Database();
$con = $db->conectar();

/**
 * CONSULTA PARA OBTENER VEHÍCULOS DISPONIBLES
 * 
 * Obtiene la lista de vehículos con información del propietario
 * para poblar los selectores de los modales.
 * Formato: "PLACA - NOMBRE_PROPIETARIO"
 */
$vehiculos_query = $con->prepare("SELECT placa, CONCAT(placa, ' - ', u.nombre_completo) as display_text FROM vehiculos v LEFT JOIN usuarios u ON v.Documento = u.documento ORDER BY placa");
$vehiculos_query->execute();
$vehiculos = $vehiculos_query->fetchAll(PDO::FETCH_ASSOC);

/**
 * CONSULTA PARA OBTENER TIPOS DE MANTENIMIENTO
 * 
 * Obtiene el catálogo de tipos de mantenimiento disponibles
 * ordenados alfabéticamente por descripción.
 */
$tipos_query = $con->prepare("SELECT id_tipo_mantenimiento, descripcion FROM tipo_mantenimiento ORDER BY descripcion");
$tipos_query->execute();
$tipos = $tipos_query->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- 
    MODAL PARA AGREGAR NUEVO MANTENIMIENTO
    
    Modal principal para el registro de nuevos mantenimientos.
    Incluye todos los campos necesarios para programar y registrar
    actividades de mantenimiento de vehículos.
-->
<div class="modal fade" id="modalAgregarMantenimiento" tabindex="-1" aria-labelledby="modalAgregarMantenimientoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg"> <!-- Modal grande para acomodar todos los campos -->
    <div class="modal-content">
      <!-- Encabezado del modal con título e icono -->
      <div class="modal-header">
        <h5 class="modal-title" id="modalAgregarMantenimientoLabel">
          <i class="bi bi-plus-circle"></i> Agregar Nuevo Mantenimiento
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <!-- 
          FORMULARIO DE REGISTRO DE MANTENIMIENTO
          
          Configuración:
          - ID específico para manejo JavaScript
          - Estructura organizada en filas y columnas responsivas
          - Validación HTML5 en campos obligatorios
      -->
      <form id="formAgregarMantenimiento">
      <div class="modal-body">
        <!-- FILA 1: Selección de vehículo y tipo de mantenimiento -->
        <div class="row">
          <!-- Selector de vehículo con información del propietario -->
          <div class="col-md-6">
            <div class="mb-3">
              <label for="placaAgregar" class="form-label">Placa del Vehículo</label>
              <select class="form-select" id="placaAgregar" name="placa" required>
                <option value="">Seleccionar vehículo...</option>
                <?php foreach($vehiculos as $vehiculo): ?>
                  <!-- Generación dinámica de opciones con escape de caracteres -->
                  <option value="<?= $vehiculo['placa'] ?>"><?= htmlspecialchars($vehiculo['display_text']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          
          <!-- Selector de tipo de mantenimiento desde catálogo -->
          <div class="col-md-6">
            <div class="mb-3">
              <label for="tipoMantenimientoAgregar" class="form-label">Tipo de Mantenimiento</label>
              <select class="form-select" id="tipoMantenimientoAgregar" name="id_tipo_mantenimiento" required>
                <option value="">Seleccionar tipo...</option>
                <?php foreach($tipos as $tipo): ?>
                  <!-- Opciones dinámicas de tipos de mantenimiento -->
                  <option value="<?= $tipo['id_tipo_mantenimiento'] ?>"><?= htmlspecialchars($tipo['descripcion']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
        
        <!-- FILA 2: Fechas de programación y realización -->
        <div class="row">
          <!-- Fecha programada (obligatoria) -->
          <div class="col-md-6">
            <div class="mb-3">
              <label for="fechaProgramadaAgregar" class="form-label">Fecha Programada</label>
              <input type="date" class="form-control" id="fechaProgramadaAgregar" name="fecha_programada" required>
            </div>
          </div>
          
          <!-- Fecha realizada (opcional, para cuando ya se completó) -->
          <div class="col-md-6">
            <div class="mb-3">
              <label for="fechaRealizadaAgregar" class="form-label">Fecha Realizada</label>
              <input type="date" class="form-control" id="fechaRealizadaAgregar" name="fecha_realizada">
            </div>
          </div>
        </div>
        
        <!-- FILA 3: Información de kilometraje -->
        <div class="row">
          <!-- Kilometraje actual del vehículo -->
          <div class="col-md-6">
            <div class="mb-3">
              <label for="kilometrajeActualAgregar" class="form-label">Kilometraje Actual</label>
              <input type="number" class="form-control" id="kilometrajeActualAgregar" name="kilometraje_actual" min="0">
            </div>
          </div>
          
          <!-- Kilometraje para próximo mantenimiento -->
          <div class="col-md-6">
            <div class="mb-3">
              <label for="proximoCambioKmAgregar" class="form-label">Próximo Cambio (Km)</label>
              <input type="number" class="form-control" id="proximoCambioKmAgregar" name="proximo_cambio_km" min="0">
            </div>
          </div>
        </div>
        
        <!-- FILA 4: Fecha de próximo mantenimiento -->
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label for="proximoCambioFechaAgregar" class="form-label">Próximo Cambio (Fecha)</label>
              <input type="date" class="form-control" id="proximoCambioFechaAgregar" name="proximo_cambio_fecha">
            </div>
          </div>
        </div>
        
        <!-- Campo de observaciones adicionales -->
        <div class="mb-3">
          <label for="observacionesAgregar" class="form-label">Observaciones</label>
          <textarea class="form-control" id="observacionesAgregar" name="observaciones" rows="3"></textarea>
        </div>
      </div>
      
        <!-- Botones de acción del formulario -->
        <div class="modal-footer">
          <!-- Botón de cancelación -->
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle"></i> Cancelar
          </button>
          <!-- Botón de envío del formulario -->
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Guardar Mantenimiento
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 
    MODAL PARA EDITAR MANTENIMIENTO EXISTENTE
    
    Modal para modificar información de mantenimientos ya registrados.
    Incluye campo oculto para el ID del mantenimiento y estructura
    similar al modal de agregar pero con propósito de edición.
-->
<div class="modal fade" id="modalEditarMantenimiento" tabindex="-1" aria-labelledby="modalEditarMantenimientoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <!-- Encabezado del modal de edición -->
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditarMantenimientoLabel">
          <i class="bi bi-pencil-square"></i> Editar Mantenimiento
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <!-- 
          FORMULARIO DE EDICIÓN DE MANTENIMIENTO
          
          Diferencias con el formulario de agregar:
          - Incluye campo oculto para ID del mantenimiento
          - Los campos se poblarán con datos existentes vía JavaScript
          - Botón de acción dice "Actualizar" en lugar de "Guardar"
      -->
      <form id="formEditarMantenimiento">
        <!-- Campo oculto para identificar el mantenimiento a editar -->
        <input type="hidden" id="idMantenimientoEditar" name="id_mantenimiento">
        
        <div class="modal-body">
          <!-- FILA 1: Selección de vehículo y tipo (editables) -->
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="placaEditar" class="form-label">Placa del Vehículo</label>
                <select class="form-select" id="placaEditar" name="placa" required>
                  <option value="">Seleccionar vehículo...</option>
                  <?php foreach($vehiculos as $vehiculo): ?>
                    <option value="<?= $vehiculo['placa'] ?>"><?= htmlspecialchars($vehiculo['display_text']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="tipoMantenimientoEditar" class="form-label">Tipo de Mantenimiento</label>
                <select class="form-select" id="tipoMantenimientoEditar" name="id_tipo_mantenimiento" required>
                  <option value="">Seleccionar tipo...</option>
                  <?php foreach($tipos as $tipo): ?>
                    <option value="<?= $tipo['id_tipo_mantenimiento'] ?>"><?= htmlspecialchars($tipo['descripcion']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          
          <!-- FILA 2: Fechas editables -->
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="fechaProgramadaEditar" class="form-label">Fecha Programada</label>
                <input type="date" class="form-control" id="fechaProgramadaEditar" name="fecha_programada" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="fechaRealizadaEditar" class="form-label">Fecha Realizada</label>
                <input type="date" class="form-control" id="fechaRealizadaEditar" name="fecha_realizada">
              </div>
            </div>
          </div>
          
          <!-- FILA 3: Kilometrajes editables -->
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="kilometrajeActualEditar" class="form-label">Kilometraje Actual</label>
                <input type="number" class="form-control" id="kilometrajeActualEditar" name="kilometraje_actual" min="0">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="proximoCambioKmEditar" class="form-label">Próximo Cambio (Km)</label>
                <input type="number" class="form-control" id="proximoCambioKmEditar" name="proximo_cambio_km" min="0">
              </div>
            </div>
          </div>
          
          <!-- FILA 4: Fecha de próximo cambio -->
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="proximoCambioFechaEditar" class="form-label">Próximo Cambio (Fecha)</label>
                <input type="date" class="form-control" id="proximoCambioFechaEditar" name="proximo_cambio_fecha">
              </div>
            </div>
          </div>
          
          <!-- Observaciones editables -->
          <div class="mb-3">
            <label for="observacionesEditar" class="form-label">Observaciones</label>
            <textarea class="form-control" id="observacionesEditar" name="observaciones" rows="3"></textarea>
          </div>
        </div>
        
        <!-- Botones de acción para edición -->
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle"></i> Cancelar
          </button>
          <!-- Botón específico para actualización -->
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Actualizar Mantenimiento
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 
    MODAL PARA VER DETALLES DEL MANTENIMIENTO
    
    Modal de solo lectura para mostrar información completa
    de un mantenimiento específico. No incluye formularios,
    solo elementos de visualización.
-->
<div class="modal fade" id="modalVerMantenimiento" tabindex="-1" aria-labelledby="modalVerMantenimientoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <!-- Encabezado del modal de visualización -->
      <div class="modal-header">
        <h5 class="modal-title" id="modalVerMantenimientoLabel">
          <i class="bi bi-eye"></i> Detalles del Mantenimiento
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body">
        <!-- FILA 1: ID y estado del mantenimiento -->
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">ID Mantenimiento:</label>
              <!-- Elemento de solo lectura para mostrar ID -->
              <p class="form-control-plaintext" id="verIdMantenimiento"></p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">Estado:</label>
              <!-- Elemento para mostrar estado calculado -->
              <p class="form-control-plaintext" id="verEstado"></p>
            </div>
          </div>
        </div>
        
        <!-- FILA 2: Información del vehículo y tipo -->
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">Placa del Vehículo:</label>
              <p class="form-control-plaintext" id="verPlaca"></p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">Tipo de Mantenimiento:</label>
              <p class="form-control-plaintext" id="verTipoMantenimiento"></p>
            </div>
          </div>
        </div>
        
        <!-- FILA 3: Fechas del mantenimiento -->
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">Fecha Programada:</label>
              <p class="form-control-plaintext" id="verFechaProgramada"></p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">Fecha Realizada:</label>
              <p class="form-control-plaintext" id="verFechaRealizada"></p>
            </div>
          </div>
        </div>
        
        <!-- FILA 4: Información de kilometraje -->
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">Kilometraje Actual:</label>
              <p class="form-control-plaintext" id="verKilometrajeActual"></p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">Próximo Cambio (Km):</label>
              <p class="form-control-plaintext" id="verProximoCambioKm"></p>
            </div>
          </div>
        </div>
        
        <!-- FILA 5: Fecha de próximo cambio -->
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">Próximo Cambio (Fecha):</label>
              <p class="form-control-plaintext" id="verProximoCambioFecha"></p>
            </div>
          </div>
        </div>
        
        <!-- Observaciones completas -->
        <div class="mb-3">
          <label class="form-label">Observaciones:</label>
          <p class="form-control-plaintext" id="verObservaciones"></p>
        </div>
      </div>
      
      <!-- Solo botón de cerrar (no hay acciones de modificación) -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- 
    MODAL PARA CONFIRMAR ELIMINACIÓN DE MANTENIMIENTO
    
    Modal de confirmación con advertencias de seguridad.
    Muestra información del mantenimiento a eliminar y
    requiere confirmación explícita del usuario.
-->
<div class="modal fade" id="modalEliminarMantenimiento" tabindex="-1" aria-labelledby="modalEliminarMantenimientoLabel" aria-hidden="true">
  <div class="modal-dialog"> <!-- Modal estándar, no grande -->
    <div class="modal-content">
      <!-- Encabezado con icono de advertencia -->
      <div class="modal-header">
        <h5 class="modal-title" id="modalEliminarMantenimientoLabel">
          <i class="bi bi-exclamation-triangle"></i> Confirmar Eliminación
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body">
        <!-- Alerta de advertencia prominente -->
        <div class="alert alert-danger" role="alert">
          <i class="bi bi-exclamation-triangle"></i>
          <strong>¡Atención!</strong> Esta acción no se puede deshacer.
        </div>
        
        <!-- Mensaje de confirmación -->
        <p>¿Estás seguro de que deseas eliminar este registro de mantenimiento?</p>
        
        <!-- 
            INFORMACIÓN DEL MANTENIMIENTO A ELIMINAR
            
            Muestra detalles clave para que el usuario confirme
            que está eliminando el registro correcto.
        -->
        <div class="mt-3">
          <strong>Detalles del mantenimiento:</strong>
          <ul class="list-unstyled mt-2">
            <li><strong>Placa:</strong> <span id="placaEliminar"></span></li>
            <li><strong>Tipo:</strong> <span id="tipoEliminar"></span></li>
            <li><strong>Fecha:</strong> <span id="fechaEliminar"></span></li>
          </ul>
        </div>
        
        <!-- Campo oculto para el ID del mantenimiento a eliminar -->
        <input type="hidden" id="idMantenimientoEliminar" name="id_mantenimiento">
      </div>
      
      <!-- Botones de confirmación y cancelación -->
      <div class="modal-footer">
        <!-- Botón de cancelación (acción segura) -->
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Cancelar
        </button>
        <!-- Botón de eliminación (acción destructiva) -->
        <button type="button" class="btn btn-danger" id="confirmarEliminar">
          <i class="bi bi-trash"></i> Eliminar Mantenimiento
        </button>
      </div>
    </div>
  </div>
</div>

<!-- 
    MODAL DE ALERTAS GENERALES
    
    Modal reutilizable para mostrar mensajes informativos,
    de éxito, error o advertencia en el sistema.
    El contenido se carga dinámicamente vía JavaScript.
-->
<div class="modal fade" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
  <div class="modal-dialog"> <!-- Modal estándar para mensajes -->
    <div class="modal-content">
      <!-- Encabezado genérico para alertas -->
      <div class="modal-header">
        <h5 class="modal-title" id="alertModalLabel">
          <i class="bi bi-info-circle"></i> Información
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body">
        <!-- 
            CONTENEDOR DINÁMICO PARA ALERTAS
            
            Este div se llena dinámicamente con:
            - Mensajes de éxito/error
            - Alertas de validación
            - Confirmaciones de acciones
            - Información del sistema
        -->
        <div id="alertContent"></div>
      </div>
      
      <!-- Botón de confirmación genérico -->
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
          <i class="bi bi-check-circle"></i> Entendido
        </button>
      </div>
    </div>
  </div>
</div>