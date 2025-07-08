// Variables globales para el manejo de mantenimientos
let mantenimientoIdParaEliminar = null; // Almacena el ID del mantenimiento que se va a eliminar

/**
 * Función para abrir el modal de agregar nuevo mantenimiento
 * Limpia el formulario y establece la fecha mínima como la fecha actual
 */
function abrirModalAgregarMantenimiento() {
    // Limpiar todos los campos del formulario de agregar mantenimiento
    document.getElementById('formAgregarMantenimiento').reset();
    
    // Establecer fecha mínima como hoy para evitar fechas pasadas
    const hoy = new Date().toISOString().split('T')[0];
    document.getElementById('fechaProgramadaAgregar').min = hoy;
    
    // Mostrar el modal de agregar mantenimiento
    const modal = new bootstrap.Modal(document.getElementById('modalAgregarMantenimiento'));
    modal.show();
}

/**
 * Función para editar un mantenimiento existente
 * @param {number} id - ID del mantenimiento a editar
 */
// Función para editar mantenimiento
// Función para editar mantenimiento
function editarMantenimiento(id) {
    // Carga datos via AJAX y abre modal
    fetch(`modals_mantenimiento/get_mantenimiento.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            // Llena formulario y muestra modal
        });
}

// Función para eliminar mantenimiento
function eliminarMantenimiento(id, placa) {
    // Carga datos y muestra confirmación
    fetch(`modals_mantenimiento/get_mantenimiento.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            // Muestra modal de confirmación
        });
}

// Función para eliminar mantenimiento
function eliminarMantenimiento(id, placa) {
    // Carga datos y muestra confirmación
    fetch(`modals_mantenimiento/get_mantenimiento.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            // Muestra modal de confirmación
        });
}

/**
 * Función para ver los detalles de un mantenimiento en modo solo lectura
 * @param {number} id - ID del mantenimiento a visualizar
 */
function verMantenimiento(id) {
    // Realizar petición AJAX para obtener los datos del mantenimiento
    fetch(`modals_mantenimiento/get_mantenimiento.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const mant = data.mantenimiento;
                
                // Llenar el modal de detalles con la información del mantenimiento
                document.getElementById('verIdMantenimiento').textContent = mant.id_mantenimiento;
                document.getElementById('verPlaca').textContent = mant.placa;
                document.getElementById('verTipoMantenimiento').textContent = mant.tipo_descripcion;
                // Determinar el estado basado en si tiene fecha de realización
                document.getElementById('verEstado').textContent = mant.fecha_realizada ? 'Completado' : 'Pendiente';
                document.getElementById('verFechaProgramada').textContent = formatearFecha(mant.fecha_programada);
                document.getElementById('verFechaRealizada').textContent = mant.fecha_realizada ? formatearFecha(mant.fecha_realizada) : 'No realizado';
                document.getElementById('verKilometrajeActual').textContent = mant.kilometraje_actual ? formatearNumero(mant.kilometraje_actual) + ' km' : 'No especificado';
                document.getElementById('verProximoCambioKm').textContent = mant.proximo_cambio_km ? formatearNumero(mant.proximo_cambio_km) + ' km' : 'No especificado';
                document.getElementById('verProximoCambioFecha').textContent = mant.proximo_cambio_fecha ? formatearFecha(mant.proximo_cambio_fecha) : 'No especificado';
                document.getElementById('verObservaciones').textContent = mant.observaciones || 'Sin observaciones';
                
                // Mostrar el modal de detalles
                const modal = new bootstrap.Modal(document.getElementById('modalVerMantenimiento'));
                modal.show();
            } else {
                // Mostrar mensaje de error si no se pueden cargar los datos
                mostrarAlerta('Error al cargar los datos del mantenimiento', 'danger');
            }
        })
        .catch(error => {
            // Manejar errores de conexión
            console.error('Error:', error);
            mostrarAlerta('Error de conexión', 'danger');
        });
}

/**
 * Función para confirmar la eliminación de un mantenimiento
 * @param {number} id - ID del mantenimiento a eliminar
 * @param {string} placa - Placa del vehículo (parámetro heredado, no usado directamente)
 */
function eliminarMantenimiento(id, placa) {
    // Guardar el ID del mantenimiento que se va a eliminar
    mantenimientoIdParaEliminar = id;
    
    // Obtener datos del mantenimiento para mostrar información detallada en la confirmación
    fetch(`modals_mantenimiento/get_mantenimiento.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const mant = data.mantenimiento;
                
                // Mostrar información del mantenimiento que se va a eliminar
                document.getElementById('placaEliminar').textContent = mant.placa;
                document.getElementById('tipoEliminar').textContent = mant.tipo_descripcion;
                document.getElementById('fechaEliminar').textContent = formatearFecha(mant.fecha_programada);
                document.getElementById('idMantenimientoEliminar').value = id;
                
                // Mostrar modal de confirmación de eliminación
                const modal = new bootstrap.Modal(document.getElementById('modalEliminarMantenimiento'));
                modal.show();
            } else {
                // Mostrar mensaje de error si no se pueden cargar los datos
                mostrarAlerta('Error al cargar los datos del mantenimiento', 'danger');
            }
        })
        .catch(error => {
            // Manejar errores de conexión
            console.error('Error:', error);
            mostrarAlerta('Error de conexión', 'danger');
        });
}

// Event listeners para manejar los formularios cuando el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    // Manejador para el formulario de agregar mantenimiento
    document.getElementById('formAgregarMantenimiento').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevenir el envío normal del formulario
        
        // Crear FormData con todos los datos del formulario
        const formData = new FormData(this);
        formData.append('accion', 'agregar'); // Especificar la acción a realizar
        
        // Enviar datos via AJAX al servidor
        fetch('modals_mantenimiento/procesar_mantenimiento.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar mensaje de éxito y cerrar modal
                mostrarAlerta('Mantenimiento agregado exitosamente', 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalAgregarMantenimiento')).hide();
                // Recargar la página después de 1.5 segundos para mostrar los cambios
                setTimeout(() => location.reload(), 1500);
            } else {
                // Mostrar mensaje de error
                mostrarAlerta(data.message || 'Error al agregar el mantenimiento', 'danger');
            }
        })
        .catch(error => {
            // Manejar errores de conexión
            console.error('Error:', error);
            mostrarAlerta('Error de conexión', 'danger');
        });
    });
    
    // Manejador para el formulario de editar mantenimiento
    document.getElementById('formEditarMantenimiento').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevenir el envío normal del formulario
        
        // Crear FormData con todos los datos del formulario
        const formData = new FormData(this);
        formData.append('accion', 'editar'); // Especificar la acción a realizar
        
        // Enviar datos via AJAX al servidor
        fetch('modals_mantenimiento/procesar_mantenimiento.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar mensaje de éxito y cerrar modal
                mostrarAlerta('Mantenimiento actualizado exitosamente', 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalEditarMantenimiento')).hide();
                // Recargar la página después de 1.5 segundos para mostrar los cambios
                setTimeout(() => location.reload(), 1500);
            } else {
                // Mostrar mensaje de error
                mostrarAlerta(data.message || 'Error al actualizar el mantenimiento', 'danger');
            }
        })
        .catch(error => {
            // Manejar errores de conexión
            console.error('Error:', error);
            mostrarAlerta('Error de conexión', 'danger');
        });
    });
    
    // Manejador para confirmar la eliminación de mantenimiento
    document.getElementById('confirmarEliminar').addEventListener('click', function() {
        if (mantenimientoIdParaEliminar) {
            // Crear FormData para la eliminación
            const formData = new FormData();
            formData.append('accion', 'eliminar');
            formData.append('id_mantenimiento', mantenimientoIdParaEliminar);
            
            // Enviar petición de eliminación al servidor
            fetch('modals_mantenimiento/procesar_mantenimiento.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito y cerrar modal
                    mostrarAlerta('Mantenimiento eliminado exitosamente', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('modalEliminarMantenimiento')).hide();
                    // Recargar la página después de 1.5 segundos para mostrar los cambios
                    setTimeout(() => location.reload(), 1500);
                } else {
                    // Mostrar mensaje de error
                    mostrarAlerta(data.message || 'Error al eliminar el mantenimiento', 'danger');
                }
            })
            .catch(error => {
                // Manejar errores de conexión
                console.error('Error:', error);
                mostrarAlerta('Error de conexión', 'danger');
            });
        }
    });
});

// Funciones auxiliares para formateo y utilidades

/**
 * Formatea una fecha en formato español (dd/mm/aaaa)
 * @param {string} fecha - Fecha en formato ISO (YYYY-MM-DD)
 * @returns {string} Fecha formateada en español
 */
function formatearFecha(fecha) {
    if (!fecha) return '';
    // Crear objeto Date agregando tiempo para evitar problemas de zona horaria
    const date = new Date(fecha + 'T00:00:00');
    return date.toLocaleDateString('es-ES');
}

/**
 * Formatea un número con separadores de miles en formato español
 * @param {number} numero - Número a formatear
 * @returns {string} Número formateado con separadores de miles
 */
function formatearNumero(numero) {
    return new Intl.NumberFormat('es-ES').format(numero);
}

/**
 * Muestra una alerta temporal en la esquina superior derecha de la pantalla
 * @param {string} mensaje - Mensaje a mostrar en la alerta
 * @param {string} tipo - Tipo de alerta Bootstrap (success, danger, warning, info)
 */
function mostrarAlerta(mensaje, tipo) {
    // Crear elemento de alerta dinámicamente
    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo} alert-dismissible fade show position-fixed`;
    alerta.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alerta.innerHTML = `
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Agregar la alerta al body del documento
    document.body.appendChild(alerta);
    
    // Auto-remover la alerta después de 5 segundos
    setTimeout(() => {
        if (alerta.parentNode) {
            alerta.remove();
        }
    }, 5000);
}