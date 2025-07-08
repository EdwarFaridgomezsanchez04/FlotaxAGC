<?php
// Incluir archivo de conexión a la base de datos
require_once('../../conecct/conex.php');

// Crear instancia de conexión a la base de datos para cargar datos dinámicos
$db = new Database();
$con = $db->conectar();
?>

<!-- Modal Agregar Usuario -->
<!-- Modal Bootstrap 5 para agregar nuevos usuarios al sistema -->
<div class="modal fade" id="agregarUsuarioModal" tabindex="-1" aria-labelledby="agregarUsuarioModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg"> <!-- Tamaño grande para acomodar todos los campos -->
    <div class="modal-content">
      <!-- Encabezado del modal con título y botón de cierre -->
      <div class="modal-header">
        <h5 class="modal-title" id="agregarUsuarioModalLabel">Agregar Nuevo Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <!-- Cuerpo del modal con formulario de registro -->
      <div class="modal-body">
        <form id="agregarUsuarioForm">
          <!-- Campo para documento de identidad (requerido) -->
          <div class="mb-3">
            <label for="documentoAgregar" class="form-label">Documento</label>
            <input type="text" class="form-control" id="documentoAgregar" name="documento" required>
          </div>
          
          <!-- Campo para nombre completo del usuario -->
          <div class="mb-3">
            <label for="nombreCompletoAgregar" class="form-label">Nombre Completo</label>
            <input type="text" class="form-control" id="nombreCompletoAgregar" name="nombre_completo" required>
          </div>
          
          <!-- Campo para dirección de correo electrónico -->
          <div class="mb-3">
            <label for="emailAgregar" class="form-label">Email</label>
            <input type="email" class="form-control" id="emailAgregar" name="email" required>
          </div>
          
          <!-- Campo para contraseña inicial del usuario -->
          <div class="mb-3">
            <label for="passwordAgregar" class="form-label">Contraseña</label>
            <input type="password" class="form-control" id="passwordAgregar" name="password" required>
          </div>
          
          <!-- Campo para número de teléfono -->
          <div class="mb-3">
            <label for="telefonoAgregar" class="form-label">Teléfono</label>
            <input type="tel" class="form-control" id="telefonoAgregar" name="telefono" required>
          </div>
          
          <!-- Selector dinámico de estado del usuario -->
          <div class="mb-3">
            <label for="estadoAgregar" class="form-label">Estado</label>
            <select class="form-select" id="estadoAgregar" name="estado" required>
              <option value="">Seleccione</option>
              <?php
              // Consulta para obtener todos los estados disponibles desde la base de datos
              $estadoQuery = $con->prepare("SELECT id_estado, tipo_stade FROM estado_usuario");
              $estadoQuery->execute();
              $estados = $estadoQuery->fetchAll(PDO::FETCH_ASSOC);
              
              // Generar opciones dinámicamente desde la base de datos
              foreach ($estados as $estado) {
                echo "<option value='{$estado['id_estado']}'>{$estado['tipo_stade']}</option>";
              }
              ?>
            </select>
          </div>
          
          <!-- Selector dinámico de rol del usuario -->
          <div class="mb-3">
            <label for="rolAgregar" class="form-label">Rol</label>
            <select class="form-select" id="rolAgregar" name="rol" required>
              <option value="">Seleccione</option>
              <?php
              // Consulta para obtener todos los roles disponibles desde la base de datos
              $rolQuery = $con->prepare("SELECT id_rol, tip_rol FROM roles");
              $rolQuery->execute();
              $roles = $rolQuery->fetchAll(PDO::FETCH_ASSOC);
              
              // Generar opciones dinámicamente desde la base de datos
              foreach ($roles as $rol) {
                echo "<option value='{$rol['id_rol']}'>{$rol['tip_rol']}</option>";
              }
              ?>
            </select>
          </div>
        </form>
      </div>
      
      <!-- Pie del modal con botones de acción -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="guardarUsuario">Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Editar Usuario -->
<!-- Modal Bootstrap 5 para editar información de usuarios existentes -->
<div class="modal fade" id="editarUsuarioModal" tabindex="-1" aria-labelledby="editarUsuarioModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg"> <!-- Tamaño grande para acomodar todos los campos -->
    <div class="modal-content">
      <!-- Encabezado del modal con título y botón de cierre -->
      <div class="modal-header">
        <h5 class="modal-title" id="editarUsuarioModalLabel">Editar Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <!-- Cuerpo del modal con formulario de edición -->
      <div class="modal-body">
        <form id="editarUsuarioForm">
          <!-- Campo oculto para mantener el documento original (no editable) -->
          <input type="hidden" id="documentoEditar" name="documento">
          
          <!-- Campo para editar nombre completo -->
          <div class="mb-3">
            <label for="nombreCompletoEditar" class="form-label">Nombre Completo</label>
            <input type="text" class="form-control" id="nombreCompletoEditar" name="nombre_completo" required>
          </div>
          
          <!-- Campo para editar email -->
          <div class="mb-3">
            <label for="emailEditar" class="form-label">Email</label>
            <input type="email" class="form-control" id="emailEditar" name="email" required>
          </div>
          
          <!-- Campo para editar teléfono -->
          <div class="mb-3">
            <label for="telefonoEditar" class="form-label">Teléfono</label>
            <input type="tel" class="form-control" id="telefonoEditar" name="telefono" required>
          </div>
          
          <!-- Selector dinámico para cambiar estado del usuario -->
          <div class="mb-3">
            <label for="estadoEditar" class="form-label">Estado</label>
            <select class="form-select" id="estadoEditar" name="estado" required>
              <option value="">Seleccione</option>
              <?php
              // Reutilizar consulta de estados para el modal de edición
              $estadoQuery = $con->prepare("SELECT id_estado, tipo_stade FROM estado_usuario");
              $estadoQuery->execute();
              $estados = $estadoQuery->fetchAll(PDO::FETCH_ASSOC);
              foreach ($estados as $estado) {
                echo "<option value='{$estado['id_estado']}'>{$estado['tipo_stade']}</option>";
              }
              ?>
            </select>
          </div>
          
          <!-- Selector dinámico para cambiar rol del usuario -->
          <div class="mb-3">
            <label for="rolEditar" class="form-label">Rol</label>
            <select class="form-select" id="rolEditar" name="rol" required>
              <option value="">Seleccione</option>
              <?php
              // Reutilizar consulta de roles para el modal de edición
              $rolQuery = $con->prepare("SELECT id_rol, tip_rol FROM roles");
              $rolQuery->execute();
              $roles = $rolQuery->fetchAll(PDO::FETCH_ASSOC);
              foreach ($roles as $rol) {
                echo "<option value='{$rol['id_rol']}'>{$rol['tip_rol']}</option>";
              }
              ?>
            </select>
          </div>
        </form>
      </div>
      
      <!-- Pie del modal con botones de acción -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="actualizarUsuario">Actualizar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Eliminar Usuario -->
<!-- Modal Bootstrap 5 para confirmar eliminación de usuarios -->
<div class="modal fade" id="eliminarUsuarioModal" tabindex="-1" aria-labelledby="eliminarUsuarioModalLabel" aria-hidden="true">
  <div class="modal-dialog"> <!-- Tamaño estándar para confirmación simple -->
    <div class="modal-content">
      <!-- Encabezado del modal con título y botón de cierre -->
      <div class="modal-header">
        <h5 class="modal-title" id="eliminarUsuarioModalLabel">Eliminar Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <!-- Cuerpo del modal con mensaje de confirmación -->
      <div class="modal-body">
        <!-- Mensaje de advertencia con documento del usuario a eliminar -->
        <p>¿Estás seguro de eliminar el usuario con documento <span id="documentoEliminar"></span>? Esta acción no se puede deshacer.</p>
        <!-- Campo oculto para almacenar el documento del usuario a eliminar -->
        <input type="hidden" id="documentoEliminarInput" name="documento">
      </div>
      
      <!-- Pie del modal con botones de confirmación -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="confirmarEliminar">Eliminar</button>
      </div>
    </div>
  </div>
</div>