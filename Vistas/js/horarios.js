// Vistas/js/horarios.js

$(document).ready(function() {
    // Gestionar horarios en formularios
    $(document).on('click', '#btnAgregarHorario', function() {
        var nuevaFila = `
        <div class="row fila-horario mb-2" data-id="new">
            <div class="col-md-3">
                <select class="form-control dia-semana" required>
                    <option value="">Seleccione día</option>
                    <option value="1">Lunes</option>
                    <option value="2">Martes</option>
                    <option value="3">Miércoles</option>
                    <option value="4">Jueves</option>
                    <option value="5">Viernes</option>
                    <option value="6">Sábado</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="time" class="form-control hora-inicio" required>
            </div>
            <div class="col-md-3">
                <input type="time" class="form-control hora-fin" required>
            </div>
            <div class="col-md-3">
                <button class="btn btn-danger btn-eliminar-horario">
                    <i class="fa fa-trash"></i> Eliminar
                </button>
            </div>
        </div>`;
        $('#contenedorHorarios').append(nuevaFila);
    });

    // Eliminar horario
    $(document).on('click', '.btn-eliminar-horario', function() {
        $(this).closest('.fila-horario').remove();
    });

    // Cargar horarios existentes al abrir modal
    $(document).on('click', '.btn-horarios', function() {
        var idDoctor = $(this).data('id');
        var nombreDoctor = $(this).data('nombre');
        
        $('#nombreDoctorHorario').text(nombreDoctor);
        $('#modalHorariosDoctor').data('id-doctor', idDoctor);
        
        $.ajax({
            url: '/clinica/Controladores/DoctoresC.php?action=GestionarHorarios',
            type: 'POST',
            data: {
                accion: 'obtener',
                id_doctor: idDoctor
            },
            success: function(horarios) {
                var html = '';
                if (horarios.length > 0) {
                    horarios.forEach(function(horario) {
                        html += `
                        <div class="row fila-horario mb-2" data-id="${horario.id}">
                            <div class="col-md-3">
                                <select class="form-control dia-semana" required>
                                    <option value="1" ${horario.dia_semana==1?'selected':''}>Lunes</option>
                                    <option value="2" ${horario.dia_semana==2?'selected':''}>Martes</option>
                                    <option value="3" ${horario.dia_semana==3?'selected':''}>Miércoles</option>
                                    <option value="4" ${horario.dia_semana==4?'selected':''}>Jueves</option>
                                    <option value="5" ${horario.dia_semana==5?'selected':''}>Viernes</option>
                                    <option value="6" ${horario.dia_semana==6?'selected':''}>Sábado</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="time" class="form-control hora-inicio" value="${horario.hora_inicio}" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" class="form-control hora-fin" value="${horario.hora_fin}" required>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-danger btn-eliminar-horario">
                                    <i class="fa fa-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>`;
                    });
                } else {
                    html = '<div class="alert alert-info">No hay horarios asignados</div>';
                }
                $('#contenedorHorarios').html(html);
            }
        });
    });

    // Guardar horarios
    $('#btnGuardarHorarios').click(function() {
        var idDoctor = $('#modalHorariosDoctor').data('id-doctor');
        var horarios = [];
        var valid = true;
        
        $('.fila-horario').each(function() {
            var $fila = $(this);
            var dia = $fila.find('.dia-semana').val();
            var inicio = $fila.find('.hora-inicio').val();
            var fin = $fila.find('.hora-fin').val();
            
            if (!dia || !inicio || !fin) {
                valid = false;
                return false; // Salir del each
            }
            
            horarios.push({
                id: $fila.data('id'),
                dia_semana: dia,
                hora_inicio: inicio,
                hora_fin: fin,
                id_consultorio: 1 // Obtener del formulario principal
            });
        });
        
        if (!valid) {
            alert('Por favor complete todos los campos de horario');
            return;
        }
        
        if (horarios.length === 0) {
            if (!confirm('¿Desea dejar al doctor sin horarios asignados?')) {
                return;
            }
        }
        
        $.ajax({
            url: '/clinica/Controladores/DoctoresC.php?action=GestionarHorarios',
            type: 'POST',
            data: {
                accion: 'guardar',
                id_doctor: idDoctor,
                horarios: JSON.stringify(horarios)
            },
            success: function(response) {
                if (response.success) {
                    $('#modalHorariosDoctor').modal('hide');
                    location.reload(); // Recargar para ver cambios
                } else {
                    alert('Error al guardar los horarios');
                }
            }
        });
    });
});

$(document).ready(function() {
    // Asumiendo que tu input de fecha tiene id="fechaCita"
    var $fechaInput = $('#fechaCita');
    
    // Calcular fechas mínimas y máximas
    var hoy = new Date();
    var hoyStr = hoy.toISOString().split('T')[0];
    $fechaInput.attr('min', hoyStr);
    
    // Fecha máxima = hoy + 6 meses
    var seisMesesDespues = new Date(hoy);
    seisMesesDespues.setMonth(seisMesesDespues.getMonth() + 6);
    var maxFechaStr = seisMesesDespues.toISOString().split('T')[0];
    $fechaInput.attr('max', maxFechaStr);
    
    // Validar que la fecha seleccionada esté dentro del rango
    $fechaInput.on('change', function() {
        var fechaSeleccionada = new Date(this.value);
        if (fechaSeleccionada < hoy) {
            alert("No puede seleccionar una fecha anterior a hoy");
            this.value = '';
        } else if (fechaSeleccionada > seisMesesDespues) {
            alert("No puede seleccionar una fecha superior a 6 meses desde hoy");
            this.value = '';
        }
    });
});
