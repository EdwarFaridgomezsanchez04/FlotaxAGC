// Vehicle Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeVehicleModals();
});

function initializeVehicleModals() {
    // Add Vehicle Button
    const btnAgregarVehiculo = document.getElementById('btnAgregarVehiculo');
    if (btnAgregarVehiculo) {
        btnAgregarVehiculo.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('modalAgregarVehiculo'));
            modal.show();
        });
    }

    // Edit Vehicle Buttons
    document.querySelectorAll('.action-icon.edit').forEach(button => {
        button.addEventListener('click', function() {
            const vehicleId = this.getAttribute('data-id');
            editarVehiculo(vehicleId);
        });
    });

    // Delete Vehicle Buttons
    document.querySelectorAll('.action-icon.delete').forEach(button => {
        button.addEventListener('click', function() {
            const vehicleId = this.getAttribute('data-id');
            eliminarVehiculo(vehicleId);
        });
    });

    // Form submission handlers
    const formAgregar = document.getElementById('formAgregarVehiculo');
    if (formAgregar) {
        formAgregar.addEventListener('submit', manejarAgregarVehiculo);
    }

    const formEditar = document.getElementById('editarVehiculoForm');
    if (formEditar) {
        formEditar.addEventListener('submit', manejarEditarVehiculo);
    }
}

// Handle Add Vehicle Form Submission
function manejarAgregarVehiculo(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    formData.append('accion', 'agregar');
    
    fetch('procesar_vehiculo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned non-JSON response');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Vehículo agregado exitosamente');
            location.reload();
        } else if (data.redirect) {
            window.location.href = data.redirect;
        } else {
            alert('Error: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la solicitud: ' + error.message);
    });
}

// Handle Edit Vehicle Form Submission
function manejarEditarVehiculo(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    formData.append('accion', 'editar'); // Cambiar a 'accion'
    
    fetch('modals_vehiculos/update_vehicle.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned non-JSON response');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Vehículo actualizado exitosamente');
            location.reload();
        } else if (data.redirect) {
            window.location.href = data.redirect;
        } else {
            alert('Error: ' + (data.message || data.error || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al actualizar el vehículo: ' + error.message);
    });
}

// Edit Vehicle Function
function editarVehiculo(vehicleId) {
    fetch(`modals_vehiculos/get_vehicle.php?placa=${vehicleId}`) // Cambiar a 'placa'
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned non-JSON response');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            populateEditForm(data.vehicle);
            const modal = new bootstrap.Modal(document.getElementById('editarVehiculoModal')); // ID correcto
            modal.show();
        } else if (data.redirect) {
            window.location.href = data.redirect;
        } else {
            alert('Error al cargar los datos del vehículo: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al cargar el vehículo: ' + error.message);
    });
}

// Delete Vehicle Function
function eliminarVehiculo(vehicleId) {
    if (confirm('¿Está seguro de que desea eliminar este vehículo?')) {
        fetch('modals_vehiculos/delete_vehicle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `placa=${vehicleId}` // Cambiar a 'placa'
        })
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server returned non-JSON response');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Vehículo eliminado exitosamente');
                location.reload();
            } else if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                alert('Error: ' + (data.message || data.error || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar el vehículo: ' + error.message);
        });
    }
}

// Populate Edit Form with Vehicle Data
function populateEditForm(vehicle) {
    const form = document.getElementById('editarVehiculoForm'); // ID correcto
    if (!form) {
        console.error('Form editarVehiculoForm not found');
        return;
    }
    
    // Mapeo correcto de campos
    const fieldMappings = {
        'placa': 'editPlaca',
        'Documento': 'editDocumento', 
        'id_marca': 'editMarca',
        'modelo': 'editModelo',
        'kilometraje_actual': 'editKilometraje',
        'id_estado': 'editEstado'
    };
    
    Object.keys(fieldMappings).forEach(vehicleField => {
        const formFieldId = fieldMappings[vehicleField];
        const input = document.getElementById(formFieldId);
        if (input && vehicle[vehicleField] !== undefined) {
            input.value = vehicle[vehicleField];
        }
    });
}

// Image Preview Function
function previewImage(input, previewId) {
    const file = input.files[0];
    const preview = document.getElementById(previewId);
    
    if (file && preview) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else if (preview) {
        preview.style.display = 'none';
    }
}