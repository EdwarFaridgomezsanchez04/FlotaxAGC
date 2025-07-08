class ReportsManager {
  constructor() {
    this.currentReport = null
    this.currentFilters = {}
    this.isLoading = false
    this.init()
  }

  init() {
    this.setupEventListeners()
    this.setupDateDefaults()
    this.initializeAnimations()
    
    // Agregar este código para manejar el cierre del modal
    const modalReporte = document.getElementById('modalReporte')
    modalReporte.addEventListener('hidden.bs.modal', () => {
        document.body.style.overflow = ''
        this.resetModal()
    })
}

  setupEventListeners() {
    // Event listeners para botones de reportes
    document.addEventListener("click", (e) => {
      if (e.target.closest('[onclick*="abrirReporte"]')) {
        e.preventDefault()
        const button = e.target.closest('[onclick*="abrirReporte"]')
        const onclick = button.getAttribute("onclick")
        const tipoMatch = onclick.match(/abrirReporte\('([^']+)'\)/)
        if (tipoMatch) {
          const tipo = tipoMatch[1]
          this.openReport(tipo)
        }
      }

      if (e.target.closest('[onclick*="exportarReporte"]')) {
        e.preventDefault()
        const button = e.target.closest('[onclick*="exportarReporte"]')
        const onclick = button.getAttribute("onclick")
        const matches = onclick.match(/exportarReporte\('([^']+)',\s*'([^']+)'\)/)
        if (matches) {
          this.exportReport(matches[1], matches[2])
        }
      }
    })

    // Event listeners para filtros
    document.addEventListener("change", (e) => {
      if (e.target.closest("#filtrosReporte")) {
        this.handleFilterChange()
      }
    })

    // Event listeners para modal
    const modal = document.getElementById("modalReporte")
    if (modal) {
      modal.addEventListener("hidden.bs.modal", () => {
        this.resetModal()
      })
    }
  }

  setupDateDefaults() {
    const today = new Date()
    const thirtyDaysAgo = new Date()
    thirtyDaysAgo.setDate(today.getDate() - 30)

    // Configurar fechas por defecto cuando se abra el modal
    document.addEventListener("shown.bs.modal", (e) => {
      if (e.target.id === "modalReporte") {
        const fechaDesde = document.getElementById("filtro_fecha_desde")
        const fechaHasta = document.getElementById("filtro_fecha_hasta")

        if (fechaDesde && !fechaDesde.value) {
          fechaDesde.value = thirtyDaysAgo.toISOString().split("T")[0]
        }
        if (fechaHasta && !fechaHasta.value) {
          fechaHasta.value = today.toISOString().split("T")[0]
        }
      }
    })
  }

  initializeAnimations() {
    // Animar tarjetas de reporte al cargar
    const reportCards = document.querySelectorAll(".report-card")
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry, index) => {
        if (entry.isIntersecting) {
          setTimeout(() => {
            entry.target.style.opacity = "1"
            entry.target.style.transform = "translateY(0)"
          }, index * 100)
        }
      })
    })

    reportCards.forEach((card) => {
      card.style.opacity = "0"
      card.style.transform = "translateY(30px)"
      card.style.transition = "all 0.6s ease-out"
      observer.observe(card)
    })
  }

  openReport(type) {
    this.currentReport = type
    this.currentFilters = {}

    const modalElement = document.getElementById("modalReporte")
    const modal = new window.bootstrap.Modal(modalElement)
    const title = document.getElementById("tituloReporte")
    const filtersContainer = document.getElementById("filtrosReporte")

    // Configurar título
    const titles = {
        vehiculos: "Reporte de Vehículos",
        mantenimientos: "Reporte de Mantenimientos",
        llantas: "Reporte de Llantas",
        soat: "Reporte de SOAT",
        tecnomecanica: "Reporte de Tecnomecánica",
        licencias: "Reporte de Licencias",
        alertas: "Reporte de Alertas",
        actividad: "Reporte de Actividad General",
    }

    title.innerHTML = `${titles[type] || "Reporte"}`

    // Generar filtros
    filtersContainer.innerHTML = this.generateFilters(type)

    // Agregar manejadores de eventos para el modal
    modalElement.addEventListener('show.bs.modal', () => {
        document.body.style.overflow = 'hidden'
    }, { once: true })

    modalElement.addEventListener('hidden.bs.modal', () => {
        document.body.style.overflow = 'auto'
        this.resetModal()
    }, { once: true })

    modal.show()

    // Cargar datos iniciales
    this.loadReportData(type, {})
  }
  

  generateFilters(type) {
    let html = `
            <div class="col-md-3">
                <label class="form-label">
                    <i class="bi bi-calendar-event me-1"></i>
                    Fecha Desde
                </label>
                <input type="date" class="form-control" id="filtro_fecha_desde">
            </div>
            <div class="col-md-3">
                <label class="form-label">
                    <i class="bi bi-calendar-check me-1"></i>
                    Fecha Hasta
                </label>
                <input type="date" class="form-control" id="filtro_fecha_hasta">
            </div>
        `

    // Filtros específicos por tipo
    switch (type) {
      case "vehiculos":
        html += `
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="bi bi-car-front me-1"></i>
                            Placa
                        </label>
                        <input type="text" class="form-control" id="filtro_placa" placeholder="Ej: ABC123">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="bi bi-check-circle me-1"></i>
                            Estado
                        </label>
                        <select class="form-select" id="filtro_estado">
                            <option value="">Todos los estados</option>
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                            <option value="Mantenimiento">En Mantenimiento</option>
                        </select>
                    </div>
                `
        break

      case "mantenimientos":
        html += `
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="bi bi-car-front me-1"></i>
                            Placa
                        </label>
                        <input type="text" class="form-control" id="filtro_placa" placeholder="Ej: ABC123">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="bi bi-tools me-1"></i>
                            Estado
                        </label>
                        <select class="form-select" id="filtro_estado">
                            <option value="">Todos los estados</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="realizado">Realizado</option>
                        </select>
                    </div>
                `
        break
        

      case "llantas":
        html += `
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="bi bi-car-front me-1"></i>
                            Placa
                        </label>
                        <input type="text" class="form-control" id="filtro_placa" placeholder="Ej: ABC123">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="bi bi-circle me-1"></i>
                            Estado
                        </label>
                        <select class="form-select" id="filtro_estado">
                            <option value="">Todos los estados</option>
                            <option value="Bueno">Bueno</option>
                            <option value="Regular">Regular</option>
                            <option value="Malo">Malo</option>
                        </select>
                    </div>
                `
        break

      case "soat":
      case "tecnomecanica":
        html += `
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="bi bi-car-front me-1"></i>
                            Placa
                        </label>
                        <input type="text" class="form-control" id="filtro_placa" placeholder="Ej: ABC123">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="bi bi-shield-check me-1"></i>
                            Estado
                        </label>
                        <select class="form-select" id="filtro_estado">
                            <option value="">Todos los estados</option>
                            <option value="vigente">Vigente</option>
                            <option value="vencido">Vencido</option>
                            <option value="proximo_vencer">Próximo a Vencer</option>
                        </select>
                    </div>
                `
        break

      case "licencias":
        html += `
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="bi bi-person me-1"></i>
                            Documento
                        </label>
                        <input type="text" class="form-control" id="filtro_documento" placeholder="Documento">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="bi bi-person-badge me-1"></i>
                            Estado
                        </label>
                        <select class="form-select" id="filtro_estado">
                            <option value="">Todos los estados</option>
                            <option value="vigente">Vigente</option>
                            <option value="vencido">Vencido</option>
                            <option value="proximo_vencer">Próximo a Vencer</option>
                        </select>
                    </div>
                `
        break

      case "alertas":
        html += `
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="bi bi-bell me-1"></i>
                            Tipo
                        </label>
                        <select class="form-select" id="filtro_tipo">
                            <option value="">Todos los tipos</option>
                            <option value="SOAT">SOAT</option>
                            <option value="tecnomecanica">Tecnomecánica</option>
                            <option value="mantenimiento">Mantenimiento</option>
                            <option value="licencia">Licencia</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="bi bi-eye me-1"></i>
                            Leído
                        </label>
                        <select class="form-select" id="filtro_leido">
                            <option value="">Todos</option>
                            <option value="si">Sí</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                `
        break


      case "actividad":
        html += `
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="bi bi-activity me-1"></i>
                            Tipo de Actividad
                        </label>
                        <select class="form-select" id="filtro_tipo">
                            <option value="">Todas las actividades</option>
                            <option value="registro">Registros</option>
                            <option value="mantenimiento">Mantenimientos</option>
                            <option value="soat">SOAT</option>
                            <option value="tecnomecanica">Tecnomecánica</option>
                        </select>
                    </div>
                `
        break
    }

    return html
  }

  loadReportData(type, filters) {
    if (this.isLoading) return

    this.isLoading = true
    const content = document.getElementById("contenidoReporte")

    // Mostrar loading con shimmer effect
    content.innerHTML = this.getLoadingHTML()

    // Simular carga de datos
    const params = new URLSearchParams({
      tipo: type,
      filtros: JSON.stringify(filters),
    })

    fetch(`reportes_ajax.php?${params}`)
      .then((response) => {
        if (!response.ok) {
          throw new Error("Error en la respuesta del servidor")
        }
        return response.json()
      })
      .then((data) => {
        this.isLoading = false
        if (data.success) {
          content.innerHTML = this.generateReportTable(data.datos, type)
          this.animateTableRows()
        } else {
          content.innerHTML = this.getErrorHTML(data.message || "Error al cargar los datos")
        }
      })
      .catch((error) => {
        this.isLoading = false
        console.error("Error:", error)
        content.innerHTML = this.getErrorHTML("Error de conexión. Por favor, intente nuevamente.")
      })
  }

  getLoadingHTML() {
    return `
            <div class="loading-container">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <div class="loading-text">Cargando datos del reporte...</div>
                <div class="mt-4 w-100">
                    <div class="loading-shimmer" style="height: 20px; border-radius: 4px; margin-bottom: 10px;"></div>
                    <div class="loading-shimmer" style="height: 20px; border-radius: 4px; margin-bottom: 10px; width: 80%;"></div>
                    <div class="loading-shimmer" style="height: 20px; border-radius: 4px; width: 60%;"></div>
                </div>
            </div>
        `
  }

  getErrorHTML(message) {
    return `
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div>${message}</div>
            </div>
        `
  }

  generateReportTable(data, type) {
    if (!data || data.length === 0) {
      return `
                <div class="no-data-container">
                    <i class="bi bi-inbox"></i>
                    <h5>No hay datos disponibles</h5>
                    <p>No se encontraron registros con los filtros aplicados.</p>
                    <button class="btn btn-outline-primary mt-3" onclick="reportsManager.clearFilters()">
                        <i class="bi bi-arrow-clockwise me-1"></i>
                        Limpiar Filtros
                    </button>
                </div>
            `
    }

    let html = `
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
        `

    // Generar encabezados
    Object.keys(data[0]).forEach((key) => {
      const displayName = this.formatColumnName(key)
      html += `<th><i class="bi bi-sort-alpha-down me-1"></i>${displayName}</th>`
    })

    html += `
                        </tr>
                    </thead>
                    <tbody>
        `

    // Generar filas
    data.forEach((row, index) => {
      html += `<tr style="animation-delay: ${index * 0.05}s">`
      Object.entries(row).forEach(([key, value]) => {
        const formattedValue = this.formatCellValue(key, value)
        const cellClass = this.getCellClass(key, value)
        html += `<td class="${cellClass}">${formattedValue}</td>`
      })
      html += "</tr>"
    })

    html += `
                    </tbody>
                </table>
            </div>
            <div class="mt-4 d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    Total de registros: <strong>${data.length}</strong>
                </div>
                <div>
                    <button class="btn btn-outline-primary btn-sm me-2" onclick="reportsManager.refreshReport()">
                        <i class="bi bi-arrow-clockwise me-1"></i>
                        Actualizar
                    </button>
                </div>
            </div>
        `

    return html
  }

  formatColumnName(key) {
    const columnNames = {
      placa: "Placa",
      marca: "Marca",
      modelo: "Modelo",
      anio: "Año",
      color: "Color",
      estado_vehiculo: "Estado",
      fecha_registro: "Fecha Registro",
      fecha_expedicion: "Fecha Expedición",
      fecha_vencimiento: "Fecha Vencimiento",
      numero_poliza: "Número Póliza",
      aseguradora: "Aseguradora",
      dias_restantes: "Días Restantes",
      usuario_responsable: "Usuario Responsable",
    }

    return columnNames[key] || key.replace(/_/g, " ").replace(/\b\w/g, (l) => l.toUpperCase())
  }

  formatCellValue(key, value) {
    if (value === null || value === undefined) return "-"

    // Formatear fechas
    if (key.includes("fecha") && value) {
      const date = new Date(value)
      return date.toLocaleDateString("es-ES")
    }

    // Formatear números
    if (key.includes("dias_restantes") && typeof value === "number") {
      if (value < 0) return `${Math.abs(value)} días vencido`
      if (value === 0) return "Vence hoy"
      return `${value} días`
    }

    return value
  }

  getCellClass(key, value) {
    let classes = ""

    if (key === "estado" || key === "estado_vehiculo") {
      if (typeof value === "string") {
        const lowerValue = value.toLowerCase()
        if (lowerValue === "vigente" || lowerValue === "activo") {
          classes += "text-success fw-bold"
        } else if (lowerValue === "vencido" || lowerValue === "inactivo") {
          classes += "text-danger fw-bold"
        } else if (lowerValue.includes("próximo") || lowerValue === "mantenimiento") {
          classes += "text-warning fw-bold"
        }
      }
    }

    if (key === "dias_restantes" && typeof value === "number") {
      if (value < 0) classes += "text-danger fw-bold"
      else if (value <= 30) classes += "text-warning fw-bold"
      else classes += "text-success"
    }

    return classes
  }

  animateTableRows() {
    const rows = document.querySelectorAll("#contenidoReporte tbody tr")
    rows.forEach((row, index) => {
      row.style.opacity = "0"
      row.style.transform = "translateX(-20px)"
      row.style.transition = "all 0.3s ease-out"

      setTimeout(() => {
        row.style.opacity = "1"
        row.style.transform = "translateX(0)"
      }, index * 50)
    })
  }

  handleFilterChange() {
    // Debounce para evitar múltiples llamadas
    clearTimeout(this.filterTimeout)
    this.filterTimeout = setTimeout(() => {
      this.applyFilters()
    }, 500)
  }

  applyFilters() {
    if (!this.currentReport) return

    // Recopilar filtros
    const filters = {}
    const inputs = document.querySelectorAll("#filtrosReporte input, #filtrosReporte select")

    inputs.forEach((input) => {
      if (input.value.trim()) {
        const key = input.id.replace("filtro_", "")
        filters[key] = input.value.trim()
      }
    })

    this.currentFilters = filters
    this.loadReportData(this.currentReport, filters)
  }

  clearFilters() {
    const inputs = document.querySelectorAll("#filtrosReporte input, #filtrosReporte select")
    inputs.forEach((input) => {
      input.value = ""
    })

    this.currentFilters = {}
    if (this.currentReport) {
      this.loadReportData(this.currentReport, {})
    }
  }

  refreshReport() {
    if (this.currentReport) {
      this.loadReportData(this.currentReport, this.currentFilters)
    }
  }

 exportReport(type, format, filters = {}) {
    // Convertir cada filtro en un parámetro individual
    const params = new URLSearchParams({
        exportar: "1",
        tipo: type,
        formato: format
    });

    // Agregar cada filtro como un parámetro separado
    Object.entries(filters).forEach(([key, value]) => {
        params.append(`filtros[${key}]`, value);
    });

    // Crear enlace temporal para descarga
    const link = document.createElement("a");
    link.href = `reportes.php?${params.toString()}`;
    link.target = "_blank";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    // Mostrar notificación
    this.showNotification(`Exportando reporte en formato ${format.toUpperCase()}...`, "info");
}

  exportCurrentReport(format) {
    if (!this.currentReport) return
    this.exportReport(this.currentReport, format, this.currentFilters)
  }

  resetModal() {
    this.currentReport = null
    this.currentFilters = {}
    this.isLoading = false

    const content = document.getElementById("contenidoReporte")
    if (content) {
      content.innerHTML = ""
    }
  }

  updateStatistics() {
    // Animar las estadísticas antes de recargar
    const statCards = document.querySelectorAll(".stat-card")
    statCards.forEach((card, index) => {
      setTimeout(() => {
        card.style.transform = "scale(0.95)"
        setTimeout(() => {
          card.style.transform = "scale(1)"
        }, 150)
      }, index * 100)
    })

    setTimeout(() => {
      location.reload()
    }, 1000)
  }

  showNotification(message, type = "info") {
    const alertClass = type === "success" ? "success" : type === "error" ? "error" : "info"
    const iconClass =
      type === "success"
        ? "bi-check-circle-fill"
        : type === "error"
          ? "bi-exclamation-triangle-fill"
          : "bi-info-circle-fill"

    const notification = document.createElement("div")
    notification.className = `notification alert ${alertClass} alert-dismissible fade show`
    notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi ${iconClass} me-2"></i>
                <div class="flex-grow-1">${message}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `

    document.body.appendChild(notification)

    // Auto-remove después de 5 segundos
    setTimeout(() => {
      if (notification.parentNode) {
        notification.classList.remove("show")
        setTimeout(() => {
          notification.remove()
        }, 150)
      }
    }, 5000)
  }
}

// Inicializar el manager cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", () => {
  window.reportsManager = new ReportsManager()
})

// Funciones globales para compatibilidad con el código existente
function abrirReporte(tipo) {
  if (window.reportsManager) {
    window.reportsManager.openReport(tipo)
  }
}

function exportarReporte(tipo, formato) {
  if (window.reportsManager) {
    window.reportsManager.exportReport(tipo, formato)
  }
}

function aplicarFiltrosReporte() {
  if (window.reportsManager) {
    window.reportsManager.applyFilters()
  }
}

function limpiarFiltrosReporte() {
  if (window.reportsManager) {
    window.reportsManager.clearFilters()
  }
}

function exportarReporteActual(formato) {
  if (window.reportsManager) {
    window.reportsManager.exportCurrentReport(formato)
  }
}

function actualizarEstadisticas() {
  if (window.reportsManager) {
    window.reportsManager.updateStatistics()
  }
}

// Función para cargar estados de vehículo dinámicamente
function cargarEstadosVehiculo() {
    $.ajax({
        url: 'reportes_ajax.php',
        type: 'POST',
        data: { accion: 'obtener_estados_vehiculo' },
        dataType: 'json',
        success: function(estados) {
            var select = $('#filtro_estado');
            select.empty();
            select.append('<option value="">Todos los estados</option>');
            estados.forEach(function(estado) {
                select.append('<option value="' + estado + '">' + estado + '</option>');
            });
        }
    });
}

// Llama esta función cuando cargue la página o el modal de filtros
$(document).ready(function() {
    cargarEstadosVehiculo();
});

