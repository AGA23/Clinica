<?php
// ==============================================================================
// 1. REPARACIÓN CRÍTICA DE SESIÓN (Debe ir antes de cualquier procesamiento)
// ==============================================================================
if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Secretario' && empty($_SESSION['id_consultorio'])) {
    if (class_exists('SecretariosC')) {
        $todos = SecretariosC::VerSecretariosC(null, null);
        if (is_array($todos)) {
            foreach ($todos as $s) {
                if (isset($s['id']) && $s['id'] == $_SESSION['id']) {
                    $_SESSION['id_consultorio'] = $s['id_consultorio'];
                    break;
                }
            }
        }
    }
}

$id_consultorio_sesion = $_SESSION['id_consultorio'] ?? null;

// ==============================================================================
// 2. PROCESAMIENTO DE FORMULARIOS (CONTROLADORES)
// ==============================================================================
if (isset($_POST['crear_cita_secretario'])) { (new CitasC())->CrearCitaSecretarioC(); }
if (isset($_POST['editar_cita_secretario'])) { (new CitasC())->ActualizarCitaC(); }

$pacientes_agenda = PacientesC::ListarPacientesC() ?: [];
?>

<!-- ==============================================================================
     3. ESTILOS CSS (Para agrandar eventos y detalles estilo Admin)
     ============================================================================== -->
<style>
    /* Cuadro del evento más grande y con sombra */
    .fc-event {
        border: none !important;
        border-left: 5px solid rgba(0,0,0,0.2) !important;
        border-radius: 4px !important;
        padding: 6px !important;
        box-shadow: 0 3px 6px rgba(0,0,0,0.16);
        min-height: 65px !important; 
        margin-bottom: 4px !important;
    }
    /* Títulos y tiempos destacados */
    .fc-time { font-weight: bold !important; font-size: 13px !important; display: block; margin-bottom: 2px; }
    .fc-title { font-weight: bold !important; font-size: 14px !important; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    
    /* Caja de detalles internos del evento */
    .event-details-box {
        margin-top: 5px;
        font-size: 11.5px;
        line-height: 1.3;
        border-top: 1px solid rgba(255,255,255,0.4);
        padding-top: 4px;
        color: #fff;
    }
    .event-details-box i { width: 15px; text-align: center; margin-right: 3px; }
    
    /* Ajuste para que el calendario no se vea apretado */
    .fc-day-grid-event { margin: 2px 5px !important; }
</style>

<!-- VISTA HTML -->
<section class="content-header">
    <h1>Calendario de Citas <small>Panel Secretario</small></h1>
    <ol class="breadcrumb">
        <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
        <li class="active">Calendario de Citas</li>
    </ol>
</section>

<section class="content">
    <?php if (isset($_SESSION['mensaje_calendario'])): ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['tipo_mensaje_calendario']) ?> alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            <?= htmlspecialchars($_SESSION['mensaje_calendario']) ?>
        </div>
        <?php unset($_SESSION['mensaje_calendario'], $_SESSION['tipo_mensaje_calendario']); endif; ?>

    <?php if(empty($id_consultorio_sesion)): ?>
        <div class="alert alert-danger">
            <h4><i class="icon fa fa-ban"></i> Error de Sesión</h4>
            No se detectó su consultorio. Intente cerrar sesión y volver a entrar.
        </div>
    <?php endif; ?>

    <div class="box box-primary">
        <div class="box-header with-border">
            <button class="btn btn-primary" data-toggle="modal" data-target="#modal-nueva-cita">
                <i class="fa fa-plus"></i> Nueva Cita
            </button>
        </div>
        <div class="box-body no-padding">
            <div id="calendario-citas-secretario"></div>
        </div>
    </div>
</section>

<!-- ==============================================================================
     4. MODAL DETALLE CITA
     ============================================================================== -->
<div class="modal fade" id="modal-detalle-cita">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h4 class="modal-title">Detalles de la Cita</h4></div>
            <div class="modal-body">
                <dl class="dl-horizontal">
                    <dt>Paciente:</dt><dd id="detalle-paciente"></dd>
                    <dt>Doctor:</dt><dd id="detalle-doctor"></dd>
                    <dt>Consultorio:</dt><dd id="detalle-consultorio"></dd>
                    <dt>Fecha:</dt><dd id="detalle-fecha"></dd>
                    <dt>Motivo:</dt><dd id="detalle-motivo"></dd>
                    <dt>Cobertura:</dt><dd id="detalle-cobertura">Cargando...</dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-warning btn-editar-cita">Editar</button>
                <button type="button" class="btn btn-danger btn-cancelar-cita">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<!-- ==============================================================================
     5. MODAL NUEVA CITA
     ============================================================================== -->
<div class="modal fade" id="modal-nueva-cita">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post">
            <input type="hidden" name="redirect_url" value="calendario-citas">
            <input type="hidden" name="id_consultorio" value="<?= $id_consultorio_sesion ?>">
            
            <div class="modal-header"><h4 class="modal-title">Agendar Nueva Cita</h4></div>
            <div class="modal-body">
                <div class="form-group"><label>Doctor:</label>
                    <select name="id_doctor" class="form-control select-doctor" required disabled><option>Cargando...</option></select>
                </div>
                <div class="form-group"><label>Tratamiento:</label>
                    <select name="id_tratamiento" class="form-control select-tratamiento" required disabled><option>Cargando...</option></select>
                </div>
                <div class="form-group"><label>Paciente:</label>
                    <select name="id_paciente" class="form-control select-paciente-nueva-cita select2" style="width: 100%;" required>
                        <option value="">Buscar Paciente...</option>
                        <?php foreach($pacientes_agenda as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= $p['apellido'].' '.$p['nombre'].' - '.$p['numero_documento'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Cobertura:</label>
                    <select name="id_tipo_pago" class="form-control select-tipo-pago" required disabled><option>Seleccione paciente...</option></select>
                    <input type="hidden" name="id_cobertura_aplicada" class="input-cobertura-aplicada">
                </div>
                <div class="row">
                    <div class="col-md-5"><div class="form-group"><label>Fecha:</label>
                        <input type="date" name="fecha" class="form-control input-fecha" required min="<?= date('Y-m-d') ?>"></div>
                    </div>
                    <div class="col-md-4"><div class="form-group"><label>Hora Inicio:</label>
                        <select name="hora_inicio" class="form-control select-hora-inicio" required disabled></select></div>
                    </div>
                    <div class="col-md-3"><div class="form-group"><label>Hora Fin:</label>
                        <input type="time" name="hora_fin" class="form-control input-hora-fin" readonly></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary" name="crear_cita_secretario">Guardar Cita</button></div>
        </form>
    </div></div>
</div>

<!-- ==============================================================================
     6. MODAL EDITAR CITA
     ============================================================================== -->
<div class="modal fade" id="modal-editar-cita">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post">
            <div class="modal-header"><h4 class="modal-title">Editar Cita</h4></div>
            <div class="modal-body">
                <input type="hidden" name="id_cita_editar" id="id_cita_editar">
                <input type="hidden" name="id_consultorio" value="<?= $id_consultorio_sesion ?>">
                
                <div class="form-group"><label>Doctor:</label>
                    <select name="id_doctor_editar" id="id_doctor_editar" class="form-control select-doctor" required></select>
                </div>
                <div class="form-group"><label>Tratamiento:</label>
                    <select name="id_tratamiento_editar" id="id_tratamiento_editar" class="form-control select-tratamiento" required></select>
                </div>
                <div class="form-group"><label>Paciente:</label>
                    <input type="text" id="paciente_editar_nombre" class="form-control" readonly>
                    <input type="hidden" name="id_paciente_editar" id="id_paciente_editar">
                </div>
                <div class="form-group"><label>Cobertura:</label>
                    <select name="id_tipo_pago_editar" id="id_tipo_pago_editar" class="form-control select-tipo-pago" required></select>
                    <input type="hidden" name="id_cobertura_aplicada_editar" id="id_cobertura_aplicada_editar" class="input-cobertura-aplicada">
                </div>
                <div class="row">
                    <div class="col-md-5"><div class="form-group"><label>Fecha:</label>
                        <input type="date" name="fecha_editar" id="fecha_editar" class="form-control input-fecha" required></div>
                    </div>
                    <div class="col-md-4"><div class="form-group"><label>Hora:</label>
                        <select name="hora_inicio_editar" id="hora_inicio_editar" class="form-control select-hora-inicio" required></select></div>
                    </div>
                    <div class="col-md-3"><div class="form-group"><label>Fin:</label>
                        <input type="time" name="hora_fin_editar" id="hora_fin_editar" class="form-control input-hora-fin" readonly></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary" name="editar_cita_secretario">Guardar Cambios</button></div>
        </form>
    </div></div>
</div>

<?php 
ob_start(); 
?>
<script>
window.addEventListener('load', function() {
    if (window.jQuery) {
        $(function () {
            const ID_CONSULTORIO_SEC = '<?php echo $id_consultorio_sesion; ?>';

            function safeParse(r) {
                if (typeof r === 'string') { try { return JSON.parse(r); } catch (e) { return r; } }
                return r; 
            }

            // 1. SELECT2
            $('.modal').on('shown.bs.modal', function() {
                $(this).find('.select2').select2({ dropdownParent: $(this), width: '100%' });
            });

            // 2. FUNCIONES DE CARGA DINÁMICA
            function cargarDoctoresTratamientos(form, idCon, idDocPre, idTratPre) {
                if(!idCon) return;
                let sDoc = form.find('.select-doctor');
                let sTrat = form.find('.select-tratamiento');

                $.post(BASE_URL + 'ajax/citasA.php', {action: 'obtenerDoctoresPorConsultorio', id_consultorio: idCon})
                .done(function(r){
                    r = safeParse(r);
                    sDoc.empty().append('<option value="">Seleccione Doctor...</option>');
                    if(r.success && r.doctores) r.doctores.forEach(d => sDoc.append(`<option value="${d.id}">${d.nombre} ${d.apellido}</option>`));
                    sDoc.prop('disabled', false);
                    if(idDocPre) sDoc.val(idDocPre).trigger('change');
                });

                $.post(BASE_URL + 'ajax/citasA.php', {action: 'obtenerTratamientosPorConsultorio', id_consultorio: idCon})
                .done(function(r){
                    r = safeParse(r);
                    sTrat.empty().append('<option value="">Seleccione Tratamiento...</option>');
                    if(r.success && r.tratamientos) r.tratamientos.forEach(t => sTrat.append(`<option value="${t.id}">${t.nombre}</option>`));
                    sTrat.prop('disabled', false);
                    if(idTratPre) sTrat.val(idTratPre);
                });
            }

            function cargarAfiliaciones(form, idPac, idPagoPre, idCobPre) {
                let sPago = form.find('.select-tipo-pago');
                if(!idPac) return;
                $.post(BASE_URL + 'ajax/citasA.php', {action: 'obtenerAfiliacionesPaciente', id_paciente: idPac})
                .done(function(r){
                    r = safeParse(r);
                    sPago.empty().append('<option value="">Seleccione...</option>');
                    sPago.append('<option value="1" data-cobertura="">PARTICULAR</option>');
                    if(r.success && r.afiliaciones) r.afiliaciones.forEach(a => {
                        sPago.append(`<option value="${a.id_obra_social}" data-cobertura="${a.id}">${a.nombre_os}</option>`);
                    });
                    sPago.prop('disabled', false);
                    if(idPagoPre) sPago.val(idPagoPre).trigger('change');
                });
            }

            function cargarHorarios(form, horaPre) {
                let doc = form.find('.select-doctor').val();
                let fec = form.find('.input-fecha').val();
                let sHora = form.find('.select-hora-inicio');
                if(!doc || !fec) return;
                $.post(BASE_URL + 'ajax/citasA.php', {action:'obtenerHorariosDisponibles', id_doctor:doc, fecha:fec, id_consultorio:ID_CONSULTORIO_SEC})
                .done(function(r){
                    r = safeParse(r);
                    sHora.empty().append('<option value="">Seleccione...</option>');
                    if(r.success && r.horarios) {
                        r.horarios.forEach(h => sHora.append(`<option value="${h}">${h}</option>`));
                        if(horaPre) sHora.append(`<option value="${horaPre}">${horaPre} (Actual)</option>`).val(horaPre);
                        sHora.prop('disabled', false);
                    }
                });
            }

            // 3. LISTENERS
            if(ID_CONSULTORIO_SEC) {
                cargarDoctoresTratamientos($('#modal-nueva-cita form'), ID_CONSULTORIO_SEC);
            }

            $('body').on('change', '.select-paciente-nueva-cita, #id_paciente_editar', function(){ cargarAfiliaciones($(this).closest('form'), $(this).val()); });
            
            $('body').on('change', '.select-tipo-pago', function(){ 
                let cob = $(this).find('option:selected').data('cobertura');
                $(this).closest('form').find('.input-cobertura-aplicada').val(cob || ''); 
            });

            $('body').on('change', '.select-doctor, .input-fecha', function(){ cargarHorarios($(this).closest('form')); });

            $('body').on('change', '.select-hora-inicio', function(){
                let h = $(this).val();
                if(h){
                    let d = new Date("2000-01-01 " + h);
                    d.setMinutes(d.getMinutes() + 30);
                    let hFin = d.toTimeString().substring(0,5);
                    $(this).closest('form').find('.input-hora-fin, #hora_fin_editar').val(hFin);
                }
            });

            // 4. FULLCALENDAR
            $('#calendario-citas-secretario').fullCalendar({
                header: {left:'prev,next today', center:'title', right:'month,agendaWeek,agendaDay'},
                locale: 'es',
                allDaySlot: false,
                slotEventOverlap: false,
                events: {
                    url: BASE_URL + 'ajax/citasA.php',
                    data: function() { return { action: 'obtenerCitasAdmin' }; }
                },
                eventRender: function(event, element) {
                    let p = event.extendedProps || event;
                    let hInicio = moment(event.start).format('HH:mm');
                    let html = `
                        <div class="fc-content">
                            <span class="fc-time"><i class="fa fa-clock-o"></i> ${hInicio}</span>
                            <span class="fc-title">${p.paciente || event.title}</span>
                            <div class="event-details-box">
                                <span><i class="fa fa-stethoscope"></i> ${p.motivo || 'Sin Motivo'}</span><br>
                                <span><i class="fa fa-user-md"></i> ${p.doctor || 'Sin Doctor'}</span>
                            </div>
                        </div>
                    `;
                    element.find('.fc-content').html(html);
                },
                eventClick: function(calEvent) {
                    let m = $('#modal-detalle-cita'), p = calEvent.extendedProps || calEvent;
                    m.find('#detalle-paciente').text(p.paciente);
                    m.find('#detalle-doctor').text(p.doctor);
                    m.find('#detalle-consultorio').text(p.consultorio);
                    m.find('#detalle-fecha').text(moment(calEvent.start).format('DD/MM/YYYY HH:mm'));
                    m.find('#detalle-motivo').text(p.motivo);
                    
                    $('.btn-editar-cita, .btn-cancelar-cita').data('id-cita', calEvent.id);
                    
                    $('#detalle-cobertura').text('Cargando...');
                    $.post(BASE_URL + 'ajax/citasA.php', {action:'obtenerNombreCobertura', id_cita:calEvent.id})
                    .done(r => { 
                        r = safeParse(r);
                        $('#detalle-cobertura').text(r.nombre || 'Particular'); 
                    });
                    m.modal('show');
                }
            });

            // 5. ACCIONES DE BOTONES

            // --- BOTÓN EDITAR ---
            $('.btn-editar-cita').on('click', function() {
                let id = $(this).data('id-cita');
                $.post(BASE_URL + 'ajax/citasA.php', {action:'obtenerDetallesCita', id_cita:id}).done(function(r){
                    r = safeParse(r);
                    if(r.success){
                        let c = r.datos, mod = $('#modal-editar-cita'), f = mod.find('form');
                        mod.find('#id_cita_editar').val(c.id);
                        mod.find('#paciente_editar_nombre').val(c.nyaP || c.paciente_nombre);
                        mod.find('#id_paciente_editar').val(c.id_paciente);
                        mod.find('#fecha_editar').val(moment(c.inicio).format('YYYY-MM-DD'));
                        cargarDoctoresTratamientos(f, c.id_consultorio, c.id_doctor, c.id_tratamiento);
                        setTimeout(() => { 
                            cargarAfiliaciones(f, c.id_paciente, c.id_tipo_pago, c.id_cobertura_aplicada); 
                            cargarHorarios(f, moment(c.inicio).format('HH:mm')); 
                        }, 500);
                        $('#modal-detalle-cita').modal('hide'); 
                        mod.modal('show');
                    }
                });
            });

            // --- BOTÓN CANCELAR (ESTILO ADMIN CON SWEETALERT2) ---
            $('.btn-cancelar-cita').on('click', function () {
                var idCita = $(this).data('id-cita');
                
                if (typeof Swal === 'undefined') {
                    // Fallback por si no está cargado SweetAlert2
                    let mot = prompt("Esta acción es irreversible. Ingrese el motivo de cancelación:");
                    if(mot) ejecutarCancelacion(idCita, mot);
                    return;
                }

                Swal.fire({
                    title: '¿Cancelar Cita?',
                    text: 'Esta acción no se puede deshacer y marcará la cita como cancelada.',
                    icon: 'warning',
                    input: 'textarea',
                    inputLabel: 'Indique el motivo de cancelación (obligatorio):',
                    inputPlaceholder: 'Escriba aquí el motivo...',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, cancelar cita',
                    cancelButtonText: 'No, mantener',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'Debe ingresar un motivo para cancelar la cita';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        ejecutarCancelacion(idCita, result.value);
                    }
                });
            });

            function ejecutarCancelacion(id, motivo) {
                $.post(BASE_URL + 'ajax/citasA.php', {
                    action: 'cancelarCita',
                    id_cita: id,
                    motivo: motivo
                }).done(function(r) {
                    r = safeParse(r);
                    if (r.success) {
                        if(typeof Swal !== 'undefined') {
                            Swal.fire('¡Cancelada!', 'La cita ha sido cancelada correctamente.', 'success');
                        }
                        $('#calendario-citas-secretario').fullCalendar('refetchEvents');
                        $('#modal-detalle-cita').modal('hide');
                    } else {
                        if(typeof Swal !== 'undefined') {
                            Swal.fire('Error', r.error || 'No se pudo cancelar la cita.', 'error');
                        } else {
                            alert(r.error || 'Error al cancelar');
                        }
                    }
                });
            }
        });
    }
});
</script>
<?php 
$js_footer = ob_get_clean(); 
echo $js_footer; 
?>