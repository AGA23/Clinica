
$(function () {
    // 1. Inicializar Select2 en ambos modales
    try {
        $('#modal-nueva-cita-admin .select2').select2({
            dropdownParent: $('#modal-nueva-cita-admin'),
            width: '100%',
            placeholder: "Buscar por Nombre o DNI...",
            allowClear: true
        });
        $('#modal-editar-cita-admin .select2').select2({
            dropdownParent: $('#modal-editar-cita-admin'),
            width: '100%',
            placeholder: "Buscar por Nombre o DNI...",
            allowClear: true
        });
    } catch (e) {
        console.warn("Select2 no cargado o error de inicialización: " + e.message);
    }

    // ============================================================================
    // 🟢 LÓGICA DE COBERTURA (Afiliaciones del Paciente)
    // ============================================================================
    /**
     * Carga las afiliaciones del paciente en el selector de tipo de pago.
     * @param {jQuery} form - El formulario actual (nueva cita o editar cita).
     * @param {string} idPaciente - ID del paciente seleccionado.
     * @param {string} idTipoPagoPre - ID del tipo de pago a seleccionar (para edición).
     * @param {string} idCobPre - ID de la cobertura específica a seleccionar (para edición).
     */
    function actualizarAfiliacionesAdmin(form, idPaciente, idTipoPagoPre, idCobPre) {
        var selectAfiliacion = form.find('.select-tipo-pago-admin');
        var inputHidden = form.find('.input-cobertura-aplicada-admin');

        selectAfiliacion.html('<option value="">Cargando...</option>').prop('disabled', true);
        inputHidden.val('');

        if (idPaciente) {
            $.post(BASE_URL + 'ajax/citasA.php', {
                action: 'obtenerAfiliacionesPaciente',
                id_paciente: idPaciente
            })
            .done(function (r) {
                if (typeof r === 'string') r = JSON.parse(r);
                selectAfiliacion.empty().append('<option value="">Seleccione...</option>');

                // 1. Opción Fija: Particular (Generalmente ID=1 para tipo_pago particular)
                selectAfiliacion.append('<option value="1" data-cobertura="">PARTICULAR (Pago Directo)</option>');

                // 2. Obras Sociales del Paciente
                if (r.success && r.afiliaciones && r.afiliaciones.length > 0) {
                    r.afiliaciones.forEach(a => {
                        var texto = `${a.nombre_os} [Plan: ${a.plan || 'S/D'}] (Nro: ${a.numero_afiliado})`;
                        // El value es el id_obra_social (tipo de pago), data-cobertura es la afiliación específica
                        selectAfiliacion.append(`<option value="${a.id_obra_social}" data-cobertura="${a.id}">${texto}</option>`);
                    });
                     // 🔄 RECARGAR EVENTOS DEL CALENDARIO
      $('#calendario-global-admin').fullCalendar('refetchEvents');
                }

                selectAfiliacion.prop('disabled', false);

                // 3. Pre-selección (Edición)
                if (idTipoPagoPre) {
                    // Buscar la opción que coincida con tipo_pago Y cobertura
                    var option = selectAfiliacion.find(`option[value='${idTipoPagoPre}']`).filter(function () {
                        // Para Particular (idTipoPagoPre=1), idCobPre es null/''/0. Para OS debe coincidir el idCobPre.
                        var currentCobId = $(this).data('cobertura') ? String($(this).data('cobertura')) : '';
                        var targetCobId = idCobPre ? String(idCobPre) : '';
                        
                        if (idTipoPagoPre === '1') { // Es particular
                            return currentCobId === '';
                        }
                        
                        // Es obra social
                        return currentCobId === targetCobId; 
                    });

                    if (option.length > 0) {
                        option.prop('selected', true);
                        inputHidden.val(option.data('cobertura') || '');
                    }
                }
            })
            .fail(function() {
                selectAfiliacion.html('<option value="">Error al cargar afiliaciones</option>');
            });
        } else {
            selectAfiliacion.html('<option value="">Seleccione paciente primero</option>');
        }
    }

    // EVENTOS
    // 1. Cambio de Paciente
    $('.select-paciente-admin').on('change select2:select', function () {
        var form = $(this).closest('form');
        form.find('.select-tipo-pago-admin').val('');
        actualizarAfiliacionesAdmin(form, $(this).val(), null, null);
    });

    // 2. Cambio Tipo Pago (Actualizar input hidden de cobertura)
    $(document).on('change', '.select-tipo-pago-admin', function (e, idCoberturaEspecifica) {
        var selected = $(this).find('option:selected');
        var idCobertura = selected.data('cobertura');
        var form = $(this).closest('form');
        form.find('.input-cobertura-aplicada-admin').val(idCobertura || '');
    });

    // ============================================================================
    // FILTROS Y FULLCALENDAR
    // ============================================================================
    
    // --- LÓGICA DE FILTROS ---
    $('#filtro-consultorio').on('change', function () {
        $('#calendario-global-admin').fullCalendar('refetchEvents');
        var idConsultorio = $(this).val();
        var selectDoctor = $('#filtro-doctor');
        selectDoctor.html('<option value="">Cargando...</option>').prop('disabled', true).val('');
        
        if (idConsultorio) {
            $.post(BASE_URL + 'ajax/citasA.php', {
                action: 'obtenerDoctoresPorConsultorio',
                id_consultorio: idConsultorio
            })
            .done(function (r) {
                if (typeof r === 'string') r = JSON.parse(r);
                selectDoctor.empty().append('<option value="">-- Todos los Doctores --</option>');
                if (r.success && r.doctores) r.doctores.forEach(d => selectDoctor.append(`<option value="${d.id}">${d.nombre} ${d.apellido}</option>`));
                selectDoctor.prop('disabled', false);
                 // 🔄 RECARGAR EVENTOS DEL CALENDARIO
      $('#calendario-global-admin').fullCalendar('refetchEvents');
            });
        } else {
            selectDoctor.empty().append('<option value="">-- Primero seleccione un consultorio --</option>');
        }
    });

    $('#filtro-doctor').on('change', function () {
        $('#calendario-global-admin').fullCalendar('refetchEvents');
    });
    
    $('#limpiar-filtros').on('click', function () {
        $('#filtro-consultorio').val('').trigger('change');
    });

    // --- FULLCALENDAR INICIALIZACIÓN ---
    $('#calendario-global-admin').fullCalendar({
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        buttonText: {
            today: 'hoy',
            month: 'mes',
            week: 'semana',
            day: 'día'
        },
        locale: 'es',
        events: {
            url: BASE_URL + 'ajax/citasA.php',
            type: 'GET',
            data: function () {
                return {
                    action: 'obtenerCitasAdmin',
                    id_consultorio: $('#filtro-consultorio').val(),
                    id_doctor: $('#filtro-doctor').val()
                };
            },
            error: function() {
                console.error("Error al cargar eventos del calendario.");
            }
        },
        // Renderizado personalizado de eventos
        eventRender: function (event, element) {
            var tipo = event.tipo || (event.extendedProps ? event.extendedProps.tipo : '');
            if (tipo === 'bloqueo') {
                var doc = event.doctor_nombre || (event.extendedProps ? event.extendedProps.doctor_nombre : '');
                var mot = event.motivo || (event.extendedProps ? event.extendedProps.motivo : '');
                var html = '<div><strong>Bloqueo Dr. ' + doc + '</strong><br>' + mot + '</div>';
                element.find('.fc-title').html(html);
                element.find('.fc-time').hide();
                element.css('background-color', '#ff4d4d').css('border-color', '#ff1a1a');
            } else {
                var pac = event.paciente || (event.extendedProps ? event.extendedProps.paciente : '');
                var mot = event.motivo || (event.extendedProps ? event.extendedProps.motivo : '');
                // Formato de hora de inicio
                var horaInicio = moment(event.start).format('h:mm A');
                var desc = horaInicio + ' - ' + pac + '<br><b>' + mot + '</b>';
                element.find('.fc-title').html(desc);
                // Si estás en vista de día o semana, el tiempo ya se muestra, solo ajustamos el título
                if(element.find('.fc-time').length) { element.find('.fc-title').css('margin-left', '0'); }
            }
        },
        // Clic en el día (para crear cita)
        dayClick: function (date, jsEvent, view) {
            var form = $('#modal-nueva-cita-admin form');
            form[0].reset();
            $('#modal-nueva-cita-admin .input-fecha-admin').val(date.format('YYYY-MM-DD'));
            $('#modal-nueva-cita-admin .select-tipo-pago-admin').html('<option value="">Seleccione paciente primero...</option>').prop('disabled', true);
            
            // Reset Select2 y otros selectores
            $('#modal-nueva-cita-admin .select2').val(null).trigger('change');
            var idCon = $('#modal-nueva-cita-admin .select-consultorio-admin').val();
            actualizarSelectoresPorConsultorio(form, idCon);
            
            $('#modal-nueva-cita-admin').modal('show');
        },
        // Clic en el evento (para ver detalles)
        eventClick: function (calEvent, jsEvent, view) {
            var tipo = calEvent.tipo || (calEvent.extendedProps ? calEvent.extendedProps.tipo : '');
            if (tipo === 'bloqueo') {
                Swal.fire('Horario Bloqueado', calEvent.motivo, 'info');
                return;
            }

           // Mapear propiedades desde extendedProps SIEMPRE
var p_paciente    = calEvent.extendedProps ? calEvent.extendedProps.paciente    : '';
var p_doctor      = calEvent.extendedProps ? calEvent.extendedProps.doctor      : '';
var p_consultorio = calEvent.extendedProps ? calEvent.extendedProps.consultorio : '';
var p_motivo      = calEvent.extendedProps ? calEvent.extendedProps.motivo      : '';
var p_estado      = calEvent.extendedProps ? calEvent.extendedProps.estado      : '';

// Rellenar Modal Detalle
$('.btn-editar-cita-admin, .btn-cancelar-cita-admin').data('id-cita', calEvent.id);

$('#detalle-paciente-admin').text(p_paciente);
$('#detalle-doctor-admin').text(p_doctor);
$('#detalle-consultorio-admin').text(p_consultorio);
$('#detalle-fecha-admin').text(moment(calEvent.start).format('DD/MM/YYYY [a las] h:mm A'));
$('#detalle-motivo-admin').text(p_motivo);

$('#detalle-estado-admin').html(
    '<span class="label" style="background-color:' + calEvent.color + '; color:white;">' 
    + p_estado + 
    '</span>'
);


            // Cargar Cobertura por separado
            $('#detalle-cobertura-admin').text('Cargando...');
            $.post(BASE_URL + 'ajax/citasA.php', {
                action: 'obtenerNombreCobertura',
                id_cita: calEvent.id
            })
            .done(function (r) {
                if (typeof r === 'string') r = JSON.parse(r);
                if (r.success) {
                    $('#detalle-cobertura-admin').text(r.nombre);
                } else {
                    $('#detalle-cobertura-admin').text('Particular / Sin especificar');
                }
                 // 🔄 RECARGAR EVENTOS DEL CALENDARIO
      $('#calendario-global-admin').fullCalendar('refetchEvents');
            });

            // Mostrar/Ocultar botones de acción según el estado
            if (p_estado !== 'Pendiente') {
                $('.btn-editar-cita-admin, .btn-cancelar-cita-admin').hide();
            } else {
                $('.btn-editar-cita-admin, .btn-cancelar-cita-admin').show();
            }
            $('#modal-detalle-cita-admin').modal('show');
        }
    });

    // ============================================================================
    // FUNCIONES DE SELECCIÓN EN MODALES (Doctores, Tratamientos, Horarios)
    // ============================================================================

    /**
     * Carga doctores y tratamientos en base al consultorio seleccionado.
     */
    function actualizarSelectoresPorConsultorio(form, idConsultorio, idDoctorSeleccionar, idTratamientoSeleccionar) {
        var selectDoctores = form.find('.select-doctor-admin');
        var selectTratamientos = form.find('.select-tratamiento-admin');
        selectDoctores.html('<option value="">Cargando...</option>').prop('disabled', true);
        selectTratamientos.html('<option value="">Cargando...</option>').prop('disabled', true);
        form.find('.select-hora-inicio-admin').empty().prop('disabled', true).val('');
        form.find('.input-hora-fin-admin').val('');

        if (idConsultorio) {
            // Cargar Doctores
            $.post(BASE_URL + 'ajax/citasA.php', {
                action: 'obtenerDoctoresPorConsultorio',
                id_consultorio: idConsultorio
            })
            .done(function (r) {
                if (typeof r === 'string') r = JSON.parse(r);
                selectDoctores.empty().append('<option value="">Seleccione Doctor...</option>');
                if (r.success && r.doctores) {
                    r.doctores.forEach(d => selectDoctores.append(`<option value="${d.id}">${d.nombre} ${d.apellido}</option>`));
                }
                selectDoctores.prop('disabled', false);
                if (idDoctorSeleccionar) {
                    selectDoctores.val(idDoctorSeleccionar).trigger('change');
                }
                 // 🔄 RECARGAR EVENTOS DEL CALENDARIO
      $('#calendario-global-admin').fullCalendar('refetchEvents');
            });

            // Cargar Tratamientos
            $.post(BASE_URL + 'ajax/citasA.php', {
                action: 'obtenerTratamientosPorConsultorio',
                id_consultorio: idConsultorio
            })
            .done(function (r) {
                if (typeof r === 'string') r = JSON.parse(r);
                selectTratamientos.empty().append('<option value="">Seleccione Tratamiento...</option>');
                if (r.success && r.tratamientos) {
                    r.tratamientos.forEach(t => selectTratamientos.append(`<option value="${t.id}">${t.nombre}</option>`));
                }
                selectTratamientos.prop('disabled', false);
                if (idTratamientoSeleccionar) {
                    selectTratamientos.val(idTratamientoSeleccionar);
                }
                 // 🔄 RECARGAR EVENTOS DEL CALENDARIO
      $('#calendario-global-admin').fullCalendar('refetchEvents');
            });
        } else {
            selectDoctores.html('<option>Seleccione un consultorio</option>');
            selectTratamientos.html('<option>Seleccione un consultorio</option>');
        }
    }

    /**
     * Carga los horarios disponibles para el doctor y fecha seleccionados.
     */
    function actualizarHorarios(form, horaSeleccionar) {
        var idDoctor = form.find('.select-doctor-admin').val();
        var fecha = form.find('.input-fecha-admin').val();
        var selectHorarios = form.find('.select-hora-inicio-admin');
        selectHorarios.html('<option>Cargando...</option>').prop('disabled', true);
        form.find('.input-hora-fin-admin').val('');

        if (idDoctor && fecha) {
            $.post(BASE_URL + 'ajax/citasA.php', {
                action: 'obtenerHorariosDisponibles',
                id_doctor: idDoctor,
                fecha: fecha
            })
            .done(function (r) {
                if (typeof r === 'string') r = JSON.parse(r);
                selectHorarios.empty().append('<option value="">Seleccione...</option>');
                if (r.success && r.horarios && r.horarios.length > 0) {
                    r.horarios.forEach(h => selectHorarios.append(`<option value="${h}">${h}</option>`));
                    selectHorarios.prop('disabled', false);
                    if (horaSeleccionar) {
                        // Si la hora original no está en los disponibles, la añadimos como opción para que se vea
                        if (selectHorarios.find('option[value="' + horaSeleccionar + '"]').length === 0) {
                            selectHorarios.append(`<option value="${horaSeleccionar}">${horaSeleccionar} (Original)</option>`);
                        }
                        selectHorarios.val(horaSeleccionar).trigger('change');
                    }
                } else {
                    selectHorarios.append('<option value="">No hay horarios disponibles</option>');
                }
                 // 🔄 RECARGAR EVENTOS DEL CALENDARIO
      $('#calendario-global-admin').fullCalendar('refetchEvents');
            })
            .fail(function() {
                selectHorarios.html('<option value="">Error al cargar horarios</option>');
            });
        } else {
            selectHorarios.html('<option value="">Seleccione doctor y fecha</option>');
        }
    }

    // --- EVENTOS DE MODALES ---

    // 1. Calcular Hora Fin
    $('body').on('change', '.select-hora-inicio-admin', function () {
        var horaInicio = $(this).val();
        var form = $(this).closest('form');
        if (horaInicio) {
            var [horas, minutos] = horaInicio.split(':');
            var fecha = new Date();
            fecha.setHours(parseInt(horas), parseInt(minutos), 0, 0);
            fecha.setMinutes(fecha.getMinutes() + 30); // Duración fija de 30 minutos
            var horaFin = ('0' + fecha.getHours()).slice(-2) + ':' + ('0' + fecha.getMinutes()).slice(-2);
            form.find('.input-hora-fin-admin').val(horaFin);
        } else {
            form.find('.input-hora-fin-admin').val('');
        }
    });

    // 2. Eventos de dependencia para Consultorio -> Doctor/Tratamiento
    $('#modal-nueva-cita-admin, #modal-editar-cita-admin').on('change', '.select-consultorio-admin', function () {
        // En el modal de edición, el consultorio_display es el selector visible
        var idConsultorio = $(this).val();
        // Sincronizar el input hidden de consultorio para el submit de edición
        if ($(this).attr('id') === 'consultorio_display_editar_admin') {
            $('#id_consultorio_editar_admin').val(idConsultorio);
        }
        actualizarSelectoresPorConsultorio($(this).closest('form'), idConsultorio);
    });
    
    // 3. Eventos de dependencia para Doctor/Fecha -> Horarios
    $('#modal-nueva-cita-admin, #modal-editar-cita-admin').on('change', '.select-doctor-admin, .input-fecha-admin', function () {
        actualizarHorarios($(this).closest('form'));
    });

    // ============================================================================
    // LÓGICA DE EDICIÓN Y CANCELACIÓN
    // ============================================================================

    // --- BOTÓN EDITAR CITA ---
    $('.btn-editar-cita-admin').on('click', function () {
        var idCita = $(this).data('id-cita');
        $.post(BASE_URL + 'ajax/citasA.php', {
            action: 'obtenerDetallesCita',
            id_cita: idCita
        })
        .done(function (r) {
            if (typeof r === 'string') r = JSON.parse(r);
            if (r.success) {
                var cita = r.datos;
                var modal = $('#modal-editar-cita-admin');
                var form = modal.find('form');

                // Rellenar campos estáticos/hidden
                modal.find('#id_cita_editar_admin').val(cita.id);
                modal.find('#id_consultorio_editar_admin').val(cita.id_consultorio); // Hidden real
                modal.find('#consultorio_display_editar_admin').val(cita.id_consultorio); // Selector display

                // Rellenar Paciente y trigger Select2
                modal.find('#id_paciente_editar_admin').val(cita.id_paciente).trigger('change');
                modal.find('#fecha_editar_admin').val(moment(cita.inicio).format('YYYY-MM-DD'));
                
                // 1. Cargar Doctores y Tratamientos
                // Luego cargar horarios (se dispara con el trigger('change') interno de actualizarSelectores)
                // Se pasa la hora inicial de la cita para que se incluya en los horarios disponibles
                actualizarSelectoresPorConsultorio(form, cita.id_consultorio, cita.id_doctor, cita.id_tratamiento);
                
                // 2. Cargar Afiliaciones (Tipo de Pago y Cobertura)
                // Usamos un pequeño retraso para asegurar que el Select2 ya cargó las afiliaciones
                setTimeout(function() {
                    actualizarAfiliacionesAdmin(form, cita.id_paciente, cita.id_tipo_pago, cita.id_cobertura_aplicada);
                }, 100); 

                // 3. Cargar Horarios (Necesita que el doctor esté cargado, por eso se hace con delay)
                setTimeout(function() {
                    actualizarHorarios(form, moment(cita.inicio).format('HH:mm'));
                }, 200);


                $('#modal-detalle-cita-admin').modal('hide');
                modal.modal('show');

            } else {
                Swal.fire('Error', r.error || 'No se pudo cargar los detalles de la cita.', 'error');
            }
             // 🔄 RECARGAR EVENTOS DEL CALENDARIO
      $('#calendario-global-admin').fullCalendar('refetchEvents');
        }).fail(function () {
            Swal.fire('Error', 'No se pudo contactar al servidor para cargar los detalles.', 'error');
        });
    });

    // --- BOTÓN CANCELAR CITA (Directo con SweetAlert) ---
    $('.btn-cancelar-cita-admin').on('click', function () {
        var idCita = $(this).data('id-cita');
        
        Swal.fire({
            title: '¿Cancelar Cita?',
            text: 'Esta acción no se puede deshacer y marcará la cita como cancelada.',
            icon: 'warning',
            input: 'textarea',
            inputLabel: 'Motivo de cancelación (obligatorio)',
            inputPlaceholder: 'Ingrese el motivo aquí...',
            showCancelButton: true,
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No, mantener',
            inputValidator: (value) => {
                if (!value) {
                    return 'Necesitas ingresar un motivo de cancelación';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                var motivo = result.value;
                $.post(BASE_URL + 'ajax/citasA.php', {
                    action: 'cancelarCita',
                    id_cita: idCita,
                    motivo: motivo // Pasamos el motivo de cancelación
                })
                .done(r => {
                    if (typeof r === 'string') r = JSON.parse(r);
                    if (r.success) {
                        Swal.fire('¡Cancelada!', 'La cita ha sido cancelada.', 'success');
                        $('#calendario-global-admin').fullCalendar('refetchEvents');
                        $('#modal-detalle-cita-admin').modal('hide');
                    } else {
                        Swal.fire('Error', r.error || 'Ocurrió un error al cancelar la cita.', 'error');
                    }
                     // 🔄 RECARGAR EVENTOS DEL CALENDARIO
      $('#calendario-global-admin').fullCalendar('refetchEvents');
                })
                .fail(() => {
                    Swal.fire('Error', 'No se pudo contactar al servidor para cancelar la cita.', 'error');
                });
            }
        });
    });
});