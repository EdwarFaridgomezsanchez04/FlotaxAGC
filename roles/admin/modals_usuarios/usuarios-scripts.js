// Esperar a que el DOM esté completamente cargado antes de ejecutar el código
document.addEventListener('DOMContentLoaded', function () {
  
  // Función auxiliar para mostrar mensajes de alerta al usuario
  function showMessage(message, type = 'info') {
    // Crear un elemento div para mostrar alertas Bootstrap dinámicamente
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insertar la alerta al inicio del body para máxima visibilidad
    document.body.insertBefore(alertDiv, document.body.firstChild);
    
    // Auto-eliminar la alerta después de 5 segundos para no saturar la interfaz
    setTimeout(() => {
      if (alertDiv.parentNode) {
        alertDiv.remove();
      }
    }, 5000);
  }

  // Event listener para abrir el modal de agregar usuario
  document.getElementById('btnAgregarUsuario').addEventListener('click', function (e) {
    e.preventDefault(); // Prevenir comportamiento por defecto del enlace/botón
    
    // Resetear el formulario para limpiar datos previos
    document.getElementById('agregarUsuarioForm').reset();
    
    // Mostrar el modal de agregar usuario usando Bootstrap 5
    new bootstrap.Modal(document.getElementById('agregarUsuarioModal')).show();
  });

  // Event listeners para todos los botones de editar usuario
  document.querySelectorAll('.edit-user').forEach(button => {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      
      // Obtener el documento del usuario desde el atributo data-id
      const documento = this.getAttribute('data-id');
      
      // Validar que se obtuvo el documento correctamente
      if (!documento) {
        showMessage('Error: No se pudo obtener el documento del usuario', 'danger');
        return;
      }

      // Realizar petición AJAX para cargar los datos del usuario
      fetch(`modals_usuarios/get_usuario_data.php?documento=${encodeURIComponent(documento)}`)
        .then(response => {
          // Verificar que la respuesta HTTP sea exitosa
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
        .then(data => {
          // Procesar la respuesta JSON del servidor
          if (data.success) {
            const usuario = data.data;
            
            // Poblar los campos del formulario de edición con los datos obtenidos
            document.getElementById('documentoEditar').value = documento;
            document.getElementById('nombreCompletoEditar').value = usuario.nombre_completo || '';
            document.getElementById('emailEditar').value = usuario.email || '';
            document.getElementById('telefonoEditar').value = usuario.telefono || '';
            document.getElementById('estadoEditar').value = usuario.id_estado_usuario || '';
            document.getElementById('rolEditar').value = usuario.id_rol || '';
            
            // Mostrar el modal de edición con los datos cargados
            new bootstrap.Modal(document.getElementById('editarUsuarioModal')).show();
          } else {
            // Mostrar error si no se pudieron cargar los datos
            showMessage('Error al cargar los datos del usuario: ' + (data.message || 'Error desconocido'), 'danger');
          }
        })
        .catch(error => {
          // Manejo de errores de red o parsing JSON
          console.error('Error:', error);
          showMessage('Error de conexión al cargar los datos del usuario', 'danger');
        });
    });
  });

  // Event listener para el botón de actualizar usuario
  document.getElementById('actualizarUsuario').addEventListener('click', function (e) {
    e.preventDefault();
    
    // Validar el formulario usando validación HTML5
    const form = document.getElementById('editarUsuarioForm');
    if (!form.checkValidity()) {
      form.reportValidity(); // Mostrar mensajes de validación nativos
      return;
    }

    // Crear FormData con todos los campos del formulario
    const formData = new FormData(form);
    
    // Enviar datos de actualización al servidor via AJAX
    fetch('modals_usuarios/actualizar_usuario.php', {
      method: 'POST',
      body: formData
    })
    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.text(); // Respuesta en texto plano del servidor
    })
    .then(result => {
      // Verificar si la actualización fue exitosa
      if (result.includes('exitosamente')) {
        showMessage(result, 'success');
        // Cerrar el modal y recargar la página para mostrar cambios
        bootstrap.Modal.getInstance(document.getElementById('editarUsuarioModal')).hide();
        setTimeout(() => location.reload(), 1500);
      } else {
        showMessage(result, 'danger');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showMessage('Error de conexión al actualizar el usuario', 'danger');
    });
  });

  // Event listeners para todos los botones de eliminar usuario
  document.querySelectorAll('.delete-user').forEach(button => {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      
      // Obtener el documento del usuario a eliminar
      const documento = this.getAttribute('data-id');
      
      if (!documento) {
        showMessage('Error: No se pudo obtener el documento del usuario', 'danger');
        return;
      }
      
      // Mostrar el documento en el modal de confirmación
      document.getElementById('documentoEliminar').textContent = documento;
      document.getElementById('documentoEliminarInput').value = documento;
      
      // Mostrar modal de confirmación de eliminación
      new bootstrap.Modal(document.getElementById('eliminarUsuarioModal')).show();
    });
  });

  // Event listener para confirmar la eliminación del usuario
  document.getElementById('confirmarEliminar').addEventListener('click', function (e) {
    e.preventDefault();
    
    // Obtener el documento del usuario a eliminar
    const documento = document.getElementById('documentoEliminarInput').value;
    
    if (!documento) {
      showMessage('Error: No se pudo obtener el documento del usuario', 'danger');
      return;
    }

    // Enviar petición de eliminación al servidor
    fetch('modals_usuarios/eliminar_usuario.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `documento=${encodeURIComponent(documento)}`
    })
    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json(); // Respuesta JSON del servidor
    })
    .then(data => {
      // Procesar respuesta de eliminación
      if (data.success) {
        showMessage(data.message, 'success');
        // Cerrar modal y recargar página para reflejar cambios
        bootstrap.Modal.getInstance(document.getElementById('eliminarUsuarioModal')).hide();
        setTimeout(() => location.reload(), 1500);
      } else {
        showMessage(data.error || 'Error al eliminar el usuario', 'danger');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showMessage('Error de conexión al eliminar el usuario', 'danger');
    });
  });

  // Event listener para guardar nuevo usuario
  document.getElementById('guardarUsuario').addEventListener('click', function (e) {
    e.preventDefault();
    
    // Validar formulario antes de enviar
    const form = document.getElementById('agregarUsuarioForm');
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    // Crear FormData con los datos del nuevo usuario
    const formData = new FormData(form);
    
    // Enviar datos al servidor para crear nuevo usuario
    fetch('modals_usuarios/agregar_usuario.php', {
      method: 'POST',
      body: formData
    })
    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.text();
    })
    .then(result => {
      // Verificar si la creación fue exitosa
      if (result.includes('exitosamente')) {
        showMessage(result, 'success');
        // Cerrar modal y recargar página para mostrar nuevo usuario
        bootstrap.Modal.getInstance(document.getElementById('agregarUsuarioModal')).hide();
        setTimeout(() => location.reload(), 1500);
      } else {
        showMessage(result, 'danger');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showMessage('Error de conexión al agregar el usuario', 'danger');
    });
  });
});