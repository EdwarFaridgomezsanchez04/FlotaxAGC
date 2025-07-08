<?php
// Archivo de modales para gestión de vehículos
require_once('../../conecct/conex.php');
$db = new Database();
$con = $db->conectar();

// Obtener datos para los dropdowns
$marcas_query = $con->prepare("SELECT DISTINCT id_marca, nombre_marca FROM marca ORDER BY nombre_marca");
$marcas_query->execute();
$marcas = $marcas_query->fetchAll(PDO::FETCH_ASSOC);

$estados_query = $con->prepare("SELECT id_estado, estado FROM estado_vehiculo ORDER BY estado");
$estados_query->execute();
$estados = $estados_query->fetchAll(PDO::FETCH_ASSOC);

$usuarios_query = $con->prepare("SELECT documento, nombre_completo FROM usuarios WHERE id_estado_usuario = 1 ORDER BY nombre_completo");
$usuarios_query->execute();
$usuarios = $usuarios_query->fetchAll(PDO::FETCH_ASSOC);

$tipos_query = $con->prepare("SELECT id_tipo_vehiculo, vehiculo FROM tipo_vehiculo ORDER BY vehiculo");
$tipos_query->execute();
$tipos_vehiculo = $tipos_query->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Modal para Agregar Nuevo Vehículo -->
<div class="modal fade" id="modalAgregarVehiculo" tabindex="-1" aria-labelledby="modalAgregarVehiculoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAgregarVehiculoLabel">
          <i class="bi bi-plus-circle"></i> Agregar Nuevo Vehículo
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formAgregarVehiculo" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="tipoVehiculoAgregar" class="form-label">Tipo de Vehículo</label>
                <select class="form-select" id="tipoVehiculoAgregar" name="tipo_vehiculo" required>
                  <option value="">Seleccionar tipo...</option>
                  <?php foreach($tipos_vehiculo as $tipo): ?>
                    <option value="<?= htmlspecialchars($tipo['id_tipo_vehiculo']) ?>"><?= htmlspecialchars($tipo['vehiculo']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="marcaAgregar" class="form-label">Marca</label>
                <select class="form-select" id="marcaAgregar" name="id_marca" required>
                  <option value="">Seleccionar marca...</option>
                  <?php foreach($marcas as $marca): ?>
                    <option value="<?= $marca['id_marca'] ?>"><?= htmlspecialchars($marca['nombre_marca']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="placaAgregar" class="form-label">Placa</label>
                <input type="text" class="form-control" id="placaAgregar" name="placa" required style="text-transform: uppercase;">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="modeloAgregar" class="form-label">Modelo (Año)</label>
                <input type="number" class="form-control" id="modeloAgregar" name="modelo" min="1900" max="2099" required>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="kilometrajeAgregar" class="form-label">Kilometraje Actual</label>
                <input type="number" class="form-control" id="kilometrajeAgregar" name="kilometraje_actual" min="0" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="estadoAgregar" class="form-label">Estado</label>
                <select class="form-select" id="estadoAgregar" name="id_estado" required>
                  <option value="">Seleccionar estado...</option>
                  <?php foreach($estados as $estado): ?>
                    <option value="<?= $estado['id_estado'] ?>"><?= htmlspecialchars($estado['estado']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="documentoAgregar" class="form-label">Propietario</label>
                <select class="form-select" id="documentoAgregar" name="documento" required>
                  <option value="">Seleccionar propietario...</option>
                  <?php foreach($usuarios as $usuario): ?>
                    <option value="<?= $usuario['documento'] ?>"><?= htmlspecialchars($usuario['nombre_completo']) . ' (' . $usuario['documento'] . ')' ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="fotoVehiculoAgregar" class="form-label">Foto del Vehículo</label>
                <input type="file" class="form-control" id="fotoVehiculoAgregar" name="foto_vehiculo" accept="image/*" onchange="previewImage(this, 'fotoPreviewAgregar')">
              </div>
            </div>
          </div>
          
          <div class="mb-3">
            <img id="fotoPreviewAgregar" src="" alt="Vista previa" class="img-fluid" style="display: none; max-height: 200px;">
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle"></i> Cancelar
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Guardar Vehículo
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal para Editar Vehículo -->
<div class="modal fade" id="editarVehiculoModal" tabindex="-1" aria-labelledby="editarVehiculoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarVehiculoModalLabel">
                    <i class="bi bi-pencil-square"></i> Editar Vehículo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editarVehiculoForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="editPlaca" name="placa">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editDocumento" class="form-label">Propietario</label>
                            <select class="form-select" id="editDocumento" name="documento" required>
                                <option value="">Seleccione un propietario</option>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <option value="<?= htmlspecialchars($usuario['documento']) ?>">
                                        <?= htmlspecialchars($usuario['nombre_completo']) . ' (' . $usuario['documento'] . ')' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="editMarca" class="form-label">Marca</label>
                            <select class="form-select" id="editMarca" name="id_marca" required>
                                <option value="">Seleccione una marca</option>
                                <?php foreach ($marcas as $marca): ?>
                                    <option value="<?= htmlspecialchars($marca['id_marca']) ?>">
                                        <?= htmlspecialchars($marca['nombre_marca']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editModelo" class="form-label">Modelo (Año)</label>
                            <input type="number" class="form-control" id="editModelo" name="modelo" min="1900" max="2099" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="editKilometraje" class="form-label">Kilometraje Actual</label>
                            <input type="number" class="form-control" id="editKilometraje" name="kilometraje_actual" min="0" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editEstado" class="form-label">Estado</label>
                            <select class="form-select" id="editEstado" name="id_estado" required>
                                <option value="">Seleccione un estado</option>
                                <?php foreach ($estados as $estado): ?>
                                    <option value="<?= htmlspecialchars($estado['id_estado']) ?>">
                                        <?= htmlspecialchars($estado['estado']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="editFoto" class="form-label">Foto del Vehículo</label>
                            <input type="file" class="form-control" id="editFoto" name="foto_vehiculo" accept="image/*" onchange="previewImage(this, 'editFotoPreview')">
                            <small class="form-text text-muted">Deje en blanco para mantener la imagen actual.</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <img id="editFotoPreview" src="" alt="Vista previa" class="img-fluid" style="display: none; max-height: 200px;">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Eliminar Vehículo -->
<div class="modal fade" id="eliminarVehiculoModal" tabindex="-1" aria-labelledby="eliminarVehiculoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eliminarVehiculoModalLabel">
                    <i class="bi bi-trash"></i> Eliminar Vehículo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                <p>¿Está seguro de que desea eliminar el vehículo con placa <strong id="deletePlaca"></strong>?</p>
                <p class="text-danger">Esta acción no se puede deshacer.</p>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmarEliminar">
                    <i class="bi bi-trash"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>