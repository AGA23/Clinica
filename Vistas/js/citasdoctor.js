// Archivo: Vistas/js/citas_doctor.js
// Lógica de JavaScript para el módulo de Agenda del Doctor.

$(document).ready(function() {

    // --- FUNCIÓN HELPER ---
    // Convierte un string de hora (ej: "14:30") a un total de minutos para una comparación numérica fiable.
    function timeToMinutes(timeStr) {
        if (typeof timeStr !== 'string' || !timeStr.includes(':')) {
            console.error("Formato de hora inválido para timeToMinutes:", timeStr);
            return 0;
        }
        const [hours, minutes] = timeStr.split(':');
        return parseInt(hours, 10) * 60 + parseInt(minutes, 10);
    }


    // --- LÓGICA INICIAL PARA ABRIR MODALES "FINALIZAR" Y "CANCELAR" ---
    // Pre-rellena el modal de cancelación con el ID de la cita.
    $(document).on('click', '.btnCancelarCita', function() {
        $('#modalCancelarCitaId').val($(this).data('id-cita'));
        $('#motivo_cancelacion').val(''); // Limpia el textarea
    });

    // Pre-rellena y resetea el modal de finalización con los datos de la cita.
    $(document).on('click', '.btnFinalizarCita', function() {
        const modal = $('#modalFinalizarCita');
        modal.find('#id_cita_finalizar').val($(this).data('id-cita'));
        modal.find('#motivo_finalizar').val($(this).data('motivo'));
        
        // Resetea los campos que se llenan manualmente
        modal.find('#observaciones_finalizar, #peso_finalizar').val('');
        
        // Resetea los selectores de Select2 y el contenedor de detalles
        if (modal.find('#medicamentos').hasClass("select2-hidden-accessible")) {
            modal.find('#medicamentos, #tratamientos').val(null).trigger('change');
        }
        $('#contenedor-detalles-medicamentos').empty();
    });


    // --- LÓGICA PARA SELECT2 Y DETALLES DE MEDICAMENTOS ---
    // Se ejecuta cada vez que el modal para finalizar cita se muestra.
    $('#modalFinalizarCita').on('shown.bs.modal', function () {
        const dropdownParent = $(this).find('.modal-content');
        const medicamentosSelect = $('#medicamentos');
        const tratamientosSelect = $('#tratamientos');
        
        // Destruye y reinicializa Select2 para evitar bugs
        if (medicamentosSelect.hasClass('select2-hidden-accessible')) { medicamentosSelect.select2('destroy'); }
        medicamentosSelect.select2({ placeholder: "Buscar y seleccionar medicamentos...", width: '100%', allowClear: true, dropdownParent: dropdownParent });
        
        if (tratamientosSelect.hasClass('select2-hidden-accessible')) { tratamientosSelect.select2('destroy'); }
        tratamientosSelect.select2({ placeholder: "Buscar y seleccionar tratamientos...", width: '100%', allowClear: true, dropdownParent: dropdownParent });
    });

    // Se ejecuta cuando el doctor selecciona un medicamento de la lista.
    $('#medicamentos').on('change', function(e) {
        const allSelectedIds = $(this).val() || [];
        const contenedor = $('#contenedor-detalles-medicamentos');
        
        const getNingunoId = () => {
            let ningunoId = null;
            $('#medicamentos option').each(function() {
                if ($(this).text().trim().toLowerCase() === 'ninguno') {
                    ningunoId = $(this).val();
                }
            });
            return ningunoId;
        };

        const ningunoId = getNingunoId();

        // Si "Ninguno" está seleccionado, se fuerza a que sea la única opción y no se muestran detalles.
        if (allSelectedIds.includes(ningunoId)) {
            if (e.originalEvent) { // Evita un bucle infinito de triggers
                $(this).val(ningunoId).trigger('change');
            }
            contenedor.empty();
            return; 
        }

        // Si no está "Ninguno", se procede a construir los campos de detalles.
        contenedor.empty();
        
        if (allSelectedIds.length > 0) {
            contenedor.append('<h5 style="font-weight: bold; margin-top: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Detalles de la Prescripción</h5>');
        }

        const hoyStr = new Date().toISOString().slice(0, 10);

        allSelectedIds.forEach(function(id) {
            const nombre = $('#medicamentos').find(`option[value="${id}"]`).text();
            const bloque = `
                <div class="well well-sm" style="background-color: #fdfdfd; border: 1px solid #e3e3e3; border-radius: 4px; padding: 10px; margin-top: 10px;">
                  <p style="font-weight: bold; margin-bottom: 10px;">${nombre}</p>
                  <div class="row">
                    <div class="form-group col-md-6" style="margin-bottom: 5px;"><label>Dosis</label><input type="text" name="detalles_medicamentos[${id}][dosis]" class="form-control input-sm" required></div>
                    <div class="form-group col-md-6" style="margin-bottom: 5px;"><label>Frecuencia</label><input type="text" name="detalles_medicamentos[${id}][frecuencia]" class="form-control input-sm" required></div>
                  </div>
                  <div class="row">
                    <div class="form-group col-md-6" style="margin-bottom: 5px;"><label>Desde</label><input type="date" name="detalles_medicamentos[${id}][fecha_inicio]" class="form-control input-sm fecha-inicio" min="${hoyStr}" required></div>
                    <div class="form-group col-md-6" style="margin-bottom: 5px;"><label>Hasta</label><input type="date" name="detalles_medicamentos[${id}][fecha_fin]" class="form-control input-sm fecha-fin" min="${hoyStr}" required></div>
                  </div>
                  <div class="form-group" style="margin-bottom: 5px;">
                    <label>Instrucciones Específicas (Ej: Tomar con abundante agua)</label>
                    <textarea name="detalles_medicamentos[${id}][observaciones]" class="form-control input-sm" rows="2" placeholder="Notas solo para este medicamento..."></textarea>
                  </div>
                </div>`;
            contenedor.append(bloque);
        });
    });
    
    // Valida que la fecha de fin de un medicamento no sea anterior a la de inicio.
    $('#contenedor-detalles-medicamentos').on('change', '.fecha-inicio', function() {
        const fechaInicio = $(this).val();
        const contenedor = $(this).closest('.well');
        const fechaFinInput = contenedor.find('.fecha-fin');
        if(fechaInicio) {
            fechaFinInput.attr('min', fechaInicio);
            if(fechaFinInput.val() < fechaInicio) { 
                fechaFinInput.val(fechaInicio); 
            }
        } else {
            const hoyStr = new Date().toISOString().slice(0, 10);
            fechaFinInput.attr('min', hoyStr);
        }
    });


    // --- LÓGICA PARA MODAL "CREAR CITA" (Cálculo de Horarios) ---
    if ($('#modalNuevaCita').length > 0) {
        // Obtenemos las variables PHP que la vista ha renderizado
        // Es necesario usar 'var' para que no tengan scope de bloque
        var horariosConsultorio = window.horariosConsultorio || [];
        var horariosDoctor = window.horariosDoctor || [];
        var consultorioId = window.consultorioId || null;

        const fechaInput = document.getElementById('fecha_crear');
        const referenciaDisponible = document.getElementById('horario_doctor_referencia');
        const horaInicioInput = document.getElementById('hora_inicio_crear');
        const horaFinInput = document.getElementById('hora_fin_crear');
        
        if (!consultorioId) {
            $('#modalNuevaCita .modal-body :input, #modalNuevaCita .modal-footer .btn-primary').prop('disabled', true);
            $('#modalNuevaCita').on('show.bs.modal', () => alert('No tienes un consultorio asignado. No puedes crear citas.'));
        } else {
            function actualizarHorario() {
                if (!fechaInput.value) {
                    referenciaDisponible.value = "Seleccione una fecha";
                    horaInicioInput.disabled = true; horaFinInput.disabled = true;
                    return;
                }
                
                let diaSemana = new Date(fechaInput.value + 'T00:00:00').getUTCDay();
                if (diaSemana === 0) diaSemana = 7;

                const horarioDoctorDia = horariosDoctor.find(h => parseInt(h.dia_semana) === diaSemana);
                const horarioConsultorioDia = horariosConsultorio.find(h => parseInt(h.dia_semana) === diaSemana);

                if (!horarioDoctorDia || !horarioConsultorioDia) {
                    referenciaDisponible.value = !horarioDoctorDia ? "Doctor no disponible este día." : "Consultorio cerrado este día.";
                    horaInicioInput.disabled = true; horaFinInput.disabled = true;
                    return;
                }

                let horaInicioStr = (horarioDoctorDia.hora_inicio > horarioConsultorioDia.hora_inicio) ? horarioDoctorDia.hora_inicio.slice(0, 5) : horarioConsultorioDia.hora_inicio.slice(0, 5);
                let horaFinStr = (horarioDoctorDia.hora_fin < horarioConsultorioDia.hora_fin) ? horarioDoctorDia.hora_fin.slice(0, 5) : horarioConsultorioDia.hora_fin.slice(0, 5);
                
                const hoy = new Date();
                if (fechaInput.value === hoy.toISOString().slice(0, 10)) {
                    const horaActualStr = hoy.toTimeString().slice(0, 5);
                    if (horaActualStr > horaInicioStr) {
                        horaInicioStr = horaActualStr;
                    }
                }

                if (timeToMinutes(horaInicioStr) >= timeToMinutes(horaFinStr)) {
                    referenciaDisponible.value = "Horario para hoy ya finalizado.";
                    horaInicioInput.disabled = true; horaFinInput.disabled = true;
                    return;
                }
                
                referenciaDisponible.value = `Disponible de ${horaInicioStr} a ${horaFinStr}`;
                horaInicioInput.min = horaInicioStr; horaInicioInput.max = horaFinStr; horaInicioInput.value = horaInicioStr;
                horaFinInput.min = horaInicioStr; horaFinInput.max = horaFinStr; horaFinInput.value = horaFinStr;
                horaInicioInput.disabled = false; horaFinInput.disabled = false;
            }

            if(fechaInput) fechaInput.addEventListener('change', actualizarHorario);
            if(horaInicioInput) horaInicioInput.addEventListener('change', function () {
                if (horaFinInput.value < horaInicioInput.value) horaFinInput.value = horaInicioInput.value;
                horaFinInput.min = horaInicioInput.value;
            });
        }
    }
});

// --- FUNCIÓN DE VALIDACIÓN PARA EL FORMULARIO DE FINALIZACIÓN ---
// Se declara fuera del $(document).ready para que sea una función global
// y pueda ser llamada por el atributo 'onsubmit' del formulario.
function validarFinalizacion() {
    const motivo = document.getElementById('motivo_finalizar');
    const observaciones = document.getElementById('observaciones_finalizar');
    const peso = document.getElementById('peso_finalizar');
    
    if (!motivo.value.trim() || !observaciones.value.trim()) {
        alert("Por favor, complete el Motivo Final y el Diagnóstico.");
        motivo.focus();
        return false;
    }
    if (!peso.value || isNaN(parseFloat(peso.value)) || parseFloat(peso.value) <= 0 || parseFloat(peso.value) > 500) {
        alert("El peso debe ser un número válido entre 0.1 y 500 kg.");
        peso.focus();
        return false;
    }
    return true;
}